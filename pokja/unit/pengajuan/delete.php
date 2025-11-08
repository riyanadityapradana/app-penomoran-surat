<?php
require_once("../config/koneksi.php"); // sesuaikan path ke file koneksi
session_start();

if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
    $id_user = $_SESSION['id_user']; // pastikan hanya pengajuan miliknya sendiri yang bisa dihapus

    // Pastikan data pengajuan ada dan milik user yang login
    $cek = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen 
                                  WHERE id_pengajuan='$id_pengajuan' 
                                  AND id_user='$id_user'");
    if (mysqli_num_rows($cek) == 0) {
        echo "<script>
                alert('Data tidak ditemukan atau bukan milik Anda!');
                window.location = 'main_pokja.php?unit=pengajuan';
              </script>";
        exit;
    }

    $data = mysqli_fetch_assoc($cek);

    // Hanya bisa dihapus jika status 'Menunggu Verifikasi' atau 'Disetujui'
    if ($data['status'] != 'Menunggu Verifikasi' && $data['status'] != 'Disetujui') {
        echo "<script>
                alert('Data tidak dapat dihapus karena status sudah selesai atau ditolak!');
                window.location = 'main_pokja.php?unit=pengajuan';
              </script>";
        exit;
    }

    // Hapus file draft jika ada
    if (!empty($data['file_draft']) && file_exists("../assets/upload/draft_word/" . $data['file_draft'])) {
        unlink("../assets/upload/draft_word/" . $data['file_draft']);
    }

    // Jalankan query hapus
    $query = "DELETE FROM tb_pengajuan_dokumen WHERE id_pengajuan='$id_pengajuan' AND id_user='$id_user'";
    if (mysqli_query($config, $query)) {
        echo "<script>
                alert('Data pengajuan berhasil dihapus!');
                window.location = 'main_pokja.php?unit=pengajuan';
              </script>";
    } else {
        echo "<script>
                alert('Gagal menghapus data: " . mysqli_error($config) . "');
                window.location = 'main_pokja.php?unit=pengajuan';
              </script>";
    }

} else {
    echo "<script>
            alert('ID pengajuan tidak ditemukan!');
            window.location = 'main_pokja.php?unit=pengajuan';
          </script>";
}
?>
