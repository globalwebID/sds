ALTER TABLE `pengaturan`
  ADD COLUMN IF NOT EXISTS `npsn` varchar(20) DEFAULT NULL AFTER `nama_sekolah`,
  ADD COLUMN IF NOT EXISTS `kementerian` varchar(150) DEFAULT NULL AFTER `npsn`,
  ADD COLUMN IF NOT EXISTS `alamat` text DEFAULT NULL AFTER `kementerian`,
  ADD COLUMN IF NOT EXISTS `desa` varchar(100) DEFAULT NULL AFTER `alamat`,
  ADD COLUMN IF NOT EXISTS `kecamatan` varchar(100) DEFAULT NULL AFTER `desa`,
  ADD COLUMN IF NOT EXISTS `kabupaten` varchar(100) DEFAULT NULL AFTER `kecamatan`,
  ADD COLUMN IF NOT EXISTS `provinsi` varchar(100) DEFAULT NULL AFTER `kabupaten`,
  ADD COLUMN IF NOT EXISTS `telepon` varchar(30) DEFAULT NULL AFTER `provinsi`,
  ADD COLUMN IF NOT EXISTS `email` varchar(150) DEFAULT NULL AFTER `telepon`,
  ADD COLUMN IF NOT EXISTS `website` varchar(255) DEFAULT NULL AFTER `email`,
  ADD COLUMN IF NOT EXISTS `kepala_sekolah` varchar(150) DEFAULT NULL AFTER `website`,
  ADD COLUMN IF NOT EXISTS `nip_kepala_sekolah` varchar(40) DEFAULT NULL AFTER `kepala_sekolah`,
  ADD COLUMN IF NOT EXISTS `favicon` varchar(255) DEFAULT NULL AFTER `logo`,
  ADD COLUMN IF NOT EXISTS `ttd_kepala_sekolah` varchar(255) DEFAULT NULL AFTER `kop_surat`,
  ADD COLUMN IF NOT EXISTS `stempel` varchar(255) DEFAULT NULL AFTER `ttd_kepala_sekolah`,
  ADD COLUMN IF NOT EXISTS `kartu_orientasi` enum('potrait','landscape') NOT NULL DEFAULT 'potrait' AFTER `stempel`,
  ADD COLUMN IF NOT EXISTS `kartu_lebar_mm` decimal(5,2) NOT NULL DEFAULT 53.98 AFTER `kartu_orientasi`,
  ADD COLUMN IF NOT EXISTS `kartu_tinggi_mm` decimal(5,2) NOT NULL DEFAULT 85.60 AFTER `kartu_lebar_mm`,
  ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

CREATE TABLE IF NOT EXISTS `sds_pengaturan_audit` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int DEFAULT NULL,
  `bagian` varchar(50) NOT NULL,
  `perubahan` longtext NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pengaturan_audit_admin` (`admin_id`,`created_at`),
  KEY `idx_pengaturan_audit_bagian` (`bagian`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

UPDATE `pengaturan` p
JOIN (SELECT * FROM `setting` ORDER BY `site_id` LIMIT 1) s
SET
  p.`nama_sekolah` = COALESCE(NULLIF(p.`nama_sekolah`,''), NULLIF(s.`nama_sekolah`,''), NULLIF(s.`site_name`,'')),
  p.`npsn` = COALESCE(NULLIF(p.`npsn`,''), NULLIF(s.`npsn`,'')),
  p.`kementerian` = COALESCE(NULLIF(p.`kementerian`,''), NULLIF(s.`kementrian`,'')),
  p.`alamat` = COALESCE(NULLIF(p.`alamat`,''), NULLIF(s.`site_address`,'')),
  p.`desa` = COALESCE(NULLIF(p.`desa`,''), NULLIF(s.`desa`,'')),
  p.`kecamatan` = COALESCE(NULLIF(p.`kecamatan`,''), NULLIF(s.`kecamatan`,'')),
  p.`kabupaten` = COALESCE(NULLIF(p.`kabupaten`,''), NULLIF(s.`kabupaten`,'')),
  p.`provinsi` = COALESCE(NULLIF(p.`provinsi`,''), NULLIF(s.`propinsi`,'')),
  p.`telepon` = COALESCE(NULLIF(p.`telepon`,''), NULLIF(s.`site_phone`,'')),
  p.`email` = COALESCE(NULLIF(p.`email`,''), NULLIF(s.`site_email`,'')),
  p.`website` = COALESCE(NULLIF(p.`website`,''), NULLIF(s.`site_url`,'')),
  p.`kepala_sekolah` = COALESCE(NULLIF(p.`kepala_sekolah`,''), NULLIF(s.`kepala_sekolah`,'')),
  p.`nip_kepala_sekolah` = COALESCE(NULLIF(p.`nip_kepala_sekolah`,''), NULLIF(s.`nip_kepala_sekolah`,''));
