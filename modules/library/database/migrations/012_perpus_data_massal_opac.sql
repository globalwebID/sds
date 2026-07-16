-- Perpustakaan SDS v2.4 — Data Massal & OPAC Publik
-- Aman dijalankan berulang pada MySQL/MariaDB.

CREATE TABLE IF NOT EXISTS perpus_import_batch (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    jenis ENUM('koleksi','eksemplar') NOT NULL,
    nama_file VARCHAR(255) DEFAULT NULL,
    total_baris INT NOT NULL DEFAULT 0,
    berhasil INT NOT NULL DEFAULT 0,
    diperbarui INT NOT NULL DEFAULT 0,
    dilewati INT NOT NULL DEFAULT 0,
    gagal INT NOT NULL DEFAULT 0,
    ringkasan LONGTEXT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_perpus_import_jenis (jenis),
    KEY idx_perpus_import_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS perpus_upgrade_v24;
DELIMITER $$
CREATE PROCEDURE perpus_upgrade_v24()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_buku_eksemplar' AND COLUMN_NAME='nomor_inventaris') THEN
        ALTER TABLE perpus_buku_eksemplar ADD COLUMN nomor_inventaris VARCHAR(100) DEFAULT NULL AFTER barcode;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_buku_eksemplar' AND COLUMN_NAME='lokasi_rak') THEN
        ALTER TABLE perpus_buku_eksemplar ADD COLUMN lokasi_rak VARCHAR(100) DEFAULT NULL AFTER tipe_koleksi_id;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_buku_eksemplar' AND COLUMN_NAME='kondisi_fisik') THEN
        ALTER TABLE perpus_buku_eksemplar ADD COLUMN kondisi_fisik ENUM('baik','rusak','hilang') NOT NULL DEFAULT 'baik' AFTER lokasi_rak;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_buku_eksemplar' AND COLUMN_NAME='sumber_pengadaan') THEN
        ALTER TABLE perpus_buku_eksemplar ADD COLUMN sumber_pengadaan VARCHAR(100) DEFAULT NULL AFTER kondisi_fisik;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_buku_eksemplar' AND COLUMN_NAME='harga') THEN
        ALTER TABLE perpus_buku_eksemplar ADD COLUMN harga DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER sumber_pengadaan;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_buku_eksemplar' AND COLUMN_NAME='tanggal_pengadaan') THEN
        ALTER TABLE perpus_buku_eksemplar ADD COLUMN tanggal_pengadaan DATE DEFAULT NULL AFTER harga;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_buku_eksemplar' AND INDEX_NAME='idx_perpus_eksemplar_inventaris') THEN
        ALTER TABLE perpus_buku_eksemplar ADD KEY idx_perpus_eksemplar_inventaris (nomor_inventaris);
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_buku_eksemplar' AND INDEX_NAME='idx_perpus_eksemplar_rak') THEN
        ALTER TABLE perpus_buku_eksemplar ADD KEY idx_perpus_eksemplar_rak (lokasi_rak);
    END IF;
    INSERT INTO perpus_pengaturan (kode,nilai,keterangan) VALUES
        ('opac_aktif','1','Status katalog publik OPAC'),
        ('opac_judul','Katalog Perpustakaan','Judul halaman OPAC publik'),
        ('opac_tampilkan_populer','1','Tampilkan koleksi populer pada OPAC')
    ON DUPLICATE KEY UPDATE keterangan=VALUES(keterangan);
    INSERT INTO perpus_pengaturan (kode,nilai,keterangan) VALUES ('schema_version','2.4.0','Versi schema modul Perpustakaan SDS') ON DUPLICATE KEY UPDATE nilai=VALUES(nilai),keterangan=VALUES(keterangan);
END$$
DELIMITER ;
CALL perpus_upgrade_v24();
DROP PROCEDURE IF EXISTS perpus_upgrade_v24;
