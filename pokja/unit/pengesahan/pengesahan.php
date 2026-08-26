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

<style>
	.pengesahan-table-wrap {
		width: 100%;
	}

	#pengesahanTable {
		min-width: 1060px;
		margin-bottom: 0 !important;
	}

	#pengesahanTable th,
	#pengesahanTable td {
		vertical-align: middle;
		white-space: nowrap;
	}

	#pengesahanTable td:nth-child(2),
	#pengesahanTable td:nth-child(4) {
		min-width: 220px;
		white-space: normal;
		text-align: left;
	}

	#pengesahanTable td:nth-child(7),
	#pengesahanTable td:nth-child(8) {
		min-width: 130px;
	}

	#pengesahanTable td .btn {
		margin: 2px;
	}

	div.dataTables_wrapper div.dataTables_length,
	div.dataTables_wrapper div.dataTables_filter {
		margin-bottom: .75rem;
	}

	.pengesahan-data-layout {
		align-items: flex-start;
	}

	.pengesahan-section-title {
		font-size: 1.35rem;
		font-weight: 500;
		margin-bottom: 1rem;
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

				<?php
				$chart_labels_pengesahan = [];
				$chart_data_pengesahan = [];
				$chart_details_pengesahan = [];
				$chart_group_notes_pengesahan = [];
				$chart_grouped_pengesahan = [];

				$q_chart_pengesahan = mysqli_query($config, "
					SELECT
						u.kode_pokja,
						u.nama_lengkap,
						COUNT(p.id_pengajuan) AS total_pengesahan
					FROM tb_user u
					LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user AND p.status = 'Selesai'
					WHERE u.level = 'Pokja'
					GROUP BY u.id_user
					ORDER BY u.kode_pokja ASC, u.nama_lengkap ASC
				");

				while ($row_chart = mysqli_fetch_assoc($q_chart_pengesahan)) {
					$kode_chart = $row_chart['kode_pokja'];
					if (!isset($chart_grouped_pengesahan[$kode_chart])) {
						$chart_grouped_pengesahan[$kode_chart] = [
							'label' => $kode_chart,
							'total' => 0,
							'details' => []
						];
					}

					$total_chart = (int)$row_chart['total_pengesahan'];
					$chart_grouped_pengesahan[$kode_chart]['total'] += $total_chart;
					if ($total_chart > 0) {
						$chart_grouped_pengesahan[$kode_chart]['details'][] = $row_chart['nama_lengkap'] . ' (' . $total_chart . ')';
					}
				}

				foreach ($chart_grouped_pengesahan as $item_chart) {
					$details_chart = !empty($item_chart['details']) ? implode(', ', $item_chart['details']) : $item_chart['label'] . ' (0)';
					$chart_labels_pengesahan[] = $item_chart['label'];
					$chart_data_pengesahan[] = $item_chart['total'];
					$chart_details_pengesahan[] = $details_chart;

					if (count($item_chart['details']) > 1) {
						$chart_group_notes_pengesahan[] = $item_chart['label'] . ': ' . $details_chart;
					}
				}
				?>

				<div class="row pengesahan-data-layout">
					<div class="col-lg-7">
						<h4 class="text-center pengesahan-section-title">Tabel Daftar Dokumen yang Telah Disahkan Khusus <b>POKJA <?=strtoupper($_SESSION['kode_pokja']);?></b></h4>
						<div class="pengesahan-table-wrap">
							<table id="pengesahanTable" class="table table-bordered table-striped text-center" style="width: 100%;">
								<thead style="background:rgb(0, 102, 51, 1); color: white;">
									<tr>
										<th>No</th>
										<th style="font-size: 14px;" width="120" responsive>Standard EP</th>
										<th style="font-size: 14px;" width="120" responsive>Jenis Dokumen</th>
										<th style="font-size: 14px;" width="120" responsive>Judul Dokumen</th>
										<th style="font-size: 14px;" width="120" responsive>Tgl Ajuan</th>
										<th style="font-size: 14px;" width="120" responsive>Status</th>
										<th style="font-size: 14px;" width="120" responsive>File Final</th>
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
												<td>{$row['elemen_penilaian']}</td>
												<td>{$row['nama_jenis']}</td>
												<td>{$row['judul_dokumen']}</td>
												<td>{$tgl_ajuan}</td>
												<td><span class='badge badge-primary'>Selesai</span></td>
												<td>";

											$pdfFinal = !empty($row['file_final']) ? $row['file_final'] : '';
											$wordFinal = !empty($row['file_draft']) ? $row['file_draft'] : '';
											if (!empty($pdfFinal) && file_exists('../assets/upload/draft_final/' . $pdfFinal)) {
												echo "<a href='../assets/upload/draft_final/{$pdfFinal}' 
														target='_blank' class='btn btn-sm btn-success' title='Download PDF final'>
														<i class='fas fa-file-pdf'></i> PDF
													</a>";
											}
											if (!empty($wordFinal) && preg_match('/\.(doc|docx)$/i', $wordFinal) && file_exists('../assets/upload/draft_final/' . $wordFinal)) {
												echo "<a href='../assets/upload/draft_final/{$wordFinal}' 
														target='_blank' class='btn btn-sm btn-info' title='Download Word final'>
														<i class='fas fa-file-word'></i> Word
													</a>";
											}
											if ((empty($pdfFinal) || !file_exists('../assets/upload/draft_final/' . $pdfFinal))
												&& (empty($wordFinal) || !preg_match('/\.(doc|docx)$/i', $wordFinal) || !file_exists('../assets/upload/draft_final/' . $wordFinal))) {
												echo "-";
											}

											echo "</td>
												<td>
													<a href='main_pokja.php?unit=detail_pengesahan&id_pengajuan={$row['id_pengajuan']}' 
														class='btn btn-sm btn-info' title='Lihat detail'>
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
					<div class="col-lg-5">
						<!-- <h4 class="text-center pengesahan-section-title">Grafik Pengesahan Dokumen per Pokja</h4> -->
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

<script src="https://code.highcharts.com/highcharts.js"></script>
<script>
	document.addEventListener('DOMContentLoaded', function() {
		if (window.jQuery && $.fn.DataTable && !$.fn.DataTable.isDataTable('#pengesahanTable')) {
			$('#pengesahanTable').DataTable({
				paging: true,
				lengthChange: true,
				searching: true,
				ordering: true,
				info: true,
				autoWidth: false,
				scrollX: true,
				responsive: false,
				columnDefs: [
					{ width: '60px', targets: 0 },
					{ width: '230px', targets: 1 },
					{ width: '150px', targets: 2 },
					{ width: '230px', targets: 3 },
					{ width: '115px', targets: 4 },
					{ width: '100px', targets: 5 },
					{ width: '145px', targets: 6 },
					{ width: '130px', targets: 7, orderable: false }
				]
			});
		}

		if (window.Highcharts && document.getElementById('pengesahanChart')) {
			var detailPengesahan = <?php echo json_encode($chart_details_pengesahan); ?>;
			Highcharts.chart('pengesahanChart', {
				chart: {
					type: 'column'
				},
				title: {
					text: 'Persentase Pengesahan Dokumen Semua Pokja'
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
		}
	});
</script>
