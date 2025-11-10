<?php
	if (!isset($_SESSION['id_user'])) {
		echo "<script>
				alert('Silakan login terlebih dahulu!');
				window.location = '../login.php';
			  </script>";
		exit;
	}

	// --- AMBIL DATA BERDASARKAN ID --- //
	if (isset($_GET['id_pengajuan'])) {
		$id_pengajuan = mysqli_real_escape_string($config, $_GET['id_pengajuan']);
		$query = mysqli_query($config, "
			SELECT * FROM tb_pengajuan_dokumen 
			WHERE id_pengajuan = '$id_pengajuan' AND id_user = '{$_SESSION['id_user']}'
		");
		$data = mysqli_fetch_assoc($query);

		if (!$data) {
			echo "<script>
					alert('Data pengajuan tidak ditemukan!');
					window.location = 'main_pokja.php?unit=pengajuan';
				  </script>";
			exit;
		}
	} else {
		echo "<script>
				alert('Parameter ID tidak ditemukan!');
				window.location = 'main_pokja.php?unit=pengajuan';
			  </script>";
		exit;
	}

	// --- CEGAH EDIT JIKA STATUS BUKAN MENUNGGU VERIFIKASI ATAU DISETUJUI --- //
	if ($data['status'] != 'Menunggu Verifikasi' && $data['status'] != 'Disetujui') {
		echo "<script>
				alert('Data tidak dapat diedit karena status sudah selesai atau ditolak!');
				window.location = 'main_pokja.php?unit=pengajuan';
			  </script>";
		exit;
	}

	// --- PROSES UPDATE --- //
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$id_pengajuan    = mysqli_real_escape_string($config, $_POST['id_pengajuan']);
		$id_jenis        = mysqli_real_escape_string($config, $_POST['id_jenis']);
		$judul_dokumen   = mysqli_real_escape_string($config, $_POST['judul_dokumen']);
		$tanggal_dokumen = mysqli_real_escape_string($config, $_POST['tanggal_dokumen']);
		$file_draft_lama = mysqli_real_escape_string($config, $_POST['file_draft_lama']);

		$file_draft = $file_draft_lama;

		// Jika ada file baru diupload
		if (!empty($_FILES['file_draft']['name'])) {
			$namaFile = $_FILES['file_draft']['name'];
			$tmpFile  = $_FILES['file_draft']['tmp_name'];
			$ukuran   = $_FILES['file_draft']['size'];
			$ext      = strtolower(pathinfo($namaFile, PATHINFO_EXTENSION));

			// Validasi tipe file
			$allowed = ['doc', 'docx'];
			if (!in_array($ext, $allowed)) {
				echo "<script>alert('Hanya file Word (.doc/.docx) yang diperbolehkan!');</script>";
			} elseif ($ukuran > 10485760) { // 10MB
				echo "<script>alert('Ukuran file maksimal 10MB!');</script>";
			} else {
				// Hapus file lama jika ada
				if (!empty($data['file_draft']) && file_exists("../assets/upload/draft_word/" . $data['file_draft'])) {
					unlink("../assets/upload/draft_word/" . $data['file_draft']);
				}

				$newName = "draft_" . time() . "." . $ext;
				$tujuan = "../assets/upload/draft_word/" . $newName;

				if (move_uploaded_file($tmpFile, $tujuan)) {
					$file_draft = $newName;
				}
			}
		}

		$query_update = "UPDATE tb_pengajuan_dokumen SET 
							id_jenis = '$id_jenis',
							judul_dokumen = '$judul_dokumen',
							tanggal_dokumen = '$tanggal_dokumen',
							file_draft = '$file_draft'
						WHERE id_pengajuan = '$id_pengajuan'";

		if (mysqli_query($config, $query_update)) {
			echo "<script>
					alert('Data pengajuan berhasil diperbarui!');
					window.location = 'main_pokja.php?unit=pengajuan';
				  </script>";
		} else {
			echo "<script>
					alert('Gagal memperbarui data: " . mysqli_error($config) . "');
					window.location = 'main_pokja.php?unit=pengajuan';
				  </script>";
		}
	}
	?>

	<section class="content-header">
		<h1>Edit Pengajuan Dokumen</h1>
	</section>

	<section class="content">
		<div class="container-fluid">
			<div class="card card-default">
				<div class="card-header">
					<h3 class="card-title">Form Edit Pengajuan</h3>
				</div>

				<form method="post" enctype="multipart/form-data">
					<input type="hidden" name="id_pengajuan" value="<?php echo $data['id_pengajuan']; ?>">
					<input type="hidden" name="file_draft_lama" value="<?php echo $data['file_draft']; ?>">

					<div class="card-body">
						<div class="row">
							<div class="col-sm-12">

								<div class="form-group">
									<label>Jenis Dokumen</label>
									<select name="id_jenis" class="form-control" required>
										<option value="">-- Pilih Jenis Dokumen --</option>
										<?php
										$qJenis = mysqli_query($config, "SELECT * FROM tb_jenis_dokumen");
										while ($j = mysqli_fetch_assoc($qJenis)) {
											$selected = ($j['id_jenis'] == $data['id_jenis']) ? 'selected' : '';
											echo "<option value='{$j['id_jenis']}' {$selected}>{$j['nama_jenis']}</option>";
										}
										?>
									</select>
								</div>

								<div class="form-group">
									<label>Judul Dokumen</label>
									<input type="text" name="judul_dokumen" class="form-control"
										   value="<?php echo htmlspecialchars($data['judul_dokumen']); ?>" required>
								</div>

								<div class="form-group">
									<label>Tanggal Dokumen</label>
									<input type="date" name="tanggal_dokumen" class="form-control"
										   value="<?php echo $data['tanggal_dokumen']; ?>" required>
								</div>

								<div class="form-group">
									<label>File Draft (Word) <small>(Kosongkan jika tidak ingin diubah, maks 10MB)</small></label><br>
									<?php if (!empty($data['file_draft'])): ?>
										<a href="../assets/upload/draft_word/<?php echo $data['file_draft']; ?>" 
										   target="_blank" class="btn btn-sm btn-info mb-2">
										   <i class="fas fa-file-word"></i> Lihat File Lama
										</a><br>
									<?php endif; ?>
									<input type="file" name="file_draft" class="form-control-file" accept=".doc,.docx">
								</div>

							</div>
						</div>
					</div>

					<div class="card-footer">
						<a class="btn btn-app bg-warning float-left" href="main_pokja.php?unit=pengajuan">
							<i class="fas fa-reply"></i> Back
						</a>
						<button class="btn btn-app bg-success float-right" type="submit">
							<i class="fas fa-save"></i> UPDATE
						</button>
					</div>
				</form>
			</div>
		</div>
		<br><br>
	</section>
