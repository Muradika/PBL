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
        $error = "ID Number harus 10 digit angka!";
    } else {
        // Check if ID number already exists
        $check_id = $conn->prepare("SELECT id FROM user WHERE id = ?");
        $check_id->bind_param("s", $id_number);
        $check_id->execute();
        $check_id->store_result();

        if ($check_id->num_rows > 0) {
            $error = "ID Number sudah terdaftar!";
        } else {
            // ✅ HASH PASSWORD SEBELUM DISIMPAN
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user
            $insert = $conn->prepare("INSERT INTO user (id, nama_lengkap, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $id_number, $nama, $email, $hashed_password, $role);

            if ($insert->execute()) {
                $success = "User Added!";
            } else {
                $error = "Failed Added User: " . $conn->error;
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
        // ✅ HASH PASSWORD BARU
        $hashed_password = password_hash($_POST['edit_password'], PASSWORD_DEFAULT);

        $update = $conn->prepare("UPDATE user SET nama_lengkap = ?, email = ?, password = ?, role = ? WHERE id = ?");
        $update->bind_param("sssss", $nama, $email, $hashed_password, $role, $id);

        $success = "User Succesfully Updated New Password!";
    } else {
        $update = $conn->prepare("UPDATE user SET nama_lengkap = ?, email = ?, role = ? WHERE id = ?");
        $update->bind_param("ssss", $nama, $email, $role, $id);

        $success = "User Added!";
    }

    if (!$update->execute()) {
        $error = "Failed Updated User: " . $conn->error;
        $success = null;
    }
    $update->close();
}

// ========== HANDLE DELETE USER ==========
if (isset($_POST['delete_id'])) {
    $delete_id = trim($_POST['delete_id']);

    // Prevent deleting own account
    if ($delete_id == $_SESSION['user_id']) {
        $error = "⚠️ Can't delete your own account!";
    } else {
        $delete = $conn->prepare("DELETE FROM user WHERE id = ?");
        $delete->bind_param("s", $delete_id);

        if ($delete->execute()) {
            $success = "User Succesfully Deleted!";
        } else {
            $error = "Failed Removed User: " . $conn->error;
        }
        $delete->close();
    }
}

// ========== FILTERS WITH PREPARED STATEMENTS ==========
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_role = isset($_GET['filter']) && $_GET['filter'] !== 'All' ? trim($_GET['filter']) : '';

// Build query with prepared statements
$where_clauses = [];
$params = [];
$types = '';

if (!empty($search_query)) {
    $where_clauses[] = "(nama_lengkap LIKE ? OR email LIKE ? OR id LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($filter_role)) {
    $where_clauses[] = "role = ?";
    $params[] = $filter_role;
    $types .= 's';
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Query with prepared statement
$query = "SELECT id, nama_lengkap, email, role FROM user" . $where_sql . " ORDER BY id ASC";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPAk - User Management</title>
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

        <!-- Hamburger Menu Button (Mobile Only) -->
        <div class="hamburger">
            <span></span>
            <span></span>
            <span></span>
        </div>

        <!-- Desktop Navigation with Dropdown -->
        <nav class="nav-menu">
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" id="profile-dropdown-btn"> Admin Dashboard
                </a>
                <div class="dropdown-menu" id="profile-dropdown-menu">
                    <a href="adminuser.php" class="dropdown-item user-btn">
                        <i class="fas fa-users"></i> User Management
                    </a>
                    <a href="adminfile.php" class="dropdown-item file-btn">
                        <i class="fas fa-file-alt"></i> File Management
                    </a>
                    <a href="logout.php" class="dropdown-item logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Log Out
                    </a>
                </div>
            </div>
        </nav>

        <!-- Mobile Navigation (Direct Links) -->
        <nav class="nav-menu-mobile">
            <a href="adminuser.php" class="nav-link-mobile">
                <i class="fas fa-users"></i> User Management
            </a>
            <a href="adminfile.php" class="nav-link-mobile">
                <i class="fas fa-file-alt"></i> File Management
            </a>
            <a href="logout.php" class="nav-link-mobile">
                <i class="fas fa-sign-out-alt"></i> Log Out
            </a>
        </nav>
    </header>

    <div class="container">
        <main class="main">
            <!-- Success/Error Messages -->
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- SEARCH & FILTERS -->
            <form class="searchbar" method="GET" action="adminuser.php">
                <!-- Search Box -->
                <div class="searchbox">
                    <span class="search-icon">🔍</span>
                    <input id="searchInput" name="search" placeholder="Search User (Name, Email, ID Number, etc.)"
                        value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" style="display:none;"></button>
                </div>

                <!-- Add User Button -->
                <button type="button" class="btn-add-user" onclick="openAddModal()">
                    <span class="add-icon">+</span> Add User
                </button>

                <!-- Role Filter Dropdown -->
                <div class="filter-dropdown-container">
                    <button id="filterButton" class="filter" type="button" title="Filter Berdasarkan Role">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                            <path d="M3 5h18M6 12h12M10 19h4" stroke="#0b2b57" stroke-width="2"
                                stroke-linecap="round" />
                        </svg>
                    </button>

                    <div id="filterOptions" class="filter-options">
                        <a href="?search=<?php echo urlencode($search_query); ?>&filter=All"
                            class="filter-option <?php echo empty($filter_role) ? 'active' : ''; ?>">
                            Semua Role
                        </a>
                        <?php
                        $roles = ["admin", "mahasiswa", "dosen"];
                        foreach ($roles as $role):
                            ?>
                            <a href="?search=<?php echo urlencode($search_query); ?>&filter=<?php echo $role; ?>"
                                class="filter-option <?php echo ($filter_role === $role) ? 'active' : ''; ?>">
                                <?php echo ucfirst($role); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </form>

            <!-- TABLE HEADER -->
            <div class="table-header">
                <div class="th-name">NAME</div>
                <div class="th-id">ID NUMBER</div>
                <div class="th-email">EMAIL</div>
                <div class="th-role">ROLE</div>
                <div class="th-actions">ACTIONS</div>
            </div>

            <!-- TABLE ROWS -->
            <div class="table-body">
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <div class="table-row">
                            <!-- Name -->
                            <div class="td-name">
                                <?php echo htmlspecialchars($user['nama_lengkap']); ?>
                            </div>

                            <!-- ID Number -->
                            <div class="td-id">
                                <span class="id-badge"><?php echo htmlspecialchars($user['id']); ?></span>
                            </div>

                            <!-- Email -->
                            <div class="td-email">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>

                            <!-- Role -->
                            <div class="td-role">
                                <span class="role-badge role-<?php echo strtolower($user['role']); ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </div>

                            <!-- Actions -->
                            <div class="td-actions">
                                <button type="button" class="btn-edit"
                                    onclick='openEditModal(<?php echo json_encode($user); ?>)'>
                                    Edit
                                </button>
                                <form method="POST" action="adminuser.php" style="display:inline;"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus user ini?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2M12 11a4 4 0 100-8 4 4 0 000 8z" />
                        </svg>
                        <h3>No users found</h3>
                        <p>Please try different search or filter criteria</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- ADD USER MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New User</h2>
                <span class="close" onclick="closeAddModal()">&times;</span>
            </div>
            <form method="POST" action="adminuser.php">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="nama_lengkap" required placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label>ID Number (10 digits)</label>
                    <input type="text" name="id_number" required pattern="\d{10}" placeholder="Enter 10-digit ID number"
                        maxlength="10">
                    <small>Must be exactly 10 digits</small>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required placeholder="Enter email address">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password" minlength="6">
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="admin">Admin</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeAddModal()">Cancel</button>
                    <button type="submit" name="add_user" class="btn-submit">Add User</button>
                </div>
            </form>
        </div>
    </div>

    <!-- EDIT USER MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-edit"></i> Edit User</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>
            <form method="POST" action="adminuser.php">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="edit_nama" id="edit_nama" required placeholder="Enter full name">
                </div>
                <div class="form-group">
                    <label>ID Number</label>
                    <input type="text" id="edit_id_display" disabled placeholder="ID Number (cannot be changed)">
                    <small>ID Number cannot be changed</small>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="edit_email" id="edit_email" required placeholder="Enter email address">
                </div>
                <div class="form-group">
                    <label>Password (leave blank to keep current)</label>
                    <input type="password" name="edit_password" id="edit_password"
                        placeholder="Enter new password (optional)" minlength="6">
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
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="edit_user" class="btn-submit">Update User</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/adminuser.js"></script>
</body>

</html>