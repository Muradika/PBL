<?php
session_start();

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke login page
header("Location: loginpage.php");
exit();
?>