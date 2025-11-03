<?php
session_start();
require_once("../config/koneksi.php");

$username = $_POST['username'];
$password = $_POST['password'];

// Ambil data user berdasarkan username
$qlogin = "SELECT * FROM tb_user WHERE username = '$username' LIMIT 1";
$login  = mysqli_query($config, $qlogin);
$jumlahdata = mysqli_num_rows($login);

if ($jumlahdata > 0) {
    $dlogin = mysqli_fetch_assoc($login);

    // Verifikasi password hash
    if (password_verify($password, $dlogin['password'])) {

        // Simpan data ke session
        $_SESSION['id_user']       = $dlogin['id_user'];
        $_SESSION['username']      = $dlogin['username'];
        $_SESSION['nama_lengkap']  = $dlogin['nama_lengkap'];
        $_SESSION['level']         = $dlogin['level'];
        $_SESSION['kode_pokja']    = $dlogin['kode_pokja'];

        // Cek level user
        if ($dlogin['level'] == 'Admin') {
            echo "<script>
                    alert('Selamat Datang Admin Sekretariat Akreditasi');
                    window.location = '../admin/main_admin.php?unit=beranda';
                  </script>";
        } elseif ($dlogin['level'] == 'Pokja') {
            echo "<script>
                    alert('Selamat Datang Pokja {$dlogin['kode_pokja']}');
                    window.location = '../pokja/main_pokja.php?unit=beranda';
                  </script>";
        } else {
            echo "<script>
                    alert('Level pengguna tidak dikenali');
                    window.location = 'form_login.php';
                  </script>";
        }

    } else {
        // Password salah
        echo "<script>
                alert('Password salah!');
                window.location = 'form_login.php';
              </script>";
    }

} else {
    // Username tidak ditemukan
    echo "<script>
            alert('Akun Anda Tidak Terdaftar!');
            window.location = 'form_login.php';
          </script>";
}
?>
