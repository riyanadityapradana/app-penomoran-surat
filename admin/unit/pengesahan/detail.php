<?php
require_once("../config/koneksi.php");
require_once("../config/upload_helper.php");
require_once("../config/dokumen_helper.php");

if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);

    // Ambil data pengesahan (status harus selesai)
    $query = mysqli_query($config, "
        SELECT p.*, j.nama_jenis, j.kode_jenis, u.nama_lengkap, u.kode_pokja
        FROM tb_pengajuan_dokumen p
        LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
        LEFT JOIN tb_user u ON p.id_user = u.id_user
        WHERE p.id_pengajuan = '$id_pengajuan' AND p.status = 'Selesai'
    ");

    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        header('Location: main_admin.php?unit=pengesahan&err=Data pengesahan tidak ditemukan atau belum berstatus Selesai!');
        exit;
    }
} else {
    header('Location: main_admin.php?unit=pengesahan&err=ID pengesahan tidak ditemukan!');
    exit;
}

$is_regulasi_workflow = app_uses_regulasi_workflow($data['bentuk_dokumen'] ?? '', $data['nomor_surat'] ?? '');
$tanggal_pengesahan_efektif = !empty($data['tanggal_pengesahan'])
    ? $data['tanggal_pengesahan']
    : (!empty($data['tanggal_disetujui']) ? $data['tanggal_disetujui'] : $data['tanggal_ajuan']);

if (empty($_SESSION['detail_pengesahan_csrf'])) {
    $_SESSION['detail_pengesahan_csrf'] = bin2hex(random_bytes(32));
}

$daftar_jenis_dokumen = [];
$query_jenis_dokumen = mysqli_query($config, "
    SELECT id_jenis, nama_jenis, kode_jenis
    FROM tb_jenis_dokumen
    WHERE TRIM(kode_jenis) <> ''
    ORDER BY nama_jenis ASC
");

if ($query_jenis_dokumen) {
    while ($jenis_dokumen = mysqli_fetch_assoc($query_jenis_dokumen)) {
        $daftar_jenis_dokumen[] = $jenis_dokumen;
    }
}

$kode_pokja_standard = trim((string) ($data['kode_pokja'] ?? ''));
$standard_ep_prefix = $kode_pokja_standard !== '' ? $kode_pokja_standard . '-' : '';
$standard_ep_detail = trim((string) ($data['elemen_penilaian'] ?? ''));

if ($standard_ep_prefix !== '' && stripos($standard_ep_detail, $standard_ep_prefix) === 0) {
    $standard_ep_detail = trim(substr($standard_ep_detail, strlen($standard_ep_prefix)));
}

$panjang_prefix_standard = function_exists('mb_strlen')
    ? mb_strlen($standard_ep_prefix, 'UTF-8')
    : strlen($standard_ep_prefix);
$maksimal_detail_standard = max(1, 100 - $panjang_prefix_standard);

// --- PROSES EDIT UPLOAD PDF (TANPA EMAIL) --- //
if (isset($_POST['edit_upload_pdf'])) {
    $upload = app_store_uploaded_file(
        $_FILES['file_pdf'] ?? null,
        '../assets/upload/draft_final/',
        'dokumen_final_' . $id_pengajuan,
        ['pdf'],
        ['application/pdf', 'application/octet-stream'],
        15 * 1024 * 1024
    );

    if (!$upload['ok']) {
        echo "<script>alert('" . addslashes($upload['message']) . "');</script>";
    } else {
        $new_name = $upload['filename'];

        // Hapus file lama jika ada
        if (!empty($data['file_final']) && file_exists('../assets/upload/draft_final/' . $data['file_final'])) {
            unlink('../assets/upload/draft_final/' . $data['file_final']);
        }

        // Update database
        mysqli_query($config, "
            UPDATE tb_pengajuan_dokumen
            SET file_final='$new_name'
            WHERE id_pengajuan='$id_pengajuan'
        ");

        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&msg=File PDF berhasil diupdate!');
        exit;
    }
}

if (isset($_POST['edit_nomor_dokumen'])) {
    $nomor_surat = isset($_POST['nomor_surat']) ? trim($_POST['nomor_surat']) : '';

    if (!$is_regulasi_workflow) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Dokumen non-Regulasi tidak menggunakan nomor dokumen!');
        exit;
    }

    if ($nomor_surat === '') {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Nomor dokumen tidak boleh kosong!');
        exit;
    }

    $nomor_surat = mysqli_real_escape_string($config, $nomor_surat);
    $update_nomor = mysqli_query($config, "
        UPDATE tb_pengajuan_dokumen
        SET nomor_surat = '$nomor_surat'
        WHERE id_pengajuan = '$id_pengajuan' AND status = 'Selesai'
    ");

    if ($update_nomor) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&msg=Nomor dokumen berhasil diperbarui!');
        exit;
    }

    $errMsg = urlencode('Gagal memperbarui nomor dokumen: ' . mysqli_error($config));
    header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=' . $errMsg);
    exit;
}

if (isset($_POST['edit_standard_ep'])) {
    $elemen_penilaian_detail = isset($_POST['elemen_penilaian_detail']) ? trim($_POST['elemen_penilaian_detail']) : '';

    if ($kode_pokja_standard === '') {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Kode Pokja tidak ditemukan sehingga Standard EP tidak dapat diperbarui!');
        exit;
    }

    if ($elemen_penilaian_detail === '') {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Standard EP tidak boleh kosong!');
        exit;
    }

    if (stripos($elemen_penilaian_detail, $standard_ep_prefix) === 0) {
        $elemen_penilaian_detail = trim(substr($elemen_penilaian_detail, strlen($standard_ep_prefix)));
    }

    $elemen_penilaian_detail = ltrim($elemen_penilaian_detail, "- \t\n\r\0\x0B");

    if ($elemen_penilaian_detail === '') {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Bagian Standard EP setelah Kode Pokja tidak boleh kosong!');
        exit;
    }

    $elemen_penilaian = $standard_ep_prefix . $elemen_penilaian_detail;

    $panjang_elemen = function_exists('mb_strlen') ? mb_strlen($elemen_penilaian, 'UTF-8') : strlen($elemen_penilaian);

    if ($panjang_elemen > 100) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Standard EP maksimal 100 karakter!');
        exit;
    }

    $update_standard_ep = mysqli_prepare($config, "
        UPDATE tb_pengajuan_dokumen
        SET elemen_penilaian = ?
        WHERE id_pengajuan = ? AND status = 'Selesai'
    ");

    if ($update_standard_ep) {
        $id_pengajuan_update = (int) $id_pengajuan;
        mysqli_stmt_bind_param($update_standard_ep, 'si', $elemen_penilaian, $id_pengajuan_update);
        $update_berhasil = mysqli_stmt_execute($update_standard_ep);
        $update_error = mysqli_stmt_error($update_standard_ep);
        mysqli_stmt_close($update_standard_ep);

        if ($update_berhasil) {
            header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&msg=Standard EP berhasil diperbarui!');
            exit;
        }

        $errMsg = urlencode('Gagal memperbarui Standard EP: ' . $update_error);
    } else {
        $errMsg = urlencode('Gagal menyiapkan pembaruan Standard EP: ' . mysqli_error($config));
    }

    header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=' . $errMsg);
    exit;
}

if (isset($_POST['edit_jenis_dokumen'])) {
    $csrf_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $id_jenis_baru = isset($_POST['id_jenis']) && ctype_digit((string) $_POST['id_jenis'])
        ? (int) $_POST['id_jenis']
        : 0;

    if (!hash_equals($_SESSION['detail_pengesahan_csrf'], $csrf_token)) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Permintaan edit Jenis Dokumen tidak valid. Silakan coba kembali!');
        exit;
    }

    if (!$is_regulasi_workflow) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Jenis Dokumen non-Regulasi dikunci sebagai Dokumen Bukti (DB)!');
        exit;
    }

    $stmt_jenis = mysqli_prepare($config, "
        SELECT id_jenis, nama_jenis, kode_jenis
        FROM tb_jenis_dokumen
        WHERE id_jenis = ? AND TRIM(kode_jenis) <> '' AND UPPER(TRIM(kode_jenis)) <> 'DB'
        LIMIT 1
    ");

    if (!$stmt_jenis) {
        $errMsg = urlencode('Gagal menyiapkan validasi Jenis Dokumen: ' . mysqli_error($config));
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=' . $errMsg);
        exit;
    }

    mysqli_stmt_bind_param($stmt_jenis, 'i', $id_jenis_baru);
    mysqli_stmt_execute($stmt_jenis);
    $jenis_baru = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_jenis));
    mysqli_stmt_close($stmt_jenis);

    if (!$jenis_baru) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Jenis Dokumen yang dipilih tidak valid!');
        exit;
    }

    if ($id_jenis_baru === (int) $data['id_jenis']) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&msg=Jenis Dokumen tidak berubah.');
        exit;
    }

    $has_word_final = !empty($data['file_draft'])
        && preg_match('/\.(doc|docx)$/i', basename($data['file_draft']));
    $has_pdf_final = !empty($data['file_final'])
        && strtolower(pathinfo(basename($data['file_final']), PATHINFO_EXTENSION)) === 'pdf';
    $validasi_file_final = app_validate_final_upload_selection(
        $jenis_baru['kode_jenis'],
        (bool) $has_word_final,
        $has_pdf_final
    );

    if (!$validasi_file_final['ok']) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=' . urlencode($validasi_file_final['message'] . ' Lengkapi file final terlebih dahulu sebelum mengubah Jenis Dokumen.'));
        exit;
    }

    $stmt_update_jenis = mysqli_prepare($config, "
        UPDATE tb_pengajuan_dokumen
        SET id_jenis = ?
        WHERE id_pengajuan = ? AND status = 'Selesai'
    ");

    if ($stmt_update_jenis) {
        $id_pengajuan_update = (int) $id_pengajuan;
        mysqli_stmt_bind_param($stmt_update_jenis, 'ii', $id_jenis_baru, $id_pengajuan_update);
        $update_berhasil = mysqli_stmt_execute($stmt_update_jenis);
        $update_error = mysqli_stmt_error($stmt_update_jenis);
        $baris_diperbarui = $update_berhasil ? mysqli_stmt_affected_rows($stmt_update_jenis) : 0;
        mysqli_stmt_close($stmt_update_jenis);

        if ($update_berhasil && $baris_diperbarui === 1) {
            header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&msg=Jenis Dokumen berhasil diperbarui!');
            exit;
        }

        if ($update_berhasil) {
            $update_error = 'Data tidak berubah karena dokumen tidak lagi berstatus Selesai.';
        }

        $errMsg = urlencode('Gagal memperbarui Jenis Dokumen: ' . $update_error);
    } else {
        $errMsg = urlencode('Gagal menyiapkan pembaruan Jenis Dokumen: ' . mysqli_error($config));
    }

    header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=' . $errMsg);
    exit;
}

if (isset($_POST['edit_bentuk_dokumen'])) {
    $csrf_token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token'])
        ? $_POST['csrf_token']
        : '';
    $bentuk_dokumen = trim((string) ($_POST['bentuk_dokumen'] ?? ''));

    if (!hash_equals($_SESSION['detail_pengesahan_csrf'], $csrf_token)) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Permintaan edit Bentuk Dokumen tidak valid. Silakan coba kembali!');
        exit;
    }

    if (!app_validate_bentuk_dokumen($bentuk_dokumen)) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Bentuk Dokumen yang dipilih tidak valid!');
        exit;
    }

    if ($bentuk_dokumen === trim((string) ($data['bentuk_dokumen'] ?? ''))) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&msg=Bentuk Dokumen tidak berubah.');
        exit;
    }

    $bentuk_baru_regulasi = app_is_bentuk_regulasi($bentuk_dokumen);
    if ($bentuk_baru_regulasi !== $is_regulasi_workflow) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=' . urlencode('Bentuk Dokumen tidak dapat dipindahkan antara alur Regulasi dan non-Regulasi setelah dokumen selesai.'));
        exit;
    }

    $stmt_update_bentuk = mysqli_prepare($config, "
        UPDATE tb_pengajuan_dokumen
        SET bentuk_dokumen = ?
        WHERE id_pengajuan = ? AND status = 'Selesai'
    ");

    if ($stmt_update_bentuk) {
        $id_pengajuan_update = (int) $id_pengajuan;
        mysqli_stmt_bind_param($stmt_update_bentuk, 'si', $bentuk_dokumen, $id_pengajuan_update);
        $update_berhasil = mysqli_stmt_execute($stmt_update_bentuk);
        $update_error = mysqli_stmt_error($stmt_update_bentuk);
        $baris_diperbarui = $update_berhasil ? mysqli_stmt_affected_rows($stmt_update_bentuk) : 0;
        mysqli_stmt_close($stmt_update_bentuk);

        if ($update_berhasil && $baris_diperbarui === 1) {
            header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&msg=Bentuk Dokumen berhasil diperbarui!');
            exit;
        }

        if ($update_berhasil) {
            $update_error = 'Data tidak berubah karena dokumen tidak lagi berstatus Selesai.';
        }

        $errMsg = urlencode('Gagal memperbarui Bentuk Dokumen: ' . $update_error);
    } else {
        $errMsg = urlencode('Gagal menyiapkan pembaruan Bentuk Dokumen: ' . mysqli_error($config));
    }

    header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=' . $errMsg);
    exit;
}

if (isset($_POST['edit_judul_dokumen'])) {
    $judul_dokumen = isset($_POST['judul_dokumen']) ? trim($_POST['judul_dokumen']) : '';

    if ($judul_dokumen === '') {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Judul Dokumen tidak boleh kosong!');
        exit;
    }

    $panjang_judul = function_exists('mb_strlen') ? mb_strlen($judul_dokumen, 'UTF-8') : strlen($judul_dokumen);

    if ($panjang_judul > 255) {
        header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Judul Dokumen maksimal 255 karakter!');
        exit;
    }

    $update_judul = mysqli_prepare($config, "
        UPDATE tb_pengajuan_dokumen
        SET judul_dokumen = ?
        WHERE id_pengajuan = ? AND status = 'Selesai'
    ");

    if ($update_judul) {
        $id_pengajuan_update = (int) $id_pengajuan;
        mysqli_stmt_bind_param($update_judul, 'si', $judul_dokumen, $id_pengajuan_update);
        $update_berhasil = mysqli_stmt_execute($update_judul);
        $update_error = mysqli_stmt_error($update_judul);
        mysqli_stmt_close($update_judul);

        if ($update_berhasil) {
            header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&msg=Judul Dokumen berhasil diperbarui!');
            exit;
        }

        $errMsg = urlencode('Gagal memperbarui Judul Dokumen: ' . $update_error);
    } else {
        $errMsg = urlencode('Gagal menyiapkan pembaruan Judul Dokumen: ' . mysqli_error($config));
    }

    header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=' . $errMsg);
    exit;
}
?>

<section class="content-header">
    <h1>Detail Dokumen Pengesahan (File PDF)</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header d-flex align-items-center" style="position:relative;">
                <h3 class="card-title">Informasi Pengesahan Dokumen</h3>
                <div class="ml-auto" style="position:absolute; right:24px; top:9px;">
                    <?php if (isset($_GET['edit'])): ?>
                        <form method="POST" enctype="multipart/form-data" class="d-inline-block ml-2" onsubmit="return confirm('Update file PDF final dokumen ini?');">
                            <input type="file" name="file_pdf" accept="application/pdf" required>
                            <button type="submit" name="edit_upload_pdf" class="btn btn-sm btn-warning">
                                <i class="fas fa-upload"></i> Update PDF
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php
                    $pdfHeaderFile = !empty($data['file_final']) ? basename($data['file_final']) : '';
                    $wordHeaderFile = !empty($data['file_draft']) ? basename($data['file_draft']) : '';
                    $hasPdfFinal = $pdfHeaderFile !== ''
                        && strtolower(pathinfo($pdfHeaderFile, PATHINFO_EXTENSION)) === 'pdf'
                        && file_exists('../assets/upload/draft_final/' . $pdfHeaderFile);
                    $hasWordFinal = $wordHeaderFile !== ''
                        && preg_match('/\.(doc|docx)$/i', $wordHeaderFile)
                        && file_exists('../assets/upload/draft_final/' . $wordHeaderFile);
                    ?>
                    <?php if ($hasPdfFinal): ?>
                        <a href="../assets/upload/draft_final/<?= rawurlencode($pdfHeaderFile); ?>" class="btn btn-sm btn-success ml-2" download>
                            <i class="fas fa-file-pdf"></i> PDF Final
                        </a>
                    <?php endif; ?>
                    <?php if ($hasWordFinal): ?>
                        <a href="../assets/upload/draft_final/<?= rawurlencode($wordHeaderFile); ?>" class="btn btn-sm btn-info ml-2" download>
                            <i class="fas fa-file-word"></i> Word Final
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">Nomor Surat</th>
                        <td>
                            <?php if ($is_regulasi_workflow): ?>
                                <span class="badge badge-success" style="font-size:1rem;"><?= htmlspecialchars($data['nomor_surat']); ?></span>
                                <button type="button" class="btn btn-sm btn-warning ml-2" data-toggle="modal" data-target="#modalEditNomorDokumen">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-minus-circle mr-1"></i>Tidak memerlukan nomor dokumen</span>
                            <?php endif; ?>
                        </td>
                    </tr>
					<tr>
                        <th width="200">Kode Pokja</th>
                        <td><?= htmlspecialchars($data['kode_pokja']); ?> (<?= htmlspecialchars($data['nama_lengkap']); ?>)</td>
                    </tr>
                    <tr>
                        <th>Standard EP</th>
                        <td>
                            <?= htmlspecialchars($data['elemen_penilaian']); ?>
                            <button type="button" class="btn btn-sm btn-warning ml-2" data-toggle="modal" data-target="#modalEditStandardEP">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th>Jenis Dokumen</th>
                        <td>
                            <?= htmlspecialchars($data['nama_jenis']); ?>
                            <?php if ($is_regulasi_workflow): ?>
                                <button type="button" class="btn btn-sm btn-warning ml-2" data-toggle="modal" data-target="#modalEditJenisDokumen">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            <?php else: ?>
                                <small class="text-muted ml-2">(dikunci otomatis)</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Bentuk Dokumen</th>
                        <td>
                            <?= htmlspecialchars(app_display_bentuk_dokumen($data['bentuk_dokumen'] ?? '')); ?>
                            <button type="button" class="btn btn-sm btn-warning ml-2" data-toggle="modal" data-target="#modalEditBentukDokumen">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th>Judul Dokumen</th>
                        <td>
                            <?= htmlspecialchars($data['judul_dokumen']); ?>
                            <button type="button" class="btn btn-sm btn-warning ml-2" data-toggle="modal" data-target="#modalEditJudulDokumen">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th>Tanggal Dokumen</th>
                        <td><?= date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?></td>
                    </tr>
					<tr>
                        <th>Tanggal Pengajuan</th>
                        <td><?= date('d-m-Y', strtotime($data['tanggal_ajuan'])); ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Pengesahan</th>
                        <td><?= date('d-m-Y H:i', strtotime($tanggal_pengesahan_efektif)); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td><span class="badge bg-success"><?= htmlspecialchars($data['status']); ?></span></td>
                    </tr>
                    <tr>
                        <th>Catatan Admin</th>
                        <td><?= !empty($data['catatan_admin']) ? htmlspecialchars($data['catatan_admin']) : '-'; ?></td>
                    </tr>
                </table>

                <?php if ($is_regulasi_workflow): ?>
                <div class="modal fade" id="modalEditNomorDokumen" tabindex="-1" role="dialog" aria-labelledby="modalEditNomorDokumenLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title" id="modalEditNomorDokumenLabel">Edit Nomor Dokumen</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="nomor_surat">Nomor Dokumen</label>
                                        <input type="text" name="nomor_surat" id="nomor_surat" class="form-control" value="<?= htmlspecialchars($data['nomor_surat']); ?>" required>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_nomor_dokumen" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="modal fade" id="modalEditJenisDokumen" tabindex="-1" role="dialog" aria-labelledby="modalEditJenisDokumenLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['detail_pengesahan_csrf']); ?>">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title" id="modalEditJenisDokumenLabel">Edit Jenis Dokumen</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group mb-0">
                                        <label class="required-label" for="id_jenis">Jenis Dokumen</label>
                                        <select name="id_jenis" id="id_jenis" class="form-control" required>
                                            <?php foreach ($daftar_jenis_dokumen as $jenis_dokumen): ?>
                                                <?php if (strtoupper(trim($jenis_dokumen['kode_jenis'])) === 'DB') continue; ?>
                                                <option value="<?= (int) $jenis_dokumen['id_jenis']; ?>" <?= (int) $jenis_dokumen['id_jenis'] === (int) $data['id_jenis'] ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($jenis_dokumen['nama_jenis']); ?> (<?= htmlspecialchars($jenis_dokumen['kode_jenis']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">
                                            Nomor surat tidak berubah otomatis. Gunakan tombol Edit pada Nomor Surat bila kode jenis pada nomor juga perlu dikoreksi.
                                        </small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_jenis_dokumen" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Simpan Jenis Dokumen
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalEditBentukDokumen" tabindex="-1" role="dialog" aria-labelledby="modalEditBentukDokumenLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['detail_pengesahan_csrf']); ?>">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title" id="modalEditBentukDokumenLabel">Edit Bentuk Dokumen</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group mb-0">
                                        <label class="required-label" for="bentuk_dokumen">Bentuk Dokumen</label>
                                        <select name="bentuk_dokumen" id="bentuk_dokumen" class="form-control" required>
                                            <option value="">-- Pilih Bentuk Dokumen --</option>
                                            <?php foreach (app_bentuk_dokumen_options() as $bentuk): ?>
                                                <?php if (app_is_bentuk_regulasi($bentuk) !== $is_regulasi_workflow) continue; ?>
                                                <option value="<?= htmlspecialchars($bentuk); ?>" <?= $bentuk === ($data['bentuk_dokumen'] ?? '') ? 'selected' : ''; ?>>
                                                    <?= htmlspecialchars($bentuk); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_bentuk_dokumen" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Simpan Bentuk Dokumen
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalEditStandardEP" tabindex="-1" role="dialog" aria-labelledby="modalEditStandardEPLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title" id="modalEditStandardEPLabel">Edit Standard EP</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label class="required-label" for="elemen_penilaian_detail">Standard EP</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control font-weight-bold" value="<?= htmlspecialchars($standard_ep_prefix); ?>" style="max-width: 120px; background-color: #e9ecef;" aria-label="Kode Pokja" title="Kode Pokja tidak dapat diubah" readonly tabindex="-1">
                                            <input type="text" name="elemen_penilaian_detail" id="elemen_penilaian_detail" class="form-control" maxlength="<?= $maksimal_detail_standard; ?>" value="<?= htmlspecialchars($standard_ep_detail); ?>" placeholder="Contoh: 3 EP 7" required autofocus>
                                        </div>
                                        <small class="form-text text-muted">Kode Pokja ditetapkan otomatis dan tidak dapat diubah.</small>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_standard_ep" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Simpan Standard EP
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="modalEditJudulDokumen" tabindex="-1" role="dialog" aria-labelledby="modalEditJudulDokumenLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title" id="modalEditJudulDokumenLabel">Edit Judul Dokumen</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="form-group mb-0">
                                        <label class="required-label" for="judul_dokumen">Judul Dokumen</label>
                                        <textarea name="judul_dokumen" id="judul_dokumen" class="form-control" rows="4" maxlength="255" required><?= htmlspecialchars($data['judul_dokumen']); ?></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                                    <button type="submit" name="edit_judul_dokumen" class="btn btn-warning">
                                        <i class="fas fa-save"></i> Simpan Judul Dokumen
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <hr>
                <h5>Preview Dokumen PDF:</h5>

                <?php
                    $pdfPreviewFile = $hasPdfFinal ? $pdfHeaderFile : '';
                ?>
                <?php if (!empty($pdfPreviewFile)): ?>
                    <?php
                        $file_url = '../assets/upload/draft_final/' . rawurlencode($pdfPreviewFile);
                    ?>

                    <!-- Tombol Download -->
                    <a href="<?= htmlspecialchars($file_url); ?>" class="btn btn-success mb-3 float-right ml-2" download>
                        <i class="fas fa-download"></i> Download File PDF
                    </a>

                    <!-- Tombol Cetak -->
                    <button type="button" class="btn btn-primary mb-3 float-right" onclick="printPDF('<?= htmlspecialchars($file_url); ?>')">
                        <i class="fas fa-print"></i> Cetak PDF
                    </button>

                    <!-- Preview PDF -->
                    <iframe 
                        src="<?= htmlspecialchars($file_url); ?>"
                        style="width:100%; height:600px;" 
                        frameborder="0">
                    </iframe>

                    <script>
                        function printPDF(url) {
                            // Buka PDF di tab baru, lalu otomatis print
                            const printWindow = window.open(url, '_blank');
                            printWindow.addEventListener('load', function() {
                                printWindow.print();
                            });
                        }
                    </script>
                <?php else: ?>
                    <p><em>Dokumen ini tidak memiliki file final PDF.</em></p>
                    <?php if ($hasWordFinal): ?>
                        <a href="../assets/upload/draft_final/<?= rawurlencode($wordHeaderFile); ?>" class="btn btn-info mb-3" download>
                            <i class="fas fa-file-word"></i> Download File Word
                        </a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <a class="btn btn-app bg-warning" href="main_admin.php?unit=pengesahan">
                    <i class="fas fa-reply"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</section>
