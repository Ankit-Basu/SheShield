<?php
class Database {
    // Parse database URL if available (for Render.com)
    private function getDbConfig() {
        if (getenv('DATABASE_URL')) {
            $db = parse_url(getenv('DATABASE_URL'));
            return [
                'host' => $db['host'],
                'db'   => ltrim($db['path'], '/'),
                'user' => $db['user'],
                'pass' => $db['pass'],
                'port' => $db['port'],
                'driver' => 'pgsql'
            ];
        }
        return [
            'host' => 'localhost',
            'db'   => 'sheshield',
            'user' => 'root',
            'pass' => '',
            'port' => 3306,
            'driver' => 'mysql'
        ];
    }
    public $conn;

    private function quoteIdentifier($identifier) {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function columnExists($table, $column) {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) AS c
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table AND COLUMN_NAME = :column"
        );
        $stmt->execute([':table' => $table, ':column' => $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function addColumnIfMissing($table, $column, $definition) {
        if (!$this->columnExists($table, $column)) {
            $this->conn->exec(
                "ALTER TABLE " . $this->quoteIdentifier($table) .
                " ADD COLUMN " . $this->quoteIdentifier($column) . " " . $definition
            );
        }
    }

    private function ensureSchema($driver) {
        if ($driver !== 'mysql') {
            return;
        }

        $this->conn->exec("CREATE TABLE IF NOT EXISTS users (
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

        $this->addColumnIfMissing('users', 'username', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('users', 'password', "VARCHAR(255) NOT NULL DEFAULT ''");
        $this->addColumnIfMissing('users', 'first_name', "VARCHAR(50) NULL");
        $this->addColumnIfMissing('users', 'last_name', "VARCHAR(50) NULL");
        $this->addColumnIfMissing('users', 'full_name', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('users', 'phone', "VARCHAR(20) DEFAULT ''");
        $this->addColumnIfMissing('users', 'phone_number', "VARCHAR(20) DEFAULT ''");
        $this->addColumnIfMissing('users', 'emergency_contact_name', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('users', 'emergency_contact_phone', "VARCHAR(20) NULL");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            role ENUM('admin', 'super_admin') DEFAULT 'admin',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS incidents (
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

        $this->addColumnIfMissing('incidents', 'incident_type', "VARCHAR(100) NOT NULL DEFAULT 'other'");
        $this->addColumnIfMissing('incidents', 'description', "TEXT NULL");
        $this->addColumnIfMissing('incidents', 'location', "VARCHAR(255) NULL");
        $this->addColumnIfMissing('incidents', 'date_time', "DATETIME NULL");
        $this->addColumnIfMissing('incidents', 'severity', "VARCHAR(20) DEFAULT 'low'");
        $this->addColumnIfMissing('incidents', 'status', "VARCHAR(20) DEFAULT 'pending'");
        $this->addColumnIfMissing('incidents', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS profile_images (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            image_path VARCHAR(500) NOT NULL,
            status ENUM('active', 'inactive', 'deleted') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS safe_spaces (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            latitude DECIMAL(10, 8) NOT NULL,
            longitude DECIMAL(11, 8) NOT NULL,
            description TEXT NOT NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            time_active INT NOT NULL DEFAULT 24,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS safe_zone (
            id INT AUTO_INCREMENT PRIMARY KEY,
            polygon_data TEXT NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS escorts (
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

        $this->addColumnIfMissing('escorts', 'profile_picture', "VARCHAR(255) NULL");
        $this->addColumnIfMissing('escorts', 'id_proof_type', "VARCHAR(50) NULL");
        $this->addColumnIfMissing('escorts', 'id_proof_number', "VARCHAR(50) NULL");
        $this->addColumnIfMissing('escorts', 'verification_status', "ENUM('pending', 'verified', 'rejected') DEFAULT 'pending'");
        $this->addColumnIfMissing('escorts', 'available_from', "TIME NULL");
        $this->addColumnIfMissing('escorts', 'available_to', "TIME NULL");
        $this->addColumnIfMissing('escorts', 'preferred_areas', "JSON NULL");
        $this->addColumnIfMissing('escorts', 'last_active', "TIMESTAMP NULL DEFAULT NULL");
        $this->addColumnIfMissing('escorts', 'emergency_contact_name', "VARCHAR(100) NULL");
        $this->addColumnIfMissing('escorts', 'emergency_contact_phone', "VARCHAR(20) NULL");
        $this->addColumnIfMissing('escorts', 'description', "TEXT NULL");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS escorts_schedule (
            schedule_id INT AUTO_INCREMENT PRIMARY KEY,
            escort_id VARCHAR(20) NOT NULL,
            day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            status ENUM('active', 'inactive', 'available', 'unavailable') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS walk_requests (
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

        $this->conn->exec("CREATE TABLE IF NOT EXISTS emergency_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            latitude DECIMAL(10,8) NULL,
            longitude DECIMAL(11,8) NULL,
            timestamp DATETIME NULL,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS emergency_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            alert_id INT NULL,
            notified_time DATETIME DEFAULT NULL,
            dispatched_time DATETIME DEFAULT NULL,
            arrived_time DATETIME DEFAULT NULL,
            resolved_time DATETIME DEFAULT NULL,
            case_resolved BOOLEAN DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS shared_locations (
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

        $this->conn->exec("CREATE TABLE IF NOT EXISTS locations (
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

    public function getConnection() {
        $this->conn = null;

        try {
            $dbConfig = $this->getDbConfig();
            
            if ($dbConfig['driver'] === 'pgsql') {
                $dsn = "pgsql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['db']};";
            } else {
                $serverDsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset=utf8mb4";
                $serverConn = new PDO(
                    $serverDsn,
                    $dbConfig['user'],
                    $dbConfig['pass'],
                    array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
                );
                $serverConn->exec(
                    "CREATE DATABASE IF NOT EXISTS " . $this->quoteIdentifier($dbConfig['db']) .
                    " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
                );
                $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['db']};charset=utf8mb4";
            }
            
            $this->conn = new PDO(
                $dsn,
                $dbConfig['user'],
                $dbConfig['pass'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );

            $this->ensureSchema($dbConfig['driver']);

            return $this->conn;
        } catch(PDOException $e) {
            error_log("Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
}
?>
