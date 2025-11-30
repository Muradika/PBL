<?php
session_start();
include '../database/pengumuman.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: loginpage.php');
    exit();
}

// Handle Add User
if (isset($_POST['add_user'])) {
    $nama = $conn->real_escape_string($_POST['nama_lengkap']);
    $id_number = $conn->real_escape_string($_POST['id_number']);
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password']; // No hashing as requested
    $role = $conn->real_escape_string($_POST['role']);

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
            // Insert new user
            $insert = $conn->prepare("INSERT INTO user (id, nama_lengkap, email, password, role) VALUES (?, ?, ?, ?, ?)");
            $insert->bind_param("sssss", $id_number, $nama, $email, $password, $role);

            if ($insert->execute()) {
                $success = "User berhasil ditambahkan!";
            } else {
                $error = "Gagal menambahkan user: " . $conn->error;
            }
            $insert->close();
        }
        $check_id->close();
    }
}

// Handle Edit User
if (isset($_POST['edit_user'])) {
    $id = $conn->real_escape_string($_POST['edit_id']);
    $nama = $conn->real_escape_string($_POST['edit_nama']);
    $email = $conn->real_escape_string($_POST['edit_email']);
    $role = $conn->real_escape_string($_POST['edit_role']);

    // Update password only if provided
    if (!empty($_POST['edit_password'])) {
        $password = $_POST['edit_password'];
        $update = $conn->prepare("UPDATE user SET nama_lengkap = ?, email = ?, password = ?, role = ? WHERE id = ?");
        $update->bind_param("sssss", $nama, $email, $password, $role, $id);
    } else {
        $update = $conn->prepare("UPDATE user SET nama_lengkap = ?, email = ?, role = ? WHERE id = ?");
        $update->bind_param("ssss", $nama, $email, $role, $id);
    }

    if ($update->execute()) {
        $success = "User berhasil diupdate!";
    } else {
        $error = "Gagal update user: " . $conn->error;
    }
    $update->close();
}

// Handle Delete User
if (isset($_POST['delete_id'])) {
    $delete_id = $conn->real_escape_string($_POST['delete_id']);

    // Prevent deleting own account
    if ($delete_id == $_SESSION['user_id']) {
        $error = "Tidak dapat menghapus akun sendiri!";
    } else {
        $delete = $conn->prepare("DELETE FROM user WHERE id = ?");
        $delete->bind_param("s", $delete_id);

        if ($delete->execute()) {
            $success = "User berhasil dihapus!";
        } else {
            $error = "Gagal menghapus user: " . $conn->error;
        }
        $delete->close();
    }
}

// --- FILTERS ---
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$filter_role = isset($_GET['filter']) && $_GET['filter'] !== 'All' ? $conn->real_escape_string($_GET['filter']) : '';

// Build WHERE clause
$where_clauses = [];

// Search filter
if (!empty($search_query)) {
    $where_clauses[] = "(nama_lengkap LIKE '%$search_query%' OR email LIKE '%$search_query%' OR id LIKE '%$search_query%')";
}

// Role filter
if (!empty($filter_role)) {
    $where_clauses[] = "role = '$filter_role'";
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Query
$query = "SELECT id, nama_lengkap, email, role FROM user" . $where_sql . " ORDER BY id ASC";
$result = $conn->query($query);
$users = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

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
                    <input id="searchInput" name="search" placeholder="Search User (Name, Email, ID Number)"
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
                        <h3>Tidak ada user yang ditemukan</h3>
                        <p>Coba ubah filter atau kata kunci pencarian Anda</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- ADD USER MODAL -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New User</h2>
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
                    <input type="password" name="password" required placeholder="Enter password">
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
                <h2>Edit User</h2>
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
                        placeholder="Enter new password (optional)">
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

    <script>
        // Dropdown toggle
        document.getElementById('profile-dropdown-btn').addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector('.dropdown').classList.toggle('active');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelector('.dropdown').classList.remove('active');
            }
        });

        // Filter dropdown
        const filterButton = document.getElementById('filterButton');
        const filterContainer = document.querySelector('.filter-dropdown-container');

        filterButton.addEventListener('click', function (e) {
            e.stopPropagation();
            filterContainer.classList.toggle('show');
        });

        document.addEventListener('click', function (e) {
            if (!e.target.closest('.filter-dropdown-container')) {
                filterContainer.classList.remove('show');
            }
        });

        // Add Modal Functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Edit Modal Functions
        function openEditModal(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_id_display').value = user.id;
            document.getElementById('edit_nama').value = user.nama_lengkap;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_password').value = '';

            document.getElementById('editModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const addModal = document.getElementById('addModal');
            const editModal = document.getElementById('editModal');

            if (event.target == addModal) {
                closeAddModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function () {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>

</html>