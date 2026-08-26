<?php
require_once("../config/koneksi.php");
require_once("../config/notifikasi.php");
require_once("../config/upload_helper.php");
require_once("../config/wa_helper.php");

$q_tanggal_pengesahan = mysqli_query($config, "SHOW COLUMNS FROM tb_pengajuan_dokumen LIKE 'tanggal_pengesahan'");
$has_tanggal_pengesahan = $q_tanggal_pengesahan && mysqli_num_rows($q_tanggal_pengesahan) > 0;

if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
    $query = mysqli_query($config, "
        SELECT p.*, j.nama_jenis, j.kode_jenis, u.nama_lengkap, u.kode_pokja, u.email_user
        FROM tb_pengajuan_dokumen p
        LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
        LEFT JOIN tb_user u ON p.id_user = u.id_user
        WHERE p.id_pengajuan = '$id_pengajuan'
    ");
    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        header('Location: main_admin.php?unit=pengajuan&err=Data pengajuan tidak ditemukan!');
        exit;
    }
} else {
    header('Location: main_admin.php?unit=pengajuan&err=ID Pengajuan tidak ditemukan!');
    exit;
}

$is_dokumen_bukti = strtoupper(trim((string) ($data['kode_jenis'] ?? ''))) === 'DB';

function app_final_filename_base($data, $id_pengajuan)
{
    $parts = [
        $data['elemen_penilaian'] ?? '',
        $data['judul_dokumen'] ?? '',
        $data['nomor_surat'] ?? ''
    ];
    $base = preg_replace('/[^a-zA-Z0-9]+/', '_', implode('_', $parts));
    $base = trim($base, '_');

    return $base !== '' ? $base : 'dokumen_final_' . $id_pengajuan;
}

function app_store_final_named_file($file, $uploadDir, $filenameBase, $allowedExtensions, $allowedMimes, $maxBytes)
{
    $validation = app_validate_upload($file, $allowedExtensions, $allowedMimes, $maxBytes);
    if (!$validation['ok']) {
        return $validation;
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
        return ['ok' => false, 'message' => 'Folder final tidak dapat dibuat. Hubungi administrator.'];
    }

    if (!is_writable($uploadDir)) {
        return ['ok' => false, 'message' => 'Folder final tidak dapat ditulis. Hubungi administrator.'];
    }

    $filename = $filenameBase . '.' . $validation['extension'];
    $destination = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

    if (file_exists($destination) && !unlink($destination)) {
        return ['ok' => false, 'message' => 'File final lama tidak dapat diganti.'];
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['ok' => false, 'message' => 'Gagal menyimpan file final.'];
    }

    return ['ok' => true, 'filename' => $filename, 'path' => $destination];
}

function app_delete_final_file($filename)
{
    if (empty($filename)) {
        return true;
    }

    $path = '../assets/upload/draft_final/' . basename($filename);
    if (file_exists($path)) {
        return unlink($path);
    }

    return true;
}

function app_delete_draft_word_file($filename)
{
    if (empty($filename)) {
        return true;
    }

    $path = '../assets/upload/draft_word/' . basename($filename);
    if (file_exists($path)) {
        return unlink($path);
    }

    return true;
}

function app_has_final_upload($file)
{
    return isset($file)
        && is_array($file)
        && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
}

if (isset($_POST['upload_final'])) {
    if (empty($data['nomor_surat'])) {
        echo "<script>alert('Nomor dokumen belum tersedia. Silakan isi nomor dokumen terlebih dahulu.');</script>";
    } elseif ($data['status'] !== 'Disetujui') {
        echo "<script>alert('Upload final hanya dapat dilakukan pada dokumen berstatus Disetujui.');</script>";
    } else {
        $finalDir = '../assets/upload/draft_final/';
        $filenameBase = app_final_filename_base($data, $id_pengajuan);
        $hasWordUpload = app_has_final_upload($_FILES['file_word_final'] ?? null);
        $hasPdfUpload = app_has_final_upload($_FILES['file_pdf_final'] ?? null);
        $finalSelection = app_validate_final_upload_selection($data['kode_jenis'] ?? '', $hasWordUpload, $hasPdfUpload);

        if (!$finalSelection['ok']) {
            echo "<script>alert('" . addslashes($finalSelection['message']) . "');</script>";
        } else {
            $uploadWord = null;
            $uploadPdf = null;
            $uploadError = '';

            if ($hasWordUpload) {
                $uploadWord = app_store_final_named_file(
                    $_FILES['file_word_final'],
                    $finalDir,
                    $filenameBase,
                    ['doc', 'docx'],
                    [
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        'application/zip',
                        'application/octet-stream'
                    ],
                    15 * 1024 * 1024
                );
                if (!$uploadWord['ok']) {
                    $uploadError = $uploadWord['message'];
                }
            }

            if ($uploadError === '' && $hasPdfUpload) {
                $uploadPdf = app_store_final_named_file(
                    $_FILES['file_pdf_final'],
                    $finalDir,
                    $filenameBase,
                    ['pdf'],
                    ['application/pdf', 'application/octet-stream'],
                    15 * 1024 * 1024
                );
                if (!$uploadPdf['ok']) {
                    $uploadError = $uploadPdf['message'];
                }
            }

            if ($uploadError !== '') {
                if ($uploadWord && !empty($uploadWord['filename'])) {
                    app_delete_final_file($uploadWord['filename']);
                }
                if ($uploadPdf && !empty($uploadPdf['filename'])) {
                    app_delete_final_file($uploadPdf['filename']);
                }
                echo "<script>alert('" . addslashes($uploadError) . "');</script>";
            } else {
                $word_name_raw = $uploadWord ? $uploadWord['filename'] : '';
                $pdf_name_raw = $uploadPdf ? $uploadPdf['filename'] : '';
                $word_name = mysqli_real_escape_string($config, $word_name_raw);
                $pdf_name = mysqli_real_escape_string($config, $pdf_name_raw);
				$set_tanggal_pengesahan = $has_tanggal_pengesahan ? ', tanggal_pengesahan=NOW()' : '';

                $updateFinal = mysqli_query($config, "
                    UPDATE tb_pengajuan_dokumen
                    SET file_draft='$word_name',
                        file_final='$pdf_name',
						status='Selesai'
						$set_tanggal_pengesahan
                    WHERE id_pengajuan='$id_pengajuan' AND status='Disetujui'
                ");

                if (!$updateFinal || mysqli_affected_rows($config) !== 1) {
                    if ($word_name_raw !== '') {
                        app_delete_final_file($word_name_raw);
                    }
                    if ($pdf_name_raw !== '') {
                        app_delete_final_file($pdf_name_raw);
                    }
                    echo "<script>alert('Gagal menyimpan data file final. Status dokumen tidak diubah.');</script>";
                } else {
                    $oldFiles = array_unique(array_filter([$data['file_draft'], $data['file_final']]));
                    foreach ($oldFiles as $oldFile) {
                        if ($oldFile !== $word_name_raw && $oldFile !== $pdf_name_raw) {
                            app_delete_final_file($oldFile);
                            app_delete_draft_word_file($oldFile);
                        }
                    }

                    $pokjaLink = app_base_url() . '/pokja/main_pokja.php?unit=pengesahan';
                    $emailSubject = 'Dokumen Final Siap Diunduh - ' . $data['judul_dokumen'];
                    $emailBody = "
                <p>Halo <b>" . htmlspecialchars($data['nama_lengkap']) . "</b>,</p>
                <p>Dokumen final Anda sudah diupload admin dan saat ini berstatus <b>Selesai</b>.</p>
                <table border='0' cellpadding='6' cellspacing='0' style='border-collapse:collapse;'>
                    <tr><td><b>Kode Pokja</b></td><td>: " . htmlspecialchars($data['kode_pokja']) . "</td></tr>
                    <tr><td><b>Judul Dokumen</b></td><td>: " . htmlspecialchars($data['judul_dokumen']) . "</td></tr>
                    <tr><td><b>Jenis Dokumen</b></td><td>: " . htmlspecialchars($data['nama_jenis']) . "</td></tr>
                    <tr><td><b>Nomor Surat</b></td><td>: " . htmlspecialchars($data['nomor_surat']) . "</td></tr>
                    <tr><td><b>Status</b></td><td>: Selesai</td></tr>
                </table>
                <p>Silakan login ke aplikasi untuk mengunduh file final.</p>
                <p><a href='" . htmlspecialchars($pokjaLink) . "'>Buka halaman pengesahan</a></p>
                    ";
                    if (!empty($data['email_user'])) {
                        app_send_email($data['email_user'], $emailSubject, $emailBody);
                    }

                    $telegramMessage = "<b>Dokumen Final Siap Diunduh</b>\n"
                . "Pokja: <b>" . htmlspecialchars($data['kode_pokja']) . "</b>\n"
                . "Judul: <b>" . htmlspecialchars($data['judul_dokumen']) . "</b>\n"
                . "Jenis: <b>" . htmlspecialchars($data['nama_jenis']) . "</b>\n"
                . "Nomor Surat: <b>" . htmlspecialchars($data['nomor_surat']) . "</b>\n"
                . "Status: <b>Selesai</b>\n"
                . "Link: " . htmlspecialchars($pokjaLink);
                    app_send_telegram($telegramMessage);

                    $data['file_draft'] = $word_name_raw;
                    $data['file_final'] = $pdf_name_raw;
                    $waUrl = '';
                    if (!empty($data['no_tlp'])) {
                        $waUrl = app_wa_url($data['no_tlp'], app_wa_selesai_message($data));
                    }
                    $uploadedFormat = $word_name_raw !== '' && $pdf_name_raw !== ''
                        ? 'Word dan PDF'
                        : ($word_name_raw !== '' ? 'Word' : 'PDF');
                    $redirectUrl = 'main_admin.php?unit=pengajuan&msg=' . urlencode('File final ' . $uploadedFormat . ' berhasil diupload dan status menjadi Selesai!');
                    echo "<script>";
                    if ($waUrl !== '') {
                        echo "var waWindow = window.open('', 'waNotifyWindow');";
                        echo "if (waWindow) { waWindow.location.href = " . json_encode($waUrl) . "; }";
                    }
                    echo "window.location.href = " . json_encode($redirectUrl) . ";";
                    echo "</script>";
                    exit;
                }
            }
        }
    }
}

if (isset($_POST['edit_nomor_dokumen'])) {
    $nomor_surat = isset($_POST['nomor_surat']) ? trim($_POST['nomor_surat']) : '';

    if ($nomor_surat === '') {
        header('Location: main_admin.php?unit=detail_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=Nomor dokumen tidak boleh kosong!');
        exit;
    }

    $nomor_surat = mysqli_real_escape_string($config, $nomor_surat);
    $update_nomor = mysqli_query($config, "
        UPDATE tb_pengajuan_dokumen
        SET nomor_surat = '$nomor_surat'
        WHERE id_pengajuan = '$id_pengajuan'
    ");

    if ($update_nomor) {
        header('Location: main_admin.php?unit=detail_pengajuan&id_pengajuan=' . $id_pengajuan . '&msg=Nomor dokumen berhasil diperbarui!');
        exit;
    }

    $errMsg = urlencode('Gagal memperbarui nomor dokumen: ' . mysqli_error($config));
    header('Location: main_admin.php?unit=detail_pengajuan&id_pengajuan=' . $id_pengajuan . '&err=' . $errMsg);
    exit;
}

function getBadgeClass($status) {
    switch ($status) {
        case 'Menunggu Verifikasi': return 'badge badge-warning';
        case 'Disetujui': return 'badge badge-success';
        case 'Ditolak': return 'badge badge-danger';
        case 'Selesai': return 'badge badge-primary';
        default: return 'badge badge-secondary';
    }
}
?>

<section class="content-header">
    <h1>Detail Pengajuan Dokumen</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">

            <div class="card-header d-flex align-items-center" style="position:relative;">
                <h3 class="card-title">Informasi Pengajuan dari Pokja</h3>
                <div class="ml-auto" style="position:absolute; right:24px; top:9px;">
                <?php if ($data['status'] === 'Selesai'): ?>
                    <?php if (!empty($data['file_final'])): ?>
                        <a href="../assets/upload/draft_final/<?= rawurlencode(basename($data['file_final'])); ?>"
                           class="btn btn-sm btn-success ml-2" download>
                            <i class="fas fa-file-pdf"></i> PDF Final
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($data['file_draft'])): ?>
                        <a href="../assets/upload/draft_final/<?= rawurlencode(basename($data['file_draft'])); ?>"
                           class="btn btn-sm btn-info ml-2" download>
                            <i class="fas fa-file-word"></i> Word Final
                        </a>
                    <?php endif; ?>
                <?php elseif (!empty($data['file_draft'])): ?>
                    <a href="../assets/upload/draft_word/<?= rawurlencode(basename($data['file_draft'])); ?>"
                       class="btn btn-sm btn-success ml-2" download>
                        <i class="fas fa-download"></i> Download Draft
                    </a>
                <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">Kode Pokja</th>
                        <td><?= htmlspecialchars($data['kode_pokja']); ?> (<?= htmlspecialchars($data['nama_lengkap']); ?>)</td>
                    </tr>
                    <tr>
                        <th>Jenis Surat</th>
                        <td><?= htmlspecialchars($data['nama_jenis']); ?></td>
                    </tr>
                    <tr>
                        <th>Standard EP</th>
                        <td><?= htmlspecialchars($data['elemen_penilaian']); ?></td>
                    </tr>
                    <tr>
                        <th>Judul Dokumen</th>
                        <td><?= htmlspecialchars($data['judul_dokumen']); ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Dokumen</th>
                        <td><?= date('d-m-Y', strtotime($data['tanggal_dokumen'])); ?></td>
                    </tr>
                    <tr>
                        <th>Tanggal Ajuan</th>
                        <td><?= date('d-m-Y', strtotime($data['tanggal_ajuan'])); ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="<?= getBadgeClass($data['status']); ?>">
                                <?= htmlspecialchars($data['status']); ?>
                            </span>
                            <?php if ($data['status'] == 'Menunggu Verifikasi'): ?>
                                <a href="main_admin.php?unit=verifikasi_pengajuan&id_pengajuan=<?= $data['id_pengajuan']; ?>"
                                    class="btn btn-sm btn-success ml-2">
                                    <i class="fas fa-check"></i> Verifikasi
                                </a>
                                <a href="main_admin.php?unit=tolak_pengajuan&id_pengajuan=<?= $data['id_pengajuan']; ?>"
                                    class="btn btn-sm btn-danger ml-2">
                                    <i class="fas fa-times"></i> Tolak
                                </a>
                            <?php elseif ($data['status'] == 'Disetujui'): ?>
                                <form method="POST" enctype="multipart/form-data" class="d-inline-block ml-2" onsubmit="return prepareFinalUpload(<?= $is_dokumen_bukti ? 'true' : 'false'; ?>);">
                                    <input type="hidden" name="id_pengajuan" value="<?= $data['id_pengajuan']; ?>">
                                    <span class="d-inline-block mr-2">
                                        <small class="d-block font-weight-bold">Word Final<?= $is_dokumen_bukti ? ' (opsional)' : ' *'; ?></small>
                                        <input type="file" name="file_word_final" id="fileWordFinal" accept=".doc,.docx" <?= $is_dokumen_bukti ? '' : 'required'; ?>>
                                    </span>
                                    <span class="d-inline-block mr-2">
                                        <small class="d-block font-weight-bold">PDF Final<?= $is_dokumen_bukti ? ' (opsional)' : ' *'; ?></small>
                                        <input type="file" name="file_pdf_final" id="filePdfFinal" accept="application/pdf,.pdf" <?= $is_dokumen_bukti ? '' : 'required'; ?>>
                                    </span>
                                    <button type="submit" name="upload_final" class="btn btn-sm btn-primary">
                                        <i class="fas fa-upload"></i> Upload Final
                                    </button>
                                </form>
                                <br>
                                <?php if ($is_dokumen_bukti): ?>
                                    <small class="form-text text-muted"><b>Dokumen Bukti: unggah minimal salah satu file final, Word atau PDF.</b></small>
                                <?php else: ?>
                                    <small class="form-text text-muted"><b>Dokumen selain Dokumen Bukti wajib mengunggah Word dan PDF.</b></small>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <?php if ($data['status'] == 'Disetujui' || $data['status'] == 'Selesai' || $data['status'] == 'Ditolak'): ?>
                    <tr>
                        <th>Nomor Dokumen</th>
                        <td>
                            <?php if (!empty($data['nomor_surat'])): ?>
                                <span class="badge badge-success" style="font-size:1rem;"><?= htmlspecialchars($data['nomor_surat']); ?></span>
                            <?php else: ?>
                                <span class="badge badge-danger" style="font-size:1rem;">Belum ada nomor</span>
                            <?php endif; ?>
                            <button type="button" class="btn btn-sm btn-warning ml-2" data-toggle="modal" data-target="#modalEditNomorDokumen">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <th>Catatan Admin</th>
                        <td><?= !empty($data['catatan_admin']) ? htmlspecialchars($data['catatan_admin']) : '<em>Belum ada catatan</em>'; ?></td>
                    </tr>
                    <tr>
                        <th>No Telepon</th>
                        <td>
                            <?= !empty($data['no_tlp']) ? htmlspecialchars($data['no_tlp']) : '<em>Belum ada nomor telepon</em>'; ?>
                            <button type='button' class='btn btn-sm btn-success' onclick='kirimWA(<?= $data['id_pengajuan']; ?>, "<?= $data['no_tlp']; ?>", "<?= $data['kode_pokja']; ?>", "<?= $data['nomor_surat']; ?>", "<?= $data['judul_dokumen']; ?>", "<?= $data['nama_jenis']; ?>", "<?= $data['tanggal_dokumen']; ?>")'>
                                <i class='fab fa-whatsapp'></i>
                            </button>
                        </td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <th>Catatan Awal</th>
                        <td><?= !empty($data['catatan']) ? htmlspecialchars($data['catatan']) : '<em>Belum ada catatan</em>'; ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

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

                <hr>

                <?php if (!empty($data['file_draft']) || !empty($data['file_final'])): ?>
                    <script>
                    function openAndPrint() {
                        const fileUrl = "<?= $data['status'] === 'Selesai'
                            ? '../assets/upload/draft_final/' . rawurlencode(basename(!empty($data['file_final']) ? $data['file_final'] : $data['file_draft']))
                            : '../assets/upload/draft_word/' . rawurlencode(basename($data['file_draft'])); ?>";
                        const printWindow = window.open(fileUrl, "_blank");
                        if (printWindow) {
                            printWindow.onload = function() {
                                printWindow.focus();
                                printWindow.print();
                            };
                        } else {
                            alert("Izinkan pop-up di browser agar dapat mencetak dokumen otomatis.");
                        }
                    }
                    </script>
                <?php else: ?>
                    <p><em>Tidak ada file yang dilampirkan.</em></p>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <a class="btn btn-app bg-warning" href="main_admin.php?unit=pengajuan">
                    <i class="fas fa-reply"></i> Kembali
                </a>
            </div>
            <br>
        </div>


    <script>
function prepareWaSubmit(message) {
    if (!confirm(message)) {
        return false;
    }

    window.open('about:blank', 'waNotifyWindow');
    return true;
}

function kirimWA(idPengajuan, noTel, kodePokja, nomorSurat, judulDokumen, namaJenis, tanggalDokumen) {
          if (!noTel || noTel.trim() === '') {
              alert('Nomor telepon tidak tersedia untuk pengajuan ini.');
              return;
          }

          var noTelFormatted = noTel.trim();
          if (noTelFormatted.startsWith('08')) {
              noTelFormatted = '62' + noTelFormatted.substring(1);
          } else if (noTelFormatted.startsWith('+62')) {
              noTelFormatted = noTelFormatted.substring(1);
          } else if (!noTelFormatted.startsWith('62')) {
              noTelFormatted = '62' + noTelFormatted;
          }

          var tanggalPengajuan = new Date().toLocaleDateString('id-ID', {
              day: 'numeric',
              month: 'long',
              year: 'numeric'
          });

          var pesan = encodeURIComponent(
              'Halo Pokja ' + kodePokja + ',\n\n' +
              'Berikut ringkasan pengajuannya:\n\n' +
              'No surat\t: ' + nomorSurat + '\n' +
              'Judul Dokumen\t: ' + judulDokumen + '\n' +
              'Jenis Dokumen\t: ' + namaJenis + '\n' +
              'Tanggal Pengajuan\t: ' + tanggalPengajuan + '\n\n' +
              'Dokumen tersebut telah "DISETUJUI" sebelum masuk ke tahap FINAL apakah ada perubahan atau tambahan di dokumen anda? jika ada anda silahkan upload ulang dokumen anda dengan berupa "Word" http://192.168.1.108/app_no-surat untuk informasi lebih lanjut.\n\n' +
              'Terima kasih.'
          );

          var url = 'https://wa.me/' + noTelFormatted + '?text=' + pesan;
          window.open(url, '_blank');
}

function prepareFinalUpload(isDokumenBukti) {
    var wordInput = document.getElementById('fileWordFinal');
    var pdfInput = document.getElementById('filePdfFinal');
    var hasWord = wordInput && wordInput.files.length > 0;
    var hasPdf = pdfInput && pdfInput.files.length > 0;

    if (isDokumenBukti && !hasWord && !hasPdf) {
        alert('Dokumen Bukti wajib mengunggah minimal satu file final: Word atau PDF.');
        return false;
    }
    if (!isDokumenBukti && (!hasWord || !hasPdf)) {
        alert('Dokumen selain Dokumen Bukti wajib mengunggah file final Word dan PDF.');
        return false;
    }

    var message = isDokumenBukti
        ? 'Upload file final Dokumen Bukti dan ubah status menjadi Selesai?'
        : 'Upload dokumen final Word dan PDF lalu ubah status menjadi Selesai?';
    return prepareWaSubmit(message + ' WhatsApp Web akan dibuka otomatis jika nomor telepon tersedia.');
}
</script>
    </div>
</section>
