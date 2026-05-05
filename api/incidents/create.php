<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
header("Content-Type: application/json; charset=UTF-8");

$cfgPath = __DIR__ . '/../../app/config/database.php';
if (!file_exists($cfgPath)) $cfgPath = __DIR__ . '/../../config/database.php';
include_once $cfgPath;
include_once __DIR__ . '/../../models/Incident.php';

// Check if user is logged in for non-anonymous reports
if(!isset($_SESSION['logged_in']) && !isset($_POST['is_anonymous'])) {
    http_response_code(401);
    echo json_encode(array(
        "status" => "error",
        "message" => "Unauthorized. Please login or submit anonymously."
    ));
    exit();
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();
$incident = new Incident($db);

$response = array();

// Handle both form data and JSON input
$data = $_POST ?: json_decode(file_get_contents("php://input"), true);

if(
    (!empty($data['type']) || !empty($data['incident_type'])) &&
    !empty($data['description']) &&
    !empty($data['location']) &&
    (!empty($data['incident_date']) || !empty($data['date_time']))
) {
    // Set incident properties
    $incident->type = $data['type'] ?? $data['incident_type'];
    $incident->description = $data['description'];
    $incident->location = $data['location'];
    $incident->incident_date = $data['incident_date'] ?? $data['date_time'];
    $incident->is_anonymous = isset($data['is_anonymous']) ? 1 : 0;
    $incident->user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $incident->status = 'pending';

    // Handle file uploads if present
    if(isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
        $incident->evidence_files = $incident->uploadEvidence($_FILES['evidence']);
    }

    // Create the incident report
    if($incident->create()) {
        $response["status"] = "success";
        $response["message"] = "Incident reported successfully";
        http_response_code(201);

        // Send notification to admin if configured
        if(isset($incident->user_id)) {
            // TODO: Implement notification system
        }
    } else {
        $response["status"] = "error";
        $response["message"] = "Unable to create incident report";
        http_response_code(500);
    }
} else {
    $response["status"] = "error";
    $response["message"] = "Unable to create incident report. Data is incomplete";
    http_response_code(400);
}

echo json_encode($response);
?>
