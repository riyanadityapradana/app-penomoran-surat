<?php
require_once("../config/koneksi.php");
require_once("../config/notifikasi.php");

if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
    $query = mysqli_query($config, "
        SELECT p.*, j.nama_jenis, j.kode_jenis, u.nama_lengkap, u.kode_pokja, u.email_user
        FROM tb_pengajuan_dokumen p
        LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
        LEFT JOIN tb_user u ON p.id_user = u.id_user
        WHERE p.id_pengajuan = '$id_pengajuan'
    ");
    $data = mysqli_fetch_assoc($query);

    if (!$data) {
        header('Location: main_admin.php?unit=pengajuan&err=Data pengajuan tidak ditemukan!');
        exit;
    }
} else {
    header('Location: main_admin.php?unit=pengajuan&err=ID Pengajuan tidak ditemukan!');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tolak'])) {
    $catatan_admin = mysqli_real_escape_string($config, $_POST['catatan_admin']);
    $tanggal_disetujui = date('Y-m-d H:i:s');

    $update = mysqli_query($config, "
        UPDATE tb_pengajuan_dokumen SET
            status = 'Ditolak',
            tanggal_disetujui = '$tanggal_disetujui',
            catatan_admin = '$catatan_admin'
        WHERE id_pengajuan = '$id_pengajuan'
    ");

    if ($update) {
        $pokjaLink = app_base_url() . '/pokja/main_pokja.php?unit=pengajuan';
        $emailSubject = 'Pengajuan Dokumen Ditolak - ' . $data['judul_dokumen'];
        $emailBody = "
            <p>Halo <b>" . htmlspecialchars($data['nama_lengkap']) . "</b>,</p>
            <p>Pengajuan dokumen Anda saat ini <b>ditolak</b> oleh admin.</p>
            <table border='0' cellpadding='6' cellspacing='0' style='border-collapse:collapse;'>
                <tr><td><b>Kode Pokja</b></td><td>: " . htmlspecialchars($data['kode_pokja']) . "</td></tr>
                <tr><td><b>Judul Dokumen</b></td><td>: " . htmlspecialchars($data['judul_dokumen']) . "</td></tr>
                <tr><td><b>Jenis Dokumen</b></td><td>: " . htmlspecialchars($data['nama_jenis']) . "</td></tr>
                <tr><td><b>Tanggal Status</b></td><td>: " . htmlspecialchars(app_format_datetime_id($tanggal_disetujui)) . "</td></tr>
                <tr><td><b>Catatan Admin</b></td><td>: " . nl2br(htmlspecialchars($catatan_admin)) . "</td></tr>
            </table>
            <p>Silakan login ke aplikasi untuk meninjau catatan admin.</p>
            <p><a href='" . htmlspecialchars($pokjaLink) . "'>Buka halaman pengajuan</a></p>
        ";
        if (!empty($data['email_user'])) {
            app_send_email($data['email_user'], $emailSubject, $emailBody);
        }

        $telegramMessage = "<b>Pengajuan Dokumen Ditolak</b>\n"
            . "Pokja: <b>" . htmlspecialchars($data['kode_pokja']) . "</b>\n"
            . "Judul: <b>" . htmlspecialchars($data['judul_dokumen']) . "</b>\n"
            . "Jenis: <b>" . htmlspecialchars($data['nama_jenis']) . "</b>\n"
            . "Status: <b>Ditolak</b>\n"
            . "Catatan: <b>" . htmlspecialchars($catatan_admin) . "</b>\n"
            . "Link: " . htmlspecialchars($pokjaLink);
        app_send_telegram($telegramMessage);

        header('Location: main_admin.php?unit=pengajuan&msg=Pengajuan berhasil ditolak, email dan Telegram sudah diproses!');
        exit;
    } else {
        echo "<script>alert('Gagal menolak pengajuan: " . mysqli_error($config) . "');</script>";
    }
}
?>

<section class="content-header">
    <h1>Penolakan Pengajuan Dokumen</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header bg-danger">
                <h3 class="card-title">Detail Pengajuan dari Pokja</h3>
            </div>

            <form method="post">
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Nama Dokumen</label>
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
                                <label>Tanggal Diajukan</label>
                                <input type="text" class="form-control" value="<?= date('d-m-Y', strtotime($data['tanggal_ajuan'])); ?>" readonly>
                            </div>

                            <div class="form-group">
                                <label>Catatan Admin</label>
                                <textarea name="catatan_admin" class="form-control" rows="4" placeholder="Tambahkan catatan pemeriksaan..." required></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <a class="btn btn-app bg-warning float-left" href="main_admin.php?unit=pengajuan">
                        <i class="fas fa-reply"></i> Kembali
                    </a>

                    <button type="submit" name="tolak" class="btn btn-app bg-danger float-right">
                        <i class="fas fa-times"></i> Tolak Pengajuan
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
