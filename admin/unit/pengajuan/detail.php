<?php
require_once("../config/koneksi.php");

// --- AMBIL DATA BERDASARKAN ID --- //
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
        header('Location: main_admin.php?unit=pengajuan&err=Data pengajuan tidak ditemukan!');
        exit;
    }
} else {
     header('Location: main_admin.php?unit=pengajuan&err=ID Pengajuan tidak ditemukan!');
    exit;
}

// --- PROSES UPLOAD PDF FINAL (TANPA EMAIL) --- //
if (isset($_POST['upload_pdf'])) {
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
                SET file_draft='$new_name', status='Selesai'
                WHERE id_pengajuan='$id_pengajuan'
            ");

            header('Location: main_admin.php?unit=pengajuan&msg=File PDF berhasil diupload dan status diubah menjadi SELESAI!');
            exit;
        } else {
            header('Location: main_admin.php?unit=detail_pengajuan&err=Gagal mengupload file!');
        }
    }
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
                    <?php else: ?>
                    <tr>
                        <th>Catatan Awal</th>
                        <td><?= !empty($data['catatan']) ? htmlspecialchars($data['catatan']) : '<em>Belum ada catatan</em>'; ?></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <hr>
                <!-- Tombol Download dipindahkan ke card-header -->

                <?php if (!empty($data['file_draft'])): ?>
                    <?php
                    $file_url = 'http://localhost:8080/akreditas/assets/upload/draft_word/' . urlencode($data['file_draft']);
                    ?>
                   <!-- <iframe 
                        src="https://docs.google.com/gview?url=<?= $file_url; ?>&embedded=true"
                        style="width:100%; height:600px;" 
                        frameborder="0">
                    </iframe>-->

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
        </div>
    </div>
</section>
