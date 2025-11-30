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

// Get all announcements
$search_query = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$where_clause = "";

if (!empty($search_query)) {
    $where_clause = " WHERE title LIKE '%$search_query%' OR type LIKE '%$search_query%' OR created_by_name LIKE '%$search_query%'";
}

$query = "SELECT id, title, type, date, image_path, document_path, created_by_name FROM pengumuman" . $where_clause . " ORDER BY date DESC";
$result = $conn->query($query);
$announcements = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SIPAk - File Management</title>
    <link rel="stylesheet" href="../css/adminfile.css">
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
                    <a href="adminuser.php" class="dropdown-item user-btn">User Management</a>
                    <a href="adminfile.php" class="dropdown-item file-btn">File Management</a>
                    <a href="logout.php" class="dropdown-item logout-btn">Log Out</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <main class="main">
            <div class="searchbar">
                <form class="searchbox" method="GET" action="adminfile.php">
                    <span class="search-icon">🔍</span>
                    <input id="searchInput" name="search" placeholder="Search File (Title, Type, Date, Creator)"
                        value="<?php echo htmlspecialchars($search_query); ?>">
                </form>
            </div>

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
                        <p>Silakan ubah filter pencarian Anda</p>
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
    </script>
</body>

</html>