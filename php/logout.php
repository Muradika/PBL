<?php
session_start();
include "../database/config.php";

// Hapus remember token dari database jika ada
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $db->prepare("UPDATE user SET remember_token=NULL WHERE id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
}

// Hapus semua session
$_SESSION = array();

// Hapus session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Hapus remember me cookies
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/', '', true, true);
}
if (isset($_COOKIE['user_token'])) {
    setcookie('user_token', '', time() - 3600, '/', '', true, true);
}

// Destroy session
session_destroy();

// Redirect ke login page
header("Location: loginpage.php");
exit();
?>