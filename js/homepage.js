// File: ../js/homepage.js

document.addEventListener("DOMContentLoaded", function () {
  // Ambil elemen setelah DOM siap
  const filterButton = document.getElementById("filterButton");
  const filterOptionsContainer = document.getElementById("filterOptions"); // Ini adalah elemen div yang berisi pilihan filter
  const filterContainer = document.querySelector(".filter-dropdown-container");

  // Toggle dropdown (pasang kelas `show` di container agar CSS menampilkannya)
  if (filterButton && filterContainer) {
    filterButton.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation(); // Mencegah klik menyebar ke dokumen
      filterContainer.classList.toggle("show");
    });
  }

  // Pastikan klik pada opsi filter tidak tertutup sebelum mengeksekusi navigasi
  if (filterOptionsContainer) {
    // Untuk tiap opsi, pasang listener yang langsung menavigasi ke href.
    const options = filterOptionsContainer.querySelectorAll(".filter-option");
    options.forEach((opt) => {
      opt.addEventListener("click", function (e) {
        // Jangan biarkan event bubble ke document yang bisa menutup dropdown terlalu cepat
        e.stopPropagation();
        // Navigasi manual untuk memastikan bekerja pada semua browser/device
        const href = this.getAttribute("href");
        if (href && href !== "#") {
          window.location.href = href;
        }
      });
    });
  }

  // Fallback: jika CSS tidak menampilkan dropdown, set style langsung untuk memastikan terlihat
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

  // Pasang observer pada classList perubahan untuk menerapkan fallback secara otomatis
  if (filterContainer && filterOptionsContainer && window.MutationObserver) {
    const obs = new MutationObserver(() => applyFallbackVisibility());
    obs.observe(filterContainer, {
      attributes: true,
      attributeFilter: ["class"],
    });
  }

  // Juga panggil sekali untuk inisialisasi
  applyFallbackVisibility();

  // Menutup filter saat klik di luar
  document.addEventListener("click", (e) => {
    if (filterContainer && !filterContainer.contains(e.target)) {
      filterContainer.classList.remove("show");
    }
  });

  // Logika Search dan Filter LOKAL (client-side) dihilangkan
  // karena sudah ditangani oleh PHP/Server-Side.
});
