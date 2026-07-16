-- E-Perpustakaan SDS v2.5
-- Reservasi, notifikasi internal, pengingat jatuh tempo, dan kiosk kunjungan.
CREATE TABLE IF NOT EXISTS perpus_reservasi (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 anggota_id BIGINT UNSIGNED NOT NULL,
 buku_id BIGINT UNSIGNED NOT NULL,
 eksemplar_id BIGINT UNSIGNED DEFAULT NULL,
 status ENUM('menunggu','siap','diambil','dibatalkan','kedaluwarsa') NOT NULL DEFAULT 'menunggu',
 tanggal_reservasi DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 tanggal_siap DATETIME DEFAULT NULL,
 batas_ambil DATETIME DEFAULT NULL,
 tanggal_selesai DATETIME DEFAULT NULL,
 sumber ENUM('opac','admin') NOT NULL DEFAULT 'opac',
 catatan VARCHAR(255) DEFAULT NULL,
 admin_id INT DEFAULT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 PRIMARY KEY(id), KEY idx_perpus_reservasi_anggota(anggota_id,status),
 KEY idx_perpus_reservasi_buku(buku_id,status), KEY idx_perpus_reservasi_batas(status,batas_ambil)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS perpus_notifikasi (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, anggota_id BIGINT UNSIGNED NOT NULL,
 tipe VARCHAR(40) NOT NULL DEFAULT 'informasi', judul VARCHAR(180) NOT NULL, pesan TEXT NOT NULL,
 referensi_tipe VARCHAR(40) DEFAULT NULL, referensi_id BIGINT UNSIGNED DEFAULT NULL,
 status ENUM('baru','dibaca') NOT NULL DEFAULT 'baru', dibaca_at DATETIME DEFAULT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY(id),
 KEY idx_perpus_notifikasi_anggota(anggota_id,status,created_at),
 KEY idx_perpus_notifikasi_referensi(referensi_tipe,referensi_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS perpus_pengingat_log (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, detail_id BIGINT UNSIGNED NOT NULL,
 anggota_id BIGINT UNSIGNED NOT NULL, jenis ENUM('akan_jatuh_tempo','jatuh_tempo','terlambat') NOT NULL,
 tanggal_proses DATE NOT NULL, pesan VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id), UNIQUE KEY uq_perpus_pengingat_harian(detail_id,jenis,tanggal_proses),
 KEY idx_perpus_pengingat_anggota(anggota_id,tanggal_proses)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
ALTER TABLE perpus_kunjungan MODIFY COLUMN sumber ENUM('rfid','manual','migrasi','kiosk') NOT NULL DEFAULT 'rfid';

INSERT INTO perpus_pengaturan (kode,nilai,keterangan) VALUES
('reservasi_aktif','1','Aktifkan reservasi atau inden melalui OPAC'),
('reservasi_maks_per_anggota','3','Maksimal reservasi aktif per anggota'),
('reservasi_hari_ambil','2','Batas pengambilan setelah reservasi siap'),
('pengingat_aktif','1','Aktifkan notifikasi pengingat jatuh tempo'),
('pengingat_hari_sebelum','2','Jumlah hari pengingat sebelum jatuh tempo'),
('kiosk_kunjungan_aktif','1','Aktifkan kiosk kunjungan mandiri'),
('kiosk_judul','Kunjungan Perpustakaan','Judul halaman kiosk kunjungan'),
('kiosk_tolak_ganda','1','Tolak pencatatan kunjungan ganda pada hari yang sama'),
('reminder_token','','Token endpoint pengingat terjadwal'),
('schema_version','2.5.0','Versi schema modul Perpustakaan SDS')
ON DUPLICATE KEY UPDATE keterangan=VALUES(keterangan), nilai=CASE WHEN kode='schema_version' THEN VALUES(nilai) ELSE nilai END;
