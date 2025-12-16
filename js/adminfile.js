document.addEventListener("DOMContentLoaded", function () {
  // ========== HAMBURGER MENU (MOBILE) ==========
  const hamburger = document.querySelector(".hamburger");
  const navMenuMobile = document.querySelector(".nav-menu-mobile");
  const navLinksMobile = document.querySelectorAll(".nav-link-mobile");

  // Create overlay
  const menuOverlay = document.createElement("div");
  menuOverlay.className = "menu-overlay";
  document.body.appendChild(menuOverlay);

  if (hamburger && navMenuMobile) {
    // Toggle mobile menu
    hamburger.addEventListener("click", function (e) {
      e.stopPropagation();
      hamburger.classList.toggle("active");
      navMenuMobile.classList.toggle("active");
      menuOverlay.classList.toggle("active");

      // Prevent body scroll when menu is open
      if (navMenuMobile.classList.contains("active")) {
        document.body.style.overflow = "hidden";
      } else {
        document.body.style.overflow = "";
      }
    });

    // Close menu when clicking nav links (mobile)
    navLinksMobile.forEach((link) => {
      link.addEventListener("click", function () {
        hamburger.classList.remove("active");
        navMenuMobile.classList.remove("active");
        menuOverlay.classList.remove("active");
        document.body.style.overflow = "";
      });
    });

    // Close when clicking overlay
    menuOverlay.addEventListener("click", function () {
      hamburger.classList.remove("active");
      navMenuMobile.classList.remove("active");
      menuOverlay.classList.remove("active");
      document.body.style.overflow = "";
    });
  }

  // ========== DROPDOWN TOGGLE (DESKTOP) ==========
  const dropdownBtn = document.getElementById("profile-dropdown-btn");
  const dropdown = document.querySelector(".dropdown");

  if (dropdownBtn) {
    dropdownBtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropdown.classList.toggle("active");
    });
  }

  document.addEventListener("click", function (e) {
    if (dropdown && !e.target.closest(".dropdown")) {
      dropdown.classList.remove("active");
    }
  });

  // ========== FILTER DROPDOWN ==========
  const filterButton = document.getElementById("filterButton");
  const filterContainer = document.querySelector(".filter-dropdown-container");

  if (filterButton) {
    filterButton.addEventListener("click", function (e) {
      e.stopPropagation();
      filterContainer.classList.toggle("show");
    });
  }

  document.addEventListener("click", function (e) {
    if (filterContainer && !e.target.closest(".filter-dropdown-container")) {
      filterContainer.classList.remove("show");
    }
  });

  // ========== AUTO-HIDE ALERTS ==========
  setTimeout(function () {
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach((alert) => {
      alert.style.opacity = "0";
      setTimeout(() => alert.remove(), 300);
    });
  }, 5000);
});

// ========== EDIT MODAL FUNCTIONS ==========
function openEditModal(file) {
  document.getElementById("edit_id").value = file.id;
  document.getElementById("edit_title").value = file.title;
  document.getElementById("edit_type").value = file.type;
  document.getElementById("edit_date").value = file.date;
  document.getElementById("old_image").value = file.image_path || "";
  document.getElementById("old_document").value = file.document_path || "";

  document.getElementById("editModal").style.display = "block";
  document.body.style.overflow = "hidden";
}

function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
  document.body.style.overflow = "auto";
}

// ========== CLOSE MODAL WHEN CLICKING OUTSIDE ==========
window.onclick = function (event) {
  const editModal = document.getElementById("editModal");

  if (event.target == editModal) {
    closeEditModal();
  }
};
