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

				<!-- Statistik Pengesahan per Pokja -->
				<?php
				// Data untuk chart pengesahan
				$chart_labels_pengesahan = [];
				$chart_data_pengesahan = [];
				$where_chart = "WHERE u.level = 'Pokja'";
				if (isset($_GET['kode_pokja']) && $_GET['kode_pokja'] != '') {
					$kode_pokja = mysqli_real_escape_string($config, $_GET['kode_pokja']);
					$where_chart .= " AND u.id_user = '$kode_pokja'";
				}
				$q_chart_pengesahan = mysqli_query($config, "
					SELECT
						u.nama_lengkap,
						COUNT(p.id_pengajuan) AS total_pengesahan
					FROM tb_user u
					LEFT JOIN tb_pengajuan_dokumen p ON u.id_user = p.id_user AND p.status = 'Selesai'
					$where_chart
					GROUP BY u.id_user
					ORDER BY total_pengesahan DESC
				");
				while ($row = mysqli_fetch_assoc($q_chart_pengesahan)) {
					$chart_labels_pengesahan[] = $row['nama_lengkap'];
					$chart_data_pengesahan[] = (int)$row['total_pengesahan'];
				}
				?>

				<div class="row">
					<div class="col-md-6">
						<h4 class="text-center mb-3">Tabel Daftar Dokumen yang Telah Disahkan</h4>
						<!-- TABEL DATA -->
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
												<button type='button' class='btn btn-sm btn-success' onclick='kirimWA({$row['id_pengajuan']}, \"{$row['no_tlp']}\", \"{$row['kode_pokja']}\", \"{$row['nomor_surat']}\", \"{$row['judul_dokumen']}\", \"{$row['nama_jenis']}\", \"{$row['tanggal_dokumen']}\")'>
													<i class='fab fa-whatsapp'></i>
												</button>
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
					<div class="col-md-6">
						<h4 class="text-center mb-3">Grafik Pengesahan Dokumen per Pokja</h4>
						<div id="pengesahanChart" style="width:100%; height:400px;"></div>
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
    credits: {
        enabled: false
    }
});
</script>
