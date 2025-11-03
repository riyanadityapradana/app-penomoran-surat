<?php
$host     = "localhost";  // atau IP server database
$username = "root";       // ganti dengan user database Anda
$password = "";           // ganti dengan password database Anda
$database = "db_surat_akreditasi"; // ganti dengan nama database Anda

// Membuat koneksi
$config = new mysqli($host, $username, $password, $database);

// Cek koneksi
if ($config->connect_error) {
    die("Koneksi gagal: " . $config->connect_error);
}
date_default_timezone_set("Asia/Makassar"); // KKoding jam ter update kalo di refest
// Jika berhasil
// echo "Koneksi berhasil";
?>
