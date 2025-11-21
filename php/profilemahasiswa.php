<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SIPAk - Sistem Informasi Pengumuman Akademik Online</title>

    <link rel="stylesheet" href="../css/profilemahasiswa.css" />
</head>

<body>
    <header class="navbar">
        <div class="logo-brand">
            <img src="../img/img_Politeknikbnw.png" alt="Logo Polibatam" class="nav-logo" />
            <div class="system-title">
                Sistem Informasi Pengumuman <br />
                Akademik <span class="online-tag">Online</span>
            </div>
        </div>

        <nav class="nav-menu">
            <a href="homepage1.php" class="nav-link active">Home</a>
            <a href="aboutuspage.php" class="nav-link">About Us</a>
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" id="profile-dropdown-btn">Profile</a>
                <div class="dropdown-menu" id="profile-dropdown-menu">
                    <a href="profilemahasiswa.php" class="dropdown-item create-btn">
                        <i class="fas fa-plus-circle"></i> Favorites
                    </a>
                    <a href="loginpage.php" class="dropdown-item logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Log Out
                    </a>
        </nav>
    </header>

    <main class="main">
        <div class="searchbar">
            <div class="searchbox">
                <span class="search-icon">🔍</span>
                <input id="searchInput" placeholder="Search File (Title, Uploader, Type)" />
            </div>

            <!-- START: Input Filter Tanggal -->
            <div class="date-filter-group">
                <label for="startDateInput" class="date-label">Dari:</label>
                <input type="date" id="startDateInput" class="date-input" title="Tanggal Mulai" />

                <label for="endDateInput" class="date-label">Sampai:</label>
                <input type="date" id="endDateInput" class="date-input" title="Tanggal Akhir" />
            </div>
            <!-- END: Input Filter Tanggal -->

            <!-- START: Kontainer Filter Dropdown -->
            <div class="filter-dropdown-container">
                <button id="filterButton" class="filter" title="Filter Berdasarkan Kategori">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 5h18M6 12h12M10 19h4" stroke="#0b2b57" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>

                <div id="filterOptions" class="filter-options">
                    <!-- data-filter harus cocok dengan data-category di card -->
                    <a href="#" data-filter="All" class="filter-option active">Semua Kategori</a>
                    <a href="#" data-filter="Jadwal Ujian" class="filter-option">Jadwal Ujian</a>
                    <a href="#" data-filter="Beasiswa" class="filter-option">Beasiswa</a>
                    <a href="#" data-filter="Perubahan Kelas" class="filter-option">Perubahan Kelas</a>
                    <a href="#" data-filter="Karir" class="filter-option">Karir</a>
                    <!-- Tambahkan kategori lain sesuai kebutuhan Anda -->
                </div>
            </div>
            <!-- END: Kontainer Filter Dropdown -->
        </div>
    </main>

    <main class="container">
        <div class="card-grid">
            <div class="announcement-card" data-category="Jadwal Ujian" data-date="2025-12-31"
                data-title="Pengumuman Jadwal AAS Semester Ganjil">
                <a href="../doc/fileexcel_Reguler Pagi - Jadwal ATS Semester Ganjil 2425.xlsx" target="_blank"
                    class="card-link">
                    <div class="card-image-box" style="
              background-image: url('https://th.bing.com/th/id/R.abd4aa65cb090255443dbba0c6bd360d?rik=O9E6FRDoeomqSg&riu=http%3a%2f%2falimamischool.com%2fwp-content%2fuploads%2f2023%2f09%2fAsesmen-SD-1140x641.png&ehk=e9TMpSgzcZXbexQKnFvLTBjogYOb09Ux30iNcxlSxgI%3d&risl=&pid=ImgRaw&r=0');
            "></div>
                    <div class="card-content">
                        <span class="card-date">31 Desember 2025</span>
                        <p class="card-category">Jadwal Ujian</p>
                        <h4 class="card-title">Pengumuman Jadwal AAS Semester Ganjil</h4>
                    </div>
                </a>
            </div>

            <div class="announcement-card" data-category="Beasiswa" data-date="2025-12-23"
                data-title="Pendaftaran Beasiswa Peningkatan Prestasi Akademik">
                <div class="card-image-box" style="
              background-image: url('https://skuling.id/wp-content/uploads/2024/07/beasiswa-ppa.png');
            "></div>
                <div class="card-content">
                    <span class="card-date">23 Desember 2025</span>
                    <p class="card-category">Beasiswa</p>
                    <h4 class="card-title">
                        Pendaftaran Beasiswa Peningkatan Prestasi Akademik
                    </h4>
                </div>
            </div>

            <div class="announcement-card" data-category="Perubahan Kelas" data-date="2025-12-15"
                data-title="Pengumuman Perubahan Jadwal Kelas IF 1B Pagi">
                <div class="card-image-box" style="
              background-image: url('https://media.sciencephoto.com/image/c0328010/800wm/C0328010-Students_at_university_class.jpg');
            "></div>
                <div class="card-content">
                    <span class="card-date">15 Desember 2025</span>
                    <p class="card-category">Perubahan Kelas</p>
                    <h4 class="card-title">
                        Pengumuman Perubahan Jadwal Kelas IF 1B Pagi
                    </h4>
                </div>
            </div>
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
    <script src="../js/homepage.js"></script>
    <script src="../js/profilemahasiswa.js"></script>"
</body>

</html>