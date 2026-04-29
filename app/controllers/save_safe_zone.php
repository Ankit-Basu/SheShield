<?php
require_once 'config.php';

header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data format']);
    exit;
}

// Validate required fields
if (!isset($data['coordinates']) || !isset($data['description']) || !isset($data['timeActive'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

// Parse coordinates
$coordinates = json_decode($data['coordinates'], true);
if (!$coordinates || !isset($coordinates['type'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid coordinates format']);
    exit;
}

// Get center point based on shape type
$center_lat = 0;
$center_lng = 0;

if ($coordinates['type'] === 'circle') {
    if (!isset($coordinates['center']) || !isset($coordinates['radius'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid circle format']);
        exit;
    }
    $center_lat = $coordinates['center'][0];
    $center_lng = $coordinates['center'][1];
} else if ($coordinates['type'] === 'polygon') {
    if (!isset($coordinates['points']) || !is_array($coordinates['points']) || count($coordinates['points']) < 3) {
        echo json_encode(['success' => false, 'message' => 'Invalid polygon format']);
        exit;
    }
    // Calculate center point of the polygon
    $lat_sum = 0;
    $lng_sum = 0;
    foreach ($coordinates['points'] as $point) {
        $lat_sum += $point[0];
        $lng_sum += $point[1];
    }
    $center_lat = $lat_sum / count($coordinates['points']);
    $center_lng = $lng_sum / count($coordinates['points']);
}

try {
    // Insert into safe_spaces table
    if (!$conn) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO safe_spaces (user_id, latitude, longitude, description, time_active) VALUES (?, ?, ?, ?, ?)");
    
    // Bind parameters including user_id
    $stmt->bind_param("iddsi", 
        $user_id,
        $center_lat,
        $center_lng,
        $data['description'],
        $data['timeActive']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

$conn->close();
?>