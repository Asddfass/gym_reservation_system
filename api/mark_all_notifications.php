<?php
session_start();
include '../includes/NotificationManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false]);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$notifManager = new NotificationManager();
$success = $notifManager->markAllAsRead($user_id);

echo json_encode(['success' => $success]);