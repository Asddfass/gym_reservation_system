<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../");
    exit();
}

$user = $_SESSION['user'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Gym Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="../css/admin.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/logo.png" alt="Logo" class="sidebar-logo">
            <h4>Admin Panel</h4>
        </div>

        <div class="sidebar-nav">
            <a href="#" class="nav-link active" data-page="admin_overview.php">
                <i class="bi bi-speedometer2"></i> Overview
            </a>
            <a href="#" class="nav-link" data-page="manage_reservations.php">
                <i class="bi bi-calendar-check"></i> Manage Reservations
            </a>
            <a href="#" class="nav-link" data-page="manage_facilities.php">
                <i class="bi bi-building"></i> Manage Facilities
            </a>
            <a href="#" class="nav-link" data-page="admin_reserve.php">
                <i class="bi bi-calendar-plus"></i> Reserve
            </a>
            <a href="#" class="nav-link" data-page="admin_reports.php">
                <i class="bi bi-graph-up"></i> Reports & Analytics
            </a>
            <a href="#" class="nav-link" data-page="calendar_view.php">
                <i class="bi bi-calendar3"></i> Calendar View
            </a>
            <a href="#" class="nav-link" data-page="manage_accounts.php">
                <i class="bi bi-people"></i> Manage Accounts
            </a>
        </div>

        <div class="sidebar-footer">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <p class="mb-0"><strong><?= htmlspecialchars($user['name']); ?></strong></p>
                    <small class="text-white-50"><?= ucfirst($user['role']); ?></small>
                </div>
                <?php include '../includes/notification_widget.php'; ?>
            </div>
            <button type="button" class="btn btn-light w-100" id="logoutBtn">
                <i class="bi bi-box-arrow-right"></i> Logout
            </button>
        </div>
    </div>

    <!-- Dynamic Content Area -->
    <div class="content">
        <iframe id="content-frame" name="content-frame" src="admin_overview.php" frameborder="0" width="100%"
            height="100%" style="min-height: 100vh;"></iframe>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="logoutModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>Confirm Logout
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-box-arrow-right" style="font-size: 3rem; color: #dc3545;"></i>
                    <h5 class="mt-3">Are you sure you want to logout?</h5>
                    <p class="text-muted">You will need to login again to access the system.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle"></i> Cancel
                    </button>
                    <a href="../index.php" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Yes, Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar navigation logic
        const links = document.querySelectorAll('.nav-link');
        const iframe = document.getElementById('content-frame');

        // Page to nav-link mapping
        const pageMapping = {
            'admin_overview.php': 'admin_overview.php',
            'manage_reservations.php': 'manage_reservations.php',
            'manage_facilities.php': 'manage_facilities.php',
            'admin_reserve.php': 'admin_reserve.php',
            'admin_reports.php': 'admin_reports.php',
            'calendar_view.php': 'calendar_view.php',
            'manage_accounts.php': 'manage_accounts.php',
            'notifications.php': 'notifications.php'
        };

        // Function to update active nav link
        function updateActiveNavLink(pageName) {
            links.forEach(link => {
                link.classList.remove('active');
                if (link.dataset.page === pageName) {
                    link.classList.add('active');
                }
            });
        }

        // Handle nav link clicks
        links.forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                links.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                iframe.src = link.dataset.page;
            });
        });

        // Listen for page change messages from iframe
        window.addEventListener('message', function (event) {
            if (event.data && event.data.type === 'pageChanged') {
                const pageName = event.data.page;
                if (pageName && pageMapping[pageName]) {
                    updateActiveNavLink(pageName);
                }
            }
        });

        // Monitor iframe load events
        iframe.addEventListener('load', function () {
            // Send a request to iframe to announce its page
            try {
                iframe.contentWindow.postMessage({
                    type: 'requestPageInfo'
                }, '*');
            } catch (e) {
                console.log('Could not communicate with iframe:', e);
            }
        });

        // Logout button handler
        document.getElementById('logoutBtn').addEventListener('click', function () {
            const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
            logoutModal.show();
        });
    </script>
</body>

</html>