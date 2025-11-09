<?php
session_start();
include 'includes/functions.php';

$fn = new Functions();

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
  $email = trim($_POST['email']);
  $password = trim($_POST['password']);

  $user = $fn->checkLogin($email, $password);

  if ($user) 
  {
    $_SESSION['user'] = $user;

    // Redirect based on role
    if ($user['role'] === 'admin') 
    {
      header("Location: admin/");
    } 
    else 
    {
      header("Location: user/");
    }
    exit();
  } 
  else 
  {
    $error = "Invalid email or password.";
  }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gymnasium Reservation System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
</head>

<body class="d-flex align-items-center justify-content-center vh-100">

  <div class="login-card shadow-lg p-4 rounded-4 bg-white" style="max-width: 400px; width: 100%;">
    <div class="text-center mb-4">
      <img src="assets/logo.png" alt="Gym Logo" width="70">
      <h3 class="mt-3 fw-bold text-primary">Gymnasium Reservation System</h3>
      <p class="text-muted small">Log in to continue</p>
    </div>

    <?php if (isset($error)): ?>
      <div class="alert alert-danger py-2"><?= $error ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Email address</label>
        <input type="email" name="email" class="form-control" placeholder="Enter email" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">Login</button>
    </form>

    <div class="text-center mt-3">
      <p class="text-muted small mb-0">Need an account? Contact the admin.</p>
    </div>
  </div>

</body>

</html>