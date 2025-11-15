// script_lp.js - Login page behavior
document.addEventListener("DOMContentLoaded", function () {
  const form = document.getElementById("loginForm");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value.trim();
    const role = document.getElementById("role").value;

    if (!email || !password || !role) {
      alert("Lengkapi email, password, dan pilih user.");
      return;
    }

    // Dummy authentication: in real app call backend
    const session = { email: email, role: role, createdAt: Date.now() };
    localStorage.setItem("sipak_session", JSON.stringify(session));

    // Redirect to verification page which will forward to the correct landing
<<<<<<< HEAD
    window.location.href = "page2_double_verification.html";
=======
    window.location.href = "page3_home.html";
>>>>>>> 8b90af10607c5b4d583541224615ac9fc8e80667
  });
});
