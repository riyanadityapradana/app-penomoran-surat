<?php
require_once("../config/koneksi.php"); // sesuaikan path

// --- AMBIL SEMUA DATA PENGAJUAN DARI POKJA --- //
$query = mysqli_query($config, "
    SELECT p.*, j.nama_jenis, u.nama_lengkap, u.kode_pokja
    FROM tb_pengajuan_dokumen p
    LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
    LEFT JOIN tb_user u ON p.id_user = u.id_user
	WHERE status != 'Selesai' ORDER BY p.id_pengajuan DESC
");
?>

<section class="content-header">
    <h1>Daftar Pengajuan Dokumen</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Data Pengajuan Dokumen dari Pokja</h3>
            </div>

            <div class="card-body">
                <table id="example2" class="table table-bordered table-striped">
                    <thead style="background:rgb(0, 0, 0, 1)">
                        <tr class="text-center">
                            <th style="color:white;">No</th>
                            <th style="color:white;">Tanggal Ajuan</th>
                            <th style="color:white;">Kode Pokja</th>
                            <th style="color:white;">Jenis Dokumen</th>
                            <th style="color:white;">No Surat</th>
                            <th style="color:white;">Status</th>
							<th style="color:white;">File Draft</th>
                            <th style="color:white;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($query) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($query)) {
                                echo "<tr>
                                        <td class='text-center'>{$no}</td>
                                        <td class='text-center'>" . date('d-m-Y', strtotime($row['tanggal_ajuan'])) . "</td>
                                        <td class='text-center'>{$row['kode_pokja']}</td>
                                        <td>{$row['nama_jenis']}</td>
                                        <td class='text-center'>" . 
                                            (!empty($row['nomor_surat']) ? htmlspecialchars($row['nomor_surat']) : '-') . 
                                        "</td>
                                        <td class='text-center'>";
                                        
                                if ($row['status'] == 'Menunggu Verifikasi') {
                                    echo "<span class='badge badge-warning'>Menunggu</span>";
                                } elseif ($row['status'] == 'Disetujui') {
                                    echo "<span class='badge badge-success'>Disetujui</span>";
                                } elseif ($row['status'] == 'Ditolak') {
                                    echo "<span class='badge badge-danger'>Ditolak</span>";
                                } elseif ($row['status'] == 'Selesai') {
                                    echo "<span class='badge badge-primary'>Selesai</span>";
                                } else {
                                    echo "<span class='badge badge-secondary'>Tidak Diketahui</span>";
                                }
								
								echo "</td>
									<td>";
								if (!empty($row['file_draft'])) {
									echo "<a href='../assets/upload/draft_word/{$row['file_draft']}' 
											target='_blank' class='btn btn-sm btn-info'>
											<i class='fas fa-file-word'></i> Download
										  </a>";
								} else {
									echo "-";
								}
								
									
                                echo "</td>
                                      <td class='text-center'>
                                        <a href='main_admin.php?unit=detail_pengajuan&id_pengajuan={$row['id_pengajuan']}' 
                                           class='btn btn-sm btn-info'>
                                           <i class='fas fa-eye'></i> Detail
                                        </a>
                                      </td>
                                    </tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='9' class='text-center'>Belum ada data pengajuan</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
