<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once __DIR__ . '/../config/database.php';
include_once __DIR__ . '/../models/User.php';
include_once __DIR__ . '/../utils/session.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$data = json_decode(file_get_contents("php://input"));
$response = array();

// Add debug logging
error_log("Login attempt for email: " . ($data->email ?? 'not set'));

if(!empty($data->email) && !empty($data->password)) {
    $user->email = $data->email;
    
    // Verify user credentials
    if($user->authenticate($data->password)) {
        // Set session variables
        // Include profile image handler
        require_once dirname(__DIR__) . '/includes/profile_image_handler.php';
        
        // Set session variables
        Session::set('logged_in', true);
        Session::set('user_id', $user->id);
        Session::set('email', $user->email);
        Session::set('first_name', $user->first_name);
        Session::set('last_name', $user->last_name);
        Session::set('is_admin', $user->is_admin ?? false);
        
        // Load and set profile image
        $profileImage = getProfileImage($user->id);
        Session::set('profile_image', $profileImage);
        
        $response["status"] = "success";
        $response["message"] = "Login successful";
        $response["user"] = array(
            "id" => $user->id,
            "email" => $user->email,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "is_admin" => $user->is_admin ?? false,
            "profile_image" => $profileImage
        );
        http_response_code(200);
    } else {
        $response["status"] = "error";
        $response["message"] = "Invalid email or password";
        http_response_code(401);
    }
} else {
    $response["status"] = "error";
    $response["message"] = "Missing email or password";
    http_response_code(400);
}

// Add debug logging
error_log("Login response: " . json_encode($response));

echo json_encode($response);