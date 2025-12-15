<?php
session_start();
include "../database/config.php";

// ========== AUTO LOGIN JIKA ADA COOKIE ==========
if (!isset($_SESSION['user_id']) && isset($_COOKIE['user_id']) && isset($_COOKIE['user_token'])) {
  $user_id = $_COOKIE['user_id'];
  $token = $_COOKIE['user_token'];

  // Verifikasi token dari database
  $stmt = $db->prepare("SELECT * FROM user WHERE id=? AND remember_token=?");
  $stmt->bind_param("ss", $user_id, $token);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

    // Redirect sesuai role
    $redirect = ($user['role'] == 'mahasiswa') ? 'homepage1.php' :
      (($user['role'] == 'dosen') ? 'profiledosen.php' : 'adminfile.php');
    header("Location: $redirect");
    exit();
  }
  $stmt->close();
}

// ========== RATE LIMITING (Simple) ==========
$max_attempts = 5;
$lockout_time = 900; // 15 menit

if (!isset($_SESSION['login_attempts'])) {
  $_SESSION['login_attempts'] = 0;
  $_SESSION['last_attempt_time'] = time();
}

// Reset counter jika sudah lewat lockout time
if (time() - $_SESSION['last_attempt_time'] > $lockout_time) {
  $_SESSION['login_attempts'] = 0;
}

$error_message = '';
$is_locked = $_SESSION['login_attempts'] >= $max_attempts;

// ========== PROSES LOGIN ==========
if (isset($_POST["login"])) {
  // Cek jika akun ter-lock
  if ($is_locked) {
    $remaining = $lockout_time - (time() - $_SESSION['last_attempt_time']);
    $error_message = "Too Much Attempt. Try Again in " . ceil($remaining / 60) . " menit.";
  } else {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $role = $_POST["role"];
    $remember = isset($_POST["remember"]) ? true : false;

    // Validasi input
    if (empty($email) || empty($password) || empty($role)) {
      $error_message = "All Fields Must be Filled in!";
    } else {
      // Query user berdasarkan email dan role
      $sql = "SELECT * FROM user WHERE email=? AND role=?";
      $stmt = $db->prepare($sql);
      $stmt->bind_param("ss", $email, $role);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // ✅ VERIFIKASI PASSWORD DENGAN password_verify()
        // CATATAN: Password di database harus sudah di-hash dengan password_hash()

        // Untuk backward compatibility (jika password masih plain text):
        $password_valid = false;
        if (password_get_info($user['password'])['algo'] === null) {
          // Password masih plain text (legacy)
          $password_valid = ($password === $user['password']);

          // Update ke hashed password untuk security
          $hashed = password_hash($password, PASSWORD_DEFAULT);
          $update_stmt = $db->prepare("UPDATE user SET password=? WHERE id=?");
          $update_stmt->bind_param("si", $hashed, $user['id']);
          $update_stmt->execute();
          $update_stmt->close();
        } else {
          // Password sudah di-hash
          $password_valid = password_verify($password, $user['password']);
        }

        if ($password_valid) {
          // Reset login attempts
          $_SESSION['login_attempts'] = 0;

          // Set session
          $_SESSION['user_id'] = $user['id'];
          $_SESSION['email'] = $user['email'];
          $_SESSION['role'] = $user['role'];
          $_SESSION['nama_lengkap'] = $user['nama_lengkap'];

          // Simpan data opsional
          if (isset($user['nama']))
            $_SESSION['nama'] = $user['nama'];
          if (isset($user['nim']))
            $_SESSION['nim'] = $user['nim'];

          // ========== REMEMBER ME FUNCTIONALITY ==========
          if ($remember) {
            // Generate random token
            $token = bin2hex(random_bytes(32));

            // Simpan token ke database
            $update_token = $db->prepare("UPDATE user SET remember_token=? WHERE id=?");
            $update_token->bind_param("si", $token, $user['id']);
            $update_token->execute();
            $update_token->close();

            // Set cookie untuk 30 hari
            setcookie('user_id', $user['id'], time() + (86400 * 30), "/", "", true, true);
            setcookie('user_token', $token, time() + (86400 * 30), "/", "", true, true);
          }

          // Redirect sesuai role
          if ($user['role'] == "mahasiswa") {
            header("Location: homepage1.php");
          } else if ($user['role'] == "dosen") {
            header("Location: profiledosen.php");
          } else if ($user['role'] == "admin") {
            header("Location: adminfile.php");
          }
          exit();
        } else {
          $_SESSION['login_attempts']++;
          $_SESSION['last_attempt_time'] = time();
          $error_message = "Wrong Password!";
        }
      } else {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        $error_message = "Email or Role Not Found!";
      }
      $stmt->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>SIPAk - Login</title>
  <link rel="icon" type="image/png" href="../img/img_Politeknikbnw.png" />
  <link rel=" stylesheet" href="../css/loginpage.css" />
</head>

<body>
  <div class="background-container">
    <div class="overlay"></div>
  </div>

  <div class="login-card">
    <div class="logo-area">
      <img src="../img/img_Politeknik.png" alt="Logo Polibatam" class="logo" />
    </div>

    <?php if (!empty($error_message)): ?>
      <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
      </div>
    <?php endif; ?>

    <?php if ($is_locked): ?>
      <div class="locked-message">
        Account Locked Due to Too Many Failed Login Attempts. Please Try Again Later.
      </div>
    <?php endif; ?>

    <form action="loginpage.php" method="post" id="loginForm">
      <div class="input-group">
        <input id="email" name="email" type="email" placeholder="Email" required <?php echo $is_locked ? 'disabled' : ''; ?> />
      </div>

      <div class="input-group">
        <input id="password" name="password" type="password" placeholder="Password" required <?php echo $is_locked ? 'disabled' : ''; ?> />
      </div>

      <div class="input-group select-wrapper">
        <select id="role" name="role" required <?php echo $is_locked ? 'disabled' : ''; ?>>
          <option value="" disabled selected>Choose User</option>
          <option value="mahasiswa">Mahasiswa</option>
          <option value="dosen">Dosen/Staff</option>
          <option value="admin">Admin</option>
        </select>
        <div class="dropdown-icon">&#9660;</div>
      </div>

      <!-- Remember Me Checkbox -->
      <div class="remember-me">
        <input type="checkbox" id="remember" name="remember" />
        <label for="remember">Remember Me</label>
      </div>

      <button type="submit" name="login" class="btn-login" <?php echo $is_locked ? 'disabled' : ''; ?>>
        <?php echo $is_locked ? 'Locked' : 'Log In'; ?>
      </button>

      <?php if ($_SESSION['login_attempts'] > 0 && !$is_locked): ?>
        <div class="attempts-warning">
          ⚠️ Login Attempt: <?php echo $_SESSION['login_attempts']; ?>/<?php echo $max_attempts; ?>
        </div>
      <?php endif; ?>
    </form>
  </div>
</body>

</html>