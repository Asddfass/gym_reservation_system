<?php
session_start();
include 'includes/functions.php';

if (!isset($_SESSION['user'])) 
{
    header("Location: index.php");
    exit();
}

$fn = new Functions();
$user = $_SESSION['user'];
$facilities = $fn->getFacilities("available");

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $facility_id = $_POST['facility_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = trim($_POST['purpose']);

    if (empty($facility_id) || empty($date) || empty($start_time) || empty($end_time) || empty($purpose)) 
    {
        $error = "Please fill out all fields.";
    } 
    else 
    {
        $inserted = $fn->execute(
            "INSERT INTO reservation (user_id, facility_id, date, start_time, end_time, purpose, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')",
            [$user['user_id'], $facility_id, $date, $start_time, $end_time, $purpose],
            "iissss"
        );

        $success = $inserted
            ? "Reservation submitted successfully! Awaiting admin approval."
            : "Failed to submit reservation. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Facility | Gymnasium Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="css/dashboard.css" rel="stylesheet">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="assets/logo.png" alt="Gym Logo" style="height:40px;margin-right:10px;">
                Reserve Facility
            </a>
            <div class="d-flex align-items-center text-white ms-auto">
                <span class="me-3">Welcome, <?= htmlspecialchars($user['name']); ?>!</span>
                <a href="index.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Page Content -->
    <div class="container py-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Reserve a Facility</h3>
            <p class="text-muted">Book your preferred facility below</p>
        </div>

        <div class="card shadow-sm mx-auto" style="max-width: 650px;">
            <div class="card-header">
                Facility Reservation Form
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success); ?></div>
                <?php elseif (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" class="row g-3">
                    <div class="col-12">
                        <label class="form-label fw-semibold">Select Facility</label>
                        <select name="facility_id" class="form-select" required>
                            <option value="">-- Choose Facility --</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?= $facility['facility_id'] ?>">
                                    <?= htmlspecialchars($facility['name']); ?> (Capacity: <?= $facility['capacity']; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold">Purpose</label>
                        <textarea name="purpose" class="form-control" rows="3" placeholder="Enter purpose" required></textarea>
                    </div>

                    <div class="col-12 d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-primary px-4">← Back to Dashboard</a>
                        <button type="submit" class="btn btn-primary px-4">Submit Reservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
