<?php
session_start();
include '../includes/database.php';
include '../includes/functions.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] === 'admin') {
    header("Location: ../");
    exit();
}

$user = $_SESSION['user'];
$func = new Functions();
$user_id = $user['user_id'];
$message = '';
$error = '';

// --- FETCH CURRENT USER DATA ---
$current_user = $func->fetchOne("SELECT user_id, name, email, role, password FROM user WHERE user_id = ?", [$user_id], "i");

if (!$current_user) {
    header("Location: user_overview.php"); // Redirect if user data can't be found
    exit();
}

// --- 1. HANDLE PROFILE UPDATE (NAME/EMAIL) ---
if (isset($_POST['update_profile'])) {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $email_changed = false;

    if (empty($new_name) || empty($new_email)) {
        $error = "Name and Email cannot be empty.";
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Check if email is already taken by another user
        if ($new_email !== $current_user['email']) {
            $email_changed = true;
            $existing_user = $func->fetchOne("SELECT user_id FROM user WHERE email = ? AND user_id != ?", [$new_email, $user_id], "si");
            if ($existing_user) {
                $error = "This email is already registered to another account.";
            }
        }

        if (empty($error)) {
            $update_result = $func->execute("UPDATE user SET name = ?, email = ? WHERE user_id = ?", [$new_name, $new_email, $user_id], "ssi");

            if ($update_result) {
                // Update session variables immediately
                $_SESSION['user']['name'] = $new_name;
                $_SESSION['user']['email'] = $new_email;
                $current_user['name'] = $new_name;
                $current_user['email'] = $new_email;

                $message = "Profile updated successfully!";
                if ($email_changed) {
                    $message .= " You may need to log in again with the new email.";
                }
            } else {
                $error = "Error updating profile. Please try again.";
            }
        }
    }
}

// --- 2. HANDLE PASSWORD CHANGE ---
if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "All password fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation password do not match.";
    } elseif (strlen($new_password) < 3) {
        $error = "New password must be at least 3 characters long.";
    } else {
        // Verify current password
        if (password_verify($current_password, $current_user['password']) || $current_password == $current_user['password']) {
            // Hash the new password and update the database
            $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_result = $func->execute("UPDATE user SET password = ? WHERE user_id = ?", [$new_hashed_password, $user_id], "si");

            if ($update_result) {
                $message = "Password changed successfully! Please use your new password next time you log in.";
                // Force a re-fetch of the user data to update the current_user array's 'password' key
                $current_user = $func->fetchOne("SELECT user_id, name, email, role, password FROM user WHERE user_id = ?", [$user_id], "i");
            } else {
                $error = "Error changing password. Please try again.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | Gym Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/user.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>

<body>
    <div class="user-content container-fluid px-4 py-4">
        <div class="content-header mb-4">
            <h3 class="fw-semibold mb-0">My Profile</h3>
            <p class="text-muted mb-0 mt-1">Manage your personal information and security settings.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-darkred text-white fw-semibold">
                        <i class="bi bi-person-circle"></i> Update Personal Information
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($user_id); ?>">

                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="<?= htmlspecialchars($current_user['name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= htmlspecialchars($current_user['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="role" class="form-label">Role</label>
                                <input type="text" class="form-control" id="role"
                                    value="<?= ucfirst(htmlspecialchars($current_user['role'])); ?>" disabled>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-darkred w-100 mt-2">Save
                                Changes</button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-darkred text-white fw-semibold">
                        <i class="bi bi-key-fill"></i> Change Password
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password"
                                    name="current_password" required>
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                    required>
                            </div>

                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required>
                            </div>

                            <button type="submit" name="change_password" class="btn btn-darkred w-100">Change
                                Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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