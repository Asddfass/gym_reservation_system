<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'admin') {
    header("Location: ../");
    exit();
}

$user = $_SESSION['user'];
$func = new Functions();
$user_id = $user['user_id'];

// Stats
$total_reservations = $func->fetchOne("SELECT COUNT(*) AS total FROM reservation WHERE user_id = ?", [$user_id], "i")['total'];
$pending = $func->fetchOne("SELECT COUNT(*) AS total FROM reservation WHERE user_id = ? AND status = 'pending'", [$user_id], "i")['total'];
$approved = $func->fetchOne("SELECT COUNT(*) AS total FROM reservation WHERE user_id = ? AND status = 'approved'", [$user_id], "i")['total'];
$cancelled = $func->fetchOne("SELECT COUNT(*) AS total FROM reservation WHERE user_id = ? AND status = 'cancelled'", [$user_id], "i")['total'];

// Recent reservations
$recent_reservations = $func->fetchAll("
    SELECT r.*, f.name AS facility_name
    FROM reservation r
    JOIN facility f ON r.facility_id = f.facility_id
    WHERE r.user_id = ?
    ORDER BY r.reservation_id DESC
    LIMIT 5
", [$user_id], "i");

// Available facilities
$available_facilities = $func->fetchAll("
    SELECT * FROM facility WHERE availability_status = 'Available' ORDER BY name ASC
");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Overview | Gym Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/user.css" rel="stylesheet">
</head>

<body>
    <div class="user-content container-fluid px-4 py-4">
        <div class="content-header mb-4">
            <h3 class="fw-semibold">Dashboard Overview</h3>
        </div>

        <!-- Dashboard Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card card-info bg-crimson text-white text-center p-3 h-100">
                    <i class="bi bi-calendar-check fs-3 mb-2"></i>
                    <h4><?= $total_reservations ?></h4>
                    <p>Total Reservations</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-info bg-gold text-dark text-center p-3 h-100">
                    <i class="bi bi-hourglass-split fs-3 mb-2"></i>
                    <h4><?= $pending ?></h4>
                    <p>Pending</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-info bg-darkred text-white text-center p-3 h-100">
                    <i class="bi bi-check2-circle fs-3 mb-2"></i>
                    <h4><?= $approved ?></h4>
                    <p>Approved</p>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="card card-info bg-gray text-white text-center p-3 h-100">
                    <i class="bi bi-x-circle fs-3 mb-2"></i>
                    <h4><?= $cancelled ?></h4>
                    <p>Cancelled</p>
                </div>
            </div>
        </div>

        <!-- Tables Side by Side (using all horizontal space) -->
        <div class="row g-4">
            <!-- Recent Reservations -->
            <div class="col-lg-6 col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-clock-history"></i> Recent Reservations
                    </div>
                    <div class="card-body table-responsive">
                        <?php if (count($recent_reservations) > 0): ?>
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Facility</th>
                                        <th>Date</th>
                                        <th>Start</th>
                                        <th>End</th>
                                        <th>Status</th>
                                        <th>Purpose</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_reservations as $row): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['facility_name']); ?></td>
                                            <td><?= htmlspecialchars($row['date']); ?></td>
                                            <td><?= htmlspecialchars($row['start_time']); ?></td>
                                            <td><?= htmlspecialchars($row['end_time']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?= $row['status'] === 'approved' ? 'bg-success' : ($row['status'] === 'pending' ? 'bg-warning text-dark' : ($row['status'] === 'cancelled' ? 'bg-secondary' : 'bg-danger')); ?>">
                                                    <?= ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($row['purpose']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center text-muted py-3">No reservations found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Available Facilities -->
            <div class="col-lg-6 col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-building"></i> Available Facilities
                    </div>
                    <div class="card-body table-responsive">
                        <?php if (count($available_facilities) > 0): ?>
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Facility Name</th>
                                        <th>Capacity</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($available_facilities as $facility): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($facility['name']); ?></td>
                                            <td><?= htmlspecialchars($facility['capacity']); ?></td>
                                            <td><span class="badge bg-success"><?= htmlspecialchars($facility['availability_status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center text-muted py-3">No available facilities at the moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script>
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