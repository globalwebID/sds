CREATE TABLE IF NOT EXISTS `app_admin_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `application` enum('absensi','mkantin','library') NOT NULL,
  `app_role` varchar(30) NOT NULL DEFAULT 'admin',
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_application` (`admin_id`,`application`),
  KEY `idx_application_active` (`application`,`active`),
  CONSTRAINT `fk_app_admin_access_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Superadmin SDS memperoleh akses awal ke seluruh aplikasi yang tersedia.
INSERT IGNORE INTO `app_admin_access` (`admin_id`,`application`,`app_role`,`active`)
SELECT `id`, 'absensi', 'superadmin', 'Y' FROM `admins` WHERE `role` = 'superadmin';

INSERT IGNORE INTO `app_admin_access` (`admin_id`,`application`,`app_role`,`active`)
SELECT `id`, 'mkantin', 'superadmin', 'Y' FROM `admins` WHERE `role` = 'superadmin';

INSERT IGNORE INTO `app_admin_access` (`admin_id`,`application`,`app_role`,`active`)
SELECT `id`, 'library', 'admin', 'Y' FROM `admins` WHERE `role` = 'superadmin';
