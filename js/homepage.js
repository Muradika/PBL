document.addEventListener("DOMContentLoaded", function () {
  // ========== HAMBURGER MENU ==========
  const hamburger = document.querySelector(".hamburger");
  const navMenu = document.querySelector(".nav-menu");
  const navLinks = document.querySelectorAll(".nav-link");

  // Create overlay element
  const menuOverlay = document.createElement("div");
  menuOverlay.className = "menu-overlay";
  document.body.appendChild(menuOverlay);

  if (hamburger && navMenu) {
    // Toggle menu
    hamburger.addEventListener("click", function (e) {
      e.stopPropagation();
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
      menuOverlay.classList.toggle("active");

      // Prevent body scroll when menu is open
      if (navMenu.classList.contains("active")) {
        document.body.style.overflow = "hidden";
      } else {
        document.body.style.overflow = "";
      }
    });

    // Close menu when clicking nav links
    navLinks.forEach((link) => {
      link.addEventListener("click", () => {
        hamburger.classList.remove("active");
        navMenu.classList.remove("active");
        menuOverlay.classList.remove("active");
        document.body.style.overflow = "";
      });
    });

    // Close menu when clicking overlay
    menuOverlay.addEventListener("click", () => {
      hamburger.classList.remove("active");
      navMenu.classList.remove("active");
      menuOverlay.classList.remove("active");
      document.body.style.overflow = "";
    });
  }

  // ========== FILTER DROPDOWN ==========
  const filterButton = document.getElementById("filterButton");
  const filterOptionsContainer = document.getElementById("filterOptions");
  const filterContainer = document.querySelector(".filter-dropdown-container");

  // Toggle dropdown filter
  if (filterButton && filterContainer) {
    filterButton.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      filterContainer.classList.toggle("show");
    });
  }

  // Handle filter options click
  if (filterOptionsContainer) {
    const options = filterOptionsContainer.querySelectorAll(".filter-option");
    options.forEach((opt) => {
      opt.addEventListener("click", function (e) {
        e.stopPropagation();
        const href = this.getAttribute("href");
        if (href && href !== "#") {
          window.location.href = href;
        }
      });
    });
  }

  // Fallback visibility
  function applyFallbackVisibility() {
    if (!filterContainer || !filterOptionsContainer) return;
    if (filterContainer.classList.contains("show")) {
      filterOptionsContainer.style.display = "block";
      filterOptionsContainer.style.opacity = "1";
      filterOptionsContainer.style.transform = "translateY(0)";
      filterOptionsContainer.style.pointerEvents = "auto";
    } else {
      filterOptionsContainer.style.display = "";
      filterOptionsContainer.style.opacity = "";
      filterOptionsContainer.style.transform = "";
      filterOptionsContainer.style.pointerEvents = "";
    }
  }

  if (filterContainer && filterOptionsContainer && window.MutationObserver) {
    const obs = new MutationObserver(() => applyFallbackVisibility());
    obs.observe(filterContainer, {
      attributes: true,
      attributeFilter: ["class"],
    });
  }

  applyFallbackVisibility();

  // Close filter when clicking outside
  document.addEventListener("click", (e) => {
    if (filterContainer && !filterContainer.contains(e.target)) {
      filterContainer.classList.remove("show");
    }
  });

  // ========== DOWNLOAD BUTTON ==========
  document.querySelectorAll(".download-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const docUrl = this.getAttribute("data-doc");
      if (docUrl && docUrl !== "#") {
        // Trigger download
        const link = document.createElement("a");
        link.href = docUrl;
        link.download = "";
        link.target = "_blank";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Visual feedback
        this.style.background = "#28a745";
        setTimeout(() => {
          this.style.background = "";
        }, 1000);
      }
    });
  });

  // ========== BOOKMARK/FAVORITE BUTTON ==========
  document.querySelectorAll(".bookmark-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const announcementId = this.getAttribute("data-id");
      const isActive = this.classList.contains("active");
      const action = isActive ? "remove" : "add";

      // Send AJAX request
      fetch("../database/favorites_handler.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `action=${action}&announcement_id=${announcementId}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Toggle active class
            this.classList.toggle("active");

            // Update SVG fill
            const svg = this.querySelector("svg path");
            if (this.classList.contains("active")) {
              svg.setAttribute("fill", "white");
              this.setAttribute("title", "Hapus dari favorit");

              // Show success message
              showToast("✓ Ditambahkan ke favorit!", "success");
            } else {
              svg.setAttribute("fill", "none");
              this.setAttribute("title", "Tambahkan ke favorit");

              // Show success message
              showToast("✗ Dihapus dari favorit", "info");
            }
          } else {
            if (data.message === "User not logged in") {
              showToast("⚠ Silakan login terlebih dahulu", "warning");
              // Redirect to login after 1 second
              setTimeout(() => {
                window.location.href = "loginpage.php";
              }, 1000);
            } else {
              showToast("✗ Gagal: " + data.message, "error");
            }
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("✗ Kesalahan koneksi", "error");
        });
    });
  });

  // ========== TOAST NOTIFICATION ==========
  function showToast(message, type = "info") {
    // Remove existing toast
    const existingToast = document.querySelector(".custom-toast");
    if (existingToast) {
      existingToast.remove();
    }

    // Create toast element
    const toast = document.createElement("div");
    toast.className = `custom-toast toast-${type}`;
    toast.textContent = message;

    // Style toast
    Object.assign(toast.style, {
      position: "fixed",
      top: "20px",
      right: "20px",
      padding: "15px 25px",
      borderRadius: "8px",
      color: "white",
      fontWeight: "bold",
      fontSize: "14px",
      zIndex: "9999",
      boxShadow: "0 4px 12px rgba(0,0,0,0.3)",
      animation: "slideIn 0.3s ease-out",
      maxWidth: "300px",
    });

    // Set background based on type
    const colors = {
      success: "#28a745",
      error: "#dc3545",
      warning: "#ffc107",
      info: "#17a2b8",
    };
    toast.style.background = colors[type] || colors.info;

    // Add to body
    document.body.appendChild(toast);

    // Remove after 3 seconds
    setTimeout(() => {
      toast.style.animation = "slideOut 0.3s ease-out";
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // Add animation keyframes
  if (!document.querySelector("#toast-animations")) {
    const style = document.createElement("style");
    style.id = "toast-animations";
    style.textContent = `
      @keyframes slideIn {
        from {
          transform: translateX(400px);
          opacity: 0;
        }
        to {
          transform: translateX(0);
          opacity: 1;
        }
      }
      @keyframes slideOut {
        from {
          transform: translateX(0);
          opacity: 1;
        }
        to {
          transform: translateX(400px);
          opacity: 0;
        }
      }
    `;
    document.head.appendChild(style);
  }
});
