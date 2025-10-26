<?php
session_start();
include '../includes/Database.php';
include '../includes/functions.php';

// Redirect if not logged in or not admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') 
{
    header("Location: ../index.php");
    exit();
}

$fn = new Functions();
$user = $_SESSION['user'];

// Handle Add Facility
if (isset($_POST['add_facility'])) 
{
    $name = trim($_POST['name']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];

    if ($name && $capacity > 0) 
    {
        $sql = "INSERT INTO facility (name, capacity, availability_status) VALUES (?, ?, ?)";
        $fn->execute($sql, [$name, $capacity, $status], "sis");
        $message = "Facility added successfully.";
    } 
    else 
    {
        $error = "Please enter valid data.";
    }
}

// Handle Delete Facility
if (isset($_GET['delete'])) 
{
    $id = intval($_GET['delete']);
    $fn->execute("DELETE FROM reservation WHERE facility_id = ?", [$id], "i");
    if ($fn->execute("DELETE FROM facility WHERE facility_id = ?", [$id], "i")) 
    {
        $message = "Facility deleted successfully.";
    } 
    else 
    {
        $error = "Failed to delete facility.";
    }
}

// Handle Edit Facility
if (isset($_POST['edit_facility'])) 
{
    $id = intval($_POST['facility_id']);
    $name = trim($_POST['name']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];

    $sql = "UPDATE facility SET name = ?, capacity = ?, availability_status = ? WHERE facility_id = ?";
    if ($fn->execute($sql, [$name, $capacity, $status, $id], "sisi")) 
    {
        $message = "Facility updated successfully.";
    } 
    else 
    {
        $error = "Failed to update facility.";
    }
}

// Fetch facilities
$facilities = $fn->getFacilities();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Facilities | Gym Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <style>

    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid px-4">
            <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
                <img src="../assets/logo.png" alt="Gym Logo" style="height:40px;margin-right:10px;">
                Manage Facilities
            </a>
            <div class="d-flex align-items-center text-white">
                <span class="me-3">Welcome, <?= htmlspecialchars($user['name']); ?>!</span>
                <a href="../index.php" class="btn btn-sm btn-light">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="text-center mb-4">
            <h3 class="fw-bold">Facilities Management</h3>
            <p class="text-muted">Add, view, edit, and remove gym facilities</p>
            <a href="admin_dashboard.php" class="btn btn-light border-0 shadow-sm mt-2" style="background-color:#dc143c;color:white;">
                ← Back to Dashboard
            </a>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message); ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Facility Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-header fw-semibold">Add New Facility</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="name" class="form-control" placeholder="Facility Name" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" name="capacity" class="form-control" placeholder="Capacity" required>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <button type="submit" name="add_facility" class="btn btn-manage w-100">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Facilities Table -->
        <div class="card shadow-sm">
            <div class="card-header fw-semibold">Existing Facilities</div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($facilities)): ?>
                            <?php foreach ($facilities as $f): ?>
                                <tr>
                                    <td><?= htmlspecialchars($f['facility_id']); ?></td>
                                    <td><?= htmlspecialchars($f['name']); ?></td>
                                    <td><?= htmlspecialchars($f['capacity']); ?></td>
                                    <td>
                                        <span class="badge bg-<?= $f['availability_status'] === 'available' ? 'success' : 'secondary'; ?>">
                                            <?= ucfirst($f['availability_status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button 
                                            class="btn btn-sm btn-warning me-1 edit-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editModal"
                                            data-id="<?= $f['facility_id']; ?>"
                                            data-name="<?= htmlspecialchars($f['name']); ?>"
                                            data-capacity="<?= $f['capacity']; ?>"
                                            data-status="<?= $f['availability_status']; ?>">
                                            Edit
                                        </button>
                                        <a href="?delete=<?= $f['facility_id']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Delete this facility?')">
                                           Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">No facilities found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit Facility Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Facility</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="facility_id" id="edit_id">
                    <div class="mb-3">
                        <label class="form-label">Facility Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Capacity</label>
                        <input type="number" name="capacity" id="edit_capacity" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="available">Available</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="edit_facility" class="btn btn-manage">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const editButtons = document.querySelectorAll('.edit-btn');
        editButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_capacity').value = btn.dataset.capacity;
                document.getElementById('edit_status').value = btn.dataset.status;
            });
        });
    </script>
</body>
</html>
