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

// Fetch available facilities
$facilities = $fn->getFacilities("available");

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $facility_id = $_POST['facility_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $purpose = trim($_POST['purpose']);

    // Basic validation
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

        if ($inserted) 
        {
            $success = "Reservation submitted successfully! Awaiting admin approval.";
        } 
        else 
        {
            $error = "Failed to submit reservation. Please try again.";
        }
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
    <link rel="stylesheet" href="css/style.css">
</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #dc143c;">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center fw-bold" href="dashboard.php">
                <img src="assets/logo.png" alt="Gym Logo" width="36" height="36" class="me-2">
                Gym Reservation
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">Welcome, <?= htmlspecialchars($user['name']); ?>!</span>
                <a href="logout.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="card shadow-sm mx-auto" style="max-width: 600px;">
            <div class="card-header text-white fw-semibold" style="background-color: #dc143c;">
                Reserve a Facility
            </div>
            <div class="card-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php elseif (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Select Facility</label>
                        <select name="facility_id" class="form-select" required>
                            <option value="">-- Choose Facility --</option>
                            <?php foreach ($facilities as $facility): ?>
                                <option value="<?= $facility['facility_id'] ?>">
                                    <?= htmlspecialchars($facility['name']) ?> (Capacity: <?= $facility['capacity'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="date" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Purpose</label>
                        <textarea name="purpose" class="form-control" rows="3" placeholder="Enter purpose" required></textarea>
                    </div>

                    <button type="submit" class="btn text-white w-100" style="background-color: #dc143c;">
                        Submit Reservation
                    </button>
                </form>
            </div>
        </div>
    </div>

</body>

</html>