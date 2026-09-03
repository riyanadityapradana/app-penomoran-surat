<?php
require_once("../config/koneksi.php");
require_once("../config/dokumen_helper.php");

if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);

    // Ambil data pengesahan (status harus selesai)
    $query = mysqli_query($config, "
        SELECT p.*, j.nama_jenis, u.nama_lengkap, u.kode_pokja
        FROM tb_pengajuan_dokumen p
        LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
        LEFT JOIN tb_user u ON p.id_user = u.id_user
        WHERE p.id_pengajuan = '$id_pengajuan' AND p.status = 'Selesai'
    ");

    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        echo "<script>
                alert('Data pengesahan tidak ditemukan atau belum berstatus Selesai!');
                window.location = 'main_pokja.php?unit=detail_pengesahan';
              </script>";
        exit;
    }
} else {
    echo "<script>
            alert('ID pengesahan tidak ditemukan!');
            window.location = 'main_pokja.php?unit=pengesahan';
          </script>";
    exit;
}
$is_regulasi_workflow = app_uses_regulasi_workflow($data['bentuk_dokumen'] ?? '', $data['nomor_surat'] ?? '');
$tanggal_pengesahan_efektif = !empty($data['tanggal_pengesahan'])
    ? $data['tanggal_pengesahan']
    : (!empty($data['tanggal_disetujui']) ? $data['tanggal_disetujui'] : $data['tanggal_ajuan']);
?>

<section class="content-header">
    <h1>Detail Dokumen Pengesahan (File PDF)</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Informasi Pengesahan Dokumen</h3>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">Nomor Surat</th>
                        <td><?= !empty($data['nomor_surat'])
                            ? htmlspecialchars($data['nomor_surat'])
                            : ($is_regulasi_workflow ? '<em>Nomor dokumen belum tersedia</em>' : '<em>Tidak memerlukan nomor dokumen</em>'); ?></td>
                    </tr>
					<tr>
                        <th width="200">Kode Pokja</th>
                        <td><?= htmlspecialchars($data['kode_pokja']); ?> (<?= htmlspecialchars($data['nama_lengkap']); ?>)</td>
                    </tr>
                    <tr>
                        <th>Standard EP</th>
                        <td><?= htmlspecialchars($data['elemen_penilaian']); ?></td>
                    </tr>
                    <tr>
                        <th>Jenis Dokumen</th>
                        <td><?= htmlspecialchars($data['nama_jenis']); ?></td>
                    </tr>
                    <tr>
                        <th>Bentuk Dokumen</th>
                        <td><?= htmlspecialchars(app_display_bentuk_dokumen($data['bentuk_dokumen'] ?? '')); ?></td>
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

                <hr>
                <?php
                    $pdfFinal = !empty($data['file_final']) ? basename($data['file_final']) : '';
                    $wordFinal = !empty($data['file_draft']) ? basename($data['file_draft']) : '';
                    $hasPdfFinal = $pdfFinal !== ''
                        && strtolower(pathinfo($pdfFinal, PATHINFO_EXTENSION)) === 'pdf'
                        && file_exists('../assets/upload/draft_final/' . $pdfFinal);
                    $hasWordFinal = $wordFinal !== ''
                        && preg_match('/\.(doc|docx)$/i', $wordFinal)
                        && file_exists('../assets/upload/draft_final/' . $wordFinal);
                ?>

                <?php if ($hasPdfFinal): ?>
                    <?php $pdfUrl = '../assets/upload/draft_final/' . rawurlencode($pdfFinal); ?>
                    <h5>Preview Dokumen PDF:</h5>
                    <a href="<?= htmlspecialchars($pdfUrl); ?>" class="btn btn-success mb-3 float-right ml-2" download>
                        <i class="fas fa-file-pdf"></i> Download File PDF
                    </a>
                    <?php if ($hasWordFinal): ?>
                        <a href="../assets/upload/draft_final/<?= rawurlencode($wordFinal); ?>" class="btn btn-info mb-3 float-right ml-2" download>
                            <i class="fas fa-file-word"></i> Download File Word
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-primary mb-3 float-right" onclick="printPDF('<?= htmlspecialchars($pdfUrl); ?>')">
                        <i class="fas fa-print"></i> Cetak PDF
                    </button>
                    <iframe src="<?= htmlspecialchars($pdfUrl); ?>" style="width:100%; height:600px;" frameborder="0"></iframe>
                    <script>
                        function printPDF(url) {
                            const printWindow = window.open(url, '_blank');
                            if (printWindow) {
                                printWindow.addEventListener('load', function() {
                                    printWindow.print();
                                });
                            }
                        }
                    </script>
                <?php elseif ($hasWordFinal): ?>
                    <h5>Dokumen Final Word:</h5>
                    <a href="../assets/upload/draft_final/<?= rawurlencode($wordFinal); ?>" class="btn btn-info mb-3" download>
                        <i class="fas fa-file-word"></i> Download File Word
                    </a>
                <?php else: ?>
                    <p><em>Tidak ada file final yang tersedia.</em></p>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <a class="btn btn-app bg-warning" href="main_pokja.php?unit=pengesahan">
                    <i class="fas fa-reply"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</section>
