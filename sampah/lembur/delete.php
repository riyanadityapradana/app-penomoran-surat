<?php
	require_once '../config/koneksi.php';
	session_start();

	// Pastikan ada parameter id
	if (isset($_GET['id'])) {
		$id_lembur = $_GET['id'];

		// Cek apakah data lembur milik staff yang sedang login
		$id_staff = $_SESSION['kode_user']; // Pastikan session ini sudah tersedia
		$cek      = mysqli_query($config, "SELECT * FROM tb_lembur WHERE id_lembur = '$id_lembur' AND id_staff='$id_staff' AND status_lembur = 'Menunggu'");

		if (mysqli_num_rows($cek) > 0) {
			// Hapus kegiatan lembur terlebih dahulu
			mysqli_query($config, "DELETE FROM tb_kegiatan_lembur WHERE id_lembur='$id_lembur'");

			// Hapus data lembur
			$hapus = mysqli_query($config, "DELETE FROM tb_lembur WHERE id_lembur='$id_lembur'");

			if ($hapus) {
				echo "<script>alert('Data lembur berhasil dihapus'); window.location='main_staff.php?unit=lembur';</script>";
			} else {
				echo "<script>alert('Gagal menghapus data lembur'); window.history.back();</script>";
			}
		} else {
			echo "<script>alert('Data tidak ditemukan atau sudah diproses'); window.history.back();</script>";
		}
	} else {
		echo "<script>alert('ID tidak ditemukan'); window.history.back();</script>";
	}
?>
