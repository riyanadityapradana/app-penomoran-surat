-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 11, 2025 at 03:01 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.0.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
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
-- Table structure for table `tb_jenis_dokumen`
--

CREATE TABLE `tb_jenis_dokumen` (
  `id_jenis` int(11) NOT NULL,
  `nama_jenis` varchar(50) NOT NULL,
  `kode_jenis` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tb_jenis_dokumen`
--

INSERT INTO `tb_jenis_dokumen` (`id_jenis`, `nama_jenis`, `kode_jenis`) VALUES
(1, 'SPO', 'SPO'),
(2, 'Laporan', 'LAP'),
(3, 'SK', 'SK'),
(4, 'Pedoman', 'PED'),
(5, 'Panduan', 'PAD'),
(6, 'Undangan', 'UND');

-- --------------------------------------------------------

--
-- Table structure for table `tb_pengajuan_dokumen`
--

CREATE TABLE `tb_pengajuan_dokumen` (
  `id_pengajuan` int(11) NOT NULL,
  `id_user` int(11) NOT NULL,
  `id_jenis` int(11) NOT NULL,
  `elemen_penilaian` varchar(100) NOT NULL,
  `judul_dokumen` varchar(255) NOT NULL,
  `file_draft` varchar(255) DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `status` enum('Menunggu Verifikasi','Disetujui','Ditolak','Selesai','Final') NOT NULL,
  `tanggal_dokumen` date NOT NULL,
  `tanggal_ajuan` datetime DEFAULT current_timestamp(),
  `tanggal_disetujui` datetime DEFAULT NULL,
  `nomor_surat` varchar(100) DEFAULT NULL,
  `file_final` varchar(255) DEFAULT NULL,
  `catatan_admin` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tb_user`
--

CREATE TABLE `tb_user` (
  `id_user` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `level` enum('Admin','Pokja') NOT NULL DEFAULT 'Pokja',
  `kode_pokja` varchar(10) NOT NULL,
  `email_user` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `tb_user`
--

INSERT INTO `tb_user` (`id_user`, `username`, `password`, `nama_lengkap`, `level`, `kode_pokja`, `email_user`) VALUES
(8, 'user_tkrs', '$2y$10$gXbXxOhcxjTYlPOBAoL35uIAlEtBOR2pKMVpc3hNOv0w28AeyZq/e', 'Tata Kelola Rumah Sakit', 'Pokja', 'TKRS', 'fatmad482@gmail.com'),
(9, 'user_pmkp', '$2y$10$HM3BWXrU8FjHIrZ5IPSEeup9Klf51my8mYCc5UUeWVEasy4k6u4M6', 'Peningkatan Mutu dan Keselamatan Pasien', 'Pokja', 'PMKP', NULL),
(12, 'admin_riyan', '$2y$10$DBxLBPEMoVABgEOd6BWZQ.SjhiiI7c1tsBTuqKCOPztF/BY9mBbGC', 'Riyan aditya pradana', 'Admin', 'Admin', 'sekretariatrspelitainsani@gmail.com'),
(13, 'admin_liza', '$2y$10$Oh81ZbDTKz8//ErnAAQtV.AGM8/xitIqfkxdqvUPgUliF1gGenkfe', 'Nur Haliza', 'Admin', 'Admin', 'sekretariatrspelitainsani@gmail.com'),
(16, 'user_kps', '$2y$10$CJSc2zQ8MDGNn/g3OFAZ1OpxvnJ2xkp14rG8KEnq/NClV8CN/Us2O', 'Kualifikasi dan Pendidikan Staf', 'Pokja', 'KPS', NULL),
(17, 'user_mfk', '$2y$10$cdOSkoRuL1WWIV6KyqOlUuJq3CqBkB1JBX7EiT0YxOQ3Dlr2VABgq', 'Manajemen Fasilitas dan Keselamatan', 'Pokja', 'MFK', NULL),
(18, 'user_mrmik', '$2y$10$rSkguMGP3k2/ZVBjgO3Bee8N1/YMrLnXAAlW.7zs4s5FcFmfd1pSa', 'Manajemen Rekam Medis dan Informasi Kesehatan', 'Pokja', 'MRMIK', NULL),
(19, 'user_ppi', '$2y$10$krfhVq/0Klhjrjml.3O/n.WQbeFWkX3/E5a4vVlGMCJQrmlrPFoja', 'Pencegahan dan Pengendalian Infeksi', 'Pokja', 'PPI', NULL),
(20, 'user_ppk', '$2y$10$LkPEfupId4Wgqmgn96.1zujGA4rFygsoweC/AZMUb2Pmmounj8kCi', 'Pendidikan dalam Pelayanan Kesehatan', 'Pokja', 'PPK', NULL),
(21, 'user_akp', '$2y$10$S2schR3KDsafh2nQhDaVYeWgE3zOqvMfib0NwtVkYmlv.MABI0SZK', 'Akses dan Kontinuitas Pelayanan', 'Pokja', 'AKP', NULL),
(22, 'user_hpk', '$2y$10$DvodLUG4UaCHlfs3DAmjRuBqBgV73MxlQWap39nPNOk6ShTd/r68K', 'Hak Pasien dan Keluarga', 'Pokja', 'HPK', NULL),
(23, 'user_pp', '$2y$10$GAH4NjDPgHE.CJbIlWhulOtNInjLJv5duutotjcjkzOLvvdvcHjhC', 'Pengkajian Pasien', 'Pokja', 'PP', NULL),
(24, 'user_pap', '$2y$10$5Gmm3sbDJA1k8HQi3q38eewn2I4MEyOYkOvOsoItNFM96KrJ8Mvre', 'Pelayanan dan Asuhan Pasien', 'Pokja', 'PAP', NULL),
(25, 'user_pab', '$2y$10$qS4Dlcd7fxh3rSEBFjlJ4ODUGJe7asjMtiF5Zz43SEKerC8P4ToK.', 'Pelayanan Anestesi dan Bedah', 'Pokja', 'PAB', NULL),
(26, 'user_pkpo', '$2y$10$Ij7CHKwzDY/EWpG0F7nhh.OMqOJAjIVkQRXXJ2rVenK8/etiClXSW', 'Pelayanan Kefarmasian Penggunaan Obat', 'Pokja', 'PKPO', NULL),
(27, 'user_pn', '$2y$10$fnC/RM.pSCZjtKNEu5zRbuitEGzzhod06a8rDlCeEWirsuCF7d6Mu', 'Program Nasional', 'Pokja', 'PROGNAS', NULL),
(28, 'user_skp', '$2y$10$PadlHgr8dSSzgAsutPgMDeS3NKiB2THGMo3lrzxODTUC8CRkjEmWO', 'Sasaran Keselamatan Pasien', 'Pokja', 'SKP', NULL),
(29, 'user_ke', '$2y$10$xFwrfZ46IMnOEJ6RFztuvOVPPArdVpRADEe2MnIpD324KajQUnNHC', 'Komunikasi dan Edukasi', 'Pokja', 'KE', NULL);

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
  MODIFY `id_jenis` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tb_pengajuan_dokumen`
--
ALTER TABLE `tb_pengajuan_dokumen`
  MODIFY `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tb_user`
--
ALTER TABLE `tb_user`
  MODIFY `id_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tb_pengajuan_dokumen`
--
ALTER TABLE `tb_pengajuan_dokumen`
  ADD CONSTRAINT `tb_pengajuan_dokumen_ibfk_1` FOREIGN KEY (`id_user`) REFERENCES `tb_user` (`id_user`) ON DELETE CASCADE,
  ADD CONSTRAINT `tb_pengajuan_dokumen_ibfk_2` FOREIGN KEY (`id_jenis`) REFERENCES `tb_jenis_dokumen` (`id_jenis`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
