<?php
session_start(); // ✅ TAMBAHKAN INI di paling atas!
include "../database/config.php";

if (isset($_POST["login"])) {
  $email = $_POST["email"];
  $password = $_POST["password"];
  $role = $_POST["role"];

  // ⚠️ PENTING: Gunakan prepared statement untuk keamanan
  $sql = "SELECT * FROM user WHERE email=? AND password=? AND role=?";
  $stmt = $db->prepare($sql);
  $stmt->bind_param("sss", $email, $password, $role);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $role = $user["role"];

    // ✅ SIMPAN DATA USER KE SESSION (INI YANG PENTING!)
    $_SESSION['user_id'] = $user['id'];        // Simpan ID user
    $_SESSION['email'] = $user['email'];       // Simpan email
    $_SESSION['role'] = $user['role'];         // Simpan role
    $_SESSION['nama_lengkap'] = $user['nama_lengkap']; // Simpan nama lengkap

    // Opsional: simpan data lain jika ada (nama, nim, dll)
    if (isset($user['nama'])) {
      $_SESSION['nama'] = $user['nama'];
    }
    if (isset($user['nim'])) {
      $_SESSION['nim'] = $user['nim'];
    }

    // Redirect sesuai role
    if ($role == "mahasiswa") {
      header("Location: homepage1.php");
    } else if ($role == "dosen") {
      header("Location: profiledosen.php");
    } else if ($role == "admin") {
      header("Location: adminfile.php");
    }
    exit();
  } else {
    echo "<script>alert('Invalid email, password or role');</script>";
  }

  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0 " />
  <title>SIPAk - Login</title>

  <link rel="stylesheet" href="../css/loginpage.css" />
</head>

<body>
  <div class="background-container">
    <div class="overlay"></div>
  </div>

  <div class="login-card">
    <div class="logo-area">
      <img src="../img/img_Politeknik.png" alt="Logo Polibatam" class="logo" />
    </div>

    <form action="loginpage.php" method="post" id="loginForm">
      <div class="input-group">
        <input id="email" name="email" type="email" placeholder="Email" required />
      </div>

      <div class="input-group">
        <input id="password" name="password" type="password" placeholder="Password" required />
      </div>

      <div class="input-group select-wrapper">
        <select id="role" name="role" required>
          <option value="" disabled selected>Choose User</option>
          <option value="mahasiswa">Mahasiswa</option>
          <option value="dosen">Dosen/Staff</option>
          <option value="admin">Admin</option>
        </select>
        <div class="dropdown-icon">&#9660;</div>
      </div>

      <button type="submit" name="login" class="btn-login">Log In</button>
    </form>
  </div>
</body>

</html>