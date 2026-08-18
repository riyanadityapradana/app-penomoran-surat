<?php
require_once("../config/koneksi.php");
require_once("../config/notifikasi.php");
require_once("../config/upload_helper.php");

$user_id = $_SESSION['id_user'];
$query_user = mysqli_query($config, "SELECT kode_pokja FROM tb_user WHERE id_user = '$user_id'");
$user_data = mysqli_fetch_assoc($query_user);
$kode_pokja = $user_data['kode_pokja'] ?? '';

$checkNoTlpColumn = mysqli_query($config, "SHOW COLUMNS FROM tb_pengajuan_dokumen LIKE 'no_tlp'");
$hasNoTlpColumn = $checkNoTlpColumn && mysqli_num_rows($checkNoTlpColumn) > 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user = (int) $_SESSION['id_user'];
    $id_jenis = isset($_POST['id_jenis']) ? (int) $_POST['id_jenis'] : 0;
    $judul_dokumen = mysqli_real_escape_string($config, $_POST['judul_dokumen']);
    $tanggal_dok = mysqli_real_escape_string($config, $_POST['tanggal_dokumen']);
    $tanggal_ajuan = date('Y-m-d H:i:s');
    $catatan = mysqli_real_escape_string($config, $_POST['catatan']);
    $no_tel = mysqli_real_escape_string($config, $_POST['no_telepon'] ?? '');
    $elemen_penilaian_input = mysqli_real_escape_string($config, $_POST['elemen_penilaian']);
    $elemen_penilaian = $kode_pokja . '-' . $elemen_penilaian_input;
    $status = 'Menunggu Verifikasi';

    $stmtJenis = mysqli_prepare($config, 'SELECT nama_jenis, kode_jenis FROM tb_jenis_dokumen WHERE id_jenis = ? LIMIT 1');
    mysqli_stmt_bind_param($stmtJenis, 'i', $id_jenis);
    mysqli_stmt_execute($stmtJenis);
    $jenis_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtJenis));
    mysqli_stmt_close($stmtJenis);

    if (!$jenis_data || trim($jenis_data['kode_jenis']) === '') {
        echo "<script>alert('Jenis dokumen tidak valid. Silakan pilih kembali dari daftar.');window.location='main_pokja.php?unit=create_pengajuan';</script>";
        exit;
    }

    if ($hasNoTlpColumn && !app_validate_phone_id($no_tel)) {
        echo "<script>alert('Nomor telepon tidak valid. Gunakan format 08xxxxxxxxxx atau 62xxxxxxxxxx.');window.location='main_pokja.php?unit=create_pengajuan';</script>";
        exit;
    }

    $upload_dir = '../assets/upload/draft_word/';
    $upload = app_store_uploaded_file(
        $_FILES['file_draft'] ?? null,
        $upload_dir,
        'draft_' . $id_user,
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
        echo "<script>alert('" . addslashes($upload['message']) . "');window.location='main_pokja.php?unit=create_pengajuan';</script>";
        exit;
    }

    $new_filename = $upload['filename'];
    $full_path = $upload['path'];

    if (!empty($new_filename)) {
        $columns = ['id_user', 'id_jenis', 'judul_dokumen', 'file_draft', 'tanggal_dokumen', 'tanggal_ajuan', 'catatan', 'elemen_penilaian', 'status'];
        $values = [
            "'$id_user'",
            "'$id_jenis'",
            "'$judul_dokumen'",
            "'$new_filename'",
            "'$tanggal_dok'",
            "'$tanggal_ajuan'",
            "'$catatan'",
            "'$elemen_penilaian'",
            "'$status'"
        ];

        if ($hasNoTlpColumn) {
            $columns[] = 'no_tlp';
            $values[] = "'$no_tel'";
        }

        $query = "INSERT INTO tb_pengajuan_dokumen (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ")";

        if (mysqli_query($config, $query)) {
            $nama_jenis = $jenis_data['nama_jenis'] ?? '-';
            $nama_pengaju = $_SESSION['nama_lengkap'] ?? $kode_pokja;
            $admin_link = app_base_url() . '/admin/main_admin.php?unit=pengajuan';

            $emailSubject = 'Pengajuan Dokumen Baru dari Pokja ' . $kode_pokja;
            $emailBody = "
                <p>Halo Admin,</p>
                <p>Ada <b>pengajuan dokumen baru</b> yang perlu diverifikasi.</p>
                <table border='0' cellpadding='6' cellspacing='0' style='border-collapse:collapse;'>
                    <tr><td><b>Pokja</b></td><td>: " . htmlspecialchars($kode_pokja) . "</td></tr>
                    <tr><td><b>Nama Pengaju</b></td><td>: " . htmlspecialchars($nama_pengaju) . "</td></tr>
                    <tr><td><b>Judul Dokumen</b></td><td>: " . htmlspecialchars($judul_dokumen) . "</td></tr>
                    <tr><td><b>Jenis Dokumen</b></td><td>: " . htmlspecialchars($nama_jenis) . "</td></tr>
                    <tr><td><b>Tanggal Dokumen</b></td><td>: " . htmlspecialchars(date('d-m-Y', strtotime($tanggal_dok))) . "</td></tr>
                    <tr><td><b>Tanggal Pengajuan</b></td><td>: " . htmlspecialchars(app_format_datetime_id($tanggal_ajuan)) . "</td></tr>
                </table>
                <p>Silakan buka aplikasi untuk melakukan verifikasi.</p>
                <p><a href='" . htmlspecialchars($admin_link) . "'>Buka daftar pengajuan</a></p>
            ";
            app_send_email_to_admins($config, $emailSubject, $emailBody);

            $telegramMessage = "<b>Pengajuan Dokumen Baru</b>\n"
                . "Pokja: <b>" . htmlspecialchars($kode_pokja) . "</b>\n"
                . "Pengaju: <b>" . htmlspecialchars($nama_pengaju) . "</b>\n"
                . "Judul: <b>" . htmlspecialchars($judul_dokumen) . "</b>\n"
                . "Jenis: <b>" . htmlspecialchars($nama_jenis) . "</b>\n"
                . "Tanggal Dokumen: <b>" . htmlspecialchars(date('d-m-Y', strtotime($tanggal_dok))) . "</b>\n"
                . "Status: <b>Menunggu Verifikasi</b>\n"
                . "Link: " . htmlspecialchars($admin_link);
            app_send_telegram($telegramMessage);

            echo "<script>alert('Pengajuan dokumen berhasil disimpan.');window.location='main_pokja.php?unit=pengajuan';</script>";
        } else {
            if (file_exists($full_path)) {
                unlink($full_path);
            }
            echo "<script>alert('Gagal menyimpan data ke database: " . mysqli_error($config) . "');window.location='main_pokja.php?unit=create_pengajuan';</script>";
        }
    } else {
        echo "<script>alert('Gagal mengupload file draft.');window.location='main_pokja.php?unit=create_pengajuan';</script>";
    }
}
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Form Pengajuan Nomor Dokumen</h1>
            </div>
        </div>
    </div>
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
            if (!confirm(form.dataset.confirmMessage || 'Simpan data ini?')) {
                event.preventDefault();
            }
        });
    });
});
</script>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Silakan Input Data Pengajuan Dokumen</h3>
            </div>
            <form method="post" enctype="multipart/form-data" class="js-confirm-submit" data-confirm-message="Kirim pengajuan dokumen ini ke admin? Pastikan data dan file draft sudah benar.">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="required-label">Standard EP</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-tags"></i></span>
                                    <input type="text" class="form-control" value="<?php echo $kode_pokja; ?>" readonly style="background-color: #e9ecef; font-weight: bold;">
                                    <span class="input-group-text">-</span>
                                    <input type="text" name="elemen_penilaian" class="form-control" placeholder="Contoh: 1 EP 3" required>
                                </div>
                                <small class="form-text text-muted"><b>Note:</b> pokja "<?php echo $kode_pokja; ?>" sudah otomatis. Jadi sisanya masukkan nomor dan EP berapa.</small>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="form-group">
                                <label class="required-label">Jenis Dokumen</label>
                                <input type="hidden" name="id_user" class="form-control" value="<?php echo $_SESSION['id_user']; ?>" readonly>
                                <select name="id_jenis" class="form-control select2" required>
                                    <option value="">-- Pilih Jenis Dokumen --</option>
                                    <?php
                                    $qjenis = mysqli_query($config, "SELECT id_jenis, nama_jenis, kode_jenis FROM tb_jenis_dokumen ORDER BY nama_jenis ASC");
                                    while ($r = mysqli_fetch_assoc($qjenis)) {
                                        echo '<option value="' . (int) $r['id_jenis'] . '">' . htmlspecialchars($r['nama_jenis']) . ' (' . htmlspecialchars($r['kode_jenis']) . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required-label">Judul Dokumen</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-file-signature"></i></span>
                            <input type="text" name="judul_dokumen" class="form-control" placeholder="Contoh: Rencana Kerja Tahunan - Revisi 2025" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="required-label">Tanggal Dokumen</label>
                        <input type="date" name="tanggal_dokumen" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="required-label">File Draft (Word, maksimal 10MB)</label>
                        <input type="file" name="file_draft" accept=".doc,.docx" class="form-control js-file-preview" data-preview="#draftPreview" required>
                        <div id="draftPreview" class="upload-preview"></div>
                    </div>

                    <?php if ($hasNoTlpColumn): ?>
                    <div class="form-group">
                        <label class="required-label">Nomor Telepon WhatsApp</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                            <input type="text" name="no_telepon" class="form-control js-phone-id" placeholder="Contoh: 08123456789" pattern="(\+?62|0)8[0-9]{8,13}" required>
                        </div>
                        <small class="form-text text-muted">Masukkan nomor telepon yang dapat dihubungi untuk konfirmasi.</small>
                    </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Catatan</label>
                        <div class="input-group">
                            <textarea name="catatan" class="form-control" rows="3" placeholder="Opsional: tulis ringkasan, tujuan, atau catatan penting untuk tim verifikasi"></textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <a class="btn btn-app bg-warning float-left" href="main_pokja.php?unit=pengajuan">
                        <i class="fas fa-reply"></i> Back
                    </a>
                    <button class="btn btn-app bg-success float-right" type="submit">
                        <i class="fas fa-save"></i> SAVE
                    </button>
                </div>
            </form>
        </div>
    </div>
    <br><br>
</section>
