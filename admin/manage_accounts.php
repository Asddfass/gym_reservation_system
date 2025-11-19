<?php
session_start();
include '../includes/Database.php';
include '../includes/functions.php';
include '../includes/Mailer.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') 
{
    header("Location: ../");
    exit();
}

$fn = new Functions();
$mailer = new Mailer();
$message = $error = "";
$current_user_id = $_SESSION['user']['user_id'];

// Handle Add Account
if (isset($_POST['add_account'])) 
{
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    if ($name && $email && $password) 
    {
        // Check if email already exists
        $existing_user = $fn->fetchOne("SELECT user_id FROM user WHERE email = ?", [$email], "s");
        
        if ($existing_user) 
        {
            $error = "An account with the email " . htmlspecialchars($email) . " already exists.";
        } 
        else 
        {
            // Hash password and insert user
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $fn->execute(
                "INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)",
                [$name, $email, $hashed, $role],
                "ssss"
            );
            
            // Send welcome email
            try {
                $mailer->sendWelcomeEmail($email, $name, $role);
                $message = "Account added successfully! Welcome email sent to " . htmlspecialchars($email);
            } catch (Exception $e) {
                error_log("Failed to send welcome email: " . $e->getMessage());
                $message = "Account added successfully! (Note: Welcome email could not be sent)";
            }
        }
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
        $_POST['edit_mode'] = $id;
    }
}

// Handle Delete
if (isset($_GET['delete'])) 
{
    $id = intval($_GET['delete']);
    
    if ($id === $current_user_id) {
        $error = "You cannot delete the account you are currently logged in as.";
    } else {
        $fn->execute("DELETE FROM user WHERE user_id = ?", [$id], "i");
        $message = "Account deleted successfully.";
    }
}

// Fetch users
$accounts = $fn->fetchAll("SELECT * FROM user");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link href="../css/admin.css" rel="stylesheet">
</head>

<body>
    <div class="admin-content px-4 py-4">
    <div class="content-header d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-semibold text-dark mb-0">Manage Accounts</h2>
    </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add Account -->
        <div class="card mb-4">
            <div class="card-header bg-darkred text-white fw-semibold">Add New Account</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="Full Name" required>
                    </div>
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control" placeholder="Email" required>
                    </div>
                    <div class="col-md-2">
                        <input type="password" name="password" class="form-control" placeholder="Password" required>
                    </div>
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

        <!-- Accounts Table -->
        <div class="card">
            <div class="card-header bg-darkred text-white fw-semibold">Existing Accounts</div>
            <div class="card-body p-0">
                <div class="table-container">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($accounts as $acc): 
                                $isEditing = (isset($_POST['edit_mode']) && $_POST['edit_mode'] == $acc['user_id']);
                                $is_self = $acc['user_id'] == $current_user_id;
                            ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="user_id" value="<?= $acc['user_id'] ?>">
                                    <td><?= $acc['user_id'] ?></td>
                                    <td><input type="text" name="name" value="<?= htmlspecialchars($acc['name']) ?>" class="form-control form-control-sm" <?= $isEditing ? '' : 'readonly' ?>></td>
                                    <td><input type="email" name="email" value="<?= htmlspecialchars($acc['email']) ?>" class="form-control form-control-sm" <?= $isEditing ? '' : 'readonly' ?>></td>
                                    <td>
                                        <select name="role" class="form-select form-select-sm" <?= $isEditing ? '' : 'disabled' ?>>
                                            <?php foreach (['student', 'faculty', 'guest', 'admin'] as $r): ?>
                                                <option value="<?= $r ?>" <?= $acc['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($isEditing): ?>
                                            <div class="d-flex flex-column gap-2">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <button type="submit" name="save_edit" class="btn btn-sm btn-approve">Save</button>
                                                    <button type="submit" name="cancel" class="btn btn-sm btn-cancel">Cancel</button>
                                                </div>
                                                <div class="mt-2">
                                                    <input type="password" name="new_password" class="form-control form-control-sm mb-2" placeholder="New Password">
                                                    <button type="submit" name="change_password" class="btn btn-sm btn-deny w-100">Change Password</button>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex justify-content-center gap-2">
                                                <button type="submit" name="edit_mode" value="<?= $acc['user_id'] ?>" class="btn btn-sm btn-manage">Edit</button>
                                                
                                                <?php if ($is_self): ?>
                                                    <button type="button" class="btn btn-sm btn-secondary" disabled title="Cannot delete your own account">Delete</button>
                                                <?php else: ?>
                                                    <a href="?delete=<?= $acc['user_id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this account?')">Delete</a>
                                                <?php endif; ?>
                                            </div>
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
    </div>
</body>
</html>