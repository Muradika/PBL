<?php
session_start();
include '../database/pengumuman.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: loginpage.php');
    exit();
}

// Handle delete file
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    // Get file paths before deleting
    $stmt = $conn->prepare("SELECT image_path, document_path FROM pengumuman WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $file_data = $result->fetch_assoc();
    $stmt->close();

    // Delete files from server
    if ($file_data) {
        if (!empty($file_data['image_path']) && file_exists($file_data['image_path'])) {
            unlink($file_data['image_path']);
        }
        if (!empty($file_data['document_path']) && file_exists($file_data['document_path'])) {
            unlink($file_data['document_path']);
        }
    }

    // Delete from database
    $delete_stmt = $conn->prepare("DELETE FROM pengumuman WHERE id = ?");
    $delete_stmt->bind_param("i", $delete_id);
    $delete_stmt->execute();
    $delete_stmt->close();

    header('Location: adminfile.php?deleted=1');
    exit();
}

// --- FILTERS ---
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_type = isset($_GET['filter']) && $_GET['filter'] !== 'All' ? $conn->real_escape_string($_GET['filter']) : '';
$start_date = isset($_GET['start_date']) ? $conn->real_escape_string($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? $conn->real_escape_string($_GET['end_date']) : '';

// Build WHERE clause
$where_clauses = [];

// Search filter
if (!empty($search_query)) {
    $where_clauses[] = "(title LIKE '%$search_query%' OR type LIKE '%$search_query%' OR created_by_name LIKE '%$search_query%')";
}

// Category filter
if (!empty($filter_type)) {
    $where_clauses[] = "type = '$filter_type'";
}

// Date range filter
if (!empty($start_date)) {
    $where_clauses[] = "date >= '$start_date'";
}
if (!empty($end_date)) {
    $where_clauses[] = "date <= '$end_date'";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Query
$query = "SELECT id, title, type, date, image_path, document_path, created_by_name FROM pengumuman" . $where_sql . " ORDER BY date DESC";
$result = $conn->query($query);
$announcements = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

$conn->close();

// Helper function untuk URL params
function get_url_params()
{
    $params = $_GET;
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
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPAk - File Management</title>
    <link rel="stylesheet" href="../css/adminfile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <header class="navbar">
        <div class="logo-brand">
            <img src="../img/img_Politeknikbnw.png" alt="Logo Polibatam" class="nav-logo">
            <div class="system-title">
                Sistem Informasi Pengumuman <br>
                Akademik <span class="online-tag">Online</span>
            </div>
        </div>
        <nav class="nav-menu">
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" id="profile-dropdown-btn">Admin Dashboard</a>
                <div class="dropdown-menu" id="profile-dropdown-menu">
                    <a href="adminuser.php" class="dropdown-item user-btn"><i class="fas fa-user"></i>User
                        Management</a>
                    <a href="adminfile.php" class="dropdown-item file-btn"><i class="fas fa-file"></i>File
                        Management</a>
                    <a href="logout.php" class="dropdown-item logout-btn"><i class="fas fa-sign-out-alt"></i>Log Out</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <main class="main">
            <!-- SEARCH & FILTERS -->
            <form class="searchbar" method="GET" action="adminfile.php">
                <!-- Search Box -->
                <div class="searchbox">
                    <span class="search-icon">🔍</span>
                    <input id="searchInput" name="search" placeholder="Search File (Title, Type, Date, Creator)"
                        value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" style="display:none;"></button>
                </div>

                <!-- Date Range Filter -->
                <div class="date-filter-group">
                    <label for="startDateInput" class="date-label">Dari:</label>
                    <input type="date" id="startDateInput" name="start_date" class="date-input"
                        value="<?php echo htmlspecialchars($start_date); ?>" onchange="this.form.submit()">

                    <label for="endDateInput" class="date-label">Sampai:</label>
                    <input type="date" id="endDateInput" name="end_date" class="date-input"
                        value="<?php echo htmlspecialchars($end_date); ?>" onchange="this.form.submit()">
                </div>

                <!-- Category Filter Dropdown -->
                <div class="filter-dropdown-container">
                    <button id="filterButton" class="filter" type="button" title="Filter Berdasarkan Kategori">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M3 5h18M6 12h12M10 19h4" stroke="#0b2b57" stroke-width="2"
                                stroke-linecap="round" />
                        </svg>
                    </button>

                    <div id="filterOptions" class="filter-options">
                        <a href="?<?php echo get_url_params(); ?>&filter=All"
                            class="filter-option <?php echo empty($filter_type) ? 'active' : ''; ?>">
                            Semua Kategori
                        </a>
                        <?php
                        $categories = ["Jadwal", "Beasiswa", "Perubahan Kelas", "Karir", "Kemahasiswaan"];
                        foreach ($categories as $cat):
                            $link_params = $_GET;
                            $link_params['filter'] = $cat;
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

            <!-- TABLE HEADER -->
            <div class="table-header">
                <div class="th-type">TYPE</div>
                <div class="th-title">TITLE</div>
                <div class="th-date">DATE</div>
                <div class="th-creator">CREATED BY</div>
                <div class="th-document">DOCUMENT</div>
                <div class="th-actions">ACTIONS</div>
            </div>

            <!-- TABLE ROWS -->
            <div class="table-body">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="table-row">
                            <!-- Column 1: Type Badge -->
                            <div class="td-type">
                                <span class="type-badge"><?php echo htmlspecialchars($announcement['type']); ?></span>
                            </div>

                            <!-- Column 2: Title -->
                            <div class="td-title">
                                <?php echo htmlspecialchars($announcement['title']); ?>
                            </div>

                            <!-- Column 3: Date -->
                            <div class="td-date">
                                <?php echo date('d M Y', strtotime($announcement['date'])); ?>
                            </div>

                            <!-- Column 4: Creator -->
                            <div class="td-creator">
                                <?php
                                $creator = $announcement['created_by_name'] ?? 'Unknown';
                                echo htmlspecialchars($creator);
                                ?>
                            </div>

                            <!-- Column 5: Document -->
                            <div class="td-document">
                                <?php if (!empty($announcement['document_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($announcement['document_path']); ?>" target="_blank"
                                        class="doc-link">
                                        View Doc
                                    </a>
                                <?php else: ?>
                                    <span class="no-doc">No document</span>
                                <?php endif; ?>
                            </div>

                            <!-- Column 6: Actions -->
                            <div class="td-actions">
                                <form method="POST" action="adminfile.php"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus file ini?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2">
                            <path
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <h3>Tidak ada file yang ditemukan</h3>
                        <p>Coba ubah filter atau kata kunci pencarian Anda</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Dropdown toggle
        document.getElementById('profile-dropdown-btn').addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector('.dropdown').classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelector('.dropdown').classList.remove('active');
            }
        });

        // Filter dropdown toggle
        const filterButton = document.getElementById('filterButton');
        const filterContainer = document.querySelector('.filter-dropdown-container');

        filterButton.addEventListener('click', function (e) {
            e.stopPropagation();
            filterContainer.classList.toggle('show');
        });

        // Close filter dropdown when clicking outside
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.filter-dropdown-container')) {
                filterContainer.classList.remove('show');
            }
        });
    </script>
</body>

</html>