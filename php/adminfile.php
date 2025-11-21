<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>SIPAk - Sistem Informasi Pengumuman Akademik Online</title>
    <link rel="stylesheet" href="../css/adminfile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
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

        <nav class="nav-menu" aria-label="User menu">
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" id="profile-dropdown-btn" aria-haspopup="true"
                    aria-expanded="false">Admin Dashboard</a>
                <div class="dropdown-menu" id="profile-dropdown-menu" role="menu"
                    aria-labelledby="profile-dropdown-btn">
                    <a href="adminuser.php" class="dropdown-item create-btn" role="menuitem">User
                        Management</a>
                    <a href="adminfile.php" class="dropdown-item file-btn" role="menuitem">File
                        Management</a>
                    <a href="loginpage.php" class="dropdown-item logout-btn" role="menuitem">Log Out</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <aside class="sidebar">
            <button class="btn add" id="btnAdd">Add File</button>
        </aside>

        <main class="main">
            <div class="searchbar">
                <div class="searchbox">
                    <span class="search-icon">🔍</span>
                    <input id="searchInput" placeholder="Search File (Title, Uploader, Type)" />
                </div>
                <div class="filter" title="Filter">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 5h18M6 12h12M10 19h4" stroke="#0b2b57" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </div>
            </div>

            <div class="list-header">
                <div class="small">Type</div>
                <div class="small">Title</div>
                <div class="small">Uploader</div>
                <div class="small">Date</div>
                <div class="small">Actions</div>
            </div>

            <div class="rows" id="rows"></div>
        </main>
    </div>

    <div class="modal-backdrop" id="modalBackdrop" aria-hidden="true">
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
            <h3 id="modalTitle">Edit File</h3>

            <div style="
            display: flex;
            gap: 12px;
            align-items: flex-start;
            margin-bottom: 12px;
          ">
                <div class="file-icon" id="modalFileIcon" title="File Type Icon">
                    <svg id="fileSvg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>

                <div style="flex: 1">
                    <div class="form-row">
                        <input type="text" id="fileTitleInput" placeholder="File Title (e.g., Pengumuman Nilai)" />
                    </div>
                    <div class="form-row">
                        <input type="text" id="fileNameInput"
                            placeholder="File Name (e.g., nilai_semester_ganjil.pdf)" />
                    </div>
                    <div class="form-row">
                        <input type="text" id="uploaderInput" placeholder="Uploader Name (e.g., Admin Fakultas)" />
                    </div>
                    <div class="form-row">
                        <select id="fileTypeInput">
                            <option value="Akademik">Akademik</option>
                            <option value="Jadwal Ujian">Jadwal Ujian</option>
                            <option value="Kemahasiswaan">Kemahasiswaan</option>
                            <option value="Beasiswa">Beasiswa</option>
                            <option value="Wisuda">Wisuda</option>
                        </select>
                        <input type="date" id="uploadDateInput" />
                    </div>
                </div>
            </div>

            <div class="modal-actions">
                <button class="btn-cancel" id="btnCancel">Cancel</button>
                <button class="btn-save" id="btnSave">Save</button>
            </div>
        </div>
    </div>
    <script src="../js/adminfile.js"></script>
</body>

</html>