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

// Fetch users and facilities for dropdowns
$users = $fn->fetchAll("SELECT user_id, name, email FROM user WHERE role != 'admin' ORDER BY name");
$facilities = $fn->getFacilities();

// --- 1. FILTER AND SEARCH HANDLING ---
$filter_user = $_GET['user_id'] ?? '';
$filter_facility = $_GET['facility_id'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
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

// Add User filter
if ($filter_user) {
    $sql .= " AND r.user_id = ?";
    $params[] = intval($filter_user);
    $types .= "i";
}

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
$reservations = $fn->fetchAll($sql, $params, $types);

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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>

    <div class="admin-content px-4 py-4">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-semibold text-dark mb-0">Manage Reservations</h2>
                <p class="text-muted mb-0 mt-1">Review and manage facility reservation requests</p>
            </div>
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
                        <label for="user_id" class="form-label">User</label>
                        <select name="user_id" id="user_id" class="form-select">
                            <option value="">-- All Users --</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['user_id'] ?>" <?= $filter_user == $user['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($user['name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

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

                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-submit">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="manage_reservations.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <div class="content-body">
            <div class="card shadow-sm">
                <div class="card-header bg-darkred text-white fw-semibold">
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
                                                    <?= date('h:i A', strtotime($row['start_time'])); ?> -
                                                    <?= date('h:i A', strtotime($row['end_time'])); ?>
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
                                                    <button type="button" class="btn btn-sm btn-approve me-1"
                                                        title="Approve this reservation"
                                                        onclick="showConfirmModal('approve', <?= $row['reservation_id']; ?>, '<?= htmlspecialchars($row['user_name'], ENT_QUOTES); ?>', '<?= htmlspecialchars($row['facility_name'], ENT_QUOTES); ?>', '<?= date('M d, Y', strtotime($row['date'])); ?>', '<?= date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?>')">
                                                        <i class="bi bi-check-lg"></i> Approve
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-deny"
                                                        title="Deny this reservation"
                                                        onclick="showConfirmModal('deny', <?= $row['reservation_id']; ?>, '<?= htmlspecialchars($row['user_name'], ENT_QUOTES); ?>', '<?= htmlspecialchars($row['facility_name'], ENT_QUOTES); ?>', '<?= date('M d, Y', strtotime($row['date'])); ?>', '<?= date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?>')">
                                                        <i class="bi bi-x-lg"></i> Deny
                                                    </button>
                                                <?php elseif ($row['status'] === 'approved'): ?>
                                                    <button type="button" class="btn btn-sm btn-cancel"
                                                        title="Cancel this reservation"
                                                        onclick="showConfirmModal('cancel', <?= $row['reservation_id']; ?>, '<?= htmlspecialchars($row['user_name'], ENT_QUOTES); ?>', '<?= htmlspecialchars($row['facility_name'], ENT_QUOTES); ?>', '<?= date('M d, Y', strtotime($row['date'])); ?>', '<?= date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])); ?>')">
                                                        <i class="bi bi-x-circle"></i> Cancel
                                                    </button>
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

    <!-- Confirmation Modal for Deny/Cancel -->
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-labelledby="confirmActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header" id="modalHeader">
                    <h5 class="modal-title" id="confirmActionModalLabel">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span id="modalTitle">Confirm Action</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="modalMessage" class="mb-3 fw-semibold"></p>
                    <div class="reservation-details bg-light rounded p-3">
                        <div class="row g-2">
                            <div class="col-4 text-muted"><i class="bi bi-person"></i> User:</div>
                            <div class="col-8 fw-semibold" id="modalUserName"></div>
                            <div class="col-4 text-muted"><i class="bi bi-building"></i> Facility:</div>
                            <div class="col-8 fw-semibold" id="modalFacility"></div>
                            <div class="col-4 text-muted"><i class="bi bi-calendar"></i> Date:</div>
                            <div class="col-8 fw-semibold" id="modalDate"></div>
                            <div class="col-4 text-muted"><i class="bi bi-clock"></i> Time:</div>
                            <div class="col-8 fw-semibold" id="modalTime"></div>
                        </div>
                    </div>
                    <div class="alert alert-warning mt-3 mb-0" id="modalWarning">
                        <i class="bi bi-info-circle me-1"></i>
                        <span id="modalWarningText"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-arrow-left"></i> Go Back
                    </button>
                    <form method="POST" action="" id="confirmActionForm" class="d-inline">
                        <input type="hidden" name="id" id="confirmReservationId">
                        <button type="submit" name="action" id="confirmActionBtn" value="" class="btn">
                            <i class="bi" id="confirmActionIcon"></i>
                            <span id="confirmActionText"></span>
                        </button>
                    </form>
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

        /* Modal Styles */
        #confirmActionModal .modal-header.approve-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        #confirmActionModal .modal-header.deny-header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        #confirmActionModal .modal-header.cancel-header {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        #confirmActionModal .modal-header .btn-close {
            filter: brightness(0) invert(1);
        }

        #confirmActionModal .reservation-details {
            border-left: 4px solid #a4161a;
        }

        #confirmActionModal .btn-deny-confirm {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
        }

        #confirmActionModal .btn-deny-confirm:hover {
            background: linear-gradient(135deg, #c82333, #bd2130);
            color: white;
        }

        #confirmActionModal .btn-cancel-confirm {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
            border: none;
        }

        #confirmActionModal .btn-cancel-confirm:hover {
            background: linear-gradient(135deg, #5a6268, #545b62);
            color: white;
        }

        #confirmActionModal .btn-approve-confirm {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
        }

        #confirmActionModal .btn-approve-confirm:hover {
            background: linear-gradient(135deg, #218838, #1ea886);
            color: white;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Confirmation Modal Function
        function showConfirmModal(action, reservationId, userName, facility, date, time) {
            const modal = document.getElementById('confirmActionModal');
            const modalHeader = document.getElementById('modalHeader');
            const modalTitle = document.getElementById('modalTitle');
            const modalMessage = document.getElementById('modalMessage');
            const modalUserName = document.getElementById('modalUserName');
            const modalFacility = document.getElementById('modalFacility');
            const modalDate = document.getElementById('modalDate');
            const modalTime = document.getElementById('modalTime');
            const modalWarningText = document.getElementById('modalWarningText');
            const confirmReservationId = document.getElementById('confirmReservationId');
            const confirmActionBtn = document.getElementById('confirmActionBtn');
            const confirmActionIcon = document.getElementById('confirmActionIcon');
            const confirmActionText = document.getElementById('confirmActionText');

            // Set reservation details
            modalUserName.textContent = userName;
            modalFacility.textContent = facility;
            modalDate.textContent = date;
            modalTime.textContent = time;
            confirmReservationId.value = reservationId;
            confirmActionBtn.value = action;

            // Customize based on action type
            modalHeader.classList.remove('approve-header', 'deny-header', 'cancel-header');

            if (action === 'approve') {
                modalHeader.classList.add('approve-header');
                modalTitle.textContent = 'Approve Reservation';
                modalMessage.textContent = 'Are you sure you want to approve this reservation?';
                modalWarningText.textContent = 'The user will be notified via email that their reservation has been approved.';
                confirmActionBtn.className = 'btn btn-approve-confirm';
                confirmActionIcon.className = 'bi bi-check-lg me-1';
                confirmActionText.textContent = 'Yes, Approve Reservation';
            } else if (action === 'deny') {
                modalHeader.classList.add('deny-header');
                modalTitle.textContent = 'Deny Reservation';
                modalMessage.textContent = 'Are you sure you want to deny this reservation?';
                modalWarningText.textContent = 'The user will be notified via email that their reservation request has been denied.';
                confirmActionBtn.className = 'btn btn-deny-confirm';
                confirmActionIcon.className = 'bi bi-x-lg me-1';
                confirmActionText.textContent = 'Yes, Deny Reservation';
            } else if (action === 'cancel') {
                modalHeader.classList.add('cancel-header');
                modalTitle.textContent = 'Cancel Approved Reservation';
                modalMessage.textContent = 'Are you sure you want to cancel this APPROVED reservation?';
                modalWarningText.textContent = 'This will cancel an already approved reservation. The user will be notified via email.';
                confirmActionBtn.className = 'btn btn-cancel-confirm';
                confirmActionIcon.className = 'bi bi-x-circle me-1';
                confirmActionText.textContent = 'Yes, Cancel Reservation';
            }

            // Show the modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }

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
        (function () {
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

            window.addEventListener('message', function (event) {
                if (event.data && event.data.type === 'requestPageInfo') {
                    announcePage();
                }
            });

            document.addEventListener('click', function (e) {
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