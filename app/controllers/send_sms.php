<?php
require_once 'config.php';

// Define cURL constants if not already defined
if (!defined('CURLOPT_URL')) define('CURLOPT_URL', 10002);
if (!defined('CURLOPT_RETURNTRANSFER')) define('CURLOPT_RETURNTRANSFER', 19913);
if (!defined('CURLOPT_ENCODING')) define('CURLOPT_ENCODING', 10102);
if (!defined('CURLOPT_MAXREDIRS')) define('CURLOPT_MAXREDIRS', 68);
if (!defined('CURLOPT_TIMEOUT')) define('CURLOPT_TIMEOUT', 13);
if (!defined('CURLOPT_SSL_VERIFYHOST')) define('CURLOPT_SSL_VERIFYHOST', 81);
if (!defined('CURLOPT_SSL_VERIFYPEER')) define('CURLOPT_SSL_VERIFYPEER', 64);
if (!defined('CURLOPT_HTTP_VERSION')) define('CURLOPT_HTTP_VERSION', 84);
if (!defined('CURLOPT_CUSTOMREQUEST')) define('CURLOPT_CUSTOMREQUEST', 10036);
if (!defined('CURLOPT_POSTFIELDS')) define('CURLOPT_POSTFIELDS', 10015);
if (!defined('CURLOPT_HTTPHEADER')) define('CURLOPT_HTTPHEADER', 10023);
if (!defined('CURL_HTTP_VERSION_1_1')) define('CURL_HTTP_VERSION_1_1', 2);

function sendEmergencySMS($numbers, $message) {
    $fields = array(
        "message" => $message,
        "language" => "english",
        "route" => "q",
        "numbers" => implode(',', $numbers),
    );

    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://www.fast2sms.com/dev/bulkV2",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => json_encode($fields),
        CURLOPT_HTTPHEADER => array(
            "authorization: " . FAST2SMS_API_KEY,
            "content-type: application/json"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, true);
}
?>