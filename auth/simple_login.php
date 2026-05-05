<?php
// Simple, bulletproof login handler using mysqli (same as dashboard)
require_once __DIR__ . '/../app/middleware/session_bootstrap.php';
configure_session_storage();
session_start();
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../app/models/mysqli_db.php';

$data = json_decode(file_get_contents("php://input"));
$response = [];

if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Email and password required"]);
    exit;
}

$email = $data->email;
$password = $data->password;

if (!isset($conn) || $conn->connect_error) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed"]);
    exit;
}

$stmt = $conn->prepare("SELECT id, first_name, last_name, email, password, phone FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        // Set all session variables directly
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = (int)$row['id'];
        $_SESSION['first_name'] = $row['first_name'];
        $_SESSION['last_name'] = $row['last_name'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['phone'] = $row['phone'] ?? '';
        $_SESSION['is_admin'] = false;

        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "user" => [
                "id" => $row['id'],
                "first_name" => $row['first_name'],
                "last_name" => $row['last_name'],
                "email" => $row['email']
            ]
        ]);
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Invalid password"]);
    }
} else {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "User not found"]);
}
$stmt->close();
