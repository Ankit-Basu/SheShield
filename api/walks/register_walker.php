<?php
// Saves a volunteer's walker profile. New walkers stay unverified until an admin approves them.
require __DIR__ . '/../user/_auth.php';

$email = (string)($_SESSION['email'] ?? '');
$name = trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
$clean = fn ($key) => trim(strip_tags((string)($input[$key] ?? '')));

$phone = $clean('phone');
$type = $clean('type');
$gender = $clean('gender');
$idType = $clean('idProofType');
$idNumber = $clean('idProofNumber');
$from = $clean('availableFrom');
$until = $clean('availableUntil');
$areas = array_values(array_filter(array_map(fn ($a) => trim(strip_tags((string)$a)), (array)($input['preferredAreas'] ?? []))));

if ($email === '' || $name === '') {
    ss_user_respond(400, 'error', 'Add your name in Settings first.');
}
if ($phone === '' || $idNumber === '' || $from === '' || $until === '') {
    ss_user_respond(400, 'error', 'Please complete every field.');
}
if (!in_array($type, ['student', 'staff', 'security'], true) || !in_array($gender, ['male', 'female', 'other'], true)) {
    ss_user_respond(400, 'error', 'Please choose your role and gender.');
}
if (!in_array($idType, ['University ID', 'Staff ID', 'Government ID'], true)) {
    ss_user_respond(400, 'error', 'Please choose an ID type.');
}
if (!$areas) {
    ss_user_respond(400, 'error', 'Choose at least one area.');
}

$existing = $db->prepare('SELECT escort_id FROM escorts WHERE email = :email');
$existing->execute([':email' => $email]);
$escortId = $existing->fetchColumn();

$params = [
    ':name' => $name, ':phone' => substr($phone, 0, 15), ':type' => $type, ':gender' => $gender,
    ':idt' => $idType, ':idn' => $idNumber, ':from' => $from, ':until' => $until, ':areas' => json_encode($areas),
];

if ($escortId) {
    $sql = 'UPDATE escorts SET name = :name, phone = :phone, type = :type, gender = :gender, id_proof_type = :idt,
            id_proof_number = :idn, available_from = :from, available_to = :until, preferred_areas = :areas
            WHERE escort_id = :id';
    $params[':id'] = $escortId;
    $db->prepare($sql)->execute($params);
    ss_user_respond(200, 'success', 'Your walker details are updated.', ['escortId' => $escortId]);
}

$escortId = 'ESC-' . strtoupper(substr(uniqid(), -8));
$sql = "INSERT INTO escorts (escort_id, name, email, phone, type, gender, status, id_proof_type, id_proof_number,
        verification_status, available_from, available_to, preferred_areas)
        VALUES (:id, :name, :email, :phone, :type, :gender, 'active', :idt, :idn, 'pending', :from, :until, :areas)";
$params[':id'] = $escortId;
$params[':email'] = $email;
$db->prepare($sql)->execute($params);

ss_user_respond(201, 'success', 'Thank you. An admin will verify your details.', ['escortId' => $escortId]);
