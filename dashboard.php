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
  <link rel="icon" type="image/png" href="assets/icon-192.png">
<link rel="apple-touch-icon" href="assets/icon-192.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/style.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#0e1524">
</head>
<body>
<div class="app">

  <aside class="sidebar">
    <div class="brand"><img src="assets/logo.png" alt="" width="28" height="28" style="border-radius:8px; margin-right:8px;">Due It Now</div>
    <nav class="side-nav" aria-label="Main">
      <a href="#overview" data-view="overview"><i class="bi bi-house"></i>Overview</a>
      <a href="#tasks" data-view="tasks"><i class="bi bi-check2-square"></i>My tasks</a>
      <a href="#calendar" data-view="calendar"><i class="bi bi-calendar3"></i>Calendar</a>
      <a href="#insights" data-view="insights"><i class="bi bi-bar-chart"></i>Insights</a>
    </nav>

    <div class="side-bottom">
      <nav class="side-nav" aria-label="Account">
        <a href="#profile" data-view="profile"><i class="bi bi-person"></i>Profile</a>
        <a href="#settings" data-view="settings"><i class="bi bi-gear"></i>Settings</a>
      </nav>
      <div class="user-row">
        <span class="avatar" id="sideAvatar"><?= htmlspecialchars($initials) ?></span>
        <span class="user-name" id="sideName"><?= htmlspecialchars($name) ?></span>
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
      <div class="notification-wrap">
        <button type="button" id="notificationBtn" class="btn btn-soft notification-btn" aria-expanded="false" aria-controls="notificationPanel">
          <i class="bi bi-bell"></i><span class="notification-label">Notifications</span><span id="overdueBadge" class="notification-badge" hidden>0</span>
        </button>
        <div id="notificationPanel" class="notification-panel" hidden>
          <div class="notification-panel-head"><strong>Overdue tasks</strong><span id="notificationCountLabel" class="stat-sub">0 tasks</span></div>
          <div id="notificationList"></div>
        </div>
      </div>
    </header>

    <!-- OVERVIEW -->
    <section id="view-overview" class="view">
      <div class="page-head"><div><h1>Overview</h1><p>Stay on top of your deadlines.</p></div></div>
      <div class="stats stats-4" aria-label="Progress summary">
        <div class="stat"><span class="stat-label">Pending</span><span class="stat-num" id="countPending">0</span><span class="stat-sub"><i class="dot dot-medium"></i>still to do</span></div>
        <div class="stat"><span class="stat-label">Completed</span><span class="stat-num" id="countDone">0</span><span class="stat-sub"><i class="dot dot-done"></i>finished</span></div>
        <div class="stat"><span class="stat-label">Overdue</span><span class="stat-num" id="countOverdue">0</span><span class="stat-sub"><i class="dot dot-high"></i>need attention</span></div>
        <div class="stat"><span class="stat-label">Total</span><span class="stat-num" id="countTotal">0</span><span class="stat-sub"><i class="dot dot-low"></i>all tasks</span></div>
      </div>
      <div class="panel">
        <h2 class="panel-title">Upcoming deadlines</h2>
        <div class="upcoming-box"></div>
      </div>
    </section>

    <!-- MY TASKS -->
    <section id="view-tasks" class="view" hidden>
      <div class="page-head">
        <div><h1>My tasks</h1><p>Search, edit, complete or delete your tasks.</p></div>
        <div>
          <label for="statusFilter" class="visually-hidden">Filter by status</label>
          <select id="statusFilter" class="form-select pill-select">
            <option value="all">All statuses</option>
            <option value="pending">Pending</option>
            <option value="overdue">Overdue</option>
            <option value="completed">Completed</option>
          </select>
        </div>
      </div>
      <div class="panel add-task-panel">
        <h2 class="panel-title">Add a new task</h2>
        <form id="taskForm" class="row g-2">
          <div class="col-md-4">
            <label for="title" class="form-label">Title</label>
            <input type="text" id="title" class="form-control" required>
          </div>
          <div class="col-md-2">
            <label for="category" class="form-label">Category</label>
            <input type="text" id="category" class="form-control" placeholder="Choose or type a category">
            <div class="category-options" aria-label="Suggested categories">
              <button type="button" class="chip" data-category-target="category" data-category="Assignment">Assignment</button>
              <button type="button" class="chip" data-category-target="category" data-category="Project">Project</button>
              <button type="button" class="chip" data-category-target="category" data-category="Exam">Exam</button>
              <button type="button" class="chip" data-category-target="category" data-category="Personal">Personal</button>
            </div>
          </div>
          <div class="col-md-2">
            <label for="priority" class="form-label">Priority</label>
            <select id="priority" class="form-select">
              <option value="low">Low</option><option value="medium" selected>Medium</option><option value="high">High</option>
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
            <textarea id="description" class="form-control" rows="3"></textarea>
          </div>
          <div class="col-12"><button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Add task</button></div>
        </form>
      </div>
      <div id="taskBody" class="task-list"></div>
    </section>

    <!-- CALENDAR -->
    <section id="view-calendar" class="view" hidden>
      <div class="page-head">
        <div><h1>Calendar</h1><p>Your academic schedule.</p></div>
        <button type="button" class="btn btn-soft js-export"><i class="bi bi-download me-1"></i>Download .ics</button>
      </div>
         <div id="gBanner"></div>
      <div class="cal-layout">
        <div class="panel">
          <div class="cal-toolbar">
            <h2 id="calTitle" class="panel-title mb-0"></h2>
            <div class="cal-nav">
              <button type="button" id="calPrev" class="btn btn-soft" aria-label="Previous month"><i class="bi bi-chevron-left"></i></button>
              <button type="button" id="calToday" class="btn btn-soft">Today</button>
              <button type="button" id="calNext" class="btn btn-soft" aria-label="Next month"><i class="bi bi-chevron-right"></i></button>
            </div>
          </div>
          <div class="chip-row" id="calFilters">
            <button type="button" class="chip active" data-filter="all">All</button>
            <button type="button" class="chip" data-filter="assignment">Assignments</button>
            <button type="button" class="chip" data-filter="project">Projects</button>
            <button type="button" class="chip" data-filter="exam">Exams</button>
            <button type="button" class="chip" data-filter="personal">Personal</button>
          </div>
          <div class="cal-week"><span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span></div>
          <div id="calGrid" class="cal-grid"></div>
          <div class="legend">
            <span><i class="dot dot-high"></i>High</span>
            <span><i class="dot dot-medium"></i>Medium</span>
            <span><i class="dot dot-low"></i>Low</span>
            <span><i class="dot dot-done"></i>Done</span>
          </div>
          <hr class="cal-hr">
          <div id="dayPanel"></div>
        </div>
        <div class="panel">
          <h2 class="panel-title">Upcoming deadlines</h2>
          <div class="upcoming-box"></div>
        </div>
      </div>
    </section>

    <!-- INSIGHTS -->
    <section id="view-insights" class="view" hidden>
      <div class="page-head"><div><h1>Insights</h1><p>Analytics and productivity.</p></div></div>
      <div class="stats stats-4">
        <div class="stat"><span class="stat-label">Total tasks</span><span class="stat-num" id="inTotal">0</span><span class="stat-sub"><i class="dot dot-low"></i>created</span></div>
        <div class="stat"><span class="stat-label">Completed</span><span class="stat-num" id="inDone">0</span><span class="stat-sub"><i class="dot dot-done"></i>finished</span></div>
        <div class="stat"><span class="stat-label">Pending</span><span class="stat-num" id="inPending">0</span><span class="stat-sub"><i class="dot dot-medium"></i>still to do</span></div>
        <div class="stat"><span class="stat-label">Overdue</span><span class="stat-num" id="inOverdue">0</span><span class="stat-sub"><i class="dot dot-high"></i>past deadline</span></div>
      </div>
      <div class="insight-grid">
        <div class="panel insight-card">
          <div class="ring" id="inRing"><span id="inPct">0%</span></div>
          <div><div class="stat-label">Task completion</div><div class="insight-big">Overall</div><div class="stat-sub">completed vs. total</div></div>
        </div>
        <div class="panel insight-card">
          <div class="insight-icon"><i class="bi bi-alarm"></i></div>
          <div><div class="stat-label">On-time rate</div><div class="insight-big" id="inOnTime">—</div><div class="stat-sub" id="inOnTimeSub"></div></div>
        </div>
        <div class="panel insight-card">
          <div class="insight-icon"><i class="bi bi-trophy"></i></div>
          <div><div class="stat-label">Most productive week</div><div class="insight-big" id="inBestWeek">—</div><div class="stat-sub" id="inBestWeekSub"></div></div>
        </div>
      </div>
      <div class="chart-grid">
        <div class="panel"><h2 class="panel-title">Your productivity (tasks completed per week)</h2><div class="chart-box"><canvas id="chartWeek"></canvas></div></div>
        <div class="panel"><h2 class="panel-title">Tasks by category</h2><div class="chart-box"><canvas id="chartCat"></canvas></div></div>
      </div>
      <div class="panel">
        <h2 class="panel-title">Next deadlines</h2>
        <div id="inUpcoming"></div>
      </div>
    </section>

    <!-- PROFILE -->
    <section id="view-profile" class="view" hidden>
      <div class="page-head"><div><h1>Profile</h1><p>Your account details.</p></div></div>
      <div class="panel profile-head">
        <span class="avatar avatar-lg" id="pAvatar"><?= htmlspecialchars($initials) ?></span>
        <div>
          <div class="insight-big" id="pName"><?= htmlspecialchars($name) ?></div>
          <div class="stat-sub" id="pEmail"></div>
          <div class="stat-sub" id="pJoined"></div>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-lg-6">
          <form id="nameForm" class="panel h-100">
            <h2 class="panel-title">Edit profile</h2>
            <label for="pf_name" class="form-label">Full name</label>
            <input type="text" id="pf_name" class="form-control mb-3" required>
            <button type="submit" class="btn btn-primary">Save name</button>
          </form>
        </div>
        <div class="col-lg-6">
          <form id="passForm" class="panel h-100">
            <h2 class="panel-title">Change password</h2>
            <label for="pw_current" class="form-label">Current password</label>
            <input type="password" id="pw_current" class="form-control mb-3" required>
            <label for="pw_new" class="form-label">New password (8+ characters)</label>
            <input type="password" id="pw_new" class="form-control mb-3" minlength="8" required>
            <label for="pw_confirm" class="form-label">Confirm new password</label>
            <input type="password" id="pw_confirm" class="form-control mb-3" minlength="8" required>
            <button type="submit" class="btn btn-primary">Change password</button>
          </form>
        </div>
      </div>
    </section>

    <!-- SETTINGS -->
    <section id="view-settings" class="view" hidden>
      <div class="page-head"><div><h1>Settings</h1><p>Customize how Due It Now works for you.</p></div></div>
      <div class="panel">
        <div class="setting-row">
          <div><strong>Theme</strong><div class="stat-sub">Dark or light appearance.</div></div>
          <select id="setTheme" class="form-select pill-select">
            <option value="dark">Dark</option>
            <option value="light">Light</option>
          </select>
        </div>
        <div class="setting-row">
          <div><strong>Remind me before a deadline</strong><div class="stat-sub">Used for reminders in exported calendar events.</div></div>
          <select id="setLead" class="form-select pill-select">
            <option value="1">1 hour</option>
            <option value="3">3 hours</option>
            <option value="6">6 hours</option>
            <option value="24">1 day</option>
            <option value="48">2 days</option>
            <option value="72">3 days</option>
          </select>
        </div>
        <div class="setting-row">
  <div><strong>Google Calendar</strong><div class="stat-sub" id="gStatus">Checking...</div></div>
  <div id="gActions"></div>
</div>
        <div class="setting-row">
          <div><strong>Calendar sync</strong><div class="stat-sub">Download your pending tasks with reminders. Import into Google Calendar, Outlook or your phone.</div></div>
          <button type="button" class="btn btn-soft js-export"><i class="bi bi-download me-1"></i>Download .ics</button>
        </div>
      </div>
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
        <input type="text" id="edit_category" class="form-control mb-2" placeholder="Choose or type a category">
        <div class="category-options mb-3" aria-label="Suggested categories">
          <button type="button" class="chip" data-category-target="edit_category" data-category="Assignment">Assignment</button>
          <button type="button" class="chip" data-category-target="edit_category" data-category="Project">Project</button>
          <button type="button" class="chip" data-category-target="edit_category" data-category="Exam">Exam</button>
          <button type="button" class="chip" data-category-target="edit_category" data-category="Personal">Personal</button>
        </div>

        <div class="row">
          <div class="col-12 col-md-6">
            <label for="edit_priority" class="form-label">Priority</label>
            <select id="edit_priority" class="form-select mb-3">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
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
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-danger" id="editDeleteBtn"><i class="bi bi-trash me-1"></i>Delete task</button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-soft" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastBox"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="assets/dashboard.js"></script>
<script>
  if ("serviceWorker" in navigator) {
    navigator.serviceWorker.register("service-worker.js");
  }
</script>
</body>
</html>