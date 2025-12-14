<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../");
    exit();
}

$fn = new Functions();

// Optimized COUNT(*) queries
$totalUsers = $fn->getCount('user', "role != 'admin'");
$totalFacilities = $fn->getCount('facility');
$totalReservations = $fn->getCount('reservation');
$pendingReservations = $fn->getCount('reservation', "status = 'pending'");

// Chart Data: Reservations per day (last 7 days)
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $count = $fn->fetchOne(
        "SELECT COUNT(*) as count FROM reservation WHERE DATE(created_at) = ?",
        [$date],
        "s"
    );
    $chartData[] = [
        'date' => date('M d', strtotime($date)),
        'count' => (int) ($count['count'] ?? 0)
    ];
}

// Chart Data: Reservations by status
$statusData = $fn->fetchAll(
    "SELECT status, COUNT(*) as count FROM reservation GROUP BY status"
);

// Chart Data: Facility usage (top 5)
$facilityUsage = $fn->fetchAll(
    "SELECT f.name, COUNT(r.reservation_id) as count 
     FROM facility f 
     LEFT JOIN reservation r ON f.facility_id = r.facility_id 
     GROUP BY f.facility_id 
     ORDER BY count DESC 
     LIMIT 5"
);
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

    <div class="admin-content px-4 py-4">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-semibold text-dark mb-0">System Overview</h2>
        </div>

        <!-- Stats Cards -->
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

        <!-- Charts Row -->
        <div class="row g-3 mb-4">
            <div class="col-lg-8">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-darkred text-white fw-semibold">
                        <i class="bi bi-graph-up"></i> Reservation Trends (Last 7 Days)
                    </div>
                    <div class="card-body">
                        <canvas id="trendsChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-darkred text-white fw-semibold">
                        <i class="bi bi-pie-chart"></i> Status Distribution
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Facility Usage Chart -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-darkred text-white fw-semibold">
                        <i class="bi bi-bar-chart"></i> Top Facility Usage
                    </div>
                    <div class="card-body">
                        <canvas id="facilityChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Reservations Table -->
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
                                                 ORDER BY r.reservation_id DESC LIMIT 5");
                            if (!empty($recent)):
                                foreach ($recent as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['user_name']); ?></td>
                                        <td><?= htmlspecialchars($row['facility_name']); ?></td>
                                        <td><?= htmlspecialchars($row['date']); ?></td>
                                        <td><?= htmlspecialchars($row['start_time']); ?> -
                                            <?= htmlspecialchars($row['end_time']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?=
                                                match ($row['status']) {
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

    <script>
        // Chart.js Configuration
        const chartColors = {
            primary: '#a4161a',
            crimson: '#dc2f02',
            gold: '#ffd60a',
            success: '#28a745',
            danger: '#dc3545',
            warning: '#ffc107',
            secondary: '#6c757d',
            info: '#17a2b8'
        };

        // Reservation Trends Line Chart
        const trendsCtx = document.getElementById('trendsChart').getContext('2d');
        new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($chartData, 'date')) ?>,
                datasets: [{
                    label: 'Reservations',
                    data: <?= json_encode(array_column($chartData, 'count')) ?>,
                    borderColor: chartColors.primary,
                    backgroundColor: 'rgba(164, 22, 26, 0.1)',
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: chartColors.crimson,
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Status Distribution Doughnut Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusLabels = <?= json_encode(array_map(fn($s) => ucfirst($s['status']), $statusData)) ?>;
        const statusCounts = <?= json_encode(array_column($statusData, 'count')) ?>;
        const statusColors = statusLabels.map(label => {
            switch (label.toLowerCase()) {
                case 'approved': return chartColors.success;
                case 'denied': return chartColors.danger;
                case 'pending': return chartColors.warning;
                case 'cancelled': return chartColors.secondary;
                default: return chartColors.info;
            }
        });

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: statusColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15 }
                    }
                }
            }
        });

        // Facility Usage Bar Chart
        const facilityCtx = document.getElementById('facilityChart').getContext('2d');
        new Chart(facilityCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($facilityUsage, 'name')) ?>,
                datasets: [{
                    label: 'Reservations',
                    data: <?= json_encode(array_column($facilityUsage, 'count')) ?>,
                    backgroundColor: [
                        chartColors.primary,
                        chartColors.crimson,
                        chartColors.gold,
                        chartColors.success,
                        chartColors.info
                    ],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });

        // Notify parent frame about current page
        (function () {
            const currentPage = window.location.pathname.split('/').pop();
            function announcePage() {
                if (window.parent !== window) {
                    window.parent.postMessage({ type: 'pageChanged', page: currentPage }, '*');
                }
            }
            announcePage();
            window.addEventListener('message', function (event) {
                if (event.data && event.data.type === 'requestPageInfo') {
                    announcePage();
                }
            });
        })();
    </script>
</body>

</html>