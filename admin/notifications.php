<?php
session_start();
include '../includes/Database.php';
include '../includes/functions.php';
include '../includes/NotificationManager.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../");
    exit();
}

$user = $_SESSION['user'];
$user_id = $user['user_id'];
$notifManager = new NotificationManager();

// Handle actions
if (isset($_GET['action'])) {
    if ($_GET['action'] === 'mark_read' && isset($_GET['id'])) {
        $notifManager->markAsRead($_GET['id'], $user_id);
        header("Location: notifications.php");
        exit();
    } elseif ($_GET['action'] === 'mark_all_read') {
        $notifManager->markAllAsRead($user_id);
        header("Location: notifications.php");
        exit();
    } elseif ($_GET['action'] === 'delete' && isset($_GET['id'])) {
        $notifManager->deleteNotification($_GET['id'], $user_id);
        header("Location: notifications.php");
        exit();
    }
}

// Get all notifications
$notifications = $notifManager->getAllNotifications($user_id, 50);
$unreadCount = $notifManager->getUnreadCount($user_id);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        .notification-card {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s ease;
            margin-bottom: 12px;
        }

        .notification-card.unread {
            background-color: #fff5f5;
            border-left-color: #dc143c;
        }

        .notification-card:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .notification-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
        }

        .notification-icon.success {
            background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.2));
            color: #28a745;
        }

        .notification-icon.error {
            background: linear-gradient(135deg, rgba(220, 53, 69, 0.1), rgba(220, 53, 69, 0.2));
            color: #dc3545;
        }

        .notification-icon.warning {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.2));
            color: #ffc107;
        }

        .notification-icon.info {
            background: linear-gradient(135deg, rgba(23, 162, 184, 0.1), rgba(23, 162, 184, 0.2));
            color: #17a2b8;
        }

        .notification-title {
            font-weight: 600;
            font-size: 1rem;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .notification-message {
            font-size: 0.9rem;
            color: #718096;
            margin-bottom: 8px;
        }

        .notification-time {
            font-size: 0.8rem;
            color: #a0aec0;
        }

        .action-btn {
            padding: 4px 8px;
            font-size: 0.85rem;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #a0aec0;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .filter-tabs {
            border-bottom: 2px solid #e9ecef;
            margin-bottom: 24px;
        }

        .filter-tab {
            padding: 12px 24px;
            border: none;
            background: none;
            color: #6c757d;
            font-weight: 500;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
        }

        .filter-tab.active {
            color: #dc143c;
        }

        .filter-tab.active::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(135deg, #a4161a, #dc143c);
        }

        .card-info {
            background: white;
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .card-info:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .card-info i {
            font-size: 2.5rem;
            opacity: 0.9;
            margin-bottom: 1rem;
            display: block;
        }

        .card-info h4 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .card-info p {
            font-size: 0.95rem;
            opacity: 0.8;
            margin: 0;
            font-weight: 500;
        }
    </style>
</head>

<body>
    <div class="admin-content px-4 py-4">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-semibold text-dark mb-0">Notifications</h2>
                <p class="text-muted mb-0 mt-1">Stay updated with reservation requests and system updates</p>
            </div>
            <?php if ($unreadCount > 0): ?>
                <a href="?action=mark_all_read" class="btn btn-darkred">
                    <i class="bi bi-check-all"></i> Mark All as Read
                </a>
            <?php endif; ?>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card-info bg-crimson text-white">
                    <i class="bi bi-bell-fill"></i>
                    <h4><?= count($notifications) ?></h4>
                    <p>Total Notifications</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-info bg-gold text-dark">
                    <i class="bi bi-envelope-fill"></i>
                    <h4><?= $unreadCount ?></h4>
                    <p>Unread</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-info bg-darkred text-white">
                    <i class="bi bi-envelope-open-fill"></i>
                    <h4><?= count($notifications) - $unreadCount ?></h4>
                    <p>Read</p>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="filter-tabs">
            <button class="filter-tab active" data-filter="all">All</button>
            <button class="filter-tab" data-filter="unread">Unread</button>
            <button class="filter-tab" data-filter="success">Success</button>
            <button class="filter-tab" data-filter="error">Errors</button>
            <button class="filter-tab" data-filter="warning">Warnings</button>
            <button class="filter-tab" data-filter="info">Info</button>
        </div>

        <!-- Notifications List -->
        <div id="notificationsList">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="bi bi-bell-slash"></i>
                    <h4>No Notifications Yet</h4>
                    <p>You'll see your notifications here when you receive them</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="card notification-card <?= $notif['is_read'] ? '' : 'unread' ?>"
                        data-read="<?= $notif['is_read'] ?>"
                        data-type="<?= $notif['type'] ?>">
                        <div class="card-body">
                            <div class="d-flex align-items-start">
                                <div class="notification-icon <?= $notif['type'] ?> me-3">
                                    <i class="bi bi-<?=
                                                    match ($notif['type']) {
                                                        'success' => 'check-circle-fill',
                                                        'error' => 'x-circle-fill',
                                                        'warning' => 'exclamation-triangle-fill',
                                                        default => 'info-circle-fill'
                                                    }
                                                    ?>"></i>
                                </div>

                                <div class="flex-grow-1">
                                    <div class="notification-title"><?= htmlspecialchars($notif['title']) ?></div>
                                    <div class="notification-message"><?= htmlspecialchars($notif['message']) ?></div>
                                    <div class="notification-time">
                                        <i class="bi bi-clock"></i> <?= timeAgo($notif['created_at']) ?>
                                    </div>
                                </div>

                                <div class="d-flex gap-2">
                                    <?php if (!$notif['is_read']): ?>
                                        <a href="?action=mark_read&id=<?= $notif['notification_id'] ?>"
                                            class="btn btn-sm btn-outline-success action-btn"
                                            title="Mark as read">
                                            <i class="bi bi-check"></i>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($notif['link']): ?>
                                        <a href="<?= htmlspecialchars($notif['link']) ?>"
                                            class="btn btn-sm btn-outline-primary action-btn"
                                            title="View details">
                                            <i class="bi bi-arrow-right"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="?action=delete&id=<?= $notif['notification_id'] ?>"
                                        class="btn btn-sm btn-outline-danger action-btn"
                                        onclick="return confirm('Delete this notification?')"
                                        title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Filter functionality
        const filterTabs = document.querySelectorAll('.filter-tab');
        const notificationCards = document.querySelectorAll('.notification-card');

        filterTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Update active tab
                filterTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');

                // Filter notifications
                const filter = tab.dataset.filter;

                notificationCards.forEach(card => {
                    if (filter === 'all') {
                        card.style.display = '';
                    } else if (filter === 'unread') {
                        card.style.display = card.dataset.read === '0' ? '' : 'none';
                    } else {
                        card.style.display = card.dataset.type === filter ? '' : 'none';
                    }
                });
            });
        });

        // Notify parent frame about current page
        (function() {
            const currentPage = window.location.pathname.split('/').pop();

            // Announce page on load
            function announcePage() {
                if (window.parent !== window) {
                    window.parent.postMessage({
                        type: 'pageChanged',
                        page: currentPage
                    }, '*');
                }
            }

            // Announce immediately
            announcePage();

            // Listen for parent's request
            window.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'requestPageInfo') {
                    announcePage();
                }
            });

            // Intercept navigation links (for "View details" etc.)
            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[href]');
                if (!link) return;

                const href = link.getAttribute('href');

                // Check if it's an internal page navigation
                if (href && !href.startsWith('http') && !href.startsWith('#') &&
                    !href.includes('?action=') && href.endsWith('.php')) {

                    // Announce the target page
                    const targetPage = href.split('/').pop();
                    if (window.parent !== window) {
                        window.parent.postMessage({
                            type: 'pageChanged',
                            page: targetPage
                        }, '*');
                    }
                }
            });
        })();
    </script>
</body>

</html>

<?php
function timeAgo($datetime)
{
    $time = strtotime($datetime);
    $diff = time() - $time;

    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';

    return date('M d, Y', $time);
}
?>