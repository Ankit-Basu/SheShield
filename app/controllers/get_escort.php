<?php
require_once 'mysqli_db.php';
require_once 'PHPMailer/Exception.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

header('Content-Type: application/json');

// Get JSON data
$jsonData = json_decode(file_get_contents('php://input'), true);
$location = $jsonData['location'] ?? 'Not specified';
$destination = $jsonData['destination'] ?? 'Not specified';
$preferredTime = $jsonData['preferredTime'] ?? null;

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Query to find active escorts
    $query = "SELECT * FROM escorts 
              WHERE status = 'active' 
              ORDER BY rating DESC, completed_walks DESC LIMIT 1";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }
    
    if ($result->num_rows === 0) {
        throw new Exception("No active escorts available at this time");
    }
    
    $escort = $result->fetch_assoc();
    
    // Initialize email variables
    $emailSent = false;
    $emailMessage = '';
    
    // Try to send email to the escort
    if ($escort && isset($escort['email'])) {
        try {
            $mail = new PHPMailer(true);
            
            // Server settings
            $mail->SMTPDebug = 0; // Disable debug output
            $mail->isSMTP();
            
            // Include email configuration
            require_once 'config/email_config.php';
            
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION === 'tls' ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(DEFAULT_FROM_EMAIL, DEFAULT_FROM_NAME);
            $mail->addAddress($escort['email'], $escort['name']);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'New Walk Request - SheShield';
            
            // Generate a unique walk ID for this request
            $walkId = 'WALK-' . date('Ymd') . '-' . substr(uniqid(), -6);
            
            // Simple email body
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #d946ef;'>New Walk Request</h2>
                    <p>Hello {$escort['name']},</p>
                    <p>You have received a new walk request with the following details:</p>
                    
                    <div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                        <p><strong>Walk ID:</strong> {$walkId}</p>
                        <p><strong>Pickup Location:</strong> {$location}</p>
                        <p><strong>Destination:</strong> {$destination}</p>
                        <p><strong>Preferred Time:</strong> {$preferredTime}</p>
                    </div>
                    
                    <p>Please respond to this request as soon as possible through your SheShield dashboard.</p>
                    
                    <div style='margin-top: 30px;'>
                        <p>Best regards,<br>SheShield Team</p>
                    </div>
                </div>
            ";
            
            $mail->send();
            $emailSent = true;
            $emailMessage = 'Email notification sent successfully to escort.';
        } catch (Exception $e) {
            $emailMessage = 'Failed to send email notification to escort: ' . $mail->ErrorInfo;
            error_log("Failed to send email to escort ID: {$escort['escort_id']} - Error: " . $e->getMessage());
        }
    } else {
        $emailMessage = 'No valid email address found for the escort.';
    }
    
    // Format response with email status
    $response = [
        'success' => true,
        'emailSent' => $emailSent,
        'emailMessage' => $emailMessage,
        'escort' => [
            'id' => $escort['escort_id'],
            'name' => $escort['name'],
            'type' => $escort['type'] ?? 'student',
            'rating' => floatval($escort['rating'] ?? 5.0),
            'completedWalks' => intval($escort['completed_walks'] ?? 0),
            'profilePic' => "https://ui-avatars.com/api/?name=" . urlencode($escort['name']) . "&background=random"
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
