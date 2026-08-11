<?php
require_once("../config/koneksi.php");

// Hitung data statistik utama
$q_menunggu_total = mysqli_query($config, "SELECT COUNT(*) AS total FROM tb_pengajuan_dokumen WHERE status = 'Menunggu Verifikasi'");
$menunggu_total = (int) mysqli_fetch_assoc($q_menunggu_total)['total'];

$q_disetujui_total = mysqli_query($config, "SELECT COUNT(*) AS total FROM tb_pengajuan_dokumen WHERE status = 'Disetujui'");
$disetujui_total = (int) mysqli_fetch_assoc($q_disetujui_total)['total'];

$q_ditolak_total = mysqli_query($config, "SELECT COUNT(*) AS total FROM tb_pengajuan_dokumen WHERE status = 'Ditolak'");
$ditolak_total = (int) mysqli_fetch_assoc($q_ditolak_total)['total'];

$q_disahkan = mysqli_query($config, "SELECT COUNT(*) AS total FROM tb_pengajuan_dokumen WHERE status = 'Selesai'");
$disahkan = (int) mysqli_fetch_assoc($q_disahkan)['total'];

$q_selesai_bulan_ini = mysqli_query($config, "
    SELECT COUNT(*) AS total
    FROM tb_pengajuan_dokumen
    WHERE status = 'Selesai'
      AND MONTH(COALESCE(tanggal_disetujui, tanggal_ajuan)) = MONTH(CURDATE())
      AND YEAR(COALESCE(tanggal_disetujui, tanggal_ajuan)) = YEAR(CURDATE())
");
$selesai_bulan_ini = (int) mysqli_fetch_assoc($q_selesai_bulan_ini)['total'];

$q_pokja = mysqli_query($config, "SELECT COUNT(*) AS total FROM tb_user WHERE level = 'Pokja'");
$pokja = (int) mysqli_fetch_assoc($q_pokja)['total'];

// Ambil data jumlah pengajuan per Pokja
$q_rekap = mysqli_query($config, "
    SELECT
        u.kode_pokja,
        u.nama_lengkap,
        COUNT(p.id_pengajuan) AS total_pengajuan,
        GROUP_CONCAT(DISTINCT j.nama_jenis SEPARATOR ', ') AS jenis_dokumen
    FROM tb_user u
    LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user AND p.status = 'Menunggu Verifikasi'
    LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
    WHERE u.level = 'Pokja'
    GROUP BY u.id_user
    ORDER BY total_pengajuan DESC
");

// Data untuk chart
$chart_labels = [];
$chart_data = [];
$q_chart = mysqli_query($config, "
    SELECT
        u.nama_lengkap,
        COUNT(p.id_pengajuan) AS total_pengajuan
    FROM tb_user u
    LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user AND p.status = 'Menunggu Verifikasi'
    WHERE u.level = 'Pokja'
    GROUP BY u.id_user
    ORDER BY total_pengajuan DESC
");
while ($row = mysqli_fetch_assoc($q_chart)) {
    $chart_labels[] = $row['nama_lengkap'];
    $chart_data[] = (int)$row['total_pengajuan'];
}

$trend_labels = [];
$trend_menunggu = [];
$trend_disetujui = [];
$trend_ditolak = [];
$trend_selesai = [];
for ($i = 5; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-$i months"));
    $trend_labels[] = date('M Y', strtotime($monthKey . '-01'));
    $trend_menunggu[$monthKey] = 0;
    $trend_disetujui[$monthKey] = 0;
    $trend_ditolak[$monthKey] = 0;
    $trend_selesai[$monthKey] = 0;
}
$q_trend = mysqli_query($config, "
    SELECT DATE_FORMAT(tanggal_ajuan, '%Y-%m') AS bulan, status, COUNT(*) AS total
    FROM tb_pengajuan_dokumen
    WHERE tanggal_ajuan >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
    GROUP BY DATE_FORMAT(tanggal_ajuan, '%Y-%m'), status
");
while ($row = mysqli_fetch_assoc($q_trend)) {
    if (!isset($trend_menunggu[$row['bulan']])) {
        continue;
    }
    if ($row['status'] === 'Menunggu Verifikasi') $trend_menunggu[$row['bulan']] = (int) $row['total'];
    if ($row['status'] === 'Disetujui') $trend_disetujui[$row['bulan']] = (int) $row['total'];
    if ($row['status'] === 'Ditolak') $trend_ditolak[$row['bulan']] = (int) $row['total'];
    if ($row['status'] === 'Selesai') $trend_selesai[$row['bulan']] = (int) $row['total'];
}
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
                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?= $menunggu_total ?></h3>
                                <p>Menunggu Verifikasi</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <a href="main_admin.php?unit=pengajuan" class="small-box-footer">
                                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?= $disetujui_total ?></h3>
                                <p>Disetujui</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <a href="main_admin.php?unit=pengajuan" class="small-box-footer">
                                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="small-box bg-danger">
                            <div class="inner">
                                <h3><?= $ditolak_total ?></h3>
                                <p>Ditolak</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-times-circle"></i>
                            </div>
                            <a href="main_admin.php?unit=rekap&status=Ditolak" class="small-box-footer">
                                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="small-box bg-primary">
                            <div class="inner">
                                <h3><?= $disahkan ?></h3>
                                <p>Selesai Total</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-file-pdf"></i>
                            </div>
                            <a href="main_admin.php?unit=pengesahan" class="small-box-footer">
                                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?= $selesai_bulan_ini ?></h3>
                                <p>Selesai Bulan Ini</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <a href="main_admin.php?unit=pengesahan" class="small-box-footer">
                                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-2 col-md-4 col-6">
                        <div class="small-box bg-secondary">
                            <div class="inner">
                                <h3><?= $pokja ?></h3>
                                <p>Total Pokja</p>
                            </div>
                            <div class="icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <a href="main_admin.php?unit=pokja" class="small-box-footer">
                                Lihat Data <i class="fas fa-arrow-circle-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <!-- /.row statistik -->

                <div class="card card-default mt-4">
                    <div class="card-header bg-dark">
                        <h3 class="card-title text-white"><i class="fas fa-chart-line"></i> Tren Dokumen 6 Bulan Terakhir</h3>
                    </div>
                    <div class="card-body">
                        <div id="trendChart" style="width:100%; height:360px;"></div>
                    </div>
                </div>

                <!-- Statistik per Pokja -->
                <div class="card card-default mt-4">
                    <div class="card-header bg-info">
                        <h3 class="card-title text-white"><i class="fas fa-chart-bar"></i> Statistik Pengajuan Dokumen per Pokja</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="text-center mb-3">Tabel Statistik Pengajuan Dokumen per Pokja</h4>
                                <table id="example0" class="table table-bordered table-striped" style="width: 100%;">
                                    <thead style="background:rgb(23, 162, 184, 1)">
                                        <tr>
                                            <th width="50">No</th>
                                            <th>Kode Pokja</th>
                                            <th>Nama Pokja</th>
                                            <th>Jenis Dokumen</th>
                                            <th>Total Pengajuan Dokumen</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        $warna_jenis = [
                                            'SK' => 'badge-primary',
                                            'SPO' => 'badge-success',
                                            'Panduan' => 'badge-warning',
                                            'Pedoman' => 'badge-info',
                                            // Tambahkan jenis dokumen lainnya jika ada
                                        ];
                                        mysqli_data_seek($q_rekap, 0); // Reset pointer untuk tabel
                                        if (mysqli_num_rows($q_rekap) > 0):
                                            while ($r = mysqli_fetch_assoc($q_rekap)):
                                        ?>
                                        <tr>
                                            <td class="text-center"><?= $no++; ?></td>
                                            <td class="text-center"><?= htmlspecialchars($r['kode_pokja']); ?></td>
                                            <td><?= htmlspecialchars($r['nama_lengkap']); ?></td>
                                            <td class="text-center">
                                                <?php
                                                if (!empty($r['jenis_dokumen'])) {
                                                    $jenis_list = explode(', ', $r['jenis_dokumen']);
                                                    foreach ($jenis_list as $jenis) {
                                                        $warna = $warna_jenis[$jenis] ?? 'badge-secondary';
                                                        echo '<span class="badge ' . $warna . ' mr-1">' . htmlspecialchars($jenis) . '</span>';
                                                    }
                                                } else {
                                                    echo '-';
                                                }
                                                ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge badge-primary"><?= $r['total_pengajuan']; ?></span>
                                            </td>
                                        </tr>
                                        <?php
                                            endwhile;
                                        else:
                                        ?>
                                        <tr>
                                            <td colspan="5" class="text-center"><em>Belum ada data pengajuan</em></td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4 class="text-center mb-3">Grafik Batang Pengajuan Dokumen per Pokja</h4>
                                <div id="pokjaChart" style="width:100%; height:500px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- /.card statistik per pokja -->
<br><br>
            </div>
        </div>
    </div>
</section>

<script src="../assets/chart/js/highcharts.js"></script>
<script>
if (window.Highcharts && document.getElementById('pokjaChart')) {
Highcharts.chart('pokjaChart', {
    chart: {
        type: 'column',
        backgroundColor: 'transparent',
        borderRadius: 8
    },
    title: {
        text: 'Pengajuan Dokumen per Pokja',
        style: { fontWeight: '700', color: '#172033' }
    },
    xAxis: {
        categories: <?php echo json_encode($chart_labels); ?>,
        title: {
            text: 'Nama Pokja'
        }
    },
    yAxis: {
        title: {
            text: 'Total Pengajuan Dokumen'
        },
        allowDecimals: false
    },
    series: [{
        name: 'Total Pengajuan',
        data: <?php echo json_encode($chart_data); ?>,
        color: '#0f766e'
    }],
    credits: {
        enabled: false
    }
});
}

if (window.Highcharts && document.getElementById('trendChart')) {
Highcharts.chart('trendChart', {
    chart: { type: 'line', backgroundColor: 'transparent', borderRadius: 8 },
    title: { text: 'Tren Status Dokumen per Bulan', style: { fontWeight: '700', color: '#172033' } },
    xAxis: { categories: <?php echo json_encode($trend_labels); ?> },
    yAxis: { title: { text: 'Jumlah Dokumen' }, allowDecimals: false },
    series: [
        { name: 'Menunggu', data: <?php echo json_encode(array_values($trend_menunggu)); ?>, color: '#ffc107' },
        { name: 'Disetujui', data: <?php echo json_encode(array_values($trend_disetujui)); ?>, color: '#28a745' },
        { name: 'Ditolak', data: <?php echo json_encode(array_values($trend_ditolak)); ?>, color: '#dc3545' },
        { name: 'Selesai', data: <?php echo json_encode(array_values($trend_selesai)); ?>, color: '#007bff' }
    ],
    credits: { enabled: false }
});
}
</script>

<script>
$(document).ready(function() {
    $('#example0').DataTable({
        "pageLength": 5,
        "lengthMenu": [[5, 10, 25, 50], [5, 10, 25, 50]],
        "language": {
            "lengthMenu": "Tampilkan _MENU_ entri per halaman",
            "zeroRecords": "Tidak ada data yang ditemukan",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada data tersedia",
            "infoFiltered": "(difilter dari _MAX_ total entri)",
            "search": "Cari:",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        }
    });
});
</script>
