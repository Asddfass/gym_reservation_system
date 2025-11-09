<?php
session_start();
include '../includes/Database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') 
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
    <title>Admin Dashboard | Gym Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/logo.png" alt="Logo" class="sidebar-logo">
            <h4>Admin Panel</h4>
        </div>
        <a href="#" class="nav-link active" data-page="admin_overview.php">Overview</a>
        <a href="#" class="nav-link" data-page="manage_reservations.php">Manage Reservations</a>
        <a href="#" class="nav-link" data-page="manage_facilities.php">Manage Facilities</a>
        <a href="#" class="nav-link" data-page="admin_reserve.php">Reserve</a>
        <a href="#" class="nav-link" data-page="manage_accounts.php">Manage Accounts</a>

        <div class="sidebar-footer">
            <p class="mb-2">Welcome, <?= htmlspecialchars($user['name']); ?></p>
            <a href="../index.php" class="btn btn-light w-100">Logout</a>
        </div>
    </div>

    <!-- Dynamic Content Area -->
    <div class="content">
        <iframe id="content-frame" src="admin_overview.php" frameborder="0" width="100%" height="1000px"></iframe>
    </div>

    <script>
        // Sidebar navigation logic
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
