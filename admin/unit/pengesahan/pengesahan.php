<?php
require_once("../config/upload_helper.php");

$q_tanggal_pengesahan = mysqli_query($config, "SHOW COLUMNS FROM tb_pengajuan_dokumen LIKE 'tanggal_pengesahan'");
$has_tanggal_pengesahan = $q_tanggal_pengesahan && mysqli_num_rows($q_tanggal_pengesahan) > 0;
$tanggal_pengesahan_sql = $has_tanggal_pengesahan
	? 'COALESCE(p.tanggal_pengesahan, p.tanggal_disetujui, p.tanggal_ajuan)'
	: 'COALESCE(p.tanggal_disetujui, p.tanggal_ajuan)';

function hapus_file_pengesahan($filename)
{
	if (empty($filename)) {
		return true;
	}

	$safeFilename = basename($filename);
	$uploadDirs = [
		__DIR__ . '/../../../assets/upload/draft_final',
		__DIR__ . '/../../../assets/upload/draft_word',
		__DIR__ . '/../../../assets/upload',
	];

	foreach ($uploadDirs as $uploadDir) {
		$uploadDirReal = realpath($uploadDir);
		if ($uploadDirReal === false) {
			continue;
		}

		$filePath = $uploadDirReal . DIRECTORY_SEPARATOR . $safeFilename;
		$filePathReal = realpath($filePath);
		if ($filePathReal === false || !is_file($filePathReal)) {
			continue;
		}

		$allowedPrefix = rtrim($uploadDirReal, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		if (stripos($filePathReal, $allowedPrefix) !== 0) {
			continue;
		}

		if (!unlink($filePathReal)) {
			return false;
		}
	}

	return true;
}

function nama_file_final_pengesahan($data, $id_pengajuan)
{
	$tanggal = !empty($data['tanggal_dokumen']) ? date('Ymd', strtotime($data['tanggal_dokumen'])) : date('Ymd');
	$parts = [
		$data['elemen_penilaian'] ?? '',
		$tanggal,
		$data['nomor_surat'] ?? ''
	];
	$base = preg_replace('/[^a-zA-Z0-9]+/', '_', implode('_', $parts));
	$base = trim($base, '_');

	return $base !== '' ? $base : 'dokumen_final_' . $id_pengajuan;
}

function simpan_file_final_pengesahan($file, $uploadDir, $filenameBase, $allowedExtensions, $allowedMimes, $maxBytes)
{
	$validation = app_validate_upload($file, $allowedExtensions, $allowedMimes, $maxBytes);
	if (!$validation['ok']) {
		return $validation;
	}

	if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true)) {
		return ['ok' => false, 'message' => 'Folder draft_final tidak dapat dibuat.'];
	}

	if (!is_writable($uploadDir)) {
		return ['ok' => false, 'message' => 'Folder draft_final tidak dapat ditulis.'];
	}

	$filename = $filenameBase . '.' . $validation['extension'];
	$destination = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR . $filename;

	if (file_exists($destination) && !unlink($destination)) {
		return ['ok' => false, 'message' => 'File final lama tidak dapat diganti.'];
	}

	if (!move_uploaded_file($file['tmp_name'], $destination)) {
		return ['ok' => false, 'message' => 'Gagal menyimpan file final.'];
	}

	return ['ok' => true, 'filename' => $filename, 'path' => $destination];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_final_pengesahan'])) {
	$id_pengajuan = isset($_POST['id_pengajuan']) ? mysqli_real_escape_string($config, $_POST['id_pengajuan']) : '';

	if ($id_pengajuan === '') {
		header('Location: main_admin.php?unit=pengesahan&err=ID pengesahan tidak ditemukan!');
		exit;
	}

	$q_edit = mysqli_query($config, "
		SELECT id_pengajuan, elemen_penilaian, tanggal_dokumen, nomor_surat, file_draft, file_final
		FROM tb_pengajuan_dokumen
		WHERE id_pengajuan = '$id_pengajuan' AND status = 'Selesai'
	");

	if (!$q_edit || mysqli_num_rows($q_edit) === 0) {
		header('Location: main_admin.php?unit=pengesahan&err=Data pengesahan tidak ditemukan atau belum berstatus Selesai!');
		exit;
	}

	$data_edit = mysqli_fetch_assoc($q_edit);
	$uploadDir = '../assets/upload/draft_final/';
	$filenameBase = nama_file_final_pengesahan($data_edit, $id_pengajuan);
	$uploadWord = null;
	$uploadPdf = null;
	$word_name = $data_edit['file_draft'];
	$pdf_name = $data_edit['file_final'];

	if (!empty($_FILES['file_word_final']['name'])) {
		$uploadWord = simpan_file_final_pengesahan(
			$_FILES['file_word_final'],
			$uploadDir,
			$filenameBase,
			['doc', 'docx'],
			[
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/zip',
				'application/octet-stream'
			],
			15 * 1024 * 1024
		);

		if (!$uploadWord['ok']) {
			header('Location: main_admin.php?unit=pengesahan&err=' . urlencode($uploadWord['message']));
			exit;
		}

		$word_name = $uploadWord['filename'];
	}

	if (!empty($_FILES['file_pdf_final']['name'])) {
		$uploadPdf = simpan_file_final_pengesahan(
			$_FILES['file_pdf_final'],
			$uploadDir,
			$filenameBase,
			['pdf'],
			['application/pdf', 'application/octet-stream'],
			15 * 1024 * 1024
		);

		if (!$uploadPdf['ok']) {
			if (!empty($uploadWord['filename'])) {
				hapus_file_pengesahan($uploadWord['filename']);
			}
			header('Location: main_admin.php?unit=pengesahan&err=' . urlencode($uploadPdf['message']));
			exit;
		}

		$pdf_name = $uploadPdf['filename'];
	}

	if ($uploadWord && !empty($data_edit['file_draft']) && $data_edit['file_draft'] !== $word_name) {
		hapus_file_pengesahan($data_edit['file_draft']);
	}
	if ($uploadPdf && !empty($data_edit['file_final']) && $data_edit['file_final'] !== $pdf_name) {
		hapus_file_pengesahan($data_edit['file_final']);
	}

	$word_name = mysqli_real_escape_string($config, $word_name);
	$pdf_name = mysqli_real_escape_string($config, $pdf_name);
	$update = mysqli_query($config, "
		UPDATE tb_pengajuan_dokumen
		SET file_draft = '$word_name',
			file_final = '$pdf_name'
		WHERE id_pengajuan = '$id_pengajuan' AND status = 'Selesai'
	");

	if ($update) {
		header('Location: main_admin.php?unit=pengesahan&msg=File final Word dan PDF berhasil diperbarui!');
		exit;
	}

	$errMsg = urlencode('Gagal memperbarui file final: ' . mysqli_error($config));
	header('Location: main_admin.php?unit=pengesahan&err=' . $errMsg);
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pengesahan'])) {
	$id_pengajuan = isset($_POST['id_pengajuan']) ? mysqli_real_escape_string($config, $_POST['id_pengajuan']) : '';

	if ($id_pengajuan === '') {
		header('Location: main_admin.php?unit=pengesahan&err=ID pengesahan tidak ditemukan!');
		exit;
	}

	$q_hapus = mysqli_query($config, "
		SELECT id_pengajuan, file_draft, file_final
		FROM tb_pengajuan_dokumen
		WHERE id_pengajuan = '$id_pengajuan' AND status = 'Selesai'
	");

	if (!$q_hapus || mysqli_num_rows($q_hapus) === 0) {
		header('Location: main_admin.php?unit=pengesahan&err=Data pengesahan tidak ditemukan atau belum berstatus Selesai!');
		exit;
	}

	$data_hapus = mysqli_fetch_assoc($q_hapus);
	$files = array_unique(array_filter([$data_hapus['file_draft'], $data_hapus['file_final']]));
	$file_deleted = true;

	foreach ($files as $file) {
		if (!hapus_file_pengesahan($file)) {
			$file_deleted = false;
			break;
		}
	}

	if (!$file_deleted) {
		header('Location: main_admin.php?unit=pengesahan&err=Gagal menghapus file PDF terkait. Periksa izin folder upload!');
		exit;
	}

	$delete = mysqli_query($config, "
		DELETE FROM tb_pengajuan_dokumen
		WHERE id_pengajuan = '$id_pengajuan' AND status = 'Selesai'
	");

	if ($delete) {
		header('Location: main_admin.php?unit=pengesahan&msg=Data pengesahan dan file PDF berhasil dihapus!');
		exit;
	}

	$errMsg = urlencode('Gagal menghapus data pengesahan: ' . mysqli_error($config));
	header('Location: main_admin.php?unit=pengesahan&err=' . $errMsg);
	exit;
}

$filter_pokja_input = isset($_GET['kode_pokja']) && is_scalar($_GET['kode_pokja'])
	? (string) $_GET['kode_pokja']
	: '';
$filter_pokja = ctype_digit($filter_pokja_input)
	? (int) $filter_pokja_input
	: 0;
$filter_standard_ep = isset($_GET['standard_ep']) && is_string($_GET['standard_ep'])
	? trim($_GET['standard_ep'])
	: '';

$q_standard_ep = mysqli_query($config, "
	SELECT DISTINCT p.id_user, p.elemen_penilaian
	FROM tb_pengajuan_dokumen p
	WHERE p.status = 'Selesai'
		AND p.elemen_penilaian IS NOT NULL
		AND TRIM(p.elemen_penilaian) != ''
	ORDER BY p.id_user ASC, p.elemen_penilaian ASC
");

$standard_ep_per_pokja = [];
$semua_standard_ep = [];
if ($q_standard_ep) {
	while ($standard = mysqli_fetch_assoc($q_standard_ep)) {
		$id_pokja_standard = (string) ((int) $standard['id_user']);
		$nilai_standard = $standard['elemen_penilaian'];
		$standard_ep_per_pokja[$id_pokja_standard][] = $nilai_standard;
		$semua_standard_ep[$nilai_standard] = $nilai_standard;
	}
}

foreach ($standard_ep_per_pokja as &$daftar_standard) {
	$daftar_standard = array_values(array_unique($daftar_standard));
	natcasesort($daftar_standard);
	$daftar_standard = array_values($daftar_standard);
}
unset($daftar_standard);

$semua_standard_ep = array_values($semua_standard_ep);
natcasesort($semua_standard_ep);
$semua_standard_ep = array_values($semua_standard_ep);

$standard_ep_tersedia = $filter_pokja > 0
	? ($standard_ep_per_pokja[(string) $filter_pokja] ?? [])
	: $semua_standard_ep;

if ($filter_standard_ep !== '' && !in_array($filter_standard_ep, $standard_ep_tersedia, true)) {
	$filter_standard_ep = '';
}

$filter_standard_ep_sql = mysqli_real_escape_string($config, $filter_standard_ep);
?>

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

<script>
function kirimWA(idPengajuan, noTel, kodePokja, nomorSurat, judulDokumen, namaJenis, tanggalDokumen) {
		  if (!noTel || noTel.trim() === '') {
		      alert('Nomor telepon tidak tersedia untuk pengajuan ini.');
		      return;
		  }

		  // Format nomor telepon (pastikan dimulai dengan 62)
		  var noTelFormatted = noTel.trim();
		  if (noTelFormatted.startsWith('08')) {
		      noTelFormatted = '62' + noTelFormatted.substring(1);
		  } else if (noTelFormatted.startsWith('+62')) {
		      noTelFormatted = noTelFormatted.substring(1);
		  } else if (!noTelFormatted.startsWith('62')) {
		      noTelFormatted = '62' + noTelFormatted;
		  }

		  // Format tanggal pengajuan
		  var tanggalPengajuan = new Date().toLocaleDateString('id-ID', {
		      day: 'numeric',
		      month: 'long',
		      year: 'numeric'
		  });

		  // Pesan WA
		  var pesan = encodeURIComponent(
		      'Halo Pokja ' + kodePokja + ',\n\n' +
		      'Berikut ringkasan pengajuannya:\n\n' +
		      'No surat\t: ' + nomorSurat + '\n' +
		      'Judul Dokumen\t: ' + judulDokumen + '\n' +
		      'Jenis Dokumen\t: ' + namaJenis + '\n' +
		      'Tanggal Pengajuan\t: ' + tanggalPengajuan + '\n\n' +
		      'Dokumen tersebut telah "SELESAI" diproses. Silakan cek dokumen anda http://192.168.1.108/app_no-surat untuk informasi lebih lanjut.\n\n' +
		      'Terima kasih.'
		  );

		  // Buka WhatsApp Web
		  var url = 'https://wa.me/' + noTelFormatted + '?text=' + pesan;
		  window.open(url, '_blank');
}
</script>

<style>
	.pengesahan-data-layout {
		align-items: flex-start;
	}

	.pengesahan-section-title {
		font-size: 1.35rem;
		font-weight: 500;
		margin-bottom: 1rem;
	}

	.pengesahan-table-scroll {
		width: 100%;
	}

	#example0 {
		min-width: 980px;
		margin-bottom: 0 !important;
	}

	#example0 th,
	#example0 td {
		vertical-align: middle;
		white-space: nowrap;
	}

	#example0 td:nth-child(4) {
		min-width: 190px;
		white-space: normal;
	}

	#example0 td:last-child {
		min-width: 132px;
	}

	#example0 td:last-child .btn {
		margin: 2px;
	}

	div.dataTables_wrapper div.dataTables_length,
	div.dataTables_wrapper div.dataTables_filter {
		margin-bottom: .75rem;
	}

	div.dataTables_scroll {
		border: 1px solid #d9dee3;
		border-radius: 6px;
		overflow: hidden;
	}

	div.dataTables_scrollHead table.dataTable,
	div.dataTables_scrollBody table.dataTable {
		margin-bottom: 0 !important;
	}

	.pengesahan-chart-wrap {
		min-height: 430px;
	}

	.pengesahan-chart-note {
		margin-top: .75rem;
		font-size: .9rem;
		color: #4d5965;
	}

	.pengesahan-chart-note ul {
		margin: .35rem 0 0 1.1rem;
		padding: 0;
	}

	.pengesahan-date {
		display: block;
		margin-top: .35rem;
		color: #667085;
		font-size: .78rem;
		line-height: 1.35;
	}

	.pengesahan-new-badge {
		display: inline-flex;
		align-items: center;
		margin-top: .4rem;
		padding: .28rem .5rem;
		border-radius: 4px;
		background: #dcfce7;
		color: #166534;
		font-size: .72rem;
		font-weight: 700;
	}

	@media (max-width: 991.98px) {
		.pengesahan-chart-wrap {
			margin-top: 1.5rem;
		}
	}
</style>

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
				<!-- FILTER POKJA DAN STANDARD EP -->
				<form method="GET" action="" class="mb-5">
					<input type="hidden" name="unit" value="pengesahan">
					<div class="form-row align-items-end">
						<div class="form-group col-md-4">
							<label for="kode_pokja">Kode Pokja</label>
							<select name="kode_pokja" id="kode_pokja" class="form-control" style="border: 2px solid #009688; background-color:#e0f2f1; color:#004d40;">
								<option value="">-- Semua Pokja --</option>
								<?php
								$q_pokja = mysqli_query($config, "SELECT id_user, kode_pokja, nama_lengkap FROM tb_user WHERE level = 'Pokja' ORDER BY kode_pokja ASC, nama_lengkap ASC");
								while ($p = mysqli_fetch_assoc($q_pokja)) {
									$selected = ($filter_pokja === (int) $p['id_user']) ? 'selected' : '';
									$label_pokja = $p['kode_pokja'] . ' - ' . $p['nama_lengkap'];
									echo "<option value='" . htmlspecialchars($p['id_user']) . "' $selected>" . htmlspecialchars($label_pokja) . "</option>";
								}
								?>
							</select>
						</div>
						<div class="form-group col-md-4">
							<label for="standard_ep">Standard EP</label>
							<select name="standard_ep" id="standard_ep" class="form-control" style="border: 2px solid #009688; background-color:#e0f2f1; color:#004d40;">
								<option value=""><?= $filter_pokja > 0 && empty($standard_ep_tersedia) ? '-- Belum ada Standard EP untuk Pokja ini --' : '-- Semua Standard EP --'; ?></option>
								<?php foreach ($standard_ep_tersedia as $standard): ?>
									<?php $selected = ($filter_standard_ep === $standard) ? 'selected' : ''; ?>
									<option value="<?= htmlspecialchars($standard); ?>" <?= $selected; ?>><?= htmlspecialchars($standard); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="form-group col-md-2">
							<button type="submit" class="btn btn-success btn-block">
								<i class="fas fa-filter"></i> Tampilkan
							</button>
						</div>
						<div class="form-group col-md-2">
							<a href="main_admin.php?unit=pengesahan" class="btn btn-secondary btn-block">
								<i class="fas fa-sync"></i> Reset
							</a>
						</div>
					</div>
				</form>

				<!-- Statistik Pengesahan per Pokja -->
				<?php
				// Data untuk chart pengesahan
				$chart_labels_pengesahan = [];
				$chart_data_pengesahan = [];
				$chart_details_pengesahan = [];
				$chart_group_notes_pengesahan = [];
				$where_chart = "WHERE u.level = 'Pokja'";
				$is_filter_pokja = $filter_pokja > 0;
				$join_filter_standard = $filter_standard_ep !== ''
					? " AND p.elemen_penilaian = '$filter_standard_ep_sql'"
					: '';
				if ($is_filter_pokja) {
					$where_chart .= " AND u.id_user = $filter_pokja";
					$q_chart_pengesahan = mysqli_query($config, "
						SELECT
							u.kode_pokja,
							u.nama_lengkap,
							COUNT(p.id_pengajuan) AS total_pengesahan
						FROM tb_user u
						LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user AND p.status = 'Selesai'$join_filter_standard
						$where_chart
						GROUP BY u.id_user
						ORDER BY total_pengesahan DESC
					");
				} else {
					$q_chart_pengesahan = mysqli_query($config, "
						SELECT
							u.kode_pokja,
							u.nama_lengkap,
							COUNT(p.id_pengajuan) AS total_pengesahan
						FROM tb_user u
						LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user AND p.status = 'Selesai'$join_filter_standard
						$where_chart
						GROUP BY u.id_user
						ORDER BY u.kode_pokja ASC, u.nama_lengkap ASC
					");
				}

				$chart_grouped_pengesahan = [];
				while ($row = mysqli_fetch_assoc($q_chart_pengesahan)) {
					$kode = $row['kode_pokja'];
					if (!isset($chart_grouped_pengesahan[$kode])) {
						$chart_grouped_pengesahan[$kode] = [
							'label' => $kode,
							'total' => 0,
							'details' => []
						];
					}

					$total_pengesahan = (int)$row['total_pengesahan'];
					$chart_grouped_pengesahan[$kode]['total'] += $total_pengesahan;
					if ($is_filter_pokja || $total_pengesahan > 0) {
						$chart_grouped_pengesahan[$kode]['details'][] = $row['nama_lengkap'] . ' (' . $total_pengesahan . ')';
					}
				}

				foreach ($chart_grouped_pengesahan as $item_chart) {
					$details = !empty($item_chart['details']) ? implode(', ', $item_chart['details']) : $item_chart['label'] . ' (0)';
					$chart_labels_pengesahan[] = $item_chart['label'];
					$chart_data_pengesahan[] = $item_chart['total'];
					$chart_details_pengesahan[] = $details;

					if (count($item_chart['details']) > 1) {
						$chart_group_notes_pengesahan[] = $item_chart['label'] . ': ' . $details;
					}
				}
				?>

				<div class="row pengesahan-data-layout">
					<div class="col-lg-7">
						<h4 class="text-center pengesahan-section-title">Tabel Daftar Dokumen yang Telah Disahkan</h4>
						<!-- TABEL DATA -->
						<div class="pengesahan-table-scroll">
							<table id="example0" class="table table-bordered table-striped" style="width: 100%;">
								<thead style="background:rgb(0, 102, 51, 1); color: white;">
									<tr>
										<th>No</th>
										<th>No Dokumen</th>
										<th>Jenis Dokumen</th>
										<th>Judul Dokumen</th>
										<th>Tgl Dokumen</th>
										<th>Pokja</th>
										<th>Standard EP</th>
										<th>Status</th>
										<!-- <th>File PDF Final</th> -->
									</tr>
								</thead>
								<tbody>
									<?php
									$no = 1;
									$modal_edit_final = '';
									$where = "WHERE p.status = 'Selesai'";

									if ($filter_pokja > 0) {
										$where .= " AND p.id_user = $filter_pokja";
									}

									if ($filter_standard_ep !== '') {
										$where .= " AND p.elemen_penilaian = '$filter_standard_ep_sql'";
									}

									$query = mysqli_query($config, "
										SELECT p.*, j.nama_jenis, k.kode_pokja, k.nama_lengkap,
											$tanggal_pengesahan_sql AS tanggal_pengesahan_efektif
										FROM tb_pengajuan_dokumen p
										LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
										LEFT JOIN tb_user k ON p.id_user = k.id_user
										$where
										ORDER BY tanggal_pengesahan_efektif DESC, p.id_pengajuan DESC
									");

									if (mysqli_num_rows($query) > 0) {
										while ($row = mysqli_fetch_assoc($query)) {
											$tgl_dok = date('d-m-Y', strtotime($row['tanggal_dokumen']));
											$tanggal_pengesahan = !empty($row['tanggal_pengesahan_efektif'])
												? date('d-m-Y', strtotime($row['tanggal_pengesahan_efektif']))
												: '-';
											$is_baru = !empty($row['tanggal_pengesahan_efektif'])
												&& strtotime($row['tanggal_pengesahan_efektif']) >= strtotime('-30 days');
											$jenis_dokumen_html = htmlspecialchars($row['nama_jenis'])
												. "<span class='pengesahan-date'><i class='far fa-calendar-check mr-1'></i>Disahkan {$tanggal_pengesahan}</span>"
												. ($is_baru ? "<span class='pengesahan-new-badge'><i class='fas fa-star mr-1'></i>Baru</span>" : '');
											echo "<tr>
												<td>{$no}</td>
												<td>
													{$row['nomor_surat']}
													<br><br>
													<a href='main_admin.php?unit=detail_pengesahan&id_pengajuan={$row['id_pengajuan']}' class='btn btn-sm btn-info' title='Lihat detail'>
														<i class='fas fa-eye'></i>
													</a>
													<button type='button' class='btn btn-sm btn-warning' title='Edit file final' data-toggle='modal' data-target='#modalEditFinal{$row['id_pengajuan']}'>
														<i class='fas fa-edit'></i>
													</button>
													<button type='button' class='btn btn-sm btn-success' title='Kirim WhatsApp' onclick='kirimWA({$row['id_pengajuan']}, \"{$row['no_tlp']}\", \"{$row['kode_pokja']}\", \"{$row['nomor_surat']}\", \"{$row['judul_dokumen']}\", \"{$row['nama_jenis']}\", \"{$row['tanggal_dokumen']}\")'>
														<i class='fab fa-whatsapp'></i>
													</button>
													<form method='POST' class='d-inline' onsubmit=\"return confirm('Apakah Anda ingin menghapus data ini? Data dan file PDF terkait akan dihapus permanen.');\">
														<input type='hidden' name='id_pengajuan' value='{$row['id_pengajuan']}'>
														<button type='submit' name='hapus_pengesahan' class='btn btn-sm btn-danger' title='Hapus data'>
															<i class='fas fa-trash'></i>
														</button>
													</form>
												</td>
												<td>{$jenis_dokumen_html}</td>
												<td>{$row['judul_dokumen']}</td>
												<td>{$tgl_dok}</td>
												<td>{$row['kode_pokja']}<br><small>{$row['nama_lengkap']}</small></td>
												<td>" . htmlspecialchars($row['elemen_penilaian']) . "</td>
												<td><span class='badge badge-primary'>Selesai</span></td>
											</tr>";
											$modal_edit_final .= "
											<div class='modal fade' id='modalEditFinal{$row['id_pengajuan']}' tabindex='-1' role='dialog' aria-labelledby='modalEditFinalLabel{$row['id_pengajuan']}' aria-hidden='true'>
												<div class='modal-dialog modal-dialog-centered' role='document'>
													<div class='modal-content'>
														<form method='POST' enctype='multipart/form-data' onsubmit=\"return confirm('Update file final Word dan PDF dokumen ini?');\">
															<div class='modal-header bg-warning'>
																<h5 class='modal-title' id='modalEditFinalLabel{$row['id_pengajuan']}'>
																	<i class='fas fa-edit'></i> Edit File Final
																</h5>
																<button type='button' class='close' data-dismiss='modal' aria-label='Close'>
																	<span aria-hidden='true'>&times;</span>
																</button>
															</div>
															<div class='modal-body'>
																<input type='hidden' name='id_pengajuan' value='{$row['id_pengajuan']}'>
																<div class='form-group'>
																	<label>Nomor Dokumen</label>
																	<input type='text' class='form-control' value='" . htmlspecialchars($row['nomor_surat']) . "' readonly>
																</div>
																<div class='form-group'>
																	<label>Judul Dokumen</label>
																	<input type='text' class='form-control' value='" . htmlspecialchars($row['judul_dokumen']) . "' readonly>
																</div>
																<div class='form-group'>
																	<label>File Word Final</label>
																	<input type='file' name='file_word_final' class='form-control-file' accept='.doc,.docx'>
																	<small class='form-text text-muted'>Kosongkan jika Word final tidak ingin diubah.</small>
																</div>
																<div class='form-group'>
																	<label>File PDF Final</label>
																	<input type='file' name='file_pdf_final' class='form-control-file' accept='application/pdf'>
																	<small class='form-text text-muted'>Kosongkan jika PDF final tidak ingin diubah. File akan disimpan ke folder draft_final.</small>
																</div>
															</div>
															<div class='modal-footer'>
																<button type='button' class='btn btn-secondary' data-dismiss='modal'>Batal</button>
																<button type='submit' name='edit_final_pengesahan' class='btn btn-warning'>
																	<i class='fas fa-save'></i> Update File Final
																</button>
															</div>
														</form>
													</div>
												</div>
											</div>";
											$no++;
										}
									} else {
										echo "<tr><td colspan='8'><em>Tidak ada dokumen yang telah disahkan</em></td></tr>";
									}
									?>
								</tbody>
							</table>
							<?= $modal_edit_final; ?>
						</div>
					</div>
					<div class="col-lg-5">
						<h4 class="text-center pengesahan-section-title">Grafik Pengesahan Dokumen per Pokja</h4>
						<div class="pengesahan-chart-wrap">
							<div id="pengesahanChart" style="width:100%; height:400px;"></div>
						</div>
						<?php if (!empty($chart_group_notes_pengesahan)) { ?>
							<div class="pengesahan-chart-note">
								<strong>Rincian pokja dengan beberapa program:</strong>
								<ul>
									<?php foreach ($chart_group_notes_pengesahan as $note_pengesahan) { ?>
										<li><?= htmlspecialchars($note_pengesahan); ?></li>
									<?php } ?>
								</ul>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<script src="https://code.jquery.com/jquery-1.9.1.min.js"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
var standardEpPerPokja = <?= json_encode($standard_ep_per_pokja, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
var semuaStandardEp = <?= json_encode($semua_standard_ep, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

function sinkronkanStandardEp(pertahankanPilihan) {
    var pokjaSelect = document.getElementById('kode_pokja');
    var standardSelect = document.getElementById('standard_ep');
    if (!pokjaSelect || !standardSelect) {
        return;
    }

    var pilihanSebelumnya = pertahankanPilihan ? standardSelect.value : '';
    var daftarStandard = pokjaSelect.value
        ? (standardEpPerPokja[pokjaSelect.value] || [])
        : semuaStandardEp;

    standardSelect.innerHTML = '';
    var placeholder = pokjaSelect.value && daftarStandard.length === 0
        ? '-- Belum ada Standard EP untuk Pokja ini --'
        : '-- Semua Standard EP --';
    standardSelect.add(new Option(placeholder, ''));

    daftarStandard.forEach(function(standard) {
        standardSelect.add(new Option(standard, standard));
    });

    standardSelect.value = daftarStandard.indexOf(pilihanSebelumnya) !== -1
        ? pilihanSebelumnya
        : '';
}

$(document).ready(function() {
    $('#kode_pokja').on('change', function() {
        sinkronkanStandardEp(false);
    });
    sinkronkanStandardEp(true);

    $('#example0').DataTable({
        "pageLength": 10,
        "scrollX": true,
        "autoWidth": false,
		"order": [],
        "columnDefs": [
            { "width": "60px", "targets": 0 },
            { "width": "180px", "targets": 1 },
            { "width": "130px", "targets": 2 },
            { "width": "210px", "targets": 3 },
            { "width": "115px", "targets": 4 },
            { "width": "100px", "targets": 5 },
            { "width": "150px", "targets": 6 },
            { "width": "95px", "targets": 7 }
        ],
        "lengthMenu": [[5, 10, 25, 50], [5, 10, 25, 50]],
        "language": {
            "lengthMenu": "Tampilkan _MENU_ entri per halaman",
            "zeroRecords": "Tidak ada data yang ditemukan",
            "info": "Menampilkan halaman _PAGE_ dari _PAGES_",
            "infoEmpty": "Tidak ada data tersedia",
            "infoFiltered": "(difilter dari _MAX_ total entri)",
            "search": "Cari:",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        }
    });
});

console.log('Labels Pengesahan:', <?php echo json_encode($chart_labels_pengesahan); ?>);
console.log('Data Pengesahan:', <?php echo json_encode($chart_data_pengesahan); ?>);
var detailPengesahan = <?php echo json_encode($chart_details_pengesahan); ?>;
Highcharts.chart('pengesahanChart', {
    chart: {
        type: 'column'
    },
    title: {
        text: 'Persentase Pengesahan Dokumen per Pokja'
    },
    xAxis: {
        categories: <?php echo json_encode($chart_labels_pengesahan); ?>,
        title: {
            text: 'Nama Pokja'
        }
    },
    yAxis: {
        title: {
            text: 'Total Pengesahan Dokumen'
        },
        allowDecimals: false
    },
    series: [{
        name: 'Total Dokumen yang Telah Disahkan',
        data: <?php echo json_encode($chart_data_pengesahan); ?>,
        color: '#006633'
    }],
    tooltip: {
        formatter: function() {
            var detail = detailPengesahan[this.point.index] || this.x;
            return '<b>' + this.x + '</b><br>Total: <b>' + this.y + '</b><br>' + detail;
        }
    },
    credits: {
        enabled: false
    }
});
</script>
