<?php
require_once 'send_sms.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['latitude']) && isset($data['longitude'])) {
        $locationURL = "https://www.google.com/maps?q={$data['latitude']},{$data['longitude']}";
        $message = "Emergency! My location: {$locationURL}";
        
        // Send SMS using Fast2SMS
        $numbers = ['8544758216'];
        $smsResponse = sendEmergencySMS($numbers, $message);
        
        // Always return success if SMS is sent
        echo json_encode([
            'success' => true,
            'message' => 'Location shared successfully'
        ]);
    }
}
?>