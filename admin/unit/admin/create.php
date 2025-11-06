<?php
	require_once("../config/koneksi.php"); // Sesuaikan path ke koneksi

	// Cek jika form disubmit
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$kode_pokja   = "Admin";
		$nama_lengkap = mysqli_real_escape_string($config, $_POST['nama_lengkap']);
		$username     = mysqli_real_escape_string($config, $_POST['username']);
		$password     = mysqli_real_escape_string($config, $_POST['password']);
		$email_user   = mysqli_real_escape_string($config, $_POST['email_user']);
		$level        = "Admin";
		
		// Hash password sebelum disimpan
		$hashed_password = password_hash($password, PASSWORD_DEFAULT);

		// Cek apakah username sudah ada
		$cek = mysqli_query($config, "SELECT * FROM tb_user WHERE username='$username'");
		if (mysqli_num_rows($cek) > 0) {
			header('Location: main_admin.php?unit=admin&err=Username sudah digunakan, silakan pilih yang lain!');
			exit;
		}

		// Simpan ke database
		$query = "INSERT INTO tb_user (nama_lengkap, username, password, level, kode_pokja,email_user)
				  VALUES ('$nama_lengkap', '$username', '$hashed_password', '$level', '$kode_pokja', '$email_user')";

		if (mysqli_query($config, $query)) {
			header('Location: main_admin.php?unit=admin&msg=User baru berhasil ditambahkan!');
			exit;
		} else {
			$errMsg = urlencode('Gagal menyimpan data: ' . mysqli_error($config));
			header('Location: main_admin.php?unit=create_admin&err=' . $errMsg);
			exit;
		}
	}
?>

	<section class="content-header">
		<!-- [Header sama seperti punyamu] -->
	</section>
	<section class="content">
		<div class="container-fluid">
			<div class="card card-default">
				<div class="card-header">
					<h3 class="card-title">Silahkan Input Data Admin</h3>
				</div>
				<form method="post" enctype="multipart/form-data">
					<div class="card-body">
						<div class='row'>
							<div class="col-sm-12">
								<div class="form-group">
									<label>Nama lengkap</label>
									<input type="text" name="nama_lengkap" class="form-control" required>
								</div>
								<div class="form-group">
									<label>Email User</label>
									<input type="email" name="email_user" class="form-control" required>
								</div>
								<div class="form-group">
									<label>Username</label>
									<input type="text" name="username" class="form-control" required>
								</div>
								<div class="form-group">
									<label>Password</label>
									<input type="text" name="password" class="form-control" required>
								</div>
							</div>
						</div>	
					</div>
					<div class="card-footer">
						<a class="btn btn-app bg-warning float-left" href="main_admin.php?unit=admin">
							<i class="fas fa-reply"></i> Back
						</a>
						<button class="btn btn-app bg-success float-right" type="submit">
							<i class="fas fa-save"></i> SAVE
						</button>
					</div>
				</form>
			</div>
		</div>
	</section>
