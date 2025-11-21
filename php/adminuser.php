<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>SIPAk - Sistem Informasi Pengumuman Akademik Online</title>
    <link rel="stylesheet" href="../css/adminuser.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <header class="navbar">
        <div class="logo-brand">
            <img src="../img/img_Politeknikbnw.png" alt="Logo Polibatam" class="nav-logo">
            <div class="system-title">
                Sistem Informasi Pengumuman <br />
                Akademik <span class="online-tag">Online</span>
            </div>
        </div>

        <nav class="nav-menu">
            <div class="dropdown">
                <a href="#" class="nav-link dropdown-toggle" id="profile-dropdown-btn">Admin Dashboard</a>
                <div class="dropdown-menu" id="profile-dropdown-menu">
                    <a href="adminuser.php" class="dropdown-item create-btn">User Management</a>
                    <a href="adminfile.php" class="dropdown-item file-btn">File Management</a>
                    <a href="loginpage.php" class="dropdown-item logout-btn">Log Out</a>
                </div>
            </div>
        </nav>
    </header>

    <div class="container">
        <aside class="sidebar">
            <button class="btn add" id="btnAdd">Add</button>
        </aside>

        <main class="main">
            <div class="searchbar">
                <div class="searchbox">
                    <span class="search-icon">🔍</span>
                    <input id="searchInput" placeholder="Search User" />
                </div>
                <div class="filter" title="Filter">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
                        <path d="M3 5h18M6 12h12M10 19h4" stroke="#0b2b57" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </div>
            </div>

            <div class="list-header">
                <div class="small">Photo</div>
                <div class="small">Name</div>
                <div class="small">ID Number</div>
                <div class="small">Email</div>
                <div class="small">User</div>
                <div class="small">Actions</div>
            </div>

            <div class="rows" id="rows"></div>
        </main>
    </div>

    <!-- Modal -->
    <div class="modal-backdrop" id="modalBackdrop" style="display: none;">
        <div class="modal" role="dialog" aria-modal="true" style="height: auto; max-height: 90vh;">
            <h3 id="modalTitle">Edit User</h3>

            <div class="file-form-content">
                <div style="display:flex;gap:8px;align-items:center;margin-bottom:12px">
                    <div class="avatar" id="modalAvatar" title="Klik untuk ubah foto">U</div>
                </div>

                <div class="file-inputs-container">

                    <input type="file" id="avatarFileInput" accept="image/*" style="display:none" />

                    <input type="text" id="nameInput" placeholder="Full name" />

                    <input type="text" id="idInput" placeholder="ID Number" />

                    <input type="email" id="emailInput" placeholder="Email address" />

                    <input type="password" id="passwordInput" placeholder="password" />

                    <select id="roleInput">
                        <option value="User">User</option>
                        <option value="Admin">Admin</option>
                        <option value="Editor">Mahasiswa</option>
                    </select>


                    <div class="modal-actions">
                        <button class="btn-cancel" id="btnCancel">Cancel</button>
                        <button class="btn-save" id="btnSave">Save</button>
                    </div>
                </div>
            </div>
            <script src="../js/adminuser.js"></script>
</body>

</html>