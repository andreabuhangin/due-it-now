<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Due It Now – Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <main class="container py-5">
    <h1 class="h3">Welcome, <?= htmlspecialchars($_SESSION["full_name"]) ?>!</h1>
    <a href="api/logout.php" class="btn btn-outline-danger mt-3">Log out</a>
  </main>
</body>
</html>