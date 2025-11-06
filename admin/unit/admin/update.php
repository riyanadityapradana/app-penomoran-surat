<?php
require_once("../config/koneksi.php"); // Sesuaikan path

// --- AMBIL DATA USER BERDASARKAN ID --- //
if (isset($_GET['id_user'])) {
    $id_user = mysqli_real_escape_string($config, $_GET['id_user']);
    $query   = mysqli_query($config, "SELECT * FROM tb_user WHERE id_user = '$id_user'");
    $data    = mysqli_fetch_assoc($query);

    if (!$data) {
        header('Location: main_admin.php?unit=admin&err=Data user tidak ditemukan!');
        exit;
    }
}

// --- PROSES UPDATE --- //
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_user      = mysqli_real_escape_string($config, $_POST['id_user']);
    $nama_lengkap = mysqli_real_escape_string($config, $_POST['nama_lengkap']);
    $username     = mysqli_real_escape_string($config, $_POST['username']);
    $password     = mysqli_real_escape_string($config, $_POST['password']);
	$email_user   = mysqli_real_escape_string($config, $_POST['email_user']);

    // Cek apakah password ingin diubah
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $query = "UPDATE tb_user 
                  SET nama_lengkap='$nama_lengkap', username='$username', 
                  email_user='$email_user', password='$hashed_password' WHERE id_user='$id_user'";
    } else {
        // Password tidak diubah
        $query = "UPDATE tb_user 
                  SET nama_lengkap='$nama_lengkap', username='$username', email_user='$email_user'
                  WHERE id_user='$id_user'";
    }

    if (mysqli_query($config, $query)) {
        header('Location: main_admin.php?unit=admin&msg=Data user berhasil diperbarui!');
        exit;
    } else {
        $errMsg = urlencode('Gagal menyimpan data: ' . mysqli_error($config));
			header('Location: main_admin.php?unit=admin&err=' . $errMsg);
			exit;
    }
}
?>

<section class="content-header">
    <h1>Edit Data Admin</h1>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="card card-default">
            <div class="card-header">
                <h3 class="card-title">Form Edit Data Amin</h3>
            </div>

            <form method="post">
                <input type="hidden" name="id_user" value="<?php echo $data['id_user']; ?>">

                <div class="card-body">
                    <div class='row'>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" class="form-control" 
                                       value="<?php echo htmlspecialchars($data['nama_lengkap']); ?>" required>
                            </div>
							<div class="form-group">
                                <label>Email Pengguna</label>
                                <input type="email" name="email_user" class="form-control" 
                                       value="<?php echo htmlspecialchars($data['email_user']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" name="username" class="form-control" 
                                       value="<?php echo htmlspecialchars($data['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Password (Kosongkan jika tidak ingin diubah)</label>
                                <input type="text" name="password" class="form-control" placeholder="Masukkan password baru jika ingin mengganti">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <a class="btn btn-app bg-warning float-left" href="main_admin.php?unit=admin">
                        <i class="fas fa-reply"></i> Back
                    </a>
                    <button class="btn btn-app bg-success float-right" type="submit">
                        <i class="fas fa-save"></i> UPDATE
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
