<?php
session_start();
include '../database/pengumuman.php';

// Cek apakah user adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: loginpage.php');
    exit();
}
// Handle Edit User
if (isset($_POST['edit_user'])) {
    $id = $conn->real_escape_string($_POST['edit_id']);
    $nama = $conn->real_escape_string($_POST['edit_nama']);
    $email = $conn->real_escape_string($_POST['edit_email']);
    $role = $conn->real_escape_string($_POST['edit_role']);
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

    if (isset($_POST['update_file'])) {

    $id    = $_POST['edit_id'];
    $title = $_POST['edit_title'];
    $type  = $_POST['edit_type'];
    $date  = $_POST['edit_date'];

    /* ================== FOTO ================== */
    $old_image  = $_POST['old_image'];
    $image_path = $old_image;

    if (!empty($_FILES['edit_image']['name'])) {
        $img_ext = strtolower(pathinfo($_FILES['edit_image']['name'], PATHINFO_EXTENSION));
        $allowed_img = ['jpg','jpeg','png'];

        if (!in_array($img_ext, $allowed_img)) {
            die("Format gambar tidak diizinkan");
        }

        $new_img  = 'img_' . time() . '.' . $img_ext;
        $img_dir  = 'uploads/images/';
        $image_path = $img_dir . $new_img;

        move_uploaded_file($_FILES['edit_image']['tmp_name'], $image_path);

        // Hapus foto lama
        if (!empty($old_image) && file_exists($old_image)) {
            unlink($old_image);
        }
    }

    /* ================== DOKUMEN ================== */
    $old_doc = $_POST['old_document'];
    $doc_path = $old_doc;

    if (!empty($_FILES['edit_document']['name'])) {
        $ext = strtolower(pathinfo($_FILES['edit_document']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx'];

        if (!in_array($ext, $allowed)) {
            die("Format file tidak diizinkan");
        }

        $new_name = 'doc_' . time() . '.' . $ext;
        $upload_dir = '../uploads/documents/';
        $doc_path = $upload_dir . $new_name;

        move_uploaded_file($_FILES['edit_document']['tmp_name'], $doc_path);

        if (!empty($old_doc) && file_exists($old_doc)) {
            unlink($old_doc);
        }
    }

    /* ================== UPDATE DATABASE ================== */
    $stmt = $conn->prepare("
        UPDATE pengumuman 
        SET title=?, type=?, date=?, image_path=?, document_path=?
        WHERE id=?
    ");
    $stmt->bind_param(
        "sssssi",
        $title,
        $type,
        $date,
        $image_path,
        $doc_path,
        $id
    );
    $stmt->execute();

    header("Location: adminfile.php?updated=1");
    exit();
}

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
                                <button
                            type="button"
                            class="btn-edit js-edit-file"
                            data-id="<?= $announcement['id'] ?>"
                            data-title="<?= htmlspecialchars($announcement['title']) ?>"
                            data-type="<?= htmlspecialchars($announcement['type']) ?>"
                            data-date="<?= $announcement['date'] ?>"
                            data-image="<?= $announcement['image_path'] ?>"
                            data-document="<?= $announcement['document_path'] ?>"
                            >
                            ✏️
                            </button>
                                <form method="POST" action="adminfile.php"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus file ini?');">
                                    <input type="hidden" name="delete_id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" class="btn-remove">🗑️</button>
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
<div id="editModal" class="modal">
    <div class="modal-content">
    <div class="modal-header">
        <h2>Edit Pengumuman</h2>
        <span class="close" onclick="closeEditModal()">&times;</span>
    </div>

    <form method="POST" action="adminfile.php" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" id="edit_id">
        <input type="hidden" name="old_image" id="old_image">
        <input type="hidden" name="old_document" id="old_document">

        <div class="form-group">
        <label>Title</label>
        <input type="text" name="edit_title" id="edit_title">
        </div>

        <div class="form-group">
        <label>Type</label>
        <input type="text" name="edit_type" id="edit_type">
        </div>

        <div class="form-group">
        <label>Date</label>
        <input type="date" name="edit_date" id="edit_date">
        </div>

        <div class="form-group">
        <label>Photo</label>
        <input type="file" name="edit_image" accept="image/*">
        <small>Kosongkan jika tidak ingin mengganti foto</small>
        </div>

        <div class="form-group">
        <label>Document</label>
        <input type="file" name="edit_document" id="edit_document" accept=".pdf,.doc,.docx">
        <small>Kosongkan jika tidak ingin mengganti</small>
        </div>
        

        <div class="modal-footer">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn-edit" name="update_file">Update</button>
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
        // Add Modal Functions
        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".js-edit-file").forEach(button => {
    button.addEventListener("click", function () {
    document.getElementById("edit_id").value    = this.dataset.id;
    document.getElementById("edit_title").value = this.dataset.title;
    document.getElementById("edit_type").value  = this.dataset.type;
    document.getElementById("edit_date").value  = this.dataset.date;

    document.getElementById("old_image").value = this.dataset.image;
    document.getElementById("old_document").value = this.dataset.document;

    document.getElementById("editModal").style.display = "block";
    document.body.style.overflow = "hidden";
    });
    });
});

function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
    document.body.style.overflow = "auto";
}

    </script>
</body>

</html>