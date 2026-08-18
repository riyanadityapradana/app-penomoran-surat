<?php
require_once("../config/koneksi.php");
require_once("../config/notifikasi.php");
require_once("../config/wa_helper.php");

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verifikasi'])) {
    $catatan_admin = mysqli_real_escape_string($config, $_POST['catatan_admin']);
    $tanggal_disetujui = date('Y-m-d H:i:s');

    $jenis_dokumen = strtoupper(trim((string) $data['kode_jenis']));
    $kode_pokja = strtoupper(trim((string) $data['kode_pokja']));

    if (!preg_match('/^[A-Z0-9_-]{1,10}$/', $jenis_dokumen) || !preg_match('/^[A-Z0-9_-]{1,20}$/', $kode_pokja)) {
        header('Location: main_admin.php?unit=pengajuan&err=Kode jenis dokumen atau kode Pokja tidak valid!');
        exit;
    }
    $bulan_romawi = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI', 7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'];
    $bulan = $bulan_romawi[(int) date('n')];
    $tahun = date('Y');
    $tahun_dua_digit = date('y');

    if ($jenis_dokumen == 'SK') {
        $nomor_pattern = "%/SK/DIR/%/$tahun_dua_digit-A0";
        $q_nomor = mysqli_query($config, "
            SELECT MAX(CAST(SUBSTRING_INDEX(p.nomor_surat, '/', 1) AS UNSIGNED)) AS last_urutan
            FROM tb_pengajuan_dokumen p
            LEFT JOIN tb_user u ON p.id_user = u.id_user
            LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
            WHERE u.kode_pokja = '$kode_pokja'
              AND UPPER(j.kode_jenis) = 'SK'
              AND p.nomor_surat IS NOT NULL
              AND p.nomor_surat != ''
              AND p.nomor_surat LIKE '$nomor_pattern'
        ");

        $row_nomor = mysqli_fetch_assoc($q_nomor);
        $last_urutan = isset($row_nomor['last_urutan']) ? (int) $row_nomor['last_urutan'] : 0;
        $urutan = str_pad($last_urutan + 1, 3, '0', STR_PAD_LEFT);

        $nomor_surat = "$urutan/SK/DIR/$bulan/$tahun_dua_digit-A0";
    } else {
        // Semua jenis selain SK, termasuk Dokumen Bukti (DB), memiliki seri nomor sendiri per Pokja dan tahun.
        $nomor_pattern = "A/%/$jenis_dokumen/$kode_pokja/%/$tahun";
        $q_nomor = mysqli_query($config, "
            SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(p.nomor_surat, '/', 2), '/', -1) AS UNSIGNED)) AS last_urutan
            FROM tb_pengajuan_dokumen p
            LEFT JOIN tb_user u ON p.id_user = u.id_user
            LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
            WHERE u.kode_pokja = '$kode_pokja'
              AND UPPER(j.kode_jenis) = '$jenis_dokumen'
              AND p.nomor_surat IS NOT NULL
              AND p.nomor_surat != ''
              AND p.nomor_surat LIKE '$nomor_pattern'
        ");

        $row_nomor = mysqli_fetch_assoc($q_nomor);
        $last_urutan = isset($row_nomor['last_urutan']) ? (int) $row_nomor['last_urutan'] : 0;
        $urutan = str_pad($last_urutan + 1, 3, '0', STR_PAD_LEFT);

        $nomor_surat = "A/$urutan/$jenis_dokumen/$kode_pokja/$bulan/$tahun";
    }

    $update = mysqli_query($config, "
        UPDATE tb_pengajuan_dokumen SET
            status = 'Disetujui',
            tanggal_disetujui = '$tanggal_disetujui',
            catatan_admin = '$catatan_admin',
            nomor_surat = '$nomor_surat'
        WHERE id_pengajuan = '$id_pengajuan'
    ");

    if ($update) {
        $pokjaLink = app_base_url() . '/pokja/main_pokja.php?unit=pengajuan';
        $emailSubject = 'Pengajuan Dokumen Disetujui - ' . $data['judul_dokumen'];
        $emailBody = "
            <p>Halo <b>" . htmlspecialchars($data['nama_lengkap']) . "</b>,</p>
            <p>Pengajuan dokumen Anda telah <b>disetujui</b> oleh admin.</p>
            <table border='0' cellpadding='6' cellspacing='0' style='border-collapse:collapse;'>
                <tr><td><b>Kode Pokja</b></td><td>: " . htmlspecialchars($data['kode_pokja']) . "</td></tr>
                <tr><td><b>Judul Dokumen</b></td><td>: " . htmlspecialchars($data['judul_dokumen']) . "</td></tr>
                <tr><td><b>Jenis Dokumen</b></td><td>: " . htmlspecialchars($data['nama_jenis']) . "</td></tr>
                <tr><td><b>Nomor Surat</b></td><td>: " . htmlspecialchars($nomor_surat) . "</td></tr>
                <tr><td><b>Tanggal Disetujui</b></td><td>: " . htmlspecialchars(app_format_datetime_id($tanggal_disetujui)) . "</td></tr>
                <tr><td><b>Catatan Admin</b></td><td>: " . nl2br(htmlspecialchars($catatan_admin)) . "</td></tr>
            </table>
            <p>Silakan login ke aplikasi untuk melihat detail pengajuan.</p>
            <p><a href='" . htmlspecialchars($pokjaLink) . "'>Buka halaman pengajuan</a></p>
        ";
        if (!empty($data['email_user'])) {
            app_send_email($data['email_user'], $emailSubject, $emailBody);
        }

        $telegramMessage = "<b>Pengajuan Dokumen Disetujui</b>\n"
            . "Pokja: <b>" . htmlspecialchars($data['kode_pokja']) . "</b>\n"
            . "Judul: <b>" . htmlspecialchars($data['judul_dokumen']) . "</b>\n"
            . "Jenis: <b>" . htmlspecialchars($data['nama_jenis']) . "</b>\n"
            . "Nomor Surat: <b>" . htmlspecialchars($nomor_surat) . "</b>\n"
            . "Status: <b>Disetujui</b>\n"
            . "Link: " . htmlspecialchars($pokjaLink);
        app_send_telegram($telegramMessage);

        $waUrl = '';
        if (!empty($data['no_tlp'])) {
            $waUrl = app_wa_url($data['no_tlp'], app_wa_verifikasi_message($data, $nomor_surat));
        }
        $redirectUrl = 'main_admin.php?unit=pengajuan&msg=Pengajuan berhasil diverifikasi, email, Telegram, dan WhatsApp sudah diproses!';
        echo "<script>";
        if ($waUrl !== '') {
            echo "var waWindow = window.open('', 'waNotifyWindow');";
            echo "if (waWindow) { waWindow.location.href = " . json_encode($waUrl) . "; }";
        }
        echo "window.location.href = " . json_encode($redirectUrl) . ";";
        echo "</script>";
        exit;
    } else {
        echo "<script>alert('Gagal memverifikasi pengajuan: " . mysqli_error($config) . "');</script>";
    }
}
?>

<section class="content-header">
    <h1>Verifikasi Pengajuan Dokumen</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header bg-info">
                <h3 class="card-title">Detail Pengajuan dari Pokja</h3>
            </div>

            <form method="post" onsubmit="return prepareWaSubmit('Setujui pengajuan ini dan buat nomor surat sekarang? WhatsApp Web akan dibuka otomatis jika nomor telepon tersedia.');">
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

                    <button type="submit" name="verifikasi" class="btn btn-app bg-success float-right">
                        <i class="fas fa-check"></i> Setujui & Buat Nomor Surat
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
function prepareWaSubmit(message) {
    if (!confirm(message)) {
        return false;
    }

    window.open('about:blank', 'waNotifyWindow');
    return true;
}
</script>
