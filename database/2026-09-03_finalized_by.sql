ALTER TABLE `tb_pengajuan_dokumen`
  ADD COLUMN IF NOT EXISTS `finalized_by` int(11) DEFAULT NULL AFTER `bentuk_dokumen`;

SET @index_exists = (
  SELECT COUNT(*)
  FROM `information_schema`.`statistics`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'tb_pengajuan_dokumen'
    AND `index_name` = 'idx_pengajuan_finalized_by'
);
SET @index_sql = IF(
  @index_exists = 0,
  'ALTER TABLE `tb_pengajuan_dokumen` ADD KEY `idx_pengajuan_finalized_by` (`finalized_by`)',
  'SELECT 1'
);
PREPARE index_statement FROM @index_sql;
EXECUTE index_statement;
DEALLOCATE PREPARE index_statement;

SET @constraint_exists = (
  SELECT COUNT(*)
  FROM `information_schema`.`table_constraints`
  WHERE `constraint_schema` = DATABASE()
    AND `table_name` = 'tb_pengajuan_dokumen'
    AND `constraint_name` = 'fk_pengajuan_finalized_by'
    AND `constraint_type` = 'FOREIGN KEY'
);
SET @constraint_sql = IF(
  @constraint_exists = 0,
  'ALTER TABLE `tb_pengajuan_dokumen` ADD CONSTRAINT `fk_pengajuan_finalized_by` FOREIGN KEY (`finalized_by`) REFERENCES `tb_user` (`id_user`) ON DELETE SET NULL ON UPDATE CASCADE',
  'SELECT 1'
);
PREPARE constraint_statement FROM @constraint_sql;
EXECUTE constraint_statement;
DEALLOCATE PREPARE constraint_statement;
