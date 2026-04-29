-- Complete Database Schema for Women Safety Project
-- This file contains all table definitions compiled from various SQL files

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

-- Alternative safe space table has been removed as it was redundant and not used in the application

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
);

-- Escorts table for storing escort information
CREATE TABLE IF NOT EXISTS escorts (
    escort_id VARCHAR(20) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(15),
    type ENUM('student', 'staff', 'security') NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    rating DECIMAL(3,2) DEFAULT 0.00,
    total_ratings INT DEFAULT 0,
    total_walks INT DEFAULT 0,
    completed_walks INT DEFAULT 0,
    cancelled_walks INT DEFAULT 0,
    profile_picture VARCHAR(255),
    id_proof_type VARCHAR(50) NOT NULL,
    id_proof_number VARCHAR(50) NOT NULL,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    available_from TIME,
    available_to TIME,
    preferred_areas JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_active TIMESTAMP NULL DEFAULT NULL,
    emergency_contact_name VARCHAR(100),
    emergency_contact_phone VARCHAR(15),
    description TEXT
);

-- Escorts schedule table for availability
CREATE TABLE IF NOT EXISTS escorts_schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    escort_id VARCHAR(20),
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'),
    start_time TIME,
    end_time TIME,
    status ENUM('available', 'unavailable') DEFAULT 'available',
    FOREIGN KEY (escort_id) REFERENCES escorts(escort_id) ON DELETE CASCADE,
    CONSTRAINT unique_escort_schedule UNIQUE (escort_id, day_of_week)
);

-- Escorts walks table for walk history
CREATE TABLE IF NOT EXISTS escorts_walks (
    walk_id VARCHAR(50) PRIMARY KEY,
    escort_id VARCHAR(20),
    user_id VARCHAR(50),
    start_location VARCHAR(255) NOT NULL,
    end_location VARCHAR(255) NOT NULL,
    start_time TIMESTAMP NULL DEFAULT NULL,
    end_time TIMESTAMP NULL DEFAULT NULL,
    status ENUM('requested', 'accepted', 'started', 'completed', 'cancelled') DEFAULT 'requested',
    distance DECIMAL(10,2),
    duration INT,
    rating DECIMAL(3,2),
    feedback TEXT,
    cancelled_by ENUM('user', 'escort', 'system'),
    cancellation_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (escort_id) REFERENCES escorts(escort_id) ON DELETE SET NULL
);

-- Create indexes for better performance
CREATE INDEX idx_escort_status ON escorts(status);
CREATE INDEX idx_escort_rating ON escorts(rating);
CREATE INDEX idx_escort_verification ON escorts(verification_status);
CREATE INDEX idx_walk_status ON escorts_walks(status);
CREATE INDEX idx_walk_dates ON escorts_walks(start_time, end_time);