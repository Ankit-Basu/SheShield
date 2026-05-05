<?php
require_once __DIR__ . '/app/models/mysqli_db.php';
$r = $conn->query('DESCRIBE users');
while($row = $r->fetch_assoc()) echo $row['Field'] . "\n";
echo "---\n";
$r = $conn->query("SHOW TABLES LIKE 'profile_images'");
echo "profile_images table: " . ($r->num_rows > 0 ? "EXISTS" : "MISSING") . "\n";
$r = $conn->query("SHOW TABLES LIKE 'full_name'");
$r2 = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='full_name'");
echo "full_name column: " . ($r2->num_rows > 0 ? "EXISTS" : "MISSING") . "\n";
