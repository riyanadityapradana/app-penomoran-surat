-- phpMyAdmin SQL Dump
-- version 4.5.1
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 06 Nov 2025 pada 06.35
-- Versi Server: 10.1.13-MariaDB
-- PHP Version: 5.5.37

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_surat_akreditasi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_jenis_dokumen`
--

CREATE TABLE `tb_jenis_dokumen` (
  `id_jenis` int(11) NOT NULL,
  `nama_jenis` varchar(50) NOT NULL,
  `kode_jenis` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `tb_jenis_dokumen`
--

INSERT INTO `tb_jenis_dokumen` (`id_jenis`, `nama_jenis`, `kode_jenis`) VALUES
(1, 'SPO', 'SPO'),
(2, 'Regulasi', 'REG'),
(3, 'SK', 'SK'),
(4, 'Pedoman', 'PED'),
(5, 'Panduan', 'PAD');

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_pengajuan_dokumen`
--

CREATE TABLE `tb_pengajuan_dokumen` (
  `id_pengajuan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_jenis` int(11) NOT NULL,
  `judul_dokumen` varchar(255) NOT NULL,
  `file_draft` varchar(255) DEFAULT NULL,
  `catatan` text,
  `status` enum('Menunggu Verifikasi','Disetujui','Ditolak','Selesai','Final') NOT NULL,
  `tanggal_dokumen` date NOT NULL,
  `tanggal_ajuan` datetime DEFAULT CURRENT_TIMESTAMP,
  `tanggal_disetujui` datetime DEFAULT NULL,
  `nomor_surat` varchar(100) DEFAULT NULL,
  `file_final` varchar(255) DEFAULT NULL,
  `catatan_admin` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `tb_pengajuan_dokumen`
--

INSERT INTO `tb_pengajuan_dokumen` (`id_pengajuan`, `id_user`, `id_jenis`, `judul_dokumen`, `file_draft`, `catatan`, `status`, `tanggal_dokumen`, `tanggal_ajuan`, `tanggal_disetujui`, `nomor_surat`, `file_final`, `catatan_admin`) VALUES
(5, 9, 2, 'Peraturan Jam Istirahat Pegawai', 'dokumen_final_1762056876.pdf', 'Draft Sudah Final', 'Selesai', '2025-10-30', '2025-11-02 00:00:00', '2025-11-02 00:00:00', 'A/001/SK/PMPK/XI/2025', NULL, 'Asik'),
(6, 9, 3, 'SK Pegawai Baru', 'dokumen_final_1762128554.pdf', 'Masih Awal', 'Selesai', '2025-11-04', '2025-11-02 00:00:00', '2025-11-02 00:00:00', 'A/002/SK/PMPK/XI/2025', NULL, 'ckckckck'),
(7, 8, 3, 'SK Hari Libur Hari Raya', 'dokumen_final_1762128659.pdf', '-', 'Selesai', '2025-11-01', '2025-11-03 00:00:00', '2025-11-03 00:00:00', 'A/001/SK/TKRS/XI/2025', NULL, '-'),
(8, 9, 1, 'coba coba 2', 'draft_1762131894.docx', '', 'Ditolak', '2025-11-02', '2025-11-03 00:00:00', '2025-11-03 00:00:00', NULL, NULL, 'Kalimat nya Tidak Rapi'),
(10, 9, 1, 'coba coba 3', 'draft_1762401743.docx', 'Apa aja boleh', 'Menunggu Verifikasi', '2025-11-06', '2025-11-06 00:00:00', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `level` enum('Admin','Pokja') NOT NULL DEFAULT 'Pokja',
  `kode_pokja` varchar(10) NOT NULL,
  `email_user` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `username`, `password`, `nama_lengkap`, `level`, `kode_pokja`, `email_user`) VALUES
(8, 'admin_tkrs', '$2y$10$gXbXxOhcxjTYlPOBAoL35uIAlEtBOR2pKMVpc3hNOv0w28AeyZq/e', 'Tenaga Kerja Rumah Sakit', 'Pokja', 'TKRS', NULL),
(9, 'admin_pmpk', '$2y$10$HM3BWXrU8FjHIrZ5IPSEeup9Klf51my8mYCc5UUeWVEasy4k6u4M6', 'Penilaian Medis Pegawai Kesehatan', 'Pokja', 'PMPK', NULL),
(12, 'admin', '$2y$10$DBxLBPEMoVABgEOd6BWZQ.SjhiiI7c1tsBTuqKCOPztF/BY9mBbGC', 'Catherina Vallencia', 'Admin', 'Admin', 'fatmad482@gmail.com'),
(13, '12345', '$2y$10$Oh81ZbDTKz8//ErnAAQtV.AGM8/xitIqfkxdqvUPgUliF1gGenkfe', 'Oline Manuel', 'Admin', 'Admin', 'fatmadsbs@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tb_jenis_dokumen`
--
ALTER TABLE `tb_jenis_dokumen`
  ADD PRIMARY KEY (`id_jenis`);

--
-- Indexes for table `tb_pengajuan_dokumen`
--
ALTER TABLE `tb_pengajuan_dokumen`
  ADD PRIMARY KEY (`id_pengajuan`),
  ADD KEY `id_user` (`id_user`),
  ADD KEY `id_jenis` (`id_jenis`);

--
-- Indexes for table `tb_user`
--
ALTER TABLE `tb_user`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tb_jenis_dokumen`
--
ALTER TABLE `tb_jenis_dokumen`
  MODIFY `id_jenis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT for table `tb_pengajuan_dokumen`
--
ALTER TABLE `tb_pengajuan_dokumen`
  MODIFY `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `tb_pengajuan_dokumen`
--
ALTER TABLE `tb_pengajuan_dokumen`
  ADD CONSTRAINT `tb_pengajuan_dokumen_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_pengajuan_dokumen_ibfk_2` FOREIGN KEY (`id_jenis`) REFERENCES `tb_jenis_dokumen` (`id_jenis`) ON DELETE CASCADE;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
