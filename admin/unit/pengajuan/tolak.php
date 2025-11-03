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
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tolak'])) {
    $catatan_admin = mysqli_real_escape_string($config, $_POST['catatan_admin']);
    $tanggal_disetujui = date('Y-m-d');

    // Update data pengajuan
    $update = mysqli_query($config, "
        UPDATE tb_pengajuan_dokumen SET 
            status = 'Ditolak',
            tanggal_disetujui = '$tanggal_disetujui',
            catatan_admin = '$catatan_admin'
        WHERE id_pengajuan = '$id_pengajuan'
    ");

    if ($update) {
        header('Location: main_admin.php?unit=pengajuan&msg=Pengajuan Telah berhasil ditolak!');
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
