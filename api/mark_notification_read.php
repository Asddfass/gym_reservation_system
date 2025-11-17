<?php
session_start();
include '../includes/NotificationManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (isset($data['notification_id'])) {
    $notifManager = new NotificationManager();
    $success = $notifManager->markAsRead($data['notification_id'], $user_id);
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}