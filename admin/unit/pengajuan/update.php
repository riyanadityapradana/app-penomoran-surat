<?php
require_once("../config/koneksi.php");
require_once("../config/upload_helper.php");
require_once("../config/dokumen_helper.php");

if (empty($_SESSION['pengajuan_admin_csrf'])) {
    $_SESSION['pengajuan_admin_csrf'] = bin2hex(random_bytes(32));
}

$id_pengajuan = isset($_GET['id_pengajuan']) && ctype_digit((string) $_GET['id_pengajuan'])
    ? (int) $_GET['id_pengajuan']
    : 0;

if ($id_pengajuan <= 0) {
    header('Location: main_admin.php?unit=pengajuan&err=ID pengajuan tidak valid!');
    exit;
}

$stmtData = mysqli_prepare($config, "
    SELECT p.*, u.kode_pokja, u.nama_lengkap, j.nama_jenis, j.kode_jenis
    FROM tb_pengajuan_dokumen p
    LEFT JOIN tb_user u ON p.id_user = u.id_user
    LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
    WHERE p.id_pengajuan = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmtData, 'i', $id_pengajuan);
mysqli_stmt_execute($stmtData);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtData));
mysqli_stmt_close($stmtData);

if (!$data) {
    header('Location: main_admin.php?unit=pengajuan&err=Data pengajuan tidak ditemukan!');
    exit;
}

if ($data['status'] === 'Selesai') {
    header('Location: main_admin.php?unit=pengajuan&err=Dokumen selesai diedit melalui halaman Dokumen Sah!');
    exit;
}

$qNoTlp = mysqli_query($config, "SHOW COLUMNS FROM tb_pengajuan_dokumen LIKE 'no_tlp'");
$hasNoTlp = $qNoTlp && mysqli_num_rows($qNoTlp) > 0;
$jenisTerkunci = trim((string) ($data['nomor_surat'] ?? '')) !== '';

function hapus_draft_pengajuan_admin($filename)
{
    if (empty($filename)) {
        return true;
    }

    $uploadDir = realpath(__DIR__ . '/../../../assets/upload/draft_word');
    if ($uploadDir === false) {
        return false;
    }

    $path = $uploadDir . DIRECTORY_SEPARATOR . basename($filename);
    return !is_file($path) || unlink($path);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['pengajuan_admin_csrf'], $csrf)) {
        header('Location: main_admin.php?unit=pengajuan&err=Permintaan edit tidak valid. Silakan coba kembali!');
        exit;
    }

    $judul = trim((string) ($_POST['judul_dokumen'] ?? ''));
    $tanggal = trim((string) ($_POST['tanggal_dokumen'] ?? ''));
    $standardEpInput = trim((string) ($_POST['elemen_penilaian'] ?? ''));
    $catatan = trim((string) ($_POST['catatan'] ?? ''));
    $noTlp = trim((string) ($_POST['no_telepon'] ?? ''));
    $idJenis = $jenisTerkunci ? (int) $data['id_jenis'] : (int) ($_POST['id_jenis'] ?? 0);
    $bentukDokumen = $jenisTerkunci
        ? trim((string) ($data['bentuk_dokumen'] ?? ''))
        : trim((string) ($_POST['bentuk_dokumen'] ?? ''));

    if ($judul === '' || $standardEpInput === '') {
        header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=Standard EP dan judul dokumen wajib diisi!');
        exit;
    }

    $tanggalParts = explode('-', $tanggal);
    $tanggalValid = count($tanggalParts) === 3
        && ctype_digit($tanggalParts[0])
        && ctype_digit($tanggalParts[1])
        && ctype_digit($tanggalParts[2])
        && checkdate((int) $tanggalParts[1], (int) $tanggalParts[2], (int) $tanggalParts[0]);
    if (!$tanggalValid) {
        header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=Tanggal dokumen tidak valid!');
        exit;
    }

    if (!app_validate_bentuk_dokumen($bentukDokumen)) {
        header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=Bentuk dokumen tidak valid!');
        exit;
    }

    $jenisResult = app_resolve_jenis_pengajuan($config, $bentukDokumen, $idJenis);
    if (!$jenisResult['ok']) {
        header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=' . urlencode($jenisResult['message']));
        exit;
    }
    $jenisValid = $jenisResult['data'];
    $idJenis = (int) $jenisValid['id_jenis'];

    $uploadRules = app_pengajuan_draft_upload_rules($bentukDokumen);

    if ($hasNoTlp && !app_validate_phone_id($noTlp)) {
        header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=Nomor WhatsApp tidak valid!');
        exit;
    }

    $kodePokja = trim((string) $data['kode_pokja']);
    $prefixPattern = '/^' . preg_quote($kodePokja, '/') . '\\s*-\\s*/i';
    $standardEpInput = trim(preg_replace($prefixPattern, '', $standardEpInput));
    $standardEp = $kodePokja . '-' . $standardEpInput;

    $fileDraft = (string) ($data['file_draft'] ?? '');
    $fileDraftBaru = '';
    $fileExtensionLama = strtolower(pathinfo($fileDraft, PATHINFO_EXTENSION));
    if (empty($_FILES['file_draft']['name']) && !in_array($fileExtensionLama, $uploadRules['extensions'], true)) {
        header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=File lama tidak sesuai dengan Bentuk Dokumen yang dipilih. Unggah file pengganti dengan format yang diwajibkan!');
        exit;
    }

    if (!empty($_FILES['file_draft']['name'])) {
        $upload = app_store_uploaded_file(
            $_FILES['file_draft'],
            '../assets/upload/draft_word/',
            'draft_' . (int) $data['id_user'],
            $uploadRules['extensions'],
            $uploadRules['mimes'],
            $uploadRules['max_bytes']
        );

        if (!$upload['ok']) {
            header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=' . urlencode($upload['message']));
            exit;
        }

        $fileDraftBaru = $upload['filename'];
        $fileDraft = $fileDraftBaru;
    }

    if ($hasNoTlp) {
        $stmtUpdate = mysqli_prepare($config, "
            UPDATE tb_pengajuan_dokumen
            SET id_jenis = ?, bentuk_dokumen = ?, judul_dokumen = ?, tanggal_dokumen = ?, elemen_penilaian = ?,
                catatan = ?, no_tlp = ?, file_draft = ?
            WHERE id_pengajuan = ? AND status <> 'Selesai'
        ");
        mysqli_stmt_bind_param($stmtUpdate, 'isssssssi', $idJenis, $bentukDokumen, $judul, $tanggal, $standardEp, $catatan, $noTlp, $fileDraft, $id_pengajuan);
    } else {
        $stmtUpdate = mysqli_prepare($config, "
            UPDATE tb_pengajuan_dokumen
            SET id_jenis = ?, bentuk_dokumen = ?, judul_dokumen = ?, tanggal_dokumen = ?, elemen_penilaian = ?,
                catatan = ?, file_draft = ?
            WHERE id_pengajuan = ? AND status <> 'Selesai'
        ");
        mysqli_stmt_bind_param($stmtUpdate, 'issssssi', $idJenis, $bentukDokumen, $judul, $tanggal, $standardEp, $catatan, $fileDraft, $id_pengajuan);
    }

    $updated = mysqli_stmt_execute($stmtUpdate);
    $updateError = mysqli_stmt_error($stmtUpdate);
    mysqli_stmt_close($stmtUpdate);

    if (!$updated) {
        if ($fileDraftBaru !== '') {
            hapus_draft_pengajuan_admin($fileDraftBaru);
        }
        header('Location: main_admin.php?unit=update_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=' . urlencode('Gagal memperbarui pengajuan: ' . $updateError));
        exit;
    }

    if ($fileDraftBaru !== '' && !empty($data['file_draft']) && $data['file_draft'] !== $fileDraftBaru) {
        hapus_draft_pengajuan_admin($data['file_draft']);
    }

    header('Location: main_admin.php?unit=pengajuan&msg=Data pengajuan berhasil diperbarui!');
    exit;
}

$standardEpValue = (string) ($data['elemen_penilaian'] ?? '');
$prefixPattern = '/^' . preg_quote((string) $data['kode_pokja'], '/') . '\\s*-\\s*/i';
$standardEpValue = preg_replace($prefixPattern, '', $standardEpValue);
?>

<section class="content-header">
    <div class="container-fluid">
        <h1>Edit Pengajuan Dokumen</h1>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-edit mr-2"></i>Form Edit Dokumen</h3>
            </div>
            <form method="post" enctype="multipart/form-data" class="js-confirm-submit" data-confirm-message="Simpan perubahan dokumen ini?">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['pengajuan_admin_csrf']); ?>">
                <div class="card-body">
                    <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap">
                        <div>
                            <strong><?= htmlspecialchars($data['kode_pokja']); ?></strong>
                            <span class="text-muted ml-2"><?= htmlspecialchars($data['nama_lengkap']); ?></span>
                        </div>
                        <span class="badge badge-secondary mt-1 mb-1"><?= htmlspecialchars($data['status']); ?></span>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Standard EP <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-bold"><?= htmlspecialchars($data['kode_pokja']); ?> -</span>
                                    </div>
                                    <input type="text" name="elemen_penilaian" class="form-control" value="<?= htmlspecialchars($standardEpValue); ?>" placeholder="Contoh: 3 EP 1" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Jenis Dokumen <span class="text-danger">*</span></label>
                                <?php if ($jenisTerkunci): ?>
                                    <input type="text" class="form-control" value="<?= htmlspecialchars($data['nama_jenis'] . ' (' . $data['kode_jenis'] . ')'); ?>" readonly>
                                    <small class="form-text text-muted">Jenis dokumen dikunci karena nomor surat sudah terbit.</small>
                                <?php else: ?>
                                    <select name="id_jenis" id="jenisDokumen" class="form-control select2" required>
                                        <option value="">-- Pilih Jenis Dokumen --</option>
                                        <?php
                                        $qJenis = mysqli_query($config, "SELECT id_jenis, nama_jenis, kode_jenis FROM tb_jenis_dokumen WHERE TRIM(kode_jenis) <> '' ORDER BY nama_jenis ASC");
                                        while ($jenis = mysqli_fetch_assoc($qJenis)):
                                        ?>
                                            <option value="<?= (int) $jenis['id_jenis']; ?>" data-kode-jenis="<?= htmlspecialchars(strtoupper(trim($jenis['kode_jenis']))); ?>" <?= (int) $jenis['id_jenis'] === (int) $data['id_jenis'] ? 'selected' : ''; ?>>
                                                <?= htmlspecialchars($jenis['nama_jenis'] . ' (' . $jenis['kode_jenis'] . ')'); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <small class="form-text text-muted" id="jenisDokumenHelp"></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="bentukDokumen">Bentuk Dokumen <span class="text-danger">*</span></label>
                        <?php if ($jenisTerkunci): ?>
                            <input type="text" class="form-control" value="<?= htmlspecialchars(app_display_bentuk_dokumen($data['bentuk_dokumen'] ?? '')); ?>" readonly>
                            <small class="form-text text-muted">Bentuk dokumen dikunci karena nomor surat sudah terbit.</small>
                        <?php else: ?>
                            <select name="bentuk_dokumen" id="bentukDokumen" class="form-control select2" required>
                                <option value="">-- Pilih Bentuk Dokumen --</option>
                                <?php foreach (app_bentuk_dokumen_options() as $bentuk): ?>
                                    <option value="<?= htmlspecialchars($bentuk); ?>" <?= $bentuk === ($data['bentuk_dokumen'] ?? '') ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($bentuk); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Judul Dokumen <span class="text-danger">*</span></label>
                        <input type="text" name="judul_dokumen" class="form-control" maxlength="255" value="<?= htmlspecialchars($data['judul_dokumen']); ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tanggal Dokumen <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_dokumen" class="form-control" value="<?= htmlspecialchars($data['tanggal_dokumen']); ?>" required>
                            </div>
                        </div>
                        <?php if ($hasNoTlp): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nomor WhatsApp <span class="text-danger">*</span></label>
                                <input type="text" name="no_telepon" class="form-control js-phone-id" pattern="(\+?62|0)8[0-9]{8,13}" value="<?= htmlspecialchars($data['no_tlp'] ?? ''); ?>" required>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label>Catatan</label>
                        <textarea name="catatan" class="form-control" rows="3" placeholder="Catatan pengajuan dokumen"><?= htmlspecialchars($data['catatan'] ?? ''); ?></textarea>
                    </div>

                    <?php
                    $currentUploadRules = app_pengajuan_draft_upload_rules($data['bentuk_dokumen'] ?? '');
                    $currentFileExtension = strtolower(pathinfo((string) ($data['file_draft'] ?? ''), PATHINFO_EXTENSION));
                    $currentFileIsPdf = strtolower(pathinfo((string) ($data['file_draft'] ?? ''), PATHINFO_EXTENSION)) === 'pdf';
                    ?>
                    <div class="form-group">
                        <label id="draftFileLabel"><?= htmlspecialchars($currentUploadRules['label']); ?> <small class="text-muted">(opsional)</small></label>
                        <?php if (!empty($data['file_draft'])): ?>
                            <div class="mb-2">
                                <a href="../assets/upload/draft_word/<?= rawurlencode(basename($data['file_draft'])); ?>" target="_blank" class="btn btn-sm <?= $currentFileIsPdf ? 'btn-danger' : 'btn-info'; ?>">
                                    <i class="fas <?= $currentFileIsPdf ? 'fa-file-pdf' : 'fa-file-word'; ?> mr-1"></i>Lihat File <?= $currentFileIsPdf ? 'PDF' : 'Word'; ?> Saat Ini
                                </a>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="file_draft" id="draftFileInput" class="form-control-file js-file-preview" data-current-extension="<?= htmlspecialchars($currentFileExtension); ?>" accept="<?= htmlspecialchars($currentUploadRules['accept']); ?>">
                        <small class="form-text text-muted" id="draftFileHelp"><?= htmlspecialchars($currentUploadRules['help']); ?></small>
                        <small class="form-text text-muted" id="draftFileInfo">Kosongkan jika file tidak ingin diganti.</small>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="main_admin.php?unit=pengajuan" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i>Kembali
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save mr-1"></i>Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var bentukSelect = document.getElementById('bentukDokumen');
    var jenisSelect = document.getElementById('jenisDokumen');
    var draftFileLabel = document.getElementById('draftFileLabel');
    var draftFileHelp = document.getElementById('draftFileHelp');
    var fileInput = document.querySelector('.js-file-preview');
    var fileInfo = document.getElementById('draftFileInfo');
    var jenisHelp = document.getElementById('jenisDokumenHelp');

    function refreshJenisSelect() {
        if (window.jQuery && window.jQuery(jenisSelect).hasClass('select2-hidden-accessible')) {
            window.jQuery(jenisSelect).trigger('change.select2');
        }
    }

    function sinkronkanBentukDokumen() {
        if (!bentukSelect || !jenisSelect || !fileInput || !draftFileLabel || !draftFileHelp || !jenisHelp) return;

        var bentuk = bentukSelect.value;
        var isRegulasi = bentuk === 'Regulasi';
        var dbOption = Array.prototype.find.call(jenisSelect.options, function(option) {
            return option.getAttribute('data-kode-jenis') === 'DB';
        });
        var currentExtension = (fileInput.getAttribute('data-current-extension') || '').toLowerCase();
        var currentFileValid = isRegulasi
            ? currentExtension === 'doc' || currentExtension === 'docx'
            : currentExtension === 'pdf';

        fileInput.value = '';
        if (!bentuk) {
            jenisSelect.value = '';
            jenisSelect.disabled = true;
            jenisSelect.required = false;
            fileInput.disabled = true;
            fileInput.required = false;
            fileInput.accept = '.doc,.docx,.pdf,application/pdf';
            draftFileLabel.innerHTML = 'File Dokumen <small class="text-muted">(pilih Bentuk Dokumen terlebih dahulu)</small>';
            draftFileHelp.textContent = 'Pilih Bentuk Dokumen terlebih dahulu.';
            jenisHelp.textContent = 'Pilih Bentuk Dokumen terlebih dahulu.';
            refreshJenisSelect();
            return;
        } else if (isRegulasi) {
            fileInput.disabled = false;
            jenisSelect.disabled = false;
            jenisSelect.required = true;
            if (dbOption) dbOption.disabled = true;
            if (jenisSelect.options[jenisSelect.selectedIndex] && jenisSelect.options[jenisSelect.selectedIndex].getAttribute('data-kode-jenis') === 'DB') {
                jenisSelect.value = '';
            }
            fileInput.accept = '.doc,.docx';
            draftFileLabel.innerHTML = 'File Draft Regulasi (Word, maksimal 10MB) <small class="text-muted">(opsional)</small>';
            draftFileHelp.textContent = currentFileValid
                ? 'File lama sudah berformat Word. Pilih file hanya jika ingin menggantinya.'
                : 'File lama tidak sesuai. Wajib unggah file Word (DOC/DOCX).';
            jenisHelp.textContent = 'Jenis Dokumen wajib dipilih untuk Regulasi.';
        } else {
            fileInput.disabled = false;
            if (dbOption) {
                dbOption.disabled = false;
                jenisSelect.value = dbOption.value;
            }
            jenisSelect.required = false;
            jenisSelect.disabled = true;
            fileInput.accept = '.pdf,application/pdf';
            draftFileLabel.innerHTML = 'File Dokumen (PDF, maksimal 10MB) <small class="text-muted">(opsional)</small>';
            draftFileHelp.textContent = currentFileValid
                ? 'File lama sudah berformat PDF. Pilih file hanya jika ingin menggantinya.'
                : 'File lama tidak sesuai. Wajib unggah file PDF.';
            jenisHelp.textContent = 'Jenis Dokumen otomatis dikunci sebagai Dokumen Bukti (DB).';
        }

        fileInput.required = !currentFileValid;
        refreshJenisSelect();
    }

    sinkronkanBentukDokumen();
    if (bentukSelect) {
        bentukSelect.addEventListener('change', sinkronkanBentukDokumen);
        if (window.jQuery) {
            window.jQuery(bentukSelect).on('change select2:select select2:clear', sinkronkanBentukDokumen);
        }
    }

    if (fileInput && fileInfo) {
        fileInput.addEventListener('change', function() {
            fileInfo.textContent = fileInput.files.length
                ? fileInput.files[0].name + ' (' + (fileInput.files[0].size / 1024 / 1024).toFixed(2) + ' MB)'
                : 'Kosongkan jika file tidak ingin diganti.';
        });
    }

    var phoneInput = document.querySelector('.js-phone-id');
    if (phoneInput) {
        phoneInput.addEventListener('input', function() {
            phoneInput.value = phoneInput.value.replace(/[^\d+]/g, '');
        });
    }

    var form = document.querySelector('.js-confirm-submit');
    if (form) {
        form.addEventListener('submit', function(event) {
            if (!confirm(form.dataset.confirmMessage)) {
                event.preventDefault();
            }
        });
    }
});
</script>
