<?php
session_start();
include '../database/pengumuman.php';

// ========== STRICT USER CHECK ==========
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit;
}

// Regenerate session ID untuk security
if (!isset($_SESSION['profile_verified'])) {
    session_regenerate_id(true);
    $_SESSION['profile_verified'] = true;
}

$user_id = $_SESSION['user_id'];

// ========== SETUP PARAMETER ==========
$announcements_per_page = 6;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($current_page < 1)
    $current_page = 1;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_type = isset($_GET['filter']) && $_GET['filter'] !== 'All' ? trim($_GET['filter']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

$offset = ($current_page - 1) * $announcements_per_page;

// ========== KONSTRUKSI QUERY DENGAN PREPARED STATEMENTS ==========
$where_clauses = ["f.user_id = ?"];
$params = [$user_id];
$types = 's';

if (!empty($search_query)) {
    $where_clauses[] = "(p.title LIKE ? OR p.type LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

if (!empty($filter_type)) {
    if (empty($search_query) || strpos($search_query, $filter_type) === false) {
        $where_clauses[] = "p.type = ?";
        $params[] = $filter_type;
        $types .= 's';
    }
}

if (!empty($start_date)) {
    $where_clauses[] = "p.date >= ?";
    $params[] = $start_date;
    $types .= 's';
}

if (!empty($end_date)) {
    $where_clauses[] = "p.date <= ?";
    $params[] = $end_date;
    $types .= 's';
}

$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// ========== COUNT TOTAL ==========
$count_query = "SELECT COUNT(*) AS total FROM favorites f 
                JOIN pengumuman p ON f.announcement_id = p.id" . $where_sql;
$count_stmt = $conn->prepare($count_query);
$count_stmt->bind_param($types, ...$params);
$count_stmt->execute();
$total_announcements_result = $count_stmt->get_result()->fetch_assoc();
$total_announcements = $total_announcements_result['total'];
$total_pages = ceil($total_announcements / $announcements_per_page);
$count_stmt->close();

// ========== GET DATA ==========
$data_query = "SELECT p.id, p.title, p.type, p.date, p.image_path, p.document_path 
               FROM favorites f 
               JOIN pengumuman p ON f.announcement_id = p.id"
    . $where_sql
    . " ORDER BY f.created_at DESC LIMIT ?, ?";

$types .= 'ii';
$params[] = $offset;
$params[] = $announcements_per_page;

$data_stmt = $conn->prepare($data_query);
$data_stmt->bind_param($types, ...$params);
$data_stmt->execute();
$result = $data_stmt->get_result();
$favorites = [];

while ($row = $result->fetch_assoc()) {
    $favorites[] = $row;
}

$data_stmt->close();
$conn->close();

function get_url_params($page_num)
{
    $params = $_GET;
    $params['page'] = $page_num;
    if (empty($params['search']))
        unset($params['search']);
    if (isset($params['filter']) && $params['filter'] === 'All')
        unset($params['filter']);
    if (empty($params['start_date']))
        unset($params['start_date']);
    if (empty($params['end_date']))
        unset($params['end_date']);
    return http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>SIPAk - Profile</title>
    <link rel="stylesheet" href="../css/profilemahasiswa.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    <main class="main">
        <div style="padding: 0px 0 10px 0;">
            <?php
            $nama = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'User';
            $role = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Pengguna';
            ?>

            <p class="greeting-text">
                👋 Halo <?php echo htmlspecialchars($nama); ?>, kamu login sebagai
                <?php echo htmlspecialchars($role); ?>
            </p>

            <p class="subtitle-text">
                <?php echo $total_announcements; ?> pengumuman yang Anda bookmark
            </p>
        </div>

        <form class="searchbar" method="GET" action="profilemahasiswa.php">
            <div class="searchbox">
                <span class="search-icon">🔍</span>
                <input id="searchInput" name="search" placeholder="Search File (Title, Date, Type)"
                    value="<?php echo htmlspecialchars($search_query); ?>" />
                <button type="submit" style="display:none;"></button>
            </div>

            <div class="date-filter-group">
                <label for="startDateInput" class="date-label">Dari:</label>
                <input type="date" id="startDateInput" name="start_date" class="date-input"
                    value="<?php echo htmlspecialchars($start_date); ?>" onchange="this.form.submit()" />

                <label for="endDateInput" class="date-label">Sampai:</label>
                <input type="date" id="endDateInput" name="end_date" class="date-input"
                    value="<?php echo htmlspecialchars($end_date); ?>" onchange="this.form.submit()" />
            </div>

            <div class="filter-dropdown-container">
                <button id="filterButton" class="filter" type="button">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 5h18M6 12h12M10 19h4" stroke="#0b2b57" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>

                <div id="filterOptions" class="filter-options">
                    <a href="?<?php echo get_url_params('1'); ?>&filter=All"
                        class="filter-option <?php echo empty($filter_type) ? 'active' : ''; ?>">Semua Kategori</a>

                    <?php
                    $categories = ["Jadwal", "Beasiswa", "Perubahan Kelas", "Karir", "Kemahasiswaan"];
                    foreach ($categories as $cat):
                        ?>
                        <a href="?search=<?php echo urlencode($search_query); ?>&filter=<?php echo $cat; ?>"
                            class="filter-option <?php echo ($filter_type === $cat) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </main>

    <main class="container">
        <div class="card-grid">
            <?php if (!empty($favorites)): ?>
                <?php foreach ($favorites as $fav): ?>
                    <?php
                    $doc_url = $fav['document_path'] ?: '#';
                    $img_url = $fav['image_path'] ?: 'https://via.placeholder.com/300x180?text=' . urlencode($fav['type']);
                    ?>

                    <div class="announcement-card">
                        <a href="<?php echo htmlspecialchars($doc_url); ?>" target="_blank" class="card-link">
                            <div class="card-image-box" style="
                                background-image: url('<?php echo htmlspecialchars($img_url); ?>');
                                background-size: cover;
                                background-position: center;
                            ">
                                <div class="card-actions">
                                    <?php if ($doc_url !== '#'): ?>
                                        <button class="action-btn download-btn"
                                            data-doc="<?php echo htmlspecialchars($doc_url); ?>">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14" stroke="white" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>

                                    <button class="action-btn remove-btn" data-id="<?php echo $fav['id']; ?>">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                            <path d="M18 6L6 18M6 6l12 12" stroke="white" stroke-width="2"
                                                stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="card-content">
                                <span class="card-date"><?php echo date('d F Y', strtotime($fav['date'])); ?></span>
                                <p class="card-category"><?php echo htmlspecialchars($fav['type']); ?></p>
                                <h4 class="card-title"><?php echo htmlspecialchars($fav['title']); ?></h4>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 80px 20px;">
                    <svg width="100" height="100" viewBox="0 0 24 24" fill="none"
                        style="opacity: 0.3; margin-bottom: 20px;">
                        <path d="M5 5v16l7-5 7 5V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2z" stroke="#999" stroke-width="2" />
                    </svg>
                    <h3 style="color: #999; margin-bottom: 10px;">
                        <?php echo (!empty($search_query) || !empty($filter_type) || !empty($start_date) || !empty($end_date))
                            ? "Tidak ada hasil yang ditemukan"
                            : "Belum ada favorites"; ?>
                    </h3>
                    <p style="color: #aaa; margin-bottom: 30px;">
                        <?php echo (!empty($search_query) || !empty($filter_type) || !empty($start_date) || !empty($end_date))
                            ? "Coba ubah filter atau kata kunci pencarian Anda"
                            : "Klik tombol bookmark pada pengumuman di homepage untuk menambahkan ke favorites"; ?>
                    </p>
                    <a href="<?php echo (!empty($search_query) || !empty($filter_type) || !empty($start_date) || !empty($end_date)) ? 'profilemahasiswa.php' : 'homepage1.php'; ?>"
                        style="display: inline-block; padding: 12px 30px; background: #ff6347; color: white; text-decoration: none; border-radius: 8px; font-weight: bold;">
                        <?php echo (!empty($search_query) || !empty($filter_type) || !empty($start_date) || !empty($end_date)) ? '🔄 Reset Filter' : '← Kembali ke Homepage'; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?<?php echo get_url_params($i); ?>"
                    class="page-number <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>

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
                        Platform digital untuk memudahkan akses informasi akademik mahasiswa dan dosen Politeknik Negeri
                        Batam.
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
                    <h4>Hubungi Kami</h4>
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

    <script src="../js/profilemahasiswa.js"></script>
</body>

</html>