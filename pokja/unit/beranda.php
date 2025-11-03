<?php
require_once("../config/koneksi.php");
$id_user = $_SESSION['id_user'];

// Hitung data statistik utama
$q_menunggu = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status = 'Menunggu Verifikasi' AND id_user = '$id_user'");
$menunggu = mysqli_num_rows($q_menunggu);

$q_disetujui = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status = 'Disetujui' AND id_user = '$id_user'");
$disetujui = mysqli_num_rows($q_disetujui);

$q_ditolak = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status = 'Ditolak' AND id_user = '$id_user'");
$ditolak = mysqli_num_rows($q_ditolak);

$q_selesai = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status = 'Selesai' AND id_user = '$id_user'");
$selesai = mysqli_num_rows($q_selesai);

// Ambil data diterima (disetujui dan selesai)
$q_diterima = mysqli_query($config, "
    SELECT judul_dokumen, tanggal_ajuan, catatan_admin
    FROM tb_pengajuan_dokumen 
    WHERE status IN ('Disetujui', 'Selesai') AND id_user = '$id_user'
    ORDER BY tanggal_ajuan DESC
");

// Ambil data ditolak
$q_tolak = mysqli_query($config, "
    SELECT judul_dokumen, tanggal_ajuan, catatan_admin
    FROM tb_pengajuan_dokumen 
    WHERE status = 'Ditolak' AND id_user = '$id_user'
    ORDER BY tanggal_ajuan DESC
");
?>

<!-- Content Header (Page header) -->
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h2><i class="fas fa-tachometer-alt me-2"></i> Dashboard</h2>
            </div>
            <div class="col-sm-6 text-right">
                <i class="fas fa-calendar me-1"></i>
                <?php echo date('d F Y'); ?>
            </div>
        </div>
    </div>
</section>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Statistik Card -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $menunggu ?></h3>
                        <p>Menunggu Verifikasi</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                    <a href="main_pokja.php?unit=pengajuan" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $disetujui ?></h3>
                        <p>Disetujui</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <a href="main_pokja.php?unit=pengajuan" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $ditolak ?></h3>
                        <p>Ditolak</p>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                    <a href="main_pokja.php?unit=pengajuan" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $selesai ?></h3>
                        <p>Selesai</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                    <a href="main_pokja.php?unit=pengesahan" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
        <!-- /.row statistik -->

        <!-- Dua Tabel: Diterima & Ditolak -->
        <div class="row mt-4">
            
            <!-- Data Diterima -->
            <div class="col-lg-6 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h3 class="card-title"><i class="fas fa-check"></i> Data Diterima (Disetujui & Selesai)</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-bordered mb-0">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th>Judul Dokumen</th>
                                    <th width="160">Tanggal Diajukan</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($q_diterima) > 0): ?>
                                    <?php while ($r = mysqli_fetch_assoc($q_diterima)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['judul_dokumen']); ?></td>
                                        <td class="text-center"><?= date('d-m-Y', strtotime($r['tanggal_ajuan'])); ?></td>
                                        <td><?= !empty($r['catatan_admin']) ? htmlspecialchars($r['catatan_admin']) : '<em>Belum ada catatan</em>'; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center"><em>Belum ada data diterima</em></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Data Ditolak -->
            <div class="col-lg-6 col-md-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-danger text-white">
                        <h3 class="card-title"><i class="fas fa-times"></i> Data Ditolak</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-hover table-bordered mb-0">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th>Judul Dokumen</th>
                                    <th width="160">Tanggal Diajukan</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (mysqli_num_rows($q_tolak) > 0): ?>
                                    <?php while ($r = mysqli_fetch_assoc($q_tolak)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($r['judul_dokumen']); ?></td>
                                        <td class="text-center"><?= date('d-m-Y', strtotime($r['tanggal_ajuan'])); ?></td>
                                        <td><?= !empty($r['catatan_admin']) ? htmlspecialchars($r['catatan_admin']) : '<em>Belum ada catatan</em>'; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="2" class="text-center"><em>Belum ada data ditolak</em></td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
        <!-- /.row dua tabel -->

    </div>
</section>
