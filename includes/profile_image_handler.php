<?php
require_once dirname(__DIR__) . '/database/mysqli_db.php';

function uploadProfileImage($userId, $file) {
    if (!isset($userId) || empty($userId)) {
        return ["success" => false, "message" => "User ID is required."];
    }

    $targetDir = dirname(__DIR__) . "/uploads/profile_images/" . $userId . "/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $newFileName = uniqid() . "." . $imageFileType;
    $targetFile = $targetDir . $newFileName;

    // Check if image file is actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ["success" => false, "message" => "File is not an image."];
    }

    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        return ["success" => false, "message" => "File is too large."];
    }

    // Allow certain file formats
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
        return ["success" => false, "message" => "Only JPG, JPEG & PNG files are allowed."];
    }

    if (move_uploaded_file($file["tmp_name"], $targetFile)) {
        // Insert into profile_images table
        $conn = get_mysqli_connection();
        
        $relativePath = "/uploads/profile_images/" . $userId . "/" . $newFileName;
        $stmt = $conn->prepare("INSERT INTO profile_images (user_id, image_path, status, created_at) VALUES (?, ?, 'active', NOW())");
        if (!$stmt) {
            unlink($targetFile);
            return ["success" => false, "message" => "Profile image table is not ready."];
        }

        // Deactivate previous profile images for this user
        $deactivateStmt = $conn->prepare("UPDATE profile_images SET status = 'deleted' WHERE user_id = ? AND status = 'active'");
        if ($deactivateStmt) {
            $deactivateStmt->bind_param("i", $userId);
            $deactivateStmt->execute();
            $deactivateStmt->close();
        }

        $stmt->bind_param("is", $userId, $relativePath);
        
        if($stmt->execute()) {
            $stmt->close();
            return ["success" => true, "image_path" => $relativePath];
        } else {
            unlink($targetFile); // Delete the uploaded file
            return ["success" => false, "message" => "Error updating database."];
        }
    } else {
        return ["success" => false, "message" => "Error uploading file."];
    }
}

function getProfileImage($userId) {
    try {
        if (!isset($userId) || empty($userId)) {
            error_log("getProfileImage: Invalid user ID");
            return null;
        }

        $conn = get_mysqli_connection();
        $stmt = $conn->prepare("SELECT image_path FROM profile_images WHERE user_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
        if (!$stmt) {
            error_log("getProfileImage: profile_images table is unavailable");
            return null;
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()) {
            $imagePath = $row['image_path'];
            if (preg_match('/^https?:\/\//i', $imagePath)) {
                return $imagePath;
            }

            if ($imagePath && file_exists(dirname(__DIR__) . $imagePath)) {
                return $imagePath;
            } else {
                error_log("getProfileImage: Image file not found for user " . $userId);
                // Deactivate the invalid image entry
                $deactivateStmt = $conn->prepare("UPDATE profile_images SET status = 'deleted' WHERE user_id = ? AND image_path = ?");
                if ($deactivateStmt) {
                    $deactivateStmt->bind_param("is", $userId, $imagePath);
                    $deactivateStmt->execute();
                    $deactivateStmt->close();
                }
            }
        }
        $stmt->close();
        return null;
    } catch (Throwable $e) {
        error_log("Error in getProfileImage: " . $e->getMessage());
        return null;
    }
}
