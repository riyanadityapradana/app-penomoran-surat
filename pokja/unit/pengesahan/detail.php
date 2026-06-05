<?php
require_once("../config/koneksi.php");

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
                        <td><?= htmlspecialchars($data['nomor_surat']); ?></td>
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
                        <td><?= date('d-m-Y', strtotime($data['tanggal_ajuan'])); ?></td>
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
                <h5>Preview Dokumen PDF:</h5>

                <?php
                    $pdfPreviewFile = !empty($data['file_final']) ? $data['file_final'] : $data['file_draft'];
                    $pdfPreviewFolder = file_exists('../assets/upload/draft_final/' . $pdfPreviewFile) ? 'draft_final' : 'draft_word';
                    $hasWordFinal = !empty($data['file_draft'])
                        && preg_match('/\.(doc|docx)$/i', $data['file_draft'])
                        && file_exists('../assets/upload/draft_final/' . $data['file_draft']);
                ?>
                <?php if (!empty($pdfPreviewFile)): ?>
                    <?php
                        // Lokasi file di server
                        $file_path = '../assets/upload/' . $pdfPreviewFolder . '/' . $pdfPreviewFile;

                        // URL relatif mengikuti folder aplikasi yang sedang dibuka.
                        $file_url = '../assets/upload/' . $pdfPreviewFolder . '/' . rawurlencode($pdfPreviewFile);
                    ?>

                    <!-- Tombol Download -->
                    <a href="<?= $file_path; ?>" class="btn btn-success mb-3 float-right ml-2" download>
                        <i class="fas fa-download"></i> Download File PDF
                    </a>
                    <?php if ($hasWordFinal): ?>
                    <a href="../assets/upload/draft_final/<?= htmlspecialchars($data['file_draft']); ?>" class="btn btn-info mb-3 float-right ml-2" download>
                        <i class="fas fa-file-word"></i> Download File Word
                    </a>
                    <?php endif; ?>

                    <!-- Tombol Cetak -->
                    <button type="button" class="btn btn-primary mb-3 float-right" onclick="printPDF('<?= $file_url; ?>')">
                        <i class="fas fa-print"></i> Cetak PDF
                    </button>

                    <!-- Preview PDF -->
                    <iframe 
                        src="<?= $file_url; ?>" 
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
                    <p><em>Tidak ada file PDF yang dilampirkan.</em></p>
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
