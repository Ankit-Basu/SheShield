<?php
require_once __DIR__ . '/../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

error_reporting(E_ALL);
ini_set('display_errors', 1);
error_log("Starting signup process...");

$cfgPath = __DIR__ . '/../app/config/database.php';
if (!file_exists($cfgPath)) $cfgPath = __DIR__ . '/../config/database.php';
$modelPath = __DIR__ . '/../models/User.php';
$sessionPath = __DIR__ . '/../app/middleware/session.php';

require_once $cfgPath;
require_once $modelPath;
if (file_exists($sessionPath)) require_once $sessionPath;

try {
    // Get raw posted data
    $data = json_decode(file_get_contents("php://input"));
    error_log("Received data: " . print_r($data, true));
    
    $response = array();

    // Validate required fields
    if(empty($data->first_name) || empty($data->last_name) || empty($data->email) || 
       empty($data->password)) {
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
    $user->phone = !empty($data->phone) ? htmlspecialchars(strip_tags($data->phone)) : '';
    $user->password = password_hash($data->password, PASSWORD_DEFAULT);
    $emergencyName = $data->emergency_contact_name ?? $data->emergency_name ?? null;
    $emergencyPhone = $data->emergency_contact_phone ?? $data->emergency_phone ?? null;
    $user->emergency_contact_name = !empty($emergencyName) ? htmlspecialchars(strip_tags($emergencyName)) : null;
    $user->emergency_contact_phone = !empty($emergencyPhone) ? htmlspecialchars(strip_tags($emergencyPhone)) : null;

    // Check if email exists
    if($user->emailExists()) {
        throw new Exception("Email already exists");
    }

    // Create the user
    if($user->create()) {
        error_log("User created successfully with ID: " . $user->id);
        
        // Set session variables
        if (class_exists('Session')) {
            Session::set('logged_in', true);
            Session::set('user_id', $user->id);
            Session::set('email', $user->email);
            Session::set('first_name', $user->first_name);
            Session::set('last_name', $user->last_name);
            Session::set('user_name', trim($user->first_name . ' ' . $user->last_name));
            Session::set('profile_image', null);
            Session::set('is_admin', false);
        } else {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user->id;
            $_SESSION['email'] = $user->email;
            $_SESSION['first_name'] = $user->first_name;
            $_SESSION['last_name'] = $user->last_name;
            $_SESSION['user_name'] = trim($user->first_name . ' ' . $user->last_name);
            $_SESSION['profile_image'] = null;
            $_SESSION['is_admin'] = false;
        }
        
        $response["status"] = "success";
        $response["message"] = "User created successfully";
        $response["user"] = array(
            "id" => $user->id,
            "email" => $user->email,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "profile_image" => null
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
