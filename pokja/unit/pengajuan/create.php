<?php
	require_once("../config/koneksi.php"); // Sesuaikan path ke koneksi

	// Ambil data user untuk auto-fill
	$user_id = $_SESSION['id_user'];
	$query_user = mysqli_query($config, "SELECT kode_pokja FROM tb_user WHERE id_user = '$user_id'");
	$user_data = mysqli_fetch_assoc($query_user);
	$kode_pokja = $user_data['kode_pokja'] ?? '';

	// Cek jika form disubmit
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$id_user        = mysqli_real_escape_string($config, $_POST['id_user']);
		$id_jenis       = mysqli_real_escape_string($config, $_POST['id_jenis']);
		$judul_dokumen  = mysqli_real_escape_string($config, $_POST['judul_dokumen']);
		$tanggal_dok    = mysqli_real_escape_string($config, $_POST['tanggal_dokumen']);
		$tanggal_ajuan  = date('Y-m-d'); // otomatis tanggal hari ini
		$catatan        = mysqli_real_escape_string($config, $_POST['catatan']);
		$no_tel         = mysqli_real_escape_string($config, $_POST['no_telepon']);
		$elemen_penilaian_input = mysqli_real_escape_string($config, $_POST['elemen_penilaian']);
		$elemen_penilaian = $kode_pokja . '-' . $elemen_penilaian_input; // gabungkan kode_pokja dengan input user
		$status         = 'Menunggu Verifikasi'; // status awal

		// Proses upload file draft (Word) dengan error handling yang lebih baik
		if (!isset($_FILES['file_draft']) || $_FILES['file_draft']['error'] !== UPLOAD_ERR_OK) {
			echo "<script>
					alert('Error: Tidak ada file yang dipilih atau terjadi kesalahan upload!');
					window.location = 'main_pokja.php?unit=create_pengajuan';
				  </script>";
			exit;
		}

		$allowed_ext = ['doc', 'docx'];
		$file_name   = $_FILES['file_draft']['name'];
		$file_tmp    = $_FILES['file_draft']['tmp_name'];
		$file_size   = $_FILES['file_draft']['size'];
		$file_ext    = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
		$upload_dir  = '../assets/upload/draft_word/';

		// Validasi ekstensi file
		if (!in_array($file_ext, $allowed_ext)) {
			echo "<script>
					alert('Format file tidak valid! Hanya diperbolehkan .doc atau .docx. File yang diupload: $file_name');
					window.location = 'main_pokja.php?unit=create_pengajuan';
				  </script>";
			exit;
		}

		// Validasi ukuran file (max 10MB)
		$max_size = 10 * 1024 * 1024; // 10MB
		if ($file_size > $max_size) {
			echo "<script>
					alert('Ukuran file terlalu besar! Maksimal 10MB. Ukuran file: ' + Math.round($file_size/1024/1024*100)/100 + 'MB');
					window.location = 'main_pokja.php?unit=create_pengajuan';
				  </script>";
			exit;
		}

		// Cek apakah folder upload ada dan writable
		if (!is_dir($upload_dir)) {
			echo "<script>
					alert('Error: Folder upload tidak ditemukan! Hubungi administrator.');
					window.location = 'main_pokja.php?unit=create_pengajuan';
				  </script>";
			exit;
		}

		if (!is_writable($upload_dir)) {
			echo "<script>
					alert('Error: Folder upload tidak writable! Hubungi administrator.');
					window.location = 'main_pokja.php?unit=create_pengajuan';
				  </script>";
			exit;
		}

		// Buat nama file unik dengan prefix timestamp dan user id
		$new_filename = 'draft_' . time() . '_' . $id_user . '.' . $file_ext;
		$full_path = $upload_dir . $new_filename;

		// Pindahkan file ke folder tujuan
		if (move_uploaded_file($file_tmp, $full_path)) {
			// Simpan ke database
			$query = "INSERT INTO tb_pengajuan_dokumen
					  (id_user, id_jenis, judul_dokumen, file_draft, tanggal_dokumen, tanggal_ajuan, catatan, no_tlp, elemen_penilaian, status)
					  VALUES
					  ('$id_user', '$id_jenis', '$judul_dokumen', '$new_filename', '$tanggal_dok', '$tanggal_ajuan', '$catatan', '$no_tel', '$elemen_penilaian', '$status')";
			
			if (mysqli_query($config, $query)) {
				echo "<script>
						alert('Pengajuan dokumen berhasil disimpan! File tersimpan sebagai: $new_filename');
						window.location = 'main_pokja.php?unit=pengajuan';
					  </script>";
			} else {
				// Hapus file yang sudah terupload jika insert gagal
				if (file_exists($full_path)) {
					unlink($full_path);
				}
				echo "<script>
						alert('Gagal menyimpan data ke database: " . mysqli_error($config) . "');
						window.location = 'main_pokja.php?unit=create_pengajuan';
					  </script>";
			}
		} else {
			echo "<script>
					alert('Gagal mengupload file draft! Kemungkinan penyebab:\\n- Folder tidak writable\\n- Space disk penuh\\n- File corromped\\n\\nDetail error: File tmp: $file_tmp, Target: $full_path');
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
						<div class="col-sm-6">
							<div class="form-group">
								<label>Standard EP</label>
								<div class="input-group">
									<span class="input-group-text"><i class="fas fa-tags"></i></span>
									<input type="text" class="form-control" value="<?php echo $kode_pokja; ?>" readonly style="background-color: #e9ecef; font-weight: bold;">
									<span class="input-group-text">-</span>
									<input type="text" name="elemen_penilaian" class="form-control" placeholder="Contoh: 1 EP 3" required>
								</div>
								<small class="form-text text-muted"><b>Note:</b> pokja "<?php echo $kode_pokja; ?>" sudah otomatis.Jadi sisanya masukkan nomor dan EP berapa dan jika berhubungan dengan Standard EP lain, gunakan format (1 EP 3 dan 1 EP 4)</small>
							</div>
						</div>
						<div class="col-sm-6">
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
						</div>
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
								<label>Nomor Telepon</label>
								<div class="input-group">
									<span class="input-group-text"><i class="fas fa-phone"></i></span>
									<input type="text" name="no_telepon" class="form-control" placeholder="Contoh: 08123456789" required>
								</div>
								<small class="form-text text-muted">Masukkan nomor telepon yang dapat dihubungi untuk konfirmasi.</small>
							</div>

							<div class="form-group">
								<label>Catatan</label>
								<div class="input-group">
									<textarea name="catatan" class="form-control" rows="3" placeholder="Opsional: tulis ringkasan, tujuan, atau catatan penting untuk tim verifikasi"></textarea>
								</div>
								<small class="form-text text-muted">Opsional: tulis ringkasan, tujuan, atau catatan penting untuk tim verifikasi.</small>
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
	<br><br>
</section>
