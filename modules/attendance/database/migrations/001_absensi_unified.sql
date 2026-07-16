
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

-- Tabel user dan absen digunakan bersama oleh SDS dan modul Absensi.
-- IF NOT EXISTS membuat migration ini aman dijalankan berulang kali.
ALTER TABLE `absen`
  ADD COLUMN IF NOT EXISTS `client_request_id_in` varchar(64) DEFAULT NULL AFTER `keterangan`,
  ADD COLUMN IF NOT EXISTS `client_request_id_out` varchar(64) DEFAULT NULL AFTER `client_request_id_in`,
  ADD UNIQUE INDEX IF NOT EXISTS `uniq_req_in` (`client_request_id_in`),
  ADD UNIQUE INDEX IF NOT EXISTS `uniq_req_out` (`client_request_id_out`),
  ADD INDEX IF NOT EXISTS `idx_user_tanggal` (`user_id`,`tanggal`);

ALTER TABLE `user`
  ADD UNIQUE INDEX IF NOT EXISTS `uniq_user_rfid` (`rfid`),
  ADD UNIQUE INDEX IF NOT EXISTS `uniq_user_nisn` (`nisn`);

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `absen_ekbm` (
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `absen_pegawai` (
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
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `admin` (
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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `app_devices` (
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
) ENGINE=InnoDB AUTO_INCREMENT=223145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `app_device_allowlist` (
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `artikel` (
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `bentuk_pelanggaran` (
  `bentuk_pelanggaran_id` int(11) NOT NULL AUTO_INCREMENT,
  `kategori_pelanggaran_id` int(11) NOT NULL DEFAULT 0,
  `bentuk_pelanggaran` varchar(150) DEFAULT NULL,
  `bobot` varchar(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`bentuk_pelanggaran_id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `cameras` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `chat` (
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `chat_list` (
  `chat_list_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `pegawai_id` int(11) DEFAULT NULL,
  `datetime` datetime NOT NULL,
  PRIMARY KEY (`chat_list_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `izin` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `izin_pegawai` (
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
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `jadwal_mengajar` (
  `jadwal_id` int(11) NOT NULL AUTO_INCREMENT,
  `hari` varchar(50) DEFAULT NULL,
  `pegawai` varchar(11) DEFAULT NULL,
  `mata_pelajaran` varchar(11) DEFAULT NULL,
  `tingkat` varchar(30) DEFAULT NULL,
  `kelas` varchar(30) DEFAULT NULL,
  `dari_jam` varchar(50) DEFAULT NULL,
  `sampai_jam` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`jadwal_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `jam_sekolah` (
  `jam_sekolah_id` int(11) NOT NULL AUTO_INCREMENT,
  `hari` varchar(15) NOT NULL,
  `jam_masuk` time DEFAULT NULL,
  `jam_telat` time DEFAULT NULL,
  `jam_pulang` time DEFAULT NULL,
  `tipe` varchar(10) DEFAULT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`jam_sekolah_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=22 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `kartu_nama` (
  `kartu_id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(40) DEFAULT NULL,
  `foto` varchar(100) DEFAULT NULL,
  `tipe` varchar(10) DEFAULT NULL,
  `active` enum('Y','N') NOT NULL DEFAULT 'N',
  PRIMARY KEY (`kartu_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `kategori` (
  `kategori_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `seotitle` varchar(50) NOT NULL,
  PRIMARY KEY (`kategori_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `kategori_pelanggaran` (
  `kategori_pelanggaran_id` int(11) NOT NULL AUTO_INCREMENT,
  `nama_kategori` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`kategori_pelanggaran_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `absensi_kelas` (
  `kelas_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` varchar(10) NOT NULL DEFAULT '0',
  `nama_kelas` varchar(40) NOT NULL,
  PRIMARY KEY (`kelas_id`)
) ENGINE=InnoDB AUTO_INCREMENT=54 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `lain_lain` (
  `lain_lain_id` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(40) NOT NULL,
  `tipe` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`lain_lain_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `level` (
  `level_id` int(11) NOT NULL AUTO_INCREMENT,
  `level_nama` varchar(20) NOT NULL,
  PRIMARY KEY (`level_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `libur` (
  `libur_id` int(11) NOT NULL AUTO_INCREMENT,
  `libur_hari` varchar(20) NOT NULL,
  `active` varchar(5) NOT NULL,
  PRIMARY KEY (`libur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `libur_nasional` (
  `libur_nasional_id` int(11) NOT NULL AUTO_INCREMENT,
  `libur_tanggal` date NOT NULL,
  `keterangan` varchar(60) NOT NULL,
  PRIMARY KEY (`libur_nasional_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `lokasi` (
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `mata_pelajaran` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) DEFAULT NULL,
  `nama_mapel` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `modul` (
  `modul_id` int(11) NOT NULL AUTO_INCREMENT,
  `modul_nama` varchar(45) NOT NULL,
  PRIMARY KEY (`modul_id`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `notifikasi` (
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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `pegawai` (
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
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `pelanggaran` (
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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `rfid_fix_map` (
  `rfid` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `master_id` int(11) DEFAULT NULL,
  `dup_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `role` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `level_id` int(11) NOT NULL,
  `modul_id` int(11) NOT NULL,
  `lihat` varchar(5) NOT NULL,
  `modifikasi` varchar(5) NOT NULL,
  `hapus` varchar(5) NOT NULL,
  PRIMARY KEY (`role_id`)
) ENGINE=InnoDB AUTO_INCREMENT=78 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sanksi_pelanggaran` (
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `setting` (
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `slider` (
  `slider_id` int(11) NOT NULL AUTO_INCREMENT,
  `slider_nama` varchar(50) NOT NULL,
  `slider_url` varchar(50) NOT NULL,
  `foto` varchar(150) NOT NULL,
  `active` varchar(5) NOT NULL,
  PRIMARY KEY (`slider_id`)
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `sso_nonces` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nonce` varchar(64) NOT NULL,
  `exp` int(10) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_nonce` (`nonce`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `tahun_ajaran` (
  `tahun_ajaran_id` int(11) NOT NULL AUTO_INCREMENT,
  `tahun_ajaran` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`tahun_ajaran_id`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `template_surat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(15) DEFAULT NULL,
  `template` text DEFAULT NULL,
  `tipe` varchar(60) DEFAULT NULL,
  `date` date DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `wali_murid` (
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE IF NOT EXISTS `whatsapp_pesan` (
  `whatsapp_pesan_id` int(11) NOT NULL AUTO_INCREMENT,
  `penerima` varchar(70) DEFAULT NULL,
  `tujuan` varchar(50) DEFAULT NULL,
  `pesan` text DEFAULT NULL,
  `date` date NOT NULL,
  `time` time NOT NULL,
  PRIMARY KEY (`whatsapp_pesan_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
