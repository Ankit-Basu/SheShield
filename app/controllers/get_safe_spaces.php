<?php
require_once __DIR__ . '/../models/mysqli_db.php';

header('Content-Type: application/json');

require_once __DIR__ . '/../middleware/session_bootstrap.php';
configure_session_storage();
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

// Get active safe spaces (not expired based on time_active) for the logged-in user
$sql = "SELECT ss.*, TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) as user_name 
FROM safe_spaces ss 
LEFT JOIN users u ON ss.user_id = u.id 
WHERE TIMESTAMPADD(HOUR, time_active, timestamp) > NOW() AND ss.user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$safe_spaces = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $safe_spaces[] = [
            'id' => $row['id'],
            'latitude' => (float)$row['latitude'],
            'longitude' => (float)$row['longitude'],
            'description' => $row['description'],
            'timestamp' => $row['timestamp'],
            'user_name' => $row['user_name'] ?? 'Anonymous'
        ];
    }
}
echo json_encode($safe_spaces);
$conn->close();
