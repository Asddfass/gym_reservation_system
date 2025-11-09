<?php
session_start();
include '../includes/Database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') 
{
    header("Location: ../");
    exit();
}

$fn = new Functions();
$totalUsers = count($fn->fetchAll("SELECT * FROM user WHERE role != 'admin'"));
$totalFacilities = count($fn->fetchAll("SELECT * FROM facility"));
$totalReservations = count($fn->getReservations());
$pendingReservations = count($fn->fetchAll("SELECT * FROM reservation WHERE status = 'pending'"));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css"> 
    <link href="../css/admin.css" rel="stylesheet">
</head>
<body>

<div class="admin-content px-4 py-4">
    <div class="content-header d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-semibold text-dark mb-0">System Overview</h2>
    </div>
    
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card-stats bg-crimson text-white shadow-sm w-100">
                <i class="bi bi-people-fill fs-3 mb-2"></i>
                <h4><?= $totalUsers; ?></h4>
                <p>Active Users</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card-stats bg-darkred text-white shadow-sm w-100">
                <i class="bi bi-building fs-3 mb-2"></i>
                <h4><?= $totalFacilities; ?></h4>
                <p>Facilities</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card-stats bg-gray text-white shadow-sm w-100">
                <i class="bi bi-list-task fs-3 mb-2"></i>
                <h4><?= $totalReservations; ?></h4>
                <p>Total Reservations</p>
            </div>
        </div>
        <div class="col-lg-3 col-md-6">
            <div class="card-stats bg-gold text-dark shadow-sm w-100">
                <i class="bi bi-hourglass-split fs-3 mb-2"></i>
                <h4><?= $pendingReservations; ?></h4>
                <p>Pending Approvals</p>
            </div>
        </div>
    </div>

    <div class="card shadow-sm w-100">
        <div class="card-header bg-darkred text-white fw-semibold">Recent Reservations</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
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
                                                 WHERE r.created_at <= NOW()
                                                 ORDER BY r.reservation_id DESC LIMIT 7");
                        if (!empty($recent)):
                            foreach ($recent as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['user_name']); ?></td>
                                    <td><?= htmlspecialchars($row['facility_name']); ?></td>
                                    <td><?= htmlspecialchars($row['date']); ?></td>
                                    <td><?= htmlspecialchars($row['start_time']); ?> - <?= htmlspecialchars($row['end_time']); ?></td>
                                    <td>
                                        <span class="badge bg-<?=
                                            match ($row['status']) 
                                            {
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
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No recent reservations.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>