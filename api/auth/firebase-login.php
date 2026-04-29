<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(E_ALL);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../utils/session.php';

$data = json_decode(file_get_contents("php://input"));
$response = array();

try {
    if (empty($data->id_token) || empty($data->email)) {
        throw new Exception("Missing required fields");
    }

    // Verify the Firebase ID token via Google's tokeninfo API
    $idToken = $data->id_token;
    $verifyUrl = "https://oauth2.googleapis.com/tokeninfo?id_token=" . urlencode($idToken);
    
    $ch = curl_init($verifyUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $tokenInfoRaw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log("Google token verification failed with HTTP code: " . $httpCode);
        throw new Exception("Invalid Google token");
    }

    $tokenInfo = json_decode($tokenInfoRaw, true);
    
    if (empty($tokenInfo['email'])) {
        throw new Exception("Could not verify email from Google token");
    }

    // Verify that the email in the token matches what was sent
    if ($tokenInfo['email'] !== $data->email) {
        throw new Exception("Email mismatch between token and request");
    }

    // Connect to database
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Check if user exists with this email
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $data->email);
    $stmt->execute();
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        // User exists — log them in
        $userId = $existingUser['id'];
        error_log("Firebase login: Existing user found with ID: " . $userId);
    } else {
        // User doesn't exist — auto-register from Google profile
        $nameParts = explode(' ', $data->display_name ?? 'User', 2);
        $firstName = htmlspecialchars(strip_tags($nameParts[0]));
        $lastName = isset($nameParts[1]) ? htmlspecialchars(strip_tags($nameParts[1])) : '';
        $email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone) 
                              VALUES (:username, :email, :password, :first_name, :last_name, '')");
        $stmt->bindParam(':username', $email);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $randomPassword);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->execute();
        $userId = $db->lastInsertId();
        error_log("Firebase login: New user created with ID: " . $userId);
    }

    // Fetch full user details
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Set session variables
    Session::set('logged_in', true);
    Session::set('user_id', $user['id']);
    Session::set('email', $user['email']);
    Session::set('first_name', $user['first_name']);
    Session::set('last_name', $user['last_name'] ?? '');
    Session::set('is_admin', false);
    Session::set('profile_image', $data->photo_url ?? null);

    $response['status'] = 'success';
    $response['message'] = 'Google login successful';
    $response['redirect'] = '../dashboard.php';
    $response['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'] ?? ''
    ];
    http_response_code(200);

} catch (Exception $e) {
    error_log("Firebase login error: " . $e->getMessage());
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
