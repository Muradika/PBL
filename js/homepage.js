// File: ../js/homepage.js

// --- 1. Ambil elemen penting untuk UI (Dropdown) ---
const filterButton = document.getElementById("filterButton");
const filterOptionsContainer = document.getElementById("filterOptions"); // Ini adalah elemen div yang berisi pilihan filter
const filterContainer = document.querySelector(".filter-dropdown-container");

document.addEventListener("DOMContentLoaded", function () {
  // 2. Logika Dropdown Filter (JS) tetap dibutuhkan untuk Toggle Menu
  if (filterButton && filterOptionsContainer) {
    filterButton.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation(); // Mencegah klik menyebar ke dokumen
      filterOptionsContainer.classList.toggle("show");
    });
  }

  // 3. Menutup filter saat klik di luar
  document.addEventListener("click", (e) => {
    // Cek jika yang diklik bukan bagian dari container filter atau tombol filter
    if (
      filterContainer &&
      !filterContainer.contains(e.target) &&
      !filterButton.contains(e.target)
    ) {
      filterOptionsContainer.classList.remove("show");
    }
  });

  // Logika Search dan Filter LOKAL (client-side) dihilangkan
  // karena sudah ditangani oleh PHP/Server-Side.
});
