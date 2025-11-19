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
  <title>Login - Gymnasium Reservation System</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link rel="stylesheet" href="css/style.css">
</head>

<body>
  <!-- Animated Shapes -->
  <div class="shape shape-1"></div>
  <div class="shape shape-2"></div>
  <div class="shape shape-3"></div>

  <div class="login-container">
    <div class="login-card">
      <div class="logo-container">
        <img src="assets/logo.png" alt="Gym Logo" class="logo">
        <h3>Welcome Back</h3>
        <p>Sign in to your Gymnasium Reservation account</p>
      </div>

      <?php if (isset($error)): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" id="loginForm">
        <div class="form-group mb-3">
          <label class="form-label" for="email">Email Address</label>
          <div class="input-wrapper">
            <i class="bi bi-envelope input-icon"></i>
            <input 
              type="email" 
              name="email" 
              id="email"
              class="form-control" 
              placeholder="Enter your email"
              required
              autocomplete="email"
            >
          </div>
        </div>

        <div class="form-group mb-4">
          <label class="form-label" for="password">Password</label>
          <div class="input-wrapper">
            <i class="bi bi-lock input-icon"></i>
            <input 
              type="password" 
              name="password" 
              id="password"
              class="form-control" 
              placeholder="Enter your password"
              required
              autocomplete="current-password"
            >
          </div>
        </div>

        <button type="submit" class="btn-login" id="loginBtn">
          <i class="bi bi-box-arrow-in-right"></i>
          <span>Sign In</span>
        </button>
      </form>

      <div class="divider">
        <span>Need Help?</span>
      </div>

      <div class="footer-text">
        <p>Don't have an account? <a href="#">Contact Administrator</a></p>
        <small>© 2024 Gymnasium Reservation System</small>
      </div>
    </div>
  </div>

  <script>
    // Add loading state to button on submit
    document.getElementById('loginForm').addEventListener('submit', function() {
      const btn = document.getElementById('loginBtn');
      btn.classList.add('loading');
      btn.querySelector('span').textContent = 'Signing in...';
    });

    // Add floating animation to shapes
    document.querySelectorAll('.shape').forEach((shape, index) => {
      shape.style.animationDelay = `${index * 3}s`;
    });
  </script>
</body>
</html>