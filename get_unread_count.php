<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['count' => 0]);
    exit;
}

require_once "db.php";

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_courses WHERE user_id = ? AND status = 'rejected' AND notification_read = 0");
    $stmt->execute([$_SESSION['user_id']]);
    $count = $stmt->fetchColumn();
    
    header('Content-Type: application/json');
    echo json_encode(['count' => $count]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['count' => 0]);
}
?>