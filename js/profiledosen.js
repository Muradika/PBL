// File upload handling
let uploadedFile = null;

// Initialize dropzone
const dropzone = document.getElementById('dropzone');
const fileInput = document.getElementById('fileInput');
const fileInfo = document.getElementById('fileInfo');
const fileName = document.getElementById('fileName');
const dropzoneContent = dropzone.querySelector('.dropzone-content');

document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.getElementById('profile-dropdown-btn');
    const dropdown = document.querySelector('.dropdown');

    // Fungsi untuk membuka/menutup dropdown
    dropdownBtn.addEventListener('click', function(e) {
        e.preventDefault(); // Mencegah link pindah halaman
        
        // Toggle (menambah/menghapus) class 'active' pada kontainer dropdown
        dropdown.classList.toggle('active');
    });

    // Opsional: Tutup dropdown saat pengguna mengklik di luar area menu
    document.addEventListener('click', function(e) {
        if (!dropdown.contains(e.target) && !dropdownBtn.contains(e.target)) {
            dropdown.classList.remove('active');
        }
    });
});

// Prevent default drag behaviors
['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, preventDefaults, false);
    document.body.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

// Highlight drop zone when item is dragged over it
['dragenter', 'dragover'].forEach(eventName => {
    dropzone.addEventListener(eventName, () => {
        dropzone.classList.add('dragover');
    }, false);
});

['dragleave', 'drop'].forEach(eventName => {
    dropzone.addEventListener(eventName, () => {
        dropzone.classList.remove('dragover');
    }, false);
});

// Handle dropped files
dropzone.addEventListener('drop', handleDrop, false);
dropzone.addEventListener('click', () => {
    if (!uploadedFile) {
        fileInput.click();
    }
});

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    
    if (files.length > 0) {
        handleFiles(files[0]);
    }
}

// Handle file input change
fileInput.addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        handleFiles(e.target.files[0]);
    }
});

function handleFiles(file) {
    uploadedFile = file;
    fileName.textContent = file.name;
    dropzoneContent.style.display = 'none';
    fileInfo.style.display = 'flex';
    console.log('File uploaded:', file.name);
}

function removeFile() {
    uploadedFile = null;
    fileName.textContent = '';
    dropzoneContent.style.display = 'block';
    fileInfo.style.display = 'none';
    fileInput.value = '';
    console.log('File removed');
}

// Form handling
const form = document.getElementById('announcementForm');

form.addEventListener('submit', (e) => {
    e.preventDefault();
    
    const formData = {
        title: document.getElementById('title').value,
        type: document.getElementById('type').value,
        date: document.getElementById('date').value,
        file: uploadedFile ? uploadedFile.name : null
    };
    
    console.log('Form submitted:', formData);
    alert('Announcement created successfully!\n\n' + 
          'Title: ' + formData.title + '\n' +
          'Type: ' + formData.type + '\n' +
          'Date: ' + formData.date + '\n' +
          'File: ' + (formData.file || 'No file uploaded'));
    
    // Reset form after submission
    resetForm();
});

function resetForm() {
    form.reset();
    removeFile();
    console.log('Form reset');
}

function toggleForm() {
    const formContainer = document.getElementById('formContainer');
    if (formContainer.style.display === 'none') {
        formContainer.style.display = 'block';
    } else {
        formContainer.style.display = 'none';
    }
}

function handleLogout() {
    if (confirm('Are you sure you want to log out?')) {
        console.log('User logged out');
        alert('You have been logged out successfully!');
        // In a real application, this would redirect to login page
        // window.location.href = '/login.html';
    }
}

// Set today's date as default
document.getElementById('date').valueAsDate = new Date();

console.log('Announcement system initialized');
