<?php
session_start();
include '../includes/NotificationManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$notifManager = new NotificationManager();
$count = $notifManager->getUnreadCount($user_id);

echo json_encode(['count' => $count]);