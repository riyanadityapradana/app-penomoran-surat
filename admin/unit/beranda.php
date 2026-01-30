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
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4 class="text-center mb-3">Tabel Statistik Pengajuan Dokumen per Pokja</h4>
                                <table id="example0" class="table table-bordered table-striped">
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

<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
console.log('Labels:', <?php echo json_encode($chart_labels); ?>);
console.log('Data:', <?php echo json_encode($chart_data); ?>);
Highcharts.chart('pokjaChart', {
    chart: {
        type: 'column'
    },
    title: {
        text: 'Grafik Pengajuan Dokumen per Pokja'
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
        color: '#17a2b8'
    }],
    credits: {
        enabled: false
    }
});
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
