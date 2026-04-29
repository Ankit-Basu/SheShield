-- Create escorts table
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

-- Create escorts_schedule table for availability
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

-- Create escorts_walks table for walk history
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

-- Insert sample escort data
INSERT INTO escorts (
    escort_id, name, email, phone, type, gender, status, rating, 
    total_ratings, total_walks, completed_walks, id_proof_type, id_proof_number,
    verification_status, description
) VALUES (
    'ESC-2025-001',
    'Sarah Johnson',
    'sarah.j@example.com',
    '+1234567890',
    'student',
    'female',
    'active',
    4.9,
    245,
    156,
    156,
    'student_id',
    'STU2025001',
    'verified',
    'Dedicated student volunteer committed to ensuring campus safety. Available for evening and night walks.'
);
