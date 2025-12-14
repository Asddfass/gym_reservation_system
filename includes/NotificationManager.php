<?php
include_once 'database.php';

class NotificationManager 
{
    protected $db;

    public function __construct() 
    {
        $this->db = new Database();
    }

    /**
     * Create a new notification
     */
    public function createNotification($user_id, $title, $message, $type = 'info', $link = null) 
    {
        $sql = "INSERT INTO notification (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $title, $message, $type, $link);
        return $stmt->execute();
    }

    /**
     * Get all notifications for a user
     */
    public function getAllNotifications($user_id, $limit = 50) 
    {
        $sql = "SELECT * FROM notification WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get unread notifications count
     */
    public function getUnreadCount($user_id) 
    {
        $sql = "SELECT COUNT(*) as count FROM notification WHERE user_id = ? AND is_read = 0";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }

    /**
     * Get recent unread notifications
     */
    public function getRecentUnread($user_id, $limit = 5) 
    {
        $sql = "SELECT * FROM notification WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("ii", $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) 
    {
        $sql = "UPDATE notification SET is_read = 1 WHERE notification_id = ? AND user_id = ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("ii", $notification_id, $user_id);
        return $stmt->execute();
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead($user_id) 
    {
        $sql = "UPDATE notification SET is_read = 1 WHERE user_id = ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        return $stmt->execute();
    }

    /**
     * Delete a notification
     */
    public function deleteNotification($notification_id, $user_id) 
    {
        $sql = "DELETE FROM notification WHERE notification_id = ? AND user_id = ?";
        $stmt = $this->db->conn->prepare($sql);
        $stmt->bind_param("ii", $notification_id, $user_id);
        return $stmt->execute();
    }

    /**
     * Create notification for reservation status change
     */
    public function notifyReservationStatus($user_id, $status, $facilityName, $date, $time) 
    {
        $statusMessages = [
            'approved' => [
                'title' => '✅ Reservation Approved',
                'message' => "Your reservation for {$facilityName} on {$date} at {$time} has been approved!",
                'type' => 'success'
            ],
            'denied' => [
                'title' => '❌ Reservation Denied',
                'message' => "Your reservation for {$facilityName} on {$date} at {$time} has been denied.",
                'type' => 'error'
            ],
            'cancelled' => [
                'title' => '⚠️ Reservation Cancelled',
                'message' => "Your reservation for {$facilityName} on {$date} at {$time} has been cancelled.",
                'type' => 'warning'
            ]
        ];

        if (isset($statusMessages[$status])) {
            $info = $statusMessages[$status];
            return $this->createNotification(
                $user_id,
                $info['title'],
                $info['message'],
                $info['type'],
                'my_reservations.php'
            );
        }

        return false;
    }
}
?>