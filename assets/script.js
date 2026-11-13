const showAlert = (message, type) => {
  const alertBox = document.getElementById("alert");
  if (!alertBox) return;
  alertBox.innerHTML = "";
  const item = document.createElement("div");
  item.className = `alert alert-${type}`;
  item.textContent = message;
  alertBox.appendChild(item);
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

document.querySelectorAll(".password-toggle").forEach((button) => {
  button.addEventListener("click", () => {
    const input = document.getElementById(button.dataset.passwordTarget);
    if (!input) return;
    const reveal = input.type === "password";
    input.type = reveal ? "text" : "password";
    button.innerHTML = `<i class="bi ${reveal ? "bi-eye-slash" : "bi-eye"}"></i>`;
    button.setAttribute("aria-label", reveal ? "Hide password" : "Show password");
    button.setAttribute("aria-pressed", String(reveal));
  });
});

const registerForm = document.getElementById("registerForm");
const passwordInput = document.getElementById("password");
const passwordChecks = {
  length: (value) => value.length >= 8,
  upper: (value) => /[A-Z]/.test(value),
  lower: (value) => /[a-z]/.test(value),
  number: (value) => /[0-9]/.test(value),
  special: (value) => /[^A-Za-z0-9]/.test(value),
};
const passwordIsValid = (value) => Object.values(passwordChecks).every((check) => check(value));

const updatePasswordRules = () => {
  if (!passwordInput) return;
  const value = passwordInput.value;
  document.querySelectorAll("#passwordRules [data-rule]").forEach((item) => {
    const passed = passwordChecks[item.dataset.rule](value);
    item.classList.toggle("rule-valid", passed);
    item.classList.toggle("rule-invalid", value.length > 0 && !passed);
    item.setAttribute("aria-label", `${item.textContent}: ${passed ? "met" : "not met"}`);
  });
};
passwordInput?.addEventListener("input", updatePasswordRules);

registerForm?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const email = document.getElementById("email").value.trim();
  const password = passwordInput.value;
  if (!/^[^\s@]+@gmail\.com$/i.test(email)) {
    showAlert("Please use a valid Gmail address ending in @gmail.com.", "danger");
    return;
  }
  if (!passwordIsValid(password)) {
    updatePasswordRules();
    showAlert("Please meet all password requirements before registering.", "danger");
    return;
  }
  const result = await sendData("api/register.php", {
    full_name: document.getElementById("full_name").value,
    email,
    password,
  });
  showAlert(result.message, result.success ? "success" : "danger");
  if (result.success) registerForm.reset();
  updatePasswordRules();
});

const loginForm = document.getElementById("loginForm");
loginForm?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const email = document.getElementById("email").value.trim();
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    showAlert("Please enter a valid email address.", "danger");
    return;
  }
  const result = await sendData("api/login.php", {
    email,
    password: document.getElementById("password").value,
  });
  if (result.success) window.location.href = "dashboard.php";
  else showAlert(result.message, "danger");
});
