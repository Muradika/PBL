// ========== GLOBAL VARIABLES ==========
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

// ===================================
// 1. INITIALIZATION & DROPDOWN MENU
// ===================================

document.addEventListener("DOMContentLoaded", function () {
  const dropdownBtn = document.getElementById("profile-dropdown-btn");
  const dropdown = document.querySelector(".dropdown");
  const dateInput = document.getElementById("date");

  // Dropdown Navigation Handler
  if (dropdownBtn && dropdown) {
    // Fungsi untuk membuka/menutup dropdown
    dropdownBtn.addEventListener("click", function (e) {
      e.preventDefault();
      dropdown.classList.toggle("active");
    });

    // Tutup dropdown saat pengguna mengklik di luar area menu
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

  // Initialize button listeners that require functions
  if (form) {
    form.addEventListener("submit", validateFormAndSubmit);
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
        // Tambahan: Mengubah style langsung untuk kompatibilitas yang lebih luas
        dropzoneElement.style.borderColor = "#007bff";
        dropzoneElement.style.backgroundColor = "#f0f8ff";
      },
      false
    );
  });

  ["dragleave", "drop"].forEach((eventName) => {
    dropzoneElement.addEventListener(
      eventName,
      () => {
        dropzoneElement.classList.remove("dragover");
        // Tambahan: Mengubah style langsung untuk kompatibilitas yang lebih luas
        dropzoneElement.style.borderColor = "#ddd";
        dropzoneElement.style.backgroundColor = "";
      },
      false
    );
  });

  // C. Handle dropped files
  dropzoneElement.addEventListener("drop", (e) => handleDrop(e, type), false);

  // D. Trigger file input on dropzone click (only if no file is uploaded yet)
  dropzoneElement.addEventListener("click", (e) => {
    // Cek apakah yang diklik adalah tombol/elemen kontrol
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
  // 1. Validasi File
  if (type === "image") {
    const validTypes = ["image/jpeg", "image/jpg", "image/png"];
    if (!validTypes.includes(file.type)) {
      alert("Please upload only JPG, JPEG, or PNG image files!");
      // Clear input
      imageFileInput.value = "";
      return;
    }
  } else if (type === "document") {
    const validExtensions = [".pdf", ".doc", ".docx", ".xls", ".xlsx"];
    const fileName = file.name.toLowerCase();
    const isValid = validExtensions.some((ext) => fileName.endsWith(ext));

    if (!isValid) {
      alert("Please upload only PDF, DOC, DOCX, XLS, or XLSX files!");
      // Clear input
      documentFileInput.value = "";
      return;
    }
  }

  // 2. Set Global State & UI Update
  uploadedFiles[type] = file;
  const infoElement = type === "image" ? imageFileInfo : documentFileInfo;
  const nameElement = type === "image" ? imageFileName : documentFileName;
  const contentElement =
    type === "image" ? imageDropzoneContent : documentDropzoneContent;

  nameElement.textContent = file.name;
  // contentElement null check
  if (contentElement) contentElement.style.display = "none";
  infoElement.style.display = "flex";

  console.log(`File ${type} uploaded:`, file.name);
}

// ===================================
// 3. UTILITY FUNCTIONS (Preview/Remove/Reset)
// ===================================

window.removeFile = removeFile;
/**
 * Menghapus file yang dipilih dari state dan mereset UI/Input.
 * @param {('image'|'document')} type - Tipe file yang akan dihapus.
 */
function removeFile(type) {
  uploadedFiles[type] = null;
  const infoElement = type === "image" ? imageFileInfo : documentFileInfo;
  const nameElement = type === "image" ? imageFileName : documentFileName;
  const contentElement =
    type === "image" ? imageDropzoneContent : documentDropzoneContent;
  const fileInput = type === "image" ? imageFileInput : documentFileInput;

  nameElement.textContent = "";
  // contentElement null check
  if (contentElement) contentElement.style.display = "flex";
  infoElement.style.display = "none";

  // Reset input file agar form submission tidak mengirim data file lama
  fileInput.value = "";
  console.log(`File ${type} removed`);
}

window.previewImage = previewImage;
function previewImage() {
  const selectedImageFile = uploadedFiles.image;
  if (!selectedImageFile) {
    alert("No image selected!");
    return;
  }

  const modal = document.getElementById("imagePreviewModal");
  const modalImg = document.getElementById("previewImageContent");

  // Create URL from file
  const imageURL = URL.createObjectURL(selectedImageFile);

  modalImg.src = imageURL;
  modal.style.display = "block";

  // Prevent body scroll when modal is open
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
    alert("No document selected!");
    return;
  }

  const fileName = selectedDocumentFile.name.toLowerCase();

  // For PDF files, we can preview directly by opening in a new tab
  if (fileName.endsWith(".pdf")) {
    const fileURL = URL.createObjectURL(selectedDocumentFile);
    window.open(fileURL, "_blank");
  }
  // For Word/Excel files, trigger download
  else if (
    fileName.endsWith(".doc") ||
    fileName.endsWith(".docx") ||
    fileName.endsWith(".xls") ||
    fileName.endsWith(".xlsx")
  ) {
    // Create temporary download link
    const fileURL = URL.createObjectURL(selectedDocumentFile);
    const a = document.createElement("a");
    a.href = fileURL;
    a.download = selectedDocumentFile.name;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);

    alert(
      "Document will be downloaded. You can open it with compatible software to preview."
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
  }
}

// ===================================
// 4. FORM VALIDATION
// ===================================

/**
 * Validasi form sebelum proses submission.
 */
function validateFormAndSubmit(e) {
  const title = document.getElementById("title").value.trim();
  const type = document.getElementById("type").value;
  const date = document.getElementById("date").value;

  if (!title || !type || !date) {
    e.preventDefault();
    alert("Please fill in all required fields (Title, Type, Date)!");
    return false;
  }

  if (!uploadedFiles.image && !uploadedFiles.document) {
    if (
      !confirm(
        "No files uploaded. Continue creating announcement without files?"
      )
    ) {
      e.preventDefault();
      return false;
    }
  }

  // Jika validasi OK, biarkan form disubmit secara normal
  return true;
}
