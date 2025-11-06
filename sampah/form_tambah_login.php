<?php
require_once("../config/koneksi.php");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Tambah User Baru</title>
</head>
<body>
    <h2>Form Tambah User</h2>

    <form action="proses_tambah_user.php" method="POST">
        <label>Nama Lengkap</label><br>
        <input type="text" name="nama_lengkap" required><br><br>

        <label>Username</label><br>
        <input type="text" name="username" required><br><br>

        <label>Password</label><br>
        <input type="password" name="password" required><br><br>

        <label>Level</label><br>
        <select name="level" required>
            <option value="">-- Pilih Level --</option>
            <option value="Admin">Admin</option>
            <option value="Pokja">Pokja</option>
        </select><br><br>

        <label>Kode Pokja</label><br>
        <input type="text" name="kode_pokja" placeholder="Contoh: PMKP, TKRS, dll"><br><br>

        <button type="submit" name="simpan">Simpan</button>
    </form>

</body>
</html>
