<?php
require_once("../config/koneksi.php");
$id_user = (int) $_SESSION['id_user'];

// Hitung data statistik utama
$q_menunggu = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status = 'Menunggu Verifikasi' AND id_user = '$id_user'");
$menunggu = mysqli_num_rows($q_menunggu);

$q_disetujui = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status = 'Disetujui' AND id_user = '$id_user'");
$disetujui = mysqli_num_rows($q_disetujui);

$q_ditolak = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status = 'Ditolak' AND id_user = '$id_user'");
$ditolak = mysqli_num_rows($q_ditolak);

$q_selesai = mysqli_query($config, "SELECT * FROM tb_pengajuan_dokumen WHERE status = 'Selesai' AND id_user = '$id_user'");
$selesai = mysqli_num_rows($q_selesai);

$dokumen_pendukung = [];
$q_dokumen_pendukung = mysqli_query($config, "
    SELECT id_dokumen, nama_dokumen, deskripsi, file_word, file_pdf
    FROM tb_dokumen_pendukung
    WHERE status = 'Aktif'
      AND (
        (file_word IS NOT NULL AND file_word <> '')
        OR (file_pdf IS NOT NULL AND file_pdf <> '')
      )
    ORDER BY urutan ASC, nama_dokumen ASC
");
while ($q_dokumen_pendukung && $row_dokumen = mysqli_fetch_assoc($q_dokumen_pendukung)) {
    $dokumen_pendukung[] = [
        'id' => (int) $row_dokumen['id_dokumen'],
        'nama' => $row_dokumen['nama_dokumen'],
        'deskripsi' => $row_dokumen['deskripsi'],
        'word' => !empty($row_dokumen['file_word']),
        'pdf' => !empty($row_dokumen['file_pdf'])
    ];
}
$jumlah_dokumen_pendukung = count($dokumen_pendukung);

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

// Persentase dokumen selesai dibanding seluruh pengajuan per Pokja.
$pengesahan_labels = [];
$pengesahan_persentase = [];
$pengesahan_total = [];
$pengesahan_selesai = [];
$q_chart = mysqli_query($config, "
    SELECT
        u.kode_pokja,
        COUNT(p.id_pengajuan) AS total_pengajuan,
        SUM(CASE WHEN p.status = 'Selesai' THEN 1 ELSE 0 END) AS total_selesai
    FROM tb_user u
    LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user
    WHERE u.level = 'Pokja' AND u.kode_pokja != ''
    GROUP BY u.kode_pokja
    ORDER BY u.kode_pokja ASC
");
while ($row = mysqli_fetch_assoc($q_chart)) {
    $total_pengajuan = (int) $row['total_pengajuan'];
    $total_selesai_pokja = (int) $row['total_selesai'];

    $pengesahan_labels[] = $row['kode_pokja'];
    $pengesahan_total[] = $total_pengajuan;
    $pengesahan_selesai[] = $total_selesai_pokja;
    $pengesahan_persentase[] = $total_pengajuan > 0
        ? round(($total_selesai_pokja / $total_pengajuan) * 100, 1)
        : 0;
}

$trend_labels = [];
$trend_menunggu = [];
$trend_disetujui = [];
$trend_ditolak = [];
$trend_selesai = [];
$nama_bulan = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
for ($i = 5; $i >= 0; $i--) {
    $monthKey = date('Y-m', strtotime("-$i months"));
    $bulan_angka = (int) date('n', strtotime($monthKey . '-01'));
    $trend_labels[] = $nama_bulan[$bulan_angka] . ' ' . date('Y', strtotime($monthKey . '-01'));
    $trend_menunggu[$monthKey] = 0;
    $trend_disetujui[$monthKey] = 0;
    $trend_ditolak[$monthKey] = 0;
    $trend_selesai[$monthKey] = 0;
}
$q_trend = mysqli_query($config, "
    SELECT DATE_FORMAT(tanggal_ajuan, '%Y-%m') AS bulan, status, COUNT(*) AS total
    FROM tb_pengajuan_dokumen
    WHERE id_user = '$id_user'
      AND tanggal_ajuan >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
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

<script>
(function() {
    var dashboardChartsInitialized = false;
    var dashboardChartInitAttempts = 0;
    var dashboardCharts = [];
    var trendLabels = <?= json_encode($trend_labels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var pengesahanLabels = <?= json_encode($pengesahan_labels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var pengesahanTotal = <?= json_encode($pengesahan_total); ?>;
    var pengesahanSelesai = <?= json_encode($pengesahan_selesai); ?>;

    /* Implementasi Highcharts lama dipertahankan sementara sebagai referensi migrasi.
    function initDashboardCharts() {
        if (dashboardChartsInitialized || !window.Highcharts) {
            return;
        }

        var trendContainer = document.getElementById('trendChart');
        var percentageContainer = document.getElementById('pengesahanPercentageChart');
        if (!trendContainer || !percentageContainer) {
            if (dashboardChartInitAttempts < 20) {
                dashboardChartInitAttempts++;
                window.setTimeout(initDashboardCharts, 100);
            }
            return;
        }

        dashboardChartsInitialized = true;
        Highcharts.setOptions({
            lang: {
                decimalPoint: ',',
                thousandsSep: '.',
                noData: 'Belum ada data'
            }
        });

        dashboardCharts.push(new Highcharts.Chart({
            chart: {
                renderTo: 'trendChart',
                type: 'spline',
                backgroundColor: 'transparent',
                animation: { duration: 900 },
                spacing: [18, 14, 10, 10],
                style: { fontFamily: 'Arial, sans-serif' }
            },
            title: { text: null },
            xAxis: {
                categories: trendLabels,
                lineColor: '#dbe3ea',
                tickColor: '#dbe3ea',
                tickLength: 0,
                labels: { style: { color: '#667085', fontSize: '11px' } }
            },
            yAxis: {
                min: 0,
                allowDecimals: false,
                gridLineColor: '#e8edf3',
                gridLineDashStyle: 'ShortDash',
                title: {
                    text: 'Jumlah Dokumen',
                    style: { color: '#667085', fontWeight: 'normal' }
                },
                labels: { style: { color: '#667085' } }
            },
            legend: {
                align: 'center',
                verticalAlign: 'bottom',
                borderWidth: 0,
                itemDistance: 16,
                itemStyle: { color: '#344054', fontSize: '11px', fontWeight: '600' },
                itemHoverStyle: { color: '#111827' }
            },
            tooltip: {
                shared: true,
                useHTML: true,
                borderWidth: 0,
                borderRadius: 7,
                backgroundColor: 'rgba(17, 24, 39, .94)',
                shadow: true,
                style: { color: '#ffffff' },
                formatter: function() {
                    var html = '<b>' + this.x + '</b><br>';
                    this.points.forEach(function(point) {
                        html += '<span style="color:' + point.color + '">●</span> ' + point.series.name + ': <b>' + point.y + '</b><br>';
                    });
                    return html;
                }
            },
            plotOptions: {
                series: {
                    animation: { duration: 1200 },
                    lineWidth: 3,
                    marker: {
                        enabled: true,
                        radius: 4,
                        lineWidth: 2,
                        lineColor: '#ffffff',
                        states: { hover: { enabled: true, radius: 7, lineWidth: 2 } }
                    },
                    states: { hover: { lineWidth: 4 } }
                }
            },
            series: [
                { name: 'Menunggu', data: <?= json_encode(array_values($trend_menunggu)); ?>, color: '#f59e0b' },
                { name: 'Disetujui', data: <?= json_encode(array_values($trend_disetujui)); ?>, color: '#16a34a' },
                { name: 'Ditolak', data: <?= json_encode(array_values($trend_ditolak)); ?>, color: '#e11d48' },
                { name: 'Selesai', data: <?= json_encode(array_values($trend_selesai)); ?>, color: '#2563eb' }
            ],
            credits: { enabled: false }
        }));

        dashboardCharts.push(new Highcharts.Chart({
            chart: {
                renderTo: 'pengesahanPercentageChart',
                type: 'column',
                backgroundColor: 'transparent',
                animation: { duration: 900 },
                spacing: [18, 14, 10, 10],
                style: { fontFamily: 'Arial, sans-serif' }
            },
            colors: ['#0f766e', '#2563eb', '#f59e0b', '#7c3aed', '#e11d48', '#0891b2', '#65a30d'],
            title: { text: null },
            xAxis: {
                categories: pengesahanLabels,
                lineColor: '#dbe3ea',
                tickLength: 0,
                labels: {
                    rotation: -38,
                    align: 'right',
                    style: { color: '#667085', fontSize: '10px' }
                }
            },
            yAxis: {
                min: 0,
                max: 100,
                tickInterval: 25,
                gridLineColor: '#e8edf3',
                gridLineDashStyle: 'ShortDash',
                title: {
                    text: 'Persentase Selesai',
                    style: { color: '#667085', fontWeight: 'normal' }
                },
                labels: {
                    format: '{value}%',
                    style: { color: '#667085' }
                }
            },
            legend: { enabled: false },
            tooltip: {
                useHTML: true,
                borderWidth: 0,
                borderRadius: 7,
                backgroundColor: 'rgba(17, 24, 39, .94)',
                shadow: true,
                style: { color: '#ffffff' },
                formatter: function() {
                    var index = this.point.index;
                    return '<b>' + this.x + '</b><br>' +
                        'Selesai: <b>' + pengesahanSelesai[index] + '</b> dari <b>' + pengesahanTotal[index] + '</b><br>' +
                        'Persentase: <b>' + this.y.toFixed(1).replace('.', ',') + '%</b>';
                }
            },
            plotOptions: {
                column: {
                    animation: { duration: 1400 },
                    colorByPoint: true,
                    borderWidth: 0,
                    borderRadius: 4,
                    pointPadding: .08,
                    groupPadding: .08,
                    shadow: { color: 'rgba(15, 23, 42, .15)', offsetX: 0, offsetY: 5, opacity: .18, width: 8 },
                    states: { hover: { brightness: .08 } },
                    dataLabels: {
                        enabled: true,
                        formatter: function() {
                            return this.y > 0 ? this.y.toFixed(1).replace('.0', '') + '%' : null;
                        },
                        style: { color: '#344054', fontSize: '10px', fontWeight: '600', textShadow: 'none' }
                    }
                }
            },
            series: [{
                name: 'Pengesahan',
                data: <?= json_encode($pengesahan_persentase); ?>
            }],
            credits: { enabled: false }
        }));
    }
    */

    function initChartJsDashboard() {
        if (dashboardChartsInitialized) {
            return;
        }

        var trendCanvas = document.getElementById('trendChart');
        var percentageCanvas = document.getElementById('pengesahanPercentageChart');
        if (!window.Chart || !trendCanvas || !percentageCanvas) {
            if (dashboardChartInitAttempts < 40) {
                dashboardChartInitAttempts++;
                window.setTimeout(initChartJsDashboard, 100);
            }
            return;
        }

        dashboardChartsInitialized = true;

        var chartFont = {
            family: 'Arial, sans-serif',
            size: 11
        };
        var tooltipOptions = {
            backgroundColor: 'rgba(17, 24, 39, .94)',
            titleColor: '#ffffff',
            bodyColor: '#ffffff',
            borderWidth: 0,
            cornerRadius: 7,
            padding: 11,
            displayColors: true
        };

        dashboardCharts.push(new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [
                    { label: 'Menunggu', data: <?= json_encode(array_values($trend_menunggu)); ?>, borderColor: '#f59e0b', backgroundColor: 'rgba(245, 158, 11, .12)' },
                    { label: 'Disetujui', data: <?= json_encode(array_values($trend_disetujui)); ?>, borderColor: '#16a34a', backgroundColor: 'rgba(22, 163, 74, .12)' },
                    { label: 'Ditolak', data: <?= json_encode(array_values($trend_ditolak)); ?>, borderColor: '#e11d48', backgroundColor: 'rgba(225, 29, 72, .12)' },
                    { label: 'Selesai', data: <?= json_encode(array_values($trend_selesai)); ?>, borderColor: '#2563eb', backgroundColor: 'rgba(37, 99, 235, .12)' }
                ].map(function(dataset) {
                    dataset.borderWidth = 3;
                    dataset.fill = false;
                    dataset.tension = .38;
                    dataset.cubicInterpolationMode = 'monotone';
                    dataset.pointRadius = 4;
                    dataset.pointHoverRadius = 7;
                    dataset.pointBorderWidth = 2;
                    dataset.pointBorderColor = '#ffffff';
                    return dataset;
                })
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1300,
                    easing: 'easeOutQuart'
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#344054',
                            font: chartFont,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            padding: 16
                        }
                    },
                    tooltip: Object.assign({}, tooltipOptions, {
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.dataset.label + ': ' + context.parsed.y + ' dokumen';
                            }
                        }
                    })
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { color: '#dbe3ea' },
                        ticks: { color: '#667085', font: chartFont }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#e8edf3', borderDash: [4, 4] },
                        border: { display: false },
                        title: { display: true, text: 'Jumlah Dokumen', color: '#667085', font: chartFont },
                        ticks: { color: '#667085', precision: 0, font: chartFont }
                    }
                }
            }
        }));

        var percentageColors = pengesahanLabels.map(function(label, index) {
            var palette = ['#0f766e', '#2563eb', '#f59e0b', '#7c3aed', '#e11d48', '#0891b2', '#65a30d'];
            return palette[index % palette.length];
        });
        var percentageLabelPlugin = {
            id: 'percentageValueLabels',
            afterDatasetsDraw: function(chart) {
                var ctx = chart.ctx;
                var meta = chart.getDatasetMeta(0);
                ctx.save();
                ctx.fillStyle = '#344054';
                ctx.font = '600 10px Arial';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'bottom';
                meta.data.forEach(function(bar, index) {
                    var value = chart.data.datasets[0].data[index];
                    if (value > 0) {
                        ctx.fillText(String(value).replace('.0', '') + '%', bar.x, bar.y - 5);
                    }
                });
                ctx.restore();
            }
        };

        dashboardCharts.push(new Chart(percentageCanvas, {
            type: 'bar',
            data: {
                labels: pengesahanLabels,
                datasets: [{
                    label: 'Pengesahan',
                    data: <?= json_encode($pengesahan_persentase); ?>,
                    backgroundColor: percentageColors,
                    hoverBackgroundColor: percentageColors,
                    borderWidth: 0,
                    borderRadius: 6,
                    borderSkipped: false,
                    maxBarThickness: 42
                }]
            },
            plugins: [percentageLabelPlugin],
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 1400,
                    easing: 'easeOutQuart',
                    delay: function(context) {
                        return context.type === 'data' ? context.dataIndex * 35 : 0;
                    }
                },
                interaction: {
                    mode: 'nearest',
                    intersect: true
                },
                plugins: {
                    legend: { display: false },
                    tooltip: Object.assign({}, tooltipOptions, {
                        callbacks: {
                            label: function(context) {
                                return ' Persentase: ' + Number(context.parsed.y).toFixed(1).replace('.', ',') + '%';
                            },
                            afterLabel: function(context) {
                                var index = context.dataIndex;
                                return ' Selesai: ' + pengesahanSelesai[index] + ' dari ' + pengesahanTotal[index] + ' pengajuan';
                            }
                        }
                    })
                },
                scales: {
                    x: {
                        grid: { display: false },
                        border: { color: '#dbe3ea' },
                        ticks: {
                            color: '#667085',
                            font: { family: 'Arial, sans-serif', size: 10 },
                            maxRotation: 45,
                            minRotation: 35,
                            autoSkip: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 100,
                        grid: { color: '#e8edf3', borderDash: [4, 4] },
                        border: { display: false },
                        title: { display: true, text: 'Persentase Selesai', color: '#667085', font: chartFont },
                        ticks: {
                            stepSize: 25,
                            color: '#667085',
                            font: chartFont,
                            callback: function(value) { return value + '%'; }
                        }
                    }
                }
            }
        }));
    }

    function reflowDashboardCharts() {
        window.setTimeout(function() {
            dashboardCharts.forEach(function(chart) {
                if (chart && chart.resize) {
                    chart.resize();
                }
            });
        }, 320);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initChartJsDashboard);
    } else {
        initChartJsDashboard();
    }

    window.addEventListener('resize', reflowDashboardCharts);
    if (window.jQuery) {
        jQuery(document).on('collapsed.lte.pushmenu shown.lte.pushmenu', reflowDashboardCharts);
    }
})();
</script>

<style>
    .dashboard-chart-grid {
        margin-top: 1.5rem;
    }

    .dashboard-chart-panel {
        height: 100%;
        overflow: hidden;
        border: 1px solid #e4eaf0;
        border-radius: 8px;
        background: #ffffff;
        box-shadow: 0 14px 34px rgba(15, 23, 42, .08);
        animation: chartPanelEnter .65s ease-out both;
        transition: transform .25s ease, box-shadow .25s ease;
    }

    .dashboard-chart-grid > div:nth-child(2) .dashboard-chart-panel {
        animation-delay: .12s;
    }

    .dashboard-chart-panel:hover {
        transform: translateY(-3px);
        box-shadow: 0 18px 40px rgba(15, 23, 42, .12);
    }

    .dashboard-chart-header {
        display: flex;
        align-items: center;
        min-height: 58px;
        padding: .9rem 1.1rem;
        border-bottom: 1px solid #edf1f5;
    }

    .dashboard-chart-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 34px;
        height: 34px;
        flex: 0 0 34px;
        margin-right: .7rem;
        border-radius: 7px;
        color: #ffffff;
    }

    .dashboard-chart-icon.trend {
        background: #0f766e;
    }

    .dashboard-chart-icon.percentage {
        background: #2563eb;
    }

    .dashboard-chart-title {
        margin: 0;
        color: #182235;
        font-size: 1rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .dashboard-chart-subtitle {
        display: block;
        margin-top: .12rem;
        color: #748094;
        font-size: .78rem;
        font-weight: 400;
    }

    .dashboard-chart-body {
        padding: .5rem .75rem .75rem;
    }

    .dashboard-chart-canvas {
        width: 100%;
        height: 360px;
    }

    @keyframes chartPanelEnter {
        from {
            opacity: 0;
            transform: translateY(14px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media (max-width: 991.98px) {
        .dashboard-chart-grid > div + div {
            margin-top: 1rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .dashboard-chart-panel {
            animation: none;
            transition: none;
        }
    }
</style>

<!-- Main content -->
<section class="content">
    <div class="container-fluid">

        <!-- Statistik Card -->
        <div class="row">
            <div class="col-lg col-md-4 col-6">
                <div class="small-box bg-warning">
                    <div class="inner">
                        <h3><?= $menunggu ?></h3>
                        <p>Menunggu Verifikasi</p>
                    </div>
                    <div class="icon"><i class="fas fa-hourglass-half"></i></div>
                    <a href="main_pokja.php?unit=pengajuan" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg col-md-4 col-6">
                <div class="small-box bg-success">
                    <div class="inner">
                        <h3><?= $disetujui ?></h3>
                        <p>Disetujui</p>
                    </div>
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <a href="main_pokja.php?unit=pengajuan" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg col-md-4 col-6">
                <div class="small-box bg-danger">
                    <div class="inner">
                        <h3><?= $ditolak ?></h3>
                        <p>Ditolak</p>
                    </div>
                    <div class="icon"><i class="fas fa-times-circle"></i></div>
                    <a href="main_pokja.php?unit=pengajuan" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg col-md-4 col-6">
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3><?= $selesai ?></h3>
                        <p>Selesai</p>
                    </div>
                    <div class="icon"><i class="fas fa-file-alt"></i></div>
                    <a href="main_pokja.php?unit=pengesahan" class="small-box-footer">Lihat Detail <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>

            <div class="col-lg col-md-4 col-6">
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3><?= $jumlah_dokumen_pendukung ?></h3>
                        <p>Dokumen Pendukung</p>
                    </div>
                    <div class="icon"><i class="fas fa-folder-open"></i></div>
                    <a href="#" class="small-box-footer" data-toggle="modal" data-target="#modalDokumenPendukung">Buka Dokumen <i class="fas fa-arrow-circle-right"></i></a>
                </div>
            </div>
        </div>
        <!-- /.row statistik -->

        <div class="row dashboard-chart-grid">
            <div class="col-lg-6">
                <section class="dashboard-chart-panel" aria-labelledby="trendChartTitle">
                    <div class="dashboard-chart-header">
                        <span class="dashboard-chart-icon trend"><i class="fas fa-chart-line"></i></span>
                        <h3 class="dashboard-chart-title" id="trendChartTitle">
                            Tren Pengajuan Saya
                            <span class="dashboard-chart-subtitle">Pergerakan status dokumen dalam 6 bulan terakhir</span>
                        </h3>
                    </div>
                    <div class="dashboard-chart-body">
                        <canvas id="trendChart" class="dashboard-chart-canvas" aria-label="Grafik tren pengajuan saya"></canvas>
                    </div>
                </section>
            </div>
            <div class="col-lg-6">
                <section class="dashboard-chart-panel" aria-labelledby="pengesahanPercentageChartTitle">
                    <div class="dashboard-chart-header">
                        <span class="dashboard-chart-icon percentage"><i class="fas fa-chart-bar"></i></span>
                        <h3 class="dashboard-chart-title" id="pengesahanPercentageChartTitle">
                            Persentase Pengesahan Dokumen Semua Pokja
                            <span class="dashboard-chart-subtitle">Perbandingan dokumen selesai terhadap seluruh pengajuan</span>
                        </h3>
                    </div>
                    <div class="dashboard-chart-body">
                        <canvas id="pengesahanPercentageChart" class="dashboard-chart-canvas" aria-label="Grafik persentase pengesahan semua Pokja"></canvas>
                    </div>
                </section>
            </div>
        </div>

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
                                    <tr><td colspan="3" class="text-center"><em>Belum ada data diterima</em></td></tr>
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
                                    <tr><td colspan="3" class="text-center"><em>Belum ada data ditolak</em></td></tr>
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

<div class="modal fade" id="modalDokumenPendukung" tabindex="-1" role="dialog" aria-labelledby="modalDokumenPendukungLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalDokumenPendukungLabel"><i class="fas fa-folder-open mr-2"></i>Dokumen Pendukung</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <?php if ($jumlah_dokumen_pendukung > 0): ?>
                    <div class="form-group mb-3">
                        <label for="pilihDokumenPendukung">Pilih dokumen</label>
                        <select id="pilihDokumenPendukung" class="form-control">
                            <option value="">-- Pilih Dokumen --</option>
                            <?php foreach ($dokumen_pendukung as $dokumen): ?>
                                <option value="<?= $dokumen['id']; ?>"><?= htmlspecialchars($dokumen['nama']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="detailDokumenPendukung" class="border rounded p-3 d-none" aria-live="polite">
                        <h6 id="namaDokumenPendukung" class="font-weight-bold mb-1"></h6>
                        <p id="deskripsiDokumenPendukung" class="text-muted mb-3"></p>
                        <div class="d-flex flex-wrap">
                            <a id="downloadDokumenWord" href="#" class="btn btn-primary mr-2 mb-2 d-none">
                                <i class="fas fa-file-word mr-1"></i> Download Word
                            </a>
                            <a id="downloadDokumenPdf" href="#" class="btn btn-danger mb-2 d-none">
                                <i class="fas fa-file-pdf mr-1"></i> Download PDF
                            </a>
                        </div>
                    </div>
                    <div id="petunjukDokumenPendukung" class="text-center text-muted py-3">
                        <i class="fas fa-hand-pointer fa-2x mb-2 d-block"></i>
                        Pilih dokumen untuk melihat format yang tersedia.
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-folder-open fa-3x mb-3 d-block"></i>
                        Belum ada dokumen pendukung yang tersedia.
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<?php if ($jumlah_dokumen_pendukung > 0): ?>
<script>
(function() {
    var documents = <?= json_encode($dokumen_pendukung, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    var select = document.getElementById('pilihDokumenPendukung');
    var detail = document.getElementById('detailDokumenPendukung');
    var hint = document.getElementById('petunjukDokumenPendukung');
    var name = document.getElementById('namaDokumenPendukung');
    var description = document.getElementById('deskripsiDokumenPendukung');
    var wordButton = document.getElementById('downloadDokumenWord');
    var pdfButton = document.getElementById('downloadDokumenPdf');

    if (!select) return;

    select.addEventListener('change', function() {
        var selectedId = parseInt(this.value, 10);
        var selected = documents.find(function(documentItem) {
            return documentItem.id === selectedId;
        });

        if (!selected) {
            detail.classList.add('d-none');
            hint.classList.remove('d-none');
            return;
        }

        name.textContent = selected.nama;
        description.textContent = selected.deskripsi || 'Tidak ada deskripsi tambahan.';
        wordButton.classList.toggle('d-none', !selected.word);
        pdfButton.classList.toggle('d-none', !selected.pdf);
        wordButton.href = selected.word ? 'download_dokumen.php?id=' + selected.id + '&format=word' : '#';
        pdfButton.href = selected.pdf ? 'download_dokumen.php?id=' + selected.id + '&format=pdf' : '#';
        hint.classList.add('d-none');
        detail.classList.remove('d-none');
    });

    window.addEventListener('load', function() {
        if (!window.jQuery) return;
        window.jQuery('#modalDokumenPendukung').on('hidden.bs.modal', function() {
            select.value = '';
            detail.classList.add('d-none');
            hint.classList.remove('d-none');
        });
    });
})();
</script>
<?php endif; ?>
