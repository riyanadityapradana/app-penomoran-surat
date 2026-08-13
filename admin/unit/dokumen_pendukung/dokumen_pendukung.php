<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (!isset($_SESSION['id_user']) || ($_SESSION['level'] ?? '') !== 'Admin') {
    http_response_code(403);
    exit('Akses ditolak. Silakan login sebagai Admin.');
}

require_once(__DIR__ . '/../../../config/koneksi.php');
require_once(__DIR__ . '/../../../config/upload_helper.php');

$dokumenUploadDir = __DIR__ . '/../../../assets/upload/dokumen_pendukung';
$dokumenRedirectBase = 'main_admin.php?unit=dokumen_pendukung';

if (empty($_SESSION['dokumen_pendukung_csrf'])) {
    $_SESSION['dokumen_pendukung_csrf'] = bin2hex(random_bytes(32));
}

function dokumen_pendukung_redirect($type, $message)
{
    global $dokumenRedirectBase;
    $url = $dokumenRedirectBase . '&' . $type . '=' . urlencode($message);

    if (!headers_sent()) {
        header('Location: ' . $url);
    } else {
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    }
    exit;
}

function dokumen_pendukung_hapus_file($uploadDir, $filename)
{
    if (empty($filename)) {
        return true;
    }

    $baseDir = realpath($uploadDir);
    if ($baseDir === false) {
        return false;
    }

    $safeName = basename($filename);
    $filePath = $baseDir . DIRECTORY_SEPARATOR . $safeName;
    return !is_file($filePath) || unlink($filePath);
}

function dokumen_pendukung_ada_upload($field)
{
    return isset($_FILES[$field])
        && is_array($_FILES[$field])
        && ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

function dokumen_pendukung_upload($field, $uploadDir, $prefix, $type)
{
    if ($type === 'word') {
        return app_store_uploaded_file(
            $_FILES[$field],
            $uploadDir,
            $prefix . '_word',
            ['doc', 'docx'],
            [
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip',
                'application/octet-stream'
            ],
            15 * 1024 * 1024
        );
    }

    return app_store_uploaded_file(
        $_FILES[$field],
        $uploadDir,
        $prefix . '_pdf',
        ['pdf'],
        ['application/pdf', 'application/octet-stream'],
        15 * 1024 * 1024
    );
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['dokumen_pendukung_csrf'], $csrf)) {
        dokumen_pendukung_redirect('err', 'Permintaan tidak valid. Silakan muat ulang halaman.');
    }

    if (isset($_POST['hapus_dokumen'])) {
        $idDokumen = isset($_POST['id_dokumen']) ? (int) $_POST['id_dokumen'] : 0;
        $stmt = mysqli_prepare($config, 'SELECT file_word, file_pdf FROM tb_dokumen_pendukung WHERE id_dokumen = ?');
        mysqli_stmt_bind_param($stmt, 'i', $idDokumen);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $dokumen = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$dokumen) {
            dokumen_pendukung_redirect('err', 'Dokumen pendukung tidak ditemukan.');
        }

        $stmt = mysqli_prepare($config, 'DELETE FROM tb_dokumen_pendukung WHERE id_dokumen = ?');
        mysqli_stmt_bind_param($stmt, 'i', $idDokumen);
        $deleted = mysqli_stmt_execute($stmt);
        $deleteError = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        if (!$deleted) {
            dokumen_pendukung_redirect('err', 'Gagal menghapus dokumen: ' . $deleteError);
        }

        $wordDeleted = dokumen_pendukung_hapus_file($dokumenUploadDir, $dokumen['file_word']);
        $pdfDeleted = dokumen_pendukung_hapus_file($dokumenUploadDir, $dokumen['file_pdf']);
        $message = ($wordDeleted && $pdfDeleted)
            ? 'Dokumen pendukung berhasil dihapus.'
            : 'Data berhasil dihapus, tetapi ada file fisik yang tidak dapat dihapus.';
        dokumen_pendukung_redirect('msg', $message);
    }

    if (isset($_POST['simpan_dokumen'])) {
        $idDokumen = isset($_POST['id_dokumen']) ? (int) $_POST['id_dokumen'] : 0;
        $namaDokumen = isset($_POST['nama_dokumen']) ? trim($_POST['nama_dokumen']) : '';
        $deskripsi = isset($_POST['deskripsi']) ? trim($_POST['deskripsi']) : '';
        $status = isset($_POST['status']) && $_POST['status'] === 'Nonaktif' ? 'Nonaktif' : 'Aktif';
        $urutan = max(0, isset($_POST['urutan']) ? (int) $_POST['urutan'] : 0);
        $uploadedBy = (int) $_SESSION['id_user'];

        if ($namaDokumen === '') {
            dokumen_pendukung_redirect('err', 'Nama dokumen tidak boleh kosong.');
        }

        $namaLength = function_exists('mb_strlen') ? mb_strlen($namaDokumen, 'UTF-8') : strlen($namaDokumen);
        if ($namaLength > 150) {
            dokumen_pendukung_redirect('err', 'Nama dokumen maksimal 150 karakter.');
        }

        $existing = null;
        if ($idDokumen > 0) {
            $stmt = mysqli_prepare($config, 'SELECT * FROM tb_dokumen_pendukung WHERE id_dokumen = ?');
            mysqli_stmt_bind_param($stmt, 'i', $idDokumen);
            mysqli_stmt_execute($stmt);
            $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$existing) {
                dokumen_pendukung_redirect('err', 'Dokumen yang akan diubah tidak ditemukan.');
            }
        }

        $fileWord = $existing['file_word'] ?? null;
        $filePdf = $existing['file_pdf'] ?? null;
        $newWord = null;
        $newPdf = null;
        $prefix = 'dokumen_pendukung_' . ($idDokumen > 0 ? $idDokumen : $uploadedBy);

        if (dokumen_pendukung_ada_upload('file_word')) {
            $upload = dokumen_pendukung_upload('file_word', $dokumenUploadDir, $prefix, 'word');
            if (!$upload['ok']) {
                dokumen_pendukung_redirect('err', 'File Word: ' . $upload['message']);
            }
            $newWord = $upload['filename'];
            $fileWord = $newWord;
        } elseif (isset($_POST['hapus_file_word'])) {
            $fileWord = null;
        }

        if (dokumen_pendukung_ada_upload('file_pdf')) {
            $upload = dokumen_pendukung_upload('file_pdf', $dokumenUploadDir, $prefix, 'pdf');
            if (!$upload['ok']) {
                if ($newWord) {
                    dokumen_pendukung_hapus_file($dokumenUploadDir, $newWord);
                }
                dokumen_pendukung_redirect('err', 'File PDF: ' . $upload['message']);
            }
            $newPdf = $upload['filename'];
            $filePdf = $newPdf;
        } elseif (isset($_POST['hapus_file_pdf'])) {
            $filePdf = null;
        }

        if (empty($fileWord) && empty($filePdf)) {
            if ($newWord) {
                dokumen_pendukung_hapus_file($dokumenUploadDir, $newWord);
            }
            if ($newPdf) {
                dokumen_pendukung_hapus_file($dokumenUploadDir, $newPdf);
            }
            dokumen_pendukung_redirect('err', 'Unggah minimal satu file Word atau PDF.');
        }

        if ($idDokumen > 0) {
            $stmt = mysqli_prepare($config, '
                UPDATE tb_dokumen_pendukung
                SET nama_dokumen = ?, deskripsi = ?, file_word = ?, file_pdf = ?, status = ?, urutan = ?, uploaded_by = ?
                WHERE id_dokumen = ?
            ');
            mysqli_stmt_bind_param($stmt, 'sssssiii', $namaDokumen, $deskripsi, $fileWord, $filePdf, $status, $urutan, $uploadedBy, $idDokumen);
        } else {
            $stmt = mysqli_prepare($config, '
                INSERT INTO tb_dokumen_pendukung
                    (nama_dokumen, deskripsi, file_word, file_pdf, status, urutan, uploaded_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            mysqli_stmt_bind_param($stmt, 'sssssii', $namaDokumen, $deskripsi, $fileWord, $filePdf, $status, $urutan, $uploadedBy);
        }

        $saved = mysqli_stmt_execute($stmt);
        $saveError = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        if (!$saved) {
            if ($newWord) {
                dokumen_pendukung_hapus_file($dokumenUploadDir, $newWord);
            }
            if ($newPdf) {
                dokumen_pendukung_hapus_file($dokumenUploadDir, $newPdf);
            }
            dokumen_pendukung_redirect('err', 'Gagal menyimpan dokumen: ' . $saveError);
        }

        if ($existing) {
            if (($newWord || isset($_POST['hapus_file_word'])) && !empty($existing['file_word']) && $existing['file_word'] !== $fileWord) {
                dokumen_pendukung_hapus_file($dokumenUploadDir, $existing['file_word']);
            }
            if (($newPdf || isset($_POST['hapus_file_pdf'])) && !empty($existing['file_pdf']) && $existing['file_pdf'] !== $filePdf) {
                dokumen_pendukung_hapus_file($dokumenUploadDir, $existing['file_pdf']);
            }
        }

        dokumen_pendukung_redirect('msg', $idDokumen > 0
            ? 'Dokumen pendukung berhasil diperbarui.'
            : 'Dokumen pendukung berhasil ditambahkan.');
    }
}

$qDokumen = mysqli_query($config, '
    SELECT d.*, u.nama_lengkap AS nama_uploader
    FROM tb_dokumen_pendukung d
    LEFT JOIN tb_user u ON d.uploaded_by = u.id_user
    ORDER BY d.urutan ASC, d.nama_dokumen ASC
');
$dokumenRows = [];
while ($qDokumen && $row = mysqli_fetch_assoc($qDokumen)) {
    $dokumenRows[] = $row;
}
?>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1>Dokumen Pendukung</h1>
            </div>
            <div class="col-sm-4 text-right">
                <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalTambahDokumen">
                    <i class="fas fa-plus"></i> Tambah Dokumen
                </button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-folder-open mr-2"></i>Master Unduhan Pokja</h3>
            </div>
            <div class="card-body">
                <table id="example2" class="table table-bordered table-striped app-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Dokumen</th>
                            <th>Deskripsi</th>
                            <th>Word</th>
                            <th>PDF</th>
                            <th>Status</th>
                            <th>Urutan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dokumenRows as $index => $dokumen): ?>
                            <tr>
                                <td class="text-center"><?= $index + 1; ?></td>
                                <td class="text-long">
                                    <strong><?= htmlspecialchars($dokumen['nama_dokumen']); ?></strong>
                                    <?php if (!empty($dokumen['nama_uploader'])): ?>
                                        <br><small class="text-muted">Dikelola oleh <?= htmlspecialchars($dokumen['nama_uploader']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-long"><?= $dokumen['deskripsi'] !== '' ? htmlspecialchars($dokumen['deskripsi']) : '-'; ?></td>
                                <td class="text-center">
                                    <?= !empty($dokumen['file_word']) ? '<span class="badge badge-primary"><i class="fas fa-file-word"></i> Tersedia</span>' : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <?= !empty($dokumen['file_pdf']) ? '<span class="badge badge-danger"><i class="fas fa-file-pdf"></i> Tersedia</span>' : '-'; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $dokumen['status'] === 'Aktif' ? 'badge-success' : 'badge-secondary'; ?>"><?= htmlspecialchars($dokumen['status']); ?></span>
                                </td>
                                <td class="text-center"><?= (int) $dokumen['urutan']; ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-warning" data-toggle="modal" data-target="#modalEditDokumen<?= (int) $dokumen['id_dokumen']; ?>" title="Edit dokumen">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus dokumen dan seluruh file terkait secara permanen?');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['dokumen_pendukung_csrf']); ?>">
                                        <input type="hidden" name="id_dokumen" value="<?= (int) $dokumen['id_dokumen']; ?>">
                                        <button type="submit" name="hapus_dokumen" class="btn btn-sm btn-danger" title="Hapus dokumen">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="modalTambahDokumen" tabindex="-1" role="dialog" aria-labelledby="modalTambahDokumenLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="modalTambahDokumenLabel"><i class="fas fa-plus-circle mr-2"></i>Tambah Dokumen Pendukung</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['dokumen_pendukung_csrf']); ?>">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="required-label" for="nama_dokumen_baru">Nama Dokumen</label>
                            <input type="text" name="nama_dokumen" id="nama_dokumen_baru" class="form-control" maxlength="150" placeholder="Contoh: Cover Dokumen" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label for="urutan_baru">Urutan</label>
                            <input type="number" name="urutan" id="urutan_baru" class="form-control" min="0" value="0">
                        </div>
                        <div class="form-group col-md-2">
                            <label for="status_baru">Status</label>
                            <select name="status" id="status_baru" class="form-control">
                                <option value="Aktif">Aktif</option>
                                <option value="Nonaktif">Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="deskripsi_baru">Deskripsi</label>
                        <textarea name="deskripsi" id="deskripsi_baru" class="form-control" rows="3" placeholder="Keterangan singkat dokumen"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="file_word_baru"><i class="fas fa-file-word text-primary mr-1"></i>File Word</label>
                            <input type="file" name="file_word" id="file_word_baru" class="form-control-file" accept=".doc,.docx">
                            <small class="form-text text-muted">DOC/DOCX, maksimal 15MB.</small>
                        </div>
                        <div class="form-group col-md-6">
                            <label for="file_pdf_baru"><i class="fas fa-file-pdf text-danger mr-1"></i>File PDF</label>
                            <input type="file" name="file_pdf" id="file_pdf_baru" class="form-control-file" accept="application/pdf,.pdf">
                            <small class="form-text text-muted">PDF, maksimal 15MB. Minimal satu file wajib diunggah.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_dokumen" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php foreach ($dokumenRows as $dokumen): ?>
<div class="modal fade" id="modalEditDokumen<?= (int) $dokumen['id_dokumen']; ?>" tabindex="-1" role="dialog" aria-labelledby="modalEditDokumenLabel<?= (int) $dokumen['id_dokumen']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title" id="modalEditDokumenLabel<?= (int) $dokumen['id_dokumen']; ?>"><i class="fas fa-edit mr-2"></i>Edit Dokumen Pendukung</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['dokumen_pendukung_csrf']); ?>">
                    <input type="hidden" name="id_dokumen" value="<?= (int) $dokumen['id_dokumen']; ?>">
                    <div class="form-row">
                        <div class="form-group col-md-8">
                            <label class="required-label" for="nama_dokumen_<?= (int) $dokumen['id_dokumen']; ?>">Nama Dokumen</label>
                            <input type="text" name="nama_dokumen" id="nama_dokumen_<?= (int) $dokumen['id_dokumen']; ?>" class="form-control" maxlength="150" value="<?= htmlspecialchars($dokumen['nama_dokumen']); ?>" required>
                        </div>
                        <div class="form-group col-md-2">
                            <label>Urutan</label>
                            <input type="number" name="urutan" class="form-control" min="0" value="<?= (int) $dokumen['urutan']; ?>">
                        </div>
                        <div class="form-group col-md-2">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="Aktif" <?= $dokumen['status'] === 'Aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="Nonaktif" <?= $dokumen['status'] === 'Nonaktif' ? 'selected' : ''; ?>>Nonaktif</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control" rows="3"><?= htmlspecialchars($dokumen['deskripsi']); ?></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label><i class="fas fa-file-word text-primary mr-1"></i>Ganti File Word</label>
                            <input type="file" name="file_word" class="form-control-file" accept=".doc,.docx">
                            <?php if (!empty($dokumen['file_word'])): ?>
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" name="hapus_file_word" value="1" class="custom-control-input" id="hapus_word_<?= (int) $dokumen['id_dokumen']; ?>">
                                    <label class="custom-control-label" for="hapus_word_<?= (int) $dokumen['id_dokumen']; ?>">Hapus file Word saat ini</label>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group col-md-6">
                            <label><i class="fas fa-file-pdf text-danger mr-1"></i>Ganti File PDF</label>
                            <input type="file" name="file_pdf" class="form-control-file" accept="application/pdf,.pdf">
                            <?php if (!empty($dokumen['file_pdf'])): ?>
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" name="hapus_file_pdf" value="1" class="custom-control-input" id="hapus_pdf_<?= (int) $dokumen['id_dokumen']; ?>">
                                    <label class="custom-control-label" for="hapus_pdf_<?= (int) $dokumen['id_dokumen']; ?>">Hapus file PDF saat ini</label>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <small class="text-muted">Kosongkan input file jika file yang tersedia tidak ingin diganti.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" name="simpan_dokumen" class="btn btn-warning"><i class="fas fa-save"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
