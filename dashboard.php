<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}
$name = $_SESSION["full_name"];
$parts = preg_split('/\s+/', trim($name));
$initials = strtoupper(mb_substr($parts[0], 0, 1) . (count($parts) > 1 ? mb_substr(end($parts), 0, 1) : ""));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Due It Now – Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0e1524">
</head>
<body>
<div class="app">

  <aside class="sidebar">
    <div class="brand"><span class="brand-dot"></span>Due It Now</div>

    <nav class="side-nav" aria-label="Main">
      <a href="#overview" class="active"><i class="bi bi-house"></i>Overview</a>
      <a href="#addTask"><i class="bi bi-plus-circle"></i>Add task</a>
      <a href="#tasks"><i class="bi bi-check2-square"></i>My tasks</a>
    </nav>

    <div class="side-bottom">
      <div class="side-card">
        <h2>Deadline reminders</h2>
        <p>Get alerts when a task is almost due.</p>
        <button id="notifyBtn" class="btn btn-soft w-100" type="button"><i class="bi bi-bell me-1"></i>Enable</button>
      </div>

      <div class="user-row">
        <span class="avatar"><?= htmlspecialchars($initials) ?></span>
        <span class="user-name"><?= htmlspecialchars($name) ?></span>
        <button id="themeToggle" class="btn btn-icon" type="button" aria-label="Toggle theme"><i class="bi bi-moon"></i></button>
        <a href="api/logout.php" class="btn btn-icon" aria-label="Log out" title="Log out"><i class="bi bi-box-arrow-right"></i></a>
      </div>
    </div>
  </aside>

  <main class="main">
    <header class="topbar">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <label for="search" class="visually-hidden">Search tasks</label>
        <input type="search" id="search" placeholder="Search tasks...">
      </div>
    </header>

    <section id="overview" class="page-head">
      <div>
        <h1>My Tasks</h1>
        <p>Stay on top of your deadlines.</p>
      </div>
      <div>
        <label for="statusFilter" class="visually-hidden">Filter by status</label>
        <select id="statusFilter" class="form-select pill-select">
          <option value="all">All statuses</option>
          <option value="pending">Pending</option>
          <option value="completed">Completed</option>
        </select>
      </div>
    </section>

    <div id="dueAlerts"></div>

    <section class="stats" aria-label="Progress summary">
      <div class="stat"><span class="stat-label">Pending</span><span class="stat-num" id="countPending">0</span><span class="stat-sub"><i class="dot dot-medium"></i>still to do</span></div>
      <div class="stat"><span class="stat-label">Completed</span><span class="stat-num" id="countDone">0</span><span class="stat-sub"><i class="dot dot-done"></i>finished</span></div>
      <div class="stat"><span class="stat-label">Total</span><span class="stat-num" id="countTotal">0</span><span class="stat-sub"><i class="dot dot-low"></i>all tasks</span></div>
    </section>

    <section id="addTask" class="panel">
      <h2 class="panel-title">Add a task</h2>
      <form id="taskForm" class="row g-2">
        <div class="col-md-4">
          <label for="title" class="form-label">Title</label>
          <input type="text" id="title" class="form-control" required>
        </div>
        <div class="col-md-2">
          <label for="category" class="form-label">Category</label>
          <input type="text" id="category" class="form-control" placeholder="e.g. School">
        </div>
        <div class="col-md-2">
          <label for="priority" class="form-label">Priority</label>
          <select id="priority" class="form-select">
            <option value="low">Low</option>
            <option value="medium" selected>Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <div class="col-md-2">
          <label for="due_date" class="form-label">Due date</label>
          <input type="date" id="due_date" class="form-control">
        </div>
        <div class="col-md-2">
          <label for="due_time" class="form-label">Due time</label>
          <input type="time" id="due_time" class="form-control">
        </div>
        <div class="col-12">
          <label for="description" class="form-label">Description</label>
          <textarea id="description" class="form-control" rows="2"></textarea>
        </div>
        <div class="col-12"><button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add task</button></div>
      </form>
    </section>

    <section id="tasks">
      <div id="taskBody" class="task-list"></div>
    </section>
  </main>
</div>

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

        <div class="row">
          <div class="col-6">
            <label for="edit_due_date" class="form-label">Due date</label>
            <input type="date" id="edit_due_date" class="form-control mb-3">
          </div>
          <div class="col-6">
            <label for="edit_due_time" class="form-label">Due time</label>
            <input type="time" id="edit_due_time" class="form-control mb-3">
          </div>
        </div>

        <label for="edit_description" class="form-label">Description</label>
        <textarea id="edit_description" class="form-control" rows="3"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save changes</button>
      </div>
    </form>
  </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastBox"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/dashboard.js"></script>
<script>
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("service-worker.js");
  }
</script>
</body>
</html>