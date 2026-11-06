<?php
// Shared guard for account endpoints: starts the session, returns JSON, requires login.
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
ss_session_start();
header('Content-Type: application/json; charset=UTF-8');

function ss_user_respond($code, $status, $message, $extra = []) {
    http_response_code($code);
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ss_user_respond(405, 'error', 'POST required.');
}
if (empty($_SESSION['user_id'])) {
    ss_user_respond(401, 'error', 'Your session has expired. Please sign in again.');
}

require_once __DIR__ . '/../../app/config/database.php';
$database = new Database();
$db = $database->getConnection();
$userId = (int)$_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?: [];
