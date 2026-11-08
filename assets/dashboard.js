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

const fmtDue = (d, t) => {
  if (!d) return "—";
  const date = new Date(`${d}T00:00:00`).toLocaleDateString("en-US", { month: "short", day: "numeric" });
  const time = t ? new Date(`1970-01-01T${t}`).toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" }) : "";
  return time ? `${date}, ${time}` : date;
};

const render = () => {
  const term = document.getElementById("search").value.toLowerCase();
  const status = document.getElementById("statusFilter").value;

  const visible = tasks.filter(({ title, category, description, status: st }) =>
    (status === "all" || st === status) &&
    `${title} ${category} ${description}`.toLowerCase().includes(term));

  document.getElementById("taskBody").innerHTML = visible.length
    ? visible.map(({ id, title, category, priority, due_date, due_time, status: st }) => `
      <tr>
        <td class="${st === "completed" ? "text-decoration-line-through text-muted" : ""}">${esc(title)}</td>
        <td>${esc(category)}</td>
        <td><span class="pill pill-${esc(priority)}">${esc(priority)}</span></td>
        <td>${esc(fmtDue(due_date, due_time))}</td>
        <td><span class="pill pill-${esc(st)}">${esc(st)}</span></td>
        <td class="text-nowrap">
          <button class="btn btn-sm btn-icon" data-action="edit" data-id="${id}" title="Edit"><i class="bi bi-pencil"></i></button>
          <button class="btn btn-sm btn-icon" data-action="toggle" data-id="${id}" title="${st === "completed" ? "Mark pending" : "Mark done"}"><i class="bi ${st === "completed" ? "bi-arrow-counterclockwise" : "bi-check2"}"></i></button>
          <button class="btn btn-sm btn-icon btn-icon-danger" data-action="delete" data-id="${id}" title="Delete"><i class="bi bi-trash"></i></button>
        </td>
      </tr>`).join("")
    : `<tr><td colspan="6" class="text-center text-muted py-4">No tasks found.</td></tr>`;

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
    if (firstLoad) {
      firstLoad = false;
      deadlinePopups();
    }
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
        due_time: document.getElementById("due_time").value,
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
        document.getElementById("edit_due_time").value = (task.due_time ?? "").slice(0, 5);
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
        due_time: document.getElementById("edit_due_time").value,
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

// Dark/light mode saved with localStorage
const toggle = document.getElementById("themeToggle");
const applyTheme = (theme) => {
  document.documentElement.setAttribute("data-bs-theme", theme);
  toggle.innerHTML = theme === "dark" ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
};
applyTheme(localStorage.getItem("theme") || "light");

toggle.addEventListener("click", () => {
  const next = document.documentElement.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
  localStorage.setItem("theme", next);
  applyTheme(next);
});

let firstLoad = true;
const alerted = new Set();

// Deadline = date + time (23:59 if no time was set)
const hoursLeft = (t) => (new Date(`${t.due_date}T${(t.due_time || "23:59").slice(0, 5)}`) - new Date()) / 36e5;

const fmtLeft = (h) => {
  const a = Math.abs(h);
  const span = a < 1 ? `${Math.max(1, Math.round(a * 60))} min` : a < 24 ? `${Math.round(a)} hr` : `${Math.round(a / 24)} day(s)`;
  return h < 0 ? `Overdue by ${span}` : `Due in ${span}`;
};

const urgentTasks = () =>
  tasks.filter((t) => t.status === "pending" && t.due_date && hoursLeft(t) <= 48)
       .sort((a, b) => hoursLeft(a) - hoursLeft(b));

const checkDueSoon = () => {
  const list = urgentTasks();
  document.getElementById("dueAlerts").innerHTML = list.length
    ? `<div class="alert alert-${list.some((t) => hoursLeft(t) < 0) ? "danger" : "warning"}">
        <i class="bi bi-clock me-1"></i> ${list.map((t) => `${esc(t.title)} (${fmtLeft(hoursLeft(t))})`).join(" · ")}
       </div>`
    : "";
};

const showPopup = (t) => {
  const h = hoursLeft(t);
  const [icon, color] = h < 0 ? ["bi-exclamation-octagon", "danger"] : h < 24 ? ["bi-exclamation-triangle", "danger"] : ["bi-clock", "warning"];
  const el = document.createElement("div");
  el.className = `toast border-0 text-bg-${color}`;
  el.setAttribute("role", "alert");
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body"><strong><i class="bi ${icon} me-1"></i>${fmtLeft(h)}</strong><br>${esc(t.title)}</div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>`;
  document.getElementById("toastBox").appendChild(el);
  el.addEventListener("hidden.bs.toast", () => el.remove());
  new bootstrap.Toast(el, { delay: 10000 }).show();

  if ("Notification" in window && Notification.permission === "granted") {
    new Notification(fmtLeft(h), { body: t.title, tag: `task-${t.id}` });
  }
};

// On page open: pop up every urgent task. Afterwards: check every minute and pop up
// any task that gets within 1 hour of its deadline.
const deadlinePopups = () => {
  urgentTasks().forEach((t, i) => {
    alerted.add(t.id);
    setTimeout(() => showPopup(t), i * 600);
  });
};

setInterval(() => {
  checkDueSoon();
  urgentTasks().filter((t) => hoursLeft(t) <= 1 && !alerted.has(t.id))
    .forEach((t) => { alerted.add(t.id); showPopup(t); });
}, 60000);

document.getElementById("notifyBtn").addEventListener("click", async () => {
  if (!("Notification" in window)) return notify("Notifications are not supported here.", "danger");
  const permission = await Notification.requestPermission();
  notify(permission === "granted" ? "Reminders enabled." : "Notifications are blocked.",
         permission === "granted" ? "success" : "warning");
  checkDueSoon();
});

loadTasks();



