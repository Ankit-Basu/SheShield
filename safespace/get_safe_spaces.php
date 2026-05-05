<?php
require_once __DIR__ . '/../app/models/mysqli_db.php';

header('Content-Type: application/json');

// Get active safe spaces (not expired based on time_active)
$sql = "SELECT ss.*, TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) as user_name 
FROM safe_spaces ss 
LEFT JOIN users u ON ss.user_id = u.id 
WHERE TIMESTAMPADD(HOUR, time_active, timestamp) > NOW()";

$result = $conn->query($sql);
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
