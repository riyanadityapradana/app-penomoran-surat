<?php

function app_upload_error_message($code)
{
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'Ukuran file melebihi batas server.',
        UPLOAD_ERR_FORM_SIZE => 'Ukuran file melebihi batas form.',
        UPLOAD_ERR_PARTIAL => 'File hanya terupload sebagian.',
        UPLOAD_ERR_NO_FILE => 'Tidak ada file yang dipilih.',
        UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary server tidak tersedia.',
        UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file upload.',
        UPLOAD_ERR_EXTENSION => 'Upload dibatalkan oleh ekstensi PHP.'
    ];

    return $messages[$code] ?? 'Terjadi kesalahan upload.';
}

function app_validate_upload($file, $allowedExtensions, $allowedMimes, $maxBytes)
{
    if (!isset($file) || !is_array($file)) {
        return ['ok' => false, 'message' => 'File tidak ditemukan.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'message' => app_upload_error_message($file['error'] ?? UPLOAD_ERR_NO_FILE)];
    }

    if (($file['size'] ?? 0) <= 0) {
        return ['ok' => false, 'message' => 'File kosong atau rusak.'];
    }

    if ($file['size'] > $maxBytes) {
        return ['ok' => false, 'message' => 'Ukuran file terlalu besar. Maksimal ' . round($maxBytes / 1024 / 1024) . 'MB.'];
    }

    $extension = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions, true)) {
        return ['ok' => false, 'message' => 'Format file tidak valid. Format yang diperbolehkan: ' . implode(', ', $allowedExtensions) . '.'];
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
        }
    }

    if ($mime !== '' && !in_array($mime, $allowedMimes, true)) {
        return ['ok' => false, 'message' => 'Tipe file tidak sesuai dengan format yang dipilih.'];
    }

    return ['ok' => true, 'extension' => $extension, 'mime' => $mime];
}

function app_store_uploaded_file($file, $uploadDir, $prefix, $allowedExtensions, $allowedMimes, $maxBytes)
{
    $validation = app_validate_upload($file, $allowedExtensions, $allowedMimes, $maxBytes);
    if (!$validation['ok']) {
        return $validation;
    }

    if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
        return ['ok' => false, 'message' => 'Folder upload bermasalah. Hubungi administrator.'];
    }

    $extension = $validation['extension'];
    $random = bin2hex(random_bytes(8));
    $filename = preg_replace('/[^a-zA-Z0-9_]/', '_', $prefix) . '_' . date('YmdHis') . '_' . $random . '.' . $extension;
    $destination = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'message' => 'Gagal menyimpan file upload.'];
    }

    return ['ok' => true, 'filename' => $filename, 'path' => $destination];
}

function app_validate_phone_id($phone)
{
    $clean = preg_replace('/[\s\-().]/', '', trim((string) $phone));
    return (bool) preg_match('/^(\+?62|0)8[0-9]{8,13}$/', $clean);
}

function app_pengajuan_draft_upload_rules($kodeJenis)
{
    $isDokumenBukti = strtoupper(trim((string) $kodeJenis)) === 'DB';
    $extensions = ['doc', 'docx'];
    $mimes = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/octet-stream'
    ];

    if ($isDokumenBukti) {
        $extensions[] = 'pdf';
        $mimes[] = 'application/pdf';
    }

    return [
        'is_dokumen_bukti' => $isDokumenBukti,
        'extensions' => $extensions,
        'mimes' => $mimes,
        'accept' => $isDokumenBukti ? '.doc,.docx,.pdf,application/pdf' : '.doc,.docx',
        'label' => $isDokumenBukti
            ? 'File Dokumen Bukti (Word atau PDF, maksimal 10MB)'
            : 'File Draft (Word, maksimal 10MB)',
        'help' => $isDokumenBukti
            ? 'Unggah salah satu file: Word (DOC/DOCX) atau PDF.'
            : 'Format yang diperbolehkan: DOC atau DOCX.',
        'max_bytes' => 10 * 1024 * 1024
    ];
}

function app_validate_final_upload_selection($kodeJenis, $hasWord, $hasPdf)
{
    $isDokumenBukti = strtoupper(trim((string) $kodeJenis)) === 'DB';

    if ($isDokumenBukti && !$hasWord && !$hasPdf) {
        return [
            'ok' => false,
            'message' => 'Dokumen Bukti wajib mengunggah minimal satu file final: Word atau PDF.'
        ];
    }

    if (!$isDokumenBukti && (!$hasWord || !$hasPdf)) {
        return [
            'ok' => false,
            'message' => 'Dokumen selain Dokumen Bukti wajib mengunggah file final Word dan PDF.'
        ];
    }

    return ['ok' => true, 'message' => ''];
}
