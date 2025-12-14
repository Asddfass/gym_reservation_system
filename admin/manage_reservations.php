<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';
include '../includes/NotificationManager.php';
include '../includes/Mailer.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../");
    exit();
}

$fn = new Functions();
$notifManager = new NotificationManager();
$mailer = new Mailer();

// --- 1. FILTER AND SEARCH HANDLING ---
$search_user = $_GET['user'] ?? '';
$search_facility = $_GET['facility'] ?? '';
$filter_date = $_GET['date'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Build the dynamic SQL query
$sql = "
    SELECT r.*, u.name AS user_name, u.email AS user_email, f.name AS facility_name
    FROM reservation r
    JOIN user u ON r.user_id = u.user_id
    JOIN facility f ON r.facility_id = f.facility_id
    WHERE 1=1
";

$params = [];
$types = "";

// Add User Name search
if ($search_user) {
    $sql .= " AND u.name LIKE ?";
    $params[] = '%' . $search_user . '%';
    $types .= "s";
}

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
$sql .= " ORDER BY r.reservation_id DESC";

// Execute the query
$reservations = $fn->fetchAll($sql, $params, $types);

// Fetch available statuses for the filter dropdown
$statuses = ['all', 'pending', 'approved', 'denied', 'cancelled'];

// Handle approve/deny/cancel actions with email and notifications
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
        // Get reservation details for email
        $reservation = $fn->fetchOne(
            "SELECT r.*, u.name, u.email, f.name as facility_name 
             FROM reservation r 
             JOIN user u ON r.user_id = u.user_id 
             JOIN facility f ON r.facility_id = f.facility_id 
             WHERE r.reservation_id = ?",
            [$reservation_id],
            "i"
        );

        if ($reservation) {
            // Update status
            $fn->execute(
                "UPDATE reservation SET status = ? WHERE reservation_id = ?",
                [$status, $reservation_id],
                "si"
            );

            // Prepare reservation details for email
            $reservationDetails = [
                'facility' => $reservation['facility_name'],
                'date' => date('F d, Y', strtotime($reservation['date'])),
                'start_time' => date('h:i A', strtotime($reservation['start_time'])),
                'end_time' => date('h:i A', strtotime($reservation['end_time'])),
                'purpose' => $reservation['purpose']
            ];

            // Send email notification
            try {
                $mailer->sendReservationEmail(
                    $reservation['email'],
                    $reservation['name'],
                    $reservationDetails,
                    $status
                );
            } catch (Exception $e) {
                // Log error but don't stop the process
                error_log("Email sending failed: " . $e->getMessage());
            }

            // Create in-app notification
            $notifManager->notifyReservationStatus(
                $reservation['user_id'],
                $status,
                $reservation['facility_name'],
                date('M d, Y', strtotime($reservation['date'])),
                date('h:i A', strtotime($reservation['start_time'])) . ' - ' . date('h:i A', strtotime($reservation['end_time']))
            );
        }

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
</head>

<body>

    <div class="admin-content px-4 py-4">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-semibold text-dark mb-0">Manage Reservations</h2>
                <p class="text-muted mb-0 mt-1">Review and manage facility reservation requests</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-primary px-3 py-2"><?= count($reservations) ?> Total</span>
                <?php
                $pending_count = count(array_filter($reservations, fn($r) => $r['status'] === 'pending'));
                if ($pending_count > 0):
                ?>
                    <span class="badge bg-warning text-dark px-3 py-2"><?= $pending_count ?> Pending</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white border-bottom">
                <i class="bi bi-funnel-fill"></i> Filter & Search
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">

                    <div class="col-lg-3 col-md-6">
                        <label for="user" class="form-label">Search User Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
                            <input type="text" name="user" id="user" class="form-control" placeholder="E.g., John Doe" value="<?= htmlspecialchars($search_user); ?>">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6">
                        <label for="facility" class="form-label">Search Facility Name</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-building"></i></span>
                            <input type="text" name="facility" id="facility" class="form-control" placeholder="E.g., Court A" value="<?= htmlspecialchars($search_facility); ?>">
                        </div>
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
                                <?php if ($status !== 'all'): ?>
                                    <option value="<?= $status ?>" <?= $filter_status === $status ? 'selected' : '' ?>>
                                        <?= ucfirst($status) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-4 d-grid gap-2">
                        <button type="submit" class="btn btn-darkred">
                            <i class="bi bi-search"></i> Apply Filters
                        </button>
                    </div>
                </form>
                <?php if ($search_user || $search_facility || $filter_date || ($filter_status && $filter_status !== 'all')): ?>
                    <div class="mt-3">
                        <span class="text-muted"><i class="bi bi-info-circle"></i> Active filters applied</span>
                        <a href="manage_reservations.php" class="btn btn-sm btn-outline-secondary ms-2">
                            <i class="bi bi-x-circle"></i> Clear All
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-body">
            <div class="card shadow-sm">
                <div class="card-header">
                    <i class="bi bi-list-task"></i> Reservation Records (<?= count($reservations); ?> Found)
                </div>

                <div class="table-scroll-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th><i class="bi bi-person-badge"></i> User</th>
                                    <th><i class="bi bi-building"></i> Facility</th>
                                    <th><i class="bi bi-calendar"></i> Date</th>
                                    <th><i class="bi bi-clock"></i> Time</th>
                                    <th><i class="bi bi-chat-left-text"></i> Purpose</th>
                                    <th><i class="bi bi-tag"></i> Status</th>
                                    <th><i class="bi bi-calendar-check"></i> Created</th>
                                    <th class="text-center"><i class="bi bi-gear"></i> Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reservations)): ?>
                                    <?php foreach ($reservations as $row): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-circle me-2">
                                                        <?= strtoupper(substr($row['user_name'], 0, 1)) ?>
                                                    </div>
                                                    <?= htmlspecialchars($row['user_name']); ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($row['facility_name']); ?></td>
                                            <td><?= date('M d, Y', strtotime($row['date'])); ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?= date('h:i A', strtotime($row['start_time'])); ?> - <?= date('h:i A', strtotime($row['end_time'])); ?>
                                                </span>
                                            </td>
                                            <td><?= htmlspecialchars($row['purpose']); ?></td>
                                            <td>
                                                <span class="badge bg-<?=
                                                                        match ($row['status']) {
                                                                            'approved' => 'success',
                                                                            'denied' => 'danger',
                                                                            'cancelled' => 'secondary',
                                                                            default => 'warning'
                                                                        };
                                                                        ?>">
                                                    <i class="bi bi-<?=
                                                                    match ($row['status']) {
                                                                        'approved' => 'check-circle',
                                                                        'denied' => 'x-circle',
                                                                        'cancelled' => 'dash-circle',
                                                                        default => 'clock'
                                                                    };
                                                                    ?>"></i>
                                                    <?= ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td><?= date("M d, Y", strtotime($row['created_at'])); ?></td>
                                            <td class="text-center">
                                                <?php if ($row['status'] === 'pending'): ?>
                                                    <form method="POST" action="" class="d-inline">
                                                        <input type="hidden" name="id" value="<?= $row['reservation_id']; ?>">
                                                        <button name="action" value="approve" class="btn btn-sm btn-approve me-1" title="Approve this reservation">
                                                            <i class="bi bi-check-lg"></i> Approve
                                                        </button>
                                                        <button name="action" value="deny" class="btn btn-sm btn-deny" title="Deny this reservation">
                                                            <i class="bi bi-x-lg"></i> Deny
                                                        </button>
                                                    </form>
                                                <?php elseif ($row['status'] === 'approved'): ?>
                                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to cancel this APPROVED reservation?');">
                                                        <input type="hidden" name="id" value="<?= $row['reservation_id']; ?>">
                                                        <button name="action" value="cancel" class="btn btn-sm btn-cancel" title="Cancel this reservation">
                                                            <i class="bi bi-x-circle"></i> Cancel
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-muted">No actions available</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                            <p class="mt-2">No reservations found matching the current filters.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .avatar-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #a4161a, #dc143c);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .input-group-text {
            border: 2px solid #e9ecef;
            border-right: none;
        }

        .input-group .form-control {
            border-left: none;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary-red);
        }

        .badge i {
            font-size: 0.8rem;
        }
    </style>
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