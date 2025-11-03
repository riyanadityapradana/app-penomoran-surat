<?php
	require_once("../config/koneksi.php"); // sesuaikan path ke file koneksi

	if (isset($_GET['id_user'])) {
		$id_user = mysqli_real_escape_string($config, $_GET['id_user']);

		// Pastikan data user ada
		$cek = mysqli_query($config, "SELECT * FROM tb_user WHERE id_user='$id_user' AND level='Pokja'");
		if (mysqli_num_rows($cek) == 0) {
			header('Location: main_admin.php?unit=pokja&err=Data tidak ditemukan!');
			exit;
		}

		// Jalankan query hapus
		$query = "DELETE FROM tb_user WHERE id_user='$id_user' AND level='Pokja'";
		if (mysqli_query($config, $query)) {
			header('Location: main_admin.php?unit=pokja&msg=Data Pokja berhasil dihapus!');
		} else {
			$errMsg = urlencode('Gagal menghapus data: ' . mysqli_error($config));
			header('Location: main_admin.php?unit=pokja&err=' . $errMsg);
			exit;
		}

	} else {
		header('Location: main_admin.php?unit=pokja&err=ID user tidak ditemukan!');
		exit;
	}
?>
