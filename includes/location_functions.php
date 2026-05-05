<?php
// Location-related functions
require_once __DIR__ . '/../app/models/mysqli_db.php';

/**
 * Get safe spaces from the database
 * 
 * @return array Array of safe spaces
 */
function get_safe_spaces() {
    global $conn;
    $sql = "SELECT * FROM safe_spaces ORDER BY timestamp DESC";
    $result = $conn->query($sql);
    
    $spaces = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $spaces[] = $row;
        }
    }
    
    return $spaces;
}

/**
 * Save a safe space to the database
 * 
 * @param float $latitude Latitude coordinate
 * @param float $longitude Longitude coordinate
 * @param string $description Description of the safe space
 * @param int $timeActive Time in hours the safe space remains active
 * @param int $userId User ID who created the safe space
 * @return bool True if successful, false otherwise
 */
function save_safe_space($latitude, $longitude, $description, $timeActive, $userId = null) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO safe_spaces (latitude, longitude, description, time_active, user_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ddsii", $latitude, $longitude, $description, $timeActive, $userId);
    
    return $stmt->execute();
}

/**
 * Save a safe zone (polygon) to the database
 * 
 * @param string $polygonData JSON string of polygon coordinates
 * @param string $description Description of the safe zone
 * @return bool True if successful, false otherwise
 */
function save_safe_zone($polygonData, $description) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO safe_zone (polygon_data, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $polygonData, $description);
    
    return $stmt->execute();
}
?>
