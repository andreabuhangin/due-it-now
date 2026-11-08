<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Due It Now – Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container py-5" style="max-width: 420px;">
    <h1 class="h3 text-center mb-4">Create your account</h1>
    <div id="alert" role="alert"></div>
    <form id="registerForm" class="card card-body shadow-sm">
      <label for="full_name" class="form-label">Full Name</label>
      <input type="text" id="full_name" class="form-control mb-3" required>

      <label for="email" class="form-label">Email</label>
      <input type="email" id="email" class="form-control mb-3" required>

      <label for="password" class="form-label">Password</label>
      <input type="password" id="password" class="form-control mb-3" minlength="8" required>

      <button type="submit" class="btn btn-primary w-100">Create Account</button>
      <p class="text-center mt-3 mb-0">Already registered? <a href="index.php">Log in</a></p>
    </form>
  </main>
  <script src="assets/script.js"></script>
</body>
</html>