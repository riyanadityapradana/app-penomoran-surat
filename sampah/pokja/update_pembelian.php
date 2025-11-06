<?php
include '../../../config/koneksi.php'; // ganti dengan file koneksi milikmu

if (isset($_GET['id'])) {
    $id_barang = $_GET['id'];

    // Ambil data dari POST
    $tanggal_pembelian = $_POST['tanggal_pembelian'];
    $nama_pembeli = $_POST['nama_pembeli'];
    $harga_beli = $_POST['harga_beli'];
    $nama_supplier = $_POST['nama_supplier'];

    // Siapkan path upload
    $folder = "../../../assets/upload/";
    $foto_kwitansi = $_FILES['foto_kwitansi']['name'];
    $foto_barang = $_FILES['foto_barang']['name'];

    // Rename file untuk mencegah tabrakan nama
    $ext_kwitansi = pathinfo($foto_kwitansi, PATHINFO_EXTENSION);
    $ext_barang = pathinfo($foto_barang, PATHINFO_EXTENSION);
    $nama_kwitansi_baru = "kwitansi_" . time() . "." . $ext_kwitansi;
    $nama_barang_baru = "barang_" . time() . "." . $ext_barang;

    // Upload file
    move_uploaded_file($_FILES['foto_kwitansi']['tmp_name'], $folder . $nama_kwitansi_baru);
    move_uploaded_file($_FILES['foto_barang']['tmp_name'], $folder . $nama_barang_baru);

    // Lakukan UPDATE ke database
    $query = "UPDATE tb_barang SET 
                tanggal_pembelian = '$tanggal_pembelian',
                nama_pembeli = '$nama_pembeli',
                harga_beli = '$harga_beli',
                nama_supplier = '$nama_supplier',
                foto_kwitansi = '$nama_kwitansi_baru',
                foto_barang = '$nama_barang_baru',
                status_barang = 'Proses Pembelian',
				status_kerusakan = 'Baik'
              WHERE id_barang = '$id_barang'";

    if (mysqli_query($config, $query)) {
        echo "<script>
                alert('Data barang berhasil diperbarui!');
                window.location.href = '../../main_staff.php?unit=detail_barang&id=$id_barang';
              </script>";
    } else {
        echo "Gagal update: " . mysqli_error($config);
    }
} else {
    echo "ID Barang tidak ditemukan.";
}
?>
