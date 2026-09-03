<?php

function app_format_wa_phone($phone)
{
    $phone = preg_replace('/[\s\-().]/', '', trim((string) $phone));
    if ($phone === '') {
        return '';
    }

    if (strpos($phone, '+62') === 0) {
        return substr($phone, 1);
    }

    if (strpos($phone, '08') === 0) {
        return '62' . substr($phone, 1);
    }

    if (strpos($phone, '62') !== 0) {
        return '62' . $phone;
    }

    return $phone;
}

function app_wa_url($phone, $message)
{
    $formatted = app_format_wa_phone($phone);
    if ($formatted === '') {
        return '';
    }

    return 'https://wa.me/' . $formatted . '?text=' . rawurlencode($message);
}

function app_wa_verifikasi_message($data, $nomorSurat)
{
    return "Halo Pokja {$data['kode_pokja']},\n\n"
        . "Pengajuan dokumen Anda telah DISETUJUI dan sudah dibuatkan nomor dokumen.\n\n"
        . "No Dokumen: {$nomorSurat}\n"
        . "Judul Dokumen: {$data['judul_dokumen']}\n"
        . "Jenis Dokumen: {$data['nama_jenis']}\n"
        . "Bentuk Dokumen: " . (!empty($data['bentuk_dokumen']) ? $data['bentuk_dokumen'] : '-') . "\n\n"
        . "Silakan cek kembali dokumen. Jika masih ada perubahan, unggah ulang file Word melalui aplikasi sebelum dokumen difinalkan.\n\n"
        . "Terima kasih.";
}

function app_wa_selesai_message($data)
{
    $nomorSurat = trim((string) ($data['nomor_surat'] ?? ''));
    $nomorLine = $nomorSurat !== '' ? "No Dokumen: {$nomorSurat}\n" : '';

    return "Halo Pokja {$data['kode_pokja']},\n\n"
        . "Dokumen Anda sudah SELESAI diproses dan file PDF final sudah tersedia.\n\n"
        . $nomorLine
        . "Judul Dokumen: {$data['judul_dokumen']}\n"
        . "Jenis Dokumen: {$data['nama_jenis']}\n"
        . "Bentuk Dokumen: " . (!empty($data['bentuk_dokumen']) ? $data['bentuk_dokumen'] : '-') . "\n\n"
        . "Silakan login ke aplikasi untuk melihat atau mengunduh dokumen final.\n\n"
        . "Terima kasih.";
}
