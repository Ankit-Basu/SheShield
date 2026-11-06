<?php
// Lets static pages ask whether the visitor is signed in. Returns no personal data.
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
ss_session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
echo json_encode(['logged_in' => !empty($_SESSION['user_id'])]);
