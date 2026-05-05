<?php
// SMS-related functions
require_once __DIR__ . '/../app/models/mysqli_db.php';

/**
 * Send SMS message
 * 
 * @param string $to Recipient phone number
 * @param string $message Message content
 * @return bool True if successful, false otherwise
 */
function send_sms($to, $message) {
    global $config;
    
    // This is a placeholder for actual SMS sending logic
    // In a real implementation, you would use an SMS gateway API
    
    // Log the SMS attempt
    error_log("SMS would be sent to $to with message: $message");
    
    // Return true to simulate successful sending
    return true;
}

/**
 * Send emergency SMS to contacts
 * 
 * @param int $userId User ID to get emergency contacts for
 * @param string $latitude Latitude of emergency location
 * @param string $longitude Longitude of emergency location
 * @return bool True if successful, false otherwise
 */
function send_emergency_sms($userId, $latitude, $longitude) {
    global $conn;
    
    // Get user's emergency contacts
    $stmt = $conn->prepare("SELECT emergency_contact_name, emergency_contact_phone FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $contactName = $row['emergency_contact_name'];
        $contactPhone = $row['emergency_contact_phone'];
        
        if (!empty($contactPhone)) {
            // Create emergency message with location
            $message = "EMERGENCY ALERT: Your contact needs help! They are located at: https://maps.google.com/?q=$latitude,$longitude";
            
            // Send the SMS
            return send_sms($contactPhone, $message);
        }
    }
    
    return false;
}
?>
