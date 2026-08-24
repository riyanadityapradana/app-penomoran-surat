ALTER TABLE `tb_pengajuan_dokumen`
  ADD COLUMN IF NOT EXISTS `tanggal_pengesahan` datetime DEFAULT NULL AFTER `tanggal_disetujui`;

UPDATE `tb_pengajuan_dokumen`
SET `tanggal_pengesahan` = COALESCE(`tanggal_disetujui`, `tanggal_ajuan`)
WHERE `status` = 'Selesai'
  AND `tanggal_pengesahan` IS NULL;
