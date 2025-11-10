// auth.js - small client-side auth helpers (localStorage session)
function getSession() {
  try {
    return JSON.parse(localStorage.getItem("sipak_session") || "null");
  } catch (e) {
    return null;
  }
}

function getRole() {
  const s = getSession();
  return s && s.role ? s.role : null;
}

function logout() {
  localStorage.removeItem("sipak_session");
  // allow pages to call without default navigation
  window.location.href = "page1_login.html";
}

function requireRole(allowedRoles) {
  const role = getRole();
  if (!role) {
    alert("Anda belum login.");
    window.location.href = "page1_login.html";
    return false;
  }
  if (!allowedRoles.includes(role)) {
    alert("Anda tidak memiliki akses ke halaman ini.");
    window.location.href = "page1_login.html";
    return false;
  }
  // attach logout buttons if present
  document.querySelectorAll(".logout-btn").forEach((el) => {
    el.addEventListener("click", function (e) {
      e.preventDefault();
      logout();
    });
  });
  return true;
}

// Auto-run: if a page includes auth.js but doesn't call requireRole,
// still attach logout handlers for convenience.
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".logout-btn").forEach((el) => {
    el.addEventListener("click", function (e) {
      e.preventDefault();
      logout();
    });
  });
});
