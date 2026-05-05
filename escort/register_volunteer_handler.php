<?php
require_once __DIR__ . '/../app/models/mysqli_db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Generate unique escort ID (ESC-YYYY-XXX format)
    $year = date('Y');
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM escorts WHERE escort_id LIKE ?");
    $escortIdPrefix = "ESC-{$year}-%";
    $stmt->bind_param("s", $escortIdPrefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'] + 1;
    $escort_id = sprintf("ESC-%s-%03d", $year, $count);

    // Prepare the insert statement
    $stmt = $conn->prepare("INSERT INTO escorts (
        escort_id, name, email, phone, type, gender,
        id_proof_type, id_proof_number, verification_status,
        available_from, available_to, preferred_areas,
        emergency_contact_name, emergency_contact_phone
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?, ?)");

    // Convert preferred areas array to JSON
    $preferred_areas = isset($_POST['preferred_areas']) ? json_encode($_POST['preferred_areas']) : '[]';

    $stmt->bind_param("ssssssssssss",
        $escort_id,
        $_POST['name'],
        $_POST['email'],
        $_POST['phone'],
        $_POST['type'],
        $_POST['gender'],
        $_POST['id_proof_type'],
        $_POST['id_proof_number'],
        $_POST['available_from'],
        $_POST['available_to'],
        $preferred_areas,
        $_POST['emergency_contact_name'],
        $_POST['emergency_contact_phone']
    );

    $response = array();
    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Registration successful! Your application is pending verification.',
            'escort_id' => $escort_id
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Registration failed. Please try again.'
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
