ALTER TABLE `pengaturan`
  ADD COLUMN IF NOT EXISTS `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0 AFTER `kartu_tinggi_mm`,
  ADD COLUMN IF NOT EXISTS `maintenance_message` varchar(500) DEFAULT 'Sistem sedang dalam pemeliharaan. Silakan coba kembali beberapa saat lagi.' AFTER `maintenance_mode`,
  ADD COLUMN IF NOT EXISTS `backup_schedule` enum('disabled','daily','weekly') NOT NULL DEFAULT 'disabled' AFTER `maintenance_message`,
  ADD COLUMN IF NOT EXISTS `backup_retention_days` smallint unsigned NOT NULL DEFAULT 30 AFTER `backup_schedule`,
  ADD COLUMN IF NOT EXISTS `login_max_attempts` tinyint unsigned NOT NULL DEFAULT 5 AFTER `backup_retention_days`,
  ADD COLUMN IF NOT EXISTS `login_window_minutes` smallint unsigned NOT NULL DEFAULT 5 AFTER `login_max_attempts`,
  ADD COLUMN IF NOT EXISTS `admin_session_minutes` smallint unsigned NOT NULL DEFAULT 30 AFTER `login_window_minutes`,
  ADD COLUMN IF NOT EXISTS `password_expiry_days` smallint unsigned NOT NULL DEFAULT 0 AFTER `admin_session_minutes`,
  ADD COLUMN IF NOT EXISTS `system_timezone` varchar(64) NOT NULL DEFAULT 'Asia/Jakarta' AFTER `password_expiry_days`,
  ADD COLUMN IF NOT EXISTS `date_format` varchar(20) NOT NULL DEFAULT 'd/m/Y' AFTER `system_timezone`,
  ADD COLUMN IF NOT EXISTS `number_locale` varchar(10) NOT NULL DEFAULT 'id_ID' AFTER `date_format`,
  ADD COLUMN IF NOT EXISTS `last_backup_at` datetime DEFAULT NULL AFTER `number_locale`;

CREATE TABLE IF NOT EXISTS `sds_admin_sessions` (
  `session_hash` char(64) NOT NULL,
  `admin_id` int NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `last_activity` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`session_hash`),
  KEY `idx_sds_admin_sessions_admin` (`admin_id`,`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `admins`
  ADD COLUMN IF NOT EXISTS `password_changed_at` datetime NOT NULL DEFAULT current_timestamp() AFTER `password`;
