<?php
session_start();
require_once("../config/koneksi.php");

$username = $_POST['username'];
$password = $_POST['password'];

$qlogin = "SELECT * FROM tb_user WHERE username = '$username' AND password = '$password'";
$login  = mysqli_query($config, $qlogin);
$jumlahdata = mysqli_num_rows($login);

if ($jumlahdata > 0) {
    $dlogin = mysqli_fetch_assoc($login);

    $_SESSION['kode_user']   = $dlogin['kode_user'];
    $_SESSION['username']  = $dlogin['username'];
    $_SESSION['password']  = $dlogin['password'];
    $_SESSION['nama_karyawan']  = $dlogin['nama_karyawan'];
    $_SESSION['level']     = $dlogin['level'];

    if ($dlogin['level'] == 'Kepala Ruangan') {
        echo "<script>
                alert('Selamat Datang Admin Selaku Kepala Ruangan');
                window.location = '../admin/main_admin.php?unit=beranda';
              </script>";
    } elseif ($dlogin['level'] == 'Staff IT') {
        echo "<script>
                alert('Selamat Datang Staff IT');
                window.location = '../staff/main_staff.php?unit=beranda';
              </script>";
    } else {
        echo "<script>
                alert('Level tidak dikenali');
                window.location = 'main_login/form_login.php';
              </script>";
    }

} else {
    echo "<script>
            alert('Akun Anda Tidak Terdaftar');
            window.location = 'form_login.php';
          </script>";
}
?>
