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

// --- 1. FILTER AND SEARCH HANDLING ---
$search_user = $_GET['user'] ?? '';
$search_facility = $_GET['facility'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Build the dynamic SQL query
$sql = "
    SELECT r.*, u.name AS user_name, f.name AS facility_name
    FROM reservation r
    JOIN user u ON r.user_id = u.user_id
    JOIN facility f ON r.facility_id = f.facility_id
    WHERE 1=1
";

$params = [];
$types = "";

// Add User Name search
if ($search_user) 
{
    $sql .= " AND u.name LIKE ?";
    $params[] = '%' . $search_user . '%';
    $types .= "s";
}

// Add Facility Name search
if ($search_facility) 
{
    $sql .= " AND f.name LIKE ?";
    $params[] = '%' . $search_facility . '%';
    $types .= "s";
}

// Add Date filter
if ($filter_date) 
{
    $sql .= " AND r.date = ?";
    $params[] = $filter_date;
    $types .= "s";
}

// Add Status filter
if ($filter_status && $filter_status !== 'all') 
{
    $sql .= " AND r.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// DEFAULT SORTING: Order by reservation_id DESC (most recent submission)
$sql .= " ORDER BY r.reservation_id DESC"; 

// Execute the query
$reservations = $fn->fetchAll($sql, $params, $types);

// Fetch available statuses for the filter dropdown
$statuses = ['all', 'pending', 'approved', 'denied', 'cancelled'];

// Handle approve/deny/cancel actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $reservation_id = $_POST['id'];
    $action = $_POST['action'];
    $status = null;

    if ($action === 'approve') {
        $status = 'approved';
    } elseif ($action === 'deny') {
        $status = 'denied';
    } elseif ($action === 'cancel') {
        $status = 'cancelled';
    }

    if ($status) {
        $fn->execute(
            "UPDATE reservation SET status = ? WHERE reservation_id = ?",
            [$status, $reservation_id],
            "si"
        );
        // Important: Redirect back to the page with existing filters intact
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <link href="../css/manage_reservations.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <style>
        
    </style>
</head>
<body>

<div class="admin-content px-4 py-4">
    <div class="content-header d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-semibold text-dark mb-0">Manage Reservations</h2>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                
                <div class="col-lg-3 col-md-6">
                    <label for="user" class="form-label">Search User Name</label>
                    <input type="text" name="user" id="user" class="form-control" placeholder="E.g., John Doe" value="<?= htmlspecialchars($search_user); ?>">
                </div>

                <div class="col-lg-3 col-md-6">
                    <label for="facility" class="form-label">Search Facility Name</label>
                    <input type="text" name="facility" id="facility" class="form-control" placeholder="E.g., Court A" value="<?= htmlspecialchars($search_facility); ?>">
                </div>

                <div class="col-lg-2 col-md-4">
                    <label for="date" class="form-label">Filter by Date</label>
                    <input type="date" name="date" id="date" class="form-control" value="<?= htmlspecialchars($filter_date); ?>">
                </div>
                
                <div class="col-lg-2 col-md-4">
                    <label for="status" class="form-label">Filter by Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="all">-- All Statuses --</option>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= $status ?>" <?= $filter_status === $status ? 'selected' : '' ?>>
                                <?= ucfirst($status) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-lg-2 col-md-4 d-grid gap-2">
                    <button type="submit" class="btn btn-submit">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
            </form>
            <?php if ($search_user || $search_facility || $filter_date || ($filter_status && $filter_status !== 'all')): ?>
                <div class="mt-3">
                    <span class="text-muted">Showing results for current filters.</span>
                    <a href="manage_reservations.php" class="btn btn-sm btn-secondary ms-2">Clear Filters</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="content-body">
        <div class="card shadow-sm">
            <div class="card-header bg-darkred text-white fw-semibold">
                Reservation Records (<?= count($reservations); ?> Found)
            </div>
            
            <!-- WRAPPER FOR SCROLLING -->
            <div class="table-scroll-wrapper">
                <div class="table-responsive">
                    <table class="table table-hover table-striped align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Facility</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Created At</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($reservations)): ?>
                                <?php foreach ($reservations as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['user_name']); ?></td>
                                        <td><?= htmlspecialchars($row['facility_name']); ?></td>
                                        <td><?= htmlspecialchars($row['date']); ?></td>
                                        <td><?= htmlspecialchars($row['start_time']); ?> - <?= htmlspecialchars($row['end_time']); ?></td>
                                        <td><?= htmlspecialchars($row['purpose']); ?></td>
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
                                        <td><?= date("Y-m-d", strtotime($row['created_at'])); ?></td>
                                        <td class="text-center">
                                            <?php if ($row['status'] === 'pending'): ?>
                                                <form method="POST" action="" class="d-inline">
                                                    <input type="hidden" name="id" value="<?= $row['reservation_id']; ?>">
                                                    <button name="action" value="approve" class="btn btn-sm btn-approve me-1">Approve</button>
                                                    <button name="action" value="deny" class="btn btn-sm btn-deny">Deny</button>
                                                </form>
                                            <?php elseif ($row['status'] === 'approved'): ?>
                                                <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this APPROVED reservation?');">
                                                    <input type="hidden" name="id" value="<?= $row['reservation_id']; ?>" >
                                                    <button name="action" value="cancel" class="btn btn-sm btn-cancel">Cancel</button>
                                                </form>
                                            <?php else: ?>
                                                <em class="text-muted">No actions</em>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-3">No reservations found matching the current filters.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- WRAPPER FOR SCROLLING -->
        </div>
    </div>
</div>

</body>
</html>