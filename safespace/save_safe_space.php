<?php
require_once __DIR__ . '/../app/models/mysqli_db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
    $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);
    $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
    $timeActive = filter_input(INPUT_POST, 'timeActive', FILTER_VALIDATE_INT);
    require_once __DIR__ . '/../app/middleware/session_bootstrap.php';
    configure_session_storage();
    session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

    if ($latitude && $longitude && $description && $timeActive) {
        $sql = "INSERT INTO safe_spaces (user_id, latitude, longitude, description, time_active) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iddsi", $user_id, $latitude, $longitude, $description, $timeActive);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Safe space marked successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error marking safe space']);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
$conn->close();
