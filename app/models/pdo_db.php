<?php
$pdo_host = 'localhost';
$pdo_dbname = 'sheshield';
$pdo_user = 'root';
$pdo_pass = '';

try {
    $pdo = new PDO("mysql:host=$pdo_host;dbname=$pdo_dbname", $pdo_user, $pdo_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}
?>