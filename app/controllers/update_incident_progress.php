<?php
session_start();

header('Content-Type: application/json');

// Handle feedback submission
if (isset($_POST['rating'])) {
    $rating = intval($_POST['rating']);
    $feedback = isset($_POST['feedback']) ? $_POST['feedback'] : '';
    
    // Store in session for now (in a real app, you'd save to database)
    $_SESSION['user_rating'] = $rating;
    $_SESSION['user_feedback'] = $feedback;
    
    echo json_encode([
        'success' => true,
        'message' => 'Feedback submitted successfully'
    ]);
    exit;
}

// Handle progress updates
if (!isset($_POST['field'])) {
    echo json_encode(['success' => false, 'message' => 'No field specified']);
    exit;
}

$field = $_POST['field'];
$validFields = ['security_confirmed', 'victim_safe', 'resolution_confirmed'];

if (!in_array($field, $validFields)) {
    echo json_encode(['success' => false, 'message' => 'Invalid field']);
    exit;
}

// Toggle the field's value
$_SESSION[$field] = !isset($_SESSION[$field]) || !$_SESSION[$field];

// Calculate new progress
$progress = 0;
if (isset($_SESSION['security_confirmed']) && $_SESSION['security_confirmed']) $progress += 33;
if (isset($_SESSION['victim_safe']) && $_SESSION['victim_safe']) $progress += 33;
if (isset($_SESSION['resolution_confirmed']) && $_SESSION['resolution_confirmed']) $progress += 34;

echo json_encode([
    'success' => true,
    'progress' => $progress,
    'field' => $field,
    'value' => $_SESSION[$field]
]);