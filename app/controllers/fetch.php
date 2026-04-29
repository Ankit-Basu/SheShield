<?php
$servername = "localhost";
$username = "root"; // Change if needed
$password = ""; // Change if needed
$dbname = "campus_safety_db"; // Updated database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch incidents
$sql = "SELECT id, latitude, longitude, description, reported_at FROM incidents ORDER BY reported_at DESC";
$result = $conn->query($sql);

$incidents = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $incidents[] = $row;
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($incidents);

// Close connection
$conn->close();
?>
