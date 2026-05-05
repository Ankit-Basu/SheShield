<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
header("Content-Type: application/json; charset=UTF-8");

$cfgPath = __DIR__ . '/../../app/config/database.php';
if (!file_exists($cfgPath)) $cfgPath = __DIR__ . '/../../config/database.php';
$sessionPath = __DIR__ . '/../../app/middleware/session.php';
if (!file_exists($sessionPath)) $sessionPath = __DIR__ . '/../../utils/session.php';
include_once $cfgPath;
include_once __DIR__ . '/../../models/Incident.php';
include_once $sessionPath;

// Check if user is logged in and is admin
if (!Session::isLoggedIn() || !Session::get('is_admin')) {
    http_response_code(401);
    echo json_encode(array(
        "status" => "error",
        "message" => "Unauthorized access"
    ));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
$incident = new Incident($db);

// Get posted data
$data = json_decode(file_get_contents("php://input"));

$response = array();

if(!empty($data->id) && !empty($data->status)) {
    // Validate status
    $valid_statuses = ['pending', 'in_progress', 'resolved', 'closed'];
    if(!in_array($data->status, $valid_statuses)) {
        $response["status"] = "error";
        $response["message"] = "Invalid status value";
        http_response_code(400);
    } else {
        // Update the incident status
        if($incident->updateStatus($data->id, $data->status)) {
            $response["status"] = "success";
            $response["message"] = "Incident status updated successfully";
            http_response_code(200);

            // Log the status change
            $log_message = "Status updated to " . $data->status . " by admin " . Session::getUserName();
            // TODO: Implement logging system
            
            // Notify user if configured
            // TODO: Implement notification system
        } else {
            $response["status"] = "error";
            $response["message"] = "Unable to update incident status";
            http_response_code(500);
        }
    }
} else {
    $response["status"] = "error";
    $response["message"] = "Missing required data";
    http_response_code(400);
}

echo json_encode($response);
?>
