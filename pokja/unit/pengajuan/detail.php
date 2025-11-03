<?php
require_once("../config/koneksi.php");

if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
    $query = mysqli_query($config, "
        SELECT p.*, j.nama_jenis, u.nama_lengkap, u.kode_pokja 
        FROM tb_pengajuan_dokumen p
        LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
        LEFT JOIN tb_user u ON p.id_user = u.id_user
        WHERE p.id_pengajuan = '$id_pengajuan'
    ");
    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        echo "<script>
                alert('Data tidak ditemukan!');
                window.location = 'main_pokja.php?unit=pengajuan';
              </script>";
        exit;
    }
} else {
    echo "<script>
            alert('ID Pengajuan tidak ditemukan!');
            window.location = 'main_pokja.php?unit=pengajuan';
          </script>";
    exit;
}

// --- WARNA BADGE STATUS --- //
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
    <h1>Detail Pengajuan Surat (File Word)</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Informasi Pengajuan</h3>
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
						</td>
                    </tr>
                    <?php if ($data['status'] == 'Disetujui' || $data['status'] == 'Selesai' || $data['status'] == 'Ditolak'): ?>
                    <tr>
                        <th>Nomor Dokumen</th>
                        <td><?= !empty($data['nomor_surat']) ? htmlspecialchars($data['nomor_surat']) : '<em>Belum ada nomor</em>'; ?></td>
                    </tr>
                    <tr>
                        <th>Catatan Admin</th>
                        <td><?= !empty($data['catatan_admin']) ? htmlspecialchars($data['catatan_admin']) : '<em>Belum ada catatan</em>'; ?></td>
                    </tr>
                    <?php else: ?>
                    <tr>
                        <th>Catatan Awal</th>
                        <td><?= !empty($data['catatan']) ? htmlspecialchars($data['catatan']) : '<em>Belum ada catatan</em>'; ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <hr>
                <!--<h5>Preview Dokumen Word:</h5>-->

                <?php if (!empty($data['file_draft'])): ?>
                    <?php
                        // Lokasi file di server
                        $file_path = '../assets/upload/draft_word/' . $data['file_draft'];

                        // URL absolut untuk viewer Google Docs
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $file_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/akreditas/assets/upload/draft_word/' . urlencode($data['file_draft']);
                    ?>

                    <!-- Tombol Download -->
                    <a href="<?= $file_path; ?>" class="btn btn-success mb-3 float-right" download>
                        <i class="fas fa-download"></i> Download File Word
                    </a>

                    <!-- Preview via Google Docs Viewer -->
                    <!--<iframe 
                        src="https://docs.google.com/gview?url=<?= $file_url; ?>&embedded=true"
                        style="width:100%; height:600px;" 
                        frameborder="0">
                    </iframe>-->
                <?php else: ?>
                    <p><em>Tidak ada file Word yang dilampirkan.</em></p>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <a class="btn btn-app bg-warning" href="main_pokja.php?unit=pengajuan">
                    <i class="fas fa-reply"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</section>
