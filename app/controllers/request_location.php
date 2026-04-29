<?php
session_start();

// Set location permission status
$_SESSION['location_permission'] = true;

// Send response
header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'Location permission granted'
]);
?>