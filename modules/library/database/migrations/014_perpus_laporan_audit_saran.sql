-- E-Perpustakaan SDS v2.6
-- Laporan lengkap, audit integritas, log aktivitas, dan kritik/saran.
-- Migrasi utama berjalan otomatis melalui config/perpus.php.

CREATE TABLE IF NOT EXISTS `perpus_saran` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `anggota_id` BIGINT UNSIGNED DEFAULT NULL,
  `nama_pengirim` VARCHAR(150) DEFAULT NULL,
  `kontak` VARCHAR(150) DEFAULT NULL,
  `kategori` ENUM('kritik','saran','keluhan','apresiasi') NOT NULL DEFAULT 'saran',
  `judul` VARCHAR(180) NOT NULL,
  `pesan` TEXT NOT NULL,
  `status` ENUM('baru','diproses','selesai','ditolak') NOT NULL DEFAULT 'baru',
  `jawaban` TEXT DEFAULT NULL,
  `sumber` ENUM('opac','admin') NOT NULL DEFAULT 'opac',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `admin_id` INT DEFAULT NULL,
  `ditanggapi_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_perpus_saran_status` (`status`,`created_at`),
  KEY `idx_perpus_saran_anggota` (`anggota_id`),
  KEY `idx_perpus_saran_kategori` (`kategori`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `perpus_audit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT DEFAULT NULL,
  `aksi` VARCHAR(40) NOT NULL,
  `entitas` VARCHAR(60) NOT NULL,
  `entitas_id` VARCHAR(80) DEFAULT NULL,
  `ringkasan` VARCHAR(255) DEFAULT NULL,
  `data_lama` LONGTEXT DEFAULT NULL,
  `data_baru` LONGTEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_perpus_audit_waktu` (`created_at`),
  KEY `idx_perpus_audit_entitas` (`entitas`,`entitas_id`),
  KEY `idx_perpus_audit_admin` (`admin_id`,`created_at`),
  KEY `idx_perpus_audit_aksi` (`aksi`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `perpus_audit_check` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kode` VARCHAR(60) NOT NULL,
  `tingkat` ENUM('info','peringatan','kritis') NOT NULL DEFAULT 'info',
  `judul` VARCHAR(180) NOT NULL,
  `jumlah_temuan` INT NOT NULL DEFAULT 0,
  `detail_json` LONGTEXT DEFAULT NULL,
  `checked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `checked_by` INT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_perpus_check_waktu` (`checked_at`),
  KEY `idx_perpus_check_kode` (`kode`,`checked_at`),
  KEY `idx_perpus_check_tingkat` (`tingkat`,`jumlah_temuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `perpus_pengaturan` (`kode`,`nilai`,`keterangan`) VALUES
('saran_aktif','1','Aktifkan formulir kritik dan saran pada OPAC'),
('saran_wajib_identitas','0','Wajibkan identitas anggota pada kritik dan saran'),
('retensi_audit_hari','365','Lama penyimpanan log audit dalam hari'),
('schema_version','2.6.0','Versi schema modul Perpustakaan SDS')
ON DUPLICATE KEY UPDATE `keterangan`=VALUES(`keterangan`);
