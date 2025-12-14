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

// Fetch facilities for dropdown
$facilities = $func->getFacilities();

// --- 1. FILTER AND SEARCH HANDLING ---
$filter_facility = $_GET['facility_id'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Build the dynamic SQL query
$sql = "
    SELECT r.*, f.name AS facility_name
    FROM reservation r
    JOIN facility f ON r.facility_id = f.facility_id
    WHERE r.user_id = ?
";

$params = [$user_id];
$types = "i";

// User reservation count for filters
$reservation_count = count($func->fetchAll($sql, $params, $types));

// Add Facility filter
if ($filter_facility) {
    $sql .= " AND r.facility_id = ?";
    $params[] = intval($filter_facility);
    $types .= "i";
}

// Add Date From filter
if ($filter_date_from) {
    $sql .= " AND r.date >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

// Add Date To filter
if ($filter_date_to) {
    $sql .= " AND r.date <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

// Add Status filter
if ($filter_status && $filter_status !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// DEFAULT SORTING: Order by reservation_id DESC (most recent submission)
$sql .= " ORDER BY r.reservation_id DESC";

// Execute the query
$reservations = $func->fetchAll($sql, $params, $types);

// Calculate status counts
$status_counts = [
    'pending' => 0,
    'approved' => 0,
    'denied' => 0,
    'cancelled' => 0
];
foreach ($reservations as $res) {
    if (isset($status_counts[$res['status']])) {
        $status_counts[$res['status']]++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Reservations | Gym Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/user.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <div class="user-content container-fluid px-4 py-4">
        <div class="content-header mb-4">
            <h3 class="fw-semibold">My Reservations</h3>
            <p class="text-muted mb-0">Review the status and details of your booked facilities.</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="card-stats bg-crimson text-white">
                    <i class="bi bi-list-task fs-3 mb-2"></i>
                    <h4><?= count($reservations) ?></h4>
                    <p class="mb-0">Total Results</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card-stats bg-gold text-dark">
                    <i class="bi bi-hourglass-split fs-3 mb-2"></i>
                    <h4><?= $status_counts['pending'] ?></h4>
                    <p class="mb-0">Pending</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card-stats" style="background: linear-gradient(135deg, #28a745, #20c997); color: white;">
                    <i class="bi bi-check-circle fs-3 mb-2"></i>
                    <h4><?= $status_counts['approved'] ?></h4>
                    <p class="mb-0">Approved</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card-stats bg-darkred text-white">
                    <i class="bi bi-x-circle fs-3 mb-2"></i>
                    <h4><?= $status_counts['denied'] + $status_counts['cancelled'] ?></h4>
                    <p class="mb-0">Denied/Cancelled</p>
                </div>
            </div>
        </div>

        <!-- Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-darkred text-white fw-semibold">
                <i class="bi bi-funnel"></i> Filter Reservations
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label for="facility_id" class="form-label">Facility</label>
                        <select name="facility_id" id="facility_id" class="form-select">
                            <option value="">-- All Facilities --</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?= $facility['facility_id'] ?>"
                                    <?= $filter_facility == $facility['facility_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($facility['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control"
                            value="<?= htmlspecialchars($filter_date_from) ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control"
                            value="<?= htmlspecialchars($filter_date_to) ?>"
                            <?= empty($filter_date_from) ? 'disabled' : '' ?>
                            <?= !empty($filter_date_from) ? 'min="' . htmlspecialchars($filter_date_from) . '"' : '' ?>>
                    </div>

                    <div class="col-md-2">
                        <label for="status" class="form-label">Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="all" <?= $filter_status === 'all' || $filter_status === '' ? 'selected' : '' ?>>-- All Statuses --</option>
                            <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="denied" <?= $filter_status === 'denied' ? 'selected' : '' ?>>Denied</option>
                            <option value="cancelled" <?= $filter_status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-3 text-end d-flex align-items-end gap-2 justify-content-end">
                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="my_reservations.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header">
                        <i class="bi bi-list-task"></i> Reservation Details (<?= count($reservations) ?> Found)
                    </div>
                    <div class="table-scroll-wrapper">
                        <div class="table-responsive">
                            <?php if (count($reservations) > 0): ?>
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>No.</th>
                                            <th>Facility</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <?php $idnum = 0; ?>
                                    <tbody>
                                        <?php foreach ($reservations as $row): ?>
                                            <tr>
                                                <?php $idnum++; ?>
                                                <td><?= $idnum; ?></td>
                                                <td><?= htmlspecialchars($row['facility_name']); ?></td>
                                                <td><?= date('M d, Y', strtotime($row['date'])); ?></td>
                                                <td><?= date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?></td>
                                                <td><?= htmlspecialchars($row['purpose']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = match ($row['status']) {
                                                        'approved' => 'bg-success',
                                                        'pending' => 'bg-warning text-dark',
                                                        'cancelled' => 'bg-secondary',
                                                        default => 'bg-danger',
                                                    };
                                                    ?>
                                                    <span class="badge <?= $status_class; ?>">
                                                        <?= ucfirst($row['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php elseif ($reservation_count > 0): ?>
                                <div class="alert alert-info mb-0 m-3" role="alert">
                                    No reservations found matching your criteria.
                                    <a href="my_reservations.php" class="alert-link">Clear filters.</a>
                                </div>
                            <?php else: ?>
                                <p class="text-center text-muted py-5">You have no reservation.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Date validation: Date To depends on Date From
        const dateFromInput = document.getElementById('date_from');
        const dateToInput = document.getElementById('date_to');

        dateFromInput.addEventListener('change', function() {
            if (this.value) {
                dateToInput.disabled = false;
                dateToInput.min = this.value;
                // Clear date_to if it's before the new date_from
                if (dateToInput.value && dateToInput.value < this.value) {
                    dateToInput.value = '';
                }
            } else {
                dateToInput.disabled = true;
                dateToInput.value = '';
                dateToInput.min = '';
            }
        });

        // Notify parent frame about current page
        (function() {
            const currentPage = window.location.pathname.split('/').pop();

            function announcePage() {
                if (window.parent !== window) {
                    window.parent.postMessage({
                        type: 'pageChanged',
                        page: currentPage
                    }, '*');
                }
            }

            announcePage();

            window.addEventListener('message', function(event) {
                if (event.data && event.data.type === 'requestPageInfo') {
                    announcePage();
                }
            });

            document.addEventListener('click', function(e) {
                const link = e.target.closest('a[href]');
                if (!link) return;

                const href = link.getAttribute('href');

                if (href && !href.startsWith('http') && !href.startsWith('#') &&
                    !href.includes('?action=') && href.endsWith('.php')) {

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