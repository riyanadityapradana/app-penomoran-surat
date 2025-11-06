<?php
	$query = mysqli_query($config,"SELECT * FROM tb_barang WHERE id_barang = '$_GET[id]'")or die(mysqli_error($config));
	$data  = mysqli_fetch_array($query);
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
										<td width="150px"><strong>Kode Barang</strong></td>
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
										<td width="150px"><strong>Tanggal Pengajuan</strong></td>
										<td width="20px">:</td>
										<td><?= date('d-m-Y', strtotime($data['tanggal_pengajuan'])); ?></td>
									</tr>
									<?php if ($data['status_barang'] == 'Proses Pembelian' OR $data['status_barang'] == 'Digunakan') : ?>
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
											<a class="btn btn-sm btn-block bg-warning" href="main_staff.php?unit=barang"><i class="fas fa-reply"></i> Back</a>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<!-- FORM INPUT KONDISI -->
			<?php if ($data['status_barang'] == 'Diterima') : ?>		
			<div class="card card-default">
				<div class="card-header">
					<h3 class="card-title">Form Update Pembelian Barang</h3>
				</div>	
				<div class="card-body">
					<form method="post" enctype="multipart/form-data" action="unit/barang/update_pembelian.php?id=<?= $data['id_barang']; ?>">
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Tanggal Pembelian</label>
									<input type="date" name="tanggal_pembelian" class="form-control" required>
								</div>
								<div class="form-group">
									<label>Nama Pembeli</label>
									<input type="text" name="nama_pembeli" class="form-control" required>
								</div>
								<div class="form-group">
									<label>Harga Beli</label>
									<input type="number" name="harga_beli" class="form-control" required>
								</div>
								<div class="form-group">
									<label>Nama Supplier</label>
									<input type="text" name="nama_supplier" class="form-control" required>
								</div>
							</div>
							<div class="col-md-6">
								<div class="form-group">
									<label>Upload Foto Kwitansi</label>
									<input type="file" name="foto_kwitansi" class="form-control" accept="image/*" required>
								</div>
								<div class="form-group">
									<label>Upload Foto Barang Baru</label>
									<input type="file" name="foto_barang" class="form-control" accept="image/*" required>
								</div>
								<div class="form-group">
									<label>&nbsp;</label><br>
									<button type="submit" class="btn btn-success">
										<i class="fas fa-save"></i> Simpan Perubahan
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
			<?php endif; ?>
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
