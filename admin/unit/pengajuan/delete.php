<?php
require_once("../config/koneksi.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: main_admin.php?unit=pengajuan&err=Metode penghapusan tidak valid!');
    exit;
}

$csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
if (empty($_SESSION['pengajuan_admin_csrf']) || !hash_equals($_SESSION['pengajuan_admin_csrf'], $csrf)) {
    header('Location: main_admin.php?unit=pengajuan&err=Permintaan hapus tidak valid. Silakan coba kembali!');
    exit;
}

$id_pengajuan = isset($_POST['id_pengajuan']) && ctype_digit((string) $_POST['id_pengajuan'])
    ? (int) $_POST['id_pengajuan']
    : 0;
if ($id_pengajuan <= 0) {
    header('Location: main_admin.php?unit=pengajuan&err=ID pengajuan tidak valid!');
    exit;
}

$stmtData = mysqli_prepare($config, "SELECT status, file_draft, file_final FROM tb_pengajuan_dokumen WHERE id_pengajuan = ? LIMIT 1");
mysqli_stmt_bind_param($stmtData, 'i', $id_pengajuan);
mysqli_stmt_execute($stmtData);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtData));
mysqli_stmt_close($stmtData);

if (!$data) {
    header('Location: main_admin.php?unit=pengajuan&err=Data pengajuan tidak ditemukan!');
    exit;
}

if ($data['status'] === 'Selesai') {
    header('Location: main_admin.php?unit=pengajuan&err=Dokumen selesai hanya dapat dihapus dari halaman Dokumen Sah!');
    exit;
}

$stmtDelete = mysqli_prepare($config, "DELETE FROM tb_pengajuan_dokumen WHERE id_pengajuan = ? AND status <> 'Selesai'");
mysqli_stmt_bind_param($stmtDelete, 'i', $id_pengajuan);
$deleted = mysqli_stmt_execute($stmtDelete);
$deleteError = mysqli_stmt_error($stmtDelete);
$affectedRows = mysqli_stmt_affected_rows($stmtDelete);
mysqli_stmt_close($stmtDelete);

if (!$deleted || $affectedRows < 1) {
    header('Location: main_admin.php?unit=pengajuan&err=' . urlencode('Gagal menghapus pengajuan: ' . $deleteError));
    exit;
}

function hapus_file_pengajuan_admin_jika_tidak_dipakai($config, $filename)
{
    if (empty($filename)) {
        return true;
    }

    $safeFilename = basename($filename);
    $stmtRef = mysqli_prepare($config, "SELECT COUNT(*) AS total FROM tb_pengajuan_dokumen WHERE file_draft = ? OR file_final = ?");
    mysqli_stmt_bind_param($stmtRef, 'ss', $safeFilename, $safeFilename);
    mysqli_stmt_execute($stmtRef);
    $ref = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtRef));
    mysqli_stmt_close($stmtRef);

    if ((int) ($ref['total'] ?? 0) > 0) {
        return true;
    }

    $uploadDirs = [
        __DIR__ . '/../../../assets/upload/draft_word',
        __DIR__ . '/../../../assets/upload/draft_final',
        __DIR__ . '/../../../assets/upload'
    ];

    foreach ($uploadDirs as $uploadDir) {
        $uploadDirReal = realpath($uploadDir);
        if ($uploadDirReal === false) {
            continue;
        }

        $path = $uploadDirReal . DIRECTORY_SEPARATOR . $safeFilename;
        if (is_file($path) && !unlink($path)) {
            return false;
        }
    }

    return true;
}

$files = array_values(array_unique(array_filter([$data['file_draft'], $data['file_final']])));
$filesClean = true;
foreach ($files as $filename) {
    if (!hapus_file_pengajuan_admin_jika_tidak_dipakai($config, $filename)) {
        $filesClean = false;
    }
}

$message = $filesClean
    ? 'Data pengajuan dan file terkait berhasil dihapus!'
    : 'Data pengajuan berhasil dihapus, tetapi ada file yang tidak dapat dibersihkan.';
header('Location: main_admin.php?unit=pengajuan&msg=' . urlencode($message));
exit;
