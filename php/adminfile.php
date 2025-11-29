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
    $where_clause = " WHERE title LIKE '%$search_query%' OR type LIKE '%$search_query%'";
}

$query = "SELECT id, title, type, date, image_path, document_path FROM pengumuman" . $where_clause . " ORDER BY date DESC";
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
                    <input id="searchInput" name="search" placeholder="Search File (Title, Type, Date)"
                        value="<?php echo htmlspecialchars($search_query); ?>">
                </form>
            </div>

            <div class="list-header">
                <div class="small">Type</div>
                <div class="small">Title</div>
                <div class="small">Date</div>
                <div class="small">Document</div>
                <div class="small">Actions</div>
            </div>

            <div class="rows" id="rows">
                <?php if (!empty($announcements)): ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="row">
                            <div class="file-icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="file-info">
                                <div class="file-type"><?php echo htmlspecialchars($announcement['type']); ?></div>
                                <div class="file-title"><?php echo htmlspecialchars($announcement['title']); ?></div>
                            </div>
                            <div class="upload-date"><?php echo date('d M Y', strtotime($announcement['date'])); ?></div>
                            <div class="document-name">
                                <?php if (!empty($announcement['document_path'])): ?>
                                    <a href="<?php echo htmlspecialchars($announcement['document_path']); ?>" target="_blank"
                                        class="doc-link">
                                        View Document
                                    </a>
                                <?php else: ?>
                                    <span class="no-doc">No document</span>
                                <?php endif; ?>
                            </div>
                            <div class="actions-cell">
                                <form method="POST" action="adminfile.php"
                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus file ini?');"
                                    style="display: inline;">
                                    <input type="hidden" name="delete_id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" class="btn-remove">Remove</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 50px; color: #666;">
                        Tidak ada file yang ditemukan.
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