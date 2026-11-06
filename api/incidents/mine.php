<?php
// The signed-in user's own reports, newest first. Anonymous reports are never linked to anyone.
require __DIR__ . '/../user/_read.php';

$limit = max(1, min(100, (int)($_GET['limit'] ?? 50)));
$stmt = $db->prepare("SELECT * FROM incidents WHERE user_id = ? ORDER BY created_at DESC LIMIT $limit");
$stmt->execute([$userId]);

ss_read_respond(200, [
    'status' => 'success',
    'reports' => array_map('ss_incident_row', $stmt->fetchAll(PDO::FETCH_ASSOC)),
]);
