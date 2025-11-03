<!-- Content Header (Page header) -->
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h1>CATATAN KEGIATAN LEMBUR STAFF IT</h1>
      </div>
      <div class="col-sm-6">
        <ol class="breadcrumb float-sm-right">
          <li class="breadcrumb-item"><a href="main_staff.php?unit=beranda">Home</a></li>
          <li class="breadcrumb-item active">Lembur</li>
        </ol>
      </div>
    </div>
  </div><!-- /.container-fluid -->
</section>
<!-- Main content -->
<section class="content">
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
		<div class="card">
		    <div class="card-header">
			    <div class="card-tools" style="float: right; text-align: right;">
                  <a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse" style="background:rgba(69, 77, 85, 1)">
                        <i class="fas fa-bars"></i>
                  </a>
              	</div>
			</div>
		    <!-- /.card-header -->
		    <div class="card-body">
		        <table id="example1" class="table table-bordered table-striped">
		          	<thead style="background:rgb(129, 2, 0, 1)">
						<tr>
							<th style="text-align: center; color: white;">No.</th>
							<th style="text-align: center; color: white;">Nama</th>
							<th style="text-align: center; color: white;">Jabatan</th>
							<th style="text-align: center; color: white;">Tanggal</th>
							<th style="text-align: center; color: white;">Status</th>
							<th style="text-align: center; color: white;">Aksi</th>
						</tr>
		          	</thead>
		          	<tbody>
		          	<?php
					$id_staff = $_SESSION['kode_user'];
		          	$query    = mysqli_query($config,"SELECT * FROM tb_lembur a, tb_user b WHERE a.id_staff = b.kode_user ORDER BY tanggal_lembur DESC")or die(mysqli_error($config));
		          	$n        = 1;
						while ($data=mysqli_fetch_array($query)) {
						$nn=$n++;
		          	?>
						<tr>
							<td align="center"><?= $nn; ?>.</td>
							<td><?= $data['nama_karyawan'] ?></td>
							<td><?= $data['level'] ?></td>
							<td><?= date('d-m-Y', strtotime($data['tanggal_lembur'])) ?></td>
							<td><?= $data['status_lembur'] ?></td>
							<td align="center">
								<a href="?unit=detail_lembur&id=<?= $data['id_lembur'] ?>" class="btn btn-info btn-sm"><i class="fa fa-eye"></i> Detail</a>
							</td>
						</tr>
			        <?php }//end while?>
		        	</tbody>
		        </table>
		    </div>
		    <!-- /.card-body -->
		</div>
		<!-- /.card -->
	</div>
  </div>
</div>
</section>

