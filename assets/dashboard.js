const API = "api/tasks.php";
let tasks = [];

// Escape text so user input can't inject HTML (XSS protection)
const esc = (s) =>
  String(s ?? "").replace(/[&<>"']/g, (c) =>
    ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

const notify = (message, type = "success") => {
  const box = document.getElementById("toastBox");
  const el = document.createElement("div");
  el.className = `toast align-items-center text-bg-${type} border-0`;
  el.setAttribute("role", "alert");
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${esc(message)}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>`;
  box.appendChild(el);
  el.addEventListener("hidden.bs.toast", () => el.remove());
  new bootstrap.Toast(el, { delay: 4000 }).show();
};

const request = async (method, body) => {
  try {
    const res = await fetch(API, {
      method,
      headers: { "Content-Type": "application/json" },
      body: body ? JSON.stringify(body) : undefined,
    });
    if (res.status === 401) window.location.href = "index.php";
    return await res.json();
  } catch {
    return { success: false, message: "Unable to connect to the server." };
  }
};

const render = () => {
  const term = document.getElementById("search").value.toLowerCase();
  const status = document.getElementById("statusFilter").value;

  const visible = tasks.filter(({ title, category, description, status: st }) =>
    (status === "all" || st === status) &&
    `${title} ${category} ${description}`.toLowerCase().includes(term));

  document.getElementById("taskBody").innerHTML = visible.length
    ? visible.map(({ id, title, category, priority, due_date, status: st }) => `
      <tr>
        <td class="${st === "completed" ? "text-decoration-line-through text-muted" : ""}">${esc(title)}</td>
        <td>${esc(category)}</td>
        <td><span class="badge text-bg-${priority === "high" ? "danger" : priority === "medium" ? "warning" : "secondary"}">${esc(priority)}</span></td>
        <td>${esc(due_date ?? "—")}</td>
        <td>${esc(st)}</td>
        <td>
                  <button class="btn btn-sm btn-outline-primary" data-action="edit" data-id="${id}">Edit</button>
          <button class="btn btn-sm btn-success" data-action="toggle" data-id="${id}">${st === "completed" ? "Undo" : "Done"}</button>
          <button class="btn btn-sm btn-outline-danger" data-action="delete" data-id="${id}">Delete</button>
        </td>
      </tr>`).join("")
    : `<tr><td colspan="6" class="text-center text-muted">No records found.</td></tr>`;

  const done = tasks.filter((t) => t.status === "completed").length;
  document.getElementById("countDone").textContent = done;
  document.getElementById("countPending").textContent = tasks.length - done;
  document.getElementById("countTotal").textContent = tasks.length;
};

const loadTasks = async () => {
  const result = await request("GET");
  if (result.success) {
    tasks = result.tasks;
    render();
     checkDueSoon();
  } else {
    notify(result.message, "danger");
  }
};

// CREATE
document.getElementById("taskForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const result = await request("POST", {
    title: document.getElementById("title").value,
    category: document.getElementById("category").value,
    priority: document.getElementById("priority").value,
    due_date: document.getElementById("due_date").value,
    description: document.getElementById("description").value,
  });
  notify(result.message, result.success ? "success" : "danger");
  if (result.success) {
    form.reset();
    loadTasks();
  }
});

// EDIT (open form), UPDATE (toggle complete) and DELETE
const editModal = new bootstrap.Modal(document.getElementById("editModal"));

document.getElementById("taskBody").addEventListener("click", async (e) => {
  const btn = e.target.closest("button");
  if (!btn) return;
  const id = Number(btn.dataset.id);
  const task = tasks.find((t) => t.id == id);

  if (btn.dataset.action === "edit") {
    document.getElementById("edit_id").value = task.id;
    document.getElementById("edit_title").value = task.title;
    document.getElementById("edit_category").value = task.category ?? "";
    document.getElementById("edit_priority").value = task.priority;
    document.getElementById("edit_status").value = task.status;
    document.getElementById("edit_due_date").value = task.due_date ?? "";
    document.getElementById("edit_description").value = task.description ?? "";
    editModal.show();
    return;
  }
  if (btn.dataset.action === "toggle") {
    const result = await request("PUT", {
      ...task,
      status: task.status === "completed" ? "pending" : "completed",
    });
    notify(result.message, result.success ? "success" : "danger");
  }
  if (btn.dataset.action === "delete") {
    if (!confirm("Delete this task?")) return;
    const result = await request("DELETE", { id });
    notify(result.message, result.success ? "success" : "danger");
  }
  loadTasks();
});

// Save the edited task
document.getElementById("editForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const result = await request("PUT", {
    id: Number(document.getElementById("edit_id").value),
    title: document.getElementById("edit_title").value,
    category: document.getElementById("edit_category").value,
    priority: document.getElementById("edit_priority").value,
    status: document.getElementById("edit_status").value,
    due_date: document.getElementById("edit_due_date").value,
    description: document.getElementById("edit_description").value,
  });
  notify(result.message, result.success ? "success" : "danger");
  if (result.success) {
    editModal.hide();
    loadTasks();
  }
});

// Live search and filter (no page reload)
document.getElementById("search").addEventListener("input", render);
document.getElementById("statusFilter").addEventListener("change", render);


loadTasks();

// Dark/light mode saved with localStorage
const toggle = document.getElementById("themeToggle");
const applyTheme = (theme) => {
  document.documentElement.setAttribute("data-bs-theme", theme);
  toggle.textContent = theme === "dark" ? "☀ Light" : "🌙 Dark";
};
applyTheme(localStorage.getItem("theme") || "light");

toggle.addEventListener("click", () => {
  const next = document.documentElement.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
  localStorage.setItem("theme", next);
  applyTheme(next);
});

const checkDueSoon = () => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const daysLeft = (d) => Math.round((new Date(`${d}T00:00:00`) - today) / 86400000);

  const pending = tasks.filter((t) => t.status === "pending" && t.due_date);
  const overdue = pending.filter((t) => daysLeft(t.due_date) < 0);
  const soon = pending.filter((t) => daysLeft(t.due_date) >= 0 && daysLeft(t.due_date) <= 2);

  let html = "";
  if (overdue.length)
    html += `<div class="alert alert-danger">⚠ Overdue: ${overdue.map((t) => esc(t.title)).join(", ")}</div>`;
  if (soon.length)
    html += `<div class="alert alert-warning">⏰ Due soon: ${soon
      .map((t) => `${esc(t.title)} (${daysLeft(t.due_date) === 0 ? "today" : `in ${daysLeft(t.due_date)} day(s)`})`)
      .join(", ")}</div>`;
  document.getElementById("dueAlerts").innerHTML = html;
  if ((overdue.length || soon.length) && !sessionStorage.getItem("toasted")) {
  notify(`${overdue.length} overdue, ${soon.length} due soon. Check your list!`, overdue.length ? "danger" : "warning");
  sessionStorage.setItem("toasted", "1");
}

  if ("Notification" in window && Notification.permission === "granted" &&
      (overdue.length || soon.length) && !sessionStorage.getItem("notified")) {
    new Notification("Due It Now", { body: `${overdue.length} overdue, ${soon.length} due soon` });
    sessionStorage.setItem("notified", "1");
  }
};

document.getElementById("notifyBtn").addEventListener("click", async () => {
  if (!("Notification" in window)) return notify("Notifications are not supported here.", "danger");
  const permission = await Notification.requestPermission();
  notify(permission === "granted" ? "Reminders enabled." : "Notifications are blocked.",
         permission === "granted" ? "success" : "warning");
  checkDueSoon();
});