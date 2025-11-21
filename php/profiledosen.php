<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPAk - Sistem Informasi Pengumuman Akademik Online</title>
    <meta name="description" content="Sistem Informasi Pengumuman Akademik Online - Profile Dosen">
    <link rel="stylesheet" href="../css/profiledosen.css">
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
                    <a href="loginpage.php" class="dropdown-item logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Log Out
                    </a>
                </div>
            </div>
    </header>

    <main class="main-content">
        <div class="form-container" id="formContainer">
            <h2 class="form-heading">Create New Announcement</h2>

            <form id="announcementForm">
                <div class="form-group">
                    <label for="title">Title</label>
                    <input type="text" id="title" name="title" required>
                </div>

                <div class="form-group">
                    <label for="type">Type</label>
                    <select id="type" name="type" required>
                        <option value="" disabled selected>Choose file type</option>
                        <option value="jadwal">Jadwal Ujian</option>
                        <option value="beasiswa">Beasiswa</option>
                        <option value="akademik">Akademik</option>
                        <option value="kemahasiswaan">Kemahasiswaan</option>
                        <option value="wisuda">Wisuda</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="date">Date</label>
                    <input type="date" id="date" name="date" required>
                </div>

                <div class="form-group">
                    <label for="document">Document</label>
                    <div class="dropzone" id="dropzone">
                        <div class="dropzone-content">
                            <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M17 8l-5-5-5 5M12 3v12"
                                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <p>You can drag and drop files here.</p>
                        </div>
                        <div class="file-info" id="fileInfo" style="display: none;">
                            <span class="file-name" id="fileName"></span>
                            <button type="button" class="remove-file" onclick="removeFile()">×</button>
                        </div>
                    </div>
                    <div class="file-upload-info">
                        <span>Attachment</span>
                        <span class="file-limit">Maximum number of files : 1</span>
                    </div>
                    <input type="file" id="fileInput" style="display: none;" accept=".pdf,.doc,.docx,.jpg,.png">
                    <button type="button" class="btn-choose-file"
                        onclick="document.getElementById('fileInput').click()">Choose File</button>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn-remove" onclick="resetForm()">Remove</button>
                    <button type="submit" class="btn-submit">Create</button>
                </div>
            </form>
        </div>
    </main>
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