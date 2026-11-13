const API = "api/tasks.php";
const PROFILE_API = "api/profile.php";
const $ = (id) => document.getElementById(id);

let tasks = [];
let firstLoad = true;
const alerted = new Set();

/* ---------- helpers ---------- */
const esc = (s) =>
  String(s ?? "").replace(/[&<>"']/g, (c) =>
    ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

const pad = (n) => String(n).padStart(2, "0");
const ymd = (d) => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const stamp = (d) => `${d.getFullYear()}${pad(d.getMonth() + 1)}${pad(d.getDate())}T${pad(d.getHours())}${pad(d.getMinutes())}00`;
const dstamp = (d) => stamp(d).slice(0, 8);
const startOfToday = () => { const d = new Date(); d.setHours(0, 0, 0, 0); return d; };
const deadline = (t) => new Date(`${t.due_date}T${(t.due_time || "23:59").slice(0, 5)}`);
const hoursLeft = (t) => (deadline(t) - new Date()) / 36e5;
const dayDiff = (t) => Math.round((new Date(`${t.due_date}T00:00:00`) - startOfToday()) / 864e5);
const isOverdue = (t) => t.status === "pending" && t.due_date && hoursLeft(t) < 0;
const currentView = () => location.hash.slice(1) || "overview";
const getLead = () => { try { return Number(localStorage.getItem("leadHours")) || 48; } catch { return 48; } };
const initialsOf = (name) => {
  const p = name.trim().split(/\s+/);
  return ((p[0]?.[0] || "") + (p.length > 1 ? p[p.length - 1][0] : "")).toUpperCase();
};

const fmtDue = (d, t) => {
  if (!d) return "—";
  const date = new Date(`${d}T00:00:00`).toLocaleDateString("en-US", { month: "short", day: "numeric" });
  const time = t ? new Date(`1970-01-01T${t}`).toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" }) : "";
  return time ? `${date}, ${time}` : date;
};

const fmtLeft = (h) => {
  const a = Math.abs(h);
  const span = a < 1 ? `${Math.max(1, Math.round(a * 60))} min` : a < 24 ? `${Math.round(a)} hr` : `${Math.round(a / 24)} day(s)`;
  return h < 0 ? `Overdue by ${span}` : `Due in ${span}`;
};

const notify = (message, type = "success") => {
  const el = document.createElement("div");
  el.className = `toast align-items-center text-bg-${type} border-0`;
  el.setAttribute("role", "alert");
  el.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">${esc(message)}</div>
      <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>`;
  $("toastBox").appendChild(el);
  el.addEventListener("hidden.bs.toast", () => el.remove());
  new bootstrap.Toast(el, { delay: 4000 }).show();
};

const call = async (url, method = "GET", body) => {
  try {
    const res = await fetch(url, {
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
const request = (method, body) => call(API, method, body);

/* ---------- theme (runs first so later errors can't break it) ---------- */
const themeBtn = $("themeToggle");
const getPref = (k) => { try { return localStorage.getItem(k); } catch { return null; } };
const setPref = (k, v) => { try { localStorage.setItem(k, v); } catch {} };
const applyTheme = (theme) => {
  document.documentElement.setAttribute("data-bs-theme", theme);
  if (themeBtn) themeBtn.innerHTML = theme === "dark" ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
  const sel = $("setTheme");
  if (sel) sel.value = theme;
  if (currentView() === "insights") setTimeout(() => { try { renderInsights(); } catch {} }, 0);
};
themeBtn?.addEventListener("click", () => {
  const next = document.documentElement.getAttribute("data-bs-theme") === "dark" ? "light" : "dark";
  setPref("theme", next);
  applyTheme(next);
});
$("setTheme")?.addEventListener("change", (e) => {
  setPref("theme", e.target.value);
  applyTheme(e.target.value);
});
applyTheme(getPref("theme") || "dark");

/* ---------- views (sidebar pages) ---------- */
const VIEWS = ["overview", "add", "tasks", "insights", "calendar", "profile", "settings"];

const showView = (name) => {
  if (name === "add") name = "tasks";
  if (!VIEWS.includes(name)) name = "overview";
  document.querySelectorAll(".view").forEach((v) => (v.hidden = v.id !== `view-${name}`));
  document.querySelectorAll("[data-view]").forEach((a) => a.classList.toggle("active", a.dataset.view === name));
  if (name === "insights") renderInsights();
  if (name === "calendar") renderCalendar();
  if (name === "profile") loadProfile();
  window.scrollTo(0, 0);
};
window.addEventListener("hashchange", () => showView(currentView()));

/* ---------- task list (My tasks) ---------- */
const render = () => {
  const term = $("search").value.toLowerCase();
  const status = $("statusFilter").value;

  const visible = tasks.filter(({ title, category, description, status: st }) =>
    (status === "all" || st === status) &&
    `${title} ${category} ${description}`.toLowerCase().includes(term));

  $("taskBody").innerHTML = visible.length
    ? visible.map((t) => {
      const done = t.status === "completed";
      const late = isOverdue(t);
      return `
      <div class="task ${done ? "is-done" : ""} ${late ? "is-late" : ""}">
        <input type="checkbox" class="form-check-input task-check" data-action="toggle" data-id="${t.id}" ${done ? "checked" : ""} aria-label="Mark ${esc(t.title)} as ${done ? "pending" : "done"}">
        <span class="dot dot-${done ? "done" : esc(t.priority)}"></span>
        <div class="task-main">
          <div class="task-title">${esc(t.title)}</div>
          <div class="task-sub">${esc([t.category, t.description].filter(Boolean).join(" · ") || "No details")}</div>
        </div>
        <div class="task-meta">
          <span class="pill pill-${esc(t.priority)}">${esc(t.priority)}</span>
          <span class="pill pill-${esc(t.status)}">${esc(t.status)}</span>
          ${late ? '<span class="pill pill-high">overdue</span>' : ""}
          <span class="pill pill-due"><i class="bi bi-clock me-1"></i>${esc(fmtDue(t.due_date, t.due_time))}</span>
          <span class="task-actions">
            <button class="btn btn-icon" data-action="edit" data-id="${t.id}" title="Edit"><i class="bi bi-pencil"></i></button>
            <button class="btn btn-icon btn-icon-danger" data-action="delete" data-id="${t.id}" title="Delete"><i class="bi bi-trash"></i></button>
          </span>
        </div>
      </div>`;
    }).join("")
    : `<div class="empty"><i class="bi bi-inbox"></i><p>No tasks found.</p></div>`;

  const done = tasks.filter((t) => t.status === "completed").length;
  $("countDone").textContent = done;
  $("countPending").textContent = tasks.length - done;
  $("countOverdue").textContent = tasks.filter(isOverdue).length;
  $("countTotal").textContent = tasks.length;
};

/* ---------- upcoming deadlines (Overview + Calendar) ---------- */
const renderUpcoming = () => {
  const pending = tasks.filter((t) => t.status === "pending" && t.due_date);
  const groups = [
    ["Overdue", pending.filter((t) => hoursLeft(t) < 0), "high"],
    ["Due today", pending.filter((t) => hoursLeft(t) >= 0 && dayDiff(t) === 0), "medium"],
    ["Due tomorrow", pending.filter((t) => dayDiff(t) === 1), "pending"],
    ["Due this week", pending.filter((t) => dayDiff(t) >= 2 && dayDiff(t) <= 7), "pending"],
  ];
  const html = groups.map(([label, list, tone]) => `
    <div class="up-group">
      <div class="up-head"><span>${label}</span><span class="pill pill-${tone}">${list.length}</span></div>
      ${list.length
        ? list.sort((a, b) => deadline(a) - deadline(b)).map((t) => `
          <div class="up-item">
            <i class="dot dot-${esc(t.priority)}"></i>
            <span class="up-title">${esc(t.title)}</span>
            <span class="up-when">${esc(fmtDue(t.due_date, t.due_time))}</span>
          </div>`).join("")
        : `<div class="up-empty">Nothing here</div>`}
    </div>`).join("");
  document.querySelectorAll(".upcoming-box").forEach((box) => (box.innerHTML = html));
};

/* ---------- calendar ---------- */
let calMonth = new Date(); calMonth.setDate(1);
let calSelected = ymd(new Date());
let calFilter = "all";
const matchesFilter = (t) => calFilter === "all" || (t.category || "").toLowerCase().includes(calFilter);

const gcalLink = (t) => {
  let dates;
  if (t.due_time) {
    const s = deadline(t);
    dates = `${stamp(s)}/${stamp(new Date(s.getTime() + 36e5))}`;
  } else {
    const s = new Date(`${t.due_date}T00:00:00`);
    dates = `${dstamp(s)}/${dstamp(new Date(s.getTime() + 864e5))}`;
  }
  const p = new URLSearchParams({ action: "TEMPLATE", text: t.title, details: `${t.description || ""}\n(Due It Now)`, dates });
  return `https://calendar.google.com/calendar/render?${p}`;
};

const renderDayPanel = () => {
  const list = tasks.filter((t) => t.due_date === calSelected && matchesFilter(t));
  const label = new Date(`${calSelected}T00:00:00`).toLocaleDateString("en-US", { weekday: "long", month: "long", day: "numeric", year: "numeric" });
  $("dayPanel").innerHTML = `<h3 class="h6 mb-2">${label}</h3>` + (list.length
    ? list.map((t) => `
      <div class="day-task">
        <i class="dot dot-${t.status === "completed" ? "done" : esc(t.priority)}"></i>
        <div class="task-main">
          <div class="task-title">${esc(t.title)}</div>
          <div class="task-sub">${esc(fmtDue(t.due_date, t.due_time))} · ${esc(t.category || "No category")}</div>
        </div>
        <span class="pill pill-${esc(t.priority)}">${esc(t.priority)}</span>
        <a class="btn btn-icon" href="${esc(gcalLink(t))}" target="_blank" rel="noopener" title="Add to Google Calendar"><i class="bi bi-calendar-plus"></i></a>
      </div>`).join("")
    : `<div class="up-empty">No tasks on this date.</div>`);
};

const renderCalendar = () => {
  const y = calMonth.getFullYear();
  const m = calMonth.getMonth();
  $("calTitle").textContent = calMonth.toLocaleDateString("en-US", { month: "long", year: "numeric" });
  const firstDay = new Date(y, m, 1).getDay();
  const days = new Date(y, m + 1, 0).getDate();
  const todayKey = ymd(new Date());

  const byDay = {};
  tasks.filter((t) => t.due_date && matchesFilter(t)).forEach((t) => (byDay[t.due_date] ||= []).push(t));

  let cells = "";
  for (let i = 0; i < firstDay; i++) cells += `<div class="cal-cell cal-empty"></div>`;
  for (let d = 1; d <= days; d++) {
    const key = `${y}-${pad(m + 1)}-${pad(d)}`;
    const dots = (byDay[key] || []).slice(0, 4)
      .map((t) => `<i class="dot dot-${t.status === "completed" ? "done" : esc(t.priority)}"></i>`).join("");
    cells += `<button type="button" class="cal-cell ${key === todayKey ? "is-today" : ""} ${key === calSelected ? "is-selected" : ""}" data-date="${key}">
      <span class="cal-num">${d}</span><span class="cal-dots">${dots}</span></button>`;
  }
  $("calGrid").innerHTML = cells;
  renderDayPanel();
};

$("calGrid").addEventListener("click", (e) => {
  const cell = e.target.closest("[data-date]");
  if (!cell) return;
  calSelected = cell.dataset.date;
  renderCalendar();
});
$("calPrev").addEventListener("click", () => { calMonth.setMonth(calMonth.getMonth() - 1); renderCalendar(); });
$("calNext").addEventListener("click", () => { calMonth.setMonth(calMonth.getMonth() + 1); renderCalendar(); });
$("calToday").addEventListener("click", () => {
  calMonth = new Date(); calMonth.setDate(1);
  calSelected = ymd(new Date());
  renderCalendar();
});
$("calFilters").addEventListener("click", (e) => {
  const chip = e.target.closest("[data-filter]");
  if (!chip) return;
  calFilter = chip.dataset.filter;
  document.querySelectorAll("#calFilters .chip").forEach((c) => c.classList.toggle("active", c === chip));
  renderCalendar();
});

/* ---------- calendar export (.ics with reminders) ---------- */
const exportICS = () => {
  const lead = getLead() * 60;
  const esc2 = (s) => String(s || "").replace(/\\/g, "\\\\").replace(/\n/g, "\\n").replace(/[,;]/g, (c) => `\\${c}`);
  const now = new Date().toISOString().replace(/[-:]/g, "").split(".")[0] + "Z";
  const events = tasks.filter((t) => t.status === "pending" && t.due_date).map((t) => {
    const s = deadline(t);
    const start = t.due_time ? `DTSTART:${stamp(s)}` : `DTSTART;VALUE=DATE:${dstamp(s)}`;
    const end = t.due_time
      ? `DTEND:${stamp(new Date(s.getTime() + 36e5))}`
      : `DTEND;VALUE=DATE:${dstamp(new Date(s.getTime() + 864e5))}`;
    return [
      "BEGIN:VEVENT", `UID:task-${t.id}@dueitnow`, `DTSTAMP:${now}`,
      `SUMMARY:${esc2(t.title)}`, `DESCRIPTION:${esc2(t.description)}`, start, end,
      "BEGIN:VALARM", "ACTION:DISPLAY", `DESCRIPTION:${esc2(t.title)} is due soon`, `TRIGGER:-PT${lead}M`, "END:VALARM",
      "END:VEVENT",
    ].join("\r\n");
  });
  if (!events.length) return notify("No pending tasks with a due date to export.", "warning");
  const ics = ["BEGIN:VCALENDAR", "VERSION:2.0", "PRODID:-//Due It Now//EN", "CALSCALE:GREGORIAN", ...events, "END:VCALENDAR"].join("\r\n");
  const a = document.createElement("a");
  a.href = URL.createObjectURL(new Blob([ics], { type: "text/calendar" }));
  a.download = "due-it-now.ics";
  a.click();
  URL.revokeObjectURL(a.href);
  notify("Calendar file downloaded. Import it into your calendar app.");
};
document.querySelectorAll(".js-export").forEach((b) => b.addEventListener("click", exportICS));

/* ---------- insights ---------- */
let chartCat = null;
let chartWeek = null;
const cssVar = (n) => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
const weekStart = (d) => { const x = new Date(d); x.setHours(0, 0, 0, 0); x.setDate(x.getDate() - ((x.getDay() + 6) % 7)); return x; };
const completedDate = (t) =>
  t.completed_at ? new Date(t.completed_at.replace(" ", "T")) : t.due_date ? new Date(`${t.due_date}T00:00:00`) : null;

const renderInsights = () => {
  const total = tasks.length;
  const done = tasks.filter((t) => t.status === "completed");
  const pct = total ? Math.round((done.length / total) * 100) : 0;

  $("inTotal").textContent = total;
  $("inDone").textContent = done.length;
  $("inPending").textContent = total - done.length;
  $("inOverdue").textContent = tasks.filter(isOverdue).length;
  $("inPct").textContent = `${pct}%`;
  $("inRing").style.setProperty("--pct", pct);

  const timed = done.filter((t) => t.completed_at && t.due_date);
  const onTime = timed.filter((t) => completedDate(t) <= deadline(t)).length;
  $("inOnTime").textContent = timed.length ? `${Math.round((onTime / timed.length) * 100)}%` : "—";
  $("inOnTimeSub").textContent = timed.length ? `${onTime} of ${timed.length} finished on time` : "Complete a task to see this";

  const weeks = {};
  done.forEach((t) => {
    const d = completedDate(t);
    if (!d) return;
    const k = ymd(weekStart(d));
    weeks[k] = (weeks[k] || 0) + 1;
  });
  const best = Object.entries(weeks).sort((a, b) => b[1] - a[1])[0];
  $("inBestWeek").textContent = best
    ? `Week of ${new Date(`${best[0]}T00:00:00`).toLocaleDateString("en-US", { month: "short", day: "numeric" })}` : "—";
  $("inBestWeekSub").textContent = best ? `${best[1]} task(s) completed` : "No completed tasks yet";

  const next = tasks.filter((t) => t.status === "pending" && t.due_date && hoursLeft(t) >= 0)
    .sort((a, b) => deadline(a) - deadline(b)).slice(0, 5);
  $("inUpcoming").innerHTML = next.length
    ? next.map((t) => `
      <div class="up-item">
        <i class="dot dot-${esc(t.priority)}"></i>
        <span class="up-title">${esc(t.title)}</span>
        <span class="pill pill-${esc(t.priority)}">${esc(t.priority)}</span>
        <span class="up-when">${esc(fmtDue(t.due_date, t.due_time))}</span>
      </div>`).join("")
    : `<div class="up-empty">No upcoming deadlines.</div>`;

  if (typeof Chart === "undefined") return;

  const wLabels = [], wValues = [];
  const ws = weekStart(new Date());
  for (let i = 5; i >= 0; i--) {
    const d = new Date(ws); d.setDate(d.getDate() - i * 7);
    wLabels.push(d.toLocaleDateString("en-US", { month: "short", day: "numeric" }));
    wValues.push(weeks[ymd(d)] || 0);
  }

  const cats = {};
  tasks.forEach((t) => {
    const key = ((t.category || "").trim() || "Uncategorized").toLowerCase();
    cats[key] = (cats[key] || 0) + 1;
  });
  const cLabels = Object.keys(cats).map((k) => k.charAt(0).toUpperCase() + k.slice(1));
  const cValues = Object.values(cats);

  const text = cssVar("--muted");
  const line = cssVar("--line");
  chartWeek?.destroy();
  chartCat?.destroy();

  chartWeek = new Chart($("chartWeek"), {
    type: "line",
    data: { labels: wLabels, datasets: [{ label: "Completed", data: wValues, borderColor: cssVar("--brand"), backgroundColor: "rgba(143,176,201,.15)", fill: true, tension: 0.35, pointRadius: 4 }] },
    options: {
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: text }, grid: { color: line } },
        y: { beginAtZero: true, ticks: { color: text, precision: 0 }, grid: { color: line } },
      },
    },
  });

  chartCat = new Chart($("chartCat"), {
    type: "doughnut",
    data: { labels: cLabels, datasets: [{ data: cValues, backgroundColor: ["#8fb0c9", "#e0a63f", "#7aa38b", "#d9776c", "#a78bfa", "#64748b"], borderWidth: 0 }] },
    options: { maintainAspectRatio: false, cutout: "68%", plugins: { legend: { position: "right", labels: { color: text } } } },
  });
};

/* ---------- reminders (alerts, pop-ups, desktop notifications) ---------- */
const urgentTasks = () =>
  tasks.filter((t) => t.status === "pending" && t.due_date && hoursLeft(t) <= getLead())
       .sort((a, b) => hoursLeft(a) - hoursLeft(b));

const renderNotifs = (list) => {
  const n = list.length;
  $("notifCount").textContent = n;
  $("notifCount").hidden = n === 0;
  $("notifPanel").innerHTML = n
    ? list.map((t) => `
      <div class="notif-item">
        <i class="dot dot-${esc(t.priority)}"></i>
        <div><div class="notif-title">${esc(t.title)}</div>
        <div class="notif-sub">${esc(fmtLeft(hoursLeft(t)))} · ${esc(fmtDue(t.due_date, t.due_time))}</div></div>
      </div>`).join("")
    : `<div class="up-empty p-3">You're all caught up.</div>`;
};
$("notifToggle").addEventListener("click", (e) => { e.stopPropagation(); $("notifPanel").hidden = !$("notifPanel").hidden; });
document.addEventListener("click", (e) => { if (!e.target.closest(".notif-wrap")) $("notifPanel").hidden = true; });

const checkDueSoon = () => {
  const list = urgentTasks();
  renderNotifs(list);
  $("dueAlerts").innerHTML = list.length
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
  $("toastBox").appendChild(el);
  el.addEventListener("hidden.bs.toast", () => el.remove());
  new bootstrap.Toast(el, { delay: 10000 }).show();

  if ("Notification" in window && Notification.permission === "granted") {
    new Notification(fmtLeft(h), { body: t.title, tag: `task-${t.id}` });
  }
};

const deadlinePopups = () => {
  urgentTasks().forEach((t, i) => {
    alerted.add(t.id);
    setTimeout(() => showPopup(t), i * 600);
  });
};

setInterval(() => {
  checkDueSoon();
  renderUpcoming();
  urgentTasks().filter((t) => hoursLeft(t) <= 1 && !alerted.has(t.id))
    .forEach((t) => { alerted.add(t.id); showPopup(t); });
}, 60000);

const updateNotifyStatus = () => {
  $("notifyStatus").textContent = !("Notification" in window) ? "Not supported in this browser."
    : Notification.permission === "granted" ? "Enabled."
    : Notification.permission === "denied" ? "Blocked. Allow notifications in your browser's site settings."
    : "Not enabled yet.";
};

$("notifyBtn").addEventListener("click", async () => {
  if (!("Notification" in window)) return notify("Notifications are not supported here.", "danger");
  const permission = await Notification.requestPermission();
  notify(permission === "granted" ? "Reminders enabled." : "Notifications are blocked.",
         permission === "granted" ? "success" : "warning");
  updateNotifyStatus();
  checkDueSoon();
});

/* ---------- load tasks ---------- */
const showLoadError = (msg) => {
  $("taskBody").innerHTML = `<div class="empty"><i class="bi bi-wifi-off"></i><p>${esc(msg || "Could not load your tasks.")}</p>
    <button type="button" class="btn btn-soft" data-action="retry"><i class="bi bi-arrow-repeat me-1"></i>Retry</button></div>`;
};
const loadTasks = async () => {
  const result = await request("GET");
  if (!result.success) return showLoadError(result.message);
  tasks = result.tasks;
  render();
  renderUpcoming();
  checkDueSoon();
  if (currentView() === "calendar") renderCalendar();
  if (currentView() === "insights") renderInsights();
  if (firstLoad) {
    firstLoad = false;
    deadlinePopups();
  }
};

/* ---------- create / edit / complete / delete ---------- */
$("taskForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const result = await request("POST", {
    title: $("title").value,
    category: $("category").value,
    priority: $("priority").value,
    due_date: $("due_date").value,
    due_time: $("due_time").value,
    description: $("description").value,
  });
  notify(result.message, result.success ? "success" : "danger");
  if (result.success) {
    form.reset();
    await loadTasks();
    location.hash = "tasks";
  }
});

let _editModal;
const editModal = { show: () => (_editModal ||= new bootstrap.Modal($("editModal"))).show(), hide: () => _editModal?.hide() };

$("taskBody").addEventListener("click", async (e) => {
  const btn = e.target.closest("[data-action]");
  if (!btn) return;
  if (btn.dataset.action === "retry") return loadTasks();
  const id = Number(btn.dataset.id);
  const task = tasks.find((t) => t.id == id);
  if (!task) return;

  if (btn.dataset.action === "edit") {
    $("edit_id").value = task.id;
    $("edit_title").value = task.title;
    $("edit_category").value = task.category ?? "";
    $("edit_priority").value = task.priority;
    $("edit_status").value = task.status;
    $("edit_due_date").value = task.due_date ?? "";
    $("edit_due_time").value = (task.due_time ?? "").slice(0, 5);
    $("edit_description").value = task.description ?? "";
    editModal.show();
    return;
  }
  if (btn.dataset.action === "toggle") {
    const result = await request("PUT", { ...task, status: task.status === "completed" ? "pending" : "completed" });
    notify(result.message, result.success ? "success" : "danger");
  }
  if (btn.dataset.action === "delete") {
    if (!confirm("Delete this task?")) return;
    const result = await request("DELETE", { id });
    notify(result.message, result.success ? "success" : "danger");
  }
  loadTasks();
});

$("editForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const result = await request("PUT", {
    id: Number($("edit_id").value),
    title: $("edit_title").value,
    category: $("edit_category").value,
    priority: $("edit_priority").value,
    status: $("edit_status").value,
    due_date: $("edit_due_date").value,
    due_time: $("edit_due_time").value,
    description: $("edit_description").value,
  });
  notify(result.message, result.success ? "success" : "danger");
  if (result.success) {
    editModal.hide();
    loadTasks();
  }
});

/* ---------- search and filter ---------- */
$("search").addEventListener("input", () => {
  if (currentView() !== "tasks") location.hash = "tasks";
  render();
});
$("statusFilter").addEventListener("change", render);

/* ---------- profile ---------- */
const loadProfile = async () => {
  const r = await call(PROFILE_API);
  if (!r.success) return notify(r.message, "danger");
  const u = r.user;
  $("pName").textContent = u.full_name;
  $("pEmail").textContent = u.email;
  $("pJoined").textContent = `Member since ${new Date(u.created_at.replace(" ", "T")).toLocaleDateString("en-US", { month: "long", day: "numeric", year: "numeric" })}`;
  $("pf_name").value = u.full_name;
  $("pAvatar").textContent = initialsOf(u.full_name);
};

$("nameForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  const r = await call(PROFILE_API, "PUT", { action: "name", full_name: $("pf_name").value });
  notify(r.message, r.success ? "success" : "danger");
  if (r.success) {
    const name = $("pf_name").value.trim();
    $("sideName").textContent = name;
    $("sideAvatar").textContent = initialsOf(name);
    loadProfile();
  }
});

$("passForm").addEventListener("submit", async (e) => {
  e.preventDefault();
  if ($("pw_new").value !== $("pw_confirm").value) return notify("New passwords do not match.", "danger");
  const r = await call(PROFILE_API, "PUT", { action: "password", current: $("pw_current").value, new: $("pw_new").value });
  notify(r.message, r.success ? "success" : "danger");
  if (r.success) e.target.reset();
});

$("setLead").addEventListener("change", (e) => {
  setPref("leadHours", e.target.value);
  checkDueSoon();
  notify("Reminder window saved.");
});

/* ---------- Google Calendar connection ---------- */
const loadGoogle = async () => {
  const r = await call(PROFILE_API);
  const on = r.success && Number(r.user.google) === 1;
  $("gStatus").textContent = on
    ? "Connected. Tasks are added to your Google Calendar automatically."
    : "Not connected.";
  $("gActions").innerHTML = on
    ? `<button type="button" class="btn btn-soft" id="gOff">Disconnect</button>`
    : `<a class="btn btn-primary" href="api/google_connect.php"><i class="bi bi-google me-1"></i>Connect</a>`;
  $("gBanner").innerHTML = on ? "" : `
    <div class="alert alert-warning d-flex justify-content-between align-items-center">
      <span>Connect Google Calendar once, and every task syncs automatically.</span>
      <a class="btn btn-primary btn-sm" href="api/google_connect.php">Connect</a>
    </div>`;
  const off = document.getElementById("gOff");
  if (off) off.addEventListener("click", async () => {
    if (!confirm("Disconnect Google Calendar? Existing events stay in Google.")) return;
    const x = await call("api/google_disconnect.php", "POST", {});
    notify(x.message, x.success ? "success" : "danger");
    loadGoogle();
  });
};
/* ---------- start ---------- */
$("setLead").value = String(getLead());
updateNotifyStatus();
showView(currentView());
loadTasks();
loadGoogle().catch(() => {});
