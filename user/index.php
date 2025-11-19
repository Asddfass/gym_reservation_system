<?php
session_start();
include '../includes/Database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'admin') 
{
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
    <title>User Dashboard | Gym Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="../css/user.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/logo.png" alt="Logo" class="sidebar-logo">
            <h4>User Panel</h4>
        </div>
        
        <div class="sidebar-nav">
            <a href="#" class="nav-link active" data-page="user_overview.php">
                <i class="bi bi-speedometer2"></i> Overview
            </a>
            <a href="#" class="nav-link" data-page="reserve_facility.php">
                <i class="bi bi-calendar-plus"></i> Reserve Facility
            </a>
            <a href="#" class="nav-link" data-page="my_reservations.php">
                <i class="bi bi-calendar-check"></i> My Reservations
            </a>
            <a href="#" class="nav-link" data-page="user_profile.php">
                <i class="bi bi-person-circle"></i> Profile
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
            <a href="../index.php" class="btn btn-light w-100">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </div>

    <!-- Dynamic content -->
    <div class="content">
        <iframe id="content-frame" src="user_overview.php" frameborder="0" width="100%" height="100%" style="min-height: 100vh;"></iframe>
    </div>

    <script>
        // Sidebar Navigation
        const links = document.querySelectorAll('.nav-link');
        const iframe = document.getElementById('content-frame');

        links.forEach(link => {
            link.addEventListener('click', e => {
                e.preventDefault();
                links.forEach(l => l.classList.remove('active'));
                link.classList.add('active');
                iframe.src = link.dataset.page;
            });
        });
    </script>
</body>
</html>