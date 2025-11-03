<?php
session_start();
require_once("../config/koneksi.php");

if (isset($_POST['simpan'])) {
    $nama_lengkap = mysqli_real_escape_string($config, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($config, $_POST['username']);
    $password     = mysqli_real_escape_string($config, $_POST['password']);
    $level        = mysqli_real_escape_string($config, $_POST['level']);
    $kode_pokja   = mysqli_real_escape_string($config, $_POST['kode_pokja']);

    // Hash password sebelum disimpan
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Cek apakah username sudah ada
    $cek = mysqli_query($config, "SELECT * FROM tb_user WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>
                alert('Username sudah digunakan, silakan pilih yang lain!');
                window.location = 'main_admin.php?unit=create_pokja';
              </script>";
        exit;
    }

    // Simpan ke database
    $query = "INSERT INTO tb_user (nama_lengkap, username, password, level, kode_pokja)
              VALUES ('$nama_lengkap', '$username', '$hashed_password', '$level', '$kode_pokja')";

    if (mysqli_query($config, $query)) {
        echo "<script>
                alert('User baru berhasil ditambahkan!');
                window.location = 'main_admin.php?unit=create_pokja';
              </script>";
    } else {
        echo "<script>
                alert('Gagal menyimpan data: " . mysqli_error($config) . "');
                window.location = 'main_admin.php?unit=create_pokja';
              </script>";
    }
}
?>
