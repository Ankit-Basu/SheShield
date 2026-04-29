<?php
require_once 'mysqli_db.php';

try {
    // Create database if not exists
    $conn->query("CREATE DATABASE IF NOT EXISTS sheshield");
    $conn->select_db("sheshield");
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/sql/create_escorts_table.sql');
    if (!$conn->multi_query($sql)) {
        throw new Exception("Error creating tables: " . $conn->error);
    }
    
    // Clear results to allow next query
    while ($conn->more_results() && $conn->next_result());
    
    // Insert sample escort data
    $sample_escorts = [
        [
            'escort_id' => 'ESC-2025-001',
            'name' => 'Sarah Johnson',
            'email' => 'sarah.j@university.edu',
            'phone' => '1234567890',
            'type' => 'student',
            'gender' => 'female',
            'status' => 'active',
            'rating' => 4.9,
            'total_ratings' => 180,
            'total_walks' => 160,
            'completed_walks' => 156,
            'cancelled_walks' => 4,
            'id_proof_type' => 'student_id',
            'id_proof_number' => 'STU2025001',
            'verification_status' => 'verified',
            'description' => 'Dedicated student volunteer committed to ensuring campus safety.'
        ],
        [
            'escort_id' => 'ESC-2025-002',
            'name' => 'Michael Chen',
            'email' => 'michael.c@university.edu',
            'phone' => '2345678901',
            'type' => 'student',
            'gender' => 'male',
            'status' => 'active',
            'rating' => 4.8,
            'total_ratings' => 150,
            'total_walks' => 140,
            'completed_walks' => 135,
            'cancelled_walks' => 5,
            'id_proof_type' => 'student_id',
            'id_proof_number' => 'STU2025002',
            'verification_status' => 'verified',
            'description' => 'Experienced volunteer with excellent safety record.'
        ]
    ];

    foreach ($sample_escorts as $escort) {
        $stmt = $conn->prepare("INSERT IGNORE INTO escorts 
            (escort_id, name, email, phone, type, gender, status, rating, 
             total_ratings, total_walks, completed_walks, cancelled_walks,
             id_proof_type, id_proof_number, verification_status, description) 
            VALUES 
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
        $stmt->bind_param("sssssssdiiiiisss", 
            $escort['escort_id'], 
            $escort['name'],
            $escort['email'],
            $escort['phone'],
            $escort['type'],
            $escort['gender'],
            $escort['status'],
            $escort['rating'],
            $escort['total_ratings'],
            $escort['total_walks'],
            $escort['completed_walks'],
            $escort['cancelled_walks'],
            $escort['id_proof_type'],
            $escort['id_proof_number'],
            $escort['verification_status'],
            $escort['description']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting escort: " . $stmt->error);
        }
        $stmt->close();
    }

    echo "Database setup completed successfully!";
} catch(Exception $e) {
    die("Setup failed: " . $e->getMessage());
}

$conn->close();
?>
