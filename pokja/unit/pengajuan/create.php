<?php
	require_once("../config/koneksi.php"); // Sesuaikan path ke koneksi

	// Cek jika form disubmit
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$id_user        = mysqli_real_escape_string($config, $_POST['id_user']);
		$id_jenis       = mysqli_real_escape_string($config, $_POST['id_jenis']);
		$judul_dokumen  = mysqli_real_escape_string($config, $_POST['judul_dokumen']);
		$tanggal_dok    = mysqli_real_escape_string($config, $_POST['tanggal_dokumen']);
		$tanggal_ajuan  = date('Y-m-d'); // otomatis tanggal hari ini
		$catatan        = mysqli_real_escape_string($config, $_POST['catatan']);
		$status         = 'Menunggu Verifikasi'; // status awal

		// Proses upload file draft (Word)
		$allowed_ext = ['doc', 'docx'];
		$file_name   = $_FILES['file_draft']['name'];
		$file_tmp    = $_FILES['file_draft']['tmp_name'];
		$file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
		$upload_dir  = '../assets/upload/draft_word/'; // pastikan folder ini sudah ada dan writable

		// Cek format file
		if (!in_array($file_ext, $allowed_ext)) {
			echo "<script>
					alert('Format file tidak valid! Hanya diperbolehkan .doc atau .docx');
					window.location = 'main_pokja.php?unit=create_pengajuan';
				  </script>";
			exit;
		}

		// Buat nama file unik
		$new_filename = 'draft_' . time() . '.' . $file_ext;

		// Pindahkan file ke folder tujuan
		if (move_uploaded_file($file_tmp, $upload_dir . $new_filename)) {
			// Simpan ke database
			$query = "INSERT INTO tb_pengajuan_dokumen
					  (id_user, id_jenis, judul_dokumen, file_draft, tanggal_dokumen, tanggal_ajuan, catatan, status) 
					  VALUES 
					  ('$id_user', '$id_jenis', '$judul_dokumen', '$new_filename', '$tanggal_dok', '$tanggal_ajuan', '$catatan', '$status')";
			
			if (mysqli_query($config, $query)) {
				echo "<script>
						alert('Pengajuan dokumen berhasil disimpan!');
						window.location = 'main_pokja.php?unit=pengajuan';
					  </script>";
			} else {
				echo "<script>
						alert('Gagal menyimpan data: " . mysqli_error($config) . "');
						window.location = 'main_pokja.php?unit=create_pengajuan';
					  </script>";
			}
		} else {
			echo "<script>
					alert('Gagal mengupload file draft!');
					window.location = 'main_pokja.php?unit=create_pengajuan';
				  </script>";
		}
	}
?>

<section class="content-header">
	<div class="container-fluid">
		<div class="row mb-2">
			<div class="col-sm-6">
				<h1>Form Pengajuan Nomor Dokumen</h1>
			</div>
		</div>
	</div>
</section>

<section class="content">
	<div class="container-fluid">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Silakan Input Data Pengajuan Dokumen</h3>
			</div>
			<form method="post" enctype="multipart/form-data">
				<div class="card-body">
					<div class="row">
						<div class="col-sm-12">
							<div class="form-group">
								<label>Jenis Dokumen</label>
								<input type="hidden" name="id_user" class="form-control" value="<?php echo $_SESSION['id_user']; ?>" readonly>
								<select name="id_jenis" class="form-control select2" required>
									<option value="">-- Pilih Jenis Dokumen --</option>
									<?php
										$qjenis = mysqli_query($config, "SELECT * FROM tb_jenis_dokumen");
										while ($r = mysqli_fetch_assoc($qjenis)) {
											echo "<option value='{$r['id_jenis']}'>{$r['nama_jenis']}</option>";
										}
									?>
								</select>
							</div>

							<div class="form-group">
								<label>Judul Dokumen</label>
								<div class="input-group">
									<span class="input-group-text"><i class="fas fa-file-signature"></i></span>
									<input type="text" name="judul_dokumen" class="form-control" placeholder="Contoh: Rencana Kerja Tahunan - Revisi 2025" required>
								</div>
								<small class="form-text text-muted">Masukkan judul dokument. Jika dokumen adalah revisi tambahkan dibelakang judul kata "Revisi. Contoh format: - Revisi.</small>
							</div>

							<div class="form-group">
								<label>Tanggal Dokumen</label>
								<input type="date" name="tanggal_dokumen" class="form-control" value="<?= date('Y-m-d') ?>" required>
							</div>

							<div class="form-group">
								<label>File Draft (Harus Format : Word)</label>
								<input type="file" name="file_draft" accept=".doc,.docx" class="form-control" required>
							</div>

							<div class="form-group">
								<label>Catatan</label>
								<div class="input-group">
									<textarea name="catatan" class="form-control" rows="3" placeholder="Opsional: tulis ringkasan, tujuan, atau catatan penting untuk tim verifikasi dan tambahkan nomor telepon"></textarea>
								</div>
								<small class="form-text text-muted"><b>Wajib</b> — tambahkan No Telepon (contoh: WA=08123456789).</small>
							</div>

						</div>
					</div>
				</div>

				<div class="card-footer">
					<a class="btn btn-app bg-warning float-left" href="main_pokja.php?unit=pengajuan">
						<i class="fas fa-reply"></i> Back
					</a>
					<button class="btn btn-app bg-success float-right" type="submit">
						<i class="fas fa-save"></i> SAVE
					</button>
				</div>
			</form>
		</div>
	</div>
</section>
