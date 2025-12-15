<?php
session_start();
// 1. Include file koneksi database
include '../database/pengumuman.php';

// PROTEKSI: Hanya dosen yang boleh akses
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit;
}

if ($_SESSION['role'] !== 'dosen') {
    header("Location: profilemahasiswa.php");
    exit;
}

// Regenerate session ID untuk security
if (!isset($_SESSION['profile_dosen_verified'])) {
    session_regenerate_id(true);
    $_SESSION['profile_dosen_verified'] = true;
}

// Tentukan folder target untuk upload
$image_target_dir = "uploads/images/";
$document_target_dir = "uploads/documents/";

// File size limits (dalam bytes)
define('MAX_IMAGE_SIZE', 5 * 1024 * 1024); // 5MB
define('MAX_DOCUMENT_SIZE', 10 * 1024 * 1024); // 10MB

$message = "";
$message_type = ""; // 'success' atau 'error'

// 2. Logika PHP untuk memproses form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Server-side validation
    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
    $type = isset($_POST['type']) ? trim($_POST['type']) : '';
    $date = isset($_POST['date']) ? trim($_POST['date']) : '';

    // Validasi input wajib
    if (empty($title) || empty($type) || empty($date)) {
        $message = "All fields are required to be filled in!";
        $message_type = "error";
    } else {
        // Validasi panjang title
        if (strlen($title) > 200) {
            $message = "Title is too long (max. 200 characters)!";
            $message_type = "error";
        } else {
            // Validasi tipe pengumuman
            $allowed_types = ["Jadwal", "Beasiswa", "Perubahan Kelas", "Karir", "Kemahasiswaan"];
            if (!in_array($type, $allowed_types)) {
                $message = "Announcement type not valid!";
                $message_type = "error";
            } else {
                // Validasi format tanggal
                $date_obj = DateTime::createFromFormat('Y-m-d', $date);
                if (!$date_obj || $date_obj->format('Y-m-d') !== $date) {
                    $message = "Date format not valid!";
                    $message_type = "error";
                } else {
                    // Ambil data user yang login
                    $created_by = $_SESSION['user_id'];
                    $created_by_name = $_SESSION['nama_lengkap'];

                    $image_path = null;
                    $document_path = null;

                    // Pastikan folder upload ada
                    if (!is_dir($image_target_dir)) {
                        mkdir($image_target_dir, 0755, true);
                    }
                    if (!is_dir($document_target_dir)) {
                        mkdir($document_target_dir, 0755, true);
                    }

                    // A. Handle Image Upload dengan validasi ketat
                    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
                        $image_file = $_FILES['image_file'];

                        // Validasi ukuran file
                        if ($image_file['size'] > MAX_IMAGE_SIZE) {
                            $message .= "Picture size is too big (max. 5MB)<br>";
                            $message_type = "error";
                        } else {
                            // Validasi MIME type
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime_type = finfo_file($finfo, $image_file['tmp_name']);
                            finfo_close($finfo);

                            $allowed_mime = ['image/jpeg', 'image/jpg', 'image/png'];

                            if (!in_array($mime_type, $allowed_mime)) {
                                $message .= "Picture format is not valid (only JPG, JPEG, PNG)<br>";
                                $message_type = "error";
                            } else {
                                // Sanitasi nama file
                                $image_extension = strtolower(pathinfo($image_file['name'], PATHINFO_EXTENSION));
                                $image_name = uniqid('img_', true) . '.' . $image_extension;
                                $image_path = $image_target_dir . $image_name;

                                if (!move_uploaded_file($image_file['tmp_name'], $image_path)) {
                                    $message .= "Failed uploading picture<br>";
                                    $message_type = "error";
                                    $image_path = null;
                                }
                            }
                        }
                    } else {
                        $message .= "Picture required to be filled in<br>";
                        $message_type = "error";
                    }

                    // B. Handle Document Upload dengan validasi ketat
                    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == UPLOAD_ERR_OK) {
                        $document_file = $_FILES['document_file'];

                        // Validasi ukuran file
                        if ($document_file['size'] > MAX_DOCUMENT_SIZE) {
                            $message .= "Document size is too big (max. 10MB)<br>";
                            $message_type = "error";
                        } else {
                            // Validasi MIME type
                            $finfo = finfo_open(FILEINFO_MIME_TYPE);
                            $mime_type = finfo_file($finfo, $document_file['tmp_name']);
                            finfo_close($finfo);

                            $allowed_mime = [
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            ];

                            if (!in_array($mime_type, $allowed_mime)) {
                                $message .= "Document format not valid! (only PDF, DOC, DOCX, XLS, XLSX)<br>";
                                $message_type = "error";
                            } else {
                                // Sanitasi nama file
                                $document_extension = strtolower(pathinfo($document_file['name'], PATHINFO_EXTENSION));
                                $document_name = uniqid('doc_', true) . '.' . $document_extension;
                                $document_path = $document_target_dir . $document_name;

                                if (!move_uploaded_file($document_file['tmp_name'], $document_path)) {
                                    $message .= "Failed uploading document<br>";
                                    $message_type = "error";
                                    $document_path = null;
                                }
                            }
                        }
                    } else {
                        $message .= "Document required to be filled in!<br>";
                        $message_type = "error";
                    }

                    // C. Insert data ke database jika tidak ada error
                    if ($message_type !== "error" && $image_path && $document_path) {
                        $stmt = $conn->prepare("INSERT INTO pengumuman (title, type, date, image_path, document_path, created_by, created_by_name) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("sssssis", $title, $type, $date, $image_path, $document_path, $created_by, $created_by_name);

                        if ($stmt->execute()) {
                            $message = "Announcement Succesfully Created!";
                            $message_type = "success";
                            // Redirect setelah sukses (Post/Redirect/Get pattern)
                            header("Location: homepage1.php?status=success");
                            exit();
                        } else {
                            $message = "❌ Error database: " . $stmt->error;
                            $message_type = "error";

                            // Hapus file yang sudah diupload jika insert gagal
                            if ($image_path && file_exists($image_path)) {
                                unlink($image_path);
                            }
                            if ($document_path && file_exists($document_path)) {
                                unlink($document_path);
                            }
                        }

                        $stmt->close();
                    }
                }
            }
        }
    }

    // Tutup koneksi setelah selesai memproses request
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPAk - Tambah Pengumuman</title>
    <link rel="icon" type="image/png" href="../img/img_Politeknikbnw.png" />
    <meta name="description" content="Sistem Informasi Pengumuman Akademik Online - Profile Dosen">
    <link rel="stylesheet" href="../css/profiledosen.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <header class="navbar">
        <div class="logo-brand">
            <img src="../img/img_Politeknikbnw.png" alt="Logo Polibatam" class="nav-logo">
            <div class="system-title">
                Sistem Informasi Pengumuman <br />
                Akademik <span class="online-tag">Online</span>
            </div>
        </div>

        <!-- Hamburger Menu Button -->
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <nav class="nav-menu">
            <a href="homepage1.php" class="nav-link">Home</a>
            <a href="aboutuspage.php" class="nav-link">About Us</a>
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle active" id="profile-dropdown-btn">Profile</a>
                <div class="dropdown-menu" id="profile-dropdown-menu">
                    <a href="profilemahasiswa.php" class="dropdown-item create-btn">
                        <i class="fas fa-bookmark"></i> Favorites
                    </a>

                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'dosen'): ?>
                        <a href="profiledosen.php" class="dropdown-item add-btn">
                            <i class="fas fa-plus-circle"></i> Add
                        </a>
                    <?php endif; ?>

                    <a href="logout.php" class="dropdown-item logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Log Out
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <!-- Overlay untuk Mobile Menu -->
    <div class="menu-overlay"></div>

    <main class="main-content">
        <div class="form-container" id="formContainer">
            <h2 class="form-heading">Create New Announcement</h2>

            <?php if (!empty($message)): ?>
                <div class="status-message status-<?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form id="announcementForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST"
                enctype="multipart/form-data">

                <div class="form-group">
                    <label for="title">Title <span class="required">*</span></label>
                    <input type="text" id="title" name="title" required maxlength="200"
                        placeholder="Enter announcement title">
                </div>

                <div class="form-group">
                    <label for="type">Type <span class="required">*</span></label>
                    <select id="type" name="type" required>
                        <option value="" disabled selected>Choose file type</option>
                        <option value="Jadwal">Jadwal Ujian</option>
                        <option value="Beasiswa">Beasiswa</option>
                        <option value="Perubahan Kelas">Perubahan Kelas</option>
                        <option value="Karir">Karir</option>
                        <option value="Kemahasiswaan">Kemahasiswaan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Date <span class="required">*</span></label>
                    <input type="date" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="image_file">Image <span class="required">*</span></label>
                    <div class="upload-section">
                        <div class="dropzone" id="imageDropzone">
                            <div class="dropzone-content">
                                <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <p>You can drag and drop image here.</p>
                            </div>
                            <div class="file-info" id="imageFileInfo" style="display: none;">
                                <span class="file-name" id="imageFileName"></span>
                                <div class="file-actions">
                                    <button type="button" class="btn-preview" onclick="previewImage()">Preview</button>
                                    <button type="button" class="remove-file" onclick="removeFile('image')">×</button>
                                </div>
                            </div>
                        </div>
                        <div class="file-upload-info">
                            <span>Attachment</span>
                            <span class="file-limit">Max: 1 image, 5MB (JPG, PNG)</span>
                        </div>
                        <input type="file" id="imageFileInput" name="image_file" style="display: none;"
                            accept=".jpg,.png,.jpeg" required>
                        <button type="button" class="btn-choose-file"
                            onclick="document.getElementById('imageFileInput').click()">Choose Image</button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="document_file">Document <span class="required">*</span></label>
                    <div class="upload-section">
                        <div class="dropzone" id="documentDropzone">
                            <div class="dropzone-content">
                                <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <p>You can drag and drop files here.</p>
                            </div>
                            <div class="file-info" id="documentFileInfo" style="display: none;">
                                <span class="file-name" id="documentFileName"></span>
                                <div class="file-actions">
                                    <button type="button" class="btn-preview"
                                        onclick="previewDocument()">Preview</button>
                                    <button type="button" class="remove-file"
                                        onclick="removeFile('document')">×</button>
                                </div>
                            </div>
                        </div>
                        <div class="file-upload-info">
                            <span>Attachment</span>
                            <span class="file-limit">Max: 1 file, 10MB (PDF, DOC, XLS)</span>
                        </div>
                        <input type="file" id="documentFileInput" name="document_file" style="display: none;"
                            accept=".pdf,.doc,.docx,.xls,.xlsx" required>
                        <button type="button" class="btn-choose-file"
                            onclick="document.getElementById('documentFileInput').click()">Choose File</button>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-remove" onclick="resetForm()">
                        Remove
                    </button>
                    <button type="submit" class="btn-submit">
                        Create
                    </button>
                </div>
            </form>
        </div>
    </main>

    <!-- MODAL PREVIEW IMAGE -->
    <div id="imagePreviewModal" class="preview-modal">
        <div class="modal-content-preview">
            <span class="close-modal" onclick="closeImagePreview()">&times;</span>
            <img id="previewImageContent" src="" alt="Image Preview" />
        </div>
    </div>

    <footer class="modern-footer">
        <div class="footer-container">
            <div class="footer-main">
                <!-- Brand Section -->
                <div class="footer-brand">
                    <div class="brand-logo-section">
                        <div class="brand-logo">
                            <img src="../img/img_Politeknikbnw1.png" alt="Logo Polibatam">
                        </div>
                        <div class="brand-text">
                            <h3>Sistem Informasi Pengumuman<br>Akademik <span class="highlight">Online</span></h3>
                        </div>
                    </div>
                    <p class="brand-description">
                        A digital platform facilitate access to academic information for students and lecturers at
                        Politeknik Negeri Batam.
                    </p>
                    <p class="brand-motto">
                        For Your Goals Beyond Horizon
                    </p>
                </div>

                <!-- Quick Links -->
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="homepage1.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="aboutuspage.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="profilemahasiswa.php"><i class="fas fa-chevron-right"></i> Profile</a></li>
                        <li><a href="logout.php"><i class="fas fa-chevron-right"></i> Logout</a></li>
                    </ul>
                </div>

                <!-- Resources -->
                <div class="footer-section">
                    <h4>Resources</h4>
                    <ul class="footer-links">
                        <li><a href="https://learning-if.polibatam.ac.id/" target="_blank"><i
                                    class="fas fa-chevron-right"></i> E-Learning</a></li>
                        <li><a href="https://sim.polibatam.ac.id/" target="_blank"><i class="fas fa-chevron-right"></i>
                                SILAM</a></li>
                        <li><a href="https://pbl.polibatam.ac.id/" target="_blank"><i class="fas fa-chevron-right"></i>
                                SIAP-PBL</a></li>
                        <li><a href="https://helpdesk.polibatam.ac.id/open.php"><i class="fas fa-chevron-right"></i>
                                Help Center</a></li>
                    </ul>
                </div>

                <!-- Contact & Social -->
                <div class="footer-section">
                    <h4>Contact Us</h4>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>Jl. Ahmad Yani Batam Kota,<br>Kota Batam, Kepulauan Riau, Indonesia</span>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <a href="tel:+627784698581017">+62-778-469858 Ext.1017</a>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <a href="mailto:info@polibatam.ac.id">info@polibatam.ac.id</a>
                    </div>

                    <div class="social-media">
                        <a href="https://www.instagram.com/polibatamofficial" target="_blank"
                            class="social-link instagram" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://youtube.com/@polibatamtv" target="_blank" class="social-link youtube"
                            title="YouTube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="https://www.polibatam.ac.id" target="_blank" class="social-link linkedin"
                            title="Website">
                            <i class="fas fa-globe"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="copyright">
                    © 2025 Politeknik Negeri Batam. All rights reserved.
                </div>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>
    <script src="../js/profiledosen.js"></script>
</body>

</html>