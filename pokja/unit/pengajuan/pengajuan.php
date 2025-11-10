<!-- Content Header (Page header) -->
<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>DATA PENGAJUAN NOMOR SURAT</h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="main_pokja.php?unit=beranda">Home</a></li>
					<li class="breadcrumb-item active">Dokumen Ajuan</li>
				</ol>
			</div>
		</div>
	</div>
</section>

<!-- Main content -->
<section class="content">
	<div class="container-fluid">
		<div class="card">
			<div class="card-header">
				<div class="card-tools" style="float: left;">
					<a href="?unit=create_pengajuan" class="btn btn-tool btn-sm" style="background:rgba(0, 123, 255, 1)">
						<i class="fas fa-plus-square" style="color: white;"> Tambah Data</i>
					</a>
				</div>
				<div class="card-tools" style="float: right;">
					<a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse" style="background:rgba(69, 77, 85, 1)">
						<i class="fas fa-bars"></i>
					</a>
				</div>
			</div>
			<div class="card-body">
				<div class="alert alert-info">
					<i class="fas fa-info-circle"></i> <strong>Catatan:</strong> Jika status pengajuan masih <b>'Menunggu Verifikasi' atau 'Disetujui'</b>, maka data dapat diubah.
				</div>
				<table id="example2" class="table table-bordered table-striped text-center">
					<thead style="background:rgb(0, 0, 0, 1); color: white;">
						<tr>
							<th>No</th>
							<th>Dokumen</th>
							<th style="font-size: 14px;" width="120" responsive>Judul Dokumen</th>
							<th style="font-size: 14px;" width="120" responsive>Tgl Dokumen</th>
							<th style="font-size: 14px;" width="120" responsive>Tgl Ajuan</th>
							<th style="font-size: 14px;" width="120" responsive>Status</th>
							<th style="font-size: 14px;" width="120" responsive>File Draft</th>
							<th style="font-size: 14px;" width="120" responsive>Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$no = 1;
						$id_user = $_SESSION['id_user'];

						$query = mysqli_query($config, "
							SELECT p.*, j.nama_jenis
							FROM tb_pengajuan_dokumen p
							LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
							WHERE p.id_user = '$id_user' AND status != 'Selesai'
							ORDER BY p.id_pengajuan DESC
						");

						if (mysqli_num_rows($query) > 0) {
							while ($row = mysqli_fetch_assoc($query)) {
								$tgl_dok = date('d-m-Y', strtotime($row['tanggal_dokumen']));
								$tgl_ajuan = date('d-m-Y', strtotime($row['tanggal_ajuan']));
								$status = $row['status'];

								echo "<tr>
									<td>{$no}</td>
									<td>{$row['nama_jenis']}</td>
									<td>{$row['judul_dokumen']}</td>
									<td>{$tgl_dok}</td>
									<td>{$tgl_ajuan}</td>
									<td>";

								if ($status == 'Menunggu Verifikasi') {
									echo "<span class='badge badge-warning'>Menunggu</span>";
								} elseif ($status == 'Disetujui') {
									echo "<span class='badge badge-success'>Disetujui</span>";
								} elseif ($status == 'Ditolak') {
									echo "<span class='badge badge-danger'>Ditolak</span>";
								} elseif ($status == 'Selesai') {
									echo "<span class='badge badge-primary'>Selesai</span>";
								} else {
									echo "<span class='badge badge-secondary'>Tidak Diketahui</span>";
								}

								echo "</td>
									<td>";
								if (!empty($row['file_draft'])) {
									echo "<a href='../assets/upload/draft_word/{$row['file_draft']}' target='_blank' class='btn btn-sm btn-info'>
											<i class='fas fa-file-word'></i> Download
										  </a>";
								} else {
									echo "-";
								}
								echo "</td>
									<td>";

								// Tombol Detail
								echo "<a href='main_pokja.php?unit=detail_pengajuan&id_pengajuan={$row['id_pengajuan']}' 
										class='btn btn-sm btn-primary'>
										<i class='fas fa-eye'></i>
									  </a> ";

								// Tombol Edit & Hapus hanya jika status 'Menunggu Verifikasi' atau 'Disetujui'
								if ($status == 'Menunggu Verifikasi' || $status == 'Disetujui') {
									echo "<a href='main_pokja.php?unit=update_pengajuan&id_pengajuan={$row['id_pengajuan']}' 
											class='btn btn-sm btn-success'>
											<i class='fas fa-edit'></i>
										  </a> 
										  <a href='main_pokja.php?unit=delete_pengajuan&id_pengajuan={$row['id_pengajuan']}' 
											onclick=\"return confirm('Yakin ingin menghapus data ini?')\" 
											class='btn btn-sm btn-danger'>
											<i class='fas fa-trash'></i>
										  </a> ";

									// 🟩 Tombol Kirim Email
									echo "<button class='btn btn-sm btn-warning' data-toggle='modal' data-target='#emailModal{$row['id_pengajuan']}'>
											<i class='fa fa-paper-plane'></i>
										  </button>";

									// 🟩 Modal Konfirmasi Kirim Email
									echo "
									<div class='modal fade' id='emailModal{$row['id_pengajuan']}' tabindex='-1' role='dialog'>
									  <div class='modal-dialog modal-dialog-centered' role='document'>
										<div class='modal-content'>
										  <div class='modal-header bg-warning'>
											<h5 class='modal-title'><i class='fas fa-envelope'></i> Konfirmasi Kirim Email</h5>
											<button type='button' class='close' data-dismiss='modal'>&times;</button>
										  </div>
										  <div class='modal-body'>
											<p>Apakah Anda ingin mengirim email notifikasi untuk dokumen <strong>{$row['judul_dokumen']}</strong>?</p>
										  </div>
										  <div class='modal-footer'>
										 <form method='post' action='main_pokja.php?unit=kirim_email&id_pengajuan={$row['id_pengajuan']}' style='display:inline;'>
										  <input type='hidden' name='email_admin' value='sekretariatrspelitainsani@gmail.com'>
										  <button type='submit' name='kirim_email' class='btn btn-primary'>
										  	<i class='fas fa-paper-plane'></i> Kirim Email
										  </button>
										 </form>
										 <button type='button' class='btn btn-secondary' data-dismiss='modal'>Tutup</button>
										  </div>
										</div>
									  </div>
									</div>";
								}

								echo "</td>
								</tr>";
								$no++;
							}
						} else {
							echo "<tr><td colspan='8'>Belum ada pengajuan surat</td></tr>";
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</section>
