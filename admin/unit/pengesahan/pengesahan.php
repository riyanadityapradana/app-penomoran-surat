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
		overflow-x: auto;
		overflow-y: hidden;
		width: 100%;
	}

	.pengesahan-table-scroll::-webkit-scrollbar,
	div.dataTables_scrollBody::-webkit-scrollbar {
		height: 10px;
	}

	.pengesahan-table-scroll::-webkit-scrollbar-track,
	div.dataTables_scrollBody::-webkit-scrollbar-track {
		background: #eef1f4;
		border-radius: 999px;
	}

	.pengesahan-table-scroll::-webkit-scrollbar-thumb,
	div.dataTables_scrollBody::-webkit-scrollbar-thumb {
		background: #8c98a4;
		border-radius: 999px;
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
				<!-- FILTER POKJA -->
				<form method="GET" action="" class="mb-5">
					<input type="hidden" name="unit" value="pengesahan">
					<div class="form-group row">
						<label for="kode_pokja" class="col-sm-2 col-form-label text-left">Cari Berdasarkan:</label>
						<div class="col-sm-6">
							<select name="kode_pokja" id="kode_pokja" class="form-control" style="border: 2px solid #009688; background-color:#e0f2f1; color:#004d40;">
								<option value="">-- Semua Pokja --</option>
								<?php
								$q_pokja = mysqli_query($config, "SELECT id_user, kode_pokja, nama_lengkap FROM tb_user WHERE level = 'Pokja' ORDER BY kode_pokja ASC, nama_lengkap ASC");
								while ($p = mysqli_fetch_assoc($q_pokja)) {
									$selected = (isset($_GET['kode_pokja']) && $_GET['kode_pokja'] == $p['id_user']) ? 'selected' : '';
									$label_pokja = $p['kode_pokja'] . ' - ' . $p['nama_lengkap'];
									echo "<option value='" . htmlspecialchars($p['id_user']) . "' $selected>" . htmlspecialchars($label_pokja) . "</option>";
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

				<!-- Statistik Pengesahan per Pokja -->
				<?php
				// Data untuk chart pengesahan
				$chart_labels_pengesahan = [];
				$chart_data_pengesahan = [];
				$chart_details_pengesahan = [];
				$chart_group_notes_pengesahan = [];
				$where_chart = "WHERE u.level = 'Pokja'";
				$is_filter_pokja = isset($_GET['kode_pokja']) && $_GET['kode_pokja'] != '';
				if ($is_filter_pokja) {
					$kode_pokja = mysqli_real_escape_string($config, $_GET['kode_pokja']);
					$where_chart .= " AND u.id_user = '$kode_pokja'";
					$q_chart_pengesahan = mysqli_query($config, "
						SELECT
							u.kode_pokja,
							u.nama_lengkap,
							COUNT(p.id_pengajuan) AS total_pengesahan
						FROM tb_user u
						LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user AND p.status = 'Selesai'
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
						LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user AND p.status = 'Selesai'
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
							<table id="example0" class="table table-bordered table-striped">
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
										SELECT p.*, j.nama_jenis, k.kode_pokja, k.nama_lengkap
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
												<td>
													{$row['nomor_surat']}
													<br><br>
													<a href='main_admin.php?unit=detail_pengesahan&id_pengajuan={$row['id_pengajuan']}' class='btn btn-sm btn-info' title='Lihat detail'>
														<i class='fas fa-eye'></i>
													</a>
													<a href='main_admin.php?unit=detail_pengesahan&id_pengajuan={$row['id_pengajuan']}&edit=1' class='btn btn-sm btn-warning' title='Edit PDF'>
														<i class='fas fa-edit'></i>
													</a>
													<button type='button' class='btn btn-sm btn-success' title='Kirim WhatsApp' onclick='kirimWA({$row['id_pengajuan']}, \"{$row['no_tlp']}\", \"{$row['kode_pokja']}\", \"{$row['nomor_surat']}\", \"{$row['judul_dokumen']}\", \"{$row['nama_jenis']}\", \"{$row['tanggal_dokumen']}\")'>
														<i class='fab fa-whatsapp'></i>
													</button>
												</td>
												<td>{$row['nama_jenis']}</td>
												<td>{$row['judul_dokumen']}</td>
												<td>{$tgl_dok}</td>
												<td>{$row['kode_pokja']}<br><small>{$row['nama_lengkap']}</small></td>
												<td><span class='badge badge-primary'>Selesai</span></td>
											</tr>";
											$no++;
										}
									} else {
										echo "<tr><td colspan='7'><em>Tidak ada dokumen yang telah disahkan</em></td></tr>";
									}
									?>
								</tbody>
							</table>
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
$(document).ready(function() {
    $('#example0').DataTable({
        "pageLength": 10,
        "scrollX": true,
        "autoWidth": false,
        "columnDefs": [
            { "width": "60px", "targets": 0 },
            { "width": "150px", "targets": 1 },
            { "width": "130px", "targets": 2 },
            { "width": "210px", "targets": 3 },
            { "width": "115px", "targets": 4 },
            { "width": "100px", "targets": 5 },
            { "width": "95px", "targets": 6 }
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
