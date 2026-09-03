<?php

function app_bentuk_dokumen_options()
{
    return [
        'Regulasi',
        'Dokumen Bukti',
        'Dokumen Lainnya'
    ];
}

function app_validate_bentuk_dokumen($value)
{
    return in_array(trim((string) $value), app_bentuk_dokumen_options(), true);
}

function app_is_bentuk_regulasi($value)
{
    return strcasecmp(trim((string) $value), 'Regulasi') === 0;
}

function app_uses_regulasi_workflow($bentukDokumen, $nomorSurat = '')
{
    $bentukDokumen = trim((string) $bentukDokumen);
    if ($bentukDokumen === '') {
        return trim((string) $nomorSurat) !== '';
    }

    return app_is_bentuk_regulasi($bentukDokumen);
}

function app_resolve_jenis_pengajuan($config, $bentukDokumen, $requestedIdJenis = 0)
{
    if (!app_validate_bentuk_dokumen($bentukDokumen)) {
        return ['ok' => false, 'message' => 'Bentuk dokumen tidak valid.', 'data' => null];
    }

    if (app_is_bentuk_regulasi($bentukDokumen)) {
        $idJenis = (int) $requestedIdJenis;
        $stmt = mysqli_prepare($config, "
            SELECT id_jenis, nama_jenis, kode_jenis
            FROM tb_jenis_dokumen
            WHERE id_jenis = ? AND UPPER(TRIM(kode_jenis)) <> 'DB'
            LIMIT 1
        ");
        if (!$stmt) {
            return ['ok' => false, 'message' => 'Gagal memeriksa Jenis Dokumen.', 'data' => null];
        }
        mysqli_stmt_bind_param($stmt, 'i', $idJenis);
    } else {
        $kodeDokumenBukti = 'DB';
        $stmt = mysqli_prepare($config, "
            SELECT id_jenis, nama_jenis, kode_jenis
            FROM tb_jenis_dokumen
            WHERE UPPER(TRIM(kode_jenis)) = ?
            LIMIT 1
        ");
        if (!$stmt) {
            return ['ok' => false, 'message' => 'Gagal memeriksa Jenis Dokumen.', 'data' => null];
        }
        mysqli_stmt_bind_param($stmt, 's', $kodeDokumenBukti);
    }

    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$data) {
        $message = app_is_bentuk_regulasi($bentukDokumen)
            ? 'Jenis Dokumen wajib dipilih untuk bentuk Regulasi.'
            : 'Master Jenis Dokumen dengan kode DB tidak ditemukan.';
        return ['ok' => false, 'message' => $message, 'data' => null];
    }

    return ['ok' => true, 'message' => '', 'data' => $data];
}

function app_display_bentuk_dokumen($value)
{
    $value = trim((string) $value);
    return $value !== '' ? $value : '-';
}
