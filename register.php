<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Due It Now – Register</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container py-5 auth-container">
    <h1 class="h3 text-center mb-4">Create your account</h1>
    <div id="alert" role="alert" aria-live="polite"></div>
    <form id="registerForm" class="card card-body shadow-sm">
      <label for="full_name" class="form-label">Full Name</label>
      <input type="text" id="full_name" class="form-control mb-3" autocomplete="name" required>

      <label for="email" class="form-label">Gmail Address</label>
      <input type="email" id="email" class="form-control mb-3" autocomplete="email" placeholder="name@gmail.com" required>
      <div class="form-text mb-3">Only valid @gmail.com addresses are accepted.</div>

      <label for="password" class="form-label">Password</label>
      <div class="input-group password-group mb-2">
        <input type="password" id="password" class="form-control" autocomplete="new-password" required>
        <button class="btn btn-soft password-toggle" type="button" data-password-target="password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye"></i></button>
      </div>
      <div class="password-rules mb-3" id="passwordRules" aria-live="polite">
        <div data-rule="length">At least 8 characters</div>
        <div data-rule="upper">At least one uppercase letter (A–Z)</div>
        <div data-rule="lower">At least one lowercase letter (a–z)</div>
        <div data-rule="number">At least one number (0–9)</div>
        <div data-rule="special">At least one special character</div>
      </div>

      <button type="submit" class="btn btn-primary w-100">Create Account</button>
      <p class="text-center mt-3 mb-0">Already registered? <a href="index.php">Log in</a></p>
    </form>
  </main>
  <script src="assets/script.js"></script>
</body>
</html>
