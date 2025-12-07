// ========== GLOBAL VARIABLES ==========
let uploadedFiles = {
  image: null,
  document: null,
};

// File size limits (dalam bytes)
const MAX_IMAGE_SIZE = 5 * 1024 * 1024; // 5MB
const MAX_DOCUMENT_SIZE = 10 * 1024 * 1024; // 10MB

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

// ===================================
// 1. INITIALIZATION & DROPDOWN MENU
// ===================================

document.addEventListener("DOMContentLoaded", function () {
  const hamburger = document.querySelector(".hamburger");
  const navMenu = document.querySelector(".nav-menu");
  const navLinks = document.querySelectorAll(".nav-link");
  const menuOverlay = document.querySelector(".menu-overlay");
  const dropdownBtn = document.getElementById("profile-dropdown-btn");
  const dropdown = document.querySelector(".dropdown");
  const dateInput = document.getElementById("date");

  // ===== Hamburger Menu Logic =====
  function closeHamburgerMenu() {
    if (hamburger && navMenu && menuOverlay) {
      hamburger.classList.remove("active");
      navMenu.classList.remove("active");
      menuOverlay.classList.remove("active");
      document.body.style.overflow = "";
    }
  }

  if (hamburger && navMenu && menuOverlay) {
    // Toggle menu saat hamburger diklik
    hamburger.addEventListener("click", function (e) {
      e.stopPropagation();
      hamburger.classList.toggle("active");
      navMenu.classList.toggle("active");
      menuOverlay.classList.toggle("active");

      if (navMenu.classList.contains("active")) {
        document.body.style.overflow = "hidden";
      } else {
        document.body.style.overflow = "";
      }
    });

    // Tutup menu saat klik link (kecuali dropdown toggle)
    navLinks.forEach((link) => {
      link.addEventListener("click", (e) => {
        if (!link.classList.contains("dropdown-toggle")) {
          closeHamburgerMenu();
        } else {
          e.stopPropagation();
        }
      });
    });

    // Tutup menu saat klik overlay
    menuOverlay.addEventListener("click", () => {
      closeHamburgerMenu();
    });
  }

  // ===== Profile Dropdown Logic =====
  if (dropdownBtn && dropdown) {
    dropdownBtn.addEventListener("click", function (e) {
      e.preventDefault();
      e.stopPropagation();
      dropdown.classList.toggle("active");
    });

    // Tutup dropdown saat klik di luar
    document.addEventListener("click", function (e) {
      if (!dropdown.contains(e.target) && !dropdownBtn.contains(e.target)) {
        dropdown.classList.remove("active");
      }
    });
  }

  // Set today's date as default
  if (dateInput) {
    dateInput.valueAsDate = new Date();
  }

  // Initialize dropzone listeners
  if (imageDropzone && documentDropzone) {
    initializeDropzone(imageDropzone, "image");
    initializeDropzone(documentDropzone, "document");
  }

  // Initialize form validation
  if (form) {
    form.addEventListener("submit", validateFormAndSubmit);
  }

  // Auto-hide success/error messages after 5 seconds
  const statusMessage = document.querySelector(".status-message");
  if (statusMessage) {
    setTimeout(() => {
      statusMessage.style.transition = "opacity 0.5s ease";
      statusMessage.style.opacity = "0";
      setTimeout(() => statusMessage.remove(), 500);
    }, 5000);
  }
});

// ===================================
// 2. DROPZONE & FILE UPLOAD HANDLERS
// ===================================

function preventDefaults(e) {
  e.preventDefault();
  e.stopPropagation();
}

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

  // D. Trigger file input on dropzone click (only if no file is uploaded yet)
  dropzoneElement.addEventListener("click", (e) => {
    const isControl =
      e.target.closest(".btn-preview") || e.target.closest(".remove-file");

    if (!isControl) {
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

function handleDrop(e, type) {
  const dt = e.dataTransfer;
  const files = dt.files;

  if (files.length > 0) {
    handleFiles(files[0], type);
  }
}

function handleFiles(file, type) {
  // 1. Validasi File Size
  const maxSize = type === "image" ? MAX_IMAGE_SIZE : MAX_DOCUMENT_SIZE;
  if (file.size > maxSize) {
    const maxSizeMB = maxSize / (1024 * 1024);
    showToast(`File size exceeds ${maxSizeMB}MB limit!`, "error");
    // Clear input
    if (type === "image") {
      imageFileInput.value = "";
    } else {
      documentFileInput.value = "";
    }
    return;
  }

  // 2. Validasi File Type
  if (type === "image") {
    const validTypes = ["image/jpeg", "image/jpg", "image/png"];
    if (!validTypes.includes(file.type)) {
      showToast("Please upload only JPG, JPEG, or PNG image files!", "error");
      imageFileInput.value = "";
      return;
    }
  } else if (type === "document") {
    const validExtensions = [".pdf", ".doc", ".docx", ".xls", ".xlsx"];
    const fileName = file.name.toLowerCase();
    const isValid = validExtensions.some((ext) => fileName.endsWith(ext));

    if (!isValid) {
      showToast(
        "Please upload only PDF, DOC, DOCX, XLS, or XLSX files!",
        "error"
      );
      documentFileInput.value = "";
      return;
    }
  }

  // 3. Set Global State & UI Update
  uploadedFiles[type] = file;
  const infoElement = type === "image" ? imageFileInfo : documentFileInfo;
  const nameElement = type === "image" ? imageFileName : documentFileName;
  const contentElement =
    type === "image" ? imageDropzoneContent : documentDropzoneContent;

  nameElement.textContent = file.name;
  if (contentElement) contentElement.style.display = "none";
  infoElement.style.display = "flex";

  console.log(
    `File ${type} uploaded:`,
    file.name,
    `(${formatBytes(file.size)})`
  );
  showToast(
    `✓ ${type === "image" ? "Image" : "Document"} uploaded successfully`,
    "success"
  );
}

// ===================================
// 3. UTILITY FUNCTIONS
// ===================================

window.removeFile = removeFile;
function removeFile(type) {
  uploadedFiles[type] = null;
  const infoElement = type === "image" ? imageFileInfo : documentFileInfo;
  const nameElement = type === "image" ? imageFileName : documentFileName;
  const contentElement =
    type === "image" ? imageDropzoneContent : documentDropzoneContent;
  const fileInput = type === "image" ? imageFileInput : documentFileInput;

  nameElement.textContent = "";
  if (contentElement) contentElement.style.display = "flex";
  infoElement.style.display = "none";

  fileInput.value = "";
  console.log(`File ${type} removed`);
  showToast(`${type === "image" ? "Image" : "Document"} removed`, "info");
}

window.previewImage = previewImage;
function previewImage() {
  const selectedImageFile = uploadedFiles.image;
  if (!selectedImageFile) {
    showToast("No image selected!", "warning");
    return;
  }

  const modal = document.getElementById("imagePreviewModal");
  const modalImg = document.getElementById("previewImageContent");

  const imageURL = URL.createObjectURL(selectedImageFile);

  modalImg.src = imageURL;
  modal.style.display = "block";
  document.body.style.overflow = "hidden";
}

window.closeImagePreview = closeImagePreview;
function closeImagePreview() {
  const modal = document.getElementById("imagePreviewModal");
  if (modal) {
    modal.style.display = "none";
    document.body.style.overflow = "auto";
  }
}

// Close modal when clicking outside the image
window.onclick = function (event) {
  const modal = document.getElementById("imagePreviewModal");
  if (modal && event.target == modal) {
    closeImagePreview();
  }
};

// Close modal with ESC key
document.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
    closeImagePreview();
  }
});

window.previewDocument = previewDocument;
function previewDocument() {
  const selectedDocumentFile = uploadedFiles.document;
  if (!selectedDocumentFile) {
    showToast("No document selected!", "warning");
    return;
  }

  const fileName = selectedDocumentFile.name.toLowerCase();

  // For PDF files, preview in new tab
  if (fileName.endsWith(".pdf")) {
    const fileURL = URL.createObjectURL(selectedDocumentFile);
    window.open(fileURL, "_blank");
  }
  // For Word/Excel files, trigger download
  else {
    const fileURL = URL.createObjectURL(selectedDocumentFile);
    const a = document.createElement("a");
    a.href = fileURL;
    a.download = selectedDocumentFile.name;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    showToast(
      "Document will be downloaded. Open it with compatible software to preview.",
      "info"
    );
  }
}

window.resetForm = resetForm;
function resetForm() {
  if (confirm("Are you sure you want to clear all form data?")) {
    if (form) form.reset();
    removeFile("image");
    removeFile("document");
    console.log("Form reset");
    showToast("Form cleared successfully", "info");
  }
}

// ===================================
// 4. FORM VALIDATION
// ===================================

function validateFormAndSubmit(e) {
  const title = document.getElementById("title").value.trim();
  const type = document.getElementById("type").value;
  const date = document.getElementById("date").value;
  const image_file = document.getElementById("imageFileInput").value;
  const document_file = document.getElementById("documentFileInput").value;

  if (!title || !type || !date || !image_file || !document_file) {
    e.preventDefault();
    showToast(
      "Please fill in all required fields (Title, Type, Date, Image, Document)!",
      "error"
    );

    // Highlight empty fields
    if (!title) document.getElementById("title").focus();
    else if (!type) document.getElementById("type").focus();
    else if (!date) document.getElementById("date").focus();
    else if (!image_file) {
      showToast("Please upload an image!", "error");
    } else if (!document_file) {
      showToast("Please upload a document!", "error");
    }

    return false;
  }

  // Validasi panjang title
  if (title.length > 200) {
    e.preventDefault();
    showToast("Title is too long (maximum 200 characters)!", "error");
    document.getElementById("title").focus();
    return false;
  }

  // Show loading indicator
  const submitBtn = document.querySelector(".btn-submit");
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
  }

  return true;
}

// ===================================
// 5. HELPER FUNCTIONS
// ===================================

function formatBytes(bytes, decimals = 2) {
  if (bytes === 0) return "0 Bytes";
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ["Bytes", "KB", "MB", "GB"];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
}

function showToast(message, type = "info") {
  const existingToast = document.querySelector(".custom-toast");
  if (existingToast) {
    existingToast.remove();
  }

  const toast = document.createElement("div");
  toast.className = `custom-toast toast-${type}`;

  // Add icon based on type
  const icons = {
    success: '<i class="fas fa-check-circle"></i>',
    error: '<i class="fas fa-exclamation-circle"></i>',
    warning: '<i class="fas fa-exclamation-triangle"></i>',
    info: '<i class="fas fa-info-circle"></i>',
  };

  toast.innerHTML = `${icons[type] || icons.info} <span>${message}</span>`;

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
    maxWidth: "350px",
    display: "flex",
    alignItems: "center",
    gap: "10px",
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
    toast.style.animation = "slideOut 0.3s ease-out";
    setTimeout(() => toast.remove(), 300);
  }, 4000);
}

// Add CSS for animations (only once)
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
    
    /* Mobile responsive toast */
    @media (max-width: 768px) {
      .custom-toast {
        top: 10px !important;
        right: 10px !important;
        left: 10px !important;
        max-width: calc(100% - 20px) !important;
        font-size: 13px !important;
        padding: 12px 20px !important;
      }
    }
  `;
  document.head.appendChild(style);
}
