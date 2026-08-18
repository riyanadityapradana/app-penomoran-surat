<?php
require_once("../config/upload_helper.php");

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
    $query = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE id_pengajuan = '$id_pengajuan' AND id_user = '{$_SESSION['id_user']}'");
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
    $judul_dokumen = mysqli_real_escape_string($config, $_POST['judul_dokumen']);
    $tanggal_dokumen = mysqli_real_escape_string($config, $_POST['tanggal_dokumen']);
    $no_tel = mysqli_real_escape_string($config, $_POST['no_telepon'] ?? '');
    $elemen_penilaian_input = mysqli_real_escape_string($config, $_POST['elemen_penilaian']);
    $elemen_penilaian = $kode_pokja . '-' . $elemen_penilaian_input;
    $file_draft_lama = mysqli_real_escape_string($config, $_POST['file_draft_lama']);

    $file_draft = $file_draft_lama;

    $stmtJenis = mysqli_prepare($config, 'SELECT id_jenis FROM tb_jenis_dokumen WHERE id_jenis = ? AND kode_jenis <> \'\' LIMIT 1');
    mysqli_stmt_bind_param($stmtJenis, 'i', $id_jenis);
    mysqli_stmt_execute($stmtJenis);
    $jenisValid = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtJenis));
    mysqli_stmt_close($stmtJenis);

    if (!$jenisValid) {
        echo "<script>alert('Jenis dokumen tidak valid. Silakan pilih kembali dari daftar.');window.location='main_pokja.php?unit=update_pengajuan&id_pengajuan={$id_pengajuan}';</script>";
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
            ['doc', 'docx'],
            [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/octet-stream'
            ],
            10 * 1024 * 1024
        );

        if (!$upload['ok']) {
            echo "<script>alert('" . addslashes($upload['message']) . "');window.location='main_pokja.php?unit=update_pengajuan&id_pengajuan={$id_pengajuan}';</script>";
            exit;
        } else {
            if (!empty($data['file_draft']) && file_exists('../assets/upload/draft_word/' . $data['file_draft'])) {
                unlink('../assets/upload/draft_word/' . $data['file_draft']);
            }

            $file_draft = $upload['filename'];
        }
    }

    $setParts = [
        "id_jenis = '$id_jenis'",
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
        echo "<script>alert('Data pengajuan berhasil diperbarui!');window.location='main_pokja.php?unit=pengajuan';</script>";
    } else {
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
                        <div class="col-sm-6">
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
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="required-label">Jenis Dokumen</label>
                                <select name="id_jenis" class="form-control" required>
                                    <option value="">-- Pilih Jenis Dokumen --</option>
                                    <?php
                                    $qJenis = mysqli_query($config, "SELECT id_jenis, nama_jenis, kode_jenis FROM tb_jenis_dokumen ORDER BY nama_jenis ASC");
                                    while ($j = mysqli_fetch_assoc($qJenis)) {
                                        $selected = ($j['id_jenis'] == $data['id_jenis']) ? 'selected' : '';
                                        echo '<option value="' . (int) $j['id_jenis'] . '" ' . $selected . '>' . htmlspecialchars($j['nama_jenis']) . ' (' . htmlspecialchars($j['kode_jenis']) . ')</option>';
                                    }
                                    ?>
                                </select>
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

                            <div class="form-group">
                                <label>File Draft (Word) <small>(Kosongkan jika tidak ingin diubah, maks 10MB)</small></label><br>
                                <?php if (!empty($data['file_draft'])): ?>
                                    <a href="../assets/upload/draft_word/<?php echo $data['file_draft']; ?>" target="_blank" class="btn btn-sm btn-info mb-2">
                                        <i class="fas fa-file-word"></i> Lihat File Lama
                                    </a><br>
                                <?php endif; ?>
                                <input type="file" name="file_draft" class="form-control-file js-file-preview" data-preview="#draftPreview" accept=".doc,.docx">
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
