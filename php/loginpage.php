<?php
include "../database/config.php";

if (isset($_POST["login"])) {
  $email = $_POST["email"];
  $password = $_POST["password"];
  $role = $_POST["role"];

  $sql = "SELECT * FROM user WHERE email='$email' AND password='$password' AND role='$role'";
  $result = $db->query($sql);
  if ($result->num_rows > 0) {

    $user = $result->fetch_assoc();
    $role = $user["role"];

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
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
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