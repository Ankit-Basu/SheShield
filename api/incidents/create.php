<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
header("Content-Type: application/json; charset=UTF-8");

$cfgPath = __DIR__ . '/../../app/config/database.php';
include_once $cfgPath;
include_once __DIR__ . '/../../app/models/Incident.php';

// Accepts multipart form data (with evidence files) or a JSON body.
$data = $_POST ?: (json_decode(file_get_contents("php://input"), true) ?: []);
$isAnonymous = !empty($data['is_anonymous']);

if (!isset($_SESSION['logged_in']) && !$isAnonymous) {
    http_response_code(401);
    echo json_encode([
        "status" => "error",
        "message" => "Please sign in or submit anonymously.",
    ]);
    exit();
}

$type = $data['type'] ?? $data['incident_type'] ?? '';
$when = $data['incident_date'] ?? $data['date_time'] ?? '';

if ($type === '' || empty($data['description']) || empty($data['location']) || $when === '') {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Please complete the incident type, date, location and description.",
    ]);
    exit();
}

$timestamp = strtotime($when);
if ($timestamp === false) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "That date and time is not valid."]);
    exit();
}

$severities = ['low', 'medium', 'high'];
$severity = in_array($data['severity'] ?? '', $severities, true) ? $data['severity'] : 'low';

try {
    $database = new Database();
    $db = $database->getConnection();
    $incident = new Incident($db);

    $incident->type = $type;
    $incident->description = $data['description'];
    $incident->location = $data['location'];
    $incident->incident_date = date('Y-m-d H:i:s', $timestamp);
    $incident->is_anonymous = $isAnonymous ? 1 : 0;
    // An anonymous report must not be traceable back to the signed-in account.
    $incident->user_id = (!$isAnonymous && isset($_SESSION['user_id'])) ? $_SESSION['user_id'] : null;
    $incident->status = 'pending';
    $incident->severity = $severity;
    $incident->evidence_files = '';

    if (isset($_FILES['evidence']) && !empty($_FILES['evidence']['name'][0])) {
        $incident->evidence_files = $incident->uploadEvidence($_FILES['evidence']);
    }

    if ($incident->create()) {
        http_response_code(201);
        echo json_encode([
            "status" => "success",
            "message" => "Incident reported successfully",
            "report_id" => 'SS-' . date('Y') . '-' . str_pad((string)$incident->id, 4, '0', STR_PAD_LEFT),
            "id" => (int)$incident->id,
        ]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Unable to save the report. Please try again."]);
    }
} catch (Exception $e) {
    error_log('Incident create error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
