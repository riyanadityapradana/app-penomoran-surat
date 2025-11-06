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
            // <a href='https://192.168.1.66/main_admin.php?unit=detail_pengajuan&id_pengajuan={$data['id_pengajuan']}'
            <a href='http://192.168.1.66/akreditas/'
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

<section class="content-header">
    <h1>Kirim Email Notifikasi Pengajuan</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header bg-info">
                <h3 class="card-title">Detail Pengajuan</h3>
            </div>
            
            <form method="post">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12">

                            <div class="form-group">
                                <label>Judul Dokumen</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($data['judul_dokumen']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Jenis Dokumen</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($data['nama_jenis']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Kode Pokja</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($data['kode_pokja']); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Tanggal Pengajuan</label>
                                <input type="text" class="form-control" value="<?= date('d-m-Y', strtotime($data['tanggal_ajuan'])); ?>" readonly>
                            </div>

                            <hr>
                            <h5><b>Form Kirim Email</b></h5>

                            <div class="form-group">
                                <label>Pilih Admin Tujuan</label>
                                <select name="email_admin" class="form-control" required>
                                    <option value="">-- Pilih Admin --</option>
                                    <?php
                                    $admins = mysqli_query($config, "SELECT email_user, nama_lengkap FROM tb_user WHERE level='Admin'");
                                    while ($row = mysqli_fetch_assoc($admins)) {
                                        echo "<option value='{$row['email_user']}'>{$row['nama_lengkap']} ({$row['email_user']})</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <a class="btn btn-app bg-warning float-left" href="main_pokja.php?unit=pengajuan">
                        <i class="fas fa-reply"></i> Kembali
                    </a>
                    <button type="submit" name="kirim_email" class="btn btn-app bg-success float-right">
                        <i class="fas fa-paper-plane"></i> Kirim Email Otomatis
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
