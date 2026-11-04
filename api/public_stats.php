<?php
// Aggregate counts for the landing page. Contains no personal data.
require_once __DIR__ . '/../app/models/mysqli_db.php';
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=60');

$queries = [
    'reports' => "SELECT COUNT(*) FROM incidents",
    'resolved' => "SELECT COUNT(*) FROM incidents WHERE status = 'resolved'",
    'safe_spaces' => "SELECT COUNT(*) FROM safe_spaces",
    'walkers' => "SELECT COUNT(*) FROM escorts WHERE status = 'active'",
];

$stats = [];
foreach ($queries as $key => $sql) {
    $result = $conn ? $conn->query($sql) : false;
    $stats[$key] = $result ? (int)$result->fetch_row()[0] : null;
}

echo json_encode($stats);
