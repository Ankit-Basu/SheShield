<?php
session_start();
// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: /dashboard.php');
    exit();
}
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Starting signup process...");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

try {
    // Get raw posted data
    $data = json_decode(file_get_contents("php://input"));
    error_log("Received data: " . print_r($data, true));
    
    $response = array();

    // Validate required fields
    if(empty($data->first_name) || empty($data->last_name) || empty($data->email) || 
       empty($data->password) || empty($data->phone)) {
        throw new Exception("Missing required fields");
    }

    // Validate email format
    if(!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Validate password strength
    if(strlen($data->password) < 8) {
        throw new Exception("Password must be at least 8 characters long");
    }

    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if(!$db) {
        error_log("Failed to get database connection");
        throw new Exception("Database connection failed");
    }

    $user = new User($db);

    // Set user properties with proper sanitization
    $user->first_name = htmlspecialchars(strip_tags($data->first_name));
    $user->last_name = htmlspecialchars(strip_tags($data->last_name));
    $user->email = filter_var($data->email, FILTER_SANITIZE_EMAIL);
    $user->phone = htmlspecialchars(strip_tags($data->phone));
    $user->password = password_hash($data->password, PASSWORD_DEFAULT);
    $user->emergency_contact_name = !empty($data->emergency_contact_name) ? htmlspecialchars(strip_tags($data->emergency_contact_name)) : null;
    $user->emergency_contact_phone = !empty($data->emergency_contact_phone) ? htmlspecialchars(strip_tags($data->emergency_contact_phone)) : null;

    // Check if email exists
    if($user->emailExists()) {
        throw new Exception("Email already exists");
    }

    // Create the user
    if($user->create()) {
        error_log("User created successfully with ID: " . $user->id);
        
        // Set session variables
        $_SESSION['user_id'] = $user->id;
        $_SESSION['email'] = $user->email;
        $_SESSION['first_name'] = $user->first_name;
        
        $response["status"] = "success";
        $response["message"] = "User created successfully";
        $response["user"] = array(
            "id" => $user->id,
            "email" => $user->email,
            "first_name" => $user->first_name
        );
        http_response_code(201);
    } else {
        throw new Exception("Unable to create user");
    }

} catch(Exception $e) {
    error_log("Signup error: " . $e->getMessage());
    $response["status"] = "error";
    $response["message"] = $e->getMessage();
    http_response_code(400);
}

echo json_encode($response);