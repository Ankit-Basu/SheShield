<?php
// The signed-in user's walk requests, with the assigned walker when there is one.
require __DIR__ . '/../user/_read.php';

$stmt = $db->prepare(
    "SELECT w.walk_id, w.pickup_location, w.destination, w.request_time, w.status, w.created_at,
            e.name AS walker_name, e.phone AS walker_phone
     FROM walk_requests w LEFT JOIN escorts e ON e.escort_id COLLATE utf8mb4_unicode_ci = w.escort_id COLLATE utf8mb4_unicode_ci
     WHERE w.user_id = ? ORDER BY w.request_time DESC LIMIT 50"
);
$stmt->execute([$userId]);

$walker = $db->prepare("SELECT escort_id, verification_status, available_from, available_to, preferred_areas FROM escorts WHERE email = ? LIMIT 1");
$walker->execute([$_SESSION['email'] ?? '']);
$me = $walker->fetch(PDO::FETCH_ASSOC) ?: null;

ss_read_respond(200, [
    'status' => 'success',
    'walks' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    'walker_profile' => $me ? [
        'verification_status' => $me['verification_status'],
        'available_from' => $me['available_from'],
        'available_to' => $me['available_to'],
        'preferred_areas' => json_decode((string)$me['preferred_areas'], true) ?: [],
    ] : null,
]);
