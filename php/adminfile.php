<?php
session_start();
include '../database/pengumuman.php';

// ========== STRICT ADMIN CHECK ==========
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: loginpage.php');
    exit();
}

// Regenerate session ID untuk security
if (!isset($_SESSION['admin_verified'])) {
    session_regenerate_id(true);
    $_SESSION['admin_verified'] = true;
}

// ========== HANDLE UPDATE FILE ==========
if (isset($_POST['update_file'])) {
    $id = (int) $_POST['edit_id'];
    $title = trim($_POST['edit_title']);
    $type = trim($_POST['edit_type']);
    $date = trim($_POST['edit_date']);

    // Image handling
    $old_image = $_POST['old_image'];
    $image_path = $old_image;

    if (!empty($_FILES['edit_image']['name'])) {
        $img_ext = strtolower(pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION));
        $allowed_img = ['jpg', 'jpeg', 'png'];

        if (!in_array($img_ext, $allowed_img)) {
            $error = "Format tidak diizinkan! Hanya JPG, JPEG, PNG.";
        } else {
            $new_img = 'img_' . time() . '.' . $img_ext;
            $img_dir = '../uploads/images/';

            if (!is_dir($img_dir)) {
                mkdir($img_dir, 0755, true);
            }

            $image_path = $img_dir . $new_img;

            if (move_uploaded_file($_FILES['edit_image']['tmp_name'], $image_path)) {
                // Delete old image
                if (!empty($old_image) && file_exists($old_image)) {
                    unlink($old_image);
                }
            }
        }
    }

    // Document handling
    $old_doc = $_POST['old_document'];
    $doc_path = $old_doc;

    if (!empty($_FILES['edit_document']['name'])) {
        $ext = strtolower(pathinfo($_FILES['edit_document']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];

        if (!in_array($ext, $allowed)) {
            $error = "Format tidak diizinkan! Hanya PDF, DOC, DOCX, XLS, XLSX.";
        } else {
            $new_name = 'doc_' . time() . '.' . $ext;
            $upload_dir = '../uploads/documents/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $doc_path = $upload_dir . $new_name;

            if (move_uploaded_file($_FILES['edit_document']['tmp_name'], $doc_path)) {
                // Delete old document
                if (!empty($old_doc) && file_exists($old_doc)) {
                    unlink($old_doc);
                }
            }
        }
    }

    // Update database with prepared statement
    if (!isset($error)) {
        $stmt = $conn->prepare("UPDATE pengumuman SET title=?, type=?, date=?, image_path=?, document_path=? WHERE id=?");
        $stmt->bind_param("sssssi", $title, $type, $date, $image_path, $doc_path, $id);

        if ($stmt->execute()) {
            $success = "Berkas Berhasil Diperbarui!";
        } else {
            $error = "Berkas Gagal Diperbarui: " . $conn->error;
        }
        $stmt->close();
    }
}

// ========== HANDLE DELETE FILE ==========
if (isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];

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

    if ($delete_stmt->execute()) {
        $success = "Berkas Berhasil Dihapus!";
    } else {
        $error = "Berkas Gagal Dihapus!";
    }
    $delete_stmt->close();
}

// ========== PAGINATION SETUP ==========
$files_per_page = 10;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($current_page < 1)
    $current_page = 1;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_type = isset($_GET['filter']) && $_GET['filter'] !== 'All' ? trim($_GET['filter']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

$offset = ($current_page - 1) * $files_per_page;

// ========== BUILD QUERY ==========
$where_clauses = [];
$bind_params = '';
$bind_values = [];

if (!empty($search_query)) {
    $where_clauses[] = "(title LIKE ? OR type LIKE ? OR created_by_name LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $bind_params .= 'sss';
    $bind_values[] = $search_param;
    $bind_values[] = $search_param;
    $bind_values[] = $search_param;
}

if (!empty($filter_type)) {
    $where_clauses[] = "type = ?";
    $bind_params .= 's';
    $bind_values[] = $filter_type;
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

// ========== COUNT TOTAL ==========
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
$total_files_result = $count_stmt->get_result()->fetch_assoc();
$total_files = $total_files_result['total'];
$total_pages = ceil($total_files / $files_per_page);
$count_stmt->close();

// ========== FETCH DATA ==========
$data_query = "SELECT id, title, type, date, image_path, document_path, created_by_name FROM pengumuman"
    . $where_sql
    . " ORDER BY date DESC LIMIT ?, ?";

$bind_params .= 'ii';
$bind_values[] = $offset;
$bind_values[] = $files_per_page;

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
$conn->close();

// ========== URL PARAMS HELPER ==========
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

// ========== SMART PAGINATION LOGIC ==========
$max_visible_pages = 3;
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
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPAk - Manajemen Berkas</title>
    <link rel="icon" type="image/png" href="../img/img_Politeknikbnw.png" />
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

        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <nav class="nav-menu">
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" id="profile-dropdown-btn"> Dasbor Admin
                </a>
                <div class="dropdown-menu" id="profile-dropdown-menu">
                    <a href="adminuser.php" class="dropdown-item user-btn">
                        <i class="fas fa-users"></i> Manajemen Pengguna
                    </a>
                    <a href="adminfile.php" class="dropdown-item file-btn">
                        <i class="fas fa-file-alt"></i> Manajemen Berkas
                    </a>
                    <a href="logout.php" class="dropdown-item logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Keluar
                    </a>
                </div>
            </div>
        </nav>

        <nav class="nav-menu-mobile">
            <a href="adminuser.php" class="nav-link-mobile">
                <i class="fas fa-users"></i> Manajemen Pengguna
            </a>
            <a href="adminfile.php" class="nav-link-mobile">
                <i class="fas fa-file-alt"></i> Manajemen Berkas
            </a>
            <a href="logout.php" class="nav-link-mobile">
                <i class="fas fa-sign-out-alt"></i> Keluar
            </a>
        </nav>
    </header>

    <div class="container">
        <main class="main">
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form class="searchbar" method="GET" action="adminfile.php">
                <div class="searchbox">
                    <span class="search-icon">🔍</span>
                    <input id="searchInput" name="search" placeholder="Cari Berkas (Judul, Jenis, Pembuat, dll.)"
                        value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" style="display:none;"></button>
                </div>

                <div class="date-filter-group">
                    <label for="startDateInput" class="date-label">Dari:</label>
                    <input type="date" id="startDateInput" name="start_date" class="date-input"
                        value="<?php echo htmlspecialchars($start_date); ?>" onchange="this.form.submit()">

                    <label for="endDateInput" class="date-label">Sampai:</label>
                    <input type="date" id="endDateInput" name="end_date" class="date-input"
                        value="<?php echo htmlspecialchars($end_date); ?>" onchange="this.form.submit()">
                </div>

                <div class="filter-dropdown-container">
                    <button id="filterButton" class="filter" type="button" title="Filter Berdasarkan Kategori">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M3 5h18M6 12h12M10 19h4" stroke="#0b2b57" stroke-width="2"
                                stroke-linecap="round" />
                        </svg>
                    </button>

                    <div id="filterOptions" class="filter-options">
                        <a href="?<?php echo get_url_params(1); ?>&filter=All"
                            class="filter-option <?php echo empty($filter_type) ? 'active' : ''; ?>">
                            Semua Kategori
                        </a>
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

            <div class="table-header">
                <div class="th-type">JENIS</div>
                <div class="th-title">JUDUL</div>
                <div class="th-date">TANGGAL</div>
                <div class="th-creator">DIBUAT OLEH</div>
                <div class="th-document">DOKUMEN</div>
                <div class="th-actions">AKSI</div>
            </div>

            <div class="table-body">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="table-row">
                            <div class="td-type">
                                <span class="type-badge"><?php echo htmlspecialchars($announcement['type']); ?></span>
                            </div>

                            <div class="td-title">
                                <?php echo htmlspecialchars($announcement['title']); ?>
                            </div>

                            <div class="td-date">
                                <?php echo date('d M Y', strtotime($announcement['date'])); ?>
                            </div>

                            <div class="td-creator">
                                <?php echo htmlspecialchars($announcement['created_by_name'] ?? 'Tidak Diketahui'); ?>
                            </div>

                            <div class="td-document">
                                <?php if (!empty($announcement['document_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($announcement['document_path']); ?>" target="_blank"
                                        class="doc-link">
                                        📄 Lihat
                                    </a>
                                <?php else: ?>
                                    <span class="no-doc">Tidak Ada Dok</span>
                                <?php endif; ?>
                            </div>

                            <div class="td-actions">
                                <button type="button" class="btn-edit"
                                    onclick='openEditModal(<?php echo json_encode($announcement); ?>)'>
                                    Ubah
                                </button>
                                <form method="POST" action="adminfile.php" style="display:inline;"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengumuman ini?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" class="btn-remove">Hapus</button>
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
                        <h3>Tidak ada berkas ditemukan</h3>
                        <p>Silakan coba kata kunci atau filter yang berbeda</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- SMART PAGINATION -->
    <?php if ($total_pages > 1): ?>
        <div class="pagination-info">
            Halaman <?php echo $current_page; ?> dari <?php echo $total_pages; ?>
            (Total: <?php echo $total_files; ?> Berkas)
        </div>

        <div class="pagination">
            <!-- Previous Arrow -->
            <?php if ($show_prev): ?>
                <a href="?<?php echo get_url_params($current_page - 1); ?>" class="page-arrow" title="Sebelumnya">
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
                <a href="?<?php echo get_url_params($current_page + 1); ?>" class="page-arrow" title="Selanjutnya">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Ubah Pengumuman</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>

            <form method="POST" action="adminfile.php" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="hidden" name="old_image" id="old_image">
                <input type="hidden" name="old_document" id="old_document">

                <div class="form-group">
                    <label>Judul</label>
                    <input type="text" name="edit_title" id="edit_title" required>
                </div>

                <div class="form-group">
                    <label>Jenis</label>
                    <select name="edit_type" id="edit_type" required>
                        <option value="Jadwal">Jadwal</option>
                        <option value="Beasiswa">Beasiswa</option>
                        <option value="Perubahan Kelas">Perubahan Kelas</option>
                        <option value="Karir">Karir</option>
                        <option value="Kemahasiswaan">Kemahasiswaan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="edit_date" id="edit_date" required>
                </div>

                <div class="form-group">
                    <label>Foto (Opsional)</label>
                    <input type="file" name="edit_image" accept="image/*">
                    <small>Kosongkan jika tidak ingin mengubah foto</small>
                </div>

                <div class="form-group">
                    <label>Dokumen (Opsional)</label>
                    <input type="file" name="edit_document" accept=".pdf,.doc,.docx,.xls,.xlsx">
                    <small>Kosongkan jika tidak ingin mengubah dokumen</small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" name="update_file" class="btn-submit">Perbarui Berkas</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/adminfile.js"></script>
</body>

</html>