<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPAk - Sistem Informasi Pengumuman Akademik Online</title>
    <link rel="icon" type="image/png" href="../img/img_Politeknikbnw.png" />
    <link rel="stylesheet" href="../css/landingpage.css">
</head>

<body>
    <!-- Animated Background -->
    <div class="bg-animation">
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Navigation -->
    <nav class="navbar">
        <div class="logo-section">
            <img src="../img/img_Politeknikbnw.png" alt="Logo Polibatam">
            <div class="brand-text">
                <h2>SIPAk <span class="highlight">Online</span></h2>
            </div>
        </div>
        <ul class="nav-links">
            <li><a href="#home">Beranda</a></li>
            <li><a href="#features">Fitur</a></li>
            <li><a href="#about">Tentang</a></li>
        </ul>
        <button class="btn-login-nav" onclick="window.location.href='loginpage.php'">Masuk</button>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-badge">✨ Platform Pengumuman Akademik Terpadu</div>
            <h1>Sistem Informasi Pengumuman Akademik Online</h1>
            <p class="subtitle">
                Akses semua pengumuman kampus dengan mudah, cepat, dan terorganisir.
                Jadwal ujian, beasiswa, perubahan kelas, hingga informasi kemahasiswaan - semuanya dalam satu platform
                digital yang modern.
            </p>
            <div class="cta-buttons">
                <a href="loginpage.php" class="btn-primary btn-fill">Masuk Sekarang →</a>
                <a href="#features" class="btn-primary btn-outline">Pelajari Lebih Lanjut</a>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-number">100+</div>
                <div class="stat-label">Pengumuman Terarsip</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">50+</div>
                <div class="stat-label">Pengguna Aktif</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">24/7</div>
                <div class="stat-label">Akses Kapan Saja</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">100%</div>
                <div class="stat-label">Digital & Paperless</div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="section-header">
            <h2>Fitur Unggulan SIPAk</h2>
            <p>Platform modern yang dirancang mahasiswa Polibatam untuk kemudahan akses informasi akademik</p>
        </div>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">😁</div>
                <h3>Tampilan User Friendly</h3>
                <p>Web dirancang mahasiswa untuk bagus dipandang secara desain dan pemilihan warna agar user mengalami
                    pengalaman terbaik.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔍</div>
                <h3>Pencarian Cerdas</h3>
                <p>Temukan pengumuman yang Anda butuhkan dengan fitur pencarian dan filter yang canggih.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔖</div>
                <h3>Bookmark Favorit</h3>
                <p>Simpan pengumuman penting untuk akses cepat dan mudah di kemudian hari.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>Responsif & Mobile</h3>
                <p>Akses dari perangkat apapun - desktop, tablet, atau smartphone dengan tampilan optimal.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📥</div>
                <h3>Download Dokumen</h3>
                <p>Unduh lampiran pengumuman seperti PDF, Word, dan dokumen lainnya dengan mudah.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🗂️</div>
                <h3>Kategori Terorganisir</h3>
                <p>Pengumuman dikelompokkan berdasarkan kategori untuk navigasi yang lebih mudah.</p>
            </div>
        </div>
    </section>

    <!-- Footer CTA -->
    <section class="footer-cta" id="about">
        <h2>Ingin Mengakses Informasi Akademik?</h2>
        <p>Bergabunglah dengan puluhan mahasiswa dan dosen yang telah merasakan kemudahan SIPAk</p>
        <a href="loginpage.php" class="btn-primary btn-fill">Masuk ke SIPAk →</a>
    </section>

    <!-- Footer -->
    <footer>
        <p>© 2025 Politeknik Negeri Batam - SIPAk. Hak Cipta Dilindungi.</p>
        <p style="margin-top: 0.5rem; font-size: 0.9rem;">For Your Goals Beyond Horizon</p>
    </footer>

    <script src="../js/landingpage.js"></script>
</body>

</html>