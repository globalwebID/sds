-- Perpustakaan SDS v2.3 — Feature Parity Phase 1
-- Aman dijalankan berulang pada MySQL/MariaDB.

CREATE TABLE IF NOT EXISTS perpus_perpanjangan (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    detail_id BIGINT UNSIGNED NOT NULL,
    tanggal_perpanjang DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    jatuh_tempo_lama DATE NOT NULL,
    jatuh_tempo_baru DATE NOT NULL,
    admin_id INT DEFAULT NULL,
    catatan VARCHAR(255) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_perpus_perpanjangan_detail (detail_id),
    KEY idx_perpus_perpanjangan_tanggal (tanggal_perpanjang)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_denda_pembayaran (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    detail_id BIGINT UNSIGNED NOT NULL,
    anggota_id BIGINT UNSIGNED NOT NULL,
    jenis ENUM('pembayaran','keringanan','pembebasan','koreksi') NOT NULL DEFAULT 'pembayaran',
    nominal DECIMAL(14,2) NOT NULL DEFAULT 0,
    catatan VARCHAR(255) DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_perpus_denda_detail (detail_id),
    KEY idx_perpus_denda_anggota (anggota_id),
    KEY idx_perpus_denda_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS perpus_upgrade_v23;
DELIMITER $$
CREATE PROCEDURE perpus_upgrade_v23()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_tipe_member' AND COLUMN_NAME='maksimal_perpanjangan') THEN
        ALTER TABLE perpus_tipe_member ADD COLUMN maksimal_perpanjangan INT NOT NULL DEFAULT 1 AFTER denda_per_hari;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_tipe_member' AND COLUMN_NAME='hari_perpanjangan') THEN
        ALTER TABLE perpus_tipe_member ADD COLUMN hari_perpanjangan INT NOT NULL DEFAULT 7 AFTER maksimal_perpanjangan;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_peminjaman_detail' AND COLUMN_NAME='jumlah_perpanjangan') THEN
        ALTER TABLE perpus_peminjaman_detail ADD COLUMN jumlah_perpanjangan INT NOT NULL DEFAULT 0 AFTER tanggal_jatuh_tempo;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_peminjaman_detail' AND COLUMN_NAME='denda_status') THEN
        ALTER TABLE perpus_peminjaman_detail ADD COLUMN denda_status ENUM('belum_lunas','lunas','dibebaskan') NOT NULL DEFAULT 'belum_lunas' AFTER denda;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_peminjaman_detail' AND COLUMN_NAME='kondisi_kembali') THEN
        ALTER TABLE perpus_peminjaman_detail ADD COLUMN kondisi_kembali ENUM('baik','rusak','hilang') NOT NULL DEFAULT 'baik' AFTER status;
    END IF;
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='perpus_peminjaman_detail' AND COLUMN_NAME='catatan_kembali') THEN
        ALTER TABLE perpus_peminjaman_detail ADD COLUMN catatan_kembali VARCHAR(255) DEFAULT NULL AFTER kondisi_kembali;
    END IF;
    UPDATE perpus_peminjaman_detail SET denda_status=CASE WHEN denda<=0 THEN 'lunas' ELSE denda_status END WHERE status<>'dipinjam';
    INSERT INTO perpus_pengaturan (kode,nilai,keterangan) VALUES ('schema_version','2.3.0','Versi schema modul Perpustakaan SDS') ON DUPLICATE KEY UPDATE nilai=VALUES(nilai),keterangan=VALUES(keterangan);
END$$
DELIMITER ;
CALL perpus_upgrade_v23();
DROP PROCEDURE IF EXISTS perpus_upgrade_v23;
