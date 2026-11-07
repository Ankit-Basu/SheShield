<?php
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
$token = ss_bearer_token();
ss_session_start();
$_SESSION = array();
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
}
session_destroy();

// The mobile app gets JSON and its token stops working; browsers go back to the landing page.
if ($token !== '') {
    ss_revoke_token($token);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['status' => 'success', 'message' => 'Signed out.']);
    exit();
}
header('Location: ../../pro/landing.html');
exit();
