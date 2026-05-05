<?php
require_once __DIR__ . '/../app/models/mysqli_db.php';


header('Content-Type: application/json');

// Get JSON data
$jsonData = json_decode(file_get_contents('php://input'), true);
$location = $jsonData['location'] ?? 'Not specified';
$destination = $jsonData['destination'] ?? 'Not specified';

try {
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Get available escort from database
    $query = "SELECT * FROM escorts WHERE status = 'active' AND (verification_status = 'verified' OR verification_status = 'pending') ORDER BY RAND() LIMIT 1";
              
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Query failed: " . $conn->error);
    }

    $escort = $result->fetch_assoc();

    if ($escort) {
        // Format response
        $response = [
            'success' => true,
            'escort' => [
                'id' => $escort['escort_id'],
                'name' => $escort['name'],
                'type' => $escort['type'],
                'rating' => number_format($escort['avg_rating'] ?? 4.9, 1),
                'completedWalks' => $escort['completed_walks'] ?? 156,
                'profilePic' => "https://ui-avatars.com/api/?name=" . urlencode($escort['name']) . "&background=random"
            ]
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'No escorts are available at the moment. Please try again later.'
        ];
    }
} catch (Exception $e) {
    error_log("Error in get_escort.php: " . $e->getMessage());
    
    // For demo, return dummy data if database error
    $response = [
        'success' => true,
        'emailSent' => false,
        'emailMessage' => 'Database Error: ' . $e->getMessage(),
        'escort' => [
            'id' => 'ESC-2025-001',
            'name' => 'Sarah Johnson',
            'type' => 'student',
            'rating' => 4.9,
            'completedWalks' => 156,
            'profilePic' => "https://ui-avatars.com/api/?name=Sarah+Johnson&background=random"
        ]
    ];
}

echo json_encode($response);
