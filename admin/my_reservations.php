<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../");
    exit();
}

$user = $_SESSION['user'];
$func = new Functions();
$user_id = $user['user_id'];

// --- 1. FILTER AND SEARCH HANDLING ---
$search_facility = $_GET['facility'] ?? '';
$filter_date = $_GET['date'] ?? '';
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

// Add Facility Name search
if ($search_facility) {
    $sql .= " AND f.name LIKE ?";
    $params[] = '%' . $search_facility . '%';
    $types .= "s";
}


// Add Date filter
if ($filter_date) {
    $sql .= " AND r.date = ?";
    $params[] = $filter_date;
    $types .= "s";
}

// Add Status filter
if ($filter_status && $filter_status !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// DEFAULT SORTING: Order by reservation_id DESC (most recent submission)
// If you meant sorting by date/time, change 'r.reservation_id DESC' to 'r.date DESC, r.start_time ASC'
$sql .= " ORDER BY r.reservation_id DESC";

// Execute the query
$reservations = $func->fetchAll($sql, $params, $types);

// Fetch available statuses for the filter dropdown
$statuses = ['all', 'pending', 'approved', 'cancelled'];
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

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">

                    <div class="col-md-4">
                        <label for="facility" class="form-label">Search Facility Name</label>
                        <input type="text" name="facility" id="facility" class="form-control" placeholder="E.g., Gymn, Court" value="<?= htmlspecialchars($search_facility); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="date" class="form-label">Filter by Date</label>
                        <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($filter_date); ?>">
                    </div>

                    <div class="col-md-3">
                        <label for="status" class="form-label">Filter by Status</label>
                        <select name="status" id="status" class="form-select">
                            <option value="all">-- All Statuses --</option>
                            <?php foreach ($statuses as $status): ?>
                                <?php if ($status !== 'all'): ?>
                                    <option value="<?= $status ?>" <?= $filter_status === $status ? 'selected' : '' ?>>
                                        <?= ucfirst($status) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2 d-grid gap-2">
                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white fw-semibold">
                        <i class="bi bi-list-task"></i> Reservation Details
                    </div>
                    <div class="card-body table-responsive">
                        <?php if (count($reservations) > 0): ?>
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
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
                                            <?php
                                            $idnum++;
                                            ?>
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
                            <div class="alert alert-info mb-0" role="alert">
                                No reservations found matching your criteria.
                                <a href="my_reservations.php" class="alert-link">Clear filters.</a>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted py-3">You have no reservation.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
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