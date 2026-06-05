<?php
require_once("../config/koneksi.php");
require_once("../config/upload_helper.php");

$checkNoTlpColumn = mysqli_query($config, "SHOW COLUMNS FROM tb_pengajuan_dokumen LIKE 'no_tlp'");
$hasNoTlpColumn = $checkNoTlpColumn && mysqli_num_rows($checkNoTlpColumn) > 0;

if (!isset($_GET['id_pengajuan'])) {
    header('Location: main_admin.php?unit=pengajuan&err=ID pengajuan tidak ditemukan!');
    exit;
}

$id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
$query = mysqli_query($config, "
    SELECT p.*, u.kode_pokja, u.nama_lengkap
    FROM tb_pengajuan_dokumen p
    LEFT JOIN tb_user u ON p.id_user = u.id_user
    WHERE p.id_pengajuan = '$id_pengajuan'
");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    header('Location: main_admin.php?unit=pengajuan&err=Data pengajuan tidak ditemukan!');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_jenis = mysqli_real_escape_string($config, $_POST['id_jenis'] ?? '');
    $elemen_penilaian = mysqli_real_escape_string($config, $_POST['elemen_penilaian'] ?? '');
    $judul_dokumen = mysqli_real_escape_string($config, $_POST['judul_dokumen'] ?? '');
    $tanggal_dokumen = mysqli_real_escape_string($config, $_POST['tanggal_dokumen'] ?? '');
    $tanggal_ajuan = mysqli_real_escape_string($config, $_POST['tanggal_ajuan'] ?? '');
    $nomor_surat = mysqli_real_escape_string($config, $_POST['nomor_surat'] ?? '');
    $status = mysqli_real_escape_string($config, $_POST['status'] ?? '');
    $catatan = mysqli_real_escape_string($config, $_POST['catatan'] ?? '');
    $catatan_admin = mysqli_real_escape_string($config, $_POST['catatan_admin'] ?? '');
    $no_tlp = mysqli_real_escape_string($config, $_POST['no_tlp'] ?? '');
    $file_draft = $data['file_draft'];
    $file_final = $data['file_final'];

    $allowedStatus = ['Menunggu Verifikasi', 'Disetujui', 'Ditolak', 'Selesai'];
    if (!in_array($status, $allowedStatus, true)) {
        header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=Status tidak valid!');
        exit;
    }

    if ($hasNoTlpColumn && $no_tlp !== '' && !app_validate_phone_id($no_tlp)) {
        header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=Nomor telepon tidak valid!');
        exit;
    }

    if (!empty($_FILES['file_draft']['name'])) {
        $upload = app_store_uploaded_file(
            $_FILES['file_draft'],
            '../assets/upload/draft_word/',
            'pengajuan_' . $id_pengajuan,
            ['doc', 'docx', 'pdf'],
            [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/pdf',
                'application/zip',
                'application/octet-stream'
            ],
            15 * 1024 * 1024
        );

        if (!$upload['ok']) {
            header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=' . urlencode($upload['message']));
            exit;
        }

        if (!empty($data['file_draft']) && file_exists('../assets/upload/draft_word/' . $data['file_draft'])) {
            unlink('../assets/upload/draft_word/' . $data['file_draft']);
        }

        $file_draft = $upload['filename'];
        if ($status === 'Selesai') {
            $file_final = $upload['filename'];
        }
    }

    $setParts = [
        "id_jenis = '$id_jenis'",
        "elemen_penilaian = '$elemen_penilaian'",
        "judul_dokumen = '$judul_dokumen'",
        "tanggal_dokumen = '$tanggal_dokumen'",
        "tanggal_ajuan = '$tanggal_ajuan'",
        "nomor_surat = '$nomor_surat'",
        "status = '$status'",
        "catatan = '$catatan'",
        "catatan_admin = '$catatan_admin'",
        "file_draft = '$file_draft'",
        "file_final = '$file_final'"
    ];

    if ($hasNoTlpColumn) {
        $setParts[] = "no_tlp = '$no_tlp'";
    }

    $update = mysqli_query($config, "
        UPDATE tb_pengajuan_dokumen
        SET " . implode(', ', $setParts) . "
        WHERE id_pengajuan = '$id_pengajuan'
    ");

    if ($update) {
        header('Location: main_admin.php?unit=pengajuan&msg=Data pengajuan berhasil diperbarui!');
        exit;
    }

    $errMsg = urlencode('Gagal memperbarui data pengajuan: ' . mysqli_error($config));
    header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=' . $errMsg);
    exit;
}
?>

<section class="content-header">
    <h1>Edit Pengajuan Dokumen</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Form Edit Pengajuan dari Pokja</h3>
            </div>

            <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Simpan perubahan pengajuan ini?');">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Pokja</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($data['kode_pokja'] . ' - ' . $data['nama_lengkap']); ?>" readonly>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control" required>
                                    <?php foreach (['Menunggu Verifikasi', 'Disetujui', 'Ditolak', 'Selesai'] as $statusOption): ?>
                                        <option value="<?= $statusOption; ?>" <?= $data['status'] === $statusOption ? 'selected' : ''; ?>>
                                            <?= $statusOption; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Standard EP</label>
                                <input type="text" name="elemen_penilaian" class="form-control" value="<?= htmlspecialchars($data['elemen_penilaian']); ?>" required>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Jenis Dokumen</label>
                                <select name="id_jenis" class="form-control" required>
                                    <option value="">-- Pilih Jenis Dokumen --</option>
                                    <?php
                                    $qJenis = mysqli_query($config, "SELECT * FROM tb_jenis_dokumen ORDER BY nama_jenis ASC");
                                    while ($jenis = mysqli_fetch_assoc($qJenis)) {
                                        $selected = ($jenis['id_jenis'] == $data['id_jenis']) ? 'selected' : '';
                                        echo "<option value='{$jenis['id_jenis']}' {$selected}>" . htmlspecialchars($jenis['nama_jenis']) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Judul Dokumen</label>
                        <input type="text" name="judul_dokumen" class="form-control" value="<?= htmlspecialchars($data['judul_dokumen']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Tanggal Dokumen</label>
                                <input type="date" name="tanggal_dokumen" class="form-control" value="<?= htmlspecialchars($data['tanggal_dokumen']); ?>" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Tanggal Ajuan</label>
                                <input type="date" name="tanggal_ajuan" class="form-control" value="<?= htmlspecialchars($data['tanggal_ajuan']); ?>" required>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="form-group">
                                <label>Nomor Surat</label>
                                <input type="text" name="nomor_surat" class="form-control" value="<?= htmlspecialchars($data['nomor_surat'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>

                    <?php if ($hasNoTlpColumn): ?>
                    <div class="form-group">
                        <label>Nomor Telepon WhatsApp</label>
                        <input type="text" name="no_tlp" class="form-control" value="<?= htmlspecialchars($data['no_tlp'] ?? ''); ?>" pattern="(\+?62|0)8[0-9]{8,13}">
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Catatan Awal</label>
                                <textarea name="catatan" class="form-control" rows="4"><?= htmlspecialchars($data['catatan'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label>Catatan Admin</label>
                                <textarea name="catatan_admin" class="form-control" rows="4"><?= htmlspecialchars($data['catatan_admin'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>File Draft / Final <small>(kosongkan jika tidak ingin diubah)</small></label><br>
                        <?php if (!empty($data['file_draft'])): ?>
                            <a href="../assets/upload/draft_word/<?= htmlspecialchars($data['file_draft']); ?>" target="_blank" class="btn btn-sm btn-info mb-2">
                                <i class="fas fa-file"></i> Lihat File Saat Ini
                            </a><br>
                        <?php endif; ?>
                        <input type="file" name="file_draft" class="form-control-file" accept=".doc,.docx,.pdf">
                        <small class="form-text text-muted">Format: Word atau PDF, maksimal 15MB.</small>
                    </div>
                </div>

                <div class="card-footer">
                    <a class="btn btn-app bg-warning float-left" href="main_admin.php?unit=pengajuan">
                        <i class="fas fa-reply"></i> Kembali
                    </a>
                    <button class="btn btn-app bg-success float-right" type="submit">
                        <i class="fas fa-save"></i> Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
