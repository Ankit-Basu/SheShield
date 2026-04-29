<?php
require_once 'mysql_db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['coordinates']) || !isset($_POST['description']) || !isset($_POST['timeActive'])) {
        throw new Exception('Missing required fields');
    }

    $coordinates = json_decode($_POST['coordinates'], true);
    if (!$coordinates || !is_array($coordinates) || count($coordinates) < 3) {
        throw new Exception('Invalid coordinates format');
    }

    // Calculate center point of the polygon
    $lat_sum = 0;
    $lng_sum = 0;
    foreach ($coordinates as $coord) {
        $lat_sum += $coord[0];
        $lng_sum += $coord[1];
    }
    $center_lat = $lat_sum / count($coordinates);
    $center_lng = $lng_sum / count($coordinates);

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO safe_spaces (latitude, longitude, description, time_active, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ddsi", 
        $center_lat,
        $center_lng,
        $_POST['description'],
        $_POST['timeActive']
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Database error: ' . $stmt->error);
    }

    $stmt->close();
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
