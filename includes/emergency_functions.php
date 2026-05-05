<?php
// Emergency-related functions
require_once __DIR__ . '/../app/models/mysqli_db.php';
require_once __DIR__ . '/sms_functions.php';

/**
 * Handle emergency alert
 * 
 * @param float $latitude Latitude coordinate
 * @param float $longitude Longitude coordinate
 * @return array Response with status and message
 */
function handle_emergency($latitude, $longitude) {
    global $conn;
    
    try {
        // Insert emergency alert
        $stmt = $conn->prepare("INSERT INTO emergency_alerts (latitude, longitude, timestamp, status) VALUES (?, ?, NOW(), 'active')");
        $stmt->bind_param("dd", $latitude, $longitude);
        
        if ($stmt->execute()) {
            $alertId = $conn->insert_id;
            
            // Create response entry
            $stmt = $conn->prepare("INSERT INTO emergency_responses (alert_id, notified_time) VALUES (?, NOW())");
            $stmt->bind_param("i", $alertId);
            $stmt->execute();
            
            // Send SMS notification to emergency contacts
            // This would call the send_sms function from sms_functions.php
            
            return [
                'status' => 'success',
                'message' => 'Emergency alert sent successfully',
                'alert_id' => $alertId
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to create emergency alert'
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Mark an incident as resolved
 * 
 * @param int $alertId Alert ID to resolve
 * @return bool True if successful, false otherwise
 */
function resolve_incident($alertId) {
    global $conn;
    
    try {
        // Update emergency alert status
        $stmt = $conn->prepare("UPDATE emergency_alerts SET status = 'resolved' WHERE id = ?");
        $stmt->bind_param("i", $alertId);
        $alertUpdated = $stmt->execute();
        
        // Update emergency response
        $stmt = $conn->prepare("UPDATE emergency_responses SET resolved_time = NOW(), case_resolved = 1 WHERE alert_id = ?");
        $stmt->bind_param("i", $alertId);
        $responseUpdated = $stmt->execute();
        
        return $alertUpdated && $responseUpdated;
    } catch (Exception $e) {
        error_log('Error in resolve_incident: ' . $e->getMessage());
        return false;
    }
}
?>
