<?php
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../app/middleware/session_bootstrap.php';
    configure_session_storage();
    session_start();
    if (empty($_SESSION['user_id'])) {
        throw new Exception('Please sign in again.');
    }
    $sessionUser = (int)$_SESSION['user_id'];
    $isAdmin = !empty($_SESSION['is_admin']) ? 1 : 0;

    require_once __DIR__ . '/../app/models/mysqli_db.php';

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (empty($data['id'])) {
        throw new Exception('Invalid incident ID');
    }
    $id = $data['id'];

    $sql = "UPDATE incidents SET status='resolved' WHERE id=? AND status='pending' AND (user_id=? OR ?=1)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare SQL statement');
    }
    $stmt->bind_param("iii", $id, $sessionUser, $isAdmin);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No matching record found or already resolved']);
        }
    } else {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }
} catch (Exception $e) {
    error_log('Error in resolve-incident.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>
