<?php
require __DIR__ . '/_auth.php';

$current = (string)($input['current_password'] ?? '');
$next = (string)($input['new_password'] ?? '');

if (strlen($next) < 8) {
    ss_user_respond(400, 'error', 'New password must be at least 8 characters.');
}

$stmt = $db->prepare('SELECT password FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$hash = $stmt->fetchColumn();

if ($hash === false || !password_verify($current, $hash)) {
    ss_user_respond(403, 'error', 'Your current password is incorrect.');
}

$update = $db->prepare('UPDATE users SET password = :password WHERE id = :id');
$ok = $update->execute([':password' => password_hash($next, PASSWORD_DEFAULT), ':id' => $userId]);

if (!$ok) {
    ss_user_respond(500, 'error', 'Could not update your password. Please try again.');
}
ss_user_respond(200, 'success', 'Password updated.');
