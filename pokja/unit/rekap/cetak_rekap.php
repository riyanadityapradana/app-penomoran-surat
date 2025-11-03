<?php
require_once("../../../config/koneksi.php");
session_start(); // pastikan session aktif

// Ambil kode pokja dari session login
$kode_pokja_login = isset($_SESSION['kode_pokja']) ? $_SESSION['kode_pokja'] : '';

// Ambil filter dari URL (hanya status & tanggal)
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$tgl_filter = isset($_GET['tanggal_disetujui']) ? $_GET['tanggal_disetujui'] : '';

// Query dasar
$query = "SELECT p.*, j.nama_jenis, u.kode_pokja 
          FROM tb_pengajuan_dokumen p
          LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
          LEFT JOIN tb_user u ON p.id_user = u.id_user
          WHERE 1=1";

// Filter otomatis berdasarkan Pokja login
if (!empty($kode_pokja_login)) {
    $query .= " AND u.kode_pokja = '$kode_pokja_login'";
}

// Filter tambahan (status dan tanggal)
if (!empty($status_filter)) {
    $query .= " AND p.status = '$status_filter'";
}
if (!empty($tgl_filter)) {
    $query .= " AND DATE(p.tanggal_disetujui) = '$tgl_filter'";
}

$query .= " ORDER BY p.id_pengajuan DESC";
$data = mysqli_query($config, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Rekap Dokumen</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #000;
            margin: 20px;
        }
        h2, h3 {
            text-align: center;
            margin: 0;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 25px;
        }
        th, td {
            border: 1px solid #000;
            padding: 7px 10px;
            text-align: center;
            font-size: 13px;
        }
        th {
            background: #eee;
        }
        .info {
            margin-top: 20px;
            font-size: 14px;
        }
        .text-left { text-align: left; }
        .no-data {
            text-align: center;
            font-style: italic;
            padding: 15px;
        }
        @media print {
            @page { size: landscape; margin: 10mm; }
        }
    </style>
</head>
<body onload="window.print()">

    <h2>REKAP DOKUMEN PENGESAHAN</h2>
    <h3>Sistem Informasi Pengesahan Dokumen</h3>
    <hr>

    <div class="info">
        <strong>Filter:</strong><br>
        Status: <?= $status_filter ? $status_filter : 'Semua' ?><br>
        Tanggal Pengesahan: <?= $tgl_filter ? date('d-m-Y', strtotime($tgl_filter)) : 'Semua' ?><br>
        Pokja: <?= $kode_pokja_login ? $kode_pokja_login : 'Tidak Dikenali' ?><br>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal Pengajuan</th>
                <th>No Dokumen</th>
                <th>Jenis Dokumen</th>
                <th>Judul Dokumen</th>
                <th>Tanggal Disahkan / Ditolak</th>
                <th>Kode Pokja</th>
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
                            <td class='text-left'>{$row['judul_dokumen']}</td>
                            <td>" . (!empty($row['tanggal_disetujui']) ? date('d-m-Y', strtotime($row['tanggal_disetujui'])) : '-') . "</td>
                            <td>{$row['kode_pokja']}</td>
                            <td>{$row['status']}</td>
                          </tr>";
                    $no++;
                }
            } else {
                echo "<tr><td colspan='8' class='no-data'>Tidak ada data untuk filter ini.</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <div style="margin-top: 50px; text-align: right;">
        <p>Dicetak pada: <?= date('d-m-Y H:i:s'); ?></p>
    </div>

</body>
</html>
