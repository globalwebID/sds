
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(40) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` char(60) NOT NULL,
  `password_changed_at` datetime NOT NULL DEFAULT current_timestamp(),
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','staff','kesiswaan') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_admin_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_admin_access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned NOT NULL,
  `application` varchar(30) NOT NULL,
  `app_role` varchar(30) NOT NULL DEFAULT 'admin',
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_admin_application` (`admin_id`,`application`),
  KEY `idx_application_active` (`application`,`active`),
  CONSTRAINT `fk_app_admin_access_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `berkas_pelanggaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `berkas_pelanggaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_psiswa` int(11) NOT NULL,
  `nama_pelanggaran` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_psiswa` (`id_psiswa`) USING BTREE,
  CONSTRAINT `berkas_pelanggaran_ibfk_1` FOREIGN KEY (`id_psiswa`) REFERENCES `pendaftaran_siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `berkas_tambahan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `berkas_tambahan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_siswa` int(11) NOT NULL,
  `nama_berkas` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `id_siswa` (`id_siswa`),
  CONSTRAINT `berkas_tambahan_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `pendaftaran_siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cetak_ttd_daftar_ulang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cetak_ttd_daftar_ulang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) NOT NULL,
  `ttd_mode` varchar(20) NOT NULL DEFAULT 'auto',
  `nama_ttd` varchar(150) DEFAULT NULL,
  `hubungan_ttd` varchar(80) DEFAULT NULL,
  `hp_ttd` varchar(50) DEFAULT NULL,
  `alamat_sama_siswa` tinyint(1) NOT NULL DEFAULT 1,
  `alamat_ttd` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_siswa_id` (`siswa_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ekskul_absensi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ekskul_absensi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) DEFAULT NULL,
  `ekskul_id` int(11) DEFAULT NULL,
  `status` enum('H','I','S','A','P') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_absen_per_status` (`siswa_id`,`ekskul_id`,`tanggal`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ekskul_materi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ekskul_materi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ekskul_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `ekskul_id` (`ekskul_id`),
  CONSTRAINT `ekskul_materi_ibfk_1` FOREIGN KEY (`ekskul_id`) REFERENCES `ekstrakurikuler` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ekstrakurikuler`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ekstrakurikuler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_ekskul` varchar(100) NOT NULL,
  `tahun_ajaran` varchar(10) NOT NULL,
  `nama_pembina` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ekstrakurikuler_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ekstrakurikuler_siswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) NOT NULL,
  `ekstrakurikuler_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `siswa_id` (`siswa_id`),
  KEY `ekstrakurikuler_id` (`ekstrakurikuler_id`),
  CONSTRAINT `ekstrakurikuler_siswa_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `pendaftaran_siswa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ekstrakurikuler_siswa_ibfk_2` FOREIGN KEY (`ekstrakurikuler_id`) REFERENCES `ekstrakurikuler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `form_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `form_fields` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `type` enum('text','email','checkbox','file','date','select') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `formulir`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `formulir` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) DEFAULT NULL,
  `nilai` varchar(100) DEFAULT NULL,
  `kirim_pesan` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `informasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `informasi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `judul` varchar(255) NOT NULL,
  `isi` longtext DEFAULT NULL,
  `tanggal` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `informasi_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `informasi_user` (
  `user_id` int(11) NOT NULL,
  `informasi_id` int(11) NOT NULL,
  `dibaca` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`user_id`,`informasi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jurusan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jurusan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode_jurusan` varchar(20) NOT NULL,
  `nama_jurusan` varchar(100) NOT NULL,
  `tahun_ajaran` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kartu_rfid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kartu_rfid` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(64) NOT NULL,
  `pemilik_tipe` enum('siswa','pegawai') NOT NULL,
  `pemilik_id` int(11) NOT NULL,
  `tanggal_terbit` date DEFAULT NULL,
  `tanggal_berakhir` date DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kartu_uid` (`uid`),
  UNIQUE KEY `uq_kartu_pemilik` (`pemilik_tipe`,`pemilik_id`),
  KEY `idx_kartu_pemilik` (`pemilik_tipe`,`pemilik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kartu_rfid_riwayat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kartu_rfid_riwayat` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uid` varchar(64) NOT NULL,
  `pemilik_tipe` enum('siswa','pegawai') NOT NULL,
  `pemilik_id` int(11) NOT NULL,
  `status_akhir` enum('diganti','dilepas','hilang','rusak','migrasi') NOT NULL DEFAULT 'dilepas',
  `tanggal_mulai` datetime DEFAULT NULL,
  `tanggal_selesai` datetime NOT NULL DEFAULT current_timestamp(),
  `keterangan` varchar(255) DEFAULT NULL,
  `diproses_oleh` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kartu_riwayat_uid` (`uid`),
  KEY `idx_kartu_riwayat_pemilik` (`pemilik_tipe`,`pemilik_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kelas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kelas` varchar(50) DEFAULT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `jurusan_id` int(11) NOT NULL,
  `wali_kelas` varchar(50) NOT NULL,
  `kuota` int(11) NOT NULL,
  `terisi` int(11) NOT NULL DEFAULT 0,
  `tingkat_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `jurusan_id` (`jurusan_id`),
  CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`jurusan_id`) REFERENCES `jurusan` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `log_aktivitas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `aksi` text DEFAULT NULL,
  `keterangan` text NOT NULL,
  `waktu` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `log_notifikasi_wa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_notifikasi_wa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_scan_id` int(11) NOT NULL,
  `waktu_kirim` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `log_scan_rfid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log_scan_rfid` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) DEFAULT NULL,
  `waktu` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nilai_ekskul`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nilai_ekskul` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) NOT NULL,
  `ekskul_id` int(11) NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `nilai` decimal(5,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `siswa_id` (`siswa_id`),
  KEY `ekskul_id` (`ekskul_id`),
  CONSTRAINT `nilai_ekskul_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `pendaftaran_siswa` (`id`) ON DELETE CASCADE,
  CONSTRAINT `nilai_ekskul_ibfk_2` FOREIGN KEY (`ekskul_id`) REFERENCES `ekstrakurikuler` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pegawai` (
  `pegawai_id` int(11) NOT NULL AUTO_INCREMENT,
  `nip` varchar(30) NOT NULL,
  `rfid` varchar(50) DEFAULT NULL,
  `qrcode` varchar(50) DEFAULT NULL,
  `nama_lengkap` varchar(60) DEFAULT NULL,
  `email` varchar(60) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `tempat_lahir` varchar(30) DEFAULT NULL,
  `tanggal_lahir` varchar(20) DEFAULT NULL,
  `jenis_kelamin` varchar(20) DEFAULT NULL,
  `jabatan` varchar(45) DEFAULT NULL,
  `wali_kelas` varchar(45) DEFAULT NULL,
  `lokasi` int(11) NOT NULL DEFAULT 0,
  `telp` varchar(15) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `avatar` varchar(150) DEFAULT NULL,
  `tanggal_registrasi` datetime NOT NULL,
  `tanggal_login` datetime NOT NULL,
  `ip` varchar(20) DEFAULT NULL,
  `browser` varchar(40) DEFAULT NULL,
  `status` enum('Offline','Online') NOT NULL DEFAULT 'Offline',
  `active` enum('Y','N') NOT NULL DEFAULT 'Y',
  PRIMARY KEY (`pegawai_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pendaftaran_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pendaftaran_siswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `pin` varchar(255) DEFAULT NULL,
  `nipd` varchar(20) DEFAULT NULL,
  `sekolah_asal` varchar(100) NOT NULL,
  `nomor_ijazah` varchar(50) NOT NULL,
  `jenis_kelamin` varchar(10) NOT NULL,
  `tempat_lahir` varchar(50) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `no_kk` varchar(30) NOT NULL,
  `nik` varchar(30) NOT NULL,
  `no_registrasi_akta` varchar(50) NOT NULL,
  `kebutuhan_khusus` varchar(100) DEFAULT NULL,
  `agama` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `desa` varchar(100) NOT NULL,
  `kecamatan` varchar(100) NOT NULL,
  `kota` varchar(100) NOT NULL,
  `provinsi` varchar(50) NOT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `tempat_tinggal` varchar(50) NOT NULL,
  `moda_transportasi` varchar(50) NOT NULL,
  `anak_ke` int(11) NOT NULL,
  `jumlah_saudara_kandung` int(11) NOT NULL,
  `tinggi_badan` int(11) NOT NULL,
  `berat_badan` int(11) NOT NULL,
  `hobi` varchar(100) NOT NULL,
  `cita_cita` varchar(100) NOT NULL,
  `nomor_kip` varchar(50) DEFAULT NULL,
  `nomor_kps` varchar(50) DEFAULT NULL,
  `nomor_pkh` varchar(50) DEFAULT NULL,
  `nomor_kks` varchar(50) DEFAULT NULL,
  `nomor_kis` varchar(50) DEFAULT NULL,
  `file_kip` varchar(255) DEFAULT NULL,
  `file_kps` varchar(255) DEFAULT NULL,
  `file_pkh` varchar(255) DEFAULT NULL,
  `file_kks` varchar(255) DEFAULT NULL,
  `file_kis` varchar(255) DEFAULT NULL,
  `nama_ayah` varchar(100) NOT NULL,
  `nik_ayah` varchar(30) NOT NULL,
  `tahun_lahir_ayah` int(11) NOT NULL,
  `pendidikan_ayah` varchar(50) NOT NULL,
  `pekerjaan_ayah` varchar(50) NOT NULL,
  `penghasilan_ayah` varchar(50) DEFAULT NULL,
  `nama_ibu` varchar(100) NOT NULL,
  `nik_ibu` varchar(30) NOT NULL,
  `tahun_lahir_ibu` int(11) NOT NULL,
  `pendidikan_ibu` varchar(50) NOT NULL,
  `pekerjaan_ibu` varchar(50) NOT NULL,
  `penghasilan_ibu` varchar(50) DEFAULT NULL,
  `nama_wali` varchar(100) DEFAULT NULL,
  `nik_wali` varchar(30) DEFAULT NULL,
  `tahun_lahir_wali` int(11) DEFAULT NULL,
  `pendidikan_wali` varchar(50) DEFAULT NULL,
  `pekerjaan_wali` varchar(50) DEFAULT NULL,
  `penghasilan_wali` varchar(50) DEFAULT NULL,
  `nohp_ortu` varchar(20) DEFAULT NULL,
  `nohp_siswa` varchar(20) NOT NULL,
  `file_kk` varchar(255) DEFAULT NULL,
  `file_ijazah` varchar(255) NOT NULL,
  `file_akta` varchar(255) DEFAULT NULL,
  `foto` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pernyataan_setuju` tinyint(1) DEFAULT 0,
  `tanggal_input` timestamp NOT NULL DEFAULT current_timestamp(),
  `tahun_ajaran` varchar(50) DEFAULT NULL,
  `sudah_dapodik` tinyint(1) DEFAULT 0,
  `status_aktif` tinyint(1) DEFAULT 1,
  `alasan_nonaktif` text DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `jurusan_id` int(11) DEFAULT NULL,
  `rfid_uid` varchar(50) DEFAULT NULL,
  `saldo` int(11) NOT NULL DEFAULT 0,
  `blokir` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nisn` (`nisn`),
  UNIQUE KEY `uniq_pendaftaran_nisn` (`nisn`),
  UNIQUE KEY `nipd` (`nipd`),
  UNIQUE KEY `rfid` (`rfid_uid`),
  KEY `kelas_id` (`kelas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pengaturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_sekolah` varchar(255) DEFAULT NULL,
  `npsn` varchar(20) DEFAULT NULL,
  `kementerian` varchar(150) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `desa` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `kabupaten` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `telepon` varchar(30) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `kepala_sekolah` varchar(150) DEFAULT NULL,
  `nip_kepala_sekolah` varchar(40) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `favicon` varchar(255) DEFAULT NULL,
  `kop_surat` varchar(255) DEFAULT NULL,
  `ttd_kepala_sekolah` varchar(255) DEFAULT NULL,
  `stempel` varchar(255) DEFAULT NULL,
  `kartu_orientasi` enum('potrait','landscape') NOT NULL DEFAULT 'potrait',
  `kartu_lebar_mm` decimal(5,2) NOT NULL DEFAULT 53.98,
  `kartu_tinggi_mm` decimal(5,2) NOT NULL DEFAULT 85.60,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `maintenance_message` varchar(500) DEFAULT 'Sistem sedang dalam pemeliharaan. Silakan coba kembali beberapa saat lagi.',
  `backup_schedule` enum('disabled','daily','weekly') NOT NULL DEFAULT 'disabled',
  `backup_retention_days` smallint(5) unsigned NOT NULL DEFAULT 30,
  `login_max_attempts` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `login_window_minutes` smallint(5) unsigned NOT NULL DEFAULT 5,
  `admin_session_minutes` smallint(5) unsigned NOT NULL DEFAULT 30,
  `password_expiry_days` smallint(5) unsigned NOT NULL DEFAULT 0,
  `system_timezone` varchar(64) NOT NULL DEFAULT 'Asia/Jakarta',
  `date_format` varchar(20) NOT NULL DEFAULT 'd/m/Y',
  `number_locale` varchar(10) NOT NULL DEFAULT 'id_ID',
  `last_backup_at` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pengaturan_nipd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pengaturan_nipd` (
  `jurusan_id` int(11) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `kode_depan` int(11) DEFAULT NULL,
  `urutan_awal` int(11) DEFAULT NULL,
  `kode_akhir` int(11) DEFAULT NULL,
  `urutan_akhir` int(11) DEFAULT NULL,
  PRIMARY KEY (`jurusan_id`,`tahun_ajaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sds_admin_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sds_admin_sessions` (
  `session_hash` char(64) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `last_activity` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`session_hash`),
  KEY `idx_sds_admin_sessions_admin` (`admin_id`,`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sds_module_installations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sds_module_installations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` varchar(64) NOT NULL,
  `package_name` varchar(128) NOT NULL,
  `version` varchar(40) NOT NULL,
  `package_checksum` char(64) NOT NULL,
  `status` enum('installed','failed','removed') NOT NULL DEFAULT 'installed',
  `installed_by` int(11) DEFAULT NULL,
  `details` longtext DEFAULT NULL,
  `installed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_sds_module_installations_module` (`module_id`,`installed_at`),
  KEY `idx_sds_module_installations_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sds_module_migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sds_module_migrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` varchar(64) NOT NULL,
  `migration` varchar(255) NOT NULL,
  `checksum` char(64) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sds_module_migration` (`module_id`,`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sds_pengaturan_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sds_pengaturan_audit` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `bagian` varchar(50) NOT NULL,
  `perubahan` longtext NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_pengaturan_audit_admin` (`admin_id`,`created_at`),
  KEY `idx_pengaturan_audit_bagian` (`bagian`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `setting`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setting` (
  `site_id` int(11) NOT NULL AUTO_INCREMENT,
  `site_name` varchar(50) NOT NULL,
  `nama_sekolah` varchar(100) DEFAULT NULL,
  `kementrian` varchar(100) DEFAULT NULL,
  `npsn` varchar(100) DEFAULT NULL,
  `desa` varchar(100) DEFAULT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `kabupaten` varchar(100) DEFAULT NULL,
  `propinsi` varchar(100) DEFAULT NULL,
  `kepala_sekolah` varchar(100) DEFAULT NULL,
  `nip_kepala_sekolah` varchar(100) DEFAULT NULL,
  `site_phone` char(12) DEFAULT NULL,
  `site_address` text DEFAULT NULL,
  `site_owner` varchar(50) DEFAULT NULL,
  `site_logo` varchar(100) DEFAULT NULL,
  `site_favicon` varchar(60) DEFAULT NULL,
  `site_kop` varchar(100) DEFAULT NULL,
  `ttd_kepsek` varchar(100) DEFAULT NULL,
  `stempel` varchar(100) DEFAULT NULL,
  `site_url` varchar(100) DEFAULT NULL,
  `site_email` varchar(30) DEFAULT NULL,
  `gmail_host` varchar(50) DEFAULT NULL,
  `gmail_username` varchar(30) DEFAULT NULL,
  `gmail_password` varchar(50) DEFAULT NULL,
  `gmail_port` varchar(10) DEFAULT NULL,
  `gmail_active` varchar(5) DEFAULT NULL,
  `google_client_id` varchar(200) DEFAULT NULL,
  `google_client_secret` varchar(200) DEFAULT NULL,
  `google_client_active` varchar(5) DEFAULT NULL,
  `tipe_absen_siswa` varchar(30) DEFAULT NULL,
  `tipe_absen_pegawai` varchar(10) DEFAULT NULL,
  `tipe_absen_layar` varchar(30) DEFAULT NULL,
  `tipe_absen_layar_pegawai` varchar(30) NOT NULL DEFAULT 'qrcode',
  `timezone` varchar(50) DEFAULT NULL,
  `whatsapp_phone` varchar(30) DEFAULT NULL,
  `whatsapp_token` varchar(200) DEFAULT NULL,
  `secret_key` varchar(40) DEFAULT NULL,
  `whatsapp_domain` varchar(100) DEFAULT NULL,
  `whatsapp_tipe` varchar(20) DEFAULT NULL,
  `whatsapp_template` text DEFAULT NULL,
  `whatsapp_active` enum('Y','N') DEFAULT 'N',
  PRIMARY KEY (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `siswa_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `siswa_kelas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `siswa_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `naik_kelas` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_siswa_kelas_tahun` (`siswa_id`,`tahun_ajaran`),
  KEY `siswa_id` (`siswa_id`),
  KEY `kelas_id` (`kelas_id`),
  KEY `idx_siswa_kelas_kelas_tahun` (`kelas_id`,`tahun_ajaran`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tahun_ajaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tahun_ajaran` (
  `tahun_ajaran_id` int(11) NOT NULL AUTO_INCREMENT,
  `tahun_ajaran` varchar(50) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `semester_aktif` varchar(10) NOT NULL DEFAULT 'ganjil',
  `tanggal_mulai` date DEFAULT NULL,
  `tanggal_selesai` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `activated_at` datetime DEFAULT NULL,
  `activated_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`tahun_ajaran_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `template_kartu`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `template_kartu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) DEFAULT NULL,
  `mode` enum('landscape','potrait') DEFAULT 'landscape',
  `front` longtext DEFAULT NULL,
  `back` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tingkat_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tingkat_kelas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_tingkat` varchar(10) NOT NULL,
  `urutan_tingkat` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nama_tingkat` (`nama_tingkat`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tmp_pendaftaran_siswa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tmp_pendaftaran_siswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nisn` varchar(20) NOT NULL,
  `pin` varchar(6) DEFAULT NULL,
  `nipd` varchar(20) DEFAULT NULL,
  `sekolah_asal` varchar(100) NOT NULL,
  `nomor_ijazah` varchar(50) NOT NULL,
  `jenis_kelamin` varchar(10) NOT NULL,
  `tempat_lahir` varchar(50) NOT NULL,
  `tanggal_lahir` date NOT NULL,
  `no_kk` varchar(30) NOT NULL,
  `nik` varchar(30) NOT NULL,
  `no_registrasi_akta` varchar(50) NOT NULL,
  `kebutuhan_khusus` varchar(100) DEFAULT NULL,
  `agama` varchar(20) NOT NULL,
  `alamat` text NOT NULL,
  `desa` varchar(100) NOT NULL,
  `kecamatan` varchar(100) NOT NULL,
  `kota` varchar(100) NOT NULL,
  `provinsi` varchar(50) NOT NULL,
  `latitude` decimal(10,6) NOT NULL,
  `longitude` decimal(10,6) NOT NULL,
  `tempat_tinggal` varchar(50) NOT NULL,
  `moda_transportasi` varchar(50) NOT NULL,
  `anak_ke` int(11) NOT NULL,
  `jumlah_saudara_kandung` int(11) NOT NULL,
  `tinggi_badan` int(11) NOT NULL,
  `berat_badan` int(11) NOT NULL,
  `hobi` varchar(100) NOT NULL,
  `cita_cita` varchar(100) NOT NULL,
  `nomor_kip` varchar(50) DEFAULT NULL,
  `nomor_kps` varchar(50) DEFAULT NULL,
  `nomor_pkh` varchar(50) DEFAULT NULL,
  `nomor_kks` varchar(50) DEFAULT NULL,
  `nomor_kis` varchar(50) DEFAULT NULL,
  `file_kip` varchar(255) DEFAULT NULL,
  `file_kps` varchar(255) DEFAULT NULL,
  `file_pkh` varchar(255) DEFAULT NULL,
  `file_kks` varchar(255) DEFAULT NULL,
  `file_kis` varchar(255) DEFAULT NULL,
  `nama_ayah` varchar(100) NOT NULL,
  `nik_ayah` varchar(30) NOT NULL,
  `tahun_lahir_ayah` int(11) NOT NULL,
  `pendidikan_ayah` varchar(50) NOT NULL,
  `pekerjaan_ayah` varchar(50) NOT NULL,
  `penghasilan_ayah` varchar(50) DEFAULT NULL,
  `nama_ibu` varchar(100) NOT NULL,
  `nik_ibu` varchar(30) NOT NULL,
  `tahun_lahir_ibu` int(11) NOT NULL,
  `pendidikan_ibu` varchar(50) NOT NULL,
  `pekerjaan_ibu` varchar(50) NOT NULL,
  `penghasilan_ibu` varchar(50) DEFAULT NULL,
  `nama_wali` varchar(100) DEFAULT NULL,
  `nik_wali` varchar(30) DEFAULT NULL,
  `tahun_lahir_wali` int(11) DEFAULT NULL,
  `pendidikan_wali` varchar(50) DEFAULT NULL,
  `pekerjaan_wali` varchar(50) DEFAULT NULL,
  `penghasilan_wali` varchar(50) DEFAULT NULL,
  `nohp_ortu` varchar(20) DEFAULT NULL,
  `nohp_siswa` varchar(20) NOT NULL,
  `file_kk` varchar(255) DEFAULT NULL,
  `file_ijazah` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `pernyataan_setuju` tinyint(1) DEFAULT 0,
  `tanggal_input` timestamp NOT NULL DEFAULT current_timestamp(),
  `tahun_ajaran` varchar(50) DEFAULT NULL,
  `sudah_dapodik` tinyint(1) DEFAULT 0,
  `status_aktif` tinyint(1) DEFAULT 1,
  `alasan_nonaktif` text DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `jurusan_id` int(11) DEFAULT NULL,
  `rfid_uid` varchar(50) DEFAULT NULL,
  `saldo` int(11) NOT NULL DEFAULT 0,
  `blokir` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nisn` (`nisn`),
  UNIQUE KEY `nipd` (`nipd`),
  UNIQUE KEY `rfid` (`rfid_uid`),
  KEY `kelas_id` (`kelas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

