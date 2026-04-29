<?php
header('Content-Type: application/json');
include 'db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['status']) || !isset($data['response_id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$status = $data['status'];
$response_id = $data['response_id'];
$time_column = $status . '_time';
$current_time = date('Y-m-d H:i:s');

// Validate response_id exists
$check_sql = "SELECT id FROM emergency_responses WHERE id = ?";
$check_stmt = mysqli_prepare($conn, $check_sql);
mysqli_stmt_bind_param($check_stmt, "i", $response_id);
mysqli_stmt_execute($check_stmt);
mysqli_stmt_store_result($check_stmt);

if (mysqli_stmt_num_rows($check_stmt) == 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid response ID']);
    mysqli_stmt_close($check_stmt);
    exit;
}
mysqli_stmt_close($check_stmt);

try {
    // Update emergency_responses
    $sql = "UPDATE emergency_responses SET $time_column = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }
    
    mysqli_stmt_bind_param($stmt, "si", $current_time, $response_id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
    echo json_encode(['success' => true, 'time' => $current_time]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} finally {
    mysqli_close($conn);
}