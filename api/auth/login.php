<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
header("Content-Type: application/json; charset=UTF-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cfgPath = __DIR__ . '/../../app/config/database.php';
if (!file_exists($cfgPath)) $cfgPath = __DIR__ . '/../../config/database.php';
$modelPath = __DIR__ . '/../../models/User.php';
$sessionPath = __DIR__ . '/../../app/middleware/session.php';

include_once $cfgPath;
include_once $modelPath;
if (file_exists($sessionPath)) include_once $sessionPath;

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
        Session::set('logged_in', true);
        Session::set('user_id', $user->id);
        Session::set('email', $user->email);
        Session::set('first_name', $user->first_name);
        Session::set('last_name', $user->last_name);
        Session::set('user_name', trim($user->first_name . ' ' . $user->last_name));
        Session::set('is_admin', $user->is_admin ?? false);
        Session::set('profile_image', null);
        
        $response["status"] = "success";
        $response["message"] = "Login successful";
        $response["user"] = array(
            "id" => $user->id,
            "email" => $user->email,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "is_admin" => $user->is_admin ?? false,
            "profile_image" => null
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
