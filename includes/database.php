<?php
// Database connection file
$cfgPath = __DIR__ . '/../app/config/database.php';
if (!file_exists($cfgPath)) $cfgPath = __DIR__ . '/../config/database.php';
require_once $cfgPath;

// This file serves as a central point for database operations
// It includes the database configuration and provides common database functions

// You can add additional database utility functions here
?>
