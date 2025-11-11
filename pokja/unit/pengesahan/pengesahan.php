<!-- Content Header (Page header) -->
<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>DATA PENGESAHAN DOKUMEN</h1>
			</div>
			<div class="col-sm-6">
				<ol class="breadcrumb float-sm-right">
					<li class="breadcrumb-item"><a href="main_pokja.php?unit=beranda">Home</a></li>
					<li class="breadcrumb-item active">Dokumen Selesai</li>
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
				<div class="card-tools" style="float: right; text-align: right;">
					<a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse" style="background:rgba(69, 77, 85, 1)">
						<i class="fas fa-bars"></i>
					</a>
				</div>
				<h3 class="card-title">Daftar Dokumen yang Telah Disahkan</h3>
			</div>

			<div class="card-body">
				<?php
				// Query untuk jenis dokumen
				$query_jenis = "SELECT id_jenis, nama_jenis FROM tb_jenis_dokumen ORDER BY nama_jenis ASC";
				$jenis_data = mysqli_query($config, $query_jenis);

				// Ambil filter jenis dokumen
				$id_jenis_filter = isset($_GET['id_jenis']) ? $_GET['id_jenis'] : '';
				?>
				<form method="GET" class="form-inline mb-4">
					<input type="hidden" name="unit" value="pengesahan">
					<div class="form-group mr-4">
						<label for="id_jenis" class="mr-4"><strong>Jenis Dokumen:</strong></label>
						<select name="id_jenis" id="id_jenis" class="form-control select2">
							<option value="">Semua</option>
							<?php while ($jenis = mysqli_fetch_assoc($jenis_data)) { ?>
								<option value="<?= $jenis['id_jenis'] ?>" <?= ($id_jenis_filter == $jenis['id_jenis']) ? 'selected' : '' ?>><?= htmlspecialchars($jenis['nama_jenis']) ?></option>
							<?php } ?>
						</select>
					</div>
					<button type="submit" class="btn btn-success">
						<i class="fas fa-filter"></i> Filter
					</button>
				</form>

				<table id="example2" class="table table-bordered table-striped text-center">
					<thead style="background:rgb(0, 102, 51, 1); color: white;">
						<tr>
							<th>No</th>
							<th style="font-size: 14px;" width="120" responsive>Jenis Dokumen</th>
							<th style="font-size: 14px;" width="120" responsive>Judul Dokumen</th>
							<th style="font-size: 14px;" width="120" responsive>Tgl Dokumen</th>
							<th style="font-size: 14px;" width="120" responsive>Tgl Ajuan</th>
							<th style="font-size: 14px;" width="120" responsive>Status</th>
							<th style="font-size: 14px;" width="120" responsive>File PDF Final</th>
							<th>Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$no = 1;
						$id_user = $_SESSION['id_user'];

						// Ambil hanya dokumen yang sudah selesai
						$query_str = "
							SELECT p.*, j.nama_jenis
							FROM tb_pengajuan_dokumen p
							LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
							WHERE p.id_user = '$id_user' AND p.status = 'Selesai'";

						// Tambahkan filter jenis dokumen jika dipilih
						if (!empty($id_jenis_filter)) {
							$query_str .= " AND p.id_jenis = '$id_jenis_filter'";
						}

						$query_str .= " ORDER BY p.id_pengajuan DESC";

						$query = mysqli_query($config, $query_str);

						if (mysqli_num_rows($query) > 0) {
							while ($row = mysqli_fetch_assoc($query)) {
								$tgl_dok = date('d-m-Y', strtotime($row['tanggal_dokumen']));
								$tgl_ajuan = date('d-m-Y', strtotime($row['tanggal_ajuan']));

								echo "<tr>
									<td>{$no}</td>
									<td>{$row['nama_jenis']}</td>
									<td>{$row['judul_dokumen']}</td>
									<td>{$tgl_dok}</td>
									<td>{$tgl_ajuan}</td>
									<td><span class='badge badge-primary'>Selesai</span></td>
									<td>";

								if (!empty($row['file_draft'])) {
									echo "<a href='../assets/upload/draft_word/{$row['file_draft']}' 
											target='_blank' class='btn btn-sm btn-success'>
											<i class='fas fa-file-pdf'></i> Download
										  </a>";
								} else {
									echo "-";
								}

								echo "</td>
									<td>
										<a href='main_pokja.php?unit=detail_pengesahan&id_pengajuan={$row['id_pengajuan']}' 
											class='btn btn-sm btn-info'>
											<i class='fas fa-eye'></i> Detail
										</a>
									</td>
								</tr>";
								$no++;
							}
						} else {
							echo "<tr><td colspan='8'><em>Tidak ada dokumen yang telah disahkan</em></td></tr>";
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</section>
