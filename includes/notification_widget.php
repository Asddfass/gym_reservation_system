<?php
// includes/notification_widget.php
// Include this file in your sidebar or header to display the notification bell

if (!isset($_SESSION['user'])) {
    return;
}

$user_id = $_SESSION['user']['user_id'];
?>

<!-- Notification Widget Styles -->
<style>
.notification-bell {
    position: relative;
    display: inline-block;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.notification-bell:hover {
    background: rgba(255,255,255,0.1);
}

.notification-bell i {
    font-size: 1.5rem;
    color: white;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #dc3545;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 0.7rem;
    font-weight: bold;
    min-width: 18px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.1);
    }
}

.notification-dropdown {
    position: fixed; /* Changed from absolute to fixed */
    top: auto;
    right: 20px; /* Position from right edge */
    bottom: 80px; /* Position from bottom */
    width: 380px;
    max-height: 500px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.25);
    display: none;
    z-index: 9999; /* Increased z-index */
    overflow: hidden;
}

.notification-dropdown.show {
    display: block;
    animation: slideUp 0.3s ease;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-header {
    padding: 1rem 1.25rem;
    background: linear-gradient(135deg, #a4161a, #dc143c);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notification-header h6 {
    margin: 0;
    font-weight: 600;
    font-size: 1rem;
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    gap: 1rem;
    align-items: start;
    background: white;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: #fff5f5;
    border-left: 4px solid #dc143c;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.2rem;
}

.notification-icon.success {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.notification-icon.error {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.notification-icon.warning {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.notification-icon.info {
    background: rgba(23, 162, 184, 0.1);
    color: #17a2b8;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #2d3748;
    margin-bottom: 0.25rem;
}

.notification-message {
    font-size: 0.85rem;
    color: #718096;
    margin-bottom: 0.25rem;
    overflow: hidden;
    text-overflow: ellipsis;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
}

.notification-time {
    font-size: 0.75rem;
    color: #a0aec0;
}

.notification-footer {
    padding: 0.75rem 1.25rem;
    background: #f8f9fa;
    text-align: center;
}

.notification-footer a {
    color: #a4161a;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.notification-footer a:hover {
    color: #dc143c;
}

.notification-empty {
    padding: 3rem 1.25rem;
    text-align: center;
    color: #a0aec0;
    background: white;
}

.notification-empty i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Scrollbar for notification list */
.notification-list::-webkit-scrollbar {
    width: 6px;
}

.notification-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.notification-list::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}

.notification-list::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>

<!-- Notification Bell HTML -->
<div class="notification-bell" id="notificationBell">
    <i class="bi bi-bell-fill"></i>
    <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
    
    <div class="notification-dropdown" id="notificationDropdown">
        <div class="notification-header">
            <h6><i class="bi bi-bell"></i> Notifications</h6>
            <button class="btn btn-sm text-white" onclick="markAllAsRead()" style="background: rgba(255,255,255,0.2); border: none; padding: 0.25rem 0.75rem; border-radius: 6px;">
                Mark all read
            </button>
        </div>
        
        <div class="notification-list" id="notificationList">
            <div class="notification-empty">
                <i class="bi bi-bell-slash"></i>
                <p>No notifications</p>
            </div>
        </div>
        
        <div class="notification-footer">
            <a href="<?= isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin' ? 'notifications.php' : 'notifications.php' ?>" target="content-frame">View all notifications</a>
        </div>
    </div>
</div>

<!-- Notification Widget JavaScript -->
<script>
let notificationBell = document.getElementById('notificationBell');
let notificationDropdown = document.getElementById('notificationDropdown');
let notificationList = document.getElementById('notificationList');
let notificationCount = document.getElementById('notificationCount');

// Toggle dropdown
notificationBell.addEventListener('click', function(e) {
    e.stopPropagation();
    notificationDropdown.classList.toggle('show');
    if (notificationDropdown.classList.contains('show')) {
        loadNotifications();
    }
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!notificationBell.contains(e.target) && !notificationDropdown.contains(e.target)) {
        notificationDropdown.classList.remove('show');
    }
});

// Load notification count
function loadNotificationCount() {
    fetch('../api/get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            if (data.count > 0) {
                notificationCount.textContent = data.count > 99 ? '99+' : data.count;
                notificationCount.style.display = 'block';
            } else {
                notificationCount.style.display = 'none';
            }
        })
        .catch(error => console.error('Error loading notification count:', error));
}

// Load notifications
function loadNotifications() {
    fetch('../api/get_recent_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.notifications && data.notifications.length > 0) {
                displayNotifications(data.notifications);
            } else {
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        <i class="bi bi-bell-slash"></i>
                        <p>No notifications</p>
                    </div>
                `;
            }
        })
        .catch(error => console.error('Error loading notifications:', error));
}

// Display notifications
function displayNotifications(notifications) {
    let html = '';
    notifications.forEach(notif => {
        const iconClass = getIconClass(notif.type);
        const icon = getIcon(notif.type);
        const unreadClass = notif.is_read == 0 ? 'unread' : '';
        const timeAgo = formatTimeAgo(notif.created_at);
        
        html += `
            <div class="notification-item ${unreadClass}" onclick="markAsRead(${notif.notification_id})">
                <div class="notification-icon ${notif.type}">
                    <i class="bi bi-${icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(notif.title)}</div>
                    <div class="notification-message">${escapeHtml(notif.message)}</div>
                    <div class="notification-time"><i class="bi bi-clock"></i> ${timeAgo}</div>
                </div>
            </div>
        `;
    });
    notificationList.innerHTML = html;
}

// Get icon based on type
function getIcon(type) {
    const icons = {
        'success': 'check-circle-fill',
        'error': 'x-circle-fill',
        'warning': 'exclamation-triangle-fill',
        'info': 'info-circle-fill'
    };
    return icons[type] || icons['info'];
}

// Format time ago
function formatTimeAgo(datetime) {
    const now = new Date();
    const time = new Date(datetime);
    const diff = Math.floor((now - time) / 1000);
    
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hr ago';
    if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
    
    return time.toLocaleDateString();
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Mark notification as read
function markAsRead(notificationId) {
    fetch('../api/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotificationCount();
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking notification as read:', error));
}

// Mark all as read
function markAllAsRead() {
    fetch('../api/mark_all_notifications.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotificationCount();
            loadNotifications();
        }
    })
    .catch(error => console.error('Error marking all notifications as read:', error));
}

// Load notification count on page load and refresh every 30 seconds
loadNotificationCount();
setInterval(loadNotificationCount, 10000);
</script>