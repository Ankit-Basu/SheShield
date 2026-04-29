<?php
header('Content-Type: application/json');
require 'mysql_db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['response_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing response ID']);
    exit;
}

$response_id = $data['response_id'];

$stmt = $conn->prepare("UPDATE emergency_responses SET case_resolved = 1 WHERE id = ?");
if ($stmt && $stmt->bind_param("i", $response_id) && $stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
$stmt->close();
$conn->close();
$conn->close();