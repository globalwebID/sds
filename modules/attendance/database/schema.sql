
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
DROP TABLE IF EXISTS `absen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absen` (
  `absen_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `tanggal` date NOT NULL,
  `lokasi_id` int(11) DEFAULT NULL,
  `jam_masuk` varchar(50) DEFAULT NULL,
  `jam_toleransi` varchar(50) DEFAULT NULL,
  `jam_pulang` varchar(50) DEFAULT NULL,
  `absen_in` varchar(20) DEFAULT NULL,
  `absen_out` varchar(20) DEFAULT NULL,
  `foto_in` varchar(70) DEFAULT NULL,
  `foto_out` varchar(70) DEFAULT NULL,
  `status_masuk` varchar(15) DEFAULT NULL,
  `status_pulang` varchar(15) DEFAULT NULL,
  `map_in` varchar(150) DEFAULT NULL,
  `map_out` varchar(150) DEFAULT NULL,
  `kehadiran` varchar(20) DEFAULT NULL,
  `radius` varchar(20) DEFAULT NULL,
  `radius_out` varchar(20) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `client_request_id_in` varchar(64) DEFAULT NULL,
  `client_request_id_out` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`absen_id`),
  UNIQUE KEY `uniq_req_in` (`client_request_id_in`),
  UNIQUE KEY `uniq_req_out` (`client_request_id_out`),
  KEY `idx_absen_tanggal_user` (`tanggal`,`user_id`),
  KEY `idx_absen_tanggal_status` (`tanggal`,`status_masuk`),
  KEY `idx_user_tanggal` (`user_id`,`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `absen_ekbm`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absen_ekbm` (
  `absen_id` int(11) NOT NULL AUTO_INCREMENT,
  `jadwal_id` int(11) NOT NULL DEFAULT 0,
  `pegawai` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `kelas` varchar(50) DEFAULT NULL,
  `pelajaran` varchar(11) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  `keterangan` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`absen_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `absen_pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absen_pegawai` (
  `absen_id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` int(11) NOT NULL DEFAULT 0,
  `tanggal` date NOT NULL,
  `lokasi_id` int(11) DEFAULT NULL,
  `jam_masuk` varchar(50) DEFAULT NULL,
  `jam_toleransi` varchar(50) DEFAULT NULL,
  `jam_pulang` varchar(50) DEFAULT NULL,
  `absen_in` varchar(20) DEFAULT NULL,
  `absen_out` varchar(20) DEFAULT NULL,
  `foto_in` varchar(70) DEFAULT NULL,
  `foto_out` varchar(70) DEFAULT NULL,
  `status_masuk` varchar(15) DEFAULT NULL,
  `status_pulang` varchar(15) DEFAULT NULL,
  `map_in` varchar(150) DEFAULT NULL,
  `map_out` varchar(150) DEFAULT NULL,
  `kehadiran` varchar(20) DEFAULT NULL,
  `radius` varchar(20) DEFAULT NULL,
  `radius_out` varchar(20) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`absen_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `absensi_absen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absensi_absen` (
  `absen_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `tanggal` date NOT NULL,
  `lokasi_id` int(11) DEFAULT NULL,
  `jam_masuk` varchar(50) DEFAULT NULL,
  `jam_toleransi` varchar(50) DEFAULT NULL,
  `jam_pulang` varchar(50) DEFAULT NULL,
  `absen_in` varchar(20) DEFAULT NULL,
  `absen_out` varchar(20) DEFAULT NULL,
  `foto_in` varchar(70) DEFAULT NULL,
  `foto_out` varchar(70) DEFAULT NULL,
  `status_masuk` varchar(15) DEFAULT NULL,
  `status_pulang` varchar(15) DEFAULT NULL,
  `map_in` varchar(150) DEFAULT NULL,
  `map_out` varchar(150) DEFAULT NULL,
  `kehadiran` varchar(20) DEFAULT NULL,
  `radius` varchar(20) DEFAULT NULL,
  `radius_out` varchar(20) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  PRIMARY KEY (`absen_id`),
  KEY `idx_absen_tanggal_user` (`tanggal`,`user_id`),
  KEY `idx_absen_tanggal_status` (`tanggal`,`status_masuk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `absensi_kelas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absensi_kelas` (
  `kelas_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` varchar(10) NOT NULL DEFAULT '0',
  `nama_kelas` varchar(40) NOT NULL,
  PRIMARY KEY (`kelas_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `absensi_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `absensi_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `nisn` varchar(25) DEFAULT NULL,
  `rfid` varchar(30) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(120) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `tempat_lahir` varchar(30) DEFAULT NULL,
  `tanggal_lahir` varchar(50) DEFAULT NULL,
  `jenis_kelamin` varchar(10) DEFAULT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `tahun_ajaran` varchar(25) DEFAULT NULL,
  `lokasi` int(11) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telp` varchar(15) DEFAULT NULL,
  `avatar` varchar(150) DEFAULT NULL,
  `tanggal_registrasi` datetime NOT NULL,
  `tanggal_login` datetime NOT NULL,
  `ip` varchar(30) NOT NULL,
  `browser` varchar(40) DEFAULT NULL,
  `status` varchar(15) DEFAULT NULL,
  `active` enum('Y','N') DEFAULT 'Y',
  PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `fullname` varchar(40) NOT NULL,
  `username` varchar(30) NOT NULL,
  `phone` varchar(15) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(60) NOT NULL,
  `avatar` varchar(150) NOT NULL,
  `registrasi_date` date NOT NULL,
  `tanggal_login` datetime NOT NULL,
  `time` varchar(30) NOT NULL,
  `status` varchar(10) NOT NULL,
  `level` int(11) NOT NULL,
  `ip` varchar(40) NOT NULL,
  `browser` varchar(40) NOT NULL,
  `active` varchar(2) NOT NULL,
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_device_allowlist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_device_allowlist` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `did` varchar(64) NOT NULL,
  `label` varchar(100) NOT NULL DEFAULT '',
  `token` varchar(64) NOT NULL DEFAULT '',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `notes` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_did` (`did`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_devices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `device_id` varchar(64) NOT NULL,
  `label` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `ua` varchar(255) DEFAULT NULL,
  `first_seen` datetime NOT NULL,
  `last_seen` datetime NOT NULL,
  `last_image` varchar(255) DEFAULT NULL,
  `last_page` varchar(255) DEFAULT NULL,
  `hits` int(10) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_device_id` (`device_id`),
  KEY `idx_last_seen` (`last_seen`),
  KEY `idx_ip` (`ip`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `artikel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `artikel` (
  `artikel_id` int(11) NOT NULL AUTO_INCREMENT,
  `penerbit` varchar(50) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `domain` varchar(200) NOT NULL,
  `deskripsi` text NOT NULL,
  `foto` varchar(150) NOT NULL,
  `kategori` varchar(40) NOT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `statistik` varchar(10) NOT NULL,
  `active` varchar(5) NOT NULL,
  PRIMARY KEY (`artikel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bentuk_pelanggaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bentuk_pelanggaran` (
  `bentuk_pelanggaran_id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori_pelanggaran_id` int(11) NOT NULL DEFAULT 0,
  `bentuk_pelanggaran` varchar(150) DEFAULT NULL,
  `bobot` varchar(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bentuk_pelanggaran_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cameras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cameras` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `device_id` varchar(64) NOT NULL,
  `cam_name` varchar(100) NOT NULL,
  `lokasi` varchar(120) DEFAULT NULL,
  `token` varchar(80) NOT NULL,
  `last_seen` datetime DEFAULT NULL,
  `last_ip` varchar(45) DEFAULT NULL,
  `last_image` varchar(255) DEFAULT NULL,
  `stream_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_id` (`device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat` (
  `chat_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `pegawai_id` int(11) NOT NULL DEFAULT 0,
  `pesan` text DEFAULT NULL,
  `files` varchar(100) DEFAULT NULL,
  `ukuran` varchar(20) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  `tujuan` varchar(10) DEFAULT NULL,
  `status_user` varchar(5) DEFAULT NULL,
  `status_pegawai` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `chat_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chat_list` (
  `chat_list_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`chat_list_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `izin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `izin` (
  `izin_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `nama_lengkap` varchar(80) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `files` varchar(100) DEFAULT NULL,
  `alasan` varchar(20) DEFAULT NULL,
  `keterangan` varchar(150) DEFAULT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `status` enum('PENDING','Y','N') NOT NULL DEFAULT 'PENDING',
  PRIMARY KEY (`izin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `izin_pegawai`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `izin_pegawai` (
  `izin_id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` int(11) NOT NULL DEFAULT 0,
  `nama_lengkap` varchar(80) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `tanggal_selesai` date NOT NULL,
  `files` varchar(100) DEFAULT NULL,
  `alasan` varchar(50) DEFAULT NULL,
  `keterangan` varchar(150) DEFAULT NULL,
  `time` time NOT NULL,
  `date` date NOT NULL,
  `status` enum('PENDING','Y','N') NOT NULL DEFAULT 'PENDING',
  PRIMARY KEY (`izin_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jadwal_mengajar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jadwal_mengajar` (
  `jadwal_id` int(11) NOT NULL AUTO_INCREMENT,
  `hari` varchar(50) DEFAULT NULL,
  `pegawai` varchar(11) DEFAULT NULL,
  `mata_pelajaran` varchar(11) DEFAULT NULL,
  `tingkat` varchar(30) DEFAULT NULL,
  `kelas` varchar(30) DEFAULT NULL,
  `dari_jam` varchar(50) DEFAULT NULL,
  `sampai_jam` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`jadwal_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jam_sekolah`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jam_sekolah` (
  `jam_sekolah_id` int(11) NOT NULL AUTO_INCREMENT,
  `hari` varchar(15) NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_telat` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `tipe` varchar(10) DEFAULT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`jam_sekolah_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kartu_nama`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kartu_nama` (
  `kartu_id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(40) DEFAULT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `tipe` varchar(10) DEFAULT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`kartu_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kategori`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori` (
  `kategori_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `seotitle` varchar(50) NOT NULL,
  PRIMARY KEY (`kategori_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kategori_pelanggaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kategori_pelanggaran` (
  `kategori_pelanggaran_id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`kategori_pelanggaran_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lain_lain`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lain_lain` (
  `lain_lain_id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(40) NOT NULL,
  `tipe` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`lain_lain_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `level`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `level` (
  `level_id` int(11) NOT NULL AUTO_INCREMENT,
  `level_nama` varchar(20) NOT NULL,
  PRIMARY KEY (`level_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `libur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `libur` (
  `libur_id` int(11) NOT NULL AUTO_INCREMENT,
  `libur_hari` varchar(20) NOT NULL,
  `active` varchar(5) NOT NULL,
  PRIMARY KEY (`libur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `libur_nasional`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `libur_nasional` (
  `libur_nasional_id` int(11) NOT NULL AUTO_INCREMENT,
  `libur_tanggal` date NOT NULL,
  `keterangan` varchar(60) NOT NULL,
  PRIMARY KEY (`libur_nasional_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lokasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lokasi` (
  `lokasi_id` int(11) NOT NULL AUTO_INCREMENT,
  `lokasi_nama` varchar(30) NOT NULL,
  `lokasi_alamat` text NOT NULL,
  `lokasi_latitude` varchar(100) NOT NULL,
  `lokasi_longitude` varchar(100) NOT NULL,
  `lokasi_radius` varchar(20) NOT NULL,
  `lokasi_qrcode` varchar(100) NOT NULL,
  `lokasi_tanggal` date NOT NULL,
  `lokasi_jam_mulai` time NOT NULL,
  `lokasi_jam_selesai` time NOT NULL,
  `lokasi_status` varchar(2) NOT NULL,
  PRIMARY KEY (`lokasi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mata_pelajaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mata_pelajaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) DEFAULT NULL,
  `nama_mapel` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modul`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modul` (
  `modul_id` int(11) NOT NULL AUTO_INCREMENT,
  `modul_nama` varchar(45) NOT NULL,
  PRIMARY KEY (`modul_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifikasi` (
  `notifikasi_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(20) DEFAULT NULL,
  `pegawai_id` varchar(20) DEFAULT NULL,
  `nama` varchar(50) DEFAULT NULL,
  `keterangan` varchar(150) DEFAULT NULL,
  `link` varchar(20) DEFAULT NULL,
  `tanggal` varchar(40) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  `tipe` varchar(20) DEFAULT NULL,
  `tujuan` varchar(20) DEFAULT NULL,
  `status` varchar(2) DEFAULT NULL,
  PRIMARY KEY (`notifikasi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pelanggaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pelanggaran` (
  `pelanggaran_id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` int(11) DEFAULT 0,
  `kelas` varchar(50) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `jenis_pelanggaran` int(11) DEFAULT NULL,
  `bentuk_pelanggaran` varchar(100) DEFAULT NULL,
  `bobot` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `time` time NOT NULL,
  PRIMARY KEY (`pelanggaran_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rfid_fix_map`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rfid_fix_map` (
  `rfid` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `master_id` int(11) DEFAULT NULL,
  `dup_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `level_id` int(11) NOT NULL,
  `modul_id` int(11) NOT NULL,
  `lihat` varchar(5) NOT NULL,
  `modifikasi` varchar(5) NOT NULL,
  `hapus` varchar(5) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sanksi_pelanggaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sanksi_pelanggaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pegawai_id` int(11) NOT NULL DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `wali_murid` int(11) DEFAULT NULL,
  `ditujukan` varchar(70) DEFAULT NULL,
  `kode_surat` varchar(70) DEFAULT NULL,
  `perihal` varchar(70) DEFAULT NULL,
  `keterangan` text DEFAULT NULL,
  `template` text DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `time` time DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `slider`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `slider` (
  `slider_id` int(11) NOT NULL AUTO_INCREMENT,
  `slider_nama` varchar(50) NOT NULL,
  `slider_url` varchar(50) NOT NULL,
  `foto` varchar(150) NOT NULL,
  `active` varchar(5) NOT NULL,
  PRIMARY KEY (`slider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sso_nonces`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sso_nonces` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nonce` varchar(64) NOT NULL,
  `exp` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nonce` (`nonce`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `template_surat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `template_surat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(15) DEFAULT NULL,
  `template` text DEFAULT NULL,
  `tipe` varchar(60) DEFAULT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `nisn` varchar(25) DEFAULT NULL,
  `rfid` varchar(30) DEFAULT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(120) DEFAULT NULL,
  `nama_lengkap` varchar(100) DEFAULT NULL,
  `tempat_lahir` varchar(30) DEFAULT NULL,
  `tanggal_lahir` varchar(50) DEFAULT NULL,
  `jenis_kelamin` varchar(10) DEFAULT NULL,
  `kelas` varchar(20) DEFAULT NULL,
  `tahun_ajaran` varchar(25) DEFAULT NULL,
  `lokasi` int(11) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `telp` varchar(15) DEFAULT NULL,
  `avatar` varchar(150) DEFAULT NULL,
  `tanggal_registrasi` datetime NOT NULL,
  `tanggal_login` datetime NOT NULL,
  `ip` varchar(30) NOT NULL,
  `browser` varchar(40) DEFAULT NULL,
  `status` varchar(15) DEFAULT NULL,
  `active` enum('Y','N') DEFAULT 'Y',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `uniq_user_rfid` (`rfid`),
  UNIQUE KEY `uniq_user_nisn` (`nisn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wali_murid`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wali_murid` (
  `wali_murid_id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lengkap` varchar(70) DEFAULT NULL,
  `email` varchar(70) DEFAULT NULL,
  `password` varchar(150) DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `jenis_kelamin` varchar(20) DEFAULT NULL,
  `telp` varchar(20) DEFAULT NULL,
  `nisn` varchar(50) DEFAULT NULL,
  `nama_siswa` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `avatar` varchar(100) DEFAULT 'avatar.jpg',
  `tanggal_registrasi` datetime NOT NULL,
  `tanggal_login` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip` varchar(50) DEFAULT NULL,
  `browser` varchar(80) DEFAULT NULL,
  `status` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`wali_murid_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `whatsapp_pesan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_pesan` (
  `whatsapp_pesan_id` int(11) NOT NULL AUTO_INCREMENT,
  `penerima` varchar(70) DEFAULT NULL,
  `tujuan` varchar(50) DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  PRIMARY KEY (`whatsapp_pesan_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

