<?php
session_start();
include '../database/pengumuman.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// --- 1. SETUP PARAMETER DEFAULT (SAMA SEPERTI HOMEPAGE) ---
$announcements_per_page = 6;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($current_page < 1)
    $current_page = 1;

$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_type = isset($_GET['filter']) && $_GET['filter'] !== 'All' ? $conn->real_escape_string($_GET['filter']) : '';
$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';

$offset = ($current_page - 1) * $announcements_per_page;

// --- 2. KONSTRUKSI QUERY (dengan JOIN ke favorites) ---
$where_clauses = ["f.user_id = ?"];
$bind_params = 's';
$bind_values = [$user_id];

// A. Filter Teks (Title, Type)
if (!empty($search_query)) {
    $where_clauses[] = "(p.title LIKE ? OR p.type LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $bind_params .= 'ss';
    $bind_values[] = $search_param;
    $bind_values[] = $search_param;
}

// B. Filter Kategori (Type)
if (!empty($filter_type)) {
    if (empty($search_query) || strpos($search_query, $filter_type) === false) {
        $where_clauses[] = "p.type = ?";
        $bind_params .= 's';
        $bind_values[] = $filter_type;
    }
}

// C. Filter Tanggal (Range)
if (!empty($start_date)) {
    $where_clauses[] = "p.date >= ?";
    $bind_params .= 's';
    $bind_values[] = $start_date;
}
if (!empty($end_date)) {
    $where_clauses[] = "p.date <= ?";
    $bind_params .= 's';
    $bind_values[] = $end_date;
}

$where_sql = " WHERE " . implode(" AND ", $where_clauses);

// --- 3. MENGHITUNG TOTAL FAVORITES ---
$count_query = "SELECT COUNT(*) AS total FROM favorites f 
                JOIN pengumuman p ON f.announcement_id = p.id" . $where_sql;
$count_stmt = $conn->prepare($count_query);

$refs = [];
foreach ($bind_values as $k => $v) {
    $refs[$k] = &$bind_values[$k];
}
array_unshift($refs, $bind_params);
call_user_func_array([$count_stmt, 'bind_param'], $refs);

$count_stmt->execute();
$total_announcements_result = $count_stmt->get_result()->fetch_assoc();
$total_announcements = $total_announcements_result['total'];
$total_pages = ceil($total_announcements / $announcements_per_page);
$count_stmt->close();

// --- 4. MENGAMBIL DATA FAVORITES DENGAN PAGINATION ---
$data_query = "SELECT p.id, p.title, p.type, p.date, p.image_path, p.document_path 
               FROM favorites f 
               JOIN pengumuman p ON f.announcement_id = p.id"
    . $where_sql
    . " ORDER BY f.created_at DESC LIMIT ?, ?";

$bind_params .= 'ii';
$bind_values[] = $offset;
$bind_values[] = $announcements_per_page;

$data_stmt = $conn->prepare($data_query);

$refs = [];
foreach ($bind_values as $k => $v) {
    $refs[$k] = &$bind_values[$k];
}
array_unshift($refs, $bind_params);
call_user_func_array([$data_stmt, 'bind_param'], $refs);

$data_stmt->execute();
$result = $data_stmt->get_result();
$favorites = $result->fetch_all(MYSQLI_ASSOC);

$data_stmt->close();
$conn->close();

// Helper function untuk URL params
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
    <title>SIPAk - My Favorites</title>
    <link rel="stylesheet" href="../css/profilemahasiswa.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            <a href="homepage1.php" class="nav-link">Home</a>
            <a href="aboutuspage.php" class="nav-link">About Us</a>
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle active" id="profile-dropdown-btn">Profile</a>
                <div class="dropdown-menu" id="profile-dropdown-menu">
                    <a href="profilemahasiswa.php" class="dropdown-item create-btn">
                        <i class="fas fa-bookmark"></i> Favorites
                    </a>

                    <?php
                    // 👇 HANYA TAMPILKAN MENU "ADD" JIKA ROLE = DOSEN
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'dosen'):
                        ?>
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

    <!-- SEARCH & FILTER SECTION (COPY DARI HOMEPAGE) -->
    <main class="main">
        <div style="padding: 0px 0 10px 0;">
            <?php
            // 👇 AMBIL NAMA DAN ROLE DARI SESSION
            $nama = isset($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : 'User';
            $role = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Pengguna';
            ?>

            <!-- 👇 PESAN SAPAAN -->
            <p style="color: #0b2b57; font-size: 20px; font-weight: bold; margin-top: 4px;">
                👋 Halo <?php echo htmlspecialchars($nama); ?>, kamu login sebagai
                <?php echo htmlspecialchars($role); ?>
            </p>

            <p style="color: #666; margin-left: 8px; margin-bottom: 20px;">
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
                <input type="date" id="startDateInput" name="start_date" class="date-input" title="Tanggal Mulai"
                    value="<?php echo htmlspecialchars($start_date); ?>" onchange="this.form.submit()" />

                <label for="endDateInput" class="date-label">Sampai:</label>
                <input type="date" id="endDateInput" name="end_date" class="date-input" title="Tanggal Akhir"
                    value="<?php echo htmlspecialchars($end_date); ?>" onchange="this.form.submit()" />
            </div>

            <div class="filter-dropdown-container">
                <button id="filterButton" class="filter" type="button" title="Filter Berdasarkan Kategori">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 5h18M6 12h12M10 19h4" stroke="#0b2b57" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>

                <div id="filterOptions" class="filter-options">
                    <a href="?<?php echo get_url_params('1'); ?>&filter=All" data-filter="All"
                        class="filter-option <?php echo empty($filter_type) ? 'active' : ''; ?>">Semua Kategori</a>

                    <?php
                    $categories = ["Jadwal", "Beasiswa", "Perubahan Kelas", "Karir", "Kemahasiswaan"];
                    foreach ($categories as $cat):
                        $link_params = $_GET;
                        $link_params['filter'] = $cat;
                        unset($link_params['page']);
                        $category_url = '?' . http_build_query($link_params);
                        ?>
                        <a href="<?php echo $category_url; ?>" data-filter="<?php echo htmlspecialchars($cat); ?>"
                            class="filter-option <?php echo ($filter_type === $cat) ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($cat); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </form>
    </main>

    <!-- CARD GRID -->
    <main class="container">
        <div class="card-grid">
            <?php if (!empty($favorites)): ?>
                <?php foreach ($favorites as $fav): ?>
                    <?php
                    $doc_url = $fav['document_path'] ?: '#';
                    $img_url = $fav['image_path'] ?: 'https://via.placeholder.com/300x180?text=' . urlencode($fav['type']);
                    ?>

                    <div class="announcement-card" data-id="<?php echo $fav['id']; ?>"
                        data-category="<?php echo htmlspecialchars($fav['type']); ?>"
                        data-date="<?php echo htmlspecialchars($fav['date']); ?>"
                        data-title="<?php echo htmlspecialchars($fav['title']); ?>">

                        <a href="<?php echo htmlspecialchars($doc_url); ?>" target="_blank" class="card-link">
                            <div class="card-image-box" style="
                                background-image: url('<?php echo htmlspecialchars($img_url); ?>');
                                background-size: cover;
                                background-position: center;
                            ">
                                <div class="card-actions">
                                    <?php if ($doc_url !== '#'): ?>
                                        <button class="action-btn download-btn" data-doc="<?php echo htmlspecialchars($doc_url); ?>"
                                            title="Download">
                                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M12 3v12m0 0l-4-4m4 4l4-4M5 21h14" stroke="white" stroke-width="2"
                                                    stroke-linecap="round" stroke-linejoin="round" />
                                            </svg>
                                        </button>
                                    <?php endif; ?>

                                    <button class="action-btn remove-btn" data-id="<?php echo $fav['id']; ?>"
                                        title="Remove from favorites">
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
                        <path d="M5 5v16l7-5 7 5V5a2 2 0 0 0-2-2H7a2 2 0 0 0-2 2z" stroke="#999" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <h3 style="color: #999; margin-bottom: 10px;">
                        <?php
                        if (!empty($search_query) || !empty($filter_type) || !empty($start_date) || !empty($end_date)) {
                            echo "Tidak ada hasil yang ditemukan";
                        } else {
                            echo "Belum ada favorites";
                        }
                        ?>
                    </h3>
                    <p style="color: #aaa; margin-bottom: 30px;">
                        <?php
                        if (!empty($search_query) || !empty($filter_type) || !empty($start_date) || !empty($end_date)) {
                            echo "Coba ubah filter atau kata kunci pencarian Anda";
                        } else {
                            echo "Klik tombol bookmark pada pengumuman di homepage untuk menambahkan ke favorites";
                        }
                        ?>
                    </p>
                    <a href="<?php echo (!empty($search_query) || !empty($filter_type) || !empty($start_date) || !empty($end_date)) ? 'profilemahasiswa.php' : 'homepage1.php'; ?>"
                        style="display: inline-block; padding: 12px 30px; background: #ff6347; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: 0.3s;">
                        <?php echo (!empty($search_query) || !empty($filter_type) || !empty($start_date) || !empty($end_date)) ? '🔄 Reset Filter' : '← Kembali ke Homepage'; ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- PAGINATION (SAMA SEPERTI HOMEPAGE) -->
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

    <script src="../js/profilemahasiswa.js"></script>
</body>

</html>