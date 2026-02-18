<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$db   = "mydatabases";
$user = "root";
$pass = "root"; // اگر نداری خالی بگذار ""

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("❌ خطا در اتصال دیتابیس: " . $e->getMessage());
}