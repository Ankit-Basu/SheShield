<?php
session_start();
require_once __DIR__ . '/app/models/mysqli_db.php';
echo "<pre style='background:#1a1a2e;color:#fff;padding:20px;font-family:monospace'>";
echo "=== SESSION ===\n";
print_r($_SESSION);
echo "\n=== USERS ===\n";
if (isset($conn) && !$conn->connect_error) {
    $r = $conn->query("SELECT id, first_name, last_name, email, LEFT(password,30) as pass_hash FROM users");
    if ($r && $r->num_rows > 0) while ($row = $r->fetch_assoc()) print_r($row);
    else echo "NO USERS\n";
    echo "\n=== INCIDENTS ===\n";
    $r = $conn->query("SELECT * FROM incidents ORDER BY id DESC LIMIT 5");
    if ($r && $r->num_rows > 0) while ($row = $r->fetch_assoc()) print_r($row);
    else echo "NO INCIDENTS\n";
} else echo "DB FAIL: " . ($conn->connect_error ?? 'unknown');
echo "</pre>";
