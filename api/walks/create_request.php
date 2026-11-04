<?php
// Saves a walk request for the signed-in user and assigns a verified walker when one is free.
require __DIR__ . '/../user/_auth.php';
date_default_timezone_set('Asia/Kolkata');

$pickup = trim(strip_tags($input['pickupLocation'] ?? ''));
$destination = trim(strip_tags($input['destination'] ?? ''));

if ($pickup === '' || $destination === '') {
    ss_user_respond(400, 'error', 'Choose a pickup and a destination.');
}
if (strcasecmp($pickup, $destination) === 0) {
    ss_user_respond(400, 'error', 'Pickup and destination need to be different.');
}

$when = trim((string)($input['requestTime'] ?? ''));
$timestamp = $when !== '' ? strtotime($when) : time();
if ($timestamp === false) {
    ss_user_respond(400, 'error', 'That date and time is not valid.');
}
$requestTime = date('Y-m-d H:i:s', $timestamp);
$clock = date('H:i:s', $timestamp);

// Never assign someone as their own walker, and only use verified walkers on duty at that time.
$stmt = $db->prepare(
    "SELECT escort_id, name FROM escorts
     WHERE status = 'active' AND verification_status = 'verified' AND email <> :email
       AND (available_from IS NULL OR available_to IS NULL
            OR (available_from <= available_to AND :t1 BETWEEN available_from AND available_to)
            OR (available_from > available_to AND (:t2 >= available_from OR :t3 <= available_to)))
     ORDER BY total_walks ASC, RAND() LIMIT 1"
);
$stmt->execute([':email' => $_SESSION['email'] ?? '', ':t1' => $clock, ':t2' => $clock, ':t3' => $clock]);
$walker = $stmt->fetch(PDO::FETCH_ASSOC);

$walkId = 'WALK-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
$status = $walker ? 'accepted' : 'pending';

$insert = $db->prepare(
    'INSERT INTO walk_requests (walk_id, escort_id, user_id, pickup_location, destination, request_time, status)
     VALUES (:id, :escort, :user, :pickup, :dest, :time, :status)'
);
$insert->execute([
    ':id' => $walkId,
    ':escort' => $walker['escort_id'] ?? null,
    ':user' => $userId,
    ':pickup' => $pickup,
    ':dest' => $destination,
    ':time' => $requestTime,
    ':status' => $status,
]);

ss_user_respond(201, 'success', $walker ? 'A walker has been assigned.' : 'Request saved. Waiting for a walker.', [
    'walkId' => $walkId,
    'walkStatus' => $status,
    'requestTime' => $requestTime,
]);
