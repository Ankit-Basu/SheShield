<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "sheshield";

function mysqli_column_exists($conn, $table, $column) {
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS c
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?"
    );
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return !empty($row) && (int)$row['c'] > 0;
}

function mysqli_add_column_if_missing($conn, $table, $column, $definition) {
    if (!mysqli_column_exists($conn, $table, $column)) {
        $conn->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

function ensure_mysqli_schema($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL UNIQUE,
        first_name VARCHAR(50) NULL,
        last_name VARCHAR(50) NULL,
        full_name VARCHAR(100) NULL,
        phone VARCHAR(20) DEFAULT '',
        phone_number VARCHAR(20) DEFAULT '',
        emergency_contact_name VARCHAR(100) NULL,
        emergency_contact_phone VARCHAR(20) NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        is_active BOOLEAN DEFAULT TRUE,
        last_login TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    mysqli_add_column_if_missing($conn, 'users', 'username', "VARCHAR(100) NULL");
    mysqli_add_column_if_missing($conn, 'users', 'password', "VARCHAR(255) NOT NULL DEFAULT ''");
    mysqli_add_column_if_missing($conn, 'users', 'first_name', "VARCHAR(50) NULL");
    mysqli_add_column_if_missing($conn, 'users', 'last_name', "VARCHAR(50) NULL");
    mysqli_add_column_if_missing($conn, 'users', 'full_name', "VARCHAR(100) NULL");
    mysqli_add_column_if_missing($conn, 'users', 'phone', "VARCHAR(20) DEFAULT ''");
    mysqli_add_column_if_missing($conn, 'users', 'phone_number', "VARCHAR(20) DEFAULT ''");
    mysqli_add_column_if_missing($conn, 'users', 'emergency_contact_name', "VARCHAR(100) NULL");
    mysqli_add_column_if_missing($conn, 'users', 'emergency_contact_phone', "VARCHAR(20) NULL");

    $conn->query("CREATE TABLE IF NOT EXISTS admins (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        role ENUM('admin', 'super_admin') DEFAULT 'admin',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS incidents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        incident_type VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        location VARCHAR(255) NOT NULL,
        date_time DATETIME NOT NULL,
        severity VARCHAR(20) DEFAULT 'low',
        status VARCHAR(20) DEFAULT 'pending',
        emergency_contact_name VARCHAR(100) NULL,
        emergency_contact_phone VARCHAR(20) NULL,
        evidence_file VARCHAR(255) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    mysqli_add_column_if_missing($conn, 'incidents', 'incident_type', "VARCHAR(100) NOT NULL DEFAULT 'other'");
    mysqli_add_column_if_missing($conn, 'incidents', 'description', "TEXT NULL");
    mysqli_add_column_if_missing($conn, 'incidents', 'location', "VARCHAR(255) NULL");
    mysqli_add_column_if_missing($conn, 'incidents', 'date_time', "DATETIME NULL");
    mysqli_add_column_if_missing($conn, 'incidents', 'severity', "VARCHAR(20) DEFAULT 'low'");
    mysqli_add_column_if_missing($conn, 'incidents', 'status', "VARCHAR(20) DEFAULT 'pending'");
    mysqli_add_column_if_missing($conn, 'incidents', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

    $conn->query("CREATE TABLE IF NOT EXISTS profile_images (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        image_path VARCHAR(500) NOT NULL,
        status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS safe_spaces (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        description TEXT NOT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        time_active INT NOT NULL DEFAULT 24,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS safe_zone (
        id INT AUTO_INCREMENT PRIMARY KEY,
        polygon_data TEXT NOT NULL,
        description TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS escorts (
        escort_id VARCHAR(20) PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20) NULL,
        type VARCHAR(50) NULL,
        gender VARCHAR(20) NULL,
        status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
        rating DECIMAL(3,2) DEFAULT 0.00,
        total_ratings INT DEFAULT 0,
        total_walks INT DEFAULT 0,
        completed_walks INT DEFAULT 0,
        cancelled_walks INT DEFAULT 0,
        profile_picture VARCHAR(255) NULL,
        id_proof_type VARCHAR(50) NULL,
        id_proof_number VARCHAR(50) NULL,
        verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
        available_from TIME NULL,
        available_to TIME NULL,
        preferred_areas JSON NULL,
        last_active TIMESTAMP NULL DEFAULT NULL,
        emergency_contact_name VARCHAR(100) NULL,
        emergency_contact_phone VARCHAR(20) NULL,
        description TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    mysqli_add_column_if_missing($conn, 'escorts', 'profile_picture', "VARCHAR(255) NULL");
    mysqli_add_column_if_missing($conn, 'escorts', 'id_proof_type', "VARCHAR(50) NULL");
    mysqli_add_column_if_missing($conn, 'escorts', 'id_proof_number', "VARCHAR(50) NULL");
    mysqli_add_column_if_missing($conn, 'escorts', 'verification_status', "ENUM('pending', 'verified', 'rejected') DEFAULT 'pending'");
    mysqli_add_column_if_missing($conn, 'escorts', 'available_from', "TIME NULL");
    mysqli_add_column_if_missing($conn, 'escorts', 'available_to', "TIME NULL");
    mysqli_add_column_if_missing($conn, 'escorts', 'preferred_areas', "JSON NULL");
    mysqli_add_column_if_missing($conn, 'escorts', 'last_active', "TIMESTAMP NULL DEFAULT NULL");
    mysqli_add_column_if_missing($conn, 'escorts', 'emergency_contact_name', "VARCHAR(100) NULL");
    mysqli_add_column_if_missing($conn, 'escorts', 'emergency_contact_phone', "VARCHAR(20) NULL");
    mysqli_add_column_if_missing($conn, 'escorts', 'description', "TEXT NULL");

    $conn->query("CREATE TABLE IF NOT EXISTS escorts_schedule (
        schedule_id INT AUTO_INCREMENT PRIMARY KEY,
        escort_id VARCHAR(20) NOT NULL,
        day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        status ENUM('active', 'inactive', 'available', 'unavailable') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS walk_requests (
        walk_id VARCHAR(50) PRIMARY KEY,
        escort_id VARCHAR(20) NOT NULL,
        user_id INT NULL,
        pickup_location VARCHAR(255) NOT NULL,
        destination VARCHAR(255) NOT NULL,
        request_time DATETIME NOT NULL,
        status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS emergency_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        latitude DECIMAL(10,8) NULL,
        longitude DECIMAL(11,8) NULL,
        timestamp DATETIME NULL,
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS emergency_responses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        alert_id INT NULL,
        notified_time DATETIME DEFAULT NULL,
        dispatched_time DATETIME DEFAULT NULL,
        arrived_time DATETIME DEFAULT NULL,
        resolved_time DATETIME DEFAULT NULL,
        case_resolved BOOLEAN DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS shared_locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        timestamp DATETIME NOT NULL,
        status ENUM('active', 'expired') DEFAULT 'active',
        expiry_time DATETIME NULL,
        shared_by VARCHAR(100) NULL,
        access_count INT DEFAULT 0,
        last_accessed DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS locations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        latitude DECIMAL(10, 8) NOT NULL,
        longitude DECIMAL(11, 8) NOT NULL,
        description TEXT NULL,
        category VARCHAR(50) NULL,
        polygon_data TEXT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_by INT NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}

function get_mysqli_connection() {
    global $servername, $username, $password, $dbname;

    $conn = new mysqli($servername, $username, $password);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    if (!$conn->select_db($dbname)) {
        die("Database selection failed: " . $conn->error);
    }

    $conn->set_charset("utf8mb4");
    ensure_mysqli_schema($conn);

    return $conn;
}
?>
