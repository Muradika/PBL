<?php
session_start();
include '../database/pengumuman.php';

// --- 1. SETUP PARAMETER DEFAULT ---
$announcements_per_page = 6;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($current_page < 1)
    $current_page = 1;

$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_type = isset($_GET['filter']) && $_GET['filter'] !== 'All' ? $conn->real_escape_string($_GET['filter']) : '';
$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';

$offset = ($current_page - 1) * $announcements_per_page;

// --- 2. KONSTRUKSI QUERY ---
$where_clauses = [];
$bind_params = '';
$bind_values = [];

if (!empty($search_query)) {
    $where_clauses[] = "(title LIKE ? OR type LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $bind_params .= 'ss';
    $bind_values[] = $search_param;
    $bind_values[] = $search_param;
}

if (!empty($filter_type)) {
    if (empty($search_query) || strpos($search_query, $filter_type) === false) {
        $where_clauses[] = "type = ?";
        $bind_params .= 's';
        $bind_values[] = $filter_type;
    }
}

if (!empty($start_date)) {
    $where_clauses[] = "date >= ?";
    $bind_params .= 's';
    $bind_values[] = $start_date;
}
if (!empty($end_date)) {
    $where_clauses[] = "date <= ?";
    $bind_params .= 's';
    $bind_values[] = $end_date;
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// --- 3. HITUNG TOTAL ---
$count_query = "SELECT COUNT(*) AS total FROM pengumuman" . $where_sql;
$count_stmt = $conn->prepare($count_query);

if (!empty($bind_params)) {
    $refs = [];
    foreach ($bind_values as $k => $v) {
        $refs[$k] = &$bind_values[$k];
    }
    array_unshift($refs, $bind_params);
    call_user_func_array([$count_stmt, 'bind_param'], $refs);
}

$count_stmt->execute();
$total_announcements_result = $count_stmt->get_result()->fetch_assoc();
$total_announcements = $total_announcements_result['total'];
$total_pages = ceil($total_announcements / $announcements_per_page);
$count_stmt->close();

// --- 4. AMBIL DATA ---
$data_query = "SELECT id, title, type, date, image_path, document_path FROM pengumuman"
    . $where_sql
    . " ORDER BY date DESC LIMIT ?, ?";

$bind_params .= 'ii';
$bind_values[] = $offset;
$bind_values[] = $announcements_per_page;

$data_stmt = $conn->prepare($data_query);

if (!empty($bind_params)) {
    $refs = [];
    foreach ($bind_values as $k => $v) {
        $refs[$k] = &$bind_values[$k];
    }
    array_unshift($refs, $bind_params);
    call_user_func_array([$data_stmt, 'bind_param'], $refs);
}

$data_stmt->execute();
$result = $data_stmt->get_result();
$announcements = $result->fetch_all(MYSQLI_ASSOC);

$data_stmt->close();

// --- 5. AMBIL FAVORITES USER (jika login) ---
$user_favorites = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $fav_stmt = $conn->prepare("SELECT announcement_id FROM favorites WHERE user_id = ?");
    $fav_stmt->bind_param("s", $user_id);
    $fav_stmt->execute();
    $fav_result = $fav_stmt->get_result();
    while ($row = $fav_result->fetch_assoc()) {
        $user_favorites[] = $row['announcement_id'];
    }
    $fav_stmt->close();
}

$conn->close();

// --- 6. FUNCTION UNTUK URL PARAMS ---
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

// --- 7. SMART PAGINATION LOGIC ---
$max_visible_pages = 6;
$half_visible = floor($max_visible_pages / 2);

if ($total_pages <= $max_visible_pages) {
    $start_page = 1;
    $end_page = $total_pages;
} else {
    if ($current_page <= $half_visible) {
        $start_page = 1;
        $end_page = $max_visible_pages;
    } elseif ($current_page >= ($total_pages - $half_visible)) {
        $start_page = $total_pages - $max_visible_pages + 1;
        $end_page = $total_pages;
    } else {
        $start_page = $current_page - $half_visible;
        $end_page = $current_page + $half_visible;
    }
}

$show_prev = $current_page > 1;
$show_next = $current_page < $total_pages;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>SIPAk - Sistem Informasi Pengumuman Akademik Online</title>
    <link rel="stylesheet" href="../css/homepage.css" />
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

        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <nav class="nav-menu">
            <a href="homepage1.php" class="nav-link active">Home</a>
            <a href="aboutuspage.php" class="nav-link">About Us</a>
            <a href="profilemahasiswa.php" class="nav-link">Profile</a>
        </nav>
    </header>

    <main class="main">
        <form class="searchbar" method="GET" action="homepage1.php">
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
                        $link_params = $_GET;
                        $link_params['filter'] = $cat;
                        unset($link_params['page']);
                        $category_url = '?' . http_build_query($link_params);
                        ?>
                        <a href="<?php echo $category_url; ?>"
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
            <?php if (!empty($announcements)): ?>
                <?php foreach ($announcements as $announcement): ?>
                    <?php
                    $doc_url = $announcement['document_path'] ?: '#';
                    $img_url = $announcement['image_path'] ?: 'https://via.placeholder.com/300x180?text=' . urlencode($announcement['type']);
                    $is_favorited = in_array($announcement['id'], $user_favorites);
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

                                    <button class="action-btn bookmark-btn <?php echo $is_favorited ? 'active' : ''; ?>"
                                        data-id="<?php echo $announcement['id']; ?>">
                                        <svg width="20" height="20" viewBox="0 0 24 24"
                                            fill="<?php echo $is_favorited ? 'white' : 'none'; ?>">
                                            <path d="M5 5v16l7-5 7 5V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2z" stroke="white"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="card-content">
                                <span class="card-date"><?php echo date('d F Y', strtotime($announcement['date'])); ?></span>
                                <p class="card-category"><?php echo htmlspecialchars($announcement['type']); ?></p>
                                <h4 class="card-title"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; padding: 250px;">
                    Tidak ada pengumuman yang ditemukan dengan kriteria yang dicari.
                </p>
            <?php endif; ?>
        </div>
    </main>

    <!-- SMART PAGINATION -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-info">
            Halaman <?php echo $current_page; ?> dari <?php echo $total_pages; ?>
            (Total: <?php echo $total_announcements; ?> pengumuman)
        </div>

        <div class="pagination">
            <!-- Previous Arrow -->
            <?php if ($show_prev): ?>
                <a href="?<?php echo get_url_params($current_page - 1); ?>" class="page-arrow" title="Previous">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <!-- First Page -->
            <?php if ($start_page > 1): ?>
                <a href="?<?php echo get_url_params(1); ?>" class="page-number">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Page Numbers -->
            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                <a href="?<?php echo get_url_params($i); ?>"
                    class="page-number <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>

            <!-- Last Page -->
            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="page-dots">...</span>
                <?php endif; ?>
                <a href="?<?php echo get_url_params($total_pages); ?>" class="page-number">
                    <?php echo $total_pages; ?>
                </a>
            <?php endif; ?>

            <!-- Next Arrow -->
            <?php if ($show_next): ?>
                <a href="?<?php echo get_url_params($current_page + 1); ?>" class="page-arrow" title="Next">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <footer class="modern-footer">
        <div class="footer-container">
            <div class="footer-main">
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
                    <p class="brand-motto">For Your Goals Beyond Horizon</p>
                </div>

                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="homepage1.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                        <li><a href="aboutuspage.php"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="profilemahasiswa.php"><i class="fas fa-chevron-right"></i> Profile</a></li>
                        <li><a href="logout.php"><i class="fas fa-chevron-right"></i> Logout</a></li>
                    </ul>
                </div>

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
                            class="social-link instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="https://youtube.com/@polibatamtv" target="_blank" class="social-link youtube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="https://www.polibatam.ac.id" target="_blank" class="social-link linkedin">
                            <i class="fas fa-globe"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="footer-bottom">
                <div class="copyright">© 2025 Politeknik Negeri Batam. All rights reserved.</div>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Sitemap</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="../js/homepage.js"></script>
</body>

</html>