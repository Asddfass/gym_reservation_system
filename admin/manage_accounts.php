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
$message = $error = "";

// Handle Add Account
if (isset($_POST['add_account'])) 
{
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if ($name && $email && $password) 
    {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $fn->execute("INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)",
            [$name, $email, $hashed, $role], "ssss");
        $message = "Account added successfully.";
    } 
    else 
    {
        $error = "Please fill in all fields.";
    }
}

// Handle Edit
if (isset($_POST['save_edit'])) 
{
    $id = intval($_POST['user_id']);
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $fn->execute("UPDATE user SET name=?, email=?, role=? WHERE user_id=?", [$name, $email, $role, $id], "sssi");
    $message = "Account updated successfully.";
}

// Handle Change Password
if (isset($_POST['change_password'])) 
{
    $id = intval($_POST['user_id']);
    $new_pass = trim($_POST['new_password']);

    if (!empty($new_pass)) 
    {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $fn->execute("UPDATE user SET password=? WHERE user_id=?", [$hashed, $id], "si");
        $message = "Password changed successfully.";
    } 
    else 
    {
        $error = "Password field cannot be empty.";
        $_POST['edit_mode'] = $id; // Stay in edit mode
    }
}

// Handle Delete
if (isset($_GET['delete'])) 
{
    $id = intval($_GET['delete']);
    $fn->execute("DELETE FROM user WHERE user_id = ?", [$id], "i");
    $message = "Account deleted successfully.";
}

// Fetch all users
$accounts = $fn->fetchAll("SELECT * FROM user");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Accounts | Gym Reservation System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="../css/admin_dashboard.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center" href="admin_dashboard.php">
            <img src="../assets/logo.png" alt="Gym Logo">
            Manage Accounts
        </a>
        <div class="d-flex align-items-center text-white">
            <span class="me-3">Welcome, <?= htmlspecialchars($user['name']); ?>!</span>
            <a href="../index.php" class="btn btn-sm btn-light">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="text-center mb-4">
        <h3 class="fw-bold">User Account Management</h3>
        <p class="text-muted">Add, edit, and manage user accounts</p>
        <a href="admin_dashboard.php" class="btn btn-light border-0 shadow-sm mt-2" style="background-color:#dc143c;color:white;">
            ← Back to Dashboard
        </a>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Add Account -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">Add New Account</div>
        <div class="card-body">
            <form method="POST" class="row g-3">
                <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Full Name" required></div>
                <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                <div class="col-md-2">
                    <select name="role" class="form-select">
                        <option value="student">Student</option>
                        <option value="faculty">Faculty</option>
                        <option value="guest">Guest</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button type="submit" name="add_account" class="btn btn-manage w-100">Add</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage Accounts -->
    <div class="card shadow-sm">
        <div class="card-header">Existing Accounts</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($accounts as $acc): 
                        $isEditing = (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $acc['user_id']);
                    ?>
                    <tr>
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?= $acc['user_id'] ?>">
                            <td><?= $acc['user_id'] ?></td>
                            <td><input type="text" name="name" value="<?= htmlspecialchars($acc['name']) ?>" class="form-control form-control-sm" <?= $isEditing ? '' : 'readonly' ?>></td>
                            <td><input type="email" name="email" value="<?= htmlspecialchars($acc['email']) ?>" class="form-control form-control-sm" <?= $isEditing ? '' : 'readonly' ?>></td>
                            <td>
                                <select name="role" class="form-select form-select-sm" <?= $isEditing ? '' : 'disabled' ?>>
                                    <?php foreach (['student','faculty','guest','admin'] as $r): ?>
                                        <option value="<?= $r ?>" <?= $acc['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td class="text-center">
                                <?php if ($isEditing): ?>
                                    <button type="submit" name="save_edit" class="btn btn-sm btn-success">Save</button>
                                    <button type="submit" name="cancel" class="btn btn-sm btn-secondary">Cancel</button>
                                    <div class="mt-2">
                                        <input type="password" name="new_password" class="form-control form-control-sm mb-1" placeholder="New Password">
                                        <button type="submit" name="change_password" class="btn btn-sm btn-outline-danger w-100">Change Password</button>
                                    </div>
                                <?php else: ?>
                                    <button type="submit" name="edit_mode" value="<?= $acc['user_id'] ?>" class="btn btn-sm btn-warning">Edit</button>
                                    <a href="?delete=<?= $acc['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this account?')">Delete</a>
                                <?php endif; ?>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
