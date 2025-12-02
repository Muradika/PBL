<?php
$hostname = "localhost"; // Ganti jika database di server lain
$username = "root"; // Ganti dengan username database kamu
$password = ""; // Ganti dengan password database kamu
$dbname = "sipak"; // Ganti dengan nama database yang sudah kamu buat

// Buat koneksi
$conn = new mysqli($hostname, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>