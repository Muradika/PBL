document.addEventListener("DOMContentLoaded", function () {
  // ===================================
  // I. DOM Element Selectors
  // ===================================
  const hamburger = document.querySelector(".hamburger");
  const navMenu = document.querySelector(".nav-menu");
  const navLinks = document.querySelectorAll(".nav-link");
  const dropdownBtn = document.getElementById("profile-dropdown-btn");
  const dropdown = document.querySelector(".dropdown");
  const filterButton = document.getElementById("filterButton");
  const filterOptionsContainer = document.getElementById("filterOptions");
  const filterContainer = document.querySelector(".filter-dropdown-container");

  // ===================================
  // II. Global Functions
  // ===================================

  // Function untuk menampilkan Toast Notification kustom
  function showToast(message, type = "info") {
    const existingToast = document.querySelector(".custom-toast");
    if (existingToast) {
      existingToast.remove();
    }

    const toast = document.createElement("div");
    toast.className = `custom-toast toast-${type}`;
    toast.textContent = message;

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

    const colors = {
      success: "#28a745",
      error: "#dc3545",
      warning: "#ffc107",
      info: "#17a2b8",
    };
    toast.style.background = colors[type] || colors.info;

    document.body.appendChild(toast);

    setTimeout(() => {
      // Menggunakan class untuk animasi keluar (asumsi CSS terkait sudah ada)
      toast.style.animation = "slideOut 0.3s ease-out";
      setTimeout(() => toast.remove(), 300);
    }, 3000);
  }

  // Function untuk menutup elemen Hamburguer dan Overlay
  function closeHamburgerMenu() {
    hamburger.classList.remove("active");
    navMenu.classList.remove("active");
    menuOverlay.classList.remove("active");
    document.body.style.overflow = "";
  }

  // ===================================
  // III. Hamburger Menu Logic
  // ===================================

  // Create and append overlay
  const menuOverlay = document.createElement("div");
  menuOverlay.className = "menu-overlay";
  document.body.appendChild(menuOverlay);

  if (hamburger && navMenu) {
    hamburger.addEventListener("click", function (e) {
      e.stopPropagation();
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
      menuOverlay.classList.toggle("active");

      if (navMenu.classList.contains("active")) {
        document.body.style.overflow = "hidden"; // Mencegah scroll saat menu terbuka
      } else {
        document.body.style.overflow = "";
      }
    });

    // PERBAIKAN: Hanya tutup Hamburger jika link yang diklik BUKAN Profile (dropdown-toggle)
    navLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        // Jika link tersebut BUKAN tombol dropdown (Profile)
        if (!link.classList.contains("dropdown-toggle")) {
          closeHamburgerMenu();
        } else {
          // Hentikan propagasi agar klik tidak memicu penutupan dari luar dropdown
          e.stopPropagation();
        }
      });
    });

    menuOverlay.addEventListener("click", () => {
      closeHamburgerMenu(); // Tutup menu saat klik overlay
    });
  }

  // ===================================
  // IV. Dropdown Logic (Profile & Filter)
  // ===================================

  // --- Profile Dropdown ---
  if (dropdownBtn && dropdown) {
    dropdownBtn.addEventListener("click", function (e) {
      e.preventDefault();
      dropdown.classList.toggle("active");
    });

    document.addEventListener("click", function (e) {
      // Tutup dropdown jika klik di luar area dropdown DAN tombol dropdown
      if (!dropdown.contains(e.target) && !dropdownBtn.contains(e.target)) {
        dropdown.classList.remove("active");
      }
    });
  }

  // --- Filter Dropdown ---
  if (filterButton && filterContainer) {
    filterButton.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      filterContainer.classList.toggle("show");
    });
  }

  // Mengarahkan ke link saat opsi filter diklik
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

  // Fallback Visibility (Original Code) - Memastikan transisi berjalan
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
  applyFallbackVisibility(); // Jalankan sekali saat load

  // Tutup filter saat klik di luar
  document.addEventListener("click", (e) => {
    if (filterContainer && !filterContainer.contains(e.target)) {
      filterContainer.classList.remove("show");
    }
  });

  // ===================================
  // V. Interaksi Konten (Download & Favorites)
  // ===================================

  // --- Download Button ---
  document.querySelectorAll(".download-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const docUrl = this.getAttribute("data-doc");
      if (docUrl && docUrl !== "#") {
        const link = document.createElement("a");
        link.href = docUrl;
        link.download = "";
        link.target = "_blank"; // Buka di tab baru (opsional)
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Feedback visual
        this.style.background = "#28a745";
        setTimeout(() => {
          this.style.background = ""; // Kembalikan ke style semula
        }, 1000);
      }
    });
  });

  // --- Remove From Favorites ---
  document.querySelectorAll(".remove-btn").forEach((btn) => {
    btn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const announcementId = this.getAttribute("data-id");
      const card = this.closest(".announcement-card");

      if (!confirm("Remove this announcement from favorites?")) {
        return;
      }

      fetch("../database/favorites_handler.php", {
        method: "POST",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded",
        },
        body: `action=remove&announcement_id=${announcementId}`,
      })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            // Animasi penghapusan
            card.style.transition = "all 0.3s ease-out";
            card.style.opacity = "0";
            card.style.transform = "scale(0.8)";

            setTimeout(() => {
              card.remove();

              // Periksa jika tidak ada kartu tersisa
              const remainingCards =
                document.querySelectorAll(".announcement-card");
              if (remainingCards.length === 0) {
                location.reload(); // Muat ulang halaman jika semua dihapus
              }
            }, 300);

            showToast("✓ Removed from favorites", "success");
          } else {
            showToast("✗ Failed to remove", "error");
          }
        })
        .catch((error) => {
          console.error("Error:", error);
          showToast("✗ Connection error", "error");
        });
    });
  });

  // ===================================
  // VI. Toast Notification Styles (CSS Injection)
  // ===================================

  // Tambahkan CSS untuk animasi Toast (sekali saja)
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
