<?php
require_once __DIR__ . '/../app/models/mysqli_db.php';
header('Content-Type: application/json');

try {
    // Get JSON data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['latitude']) || !isset($data['longitude'])) {
        throw new Exception('Missing location data');
    }

    $latitude = $data['latitude'];
    $longitude = $data['longitude'];
    $timestamp = date('Y-m-d H:i:s');

    // Insert into shared_locations table
    $stmt = $conn->prepare("INSERT INTO shared_locations (latitude, longitude, timestamp, status) VALUES (?, ?, ?, 'active')");
    $stmt->bind_param("dds", $latitude, $longitude, $timestamp);

    if ($stmt->execute()) {
        // Get the location ID
        $location_id = $conn->insert_id;
        
        // Generate shareable link
        $share_url = "https://" . $_SERVER['HTTP_HOST'] . "/view_location.php?id=" . $location_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'Location shared successfully',
            'share_url' => $share_url,
            'location_id' => $location_id
        ]);
    } else {
        throw new Exception('Failed to save location');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
