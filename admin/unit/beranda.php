<?php
require_once("../config/koneksi.php");

// Hitung data statistik utama
$q_diajukan = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status != 'Selesai'");
$diajukan = mysqli_num_rows($q_diajukan);

$q_disahkan = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status = 'Selesai'");
$disahkan = mysqli_num_rows($q_disahkan);

$q_pokja = mysqli_query($config, "SELECT * FROM tb_user WHERE level = 'Pokja'");
$pokja = mysqli_num_rows($q_pokja);

// Ambil data jumlah pengajuan per Pokja
$q_rekap = mysqli_query($config, "
    SELECT 
        u.kode_pokja, 
        u.nama_lengkap,
        COUNT(p.id_pengajuan) AS total_pengajuan
    FROM tb_user u
    LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user
    WHERE u.level = 'Pokja'
    GROUP BY u.id_user
    ORDER BY total_pengajuan DESC
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
        <div class="row">
            <div class="col-12">

                <!-- Statistik Card -->
                <div class="row">
                    <div class="col-lg-4 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3><?= $diajukan ?></h3>
                                <p>Dokumen Diajukan</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-upload"></i>
                            </div>
                            <a href="main_admin.php?unit=dokumen_ajuan" class="small-box-footer">
                                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?= $disahkan ?></h3>
                                <p>Dokumen Disahkan</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <a href="main_admin.php?unit=dokumen_sah" class="small-box-footer">
                                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?= $pokja ?></h3>
                                <p>Total Pokja</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <a href="main_admin.php?unit=pokja" class="small-box-footer">
                                Lihat Data Pokja <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /.row statistik -->

                <!-- Statistik per Pokja -->
                <div class="card card-default mt-4">
                    <div class="card-header bg-info">
                        <h3 class="card-title text-white"><i class="fas fa-chart-bar"></i> Statistik Pengajuan Dokumen per Pokja</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped table-bordered mb-0">
                            <thead class="text-center bg-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>Kode Pokja</th>
                                    <th>Nama Pokja</th>
                                    <th>Total Pengajuan Dokumen</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if (mysqli_num_rows($q_rekap) > 0):
                                    while ($r = mysqli_fetch_assoc($q_rekap)):
                                ?>
                                <tr>
                                    <td class="text-center"><?= $no++; ?></td>
                                    <td class="text-center"><?= htmlspecialchars($r['kode_pokja']); ?></td>
                                    <td><?= htmlspecialchars($r['nama_lengkap']); ?></td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $r['total_pengajuan']; ?></span>
                                    </td>
                                </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center"><em>Belum ada data pengajuan</em></td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- /.card statistik per pokja -->

            </div>
        </div>
    </div>
</section>
