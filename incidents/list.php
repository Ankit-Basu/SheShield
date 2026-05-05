<?php
require_once __DIR__ . '/../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
header("Content-Type: application/json; charset=UTF-8");

$cfgPath = __DIR__ . '/../app/config/database.php';
if (!file_exists($cfgPath)) $cfgPath = __DIR__ . '/../config/database.php';
include_once $cfgPath;
include_once __DIR__ . '/../models/Incident.php';

// Check if user is logged in
if(!isset($_SESSION['logged_in'])) {
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

$response = array();

// Get user's incidents
$stmt = $incident->getUserIncidents($_SESSION['user_id']);
$num = $stmt->rowCount();

if($num > 0) {
    $incidents_arr = array();
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $incident_item = array(
            "id" => $id,
            "type" => $type,
            "description" => $description,
            "location" => $location,
            "incident_date" => $incident_date,
            "status" => $status,
            "created_at" => $created_at,
            "is_anonymous" => $is_anonymous,
            "evidence_files" => $evidence_files ? explode(',', $evidence_files) : []
        );
        array_push($incidents_arr, $incident_item);
    }
    
    $response["status"] = "success";
    $response["incidents"] = $incidents_arr;
    http_response_code(200);
} else {
    $response["status"] = "success";
    $response["incidents"] = [];
    $response["message"] = "No incidents found";
    http_response_code(200);
}

echo json_encode($response);
?>
