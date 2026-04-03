<?php
require_once("../config/koneksi.php");
require_once("../config/notifikasi.php");

if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
    $query = mysqli_query($config, "
        SELECT p.*, j.nama_jenis, u.nama_lengkap, u.kode_pokja, u.email_user
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

if (isset($_POST['upload_pdf'])) {
    $file_tmp = $_FILES['file_pdf']['tmp_name'];
    $file_name = $_FILES['file_pdf']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ($file_ext != 'pdf') {
        echo "<script>alert('Hanya file PDF yang diperbolehkan!');</script>";
    } else {
        $new_name = 'dokumen_final_' . time() . '.pdf';
        $upload_path = '../assets/upload/draft_word/' . $new_name;

        if (!empty($data['file_draft']) && file_exists('../assets/upload/draft_word/' . $data['file_draft'])) {
            unlink('../assets/upload/draft_word/' . $data['file_draft']);
        }

        if (move_uploaded_file($file_tmp, $upload_path)) {
            mysqli_query($config, "
                UPDATE tb_pengajuan_dokumen
                SET file_draft='$new_name', file_final='$new_name', status='Selesai'
                WHERE id_pengajuan='$id_pengajuan'
            ");

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

            header('Location: main_admin.php?unit=pengajuan&msg=File PDF berhasil diupload, status SELESAI, email dan Telegram sudah diproses!');
            exit;
        } else {
            header('Location: main_admin.php?unit=detail_pengajuan&err=Gagal mengupload file!');
        }
    }
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
                <?php if (!empty($data['file_draft'])): ?>
                    <a href="../assets/upload/draft_word/<?= $data['file_draft']; ?>"
                       class="btn btn-sm btn-success ml-2" download onclick="openAndPrint()">
                        <i class="fas fa-download"></i> Download
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
                                <form method="POST" enctype="multipart/form-data" class="d-inline-block ml-2">
                                    <input type="hidden" name="id_pengajuan" value="<?= $data['id_pengajuan']; ?>">
                                    <input type="file" name="file_pdf" accept="application/pdf" required>
                                    <button type="submit" name="upload_pdf" class="btn btn-sm btn-primary">
                                        <i class="fas fa-upload"></i> Upload PDF Final
                                    </button>
                                </form>
                                <br>
                                <small class="form-text text-muted"><b>Note : Mohon konfirmasi ke pokja terlebih dahulu sebelum mengupload file PDF final.</b></small>
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

                <hr>

                <?php if (!empty($data['file_draft'])): ?>
                    <script>
                    function openAndPrint() {
                        const fileUrl = "../assets/upload/draft_word/<?= $data['file_draft']; ?>";
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
</script>
    </div>
</section>
