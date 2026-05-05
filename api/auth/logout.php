<?php
$sessionPath = __DIR__ . '/../../app/middleware/session.php';
if (!file_exists($sessionPath)) $sessionPath = __DIR__ . '/../../utils/session.php';
require_once $sessionPath;

// Destroy the session
Session::destroy();

// Redirect to login page
header('Location: ../../pro/landing.html');
exit();
?>
