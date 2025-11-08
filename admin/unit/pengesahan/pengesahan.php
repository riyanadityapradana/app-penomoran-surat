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
			<div class="card-header" style="background-color:#000000; color:white;">
				<h3 class="card-title">Daftar Dokumen yang Telah Disahkan</h3>
				<div class="card-tools">
					<a href="#" class="btn btn-tool btn-sm text-white" data-card-widget="collapse">
						<i class="fas fa-minus"></i>
					</a>
				</div>
			</div>

			<div class="card-body">
				<!-- FILTER POKJA -->
				<form method="GET" action="" class="mb-5">
					<input type="hidden" name="unit" value="pengesahan">
					<div class="form-group row">
						<label for="kode_pokja" class="col-sm-2 col-form-label text-left">Cari Berdasarkan:</label>
						<div class="col-sm-6">
							<select name="kode_pokja" id="kode_pokja" class="form-control" style="border: 2px solid #009688; background-color:#e0f2f1; color:#004d40;">
								<option value="">-- Semua Pokja --</option>
								<?php
								$q_pokja = mysqli_query($config, "SELECT id_user, kode_pokja FROM tb_user WHERE level = 'Pokja' ORDER BY kode_pokja ASC");
								while ($p = mysqli_fetch_assoc($q_pokja)) {
									$selected = (isset($_GET['kode_pokja']) && $_GET['kode_pokja'] == $p['id_user']) ? 'selected' : '';
									echo "<option value='{$p['id_user']}' $selected>{$p['kode_pokja']}</option>";
								}
								?>
							</select>
						</div>
						<div class="col-sm-2">
							<button type="submit" class="btn btn-success btn-block">
								<i class="fas fa-filter"></i> Tampilkan
							</button>
						</div>
						<div class="col-sm-2">
							<a href="main_admin.php?unit=pengesahan" class="btn btn-secondary btn-block">
								<i class="fas fa-sync"></i> Reset
							</a>
						</div>
					</div>
				</form>

				<!-- TABEL DATA -->
				<table id="example2" class="table table-bordered table-striped">
					<thead style="background:rgb(0, 102, 51, 1); color: white;">
						<tr>
							<th>No</th>
							<th>No Dokumen</th>
							<th>Jenis Dokumen</th>
							<th>Judul Dokumen</th>
							<th>Tgl Dokumen</th>
							<th>Pokja</th>
							<th>Status</th>
							<!-- <th>File PDF Final</th> -->
							<th width="60">Aksi</th>
						</tr>
					</thead>
					<tbody>
						<?php
						$no = 1;
						$where = "WHERE p.status = 'Selesai'";

						if (isset($_GET['kode_pokja']) && $_GET['kode_pokja'] != '') {
							$kode_pokja = mysqli_real_escape_string($config, $_GET['kode_pokja']);
							$where .= " AND p.id_user = '$kode_pokja'";
						}

						$query = mysqli_query($config, "
							SELECT p.*, j.nama_jenis, k.kode_pokja
							FROM tb_pengajuan_dokumen p
							LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
							LEFT JOIN tb_user k ON p.id_user = k.id_user
							$where
							ORDER BY p.id_pengajuan DESC
						");

						if (mysqli_num_rows($query) > 0) {
							while ($row = mysqli_fetch_assoc($query)) {
								$tgl_dok = date('d-m-Y', strtotime($row['tanggal_dokumen']));
								echo "<tr>
									<td>{$no}</td>
									<td>{$row['nomor_surat']}</td>
									<td>{$row['nama_jenis']}</td>
									<td>{$row['judul_dokumen']}</td>
									<td>{$tgl_dok}</td>
									<td>{$row['kode_pokja']}</td>
									<td><span class='badge badge-primary'>Selesai</span></td>
									<td>
										<a href='main_admin.php?unit=detail_pengesahan&id_pengajuan={$row['id_pengajuan']}' class='btn btn-sm btn-info'>
											<i class='fas fa-eye'></i>
										</a>
										<a href='main_admin.php?unit=detail_pengesahan&id_pengajuan={$row['id_pengajuan']}&edit=1' class='btn btn-sm btn-warning'>
											<i class='fas fa-edit'></i>
										</a>
									</td>
								</tr>";
								$no++;
							}
						} else {
							echo "<tr><td colspan='9'><em>Tidak ada dokumen yang telah disahkan</em></td></tr>";
						}
						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</section>
