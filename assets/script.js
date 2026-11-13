const showAlert = (message, type) => {
  document.getElementById("alert").innerHTML =
    `<div class="alert alert-${type}">${message}</div>`;
};

const sendData = async (url, payload) => {
  try {
    const res = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    return await res.json();
  } catch {
    return { success: false, message: "Unable to connect to the server." };
  }
};

const GMAIL = /^[a-z0-9._%+-]+@gmail\.com$/i;
const pwChecks = (p) => ({ len: p.length >= 8, upper: /[A-Z]/.test(p), lower: /[a-z]/.test(p), num: /\d/.test(p) });

/* Show / Hide password */
document.querySelectorAll(".pw-toggle").forEach((btn) =>
  btn.addEventListener("click", () => {
    const input = document.getElementById(btn.dataset.target);
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    btn.setAttribute("aria-label", show ? "Hide password" : "Show password");
  })
);

/* Registration */
const registerForm = document.getElementById("registerForm");

if (registerForm) {
  const emailEl = document.getElementById("email");
  const passEl = document.getElementById("password");
  const hint = document.getElementById("emailHint");

  emailEl.addEventListener("input", () => {
    const v = emailEl.value.trim();
    const ok = GMAIL.test(v);
    hint.className = "field-hint mb-3 " + (!v ? "" : ok ? "is-ok" : "is-bad");
    hint.textContent = !v ? "Only Gmail addresses (@gmail.com) are accepted."
      : ok ? "Looks good." : "Use a Gmail address, e.g. name@gmail.com";
  });

  passEl.addEventListener("input", () => {
    const c = pwChecks(passEl.value);
    document.querySelectorAll("#pwRules li").forEach((li) => li.classList.toggle("is-ok", c[li.dataset.rule]));
  });

  registerForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const email = emailEl.value.trim();
    const c = pwChecks(passEl.value);
    if (!GMAIL.test(email)) return showAlert("Please use a Gmail address (name@gmail.com).", "danger");
    if (!Object.values(c).every(Boolean)) return showAlert("Password needs 8+ characters with an uppercase letter, a lowercase letter and a number.", "danger");
    const result = await sendData("api/register.php", {
      full_name: document.getElementById("full_name").value,
      email,
      password: passEl.value,
    });
    showAlert(result.message, result.success ? "success" : "danger");
    if (result.success) {
      registerForm.reset();
      document.querySelectorAll("#pwRules li").forEach((li) => li.classList.remove("is-ok"));
    }
  });
}

/* Login */
const loginForm = document.getElementById("loginForm");

loginForm?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value;
  if (!email || !password) return showAlert("Enter your email and password.", "danger");
  const result = await sendData("api/login.php", { email, password });
  if (result.success) window.location.href = "dashboard.php";
  else showAlert(result.message, "danger");
});
