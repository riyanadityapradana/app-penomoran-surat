<?php
require_once("../config/upload_helper.php");
require_once("../config/dokumen_helper.php");

if (!isset($_SESSION['id_user'])) {
    echo "<script>alert('Silakan login terlebih dahulu!');window.location='../login.php';</script>";
    exit;
}

$user_id = $_SESSION['id_user'];
$query_user = mysqli_query($config, "SELECT kode_pokja FROM tb_user WHERE id_user = '$user_id'");
$user_data = mysqli_fetch_assoc($query_user);
$kode_pokja = $user_data['kode_pokja'] ?? '';

$checkNoTlpColumn = mysqli_query($config, "SHOW COLUMNS FROM tb_pengajuan_dokumen LIKE 'no_tlp'");
$hasNoTlpColumn = $checkNoTlpColumn && mysqli_num_rows($checkNoTlpColumn) > 0;

if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
    $query = mysqli_query($config, "
        SELECT p.*, j.kode_jenis
        FROM tb_pengajuan_dokumen p
        LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
        WHERE p.id_pengajuan = '$id_pengajuan' AND p.id_user = '{$_SESSION['id_user']}'
    ");
    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        echo "<script>alert('Data pengajuan tidak ditemukan!');window.location='main_pokja.php?unit=pengajuan';</script>";
        exit;
    }
} else {
    echo "<script>alert('Parameter ID tidak ditemukan!');window.location='main_pokja.php?unit=pengajuan';</script>";
    exit;
}

if ($data['status'] != 'Menunggu Verifikasi') {
    echo "<script>alert('Data tidak dapat diedit karena status sudah disetujui, selesai, atau ditolak!');window.location='main_pokja.php?unit=pengajuan';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_pengajuan = mysqli_real_escape_string($config, $data['id_pengajuan']);
    $id_jenis = isset($_POST['id_jenis']) ? (int) $_POST['id_jenis'] : 0;
    $bentuk_dokumen = trim((string) ($_POST['bentuk_dokumen'] ?? ''));
    $judul_dokumen = mysqli_real_escape_string($config, $_POST['judul_dokumen']);
    $tanggal_dokumen = mysqli_real_escape_string($config, $_POST['tanggal_dokumen']);
    $no_tel = mysqli_real_escape_string($config, $_POST['no_telepon'] ?? '');
    $elemen_penilaian_input = mysqli_real_escape_string($config, $_POST['elemen_penilaian']);
    $elemen_penilaian = $kode_pokja . '-' . $elemen_penilaian_input;
    $file_draft = (string) ($data['file_draft'] ?? '');
    $file_draft_baru = '';

    if (!app_validate_bentuk_dokumen($bentuk_dokumen)) {
        echo "<script>alert('Bentuk dokumen tidak valid. Silakan pilih kembali dari daftar.');window.location='main_pokja.php?unit=update_pengajuan&id_pengajuan={$id_pengajuan}';</script>";
        exit;
    }

    $jenisResult = app_resolve_jenis_pengajuan($config, $bentuk_dokumen, $id_jenis);
    if (!$jenisResult['ok']) {
        echo "<script>alert('" . addslashes($jenisResult['message']) . "');window.location='main_pokja.php?unit=update_pengajuan&id_pengajuan={$id_pengajuan}';</script>";
        exit;
    }
    $jenisValid = $jenisResult['data'];
    $id_jenis = (int) $jenisValid['id_jenis'];

    $bentuk_dokumen_sql = mysqli_real_escape_string($config, $bentuk_dokumen);

    $uploadRules = app_pengajuan_draft_upload_rules($bentuk_dokumen);
    $fileExtensionLama = strtolower(pathinfo($file_draft, PATHINFO_EXTENSION));
    if (empty($_FILES['file_draft']['name']) && !in_array($fileExtensionLama, $uploadRules['extensions'], true)) {
        echo "<script>alert('File lama tidak sesuai dengan Bentuk Dokumen yang dipilih. Silakan unggah file pengganti dengan format yang diwajibkan.');window.location='main_pokja.php?unit=update_pengajuan&id_pengajuan={$id_pengajuan}';</script>";
        exit;
    }

    if ($hasNoTlpColumn && !app_validate_phone_id($no_tel)) {
        echo "<script>alert('Nomor telepon tidak valid. Gunakan format 08xxxxxxxxxx atau 62xxxxxxxxxx.');window.location='main_pokja.php?unit=update_pengajuan&id_pengajuan={$id_pengajuan}';</script>";
        exit;
    }

    if (!empty($_FILES['file_draft']['name'])) {
        $upload = app_store_uploaded_file(
            $_FILES['file_draft'],
            '../assets/upload/draft_word/',
            'draft_' . $_SESSION['id_user'],
            $uploadRules['extensions'],
            $uploadRules['mimes'],
            $uploadRules['max_bytes']
        );

        if (!$upload['ok']) {
            echo "<script>alert('" . addslashes($upload['message']) . "');window.location='main_pokja.php?unit=update_pengajuan&id_pengajuan={$id_pengajuan}';</script>";
            exit;
        } else {
            $file_draft_baru = $upload['filename'];
            $file_draft = $file_draft_baru;
        }
    }

    $setParts = [
        "id_jenis = '$id_jenis'",
        "bentuk_dokumen = '$bentuk_dokumen_sql'",
        "judul_dokumen = '$judul_dokumen'",
        "tanggal_dokumen = '$tanggal_dokumen'",
        "elemen_penilaian = '$elemen_penilaian'",
        "file_draft = '$file_draft'"
    ];

    if ($hasNoTlpColumn) {
        $setParts[] = "no_tlp = '$no_tel'";
    }

    $query_update = "UPDATE tb_pengajuan_dokumen SET " . implode(', ', $setParts) . " WHERE id_pengajuan = '$id_pengajuan' AND id_user = '{$_SESSION['id_user']}'";

    if (mysqli_query($config, $query_update)) {
        if ($file_draft_baru !== '' && !empty($data['file_draft']) && $data['file_draft'] !== $file_draft_baru) {
            $oldFilePath = '../assets/upload/draft_word/' . basename($data['file_draft']);
            if (is_file($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        echo "<script>alert('Data pengajuan berhasil diperbarui!');window.location='main_pokja.php?unit=pengajuan';</script>";
    } else {
        if ($file_draft_baru !== '') {
            $newFilePath = '../assets/upload/draft_word/' . basename($file_draft_baru);
            if (is_file($newFilePath)) {
                unlink($newFilePath);
            }
        }
        echo "<script>alert('Gagal memperbarui data: " . mysqli_error($config) . "');window.location='main_pokja.php?unit=pengajuan';</script>";
    }
}
?>

<section class="content-header">
    <h1>Edit Pengajuan Dokumen</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Form Edit Pengajuan</h3>
            </div>

            <form method="post" enctype="multipart/form-data" class="js-confirm-submit" data-confirm-message="Simpan perubahan pengajuan ini?">
                <input type="hidden" name="id_pengajuan" value="<?php echo $data['id_pengajuan']; ?>">
                <input type="hidden" name="file_draft_lama" value="<?php echo $data['file_draft']; ?>">

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="required-label">Standard EP</label>
                                <?php
                                $current_value = '';
                                if (!empty($data['elemen_penilaian'])) {
                                    $parts = explode('-', $data['elemen_penilaian'], 2);
                                    if (count($parts) > 1) {
                                        $current_value = $parts[1];
                                    }
                                }
                                ?>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    <input type="text" class="form-control" value="<?php echo $kode_pokja; ?>" readonly style="background-color: #f8f9fa; font-weight: bold;">
                                    <span class="input-group-text">-</span>
                                    <input type="text" name="elemen_penilaian" class="form-control" placeholder="Contoh: 1 EP 3" value="<?php echo htmlspecialchars($current_value); ?>" required>
                                </div>
                                <small class="form-text text-muted"><b>Note:</b> pokja "<?php echo $kode_pokja; ?>" sudah otomatis.</small>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="required-label" for="bentukDokumen">Bentuk Dokumen</label>
                                <select name="bentuk_dokumen" id="bentukDokumen" class="form-control select2" required>
                                    <option value="">-- Pilih Bentuk Dokumen --</option>
                                    <?php foreach (app_bentuk_dokumen_options() as $bentuk): ?>
                                        <option value="<?= htmlspecialchars($bentuk); ?>" <?= $bentuk === ($data['bentuk_dokumen'] ?? '') ? 'selected' : ''; ?>>
                                            <?= htmlspecialchars($bentuk); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="form-group">
                                <label class="required-label">Jenis Dokumen</label>
                                <select name="id_jenis" id="jenisDokumen" class="form-control" required>
                                    <option value="">-- Pilih Jenis Dokumen --</option>
                                    <?php
                                    $qJenis = mysqli_query($config, "SELECT id_jenis, nama_jenis, kode_jenis FROM tb_jenis_dokumen ORDER BY nama_jenis ASC");
                                    while ($j = mysqli_fetch_assoc($qJenis)) {
                                        $selected = ($j['id_jenis'] == $data['id_jenis']) ? 'selected' : '';
                                        echo '<option value="' . (int) $j['id_jenis'] . '" data-kode-jenis="' . htmlspecialchars(strtoupper(trim($j['kode_jenis']))) . '" ' . $selected . '>' . htmlspecialchars($j['nama_jenis']) . ' (' . htmlspecialchars($j['kode_jenis']) . ')</option>';
                                    }
                                    ?>
                                </select>
                                <small class="form-text text-muted" id="jenisDokumenHelp"></small>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label class="required-label">Judul Dokumen</label>
                                <input type="text" name="judul_dokumen" class="form-control" value="<?php echo htmlspecialchars($data['judul_dokumen']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="required-label">Tanggal Dokumen</label>
                                <input type="date" name="tanggal_dokumen" class="form-control" value="<?php echo $data['tanggal_dokumen']; ?>" required>
                            </div>

                            <?php if ($hasNoTlpColumn): ?>
                            <div class="form-group">
                                <label class="required-label">Nomor Telepon WhatsApp</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" name="no_telepon" class="form-control js-phone-id" placeholder="Contoh: 08123456789" pattern="(\+?62|0)8[0-9]{8,13}" value="<?php echo htmlspecialchars($data['no_tlp'] ?? ''); ?>" required>
                                </div>
                                <small class="form-text text-muted">Masukkan nomor telepon yang dapat dihubungi untuk konfirmasi.</small>
                            </div>
                            <?php endif; ?>

                            <?php
                            $currentUploadRules = app_pengajuan_draft_upload_rules($data['bentuk_dokumen'] ?? '');
                            $currentFileExtension = strtolower(pathinfo((string) ($data['file_draft'] ?? ''), PATHINFO_EXTENSION));
                            $currentFileIsPdf = $currentFileExtension === 'pdf';
                            ?>
                            <div class="form-group">
                                <label id="draftFileLabel"><?= htmlspecialchars($currentUploadRules['label']); ?> <small>(Kosongkan jika tidak ingin diubah)</small></label><br>
                                <?php if (!empty($data['file_draft'])): ?>
                                    <a href="../assets/upload/draft_word/<?= rawurlencode(basename($data['file_draft'])); ?>" target="_blank" class="btn btn-sm <?= $currentFileIsPdf ? 'btn-danger' : 'btn-info'; ?> mb-2">
                                        <i class="fas <?= $currentFileIsPdf ? 'fa-file-pdf' : 'fa-file-word'; ?>"></i> Lihat File <?= $currentFileIsPdf ? 'PDF' : 'Word'; ?> Lama
                                    </a><br>
                                <?php endif; ?>
                                <input type="file" name="file_draft" id="draftFileInput" class="form-control-file js-file-preview" data-preview="#draftPreview" data-current-extension="<?= htmlspecialchars($currentFileExtension); ?>" accept="<?= htmlspecialchars($currentUploadRules['accept']); ?>">
                                <small class="form-text text-muted" id="draftFileHelp"><?= htmlspecialchars($currentUploadRules['help']); ?></small>
                                <div id="draftPreview" class="upload-preview"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <a class="btn btn-app bg-warning float-left" href="main_pokja.php?unit=pengajuan">
                        <i class="fas fa-reply"></i> Back
                    </a>
                    <button class="btn btn-app bg-success float-right" type="submit">
                        <i class="fas fa-save"></i> UPDATE
                    </button>
                </div>
            </form>
        </div>
    </div>
    <br><br>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var bentukSelect = document.getElementById('bentukDokumen');
    var jenisSelect = document.getElementById('jenisDokumen');
    var fileInput = document.getElementById('draftFileInput');
    var fileLabel = document.getElementById('draftFileLabel');
    var fileHelp = document.getElementById('draftFileHelp');
    var jenisHelp = document.getElementById('jenisDokumenHelp');

    function refreshJenisSelect() {
        if (window.jQuery && window.jQuery(jenisSelect).hasClass('select2-hidden-accessible')) {
            window.jQuery(jenisSelect).trigger('change.select2');
        }
    }

    function sinkronkanBentukDokumen() {
        if (!bentukSelect || !jenisSelect || !fileInput || !fileLabel || !fileHelp || !jenisHelp) return;

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
            fileLabel.innerHTML = 'File Dokumen <small>(pilih Bentuk Dokumen terlebih dahulu)</small>';
            fileHelp.textContent = 'Pilih Bentuk Dokumen terlebih dahulu.';
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
            fileLabel.innerHTML = 'File Draft Regulasi (Word, maksimal 10MB) <small>(Kosongkan jika tidak ingin diubah)</small>';
            fileHelp.textContent = currentFileValid
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
            fileLabel.innerHTML = 'File Dokumen (PDF, maksimal 10MB) <small>(Kosongkan jika tidak ingin diubah)</small>';
            fileHelp.textContent = currentFileValid
                ? 'File lama sudah berformat PDF. Pilih file hanya jika ingin menggantinya.'
                : 'File lama tidak sesuai. Wajib unggah file PDF.';
            jenisHelp.textContent = 'Jenis Dokumen otomatis dikunci sebagai Dokumen Bukti (DB).';
        }

        fileInput.required = !currentFileValid;
        refreshJenisSelect();
    }

    bentukSelect.addEventListener('change', sinkronkanBentukDokumen);
    if (window.jQuery) {
        window.jQuery(bentukSelect).on('change select2:select select2:clear', sinkronkanBentukDokumen);
    }
    sinkronkanBentukDokumen();

    document.querySelectorAll('.js-file-preview').forEach(function(input) {
        input.addEventListener('change', function() {
            var target = document.querySelector(input.dataset.preview);
            if (!target || !input.files.length) return;
            var file = input.files[0];
            target.style.display = 'block';
            target.textContent = file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)';
        });
    });

    document.querySelectorAll('.js-phone-id').forEach(function(input) {
        input.addEventListener('input', function() {
            input.value = input.value.replace(/[^\d+]/g, '');
        });
    });

    document.querySelectorAll('.js-confirm-submit').forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!confirm(form.dataset.confirmMessage || 'Simpan perubahan ini?')) {
                event.preventDefault();
            }
        });
    });
});
</script>
