<?php
require_once("../config/koneksi.php"); // sesuaikan path

if (empty($_SESSION['pengajuan_admin_csrf'])) {
    $_SESSION['pengajuan_admin_csrf'] = bin2hex(random_bytes(32));
}

// --- PROSES FILTER DENGAN POST --- //
$kode_pokja_filter = '';

// Jika form disubmit dengan POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kode_pokja'])) {
    $kode_pokja_filter = $_POST['kode_pokja'];
    
    // Redirect dengan parameter GET yang bersih
    $redirect_url = $_SERVER['PHP_SELF'];
    if (isset($_GET['unit'])) {
        $redirect_url .= '?unit=' . $_GET['unit'];
        if (!empty($kode_pokja_filter)) {
            $redirect_url .= '&kode_pokja=' . urlencode($kode_pokja_filter);
        }
    }
    header("Location: " . $redirect_url);
    exit();
}

// Ambil filter dari URL (setelah redirect)
$kode_pokja_filter = isset($_GET['kode_pokja']) ? $_GET['kode_pokja'] : '';

// --- AMBIL DATA KODE POKJA UNTUK DROPDOWN --- //
$kode_pokja_query = mysqli_query($config, "SELECT DISTINCT kode_pokja FROM tb_user WHERE kode_pokja != '' ORDER BY kode_pokja ASC");

// --- AMBIL SEMUA DATA PENGAJUAN DARI POKJA DENGAN FILTER --- //
$where_clause = "WHERE p.status != 'Selesai'";
if (!empty($kode_pokja_filter)) {
    $where_clause .= " AND u.kode_pokja = '" . mysqli_real_escape_string($config, $kode_pokja_filter) . "'";
}

$query = mysqli_query($config, "
    SELECT p.*, j.nama_jenis, u.nama_lengkap, u.kode_pokja
    FROM tb_pengajuan_dokumen p
    LEFT JOIN tb_jenis_dokumen j ON p.id_jenis = j.id_jenis
    LEFT JOIN tb_user u ON p.id_user = u.id_user
    $where_clause
    ORDER BY p.id_pengajuan DESC
");
?>

<style>
    .pengajuan-title-cell {
        min-width: 280px;
        white-space: normal !important;
    }

    .pengajuan-row-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 10px;
    }

    .pengajuan-row-actions form {
        margin: 0;
    }

    .pengajuan-row-actions .btn {
        min-width: 34px;
    }
</style>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h1 class="card-title">Data Pengajuan Dokumen dari Pokja</h1>
            </div>

            <div class="card-body">
                <!-- Form Filter -->
                <form method="POST" action="" class="mb-3">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="kode_pokja">Filter berdasarkan Kode Pokja:</label>
                                <select name="kode_pokja" id="kode_pokja" class="form-control select2">
                                    <option value="">-- Semua Kode Pokja --</option>
                                    <?php
                                    // Reset pointer untuk query kode_pokja
                                    mysqli_data_seek($kode_pokja_query, 0);
                                    while ($pokja_row = mysqli_fetch_assoc($kode_pokja_query)) {
                                        $selected = ($kode_pokja_filter == $pokja_row['kode_pokja']) ? 'selected' : '';
                                        echo "<option value='{$pokja_row['kode_pokja']}' $selected>{$pokja_row['kode_pokja']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary form-control">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <a href="?unit=pengajuan" class="btn btn-secondary form-control">
                                    <i class="fas fa-times"></i> Reset
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

    <?php if (!empty($kode_pokja_filter)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Menampilkan data untuk Kode Pokja: <strong><?php echo htmlspecialchars($kode_pokja_filter); ?></strong>
        <a href="?unit=pengajuan" class="btn btn-sm btn-outline-info float-right">Tampilkan Semua Data</a>
    </div>
    <?php endif; ?>

    <table id="example2" class="table table-bordered table-striped app-table" style="width: 100%;">
                    <thead style="background:rgb(0, 0, 0, 1)">
                        <tr class="text-center">
                            <th style="color:white;" responsive>No</th>
                            <th style="color:white;" responsive>Tanggal Ajuan</th>
                            <th style="color:white;" responsive>Dokumen Pokja</th>
                            <th style="color:white;" responsive>Bentuk Dokumen</th>
                            <th style="color:white;" responsive>Judul Dokumen</th>
                            <th style="color:white;" responsive>No Surat</th>
                            <th style="color:white;" responsive>Status</th>
							<th style="color:white;" responsive>File Draft</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (mysqli_num_rows($query) > 0) {
                            $no = 1;
                            while ($row = mysqli_fetch_assoc($query)) {
                                $idPengajuan = (int) $row['id_pengajuan'];
                                $judulDokumen = htmlspecialchars($row['judul_dokumen']);
                                $kodePokja = htmlspecialchars($row['kode_pokja']);
                                $namaJenis = htmlspecialchars($row['nama_jenis']);
                                $bentukDokumen = htmlspecialchars(!empty($row['bentuk_dokumen']) ? $row['bentuk_dokumen'] : '-');
                                $nomorSurat = htmlspecialchars($row['nomor_surat'] ?? '');
                                $fileDraft = basename((string) ($row['file_draft'] ?? ''));
                                $fileDraftIsPdf = strtolower(pathinfo($fileDraft, PATHINFO_EXTENSION)) === 'pdf';
                                echo "<tr>
                                        <td class='text-center'>{$no}</td>
                                        <td class='text-center'>" . date('d-m-Y', strtotime($row['tanggal_ajuan'])) . "</td>
                                        <td class='text-left'>{$kodePokja}<br><small style='color: #6c757d;'>Jenis Dokumen: {$namaJenis}</small></td>
                                        <td class='text-center'>{$bentukDokumen}</td>
                                        <td class='text-long pengajuan-title-cell'>
                                            <div>{$judulDokumen}</div>
                                            <div class='pengajuan-row-actions'>
                                                <a href='main_admin.php?unit=detail_pengajuan&id_pengajuan={$idPengajuan}' class='btn btn-sm btn-info' title='Lihat detail dokumen' aria-label='Lihat detail dokumen'>
                                                    <i class='fas fa-eye'></i> Detail
                                                </a>
                                                <a href='main_admin.php?unit=update_pengajuan&id_pengajuan={$idPengajuan}' class='btn btn-sm btn-warning' title='Edit dokumen' aria-label='Edit dokumen'>
                                                    <i class='fas fa-edit'></i> Edit
                                                </a>
                                                <form method='POST' action='main_admin.php?unit=delete_pengajuan' onsubmit=\"return confirm('Hapus pengajuan ini beserta file terkait? Data yang dihapus tidak dapat dikembalikan.');\">
                                                    <input type='hidden' name='csrf_token' value='" . htmlspecialchars($_SESSION['pengajuan_admin_csrf']) . "'>
                                                    <input type='hidden' name='id_pengajuan' value='{$idPengajuan}'>
                                                    <button type='submit' class='btn btn-sm btn-danger' title='Hapus dokumen' aria-label='Hapus dokumen'>
                                                        <i class='fas fa-trash'></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                        <td class='text-center'>";
                                        if (!empty($row['nomor_surat'])) {
                                            echo "<span class='badge badge-defauld' style='color: black; font-weight: bold;'>{$nomorSurat}</span>";
                                        } else {
                                            echo "<span class='badge badge-danger' style='color: white; font-weight: bold;'>-</span>";
                                        }
                                echo "</td>
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
									<td class='text-center'>";
								if (!empty($row['file_draft'])) {
									$draftButtonClass = $fileDraftIsPdf ? 'btn-danger' : 'btn-info';
									$draftIcon = $fileDraftIsPdf ? 'fa-file-pdf' : 'fa-file-word';
									$draftLabel = $fileDraftIsPdf ? 'PDF' : 'Word';
									echo "<a href='../assets/upload/draft_word/" . rawurlencode($fileDraft) . "'
											target='_blank' class='btn btn-sm {$draftButtonClass}'>
											<i class='fas {$draftIcon}'></i> {$draftLabel}
										  </a>";
								} else {
									echo "-";
								}

                                echo "</td></tr>";
                                $no++;
                            }
                        } else {
                            echo "<tr><td colspan='8' class='text-center'>Belum ada data pengajuan</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
