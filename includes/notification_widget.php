<?php
// includes/notification_widget.php
// Simplified notification bell that acts as a navigation link

if (!isset($_SESSION['user'])) {
    return;
}

$user_id = $_SESSION['user']['user_id'];
$role = $_SESSION['user']['role'];
$notification_page = ($role === 'admin') ? 'notifications.php' : 'notifications.php';
?>

<!-- Notification Bell Styles -->
<style>
.notification-bell {
    position: relative;
    display: inline-block;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    text-decoration: none;
    color: white;
}

.notification-bell:hover {
    background: rgba(255,255,255,0.1);
    color: white;
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
</style>

<!-- Notification Bell HTML -->
<a href="#" class="notification-bell" id="notificationBell" data-page="<?= $notification_page ?>">
    <i class="bi bi-bell-fill"></i>
    <span class="notification-badge" id="notificationCount" style="display: none;">0</span>
</a>

<!-- Notification Bell JavaScript -->
<script>
let notificationBell = document.getElementById('notificationBell');
let notificationCount = document.getElementById('notificationCount');

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

// Handle bell click - navigate to notifications page
notificationBell.addEventListener('click', function(e) {
    e.preventDefault();
    const page = this.dataset.page;
    const iframe = document.getElementById('content-frame');
    
    if (iframe) {
        // Remove active class from all nav links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
        
        // Load notifications page in iframe
        iframe.src = page;
        
        // Manually trigger active state update after a brief delay
        setTimeout(() => {
            document.querySelectorAll('.nav-link').forEach(link => {
                if (link.dataset.page === page) {
                    link.classList.add('active');
                }
            });
        }, 100);
    } else {
        // If no iframe, navigate directly
        window.location.href = page;
    }
});

// Load notification count on page load and refresh every 10 seconds
loadNotificationCount();
setInterval(loadNotificationCount, 10000);
</script>
