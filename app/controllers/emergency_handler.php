<?php
session_start();
require_once 'mysql_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['latitude']) && isset($data['longitude'])) {
        $latitude = mysqli_real_escape_string($conn, $data['latitude']);
        $longitude = mysqli_real_escape_string($conn, $data['longitude']);
        $timestamp = date('Y-m-d H:i:s');
        
        // Create table if not exists
        $createTable = "CREATE TABLE IF NOT EXISTS emergency_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            latitude DECIMAL(10,8),
            longitude DECIMAL(11,8),
            timestamp DATETIME,
            status VARCHAR(20) DEFAULT 'active'
        )";
        
        mysqli_query($conn, $createTable);
        
        $sql = "INSERT INTO emergency_alerts (latitude, longitude, timestamp) 
                VALUES ('$latitude', '$longitude', '$timestamp')";
        
        if (mysqli_query($conn, $sql)) {
            $alert_id = mysqli_insert_id($conn);
            
            // Create emergency response record
            $createResponseTable = "CREATE TABLE IF NOT EXISTS emergency_responses (
                id INT AUTO_INCREMENT PRIMARY KEY,
                alert_id INT,
                notified_time DATETIME DEFAULT NULL,
                dispatched_time DATETIME DEFAULT NULL,
                arrived_time DATETIME DEFAULT NULL,
                resolved_time DATETIME DEFAULT NULL,
                case_resolved BOOLEAN DEFAULT 0,
                FOREIGN KEY (alert_id) REFERENCES emergency_alerts(id)
            )";
            
            mysqli_query($conn, $createResponseTable);
            
            // Insert new response record with current time as notified_time and dispatched_time
            $responseSQL = "INSERT INTO emergency_responses (alert_id, notified_time, dispatched_time) VALUES ($alert_id, '$timestamp', '$timestamp')";
            mysqli_query($conn, $responseSQL);
            
            echo json_encode([
                'success' => true,
                'message' => 'Emergency alert sent successfully',
                'location_url' => "https://www.google.com/maps?q=$latitude,$longitude"
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . mysqli_error($conn)
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid location data'
        ]);
    }
}
?>