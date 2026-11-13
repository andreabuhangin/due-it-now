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
  <main class="container py-5" style="max-width: 420px;">
    <h1 class="h3 text-center mb-4">Create your account</h1>
    <div id="alert" role="alert"></div>
    <form id="registerForm" class="card card-body shadow-sm">
      <label for="full_name" class="form-label">Full Name</label>
      <input type="text" id="full_name" class="form-control mb-3" required>

      <label for="email" class="form-label">Email</label>
      <input type="email" id="email" class="form-control" placeholder="name@gmail.com" required>
      <div id="emailHint" class="field-hint mb-3">Only Gmail addresses (@gmail.com) are accepted.</div>

      <label for="password" class="form-label">Password</label>
      <div class="input-group">
        <input type="password" id="password" class="form-control" minlength="8" required>
        <button type="button" class="btn btn-soft pw-toggle" data-target="password" aria-label="Show password"><i class="bi bi-eye"></i></button>
      </div>
      <ul id="pwRules" class="pw-rules mb-3">
        <li data-rule="len">At least 8 characters</li>
        <li data-rule="upper">One uppercase letter</li>
        <li data-rule="lower">One lowercase letter</li>
        <li data-rule="num">One number</li>
      </ul>

      <button type="submit" class="btn btn-primary w-100">Create Account</button>
      <p class="text-center mt-3 mb-0">Already registered? <a href="index.php">Log in</a></p>
    </form>
  </main>
  <script src="assets/script.js"></script>
</body>
</html>