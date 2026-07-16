CREATE TABLE IF NOT EXISTS `sds_module_installations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` varchar(64) NOT NULL,
  `package_name` varchar(128) NOT NULL,
  `version` varchar(40) NOT NULL,
  `package_checksum` char(64) NOT NULL,
  `status` enum('installed','failed','removed') NOT NULL DEFAULT 'installed',
  `installed_by` int DEFAULT NULL,
  `details` longtext DEFAULT NULL,
  `installed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sds_module_installations_module` (`module_id`,`installed_at`),
  KEY `idx_sds_module_installations_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sds_module_migrations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `module_id` varchar(64) NOT NULL,
  `migration` varchar(255) NOT NULL,
  `checksum` char(64) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sds_module_migration` (`module_id`,`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
