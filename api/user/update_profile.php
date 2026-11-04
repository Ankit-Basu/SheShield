<?php
require __DIR__ . '/_auth.php';

$first = trim(strip_tags($input['first_name'] ?? ''));
$last = trim(strip_tags($input['last_name'] ?? ''));
$phone = trim(strip_tags($input['phone'] ?? ''));
$contactName = trim(strip_tags($input['emergency_contact_name'] ?? ''));
$contactPhone = trim(strip_tags($input['emergency_contact_phone'] ?? ''));

if ($first === '') {
    ss_user_respond(400, 'error', 'First name is required.');
}

$stmt = $db->prepare(
    'UPDATE users SET first_name = :first, last_name = :last, full_name = :full, phone = :phone, phone_number = :phone2,
     emergency_contact_name = :cname, emergency_contact_phone = :cphone WHERE id = :id'
);
$ok = $stmt->execute([
    ':first' => $first,
    ':last' => $last,
    ':full' => trim("$first $last"),
    ':phone' => $phone,
    ':phone2' => $phone,
    ':cname' => $contactName,
    ':cphone' => $contactPhone,
    ':id' => $userId,
]);

if (!$ok) {
    ss_user_respond(500, 'error', 'Could not save your profile. Please try again.');
}

$_SESSION['first_name'] = $first;
$_SESSION['last_name'] = $last;
$_SESSION['user_name'] = trim("$first $last");
ss_user_respond(200, 'success', 'Profile saved.');
