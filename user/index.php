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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/user.css" rel="stylesheet">
</head>

<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <img src="../assets/logo.png" alt="Logo" class="sidebar-logo">
            <h4>User Panel</h4>
        </div>
        <a href="#" class="nav-link active" data-page="user_overview.php">Overview</a>
        <a href="#" class="nav-link" data-page="reserve_facility.php">Reserve Facility</a>
        <a href="#" class="nav-link" data-page="my_reservations.php">My Reservations</a>
        <a href="#" class="nav-link" data-page="user_profile.php">Profile</a>

        <div class="sidebar-footer">
            <p class="mb-2">Welcome, <?= htmlspecialchars($user['name']); ?></p>
            <a href="../index.php" class="btn btn-light w-100">Logout</a>
        </div>
    </div>

    <!-- Dynamic content -->
    <div class="content">
        <iframe id="content-frame" src="user_overview.php" frameborder="0" width="100%" height="1000px"></iframe>
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
