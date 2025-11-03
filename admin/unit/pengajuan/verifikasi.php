<?php
require_once("../config/koneksi.php");

if (isset($_GET['id_pengajuan'])) {
    $id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
    $query = mysqli_query($config, "
        SELECT p.*, j.nama_jenis, j.kode_jenis, u.nama_lengkap, u.kode_pokja 
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

// --- PROSES VERIFIKASI --- //
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verifikasi'])) {
    $catatan_admin = mysqli_real_escape_string($config, $_POST['catatan_admin']);
    $tanggal_disetujui = date('Y-m-d');

    // Buat nomor surat otomatis
    $jenis_dokumen = strtoupper($data['kode_jenis']);
    $kode_pokja = strtoupper($data['kode_pokja']);
    $bulan_romawi = [
        1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
        7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
    ];
    $bulan = $bulan_romawi[(int)date('n')];
    $tahun = date('Y');

    // Ambil nomor surat terakhir berdasarkan kode_pokja
    $q_nomor = mysqli_query($config, "
        SELECT p.nomor_surat 
        FROM tb_pengajuan_dokumen p
        LEFT JOIN tb_user u ON p.id_user = u.id_user
        WHERE u.kode_pokja = '$kode_pokja'
          AND p.nomor_surat IS NOT NULL
        ORDER BY p.id_pengajuan DESC 
        LIMIT 1
    ");

    if (mysqli_num_rows($q_nomor) > 0) {
        $last_nomor = mysqli_fetch_assoc($q_nomor)['nomor_surat'];

        // Format contoh: A/001/SPO/KPS/I/2025
        // Pecah bagian berdasarkan '/'
        $parts = explode('/', $last_nomor);
        $last_urutan = 0;

        if (isset($parts[1]) && is_numeric(trim($parts[1]))) {
            $last_urutan = (int) trim($parts[1]);
        }

        // Tambahkan 1 dan ubah ke format 3 digit
        $urutan = str_pad($last_urutan + 1, 3, '0', STR_PAD_LEFT);
    } else {
        // Jika belum ada nomor surat untuk Pokja ini
        $urutan = '001';
    }

    // Buat format nomor surat baru
    $nomor_surat = "A/$urutan/$jenis_dokumen/$kode_pokja/$bulan/$tahun";

    // Update data pengajuan
    $update = mysqli_query($config, "
        UPDATE tb_pengajuan_dokumen SET 
            status = 'Disetujui',
            tanggal_disetujui = '$tanggal_disetujui',
            catatan_admin = '$catatan_admin',
            nomor_surat = '$nomor_surat'
        WHERE id_pengajuan = '$id_pengajuan'
    ");

    if ($update) {
        header('Location: main_admin.php?unit=pengajuan&msg=Pengajuan berhasil diverifikasi dan nomor surat telah dibuat!');
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

                    <button type="submit" name="verifikasi" class="btn btn-app bg-success float-right">
                        <i class="fas fa-check"></i> Setujui & Buat Nomor Surat
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
