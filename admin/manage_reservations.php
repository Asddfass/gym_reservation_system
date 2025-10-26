<?php
session_start();
include '../includes/Database.php';
include '../includes/functions.php';

// Check if admin is logged in
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$fn = new Functions();
$user = $_SESSION['user'];

// Handle reservation status actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['reservation_id'])) 
{
    $reservation_id = $_POST['reservation_id'];
    $action = $_POST['action'];
    $validActions = ['approved', 'denied', 'cancelled'];

    if (in_array($action, $validActions)) 
    {
        $fn->execute(
            "UPDATE reservation SET status = ? WHERE reservation_id = ?",
            [$action, $reservation_id],
            "si"
        );
    }
}

// Fetch all reservations
$reservations = $fn->getReservations();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations | Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <link href="../css/reservations.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
                <img src="../assets/logo.png" alt="Gym Logo" style="height:40px;margin-right:10px;">
                Manage Reservations
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">Welcome, <?= htmlspecialchars($user['name']); ?>!</span>
                <a href="../index.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container py-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Manage Reservations</h3>
            <p class="text-muted">View, approve, deny, or cancel user reservations</p>
            <a href="admin_dashboard.php" class="btn btn-light border-0 shadow-sm mt-2" style="background-color:#dc143c;color:white;">
                ← Back to Dashboard
            </a>
        </div>



        <div class="card shadow-sm">
            <div class="card-header">All Reservations</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Facility</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reservations)): ?>
                            <?php foreach ($reservations as $r): ?>
                                <tr>
                                    <td><?= $r['reservation_id']; ?></td>
                                    <td><?= htmlspecialchars($r['user_name']); ?></td>
                                    <td><?= htmlspecialchars($r['facility_name']); ?></td>
                                    <td><?= htmlspecialchars($r['date']); ?></td>
                                    <td><?= htmlspecialchars($r['start_time'] . ' - ' . $r['end_time']); ?></td>
                                    <td><?= htmlspecialchars($r['purpose']); ?></td>
                                    <td>
                                        <?php
                                        $badgeClass = match ($r['status']) {
                                            'approved' => 'success',
                                            'denied' => 'danger',
                                            'cancelled' => 'secondary',
                                            'pending' => 'warning',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badgeClass; ?>">
                                            <?= ucfirst($r['status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($r['status'] === 'pending'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id']; ?>">
                                                <input type="hidden" name="action" value="approved">
                                                <button class="btn-action btn-approve">Approve</button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id']; ?>">
                                                <input type="hidden" name="action" value="denied">
                                                <button class="btn-action btn-deny">Deny</button>
                                            </form>
                                        <?php elseif ($r['status'] === 'approved'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="reservation_id" value="<?= $r['reservation_id']; ?>">
                                                <input type="hidden" name="action" value="cancelled">
                                                <button class="btn-action btn-cancel">Cancel</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">No actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-3">No reservations found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>

</html>