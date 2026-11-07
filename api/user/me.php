<?php
// Profile plus the same numbers the web dashboard shows, in one request.
require __DIR__ . '/_read.php';

$stmt = $db->prepare('SELECT id, first_name, last_name, email, phone, emergency_contact_name, emergency_contact_phone, created_at FROM users WHERE id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    ss_read_respond(404, ['status' => 'error', 'message' => 'Account not found.']);
}

$photo = $db->prepare("SELECT image_path FROM profile_images WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
$photo->execute([$userId]);
$photoPath = $photo->fetchColumn() ?: null;

$count = function ($sql, array $args = []) use ($db) {
    $s = $db->prepare($sql);
    $s->execute($args);
    return (int)$s->fetchColumn();
};

$latest = $db->prepare('SELECT * FROM incidents WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$latest->execute([$userId]);

$types = $db->prepare('SELECT incident_type, COUNT(*) AS c FROM incidents WHERE user_id = ? GROUP BY incident_type ORDER BY c DESC LIMIT 6');
$types->execute([$userId]);

$activeWalk = $db->prepare(
    "SELECT w.walk_id, w.pickup_location, w.destination, w.request_time, w.status, e.name AS walker_name
     FROM walk_requests w LEFT JOIN escorts e ON e.escort_id COLLATE utf8mb4_unicode_ci = w.escort_id COLLATE utf8mb4_unicode_ci
     WHERE w.user_id = ? AND w.status IN ('pending', 'accepted') ORDER BY w.request_time DESC LIMIT 1"
);
$activeWalk->execute([$userId]);
$walk = $activeWalk->fetch(PDO::FETCH_ASSOC) ?: null;

ss_read_respond(200, [
    'status' => 'success',
    'user' => [
        'id' => (int)$user['id'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'email' => $user['email'],
        'phone' => $user['phone'] ?: '',
        'emergency_contact_name' => $user['emergency_contact_name'] ?: '',
        'emergency_contact_phone' => $user['emergency_contact_phone'] ?: '',
        'photo' => $photoPath,
        'is_admin' => !empty($_SESSION['is_admin']),
        'member_since' => $user['created_at'],
    ],
    'stats' => [
        'reports' => $count('SELECT COUNT(*) FROM incidents WHERE user_id = ?', [$userId]),
        'resolved' => $count("SELECT COUNT(*) FROM incidents WHERE user_id = ? AND status = 'resolved'", [$userId]),
        'pending' => $count("SELECT COUNT(*) FROM incidents WHERE user_id = ? AND status = 'pending'", [$userId]),
        'active_walkers' => $count("SELECT COUNT(*) FROM escorts WHERE status = 'active' AND verification_status = 'verified'"),
        'safe_spaces' => $count('SELECT COUNT(*) FROM safe_spaces') + $count('SELECT COUNT(*) FROM safe_zone'),
    ],
    'latest_reports' => array_map('ss_incident_row', $latest->fetchAll(PDO::FETCH_ASSOC)),
    'reports_by_type' => array_map(fn ($r) => ['type' => $r['incident_type'], 'count' => (int)$r['c']], $types->fetchAll(PDO::FETCH_ASSOC)),
    'active_walk' => $walk,
]);
