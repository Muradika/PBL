<?php
session_start();
// 1. Include file koneksi database
include '../database/pengumuman.php';

// Tentukan folder target untuk upload
$image_target_dir = "uploads/images/";
$document_target_dir = "uploads/documents/";

$message = "";

// 2. Logika PHP untuk memproses form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Ambil data form
    $title = $_POST['title'];
    $type = $_POST['type'];
    $date = $_POST['date'];

    $image_path = null;
    $document_path = null;

    // Pastikan folder upload ada
    if (!is_dir($image_target_dir)) {
        mkdir($image_target_dir, 0777, true);
    }
    if (!is_dir($document_target_dir)) {
        mkdir($document_target_dir, 0777, true);
    }

    // A. Handle Image Upload (nama input: 'image_file')
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $image_name = basename($_FILES["image_file"]["name"]);
        // Menggunakan uniqid() agar nama file unik
        $image_path = $image_target_dir . uniqid('img_') . "_" . $image_name;

        if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $image_path)) {
            // Gambar berhasil diupload
        } else {
            $message .= "Maaf, ada error saat mengupload gambar Anda.<br>";
        }
    }

    // B. Handle Document Upload (nama input: 'document_file')
    if (isset($_FILES['document_file']) && $_FILES['document_file']['error'] == UPLOAD_ERR_OK) {
        $document_name = basename($_FILES["document_file"]["name"]);
        // Menggunakan uniqid() agar nama file unik
        $document_path = $document_target_dir . uniqid('doc_') . "_" . $document_name;

        if (move_uploaded_file($_FILES["document_file"]["tmp_name"], $document_path)) {
            // Dokumen berhasil diupload
        } else {
            $message .= "Maaf, ada error saat mengupload dokumen Anda.<br>";
        }
    }

    // C. Insert data ke database menggunakan Prepared Statement untuk keamanan
    if (empty($message)) { // Lanjutkan jika tidak ada error upload
        $stmt = $conn->prepare("INSERT INTO pengumuman (title, type, date, image_path, document_path) VALUES (?, ?, ?, ?, ?)");
        // "sssss" menandakan 5 parameter yang akan diisi berupa string
        $stmt->bind_param("sssss", $title, $type, $date, $image_path, $document_path);

        if ($stmt->execute()) {
            $message = "✅ Pengumuman berhasil dibuat!";
            // Redirect setelah sukses (Post/Redirect/Get pattern)
            header("Location: homepage1.php?status=success");
            exit();
        } else {
            $message = " Error database: " . $stmt->error;
        }

        $stmt->close();
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
    <title>SIPAk - Sistem Informasi Pengumuman Akademik Online</title>
    <meta name="description" content="Sistem Informasi Pengumuman Akademik Online - Profile Dosen">
    <link rel="stylesheet" href="../css/profiledosen.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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

        <nav class="nav-menu">
            <a href="homepage1.php" class="nav-link">Home</a>
            <a href="aboutuspage.php" class="nav-link">About Us</a>
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" id="profile-dropdown-btn">Profile</a>
                <div class="dropdown-menu" id="profile-dropdown-menu">
                    <a href="profiledosen.php" class="dropdown-item create-btn">
                        <i class="fas fa-plus-circle"></i> Add
                    </a>
                    <a href="logout.php" class="dropdown-item logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Log Out
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main class="main-content">
        <div class="form-container" id="formContainer">
            <h2 class="form-heading">Create New Announcement</h2>

            <?php if (!empty($message)): ?>
                <div class="status-message"
                    style="padding: 10px; margin-bottom: 15px; border-radius: 4px; background-color: <?php echo strpos($message, 'Error') !== false || strpos($message, 'Maaf') !== false ? '#f8d7da' : '#d4edda'; ?>; color: <?php echo strpos($message, 'Error') !== false || strpos($message, 'Maaf') !== false ? '#721c24' : '#155724'; ?>;">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <form id="announcementForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST"
                enctype="multipart/form-data">

                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="type">Type</label>
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
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>">
                </div>

                <div class="form-group">
                    <label for="image_file">Image</label>
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
                        <span class="file-limit">Maximum number of image : 1</span>
                    </div>
                    <input type="file" id="imageFileInput" name="image_file" style="display: none;"
                        accept=".jpg,.png,.jpeg">
                    <button type="button" class="btn-choose-file"
                        onclick="document.getElementById('imageFileInput').click()">Choose Image</button>
                </div>

                <div class="form-group">
                    <label for="document_file">Document</label>
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
                                <button type="button" class="btn-preview" onclick="previewDocument()">Preview</button>
                                <button type="button" class="remove-file" onclick="removeFile('document')">×</button>
                            </div>
                        </div>
                    </div>
                    <div class="file-upload-info">
                        <span>Attachment</span>
                        <span class="file-limit">Maximum number of files : 1</span>
                    </div>
                    <input type="file" id="documentFileInput" name="document_file" style="display: none;"
                        accept=".pdf,.doc,.docx,.xls,.xlsx">
                    <button type="button" class="btn-choose-file"
                        onclick="document.getElementById('documentFileInput').click()">Choose File</button>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-remove" onclick="resetForm()">Remove</button>
                    <button type="submit" class="btn-submit">Create</button>
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

    <footer class="main-footer">
        <div class="footer-content">
            <div class="footer-left">
                <div class="logo-title-group">
                    <img src="../img/img_Politeknikbnw1.png" alt="Logo Polibatam" class="footer-logo" />
                    <div class="footer-system-title">
                        Sistem Informasi Pengumuman <br />
                        Akademik <span class="footer-online-tag">Online</span>
                    </div>
                </div>
                <p class="footer-copyright">
                    © 2025 Politeknik Negeri Batam <br />
                    For Your Goals Beyond Horizon
                </p>
            </div>

            <div class="footer-right">
                <div class="contact-info">
                    Alamat: Jl. Ahmad Yani Batam Kota, Kota Batam, <br />
                    Kepulauan Riau, Indonesia <br /><br />
                    Phone : +62-778-469858 Ext.1017 <br />
                    Fax : +62-778-463620 <br />
                    Email : info@polibatam.ac.id
                </div>

                <div class="social-links">
                    <a href="https://www.instagram.com/polibatamofficial?igsh=dDdmeGVwbzVhbmR3"
                        class="social-btn instagram">
                        <span class="icon-label">Instagram</span>
                    </a>
                    <a href="https://youtube.com/@polibatamtv?feature=shared" class="social-btn youtube">
                        <span class="icon-label">YouTube</span>
                    </a>
                    <a href="https://www.polibatam.ac.id/" class="social-btn linkedin">
                        <span class="icon-label">Website</span>
                    </a>
                </div>
            </div>
        </div>
    </footer>
    <script src="../js/profiledosen.js"></script>
</body>

</html>