<?php
session_start();
require_once 'mysqli_db.php';
require_once 'includes/EmailHelper.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['available_from']) || !isset($data['available_to']) || !isset($data['preferred_areas'])) {
        throw new Exception('Missing required fields');
    }

    // Extract data
    $available_from = $data['available_from'];
    $available_to = $data['available_to'];
    $preferred_areas = $data['preferred_areas'];
    
    // Convert datetime to MySQL format
    $from_datetime = new DateTime($available_from);
    $to_datetime = new DateTime($available_to);
    
    // Extract time and day of week
    $start_time = $from_datetime->format('H:i:s');
    $end_time = $to_datetime->format('H:i:s');
    $day_of_week = strtolower($from_datetime->format('l'));
    
    // Generate escort ID
    $id = 'ESC-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    // Convert preferred areas to JSON
    $preferred_areas_json = json_encode($preferred_areas);
    
    error_log("Received volunteer data: " . print_r($data, true));
    error_log("Inserting into escorts_schedule: escort_id=$id, day=$day_of_week, from=$start_time, to=$end_time");
    
    // First insert/update the escorts table
    $stmt = $conn->prepare("
        INSERT INTO escorts (
            escort_id, name, email, phone, type, gender, 
            status, rating, total_ratings, total_walks, completed_walks, cancelled_walks
        ) VALUES (?, ?, ?, ?, ?, ?,
            'active', 0.00, 0, 0, 0, 0
        )
    ");

    $stmt->bind_param(
        "ssssss",
        $escort_id,
        $name,
        $email,
        $phone,
        $type,
        $gender
    );

    // Set values
    $escort_id = $id;
    $name = "Volunteer";
    $email = "ankitbasu960@gmail.com";
    $phone = "";
    $type = "student";
    $gender = "other";

    if (!$stmt->execute()) {
        throw new Exception("Failed to create escort record: " . $stmt->error);
    }

    error_log("Successfully inserted/updated escorts table");

    // Now insert into escorts_schedule table
    $stmt = $conn->prepare("INSERT INTO escorts_schedule (
        escort_id, day_of_week, start_time, end_time, status
    ) VALUES (?, ?, ?, ?, ?)");
    
    $status = 'active'; // Set status explicitly
    $stmt->bind_param("sssss", 
        $id,
        $day_of_week,
        $start_time,
        $end_time,
        $status
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to create schedule: " . $stmt->error);
    }

    error_log("Successfully inserted into escorts_schedule");

    // Send welcome email
    try {
        $emailHelper = new EmailHelper();
        $emailHelper->sendVolunteerRegistrationEmail($email, $name);
        
        echo json_encode([
            'success' => true,
            'message' => 'Your volunteer application has been submitted successfully and welcome email sent.',
            'escort_id' => $id
        ]);
    } catch (Exception $e) {
        error_log("Failed to send welcome email: " . $e->getMessage());
        echo json_encode([
            'success' => true,
            'message' => 'Your volunteer application has been submitted successfully but welcome email failed to send.',
            'escort_id' => $id
        ]);
    }

} catch (Exception $e) {
    error_log("Error in register_volunteer_handler: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
