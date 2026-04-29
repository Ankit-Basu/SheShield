-- Users table for storing user information
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    full_name VARCHAR(100),
    phone_number VARCHAR(20),
    role ENUM('user', 'admin') DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    last_login TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Locations table for storing location information
CREATE TABLE IF NOT EXISTS locations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    polygon_data TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_by INT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_category (category),
    INDEX idx_coordinates (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Safe spaces table for storing safe locations
CREATE TABLE IF NOT EXISTS safe_spaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    description TEXT NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    time_active INT NOT NULL COMMENT 'Duration in hours for which the safe space remains active',
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Emergency alerts table for storing emergency situations
CREATE TABLE IF NOT EXISTS emergency_alerts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    latitude DECIMAL(10,8),
    longitude DECIMAL(11,8),
    timestamp DATETIME,
    status VARCHAR(20) DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Emergency responses table for tracking response to alerts
CREATE TABLE IF NOT EXISTS emergency_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alert_id INT,
    notified_time DATETIME DEFAULT NULL,
    dispatched_time DATETIME DEFAULT NULL,
    arrived_time DATETIME DEFAULT NULL,
    resolved_time DATETIME DEFAULT NULL,
    case_resolved BOOLEAN DEFAULT 0,
    FOREIGN KEY (alert_id) REFERENCES emergency_alerts(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Safe zones table for storing polygon-based safe areas
CREATE TABLE IF NOT EXISTS safe_zone (
    id INT AUTO_INCREMENT PRIMARY KEY,
    polygon_data TEXT NOT NULL,
    description TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Escorts table for storing escort information
CREATE TABLE IF NOT EXISTS escorts (
    escort_id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    type VARCHAR(50),
    gender VARCHAR(20),
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_ratings INT DEFAULT 0,
    total_walks INT DEFAULT 0,
    completed_walks INT DEFAULT 0,
    cancelled_walks INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Escorts schedule table for storing availability
CREATE TABLE IF NOT EXISTS escorts_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    escort_id VARCHAR(20) NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (escort_id) REFERENCES escorts(escort_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create walk_requests table
CREATE TABLE IF NOT EXISTS walk_requests (
    walk_id VARCHAR(20) PRIMARY KEY,
    escort_id VARCHAR(20) NOT NULL,
    user_id VARCHAR(20),
    pickup_location VARCHAR(255) NOT NULL,
    destination VARCHAR(255) NOT NULL,
    request_time DATETIME NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (escort_id) REFERENCES escorts(escort_id),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Shared locations table for tracking shared location links
CREATE TABLE IF NOT EXISTS shared_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    timestamp DATETIME NOT NULL,
    status ENUM('active', 'expired') DEFAULT 'active',
    expiry_time DATETIME DEFAULT (NOW() + INTERVAL 24 HOUR),
    shared_by VARCHAR(100),
    access_count INT DEFAULT 0,
    last_accessed DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_coordinates (latitude, longitude)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Incidents table for storing safety incidents
CREATE TABLE IF NOT EXISTS incidents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    incident_type VARCHAR(50) NOT NULL,
    description TEXT,
    location VARCHAR(255),
    date_time DATETIME,
    status ENUM('pending', 'investigating', 'resolved', 'closed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
