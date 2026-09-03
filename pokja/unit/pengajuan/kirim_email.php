<?php
require_once("../config/koneksi.php");
require_once("../config/notifikasi.php");

if (!isset($_GET['id_pengajuan'])) {
    header('Location: main_pokja.php?unit=pengajuan&err=ID pengajuan tidak ditemukan!');
    exit;
}

$id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
$query = mysqli_query($config, "
    SELECT p.*, j.nama_jenis, u.nama_lengkap, u.email_user, u.kode_pokja
    FROM tb_pengajuan_dokumen p
    LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
    LEFT JOIN tb_user u ON p.id_user = u.id_user
    WHERE p.id_pengajuan = '$id_pengajuan'
");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header('Location: main_pokja.php?unit=pengajuan&err=Data pengajuan tidak ditemukan!');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kirim_email'])) {
    $adminLink = app_base_url() . '/admin/main_admin.php?unit=pengajuan';
    $subject = 'Pengajuan Dokumen Baru dari Pokja ' . $data['kode_pokja'];
    $body = "
        <p>Halo Admin,</p>
        <p>Ada <b>pengajuan dokumen baru</b> dari Pokja <b>" . htmlspecialchars($data['kode_pokja']) . "</b> yang membutuhkan verifikasi Anda.</p>
        <table border='0' cellspacing='0' cellpadding='6' style='border-collapse: collapse;'>
            <tr><td><b>Judul Dokumen</b></td><td>: " . htmlspecialchars($data['judul_dokumen']) . "</td></tr>
            <tr><td><b>Jenis Dokumen</b></td><td>: " . htmlspecialchars($data['nama_jenis']) . "</td></tr>
            <tr><td><b>Bentuk Dokumen</b></td><td>: " . htmlspecialchars(!empty($data['bentuk_dokumen']) ? $data['bentuk_dokumen'] : '-') . "</td></tr>
            <tr><td><b>Diajukan Oleh</b></td><td>: " . htmlspecialchars($data['nama_lengkap']) . "</td></tr>
            <tr><td><b>Tanggal Pengajuan</b></td><td>: " . htmlspecialchars(app_format_datetime_id($data['tanggal_ajuan'])) . "</td></tr>
        </table>
        <p>Silakan buka aplikasi untuk melihat detail pengajuan.</p>
        <p><a href='" . htmlspecialchars($adminLink) . "'>Buka daftar pengajuan</a></p>
    ";

    $result = app_send_email_to_admins($config, $subject, $body);
    if ($result['sent'] > 0) {
        header('Location: main_pokja.php?unit=pengajuan&msg=Email otomatis berhasil dikirim ke admin!');
        exit;
    }

    $message = !empty($result['failed']) ? implode(' | ', $result['failed']) : 'Tidak ada email admin yang aktif';
    header('Location: main_pokja.php?unit=pengajuan&err=Gagal mengirim email. ' . urlencode($message));
    exit;
}
?>
