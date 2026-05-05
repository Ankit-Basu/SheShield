<?php
$cfgPath = __DIR__ . '/../app/config/database.php';
if (!file_exists($cfgPath)) $cfgPath = __DIR__ . '/../config/database.php';
require_once $cfgPath;

$database = new Database();
$conn = $database->getConnection();
?>
