<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../app/models/mysqli_db.php';

$sql = "SELECT * FROM incidents WHERE status='pending' ORDER BY created_at DESC";
$result = $conn->query($sql);

$incidents = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        // Get coordinates based on location name from lpuLocations
        $location = strtolower($row['location']);
        $sql_locations = "SELECT name, latitude, longitude FROM locations WHERE is_active = TRUE";
        $result_locations = $conn->query($sql_locations);
        $coordinates = [];
        
        if ($result_locations->num_rows > 0) {
            while($loc = $result_locations->fetch_assoc()) {
                $coordinates[strtolower($loc['name'])] = ['lat' => (float)$loc['latitude'], 'lng' => (float)$loc['longitude']];
            }
        }

        foreach ($coordinates as $key => $coord) {
            if (strpos($location, $key) !== false) {
                $row['latitude'] = $coord['lat'];
                $row['longitude'] = $coord['lng'];
                break;
            }
        }

        // If no match found, use a default location
        if (!isset($row['latitude'])) {
            $row['latitude'] = 31.2533; // Default to Block 32
            $row['longitude'] = 75.7050;
        }

        $incidents[] = $row;
    }
}

echo json_encode($incidents);
$conn->close();
?>
