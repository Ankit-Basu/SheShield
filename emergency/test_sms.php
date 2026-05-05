<?php
require_once 'send_sms.php';

if (!defined('FAST2SMS_API_KEY')) {
    define('FAST2SMS_API_KEY', getenv('FAST2SMS_API_KEY') ?: '');
}

// Test message
$numbers = ['8544758216'];  // Make sure this is the correct number
$message = "Test message from Fast2SMS";

$fields = array(
    "message" => $message,
    "language" => "english",
    "route" => "v3",    // Changed to v3 route for better delivery
    "numbers" => implode(',', $numbers),
);

$ch = curl_init();
curl_setopt_array($ch, array(
    CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS => json_encode($fields),
    CURLOPT_HTTPHEADER => array(
        "authorization: " . FAST2SMS_API_KEY,
        "accept: */*",
        "cache-control: no-cache",
        "content-type: application/json"
    ),
));

$response = curl_exec($ch);
$err = curl_error($ch);
$info = curl_getinfo($ch);

echo "<h3>Debug Information:</h3>";
echo "<pre>";
echo "cURL Error: " . ($err ? $err : "None") . "\n";
echo "HTTP Code: " . $info['http_code'] . "\n";
echo "Response: " . $response . "\n";
echo "</pre>";

curl_close($ch);
?>
