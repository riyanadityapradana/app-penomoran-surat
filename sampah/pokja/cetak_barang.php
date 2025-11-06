<?php
require_once('../../../library/tcpdf/tcpdf.php');
require_once('../../../config/koneksi.php'); // Sesuaikan path ke file config.php

$id = $_GET['id'];
$query = mysqli_query($config, "SELECT * FROM tb_barang WHERE id_barang = '$id'");
$data = mysqli_fetch_assoc($query);

// Buat class custom TCPDF untuk header & footer
class MYPDF extends TCPDF {
    public function Header() {
        // Logo dan Kop
        $this->Image('../../../assets/upload/aa.png', 15, 3, 20); // sesuaikan path logo
        $this->SetFont('helvetica', 'B', 14);
        $this->Cell(0, 10, 'PEMERINTAH KOTA CONTOH', 0, 1, 'C');
        $this->SetFont('helvetica', '', 12);
        $this->Cell(0, 5, 'DINAS TEKNOLOGI INFORMASI DAN KOMUNIKASI', 0, 1, 'C');
        $this->SetFont('helvetica', 'I', 10);
        $this->Cell(0, 5, 'Jl. Contoh Raya No. 123, Telp. (0123) 456789', 0, 1, 'C');
        $this->Ln(5);
        $this->Line(10, 35, 200, 35); // Garis bawah kop
    }

    public function Footer() {
        // Footer bisa kosong atau isi lain sesuai kebutuhan
    }
}

$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(15, 40, 15);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

// TABEL DATA
$html = '
<h3 style="text-align:center;">DETAIL DATA BARANG</h3>
<table cellpadding="5">';

$html .= '<tr><td width="35%"><strong>Kode Barang</strong></td><td width="5%">:</td><td>'.$data['kode_barang'].'</td></tr>';
$html .= '<tr><td><strong>Nama Barang</strong></td><td>:</td><td>'.$data['nama_barang'].'</td></tr>';
$html .= '<tr><td><strong>Keterangan</strong></td><td>:</td><td>'.$data['keterangan_barang'].'</td></tr>';
$html .= '<tr><td><strong>Jenis Barang</strong></td><td>:</td><td>'.$data['jenis_barang'].'</td></tr>';
$html .= '<tr><td><strong>Status Barang</strong></td><td>:</td><td>'.$data['status_barang'].'</td></tr>';
$html .= '<tr><td><strong>Nama Pengaju</strong></td><td>:</td><td>'.$data['nama_pengajuan'].'</td></tr>';
$html .= '<tr><td><strong>Jabatan</strong></td><td>:</td><td>'.$data['jabatan_pengajuan'].' ('.$data['bidang_pengajuan'].')</td></tr>';
$html .= '<tr><td><strong>Tanggal Pengajuan</strong></td><td>:</td><td>'.date('d-m-Y', strtotime($data['tanggal_pengajuan'])).'</td></tr>';

if ($data['status_barang'] != 'Diajukan') {
    $html .= '<tr><td><strong>Tanggal Pembelian</strong></td><td>:</td><td>'.date('d-m-Y', strtotime($data['tanggal_pembelian'])).'</td></tr>';
    $html .= '<tr><td><strong>Nama Pembeli</strong></td><td>:</td><td>'.$data['nama_pembeli'].'</td></tr>';
    $html .= '<tr><td><strong>Harga Beli</strong></td><td>:</td><td>Rp '.number_format($data['harga_beli'], 0, ',', '.').'</td></tr>';
    $html .= '<tr><td><strong>Nama Supplier</strong></td><td>:</td><td>'.$data['nama_supplier'].'</td></tr>';
}

$html .= '</table><br><br>';


// TANDA TANGAN
$lokasi = 'Kota Contoh'; // Ganti sesuai instansi
$tanggal = date('d-m-Y');
$html .= '
<table width="100%">
    <tr>
        <td width="60%"></td>
        <td align="center">
            '.$lokasi.', '.$tanggal.'<br>
            Petugas / Penanggung Jawab<br><br><br><br>
            <u>_______________________</u>
        </td>
    </tr>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('detail_barang_'.$data['kode_barang'].'.pdf', 'I');
