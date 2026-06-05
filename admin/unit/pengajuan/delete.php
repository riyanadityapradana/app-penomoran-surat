<?php
require_once("../config/koneksi.php");

function hapus_file_pengajuan($filename)
{
    if (empty($filename)) {
        return true;
    }

    $path = '../assets/upload/draft_word/' . basename($filename);
    if (!file_exists($path)) {
        return true;
    }

    return unlink($path);
}

if (!isset($_GET['id_pengajuan'])) {
    header('Location: main_admin.php?unit=pengajuan&err=ID pengajuan tidak ditemukan!');
    exit;
}

$id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
$query = mysqli_query($config, "
    SELECT id_pengajuan, file_draft, file_final
    FROM tb_pengajuan_dokumen
    WHERE id_pengajuan = '$id_pengajuan'
");

if (!$query || mysqli_num_rows($query) === 0) {
    header('Location: main_admin.php?unit=pengajuan&err=Data pengajuan tidak ditemukan!');
    exit;
}

$data = mysqli_fetch_assoc($query);
$files = array_unique(array_filter([$data['file_draft'], $data['file_final']]));

foreach ($files as $file) {
    if (!hapus_file_pengajuan($file)) {
        header('Location: main_admin.php?unit=pengajuan&err=Gagal menghapus file terkait. Periksa izin folder upload!');
        exit;
    }
}

$delete = mysqli_query($config, "DELETE FROM tb_pengajuan_dokumen WHERE id_pengajuan = '$id_pengajuan'");
if ($delete) {
    header('Location: main_admin.php?unit=pengajuan&msg=Data pengajuan berhasil dihapus!');
    exit;
}

$errMsg = urlencode('Gagal menghapus data pengajuan: ' . mysqli_error($config));
header('Location: main_admin.php?unit=pengajuan&err=' . $errMsg);
exit;
?>
