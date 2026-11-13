<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Due It Now – Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container py-5" style="max-width: 420px;">
    <h1 class="h3 text-center mb-4">Due It Now</h1>
    <div id="alert" role="alert"></div>
    <form id="loginForm" class="card card-body shadow-sm">
      <label for="email" class="form-label">Email</label>
      <input type="email" id="email" class="form-control mb-3" required>

      <label for="password" class="form-label">Password</label>
      <div class="input-group mb-3">
        <input type="password" id="password" class="form-control" required>
        <button type="button" class="btn btn-soft pw-toggle" data-target="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
      </div>

      <button type="submit" class="btn btn-primary w-100">Log In</button>
      <p class="text-center mt-3 mb-0">No account? <a href="register.php">Register</a></p>
    </form>
  </main>
  <script src="assets/script.js"></script>
</body>
</html>