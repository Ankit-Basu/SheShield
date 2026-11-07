<?php
// Shared guard for read-only JSON endpoints used by the mobile app and pages:
// GET only, signed in (cookie or bearer token), no caching.
require_once __DIR__ . '/../../app/middleware/session_bootstrap.php';
ss_session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function ss_read_respond($code, $payload) {
    http_response_code($code);
    echo json_encode($payload);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ss_read_respond(405, ['status' => 'error', 'message' => 'GET required.']);
}
if (empty($_SESSION['user_id'])) {
    ss_read_respond(401, ['status' => 'error', 'message' => 'Your session has expired. Please sign in again.']);
}
session_write_close(); // reads never change the session, so release its lock early

require_once __DIR__ . '/../../app/config/database.php';
$db = (new Database())->getConnection();
$userId = (int)$_SESSION['user_id'];

function ss_incident_row(array $r) {
    return [
        'id' => (int)$r['id'],
        'type' => $r['incident_type'],
        'description' => $r['description'],
        'location' => $r['location'],
        'date_time' => $r['date_time'],
        'severity' => $r['severity'] ?: 'low',
        'status' => $r['status'] ?: 'pending',
        'has_evidence' => !empty($r['evidence_file']),
        'created_at' => $r['created_at'],
    ];
}
