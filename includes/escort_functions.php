<?php
// Escort-related functions
require_once __DIR__ . '/../app/models/mysqli_db.php';

/**
 * Get escort details by ID
 * 
 * @param string $escortId Escort ID to retrieve
 * @return array|null Escort details or null if not found
 */
function get_escort($escortId) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("SELECT * FROM escorts WHERE escort_id = ?");
        $stmt->bind_param("s", $escortId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        } else {
            return null;
        }
    } catch (Exception $e) {
        error_log("Error in get_escort: " . $e->getMessage());
        return null;
    }
}

/**
 * Register a new volunteer escort
 * 
 * @param array $data Escort registration data
 * @return array Response with status and message
 */
function register_volunteer($data) {
    global $conn;
    
    try {
        // Generate unique escort ID
        $escortId = 'ESC' . rand(10000, 99999);
        
        $stmt = $conn->prepare("INSERT INTO escorts (escort_id, name, email, phone, type, gender, id_proof_type, id_proof_number, created_at) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        
        $stmt->bind_param("ssssssss", 
            $escortId, 
            $data['name'], 
            $data['email'], 
            $data['phone'], 
            $data['type'], 
            $data['gender'], 
            $data['id_proof_type'], 
            $data['id_proof_number']
        );
        
        if ($stmt->execute()) {
            return [
                'status' => 'success',
                'message' => 'Volunteer registered successfully',
                'escort_id' => $escortId
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'Failed to register volunteer'
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
 * Send email notification to escort
 * 
 * @param string $escortEmail Escort email address
 * @param array $requestDetails Details of the escort request
 * @return bool True if successful, false otherwise
 */
function send_escort_email($escortEmail, $requestDetails) {
    // This is a placeholder for actual email sending logic
    // In a real implementation, you would use PHPMailer or similar
    
    $subject = "New Escort Request";  
    $message = "You have a new escort request. Details:\n";
    $message .= "Location: {$requestDetails['location']}\n";
    $message .= "Time: {$requestDetails['time']}\n";
    $message .= "Requester: {$requestDetails['requester']}\n";
    
    // Log the email attempt
    error_log("Email would be sent to $escortEmail with subject: $subject");
    
    // Return true to simulate successful sending
    return true;
}
?>
