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
        header('Location: main_admin.php?unit=pengesahan&err=Data pengesahan tidak ditemukan atau belum berstatus Selesai!');
        exit;
    }
} else {
    header('Location: main_admin.php?unit=pengesahan&err=ID pengesahan tidak ditemukan!');
    exit;
}

// --- PROSES EDIT UPLOAD PDF (TANPA EMAIL) --- //
if (isset($_POST['edit_upload_pdf'])) {
    $file_tmp = $_FILES['file_pdf']['tmp_name'];
    $file_name = $_FILES['file_pdf']['name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ($file_ext != 'pdf') {
        echo "<script>alert('Hanya file PDF yang diperbolehkan!');</script>";
    } else {
        $new_name = 'dokumen_final_' . time() . '.pdf';
        $upload_path = '../assets/upload/draft_word/' . $new_name;

        // Hapus file lama jika ada
        if (!empty($data['file_draft']) && file_exists('../assets/upload/draft_word/' . $data['file_draft'])) {
            unlink('../assets/upload/draft_word/' . $data['file_draft']);
        }

        if (move_uploaded_file($file_tmp, $upload_path)) {
            // Update database
            mysqli_query($config, "
                UPDATE tb_pengajuan_dokumen
                SET file_draft='$new_name'
                WHERE id_pengajuan='$id_pengajuan'
            ");

            header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&msg=File PDF berhasil diupdate!');
            exit;
        } else {
            header('Location: main_admin.php?unit=detail_pengesahan&id_pengajuan=' . $id_pengajuan . '&err=Gagal mengupload file!');
        }
    }
}
?>

<section class="content-header">
    <h1>Detail Dokumen Pengesahan (File PDF)</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header d-flex align-items-center" style="position:relative;">
                <h3 class="card-title">Informasi Pengesahan Dokumen</h3>
                <div class="ml-auto" style="position:absolute; right:24px; top:9px;">
                    <?php if (isset($_GET['edit'])): ?>
                        <form method="POST" enctype="multipart/form-data" class="d-inline-block ml-2">
                            <input type="file" name="file_pdf" accept="application/pdf" required>
                            <button type="submit" name="edit_upload_pdf" class="btn btn-sm btn-warning">
                                <i class="fas fa-upload"></i> Update PDF
                            </button>
                        </form>
                    <?php endif; ?>
                    <?php if (!empty($data['file_draft'])): ?>
                        <a href="../assets/upload/draft_word/<?= $data['file_draft']; ?>" class="btn btn-sm btn-success ml-2" download>
                            <i class="fas fa-download"></i> Download
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="200">Nomor Surat</th>
                        <td><span class="badge badge-success" style="font-size:1rem;"><?= htmlspecialchars($data['nomor_surat']); ?></span></td>
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

                <?php if (!empty($data['file_draft'])): ?>
                    <?php
                        // Lokasi file di server
                        $file_path = '../assets/upload/draft_word/' . $data['file_draft'];

                        // URL absolut untuk viewer PDF
                        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $file_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/app-penomoran-surat/assets/upload/draft_word//' . urlencode($data['file_draft']);
                    ?>

                    <!-- Tombol Download -->
                    <a href="<?= $file_path; ?>" class="btn btn-success mb-3 float-right ml-2" download>
                        <i class="fas fa-download"></i> Download File PDF
                    </a>

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
                <a class="btn btn-app bg-warning" href="main_admin.php?unit=pengesahan">
                    <i class="fas fa-reply"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</section>
