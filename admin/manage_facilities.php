<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../");
    exit();
}

$fn = new Functions();

// Handle Add Facility
if (isset($_POST['add_facility'])) {
    $name = trim($_POST['name']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];

    if ($name && $capacity > 0) {
        $sql = "INSERT INTO facility (name, capacity, availability_status) VALUES (?, ?, ?)";
        $fn->execute($sql, [$name, $capacity, $status], "sis");
        $message = "Facility added successfully.";
    } else {
        $error = "Please enter valid data.";
    }
}

// Handle Delete Facility
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $fn->execute("DELETE FROM reservation WHERE facility_id = ?", [$id], "i");
    if ($fn->execute("DELETE FROM facility WHERE facility_id = ?", [$id], "i")) {
        $message = "Facility deleted successfully.";
    } else {
        $error = "Failed to delete facility.";
    }
}

// Handle Edit Facility
if (isset($_POST['edit_facility'])) {
    $id = intval($_POST['facility_id']);
    $name = trim($_POST['name']);
    $capacity = intval($_POST['capacity']);
    $status = $_POST['status'];

    $sql = "UPDATE facility SET name = ?, capacity = ?, availability_status = ? WHERE facility_id = ?";
    if ($fn->execute($sql, [$name, $capacity, $status, $id], "sisi")) {
        $message = "Facility updated successfully.";
    } else {
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
    <title>Facilities | Gym Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>

    <div class="admin-content px-4 py-4">

        <div class="content-header d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-semibold text-dark mb-0">Manage Facilities</h2>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message); ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add Facility -->
        <div class="card dashboard-card mb-4 border-0 shadow-sm rounded-4">
            <div class="card-header bg-darkred text-white fw-semibold">Add New Facility</div>
            <div class="card-body bg-light">
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
                        <button type="submit" name="add_facility" class="btn btn-primary w-100">Add</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Facilities List -->
        <div class="card dashboard-card border-0 shadow-sm rounded-4">
            <div class="card-header bg-darkred text-white fw-semibold">Existing Facilities</div>
            <div class="card-body p-0 bg-white">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-secondary">
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
                            <?php
                            $idnum = 1;

                            foreach ($facilities as $f): ?>
                                <tr>
                                    <td><?= htmlspecialchars($idnum++); ?></td>
                                    <td><?= htmlspecialchars($f['name']); ?></td>
                                    <td><?= htmlspecialchars($f['capacity']); ?></td>
                                    <td>
                                        <span
                                            class="badge bg-<?= $f['availability_status'] === 'available' ? 'success' : 'secondary'; ?>">
                                            <?= ucfirst($f['availability_status']); ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-sm btn-warning me-1 edit-btn" data-bs-toggle="modal"
                                            data-bs-target="#editModal" data-id="<?= $f['facility_id']; ?>"
                                            data-name="<?= htmlspecialchars($f['name']); ?>"
                                            data-capacity="<?= $f['capacity']; ?>"
                                            data-status="<?= $f['availability_status']; ?>">
                                            Edit
                                        </button>
                                        <a href="?delete=<?= $f['facility_id']; ?>" class="btn btn-sm btn-danger"
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" class="modal-content rounded-4">
                <div class="modal-header bg-accent text-white">
                    <h5 class="modal-title" id="editModalLabel">Edit Facility</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
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
                <div class="modal-footer bg-white">
                    <button type="submit" name="edit_facility" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.getElementById('edit_id').value = btn.dataset.id;
                document.getElementById('edit_name').value = btn.dataset.name;
                document.getElementById('edit_capacity').value = btn.dataset.capacity;
                document.getElementById('edit_status').value = btn.dataset.status;
            });
        });

        // Notify parent frame about current page
        (function () {
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
            window.addEventListener('message', function (event) {
                if (event.data && event.data.type === 'requestPageInfo') {
                    announcePage();
                }
            });

            // Intercept navigation links (for "View details" etc.)
            document.addEventListener('click', function (e) {
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