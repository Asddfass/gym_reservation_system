<?php
session_start();
include 'includes/functions.php';

$fn = new Functions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = $_POST['role'];

    // Check if email exists
    $existing = $fn->fetchOne("SELECT * FROM user WHERE email = ?", [$email], "s");
    if ($existing) 
    {
        $error = "Email already registered.";
    } 
    else 
    {
        // Hash password before saving
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $inserted = $fn->execute(
            "INSERT INTO user (name, email, password, role) VALUES (?, ?, ?, ?)",
            [$name, $email, $hashedPassword, $role],
            "ssss"
        );

        if ($inserted) 
        {
            $_SESSION['success'] = "Registration successful! You can now log in.";
            header("Location: index.php");
            exit();
        } 
        else 
        {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Gymnasium Reservation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
</head>

<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="login-card shadow-lg p-4 rounded-4 bg-white" style="max-width: 420px; width: 100%;">
        <div class="text-center mb-4">
            <img src="assets/logo.png" alt="Gym Logo" width="70">
            <h3 class="mt-3 fw-bold" style="color: #dc143c;">Create an Account</h3>
            <p class="text-muted small">Join the Gymnasium Reservation System</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email address</label>
                <input type="email" name="email" class="form-control" placeholder="Enter email" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="Enter password" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role" class="form-select" required>
                    <option value="student" selected>Student</option>
                    <option value="faculty">Faculty</option>
                    <option value="guest">Guest</option>
                </select>
            </div>

            <button type="submit" class="btn w-100 text-white" style="background-color: #dc143c;">Register</button>
        </form>

        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none small" style="color: #dc143c;">Already have an account? Log in</a>
        </div>
    </div>

</body>

</html>