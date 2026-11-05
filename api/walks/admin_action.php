<?php
// Admin actions: verify or reject walkers, update walk requests.
require __DIR__ . '/../user/_auth.php';

if (empty($_SESSION['is_admin'])) {
    ss_user_respond(403, 'error', 'Admins only.');
}

$action = (string)($input['action'] ?? '');

if ($action === 'verify_walker') {
    $decision = (string)($input['decision'] ?? '');
    if (!in_array($decision, ['verified', 'rejected', 'pending'], true)) {
        ss_user_respond(400, 'error', 'Unknown decision.');
    }
    $stmt = $db->prepare('UPDATE escorts SET verification_status = :d WHERE escort_id = :id');
    $stmt->execute([':d' => $decision, ':id' => (string)($input['escortId'] ?? '')]);
    ss_user_respond(200, 'success', 'Walker updated.');
}

if ($action === 'update_walk') {
    $status = (string)($input['status'] ?? '');
    if (!in_array($status, ['pending', 'accepted', 'rejected', 'completed', 'cancelled'], true)) {
        ss_user_respond(400, 'error', 'Unknown status.');
    }
    $escortId = trim((string)($input['escortId'] ?? '')) ?: null;
    if ($escortId !== null) {
        $check = $db->prepare("SELECT 1 FROM escorts WHERE escort_id = :id AND verification_status = 'verified'");
        $check->execute([':id' => $escortId]);
        if (!$check->fetchColumn()) {
            ss_user_respond(400, 'error', 'Only verified walkers can be assigned.');
        }
    }
    $stmt = $db->prepare('UPDATE walk_requests SET status = :s, escort_id = :e WHERE walk_id = :id');
    $stmt->execute([':s' => $status, ':e' => $escortId, ':id' => (string)($input['walkId'] ?? '')]);
    ss_user_respond(200, 'success', 'Walk updated.');
}

ss_user_respond(400, 'error', 'Unknown action.');
