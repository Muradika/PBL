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
            $error = "Format gambar tidak diizinkan! Hanya JPG, JPEG, PNG.";
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
        $allowed = ['pdf', 'doc', 'docx'];

        if (!in_array($ext, $allowed)) {
            $error = "Format dokumen tidak diizinkan! Hanya PDF, DOC, DOCX.";
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
            $success = "File Updated!";
        } else {
            $error = "File Failed to be Updated: " . $conn->error;
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
        $success = "File Removed!";
    } else {
        $error = "File Failed to be Removed!";
    }
    $delete_stmt->close();
}

// ========== FILTERS WITH PREPARED STATEMENTS ==========
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_type = isset($_GET['filter']) && $_GET['filter'] !== 'All' ? trim($_GET['filter']) : '';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Build query with prepared statements
$where_clauses = [];
$params = [];
$types = '';

if (!empty($search_query)) {
    $where_clauses[] = "(title LIKE ? OR type LIKE ? OR created_by_name LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

if (!empty($filter_type)) {
    $where_clauses[] = "type = ?";
    $params[] = $filter_type;
    $types .= 's';
}

if (!empty($start_date)) {
    $where_clauses[] = "date >= ?";
    $params[] = $start_date;
    $types .= 's';
}

if (!empty($end_date)) {
    $where_clauses[] = "date <= ?";
    $params[] = $end_date;
    $types .= 's';
}

$where_sql = count($where_clauses) > 0 ? " WHERE " . implode(" AND ", $where_clauses) : "";

// Query with prepared statement
$query = "SELECT id, title, type, date, image_path, document_path, created_by_name FROM pengumuman" . $where_sql . " ORDER BY date DESC";
$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$announcements = [];

while ($row = $result->fetch_assoc()) {
    $announcements[] = $row;
}

$stmt->close();
$conn->close();

// Helper function
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
            <form class="searchbar" method="GET" action="adminfile.php">
                <!-- Search Box -->
                <div class="searchbox">
                    <span class="search-icon">🔍</span>
                    <input id="searchInput" name="search" placeholder="Search File (Title, Type, Creator, etc.)"
                        value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" style="display:none;"></button>
                </div>

                <!-- Date Range Filter -->
                <div class="date-filter-group">
                    <label for="startDateInput" class="date-label">From:</label>
                    <input type="date" id="startDateInput" name="start_date" class="date-input"
                        value="<?php echo htmlspecialchars($start_date); ?>" onchange="this.form.submit()">

                    <label for="endDateInput" class="date-label">To:</label>
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
                            ?>
                            <a href="?search=<?php echo urlencode($search_query); ?>&filter=<?php echo $cat; ?>"
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
                            <!-- Type -->
                            <div class="td-type">
                                <span class="type-badge"><?php echo htmlspecialchars($announcement['type']); ?></span>
                            </div>

                            <!-- Title -->
                            <div class="td-title">
                                <?php echo htmlspecialchars($announcement['title']); ?>
                            </div>

                            <!-- Date -->
                            <div class="td-date">
                                <?php echo date('d M Y', strtotime($announcement['date'])); ?>
                            </div>

                            <!-- Creator -->
                            <div class="td-creator">
                                <?php echo htmlspecialchars($announcement['created_by_name'] ?? 'Unknown'); ?>
                            </div>

                            <!-- Document -->
                            <div class="td-document">
                                <?php if (!empty($announcement['document_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($announcement['document_path']); ?>" target="_blank"
                                        class="doc-link">
                                        📄 View
                                    </a>
                                <?php else: ?>
                                    <span class="no-doc">No Doc</span>
                                <?php endif; ?>
                            </div>

                            <!-- Actions -->
                            <div class="td-actions">
                                <button type="button" class="btn-edit"
                                    onclick='openEditModal(<?php echo json_encode($announcement); ?>)'>
                                    Edit
                                </button>
                                <form method="POST" action="adminfile.php" style="display:inline;"
                                    onsubmit="return confirm('Are you sure want to delete this announcement?');">
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

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Announcement</h2>
                <span class="close" onclick="closeEditModal()">&times;</span>
            </div>

            <form method="POST" action="adminfile.php" enctype="multipart/form-data">
                <input type="hidden" name="edit_id" id="edit_id">
                <input type="hidden" name="old_image" id="old_image">
                <input type="hidden" name="old_document" id="old_document">

                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="edit_title" id="edit_title" required>
                </div>

                <div class="form-group">
                    <label>Type</label>
                    <select name="edit_type" id="edit_type" required>
                        <option value="Jadwal">Jadwal</option>
                        <option value="Beasiswa">Beasiswa</option>
                        <option value="Perubahan Kelas">Perubahan Kelas</option>
                        <option value="Karir">Karir</option>
                        <option value="Kemahasiswaan">Kemahasiswaan</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Date</label>
                    <input type="date" name="edit_date" id="edit_date" required>
                </div>

                <div class="form-group">
                    <label>Photo (Optional)</label>
                    <input type="file" name="edit_image" accept="image/*">
                    <small>Leave empty if don't want to change the photo</small>
                </div>

                <div class="form-group">
                    <label>Document (Optional)</label>
                    <input type="file" name="edit_document" accept=".pdf,.doc,.docx">
                    <small>Leave empty if don't want to change the document</small>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
                    <button type="submit" name="update_file" class="btn-submit">Update File</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/adminfile.js"></script>
</body>

</html>