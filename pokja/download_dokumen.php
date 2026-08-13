<?php
session_start();
require_once(__DIR__ . '/../config/koneksi.php');

if (!isset($_SESSION['id_user']) || ($_SESSION['level'] ?? '') !== 'Pokja') {
    http_response_code(403);
    exit('Akses ditolak. Silakan login sebagai Pokja.');
}

$idDokumen = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$format = isset($_GET['format']) ? strtolower(trim($_GET['format'])) : '';

if ($idDokumen <= 0 || !in_array($format, ['word', 'pdf'], true)) {
    http_response_code(400);
    exit('Permintaan download tidak valid.');
}

$stmt = mysqli_prepare($config, '
    SELECT nama_dokumen, file_word, file_pdf
    FROM tb_dokumen_pendukung
    WHERE id_dokumen = ? AND status = \'Aktif\'
    LIMIT 1
');
mysqli_stmt_bind_param($stmt, 'i', $idDokumen);
mysqli_stmt_execute($stmt);
$dokumen = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$dokumen) {
    http_response_code(404);
    exit('Dokumen tidak ditemukan atau sudah tidak aktif.');
}

$storedName = $format === 'word' ? $dokumen['file_word'] : $dokumen['file_pdf'];
if (empty($storedName) || basename($storedName) !== $storedName) {
    http_response_code(404);
    exit('Format file yang dipilih tidak tersedia.');
}

$uploadDir = realpath(__DIR__ . '/../assets/upload/dokumen_pendukung');
$filePath = $uploadDir !== false ? realpath($uploadDir . DIRECTORY_SEPARATOR . $storedName) : false;
$basePrefix = $uploadDir !== false ? rtrim($uploadDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : '';

if ($filePath === false || !is_file($filePath) || strpos($filePath, $basePrefix) !== 0) {
    http_response_code(404);
    exit('File tidak ditemukan di server.');
}

$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
$allowedExtensions = $format === 'word' ? ['doc', 'docx'] : ['pdf'];
if (!in_array($extension, $allowedExtensions, true)) {
    http_response_code(403);
    exit('Format file tidak diizinkan.');
}

$contentTypes = [
    'doc' => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'pdf' => 'application/pdf'
];
$downloadBase = preg_replace('/[^a-zA-Z0-9_-]+/', '_', $dokumen['nama_dokumen']);
$downloadBase = trim($downloadBase, '_') ?: 'dokumen_pendukung';
$downloadName = $downloadBase . '.' . $extension;

header('X-Content-Type-Options: nosniff');
header('Content-Type: ' . $contentTypes[$extension]);
header('Content-Length: ' . filesize($filePath));
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Cache-Control: private, no-store, max-age=0');
readfile($filePath);
exit;
