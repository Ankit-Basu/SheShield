<?php
session_start();
require_once 'profile_image_handler.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $userId = $_SESSION['user_id'];
        $result = uploadProfileImage($userId, $_FILES['profile_image']);
        
        if ($result['success']) {
            $_SESSION['profile_image'] = $result['image_path'];
            $response = [
                'success' => true,
                'message' => 'Profile image updated successfully',
                'image_path' => $result['image_path']
            ];
        } else {
            $response = ['success' => false, 'message' => $result['message']];
        }
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
