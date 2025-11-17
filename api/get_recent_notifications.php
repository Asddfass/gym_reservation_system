<?php
// api/get_recent_notifications.php
session_start();
include '../includes/NotificationManager.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['notifications' => []]);
    exit;
}

$user_id = $_SESSION['user']['user_id'];
$notifManager = new NotificationManager();
$notifications = $notifManager->getRecentUnread($user_id, 5);

echo json_encode(['notifications' => $notifications]);