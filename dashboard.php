<?php
session_start();
include 'includes/Database.php';
include 'includes/functions.php';

if (!isset($_SESSION['user'])) 
{
    header("Location: index.php");
    exit();
}

$fn = new Functions();
$user = $_SESSION['user'];
$facilities = $fn->getFacilities('available');

// Fetch only the current user's reservations
$reservations = $fn->fetchAll(" SELECT r.reservation_id, f.name AS facility_name, r.date, r.start_time, r.end_time, r.status
                                FROM reservation r
                                JOIN facility f ON r.facility_id = f.facility_id
                                WHERE r.user_id = ?
                                ORDER BY r.date DESC
                                ", [$user['user_id']], "i");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Gymnasium Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-4">
            <img src="assets/logo.png" alt="Gym Logo" width="36" height="36" class="me-2">
            <div class="d-flex align-items-center text-white ms-auto">
                <span class="me-3">Welcome, <?= htmlspecialchars($user['name']); ?>!</span>
                <a href="index.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container py-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Dashboard</h3>
            <p class="text-muted">Reserve facilities and view your current bookings</p>
        </div>

        <div class="d-flex justify-content-center gap-3 mb-4">
            <a href="reserve.php" class="btn btn-primary px-4 py-2">Reserve Facility</a>
        </div>

        <!-- Available Facilities -->
        <div class="card shadow-sm mb-4">
            <div class="card-header">
                Available Facilities
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Facility Name</th>
                            <th scope="col">Capacity</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($facilities)): ?>
                            <?php foreach ($facilities as $f): ?>
                                <tr>
                                    <td><?= htmlspecialchars($f['name']); ?></td>
                                    <td><?= htmlspecialchars($f['capacity']); ?></td>
                                    <td><span class="badge bg-success"><?= htmlspecialchars($f['availability_status']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-3">No facilities available at the moment.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- My Reservations -->
        <div class="card shadow-sm">
            <div class="card-header">
                My Reservations
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col">Facility</th>
                            <th scope="col">Date</th>
                            <th scope="col">Start</th>
                            <th scope="col">End</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reservations)): ?>
                            <?php foreach ($reservations as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['facility_name']); ?></td>
                                    <td><?= htmlspecialchars($r['date']); ?></td>
                                    <td><?= htmlspecialchars($r['start_time']); ?></td>
                                    <td><?= htmlspecialchars($r['end_time']); ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match ($r['status']) {
                                            'approved' => 'bg-success',
                                            'pending' => 'bg-warning',
                                            'denied' => 'bg-danger',
                                            'cancelled' => 'bg-secondary',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeClass; ?>"><?= htmlspecialchars(ucfirst($r['status'])); ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">You have no reservations yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
