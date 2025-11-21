const rowsEl = document.getElementById("rows");
const searchInput = document.getElementById("searchInput");
const modalBackdrop = document.getElementById("modalBackdrop");
const modalTitle = document.getElementById("modalTitle");

const fileTitleInput = document.getElementById("fileTitleInput");
const fileNameInput = document.getElementById("fileNameInput");
const uploaderInput = document.getElementById("uploaderInput");
const fileTypeInput = document.getElementById("fileTypeInput");
const uploadDateInput = document.getElementById("uploadDateInput");
const fileSvg = document.getElementById("fileSvg");

const btnSave = document.getElementById("btnSave");
const btnCancel = document.getElementById("btnCancel");
const btnAdd = document.getElementById("btnAdd");

let editingId = null;

// SVG paths per type
const fileIcons = {
  Akademik:
    "M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z",
  "Jadwal Ujian":
    "M7 21h10a2 2 0 002-2V9a2 2 0 00-.586-1.414l-4.414-4.414A2 2 0 0012.172 3H7a2 2 0 00-2 2v14a2 2 0 002 2z",
  Kemahasiswaan:
    "M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-.586-1.414l-4.414-4.414A2 2 0 0012.172 3H7a2 2 0 00-2 2zm12 5h-2m2 4h-2m-2-4H9m0 4h2",
  Beasiswa:
    "M4 16l4.586-4.586a2 2 0 012.828 0L14 14l5-5m-5 5l1.414 1.414a2 2 0 002.828 0L20 10m-5 9V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2z",
  Wisuda:
    "M12 2v6h6V2h-6zm0 8h6v6h-6v-6zm0 8h6v6h-6v-6zM6 2v6H2V2h4zm0 8H2v6h4v-6zm0 8H2v6h4v-6z",
};

function getFileIconPath(type) {
  return fileIcons[type] || fileIcons.PDF;
}

// Dropdown nav behavior (accessible)
document.addEventListener("DOMContentLoaded", function () {
  const dropdownBtn = document.getElementById("profile-dropdown-btn");
  const dropdown = document.querySelector(".dropdown");

  if (dropdownBtn && dropdown) {
    dropdownBtn.addEventListener("click", function (e) {
      e.preventDefault();
      dropdown.classList.toggle("active");
      const expanded = dropdown.classList.contains("active");
      dropdownBtn.setAttribute("aria-expanded", expanded ? "true" : "false");
    });

    document.addEventListener("click", function (e) {
      if (!dropdown.contains(e.target) && !dropdownBtn.contains(e.target)) {
        dropdown.classList.remove("active");
        dropdownBtn.setAttribute("aria-expanded", "false");
      }
    });
  }
});

// Sample data
let files = [
  {
    id: genId(),
    title: "Jadwal Ujian Semester Ganjil 2025",
    fileName: "Jadwal_Ujian_Ganjil.pdf",
    uploader: "Hamim Thohari",
    type: "Jadwal Ujian",
    date: "2025-01-10",
  },
  {
    id: genId(),
    title: "Daftar Mata Kuliah Pilihan TA 2025/2026",
    fileName: "MK_Pilihan_2026.xlsx",
    uploader: "Prabo Sugiono",
    type: "Akademik",
    date: "2024-12-01",
  },
  {
    id: genId(),
    title: "Panduan Penyusunan Laporan Akhir",
    fileName: "Laporan_Akhir_V1.docx",
    uploader: "Teddy Indra",
    type: "Akademik",
    date: "2025-02-28",
  },
  {
    id: genId(),
    title: "Peraturan Baru Absensi Dosen",
    fileName: "Peraturan_Absensi.zip",
    uploader: "Yanto Basna",
    type: "Kemahasiswaan",
    date: "2025-01-20",
  },
];

function genId() {
  return "f_" + Math.random().toString(36).slice(2, 9);
}

function renderRows(filter = "") {
  rowsEl.innerHTML = "";
  const q = filter.trim().toLowerCase();
  const list = files.filter((f) => {
    if (!q) return true;
    return [f.title, f.fileName, f.uploader, f.type, f.date]
      .join(" ")
      .toLowerCase()
      .includes(q);
  });

  if (list.length === 0) {
    const empty = document.createElement("div");
    empty.className = "row";
    empty.style.gridTemplateColumns = "1fr";
    empty.style.justifyContent = "center";
    empty.textContent = "No files found.";
    rowsEl.appendChild(empty);
    return;
  }

  for (const f of list) {
    const r = document.createElement("div");
    r.className = "row";
    r.dataset.id = f.id;

    const fileIcon = document.createElement("div");
    fileIcon.className = "file-icon";
    fileIcon.innerHTML = `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${getFileIconPath(
      f.type
    )}"/></svg>`;

    const fileTitle = document.createElement("div");
    fileTitle.className = "file-title";
    fileTitle.innerHTML = `<strong>${escapeHtml(
      f.title
    )}</strong><br><span class="small" style="font-weight:400;color:#7a8a9e;">${escapeHtml(
      f.fileName
    )}</span>`;

    const uploader = document.createElement("div");
    uploader.className = "uploader-name";
    uploader.textContent = f.uploader;

    const uploadDate = document.createElement("div");
    uploadDate.className = "upload-date";
    uploadDate.textContent = f.date;

    const actions = document.createElement("div");
    actions.className = "actions-cell";

    const btnRemove = document.createElement("button");
    btnRemove.className = "btn-remove";
    btnRemove.textContent = "Remove";
    btnRemove.addEventListener("click", () => removeFile(f.id));

    const btnEdit = document.createElement("button");
    btnEdit.className = "btn-edit";
    btnEdit.textContent = "Edit";
    btnEdit.addEventListener("click", () => openEditModal(f.id));

    actions.appendChild(btnRemove);
    actions.appendChild(btnEdit);

    r.appendChild(fileIcon);
    r.appendChild(fileTitle);
    r.appendChild(uploader);
    r.appendChild(uploadDate);
    r.appendChild(actions);

    rowsEl.appendChild(r);
  }
}

function escapeHtml(str) {
  if (!str) return "";
  return String(str).replace(/[&<>"']/g, (s) => {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[s];
  });
}

function updateModalIcon(type) {
  fileSvg.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${getFileIconPath(
    type
  )}"/>`;
}

function openEditModal(id) {
  editingId = id;
  const f = files.find((x) => x.id === id);
  if (!f) return;
  modalTitle.textContent = "Edit File";
  fileTitleInput.value = f.title;
  fileNameInput.value = f.fileName;
  uploaderInput.value = f.uploader;
  fileTypeInput.value = f.type;
  uploadDateInput.value = f.date;
  updateModalIcon(f.type);
  showModal();
}

function openAddModal() {
  editingId = null;
  modalTitle.textContent = "Add New File";
  fileTitleInput.value = "";
  fileNameInput.value = "";
  uploaderInput.value = "";
  fileTypeInput.value = "PDF";
  uploadDateInput.value = new Date().toISOString().slice(0, 10);
  updateModalIcon("PDF");
  showModal();
}

function showModal() {
  modalBackdrop.style.display = "flex";
  modalBackdrop.setAttribute("aria-hidden", "false");
  fileTitleInput.focus();
}

function hideModal() {
  modalBackdrop.style.display = "none";
  modalBackdrop.setAttribute("aria-hidden", "true");
}

btnAdd.addEventListener("click", openAddModal);
btnCancel.addEventListener("click", hideModal);

modalBackdrop.addEventListener("click", (e) => {
  if (e.target === modalBackdrop) hideModal();
});

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") hideModal();
});

fileTypeInput.addEventListener("change", (e) =>
  updateModalIcon(e.target.value)
);

btnSave.addEventListener("click", () => {
  const title = fileTitleInput.value.trim();
  const fileName = fileNameInput.value.trim();
  const uploader = uploaderInput.value.trim();
  const type = fileTypeInput.value;
  const date = uploadDateInput.value;

  if (!title || !fileName || !uploader || !date) {
    alert("Please fill in all fields (Title, File Name, Uploader, Date).");
    return;
  }

  if (editingId) {
    const f = files.find((x) => x.id === editingId);
    if (f) {
      f.title = title;
      f.fileName = fileName;
      f.uploader = uploader;
      f.type = type;
      f.date = date;
    }
  } else {
    files.unshift({ id: genId(), title, fileName, uploader, type, date });
  }

  renderRows(searchInput.value);
  hideModal();
  rowsEl.scrollTop = 0;
});

function removeFile(id) {
  const f = files.find((x) => x.id === id);
  if (!f) return;
  const confirmDel = confirm(`Hapus file "${f.title}"?`);
  if (!confirmDel) return;
  files = files.filter((x) => x.id !== id);
  renderRows(searchInput.value);
}

searchInput.addEventListener("input", () => renderRows(searchInput.value));

searchInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter") {
    const first = rowsEl.querySelector(".row");
    if (first) first.scrollIntoView({ behavior: "smooth", block: "center" });
  }
});

// initial render
renderRows();
