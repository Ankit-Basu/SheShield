<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(E_ALL);
ini_set('display_errors', 0);

// Try to load from MVC structure, fallback to old paths
$configPath = __DIR__ . '/../../app/config/database.php';
$modelPath = __DIR__ . '/../../models/User.php';
$sessionPath = __DIR__ . '/../../app/middleware/session.php';

if (!file_exists($configPath)) $configPath = __DIR__ . '/../../config/database.php';
if (!file_exists($sessionPath)) $sessionPath = __DIR__ . '/../../utils/session.php';

if (file_exists($configPath)) require_once $configPath;
if (file_exists($modelPath)) require_once $modelPath;
if (file_exists($sessionPath)) require_once $sessionPath;

$data = json_decode(file_get_contents("php://input"));
$response = array();

function base64url_decode_json_segment($segment) {
    $decoded = base64_decode(strtr($segment, '-_', '+/') . str_repeat('=', (4 - strlen($segment) % 4) % 4), true);
    if ($decoded === false) {
        throw new Exception("Invalid Firebase sign-in session");
    }
    return $decoded;
}

function decode_firebase_id_token_claims($idToken) {
    $parts = explode('.', $idToken);
    if (count($parts) < 2) {
        throw new Exception("Invalid Firebase sign-in session");
    }

    $payload = json_decode(base64url_decode_json_segment($parts[1]), true);
    if (!is_array($payload)) {
        throw new Exception("Invalid Firebase sign-in session");
    }

    return $payload;
}

try {
    // Support both field name formats from frontend
    $idToken = $data->idToken ?? $data->id_token ?? null;
    $email = isset($data->email) ? filter_var($data->email, FILTER_SANITIZE_EMAIL) : null;
    $displayName = trim($data->name ?? $data->display_name ?? '');
    $photoUrl = $data->photo ?? $data->photo_url ?? null;

    if (empty($idToken) || empty($email)) {
        throw new Exception("Missing required fields");
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email address");
    }

    $photoUrl = filter_var($photoUrl, FILTER_VALIDATE_URL) ? $photoUrl : null;

    $tokenClaims = decode_firebase_id_token_claims($idToken);
    $projectId = 'sheshield-fd94e';
    $tokenEmail = $tokenClaims['email'] ?? null;
    $tokenAudience = $tokenClaims['aud'] ?? '';
    $tokenIssuer = $tokenClaims['iss'] ?? '';
    $tokenExpiry = isset($tokenClaims['exp']) ? (int)$tokenClaims['exp'] : 0;

    if ($tokenExpiry > 0 && $tokenExpiry < time() - 60) {
        throw new Exception("Firebase sign-in session expired");
    }

    if ($tokenAudience !== $projectId || $tokenIssuer !== "https://securetoken.google.com/$projectId") {
        throw new Exception("Firebase project mismatch");
    }

    if (!$tokenEmail || strcasecmp($tokenEmail, $email) !== 0) {
        throw new Exception("Email mismatch between Firebase and request");
    }

    if ($displayName === '' && !empty($tokenClaims['name'])) {
        $displayName = trim($tokenClaims['name']);
    }

    if ($displayName === '') {
        $displayName = strstr($email, '@', true) ?: 'User';
    }

    if (!$photoUrl && !empty($tokenClaims['picture'])) {
        $claimPhoto = filter_var($tokenClaims['picture'], FILTER_VALIDATE_URL);
        $photoUrl = $claimPhoto ?: null;
    }

    // Connect to database
    $database = new Database();
    $db = $database->getConnection();

    if (!$db) {
        throw new Exception("Database connection failed");
    }

    // Check if user exists with this email
    $stmt = $db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    $nameParts = preg_split('/\s+/', $displayName, 2);
    $firstName = htmlspecialchars(strip_tags($nameParts[0] ?? 'User'));
    $lastName = isset($nameParts[1]) ? htmlspecialchars(strip_tags($nameParts[1])) : '';
    $fullName = trim($firstName . ' ' . $lastName);

    if ($existingUser) {
        // User exists — log them in
        $userId = $existingUser['id'];
        if (empty($existingUser['first_name']) || empty($existingUser['last_name']) || empty($existingUser['full_name'])) {
            $stmt = $db->prepare("UPDATE users
                                  SET first_name = CASE WHEN first_name IS NULL OR first_name = '' THEN :first_name ELSE first_name END,
                                      last_name = CASE WHEN last_name IS NULL OR last_name = '' THEN :last_name ELSE last_name END,
                                      full_name = CASE WHEN full_name IS NULL OR full_name = '' THEN :full_name ELSE full_name END
                                  WHERE id = :id");
            $stmt->execute([
                ':first_name' => $firstName,
                ':last_name' => $lastName,
                ':full_name' => $fullName,
                ':id' => $userId
            ]);
        }
        error_log("Firebase login: Existing user found with ID: " . $userId);
    } else {
        // User doesn't exist — auto-register from Google profile
        $nameParts = explode(' ', $displayName, 2);
        $firstName = htmlspecialchars(strip_tags($nameParts[0]));
        $lastName = isset($nameParts[1]) ? htmlspecialchars(strip_tags($nameParts[1])) : '';
        $safeEmail = filter_var($email, FILTER_SANITIZE_EMAIL);
        $randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name, full_name, phone) 
                              VALUES (:username, :email, :password, :first_name, :last_name, :full_name, '')");
        $stmt->bindParam(':username', $safeEmail);
        $stmt->bindParam(':email', $safeEmail);
        $stmt->bindParam(':password', $randomPassword);
        $stmt->bindParam(':first_name', $firstName);
        $stmt->bindParam(':last_name', $lastName);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->execute();
        $userId = $db->lastInsertId();
        error_log("Firebase login: New user created with ID: " . $userId);
    }

    // Fetch full user details
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        throw new Exception("Unable to load user after login");
    }

    if ($photoUrl) {
        $stmt = $db->prepare("SELECT id FROM profile_images WHERE user_id = :user_id AND image_path = :image_path AND status = 'active' LIMIT 1");
        $stmt->execute([':user_id' => $user['id'], ':image_path' => $photoUrl]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            $stmt = $db->prepare("UPDATE profile_images SET status = 'deleted' WHERE user_id = :user_id AND status = 'active'");
            $stmt->execute([':user_id' => $user['id']]);
            $stmt = $db->prepare("INSERT INTO profile_images (user_id, image_path, status, created_at) VALUES (:user_id, :image_path, 'active', NOW())");
            $stmt->execute([':user_id' => $user['id'], ':image_path' => $photoUrl]);
        }
    }

    // Set session variables
    Session::set('logged_in', true);
    Session::set('user_id', $user['id']);
    Session::set('email', $user['email']);
    Session::set('first_name', $user['first_name'] ?: $firstName);
    Session::set('last_name', $user['last_name'] ?? $lastName);
    Session::set('user_name', trim(($user['first_name'] ?: $firstName) . ' ' . ($user['last_name'] ?? $lastName)));
    Session::set('is_admin', false);
    Session::set('profile_image', $photoUrl);

    $response['status'] = 'success';
    $response['message'] = 'Google login successful';
    $response['redirect'] = '../views/pages/dashboard.php';
    $response['user'] = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'] ?: $firstName,
        'last_name' => $user['last_name'] ?? $lastName,
        'profile_image' => $photoUrl
    ];
    http_response_code(200);

} catch (Throwable $e) {
    error_log("Firebase login error: " . $e->getMessage());
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);
