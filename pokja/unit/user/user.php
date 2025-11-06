	<!-- Content Header (Page header) -->
	<section class="content-header">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1>KELOLA DATA USER SEKRETARIAT</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="main_staff.php?unit=beranda">Home</a></li>
						<li class="breadcrumb-item active">User Akreditas</li>
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
							<h3 class="card-title">Data User Sekretariat</h3>
							<div class="card-tools" style="float: right; text-align: right;">
								<a href="#" class="btn btn-tool btn-sm" data-card-widget="collapse" style="background:rgba(69, 77, 85, 1)">
									<i class="fas fa-bars"></i>
								</a>
							</div>
						</div>
						<!-- /.card-header -->
						<div class="card-body">
							<table id="example2" class="table table-bordered table-striped">
								<thead style="background:rgb(0, 0, 0, 1)">
									<tr>
										<th style="text-align: center; color: white;">No</th>
										<th style="text-align: center; color: white;">Kode Pokja</th>
										<th style="text-align: center; color: white;">Nama Lengkap</th>
										<th style="text-align: center; color: white;">Username</th>
										<th style="text-align: center; color: white;">Action</th>
									</tr>
								</thead>
								<tbody>
								<?php
								$id_user = $_SESSION['id_user'];
								$query = mysqli_query($config,"SELECT * FROM tb_user WHERE id_user='$id_user' ORDER BY nama_lengkap DESC")or die(mysqli_error($config));
								$n=1;
								while ($data=mysqli_fetch_array($query)) {
									$idd = $data['id_user'];
									$nn=$n++;
								?>
								  <tr>
									<td align="center"><?php echo $nn ?></td>
									<td><?= $data['kode_pokja'] ?></td>
									<td><?= $data['nama_lengkap'] ?></td>
									<td><?= $data['username'] ?></td>
									<td align="center">
										<input type="hidden" id="code">
										<span><a href="?unit=update_user&id_user=<?=$idd;?>" class="btn btn-success"><i class="fa fa-user-edit"></i> Edit</a></span>
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