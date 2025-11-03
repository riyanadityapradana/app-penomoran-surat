<?php
	require_once("../config/koneksi.php");

	if (!isset($_GET['id'])) {
		echo "<script>alert('ID lembur tidak ditemukan!'); window.history.back();</script>";
		exit;
	}

	$id_lembur = $_GET['id'];

	// Proses konfirmasi jika ada POST dari tombol konfirmasi
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['aksi'])) {
		$aksi = $_POST['aksi']; // 'Diterima' atau 'Ditolak'
		$id_pimpinan = $_SESSION['kode_user'];

		$update = mysqli_query($config, "UPDATE tb_lembur 
			SET status_lembur = '$aksi', id_pimpinan = '$id_pimpinan' 
			WHERE id_lembur = '$id_lembur'");

		if ($update) {
			echo "<script>alert('Lembur telah dikonfirmasi!'); window.location = '?unit=detail_lembur&id=$id_lembur';</script>";
			exit;
		} else {
			echo "<script>alert('Gagal mengupdate status lembur');</script>";
		}
	}

	// Ambil data lembur
	$queryLembur = mysqli_query($config, "SELECT l.*, s.nama_karyawan AS nama_staff, s.level AS jabatan_staff 
		FROM tb_lembur l 
		LEFT JOIN tb_user s ON l.id_staff = s.kode_user 
		WHERE l.id_lembur = '$id_lembur'");
	$dataLembur = mysqli_fetch_assoc($queryLembur);

	// Ambil data kegiatan
	$queryKegiatan = mysqli_query($config, "SELECT * FROM tb_kegiatan_lembur WHERE id_lembur = '$id_lembur'");

	// Jika sudah dikonfirmasi, ambil info pimpinan
	$dataPimpinan = null;
	if (!empty($dataLembur['id_pimpinan'])) {
		$queryPimpinan = mysqli_query($config, "SELECT nama_karyawan, level FROM tb_user WHERE kode_user = '{$dataLembur['id_pimpinan']}'");
		$dataPimpinan = mysqli_fetch_assoc($queryPimpinan);
	}
?>

	<link rel="stylesheet" href="../assets/dist/css/bootstrap.min.css">

	<section class="content">
		<div class="container-fluid">
			<div class="card card-default">
				<div class="card-header">
					<h3 class="card-title">Detail Lembur Staff</h3>
				</div>
				<div class="card-body">
					<div class="row">
						<!-- Kolom Kiri -->
						<div class="col-md-6">
							<table class="table table-bordered">
								<tr>
									<th width="150">ID Lembur</th><td>:</td><td><?= $dataLembur['id_lembur']; ?></td>
								</tr>
								<tr>
									<th>Nama Staff</th><td>:</td><td><?= $dataLembur['nama_staff']; ?></td>
								</tr>
								<tr>
									<th>Jabatan</th><td>:</td><td><?= $dataLembur['jabatan_staff']; ?></td>
								</tr>
								<tr>
									<th>Tanggal</th><td>:</td><td><?= date('d-m-Y', strtotime($dataLembur['tanggal_lembur'])); ?></td>
								</tr>
								<tr>
									<th>Status</th><td>:</td><td><?= $dataLembur['status_lembur']; ?></td>
								</tr>
								<?php if ($dataPimpinan): ?>
								<tr>
									<th>Diproses Oleh</th><td>:</td><td><?= $dataPimpinan['nama_karyawan']; ?> (<?= $dataPimpinan['level']; ?>)</td>
								</tr>
								<?php endif; ?>
								<?php if ($dataLembur['status_lembur'] == 'Menunggu'): ?>
								<tr>
									<td colspan="2">
										<form method="post">
											<input type="hidden" name="aksi" value="Diterima">
											<button type="submit" class="btn btn-block btn-success">Terima</button>
										</form>
									</td>
									<td>
										<form method="post">
											<input type="hidden" name="aksi" value="Ditolak">
											<button type="submit" class="btn btn-block btn-danger">Tolak</button>
										</form>
									</td>
								</tr>
								<?php endif; ?>
							</table>
						</div>

						<!-- Kolom Kanan -->
						<div class="col-md-6">
							<h5>Daftar Kegiatan Lembur:</h5>
							<ul class="list-group">
								<?php while ($k = mysqli_fetch_assoc($queryKegiatan)) : ?>
									<li class="list-group-item">- <?= htmlspecialchars($k['kegiatan']); ?></li>
								<?php endwhile; ?>
							</ul>
							<a href="main_admin.php?unit=lembur" class="btn btn-warning mt-4">Kembali</a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
