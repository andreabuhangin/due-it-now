const API = "api/tasks.php";
let tasks = [];

// Escape text so user input can't inject HTML (XSS protection)
const esc = (s) =>
  String(s ?? "").replace(/[&<>"']/g, (c) =>
    ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

const notify = (message, type = "success") => {
  document.getElementById("alert").innerHTML =
    `<div class="alert alert-${type}">${esc(message)}</div>`;
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

// UPDATE (toggle complete) and DELETE
document.getElementById("taskBody").addEventListener("click", async (e) => {
  const btn = e.target.closest("button");
  if (!btn) return;
  const id = Number(btn.dataset.id);
  const task = tasks.find((t) => t.id == id);

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

// Live search and filter (no page reload)
document.getElementById("search").addEventListener("input", render);
document.getElementById("statusFilter").addEventListener("change", render);

loadTasks();