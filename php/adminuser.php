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

// ========== HANDLE ADD USER ==========
if (isset($_POST['add_user'])) {
    $nama = trim($_POST['nama_lengkap']);
    $id_number = trim($_POST['id_number']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate ID number (10 digits only)
    if (!preg_match('/^\d{10}$/', $id_number)) {
        $error = "Nomor ID harus 10 digit angka!";
    } else {
        // Check if ID number already exists
        $check_id = $conn->prepare("SELECT id FROM user WHERE id = ?");
        $check_id->bind_param("s", $id_number);
        $check_id->execute();
        $check_id->store_result();

        if ($check_id->num_rows > 0) {
            $error = "Nomor ID sudah terdaftar!";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $insert = $conn->prepare("INSERT INTO user (id, nama_lengkap, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $id_number, $nama, $email, $hashed_password, $role);

            if ($insert->execute()) {
                $success = "Pengguna Berhasil Ditambahkan!";
            } else {
                $error = "Gagal Menambahkan Pengguna: " . $conn->error;
            }
            $insert->close();
        }
        $check_id->close();
    }
}

// ========== HANDLE EDIT USER ==========
if (isset($_POST['edit_user'])) {
    $id = trim($_POST['edit_id']);
    $nama = trim($_POST['edit_nama']);
    $email = trim($_POST['edit_email']);
    $role = $_POST['edit_role'];

    // Update password only if provided
    if (!empty($_POST['edit_password'])) {
        $hashed_password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE user SET nama_lengkap = ?, email = ?, password = ?, role = ? WHERE id = ?");
        $update->bind_param("sssss", $nama, $email, $hashed_password, $role, $id);

        $success = "Pengguna Berhasil Diperbarui dengan Kata Sandi Baru!";
    } else {
        $update = $conn->prepare("UPDATE user SET nama_lengkap = ?, email = ?, role = ? WHERE id = ?");
        $update->bind_param("ssss", $nama, $email, $role, $id);

        $success = "Pengguna Berhasil Diperbarui!";
    }

    if (!$update->execute()) {
        $error = "Gagal Memperbarui Pengguna: " . $conn->error;
        $success = null;
    }
    $update->close();
}

// ========== HANDLE DELETE USER ==========
if (isset($_POST['delete_id'])) {
    $delete_id = trim($_POST['delete_id']);

    // Prevent deleting own account
    if ($delete_id == $_SESSION['user_id']) {
        $error = "⚠️ Tidak dapat menghapus akun Anda sendiri!";
    } else {
        $delete = $conn->prepare("DELETE FROM user WHERE id = ?");
        $delete->bind_param("s", $delete_id);

        if ($delete->execute()) {
            $success = "Pengguna Berhasil Dihapus!";
        } else {
            $error = "Gagal Menghapus Pengguna: " . $conn->error;
        }
        $delete->close();
    }
}

// ========== PAGINATION SETUP ==========
$users_per_page = 6;
$current_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($current_page < 1)
    $current_page = 1;

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['filter']) && $_GET['filter'] !== 'All' ? trim($_GET['filter']) : '';

$offset = ($current_page - 1) * $users_per_page;

// ========== BUILD QUERY ==========
$where_clauses = [];
$bind_params = '';
$bind_values = [];

if (!empty($search_query)) {
    $where_clauses[] = "(nama_lengkap LIKE ? OR email LIKE ? OR id LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $bind_params .= 'sss';
    $bind_values[] = $search_param;
    $bind_values[] = $search_param;
    $bind_values[] = $search_param;
}

if (!empty($filter_role)) {
    $where_clauses[] = "role = ?";
    $bind_params .= 's';
    $bind_values[] = $filter_role;
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// ========== COUNT TOTAL ==========
$count_query = "SELECT COUNT(*) AS total FROM user" . $where_sql;
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
$total_users_result = $count_stmt->get_result()->fetch_assoc();
$total_users = $total_users_result['total'];
$total_pages = ceil($total_users / $users_per_page);
$count_stmt->close();

// ========== FETCH DATA ==========
$data_query = "SELECT id, nama_lengkap, email, role FROM user"
    . $where_sql
    . " ORDER BY id ASC LIMIT ?, ?";

$bind_params .= 'ii';
$bind_values[] = $offset;
$bind_values[] = $users_per_page;

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
$users = $result->fetch_all(MYSQLI_ASSOC);

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
    <title>SIPAk - Manajemen Pengguna</title>
    <link rel="icon" type="image/png" href="../img/img_Politeknikbnw.png" />
    <link rel="stylesheet" href="../css/adminuser.css">
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

            <form class="searchbar" method="GET" action="adminuser.php">
                <div class="searchbox">
                    <span class="search-icon">🔍</span>
                    <input id="searchInput" name="search" placeholder="Cari Pengguna (Nama, Email, Nomor ID, dll.)"
                        value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" style="display:none;"></button>
                </div>

                <button type="button" class="btn-add-user" onclick="openAddModal()">
                    <span class="add-icon">+</span> Tambah Pengguna
                </button>

                <div class="filter-dropdown-container">
                    <button id="filterButton" class="filter" type="button" title="Filter Berdasarkan Role">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M3 5h18M6 12h12M10 19h4" stroke="#0b2b57" stroke-width="2"
                                stroke-linecap="round" />
                        </svg>
                    </button>

                    <div id="filterOptions" class="filter-options">
                        <a href="?<?php echo get_url_params(1); ?>&filter=All"
                            class="filter-option <?php echo empty($filter_role) ? 'active' : ''; ?>">
                            Semua Role
                        </a>
                        <?php
                        $roles = ["admin", "mahasiswa", "dosen"];
                        foreach ($roles as $role):
                            $link_params = $_GET;
                            $link_params['filter'] = $role;
                            unset($link_params['page']);
                            $role_url = '?' . http_build_query($link_params);
                            ?>
                            <a href="<?php echo $role_url; ?>"
                                class="filter-option <?php echo ($filter_role === $role) ? 'active' : ''; ?>">
                                <?php echo ucfirst($role); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>

            <div class="table-header">
                <div class="th-name">NAMA</div>
                <div class="th-id">NOMOR ID</div>
                <div class="th-email">EMAIL</div>
                <div class="th-role">ROLE</div>
                <div class="th-actions">AKSI</div>
            </div>

            <div class="table-body">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <div class="table-row">
                            <div class="td-name">
                                <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                            </div>

                            <div class="td-id">
                                <span class="id-badge"><?php echo htmlspecialchars($user['id']); ?></span>
                            </div>

                            <div class="td-email">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>

                            <div class="td-role">
                                <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>

                            <div class="td-actions">
                                <button type="button" class="btn-edit"
                                    onclick='openEditModal(<?php echo json_encode($user); ?>)'>
                                    Ubah
                                </button>
                                <form method="POST" action="adminuser.php" style="display:inline;"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus pengguna ini?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn-remove">Hapus</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" />
                        </svg>
                        <h3>Tidak ada pengguna ditemukan</h3>
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
            (Total: <?php echo $total_users; ?> Pengguna)
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

    <!-- ADD USER MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Tambah Pengguna Baru</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST" action="adminuser.php">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" required placeholder="Masukkan nama lengkap">
                </div>
                <div class="form-group">
                    <label>Nomor ID (10 digit)</label>
                    <input type="text" name="id_number" required pattern="\d{10}"
                        placeholder="Masukkan nomor ID 10 digit" maxlength="10">
                    <small>Harus tepat 10 digit</small>
                </div>
                <div class="form-group">
                    <label>Alamat Email</label>
                    <input type="email" name="email" required placeholder="Masukkan alamat email">
                </div>
                <div class="form-group">
                    <label>Kata Sandi</label>
                    <input type="password" name="password" required placeholder="Masukkan kata sandi" minlength="6">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Pilih Role</option>
                        <option value="admin">Admin</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">Batal</button>
                    <button type="submit" name="add_user" class="btn-submit">Tambah Pengguna</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT USER MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Ubah Pengguna</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="adminuser.php">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="edit_nama" id="edit_nama" required placeholder="Masukkan nama lengkap">
                </div>
                <div class="form-group">
                    <label>Nomor ID</label>
                    <input type="text" id="edit_id_display" disabled placeholder="Nomor ID (tidak dapat diubah)">
                    <small>Nomor ID tidak dapat diubah</small>
                </div>
                <div class="form-group">
                    <label>Alamat Email</label>
                    <input type="email" name="edit_email" id="edit_email" required placeholder="Masukkan alamat email">
                </div>
                <div class="form-group">
                    <label>Kata Sandi (kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" name="edit_password" id="edit_password"
                        placeholder="Masukkan kata sandi baru (opsional)" minlength="6">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="edit_role" id="edit_role" required>
                        <option value="admin">Admin</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Batal</button>
                    <button type="submit" name="edit_user" class="btn-submit">Perbarui Pengguna</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/adminuser.js"></script>
</body>

</html>