// File upload handling - Gunakan objek untuk menyimpan kedua file
let uploadedFiles = {
  image: null,
  document: null,
};

// --- DOM Element References ---
// Image Dropzone Elements
const imageDropzone = document.getElementById("imageDropzone");
const imageFileInput = document.getElementById("imageFileInput");
const imageFileInfo = document.getElementById("imageFileInfo");
const imageFileName = document.getElementById("imageFileName");
const imageDropzoneContent = imageDropzone
  ? imageDropzone.querySelector(".dropzone-content")
  : null;

// Document Dropzone Elements
const documentDropzone = document.getElementById("documentDropzone");
const documentFileInput = document.getElementById("documentFileInput");
const documentFileInfo = document.getElementById("documentFileInfo");
const documentFileName = document.getElementById("documentFileName");
const documentDropzoneContent = documentDropzone
  ? documentDropzone.querySelector(".dropzone-content")
  : null;

// Form Element
const form = document.getElementById("announcementForm");

// --- 1. Dropdown Navigation Handler ---
document.addEventListener("DOMContentLoaded", function () {
  const dropdownBtn = document.getElementById("profile-dropdown-btn");
  const dropdown = document.querySelector(".dropdown");

  if (dropdownBtn && dropdown) {
    // Fungsi untuk membuka/menutup dropdown
    dropdownBtn.addEventListener("click", function (e) {
      e.preventDefault();
      dropdown.classList.toggle("active");
    });

    // Opsional: Tutup dropdown saat pengguna mengklik di luar area menu
    document.addEventListener("click", function (e) {
      if (!dropdown.contains(e.target) && !dropdownBtn.contains(e.target)) {
        dropdown.classList.remove("active");
      }
    });
  }

  // Set today's date as default
  const dateInput = document.getElementById("date");
  if (dateInput) {
    dateInput.valueAsDate = new Date();
  }

  // Initialize dropzone listeners if elements exist
  if (imageDropzone && documentDropzone) {
    initializeDropzone(imageDropzone, "image");
    initializeDropzone(documentDropzone, "document");
  }
});

// --- 2. Dropzone Logic Helper ---
function initializeDropzone(dropzoneElement, type) {
  const fileInput = type === "image" ? imageFileInput : documentFileInput;

  // A. Prevent default drag behaviors
  ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
    dropzoneElement.addEventListener(eventName, preventDefaults, false);
  });

  // B. Highlight drop zone when item is dragged over it
  ["dragenter", "dragover"].forEach((eventName) => {
    dropzoneElement.addEventListener(
      eventName,
      () => {
        dropzoneElement.classList.add("dragover");
      },
      false
    );
  });

  ["dragleave", "drop"].forEach((eventName) => {
    dropzoneElement.addEventListener(
      eventName,
      () => {
        dropzoneElement.classList.remove("dragover");
      },
      false
    );
  });

  // C. Handle dropped files
  dropzoneElement.addEventListener("drop", (e) => handleDrop(e, type), false);

  // D. Trigger file input on dropzone click
  dropzoneElement.addEventListener("click", () => {
    // Hanya buka dialog jika belum ada file yang diupload
    if (!uploadedFiles[type]) {
      fileInput.click();
    }
  });

  // E. Handle file input change
  fileInput.addEventListener("change", (e) => {
    if (e.target.files.length > 0) {
      handleFiles(e.target.files[0], type);
    }
  });
}

function preventDefaults(e) {
  e.preventDefault();
  e.stopPropagation();
}

function handleDrop(e, type) {
  const dt = e.dataTransfer;
  const files = dt.files;

  if (files.length > 0) {
    handleFiles(files[0], type);
  }
}

function handleFiles(file, type) {
  uploadedFiles[type] = file;
  const infoElement = type === "image" ? imageFileInfo : documentFileInfo;
  const nameElement = type === "image" ? imageFileName : documentFileName;
  const contentElement =
    type === "image" ? imageDropzoneContent : documentDropzoneContent;
  const fileInput = type === "image" ? imageFileInput : documentFileInput;

  nameElement.textContent = file.name;
  contentElement.style.display = "none";
  infoElement.style.display = "flex";

  // Mengganti file di input file tersembunyi dengan file yang di-drop.
  // NOTE: Secara teknis, ini tidak bisa dilakukan secara langsung di browser karena alasan keamanan.
  // SOLUSI: Kita akan mengandalkan nama 'name' yang sudah kita set di HTML input,
  // dan saat submission (langkah 3), kita pastikan form method POST + enctype multipart/form-data
  // yang menangani file dari input aslinya.

  console.log(`File ${type} uploaded:`, file.name);
}

// --- 3. Form Submission Handler (Dihapus/Diganti) ---
// Kita tidak lagi memproses submission form di JavaScript (fetch/Ajax)
// karena kita mengandalkan POST method ke PHP (profiledosen.php)
// untuk upload file, yang lebih mudah untuk implementasi ini.

// Form listener diubah hanya untuk mencegah submission jika ada error JS (tidak perlu lagi)
// Logika utama submission sudah ditangani di profiledosen.php

// form.addEventListener('submit', (e) => {
//     // Karena kita menggunakan PHP native POST, kita tidak perlu e.preventDefault() di sini
//     // kecuali ada validasi JavaScript yang gagal.
// });

// --- 4. Fungsi Utility ---
function removeFile(type) {
  uploadedFiles[type] = null;
  const infoElement = type === "image" ? imageFileInfo : documentFileInfo;
  const nameElement = type === "image" ? imageFileName : documentFileName;
  const contentElement =
    type === "image" ? imageDropzoneContent : documentDropzoneContent;
  const fileInput = type === "image" ? imageFileInput : documentFileInput;

  nameElement.textContent = "";
  contentElement.style.display = "block";
  infoElement.style.display = "none";

  // Reset input file agar PHP tidak menerima data file ini
  fileInput.value = "";
  console.log(`File ${type} removed`);
}

function resetForm() {
  form.reset();
  removeFile("image");
  removeFile("document");
  console.log("Form reset");
}

// Fungsi `toggleForm` dan `handleLogout` tidak digunakan dalam context ini
// karena strukturnya sudah statis dan ada di HTML.
