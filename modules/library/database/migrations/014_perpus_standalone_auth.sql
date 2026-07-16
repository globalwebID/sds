CREATE TABLE IF NOT EXISTS perpus_users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  sds_admin_id INT UNSIGNED DEFAULT NULL,
  username VARCHAR(50) NOT NULL,
  email VARCHAR(120) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  nama_lengkap VARCHAR(120) NOT NULL,
  role ENUM('admin','staf') NOT NULL DEFAULT 'staf',
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  last_login_at DATETIME DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id), UNIQUE KEY uq_perpus_users_sds_admin (sds_admin_id), UNIQUE KEY uq_perpus_users_username (username),
  UNIQUE KEY uq_perpus_users_email (email), KEY idx_perpus_users_status_role (status,role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
