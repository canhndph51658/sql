<?php
// --- Kết nối PDO ---
$dsn = 'mysql:host=localhost;dbname=day-2;charset=utf8mb4';
$username = 'root';
$password = '';
$options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
