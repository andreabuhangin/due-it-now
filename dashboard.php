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
    <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0d6efd">
</head>
<body class="bg-body-tertiary">
  <nav class="navbar bg-body shadow-sm">
  <div class="container">
    <span class="navbar-brand fw-bold">Due It Now</span>
    <span>
      Hi, <?= htmlspecialchars($_SESSION["full_name"]) ?>
      <button id="themeToggle" class="btn btn-outline-secondary btn-sm ms-2" type="button">🌙 Dark</button>
      <a href="api/logout.php" class="btn btn-outline-danger btn-sm ms-2">Log out</a>
    </span>
  </div>
</nav>

  <main class="container py-4">
    <div id="alert" role="alert"></div>
   <button id="notifyBtn" class="btn btn-outline-primary btn-sm mb-3" type="button">🔔 Enable reminders</button>
<div id="dueAlerts"></div>
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
  <div class="toast-container position-fixed top-0 end-0 p-3" id="toastBox" style="margin-top: 60px;"></div>
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalTitle" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editForm" class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title h5" id="editModalTitle">Edit task</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="edit_id">
        <label for="edit_title" class="form-label">Title</label>
        <input type="text" id="edit_title" class="form-control mb-3" required>

        <label for="edit_category" class="form-label">Category</label>
        <input type="text" id="edit_category" class="form-control mb-3">

        <div class="row">
          <div class="col-6">
            <label for="edit_priority" class="form-label">Priority</label>
            <select id="edit_priority" class="form-select mb-3">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>
          <div class="col-6">
            <label for="edit_status" class="form-label">Status</label>
            <select id="edit_status" class="form-select mb-3">
              <option value="pending">Pending</option>
              <option value="completed">Completed</option>
            </select>
          </div>
        </div>

        <label for="edit_due_date" class="form-label">Due date</label>
        <input type="date" id="edit_due_date" class="form-control mb-3">

        <label for="edit_description" class="form-label">Description</label>
        <textarea id="edit_description" class="form-control" rows="3"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="assets/dashboard.js"></script>
    <script>
    if ("serviceWorker" in navigator) {
      navigator.serviceWorker.register("service-worker.js");
    }
  </script>
</body>
</html>