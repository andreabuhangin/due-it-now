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
  <link href="assets/style.css" rel="stylesheet">
</head>
<body class="bg-light">
  <nav class="navbar bg-white shadow-sm">
    <div class="container">
      <span class="navbar-brand fw-bold">Due It Now</span>
      <span>
        Hi, <?= htmlspecialchars($_SESSION["full_name"]) ?>
        <a href="api/logout.php" class="btn btn-outline-danger btn-sm ms-2">Log out</a>
      </span>
    </div>
  </nav>

  <main class="container py-4">
    <div id="alert" role="alert"></div>

    <section class="row g-3 mb-4" aria-label="Progress summary">
      <div class="col-4"><div class="card card-body text-center"><div class="h3 mb-0" id="countPending">0</div><small>Pending</small></div></div>
      <div class="col-4"><div class="card card-body text-center"><div class="h3 mb-0" id="countDone">0</div><small>Completed</small></div></div>
      <div class="col-4"><div class="card card-body text-center"><div class="h3 mb-0" id="countTotal">0</div><small>Total</small></div></div>
    </section>

    <section class="card card-body mb-4">
      <h2 class="h5">Add a task</h2>
      <form id="taskForm" class="row g-2">
        <div class="col-md-4">
          <label for="title" class="form-label">Title</label>
          <input type="text" id="title" class="form-control" required>
        </div>
        <div class="col-md-3">
          <label for="category" class="form-label">Category</label>
          <input type="text" id="category" class="form-control" placeholder="e.g. Assignment">
        </div>
        <div class="col-md-2">
          <label for="priority" class="form-label">Priority</label>
          <select id="priority" class="form-select">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="due_date" class="form-label">Due date</label>
          <input type="date" id="due_date" class="form-control">
        </div>
        <div class="col-12">
          <label for="description" class="form-label">Description</label>
          <textarea id="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-12"><button type="submit" class="btn btn-primary">Add Task</button></div>
      </form>
    </section>

    <section class="card card-body">
      <div class="row g-2 mb-3">
        <div class="col-md-8">
          <label for="search" class="visually-hidden">Search tasks</label>
          <input type="search" id="search" class="form-control" placeholder="Search tasks...">
        </div>
        <div class="col-md-4">
          <label for="statusFilter" class="visually-hidden">Filter by status</label>
          <select id="statusFilter" class="form-select">
            <option value="all">All statuses</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
          </select>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr><th>Title</th><th>Category</th><th>Priority</th><th>Due</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody id="taskBody"></tbody>
        </table>
      </div>
    </section>
  </main>

  <script src="assets/dashboard.js"></script>
</body>
</html>