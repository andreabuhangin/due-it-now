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

const registerForm = document.getElementById("registerForm");

registerForm?.addEventListener("submit", async (e) => {
  e.preventDefault();
  const result = await sendData("api/register.php", {
    full_name: document.getElementById("full_name").value,
    email: document.getElementById("email").value,
    password: document.getElementById("password").value,
  });
  showAlert(result.message, result.success ? "success" : "danger");
  if (result.success) registerForm.reset();
});