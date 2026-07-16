ALTER TABLE transaksi_kantin
  ADD COLUMN IF NOT EXISTS request_key VARCHAR(64) NULL AFTER status_dilayani;

SET @has_index := (
  SELECT COUNT(*) FROM information_schema.statistics
  WHERE table_schema=DATABASE() AND table_name='transaksi_kantin' AND index_name='uq_transaksi_kantin_request_key'
);
SET @sql := IF(@has_index=0,
  'CREATE UNIQUE INDEX uq_transaksi_kantin_request_key ON transaksi_kantin(request_key)',
  'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
