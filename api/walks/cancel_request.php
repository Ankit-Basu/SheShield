<?php
// Lets the person who made a walk request cancel it.
require __DIR__ . '/../user/_auth.php';

$stmt = $db->prepare(
    "UPDATE walk_requests SET status = 'cancelled'
     WHERE walk_id = :id AND user_id = :user AND status IN ('pending', 'accepted')"
);
$stmt->execute([':id' => (string)($input['walkId'] ?? ''), ':user' => $userId]);

if ($stmt->rowCount() === 0) {
    ss_user_respond(404, 'error', 'That walk can no longer be cancelled.');
}
ss_user_respond(200, 'success', 'Walk cancelled.');
