<?php
require_once("../config/koneksi.php");

// Pastikan user sudah login
if (!isset($_SESSION['kode_pokja'])) {
    die("<script>alert('Sesi login tidak ditemukan! Silakan login ulang.'); window.location='../login.php';</script>");
}

$kode_pokja_login = $_SESSION['kode_pokja'];

// --- AMBIL FILTER ---
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$tgl_filter = isset($_GET['tanggal_disetujui']) ? $_GET['tanggal_disetujui'] : '';

// --- QUERY DASAR ---
$query = "SELECT p.*, j.nama_jenis, u.kode_pokja 
          FROM tb_pengajuan_dokumen p
          LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
          LEFT JOIN tb_user u ON p.id_user = u.id_user
          WHERE u.kode_pokja = '$kode_pokja_login'";

// --- TAMBAHKAN FILTER ---
if (!empty($status_filter)) {
    $query .= " AND p.status = '$status_filter'";
}
if (!empty($tgl_filter)) {
    $query .= " AND DATE(p.tanggal_disetujui) = '$tgl_filter'";
}

$query .= " ORDER BY p.id_pengajuan DESC";
$data = mysqli_query($config, $query);
?>

<section class="content-header">
    <h1>Rekap Dokumen Pengesahan</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Filter dan Cetak Rekap Dokumen Pokja <b><?= htmlspecialchars($kode_pokja_login) ?></b></h3>
            </div>

            <div class="card-body">
                <form method="GET" class="form-inline mb-4">
                    <input type="hidden" name="unit" id="unit" value="rekap">

                    <!-- Filter Status -->
                    <div class="form-group mr-3">
                        <label for="status" class="mr-2"><strong>Status:</strong></label>
                        <select name="status" id="status" class="form-control" style="border:2px solid #004d26;">
                            <option value="">Semua</option>
                            <option value="Menunggu Verifikasi" <?= ($status_filter == 'Menunggu Verifikasi') ? 'selected' : '' ?>>Menunggu</option>
                            <option value="Disetujui" <?= ($status_filter == 'Disetujui') ? 'selected' : '' ?>>Disetujui</option>
                            <option value="Ditolak" <?= ($status_filter == 'Ditolak') ? 'selected' : '' ?>>Ditolak</option>
                            <option value="Selesai" <?= ($status_filter == 'Selesai') ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>

                    <!-- Filter Tanggal -->
                    <div class="form-group mr-3">
                        <label for="tanggal_disetujui" class="mr-2"><strong>Tanggal Pengesahan:</strong></label>
                        <input type="date" name="tanggal_disetujui" id="tanggal_disetujui" value="<?= $tgl_filter ?>" class="form-control" style="border:2px solid #004d26;">
                    </div>

                    <!-- Tombol Filter -->
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-filter"></i> Tampilkan
                    </button>

                    <!-- Tombol Cetak -->
                    <a href="unit/rekap/cetak_rekap.php?status=<?= $status_filter ?>&tanggal_disetujui=<?= $tgl_filter ?>&kode_pokja=<?= $kode_pokja_login ?>" 
                       target="_blank" class="btn btn-danger ml-2">
                        <i class="fas fa-print"></i> Cetak
                    </a>
                </form>

                <table id="example2" class="table table-bordered table-striped text-center">
                    <thead style="background:rgb(0, 0, 0, 1); color: white;">
                        <tr>
                            <th>No</th>
                            <th style="font-size: 14px;" width="120" responsive>Tgl Ajuan</th>
                            <th style="font-size: 14px;" width="120" responsive>No Dokumen</th>
                            <th style="font-size: 14px;" width="120" responsive>Jenis Dokumen</th>
                            <th style="font-size: 14px;" width="120" responsive>Judul Dokumen</th>
                            <th style="font-size: 14px;" width="120" responsive>Tgl Sah/ Ditolak</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($data) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($data)) {
                                echo "<tr>
                                        <td>{$no}</td>
                                        <td>" . date('d-m-Y', strtotime($row['tanggal_ajuan'])) . "</td>
                                        <td>" . (!empty($row['nomor_surat']) ? $row['nomor_surat'] : '-') . "</td>
                                        <td>{$row['nama_jenis']}</td>
                                        <td>{$row['judul_dokumen']}</td>
                                        <td>" . (!empty($row['tanggal_disetujui']) ? date('d-m-Y', strtotime($row['tanggal_disetujui'])) : '-') . "</td>
                                        <td><span class='badge badge-";
                                switch ($row['status']) {
                                    case 'Selesai': echo "primary"; break;
                                    case 'Disetujui': echo "success"; break;
                                    case 'Ditolak': echo "danger"; break;
                                    default: echo "warning";
                                }
                                echo "'>{$row['status']}</span></td>
                                      </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='7'><em>Tidak ada data dokumen</em></td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
