<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

// API endpoint for calendar events - return JSON
header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$fn = new Functions();
$isAdmin = $_SESSION['user']['role'] === 'admin';

// Get date range from request
$start = $_GET['start'] ?? date('Y-m-01');
$end = $_GET['end'] ?? date('Y-m-t');

// For admins: show all reservations
// For users: show only their own and approved ones
if ($isAdmin) {
    $sql = "SELECT r.*, u.name AS user_name, f.name AS facility_name
            FROM reservation r
            JOIN user u ON r.user_id = u.user_id
            JOIN facility f ON r.facility_id = f.facility_id
            WHERE r.date BETWEEN ? AND ?
            ORDER BY r.date, r.start_time";
    $reservations = $fn->fetchAll($sql, [$start, $end], "ss");
} else {
    $userId = $_SESSION['user']['user_id'];
    $sql = "SELECT r.*, u.name AS user_name, f.name AS facility_name
            FROM reservation r
            JOIN user u ON r.user_id = u.user_id
            JOIN facility f ON r.facility_id = f.facility_id
            WHERE r.date BETWEEN ? AND ?
            AND (r.user_id = ? OR r.status = 'approved')
            ORDER BY r.date, r.start_time";
    $reservations = $fn->fetchAll($sql, [$start, $end, $userId], "ssi");
}

// Format for FullCalendar
$events = [];
foreach ($reservations as $res) {
    // Status colors
    $color = match ($res['status']) {
        'approved' => '#28a745',
        'denied' => '#dc3545',
        'cancelled' => '#6c757d',
        default => '#ffc107'  // pending
    };

    $textColor = $res['status'] === 'pending' ? '#000' : '#fff';

    $events[] = [
        'id' => $res['reservation_id'],
        'title' => $res['facility_name'],
        'start' => $res['date'] . 'T' . $res['start_time'],
        'end' => $res['date'] . 'T' . $res['end_time'],
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => $textColor,
        'extendedProps' => [
            'facility' => $res['facility_name'],
            'facility_id' => $res['facility_id'],
            'user' => $res['user_name'],
            'purpose' => $res['purpose'],
            'status' => $res['status'],
            'date' => $res['date'],
            'time' => date('h:i A', strtotime($res['start_time'])) . ' - ' . date('h:i A', strtotime($res['end_time']))
        ]
    ];
}

echo json_encode($events);
