<?php
	$query = mysqli_query($config,"SELECT * FROM tb_barang WHERE id_barang = '$_GET[id]'")or die(mysqli_error($config));
	$data  = mysqli_fetch_array($query);
	$idb   = $data['kode_barang'];

	if (isset($_GET['aksi']) && isset($_GET['id'])) {
		$id = $_GET['id'];
		if ($_GET['aksi'] == 'terima') {
			$update = mysqli_query($config, "UPDATE tb_barang SET status_barang = 'Diterima' WHERE id_barang = '$id'");
			if ($update) {
				echo "<script>alert('Pengajuan berhasil diterima'); window.location='main_admin.php?unit=detail_barang&id=$id';</script>";
			}
		} elseif ($_GET['aksi'] == 'tolak') {
			$update = mysqli_query($config, "UPDATE tb_barang SET status_barang = 'Ditolak' WHERE id_barang = '$id'");
			if ($update) {
				echo "<script>alert('Pengajuan berhasil ditolak'); window.location='main_admin.php?unit=detail_barang&id=$id';</script>";
			}
		}
	} elseif (isset($_POST['kirim_telegram'])) {
		$id_barang = $_POST['id_barang'];
		$id_ob     = $_POST['id_ob'];
		
		// Ambil data barang
		$data = mysqli_fetch_assoc(mysqli_query($config, "SELECT * FROM tb_barang WHERE id_barang = '$id_barang'"));
		$nama_barang = $data['nama_barang'];
		$kode_barang = $data['kode_barang'];

		// Ambil data OB
		$ob      = mysqli_fetch_assoc(mysqli_query($config, "SELECT * FROM tb_petugas_ob WHERE id_ob = '$id_ob'"));
		$chat_id = $ob['chat_id'];
		$nama_ob = $ob['nama_ob'];

		// Salam berdasarkan jam
		$hour = date('H');
		if ($hour >= 5 && $hour < 11) {
			$salam = "Selamat pagi";
		} elseif ($hour >= 11 && $hour < 15) {
			$salam = "Selamat siang";
		} elseif ($hour >= 15 && $hour < 18) {
			$salam = "Selamat sore";
		} else {
			$salam = "Selamat malam";
		}

		// Ambil data OB
		$ob      = mysqli_fetch_assoc(mysqli_query($config, "SELECT * FROM tb_petugas_ob WHERE id_ob = '$id_ob'"));
		$chat_id = $ob['chat_id'];
		$link    = "https://252e-140-213-67-189.ngrok-free.app/it-utl/public/detail_barang.php?id=$idb";

		// Buat link
		$base_url = " https://e6f2-140-213-66-50.ngrok-free.app/public/detail_barang.php?id=" . $data['kode_barang'];
		$pesan = "$salam $nama_ob,\n\nMohon bantuannya untuk melakukan pembelian barang berikut:\n\n📌 *$nama_barang*\n\nUntuk detailnya, silakan cek link berikut:\n$link\n\nTerima kasih dan selamat bekerja 🙏";

		// Kirim ke Telegram (gunakan file atau fungsi lain sesuai sistem kamu)
		file_get_contents("https://api.telegram.org/bot8089365673:AAFbonQywmWGgcOFMttE-Q1rqW0SoHdmceg/sendMessage?chat_id=$chat_id&text=".urlencode($pesan));

		echo "<script>alert('Pesan Telegram berhasil dikirim ke {$ob['nama_ob']}'); window.location='main_admin.php?unit=detail_barang&id=$id_barang';</script>";
	}
?>

<style>
    .table-hover td {
        padding-top: 6px !important;
        padding-bottom: 6px !important;
        vertical-align: middle;
    }
</style>

	<section class="content-header">
		<!-- [Header sama seperti punyamu] -->
	</section>
	<section class="content">
		<div class="container-fluid">
			<div class="card card-default">
				<div class="card-header">
					<h3 class="card-title">Berikut Detail Data Barang</h3>
				</div>
				<div class="card-body">
					<div class="row">
						<!-- TABEL KIRI -->
						<div class="col-md-6">
							<table class="table table-sm table-hover">
								<tbody>
									<tr>
										<td width="180px"><strong>Kode Barang</strong></td>
										<td width="20px">:</td>
										<td><?= $data['kode_barang']; ?></td>
									</tr>
									<tr>
										<td><strong>Nama Barang</strong></td>
										<td>:</td>
										<td><?= $data['nama_barang']; ?></td>
									</tr>
									<tr>
										<td><strong>Keterangan Barang</strong></td>
										<td>:</td>
										<td><?= $data['keterangan_barang']; ?></td>
									</tr>
									<tr>
										<td><strong>Jenis Barang</strong></td>
										<td>:</td>
										<td><?= $data['jenis_barang']; ?></td>
									</tr>
									<tr>
										<td><strong>Status Barang</strong></td>
										<td>:</td>
										<td style="color : red;"><strong><b><?= $data['status_barang']; ?></b></strong></td>
									</tr>
									<tr>
										<td><strong>Foto Barang</strong></td>
										<td>:</td>
										<td>
											<a href="#" data-toggle="modal" data-target="#fotoModal">
												<img src="../assets/upload/<?= $data['foto_barang'] ?: 'Kosong.png';?>" height="50px" style="border-radius: 5px;">
											</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<!-- TABEL KANAN -->
						<div class="col-md-6">
							<table class="table table-sm table-hover">
								<tbody>
									<tr>
										<td><strong>Nama Pengaju</strong></td>
										<td>:</td>
										<td><?= $data['nama_pengajuan']; ?></td>
									</tr>
									<tr>
										<td><strong>Jabatan</strong></td>
										<td>:</td>
										<td><?= $data['jabatan_pengajuan']; ?> (<?= $data['bidang_pengajuan']; ?>)</td>
									</tr>
									<tr>
										<td width="180px"><strong>Tanggal Pengajuan</strong></td>
										<td width="20px">:</td>
										<td><?= date('d-m-Y', strtotime($data['tanggal_pengajuan'])); ?></td>
									</tr>
									<?php if ($data['status_barang'] == 'Diajukan') : ?>
									<tr>
										<td colspan="2">
											<a onclick="return confirm('Anda Menolak Permintaan Ini, Apa Anda Yakin?')" 
											   href="main_admin.php?unit=detail_barang&id=<?= $data['id_barang']; ?>&aksi=tolak" 
											   class="btn btn-block btn-danger">
												<i class="fa fa-close"></i> Tolak
											</a>
										</td>
										<td>
											<a onclick="return confirm('Anda Yakin Ingin Menerima Pengajuan Ini?')" 
											   href="main_admin.php?unit=detail_barang&id=<?= $data['id_barang']; ?>&aksi=terima" 
											   class="btn btn-block btn-success">
												<i class="fa fa-check"></i> Terima
											</a>
										</td>
									</tr>
									<?php elseif ($data['status_barang'] == 'Proses Pembelian' OR $data['status_barang'] == 'Digunakan') : ?>
									<tr>
										<td><strong>Tanggal Pembelian</strong></td>
										<td>:</td>
										<td><?= date('d-m-Y', strtotime($data['tanggal_pembelian'])); ?></td>
									</tr>
									<tr>
										<td><strong>Nama Pembeli</strong></td>
										<td>:</td>
										<td><?= $data['nama_pembeli']; ?></td>
									</tr>
									<tr>
										<td><strong>Harga Beli</strong></td>
										<td>:</td>
										<td>Rp <?= number_format($data['harga_beli'], 0, ',', '.'); ?></td>
									</tr>
									<tr>
										<td><strong>Nama Supplier</strong></td>
										<td>:</td>
										<td><?= $data['nama_supplier']; ?></td>
									</tr>
									<tr>
										<td><strong>Foto Kwitansi</strong></td>
										<td>:</td>
										<td>
											<a href="#" data-toggle="modal" data-target="#kwitansiModal">
												<img src="../assets/upload/<?= $data['foto_kwitansi'] ?: 'Kosong.png'; ?>" height="50px" style="border-radius: 5px;">
											</a>
										</td>
									</tr>
									<tr>
										<td colspan="3" align="right">
											<a class="btn btn-sm btn-block bg-success" target="_blank" href="unit/barang/cetak_barang.php?id=<?= $data['id_barang']; ?>">
												<i class="fas fa-print"></i> Cetak PDF
											</a>
										</td>
									</tr>
									<?php endif; ?>
									<tr>
										<td colspan='3' align="right">
											<a class="btn btn-sm btn-block bg-warning" href="main_admin.php?unit=barang"><i class="fas fa-reply"></i> Back</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
					<!-- Tambahkan Form Pilih OB jika status_barang = 'Diterima' -->
					<?php if ($data['status_barang'] == 'Diterima') : ?>
					<hr>
					<form method="POST">
						<input type="hidden" name="id_barang" value="<?= $data['id_barang']; ?>">
						<div class="form-group">
							<label>Pilih Petugas OB untuk Pengadaan:</label>
							<select name="id_ob" class="form-control" required>
								<option value="">-- Pilih OB Aktif --</option>
								<?php 
								$obList = mysqli_query($config, "SELECT * FROM tb_petugas_ob WHERE aktif = 1");
								while ($ob = mysqli_fetch_assoc($obList)) : ?>
									<option value="<?= $ob['id_ob']; ?>"><?= $ob['nama_ob']; ?></option>
								<?php endwhile; ?>
							</select>
						</div>
						<div class="form-group">
							<button type="submit" name="kirim_telegram" class="btn btn-primary btn-block">
								<i class="fab fa-telegram"></i> Kirim Telegram ke Petugas OB
							</button>
						</div>
					</form>
					<?php endif; ?>
				</div>
			</div>
		</div>	
		<!-- MODAL FOTO -->
		<div class="modal fade" id="fotoModal" tabindex="-1" role="dialog" aria-labelledby="fotoModalLabel" aria-hidden="true">
		  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
			<div class="modal-content">
			  <div class="modal-header">
				<h5 class="modal-title">Foto Barang</h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Tutup">
				  <span aria-hidden="true">&times;</span>
				</button>
			  </div>
			  <div class="modal-body text-center">
				<img src="../assets/upload/<?= $data['foto_barang']; ?>" class="img-fluid rounded">
			  </div>
			</div>
		  </div>
		</div>
		<!-- MODAL FOTO KWITANSI -->
		<div class="modal fade" id="kwitansiModal" tabindex="-1" role="dialog">
			<div class="modal-dialog modal-md modal-dialog-centered" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Foto Kwitansi</h5>
						<button type="button" class="close" data-dismiss="modal">
							<span>&times;</span>
						</button>
					</div>
					<div class="modal-body text-center">
						<img src="../assets/upload/<?= $data['foto_kwitansi']; ?>" class="img-fluid rounded">
					</div>
				</div>
			</div>
		</div>
	</section>
