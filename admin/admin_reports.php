<?php
session_start();
include '../includes/Database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../");
    exit();
}

$fn = new Functions();

// --- FILTER HANDLING ---
$filter_user = $_GET['user_id'] ?? '';
$filter_facility = $_GET['facility_id'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Build dynamic SQL query
$sql = "
    SELECT r.*, u.name AS user_name, u.email AS user_email, u.role AS user_role,
           f.name AS facility_name, f.capacity AS facility_capacity
    FROM reservation r
    JOIN user u ON r.user_id = u.user_id
    JOIN facility f ON r.facility_id = f.facility_id
    WHERE 1=1
";

$params = [];
$types = "";

if ($filter_user) {
    $sql .= " AND r.user_id = ?";
    $params[] = intval($filter_user);
    $types .= "i";
}

if ($filter_facility) {
    $sql .= " AND r.facility_id = ?";
    $params[] = intval($filter_facility);
    $types .= "i";
}

if ($filter_date_from) {
    $sql .= " AND r.date >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if ($filter_date_to) {
    $sql .= " AND r.date <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

if ($filter_status && $filter_status !== 'all') {
    $sql .= " AND r.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql .= " ORDER BY r.date DESC, r.start_time DESC";

$reservations = $fn->fetchAll($sql, $params, $types);
$users = $fn->fetchAll("SELECT user_id, name, email FROM user ORDER BY name");
$facilities = $fn->getFacilities();

$total_reservations = count($reservations);
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
    <title>Reports & Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <style>
        /* Enhanced Print Styles */
        @media print {
            @page {
                size: A4 landscape;
                margin: 1.5cm 1cm;
            }

            body * {
                visibility: hidden;
            }

            #printable-area,
            #printable-area * {
                visibility: visible;
            }

            #printable-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .print-header {
                display: block;
                text-align: center;
                margin-bottom: 25px;
                padding-bottom: 15px;
                border-bottom: 3px solid #a4161a;
            }

            .print-header h2 {
                margin: 0;
                font-size: 28px;
                color: #a4161a;
                font-weight: 700;
            }

            .print-header h3 {
                margin: 8px 0 0 0;
                font-size: 20px;
                color: #660708;
                font-weight: 600;
            }

            .print-info {
                margin-top: 15px;
                padding: 12px;
                background: #f8f9fa;
                border-radius: 8px;
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-info p {
                margin: 0;
                font-size: 11px;
                color: #333;
            }

            .print-info strong {
                color: #a4161a;
            }

            .print-summary {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 10px;
                margin-bottom: 20px;
            }

            .print-stat-box {
                border: 2px solid #dee2e6;
                border-radius: 6px;
                padding: 10px;
                text-align: center;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-stat-box h4 {
                font-size: 24px;
                margin: 5px 0;
                font-weight: 700;
            }

            .print-stat-box p {
                font-size: 10px;
                margin: 0;
                color: #666;
            }

            .stat-total { border-color: #dc2f02; }
            .stat-pending { border-color: #ffc107; }
            .stat-approved { border-color: #28a745; }
            .stat-denied { border-color: #dc3545; }

            .card {
                box-shadow: none !important;
                border: 2px solid #dee2e6;
                border-radius: 8px;
                page-break-inside: avoid;
            }

            .card-header {
                background: linear-gradient(135deg, #a4161a 0%, #dc143c 100%) !important;
                color: white !important;
                padding: 12px 15px !important;
                font-size: 14px !important;
                font-weight: 600 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table-scroll-wrapper {
                max-height: none !important;
                overflow: visible !important;
            }

            .table-responsive {
                overflow: visible !important;
            }

            .table {
                width: 100%;
                font-size: 9px;
                border-collapse: collapse;
            }

            .table thead th {
                position: static !important;
                background: #f8f9fa !important;
                color: #000 !important;
                font-weight: 700 !important;
                padding: 10px 6px !important;
                border: 1px solid #dee2e6 !important;
                font-size: 10px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .table tbody td {
                padding: 8px 6px !important;
                border: 1px solid #dee2e6 !important;
                font-size: 9px;
                color: #000;
                line-height: 1.4;
            }

            .table tbody tr {
                page-break-inside: avoid;
            }

            .table tbody tr:nth-child(even) {
                background-color: #f9f9f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .badge {
                padding: 4px 8px !important;
                border-radius: 4px !important;
                font-size: 8px !important;
                font-weight: 600 !important;
                border: 1px solid !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .badge.bg-success {
                background-color: #d4edda !important;
                color: #155724 !important;
                border-color: #28a745 !important;
            }

            .badge.bg-warning {
                background-color: #fff3cd !important;
                color: #856404 !important;
                border-color: #ffc107 !important;
            }

            .badge.bg-danger {
                background-color: #f8d7da !important;
                color: #721c24 !important;
                border-color: #dc3545 !important;
            }

            .badge.bg-secondary {
                background-color: #e2e3e5 !important;
                color: #383d41 !important;
                border-color: #6c757d !important;
            }

            /* Column widths */
            .table th:nth-child(1), .table td:nth-child(1) { width: 4%; }
            .table th:nth-child(2), .table td:nth-child(2) { width: 15%; }
            .table th:nth-child(3), .table td:nth-child(3) { width: 8%; }
            .table th:nth-child(4), .table td:nth-child(4) { width: 15%; }
            .table th:nth-child(5), .table td:nth-child(5) { width: 11%; }
            .table th:nth-child(6), .table td:nth-child(6) { width: 14%; }
            .table th:nth-child(7), .table td:nth-child(7) { width: 20%; word-wrap: break-word; }
            .table th:nth-child(8), .table td:nth-child(8) { width: 8%; }
            .table th:nth-child(9), .table td:nth-child(9) { width: 11%; }

            .print-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 9px;
                color: #6c757d;
                padding: 8px 0;
                border-top: 1px solid #dee2e6;
            }

            * {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        .table-scroll-wrapper {
            max-height: 50vh;
            overflow-y: auto;
        }

        .table-scroll-wrapper thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: #f8f9fa;
        }

        .print-header {
            display: none;
        }
    </style>
</head>

<body>
    <div class="admin-content px-4 py-4">
        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-semibold text-dark mb-0">Reports & Analytics</h2>
            <button onclick="window.print()" class="btn btn-darkred no-print">
                <i class="bi bi-printer"></i> Print Report
            </button>
        </div>

        <!-- Filters Section -->
        <div class="card shadow-sm mb-4 no-print">
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
                                <option value="<?= $facility['facility_id'] ?>" <?= $filter_facility == $facility['facility_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($facility['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="date_from" class="form-label">Date From</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="<?= htmlspecialchars($filter_date_from) ?>">
                    </div>

                    <div class="col-md-2">
                        <label for="date_to" class="form-label">Date To</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="<?= htmlspecialchars($filter_date_to) ?>">
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
                            <i class="bi bi-search"></i> Filter Report
                        </button>
                        <a href="admin_reports.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4 no-print">
            <div class="col-lg-3 col-md-6">
                <div class="card-stats bg-crimson text-white">
                    <i class="bi bi-list-task fs-3 mb-2"></i>
                    <h4><?= $total_reservations ?></h4>
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

        <!-- Printable Report Area -->
        <div id="printable-area">
            <!-- Print Header -->
            <div class="print-header">
                <h2>🏫 Gymnasium Reservation System</h2>
                <h3>Reservation Report</h3>
                <div class="print-info">
                    <p><strong>Generated:</strong> <?= date('F d, Y h:i A') ?></p>
                    <?php if ($filter_date_from || $filter_date_to): ?>
                        <p><strong>Date Range:</strong>
                            <?= $filter_date_from ? date('M d, Y', strtotime($filter_date_from)) : 'Start' ?>
                            to
                            <?= $filter_date_to ? date('M d, Y', strtotime($filter_date_to)) : 'End' ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($filter_user): ?>
                        <p><strong>User:</strong> <?php
                        $user_key = array_search($filter_user, array_column($users, 'user_id'));
                        echo htmlspecialchars($user_key !== false ? $users[$user_key]['name'] : 'N/A');
                        ?></p>
                    <?php endif; ?>
                    <?php if ($filter_facility): ?>
                        <p><strong>Facility:</strong> <?php
                        $facility_key = array_search($filter_facility, array_column($facilities, 'facility_id'));
                        echo htmlspecialchars($facility_key !== false ? $facilities[$facility_key]['name'] : 'N/A');
                        ?></p>
                    <?php endif; ?>
                    <?php if ($filter_status && $filter_status !== 'all'): ?>
                        <p><strong>Status Filter:</strong> <?= ucfirst($filter_status) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Print Summary Statistics -->
            <div class="print-summary">
                <div class="print-stat-box stat-total">
                    <p>Total Records</p>
                    <h4><?= $total_reservations ?></h4>
                </div>
                <div class="print-stat-box stat-pending">
                    <p>Pending</p>
                    <h4><?= $status_counts['pending'] ?></h4>
                </div>
                <div class="print-stat-box stat-approved">
                    <p>Approved</p>
                    <h4><?= $status_counts['approved'] ?></h4>
                </div>
                <div class="print-stat-box stat-denied">
                    <p>Denied/Cancelled</p>
                    <h4><?= $status_counts['denied'] + $status_counts['cancelled'] ?></h4>
                </div>
            </div>

            <!-- Report Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-darkred text-white fw-semibold">
                    Reservation Records (<?= $total_reservations ?> Found)
                </div>

                <div class="table-scroll-wrapper">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>No.</th>
                                    <th>User</th>
                                    <th>Role</th>
                                    <th>Facility</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reservations)): ?>
                                    <?php $no = 1; ?>
                                    <?php foreach ($reservations as $row): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td><?= htmlspecialchars($row['user_name']) ?></td>
                                            <td><span class="badge bg-secondary"><?= ucfirst($row['user_role']) ?></span></td>
                                            <td><?= htmlspecialchars($row['facility_name']) ?></td>
                                            <td><?= date('M d, Y', strtotime($row['date'])) ?></td>
                                            <td><?= date('h:i A', strtotime($row['start_time'])) ?> - <?= date('h:i A', strtotime($row['end_time'])) ?></td>
                                            <td><?= htmlspecialchars($row['purpose']) ?></td>
                                            <td>
                                                <span class="badge bg-<?=
                                                    match ($row['status']) {
                                                        'approved' => 'success',
                                                        'denied' => 'danger',
                                                        'cancelled' => 'secondary',
                                                        default => 'warning'
                                                    };
                                                ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-3">No reservations found matching the selected filters.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Print Footer -->
            <div class="print-footer">
                <p>Generated by Gymnasium Reservation System • <?= date('l, F d, Y - h:i A') ?> • Page {PAGE_NUM}</p>
            </div>
        </div>
    </div>

    <script>
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
        })();
    </script>
</body>
</html>