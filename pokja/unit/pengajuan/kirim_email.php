<?php
require_once("../config/koneksi.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../library/PHPMailer/src/Exception.php';
require '../library/PHPMailer/src/PHPMailer.php';
require '../library/PHPMailer/src/SMTP.php';

// Ambil data pengajuan berdasarkan ID
if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
    $query = mysqli_query($config, "
        SELECT p.*, j.nama_jenis, u.nama_lengkap, u.email_user, u.kode_pokja 
        FROM tb_pengajuan_dokumen p
        LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
        LEFT JOIN tb_user u ON p.id_user = u.id_user
        WHERE p.id_pengajuan = '$id_pengajuan'
    ");
    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        header('Location: main_pokja.php?unit=pengajuan&err=Data pengajuan tidak ditemukan!');
        exit;
    }
} else {
    header('Location: main_pokja.php?unit=pengajuan&err=ID pengajuan tidak ditemukan!');
    exit;
}

$notif = "";

// --- PROSES KIRIM EMAIL --- //
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kirim_email'])) {
    $email_admin = $_POST['email_admin'];

    // Otomatis buat judul dan pesan
    $judul = "Pengajuan Dokumen Baru dari Pokja {$data['kode_pokja']}";
    $pesan = "
        <p>Halo <b>Admin</b>,</p>
        <p>Ada <b>pengajuan dokumen baru</b> dari Pokja <b>{$data['kode_pokja']}</b> yang membutuhkan verifikasi Anda.</p>
        <p>Berikut ringkasan pengajuannya:</p>
        <table border='0' cellspacing='0' cellpadding='6' style='border-collapse: collapse;'>
            <tr><td><b>Judul Dokumen</b></td><td>: {$data['judul_dokumen']}</td></tr>
            <tr><td><b>Jenis Dokumen</b></td><td>: {$data['nama_jenis']}</td></tr>
            <tr><td><b>Diajukan Oleh</b></td><td>: {$data['nama_lengkap']}</td></tr>
            <tr><td><b>Tanggal Pengajuan</b></td><td>: " . date('d F Y', strtotime($data['tanggal_ajuan'])) . "</td></tr>
        </table>
        <br>
        <p>Silakan klik tombol di bawah ini untuk melihat detail pengajuan:</p>
        <p>
            <a href='http://192.168.1.108/akreditas/'
               style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>
               🔍 Lihat Detail Pengajuan
            </a>
        </p>
        <br>
        <hr>
        <p style='font-size:12px;color:#555;'>Email ini dikirim otomatis oleh Sistem Pengajuan Dokumen Pokja. 
        Mohon tidak membalas langsung ke email ini.</p>
    ";

    $mail = new PHPMailer(true);
    try {
        // Konfigurasi Gmail SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sekretariatrspelitainsani@gmail.com'; // Email kamu
        $mail->Password   = 'wino fwge gpyg ajny'; // App password kamu
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Pengirim & penerima
        $mail->setFrom('sekretariatrspelitainsani@gmail.com', 'Sistem Pengajuan Dokumen');
        $mail->addAddress($email_admin);

        // Konten email
        $mail->isHTML(true);
        $mail->Subject = $judul;
        $mail->Body    = $pesan;

        $mail->send();
        header('Location: main_pokja.php?unit=pengajuan&msg=Email otomatis berhasil dikirim ke ' . urlencode($email_admin) . '!');
        exit;
    } catch (Exception $e) {
        header('Location: main_pokja.php?unit=pengajuan&err=Gagal mengirim email. Error: ' . urlencode($mail->ErrorInfo));
        exit;
    }
}
?>
