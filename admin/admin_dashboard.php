<?php
session_start();
include '../includes/Database.php';
include '../includes/functions.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') 
{
    header("Location: ../index.php");
    exit();
}

$fn = new Functions();
$user = $_SESSION['user'];

// Get data for dashboard cards
$totalUsers = count($fn->fetchAll("SELECT * FROM user WHERE role != 'admin'"));
$totalFacilities = count($fn->fetchAll("SELECT * FROM facility"));
$totalReservations = count($fn->fetchAll("SELECT * FROM reservation"));
$pendingReservations = count($fn->fetchAll("SELECT * FROM reservation WHERE status = 'pending'"));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Gymnasium Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin_dashboard.css" rel="stylesheet">
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="../assets/logo.png" alt="Gym Logo">
                Admin Dashboard
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">Welcome, <?= htmlspecialchars($user['name']); ?>!</span>
                <a href="../index.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container py-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Overview</h3>
            <p class="text-muted">System statistics at a glance</p>
        </div>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4 text-center">
            <div class="col-md-3">
                <div class="card-stats bg-crimson">
                    <h4><?= $totalUsers; ?></h4>
                    <p>Active Users</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats bg-darkred">
                    <h4><?= $totalFacilities; ?></h4>
                    <p>Facilities</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats bg-gray">
                    <h4><?= $totalReservations; ?></h4>
                    <p>Total Reservations</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card-stats bg-gold">
                    <h4><?= $pendingReservations; ?></h4>
                    <p>Pending Approvals</p>
                </div>
            </div>
        </div>

        <!-- Management Buttons -->
        <div class="d-flex justify-content-center gap-3 mb-4">
            <a href="manage_reservations.php" class="btn btn-manage px-4">Manage Reservations</a>
            <a href="facilities.php" class="btn btn-manage px-4">Manage Facilities</a>
        </div>

        <!-- Latest Reservations -->
        <div class="card shadow-sm">
            <div class="card-header">Recent Reservations</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent = $fn->fetchAll("SELECT r.*, u.name AS user_name, f.name AS facility_name 
                FROM reservation r 
                JOIN user u ON r.user_id = u.user_id 
                JOIN facility f ON r.facility_id = f.facility_id 
                ORDER BY r.date DESC, r.start_time DESC 
                LIMIT 5");

                        if (!empty($recent)):
                            foreach ($recent as $row):
                        ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['user_name']); ?></td>
                                    <td><?= htmlspecialchars($row['facility_name']); ?></td>
                                    <td><?= htmlspecialchars($row['date']); ?></td>
                                    <td><?= htmlspecialchars($row['start_time']); ?> - <?= htmlspecialchars($row['end_time']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php
                                                                echo match ($row['status']) {
                                                                    'approved' => 'success',
                                                                    'denied' => 'danger',
                                                                    'cancelled' => 'secondary',
                                                                    default => 'warning'
                                                                };
                                                                ?>">
                                            <?= ucfirst($row['status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php
                            endforeach;
                        else:
                            ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No recent reservations.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>