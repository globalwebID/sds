
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
DROP TABLE IF EXISTS `perpus_anggota`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_anggota` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `pemilik_tipe` enum('siswa','pegawai','legacy') NOT NULL,
  `pemilik_id` int(11) DEFAULT NULL,
  `nomor_anggota` varchar(64) NOT NULL,
  `tipe_member_id` int(11) DEFAULT NULL,
  `status_keanggotaan` enum('aktif','nonaktif','perlu_verifikasi') NOT NULL DEFAULT 'aktif',
  `tanggal_daftar` date DEFAULT NULL,
  `tanggal_berakhir` date DEFAULT NULL,
  `legacy_id_anggota` varchar(64) DEFAULT NULL,
  `legacy_nis` varchar(64) DEFAULT NULL,
  `legacy_nama` varchar(150) DEFAULT NULL,
  `legacy_kelas` varchar(60) DEFAULT NULL,
  `legacy_jurusan` varchar(100) DEFAULT NULL,
  `legacy_tanggal_lahir` varchar(30) DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_nomor_anggota` (`nomor_anggota`),
  UNIQUE KEY `uq_perpus_pemilik` (`pemilik_tipe`,`pemilik_id`),
  KEY `idx_perpus_anggota_status` (`status_keanggotaan`),
  KEY `idx_perpus_anggota_legacy` (`legacy_id_anggota`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_audit_check`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_audit_check` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `kode` varchar(60) NOT NULL,
  `tingkat` enum('info','peringatan','kritis') NOT NULL DEFAULT 'info',
  `judul` varchar(180) NOT NULL,
  `jumlah_temuan` int(11) NOT NULL DEFAULT 0,
  `detail_json` longtext DEFAULT NULL,
  `checked_at` datetime NOT NULL DEFAULT current_timestamp(),
  `checked_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_perpus_check_waktu` (`checked_at`),
  KEY `idx_perpus_check_kode` (`kode`,`checked_at`),
  KEY `idx_perpus_check_tingkat` (`tingkat`,`jumlah_temuan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_audit_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(11) DEFAULT NULL,
  `aksi` varchar(40) NOT NULL,
  `entitas` varchar(60) NOT NULL,
  `entitas_id` varchar(80) DEFAULT NULL,
  `ringkasan` varchar(255) DEFAULT NULL,
  `data_lama` longtext DEFAULT NULL,
  `data_baru` longtext DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_perpus_audit_waktu` (`created_at`),
  KEY `idx_perpus_audit_entitas` (`entitas`,`entitas_id`),
  KEY `idx_perpus_audit_admin` (`admin_id`,`created_at`),
  KEY `idx_perpus_audit_aksi` (`aksi`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_bahasa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_bahasa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legacy_kode` varchar(30) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_bahasa_legacy` (`legacy_kode`),
  KEY `idx_perpus_bahasa_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_buku`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_buku` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id_buku` int(11) DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `isbn` varchar(50) DEFAULT NULL,
  `barcode_induk` varchar(64) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `tipe_koleksi_id` int(11) DEFAULT NULL,
  `gmd_id` int(11) DEFAULT NULL,
  `pengarang_id` int(11) DEFAULT NULL,
  `penerbit_id` int(11) DEFAULT NULL,
  `penerbit_teks` varchar(150) DEFAULT NULL,
  `tahun_terbit` varchar(10) DEFAULT NULL,
  `edisi` varchar(100) DEFAULT NULL,
  `klasifikasi` varchar(30) DEFAULT NULL,
  `nomor_panggil` varchar(80) DEFAULT NULL,
  `bahasa` varchar(50) DEFAULT NULL,
  `tempat_terbit` varchar(100) DEFAULT NULL,
  `deskripsi_fisik` text DEFAULT NULL,
  `sampul` varchar(255) DEFAULT NULL,
  `status_opac` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_buku_legacy` (`legacy_id_buku`),
  KEY `idx_perpus_buku_judul` (`judul`),
  KEY `idx_perpus_buku_isbn` (`isbn`),
  KEY `idx_perpus_buku_barcode_induk` (`barcode_induk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_buku_eksemplar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_buku_eksemplar` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id_detail` int(11) DEFAULT NULL,
  `buku_id` bigint(20) unsigned NOT NULL,
  `barcode` varchar(64) NOT NULL,
  `nomor_inventaris` varchar(100) DEFAULT NULL,
  `tipe_koleksi_id` int(11) DEFAULT NULL,
  `lokasi_rak` varchar(100) DEFAULT NULL,
  `kondisi_fisik` enum('baik','rusak','hilang') NOT NULL DEFAULT 'baik',
  `sumber_pengadaan` varchar(100) DEFAULT NULL,
  `harga` decimal(14,2) NOT NULL DEFAULT 0.00,
  `tanggal_pengadaan` date DEFAULT NULL,
  `status` enum('tersedia','dipinjam','rusak','hilang','nonaktif') NOT NULL DEFAULT 'tersedia',
  `tanggal_masuk` datetime NOT NULL DEFAULT current_timestamp(),
  `catatan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_eksemplar_barcode` (`barcode`),
  UNIQUE KEY `uq_perpus_eksemplar_legacy` (`legacy_id_detail`),
  KEY `idx_perpus_eksemplar_buku` (`buku_id`),
  KEY `idx_perpus_eksemplar_status` (`status`),
  KEY `idx_perpus_eksemplar_inventaris` (`nomor_inventaris`),
  KEY `idx_perpus_eksemplar_rak` (`lokasi_rak`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_buku_pengarang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_buku_pengarang` (
  `buku_id` bigint(20) unsigned NOT NULL,
  `pengarang_id` int(11) NOT NULL,
  `level_pengarang` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`buku_id`,`pengarang_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_buku_subyek`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_buku_subyek` (
  `buku_id` bigint(20) unsigned NOT NULL,
  `subyek_id` int(11) NOT NULL,
  PRIMARY KEY (`buku_id`,`subyek_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_denda_pembayaran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_denda_pembayaran` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `detail_id` bigint(20) unsigned NOT NULL,
  `anggota_id` bigint(20) unsigned NOT NULL,
  `jenis` enum('pembayaran','keringanan','pembebasan','koreksi') NOT NULL DEFAULT 'pembayaran',
  `nominal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `catatan` varchar(255) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_perpus_denda_detail` (`detail_id`),
  KEY `idx_perpus_denda_anggota` (`anggota_id`),
  KEY `idx_perpus_denda_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_gmd`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_gmd` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legacy_id` int(11) DEFAULT NULL,
  `kode` varchar(30) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_gmd_legacy` (`legacy_id`),
  KEY `idx_perpus_gmd_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_import_batch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_import_batch` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `jenis` enum('koleksi','eksemplar') NOT NULL,
  `nama_file` varchar(255) DEFAULT NULL,
  `total_baris` int(11) NOT NULL DEFAULT 0,
  `berhasil` int(11) NOT NULL DEFAULT 0,
  `diperbarui` int(11) NOT NULL DEFAULT 0,
  `dilewati` int(11) NOT NULL DEFAULT 0,
  `gagal` int(11) NOT NULL DEFAULT 0,
  `ringkasan` longtext DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_perpus_import_jenis` (`jenis`),
  KEY `idx_perpus_import_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_kategori_buku`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_kategori_buku` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legacy_id` int(11) DEFAULT NULL,
  `kode_kategori` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_kategori_legacy` (`legacy_id`),
  KEY `idx_perpus_kategori_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_kunjungan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_kunjungan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id` int(11) DEFAULT NULL,
  `anggota_id` bigint(20) unsigned NOT NULL,
  `waktu_kunjungan` datetime NOT NULL DEFAULT current_timestamp(),
  `sumber` enum('rfid','manual','migrasi','kiosk') NOT NULL DEFAULT 'rfid',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_kunjungan_legacy` (`legacy_id`),
  KEY `idx_perpus_kunjungan_anggota` (`anggota_id`),
  KEY `idx_perpus_kunjungan_waktu` (`waktu_kunjungan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_migrasi_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_migrasi_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sumber_database` varchar(100) DEFAULT NULL,
  `status` enum('proses','selesai','gagal') NOT NULL DEFAULT 'proses',
  `ringkasan` longtext DEFAULT NULL,
  `pesan_error` text DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `started_at` datetime NOT NULL DEFAULT current_timestamp(),
  `finished_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_perpus_migrasi_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_notifikasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_notifikasi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `anggota_id` bigint(20) unsigned NOT NULL,
  `tipe` varchar(40) NOT NULL DEFAULT 'informasi',
  `judul` varchar(180) NOT NULL,
  `pesan` text NOT NULL,
  `referensi_tipe` varchar(40) DEFAULT NULL,
  `referensi_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('baru','dibaca') NOT NULL DEFAULT 'baru',
  `dibaca_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_perpus_notifikasi_anggota` (`anggota_id`,`status`,`created_at`),
  KEY `idx_perpus_notifikasi_referensi` (`referensi_tipe`,`referensi_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_peminjaman`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_peminjaman` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id_pinjam` varchar(40) DEFAULT NULL,
  `anggota_id` bigint(20) unsigned NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `tanggal_pinjam` date NOT NULL,
  `jumlah_item` int(11) NOT NULL DEFAULT 0,
  `status` enum('aktif','selesai','dibatalkan') NOT NULL DEFAULT 'aktif',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_pinjam_legacy` (`legacy_id_pinjam`),
  KEY `idx_perpus_pinjam_anggota` (`anggota_id`),
  KEY `idx_perpus_pinjam_status` (`status`),
  KEY `idx_perpus_pinjam_tanggal` (`tanggal_pinjam`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_peminjaman_detail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_peminjaman_detail` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `legacy_id_detail` int(11) DEFAULT NULL,
  `peminjaman_id` bigint(20) unsigned NOT NULL,
  `buku_id` bigint(20) unsigned DEFAULT NULL,
  `eksemplar_id` bigint(20) unsigned DEFAULT NULL,
  `kode_resi` varchar(64) DEFAULT NULL,
  `tanggal_jatuh_tempo` date DEFAULT NULL,
  `jumlah_perpanjangan` int(11) NOT NULL DEFAULT 0,
  `tanggal_kembali` datetime DEFAULT NULL,
  `denda_awal` decimal(14,2) NOT NULL DEFAULT 0.00,
  `denda` decimal(14,2) NOT NULL DEFAULT 0.00,
  `denda_status` enum('belum_lunas','lunas','dibebaskan') NOT NULL DEFAULT 'belum_lunas',
  `status` enum('dipinjam','kembali','hilang','rusak') NOT NULL DEFAULT 'dipinjam',
  `kondisi_kembali` enum('baik','rusak','hilang') NOT NULL DEFAULT 'baik',
  `catatan_kembali` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_detail_legacy` (`legacy_id_detail`),
  KEY `idx_perpus_detail_pinjam` (`peminjaman_id`),
  KEY `idx_perpus_detail_buku` (`buku_id`),
  KEY `idx_perpus_detail_eksemplar` (`eksemplar_id`),
  KEY `idx_perpus_detail_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_penerbit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_penerbit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legacy_id` int(11) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_penerbit_legacy` (`legacy_id`),
  KEY `idx_perpus_penerbit_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_pengarang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_pengarang` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legacy_id` int(11) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  `tipe` varchar(10) DEFAULT 'p',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_pengarang_legacy` (`legacy_id`),
  KEY `idx_perpus_pengarang_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_pengaturan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_pengaturan` (
  `kode` varchar(80) NOT NULL,
  `nilai` text DEFAULT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_pengingat_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_pengingat_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `detail_id` bigint(20) unsigned NOT NULL,
  `anggota_id` bigint(20) unsigned NOT NULL,
  `jenis` enum('akan_jatuh_tempo','jatuh_tempo','terlambat') NOT NULL,
  `tanggal_proses` date NOT NULL,
  `pesan` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_pengingat_harian` (`detail_id`,`jenis`,`tanggal_proses`),
  KEY `idx_perpus_pengingat_anggota` (`anggota_id`,`tanggal_proses`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_perpanjangan`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_perpanjangan` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `detail_id` bigint(20) unsigned NOT NULL,
  `tanggal_perpanjang` datetime NOT NULL DEFAULT current_timestamp(),
  `jatuh_tempo_lama` date NOT NULL,
  `jatuh_tempo_baru` date NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `catatan` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_perpus_perpanjangan_detail` (`detail_id`),
  KEY `idx_perpus_perpanjangan_tanggal` (`tanggal_perpanjang`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_reservasi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_reservasi` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `anggota_id` bigint(20) unsigned NOT NULL,
  `buku_id` bigint(20) unsigned NOT NULL,
  `eksemplar_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('menunggu','siap','diambil','dibatalkan','kedaluwarsa') NOT NULL DEFAULT 'menunggu',
  `tanggal_reservasi` datetime NOT NULL DEFAULT current_timestamp(),
  `tanggal_siap` datetime DEFAULT NULL,
  `batas_ambil` datetime DEFAULT NULL,
  `tanggal_selesai` datetime DEFAULT NULL,
  `sumber` enum('opac','admin') NOT NULL DEFAULT 'opac',
  `catatan` varchar(255) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_perpus_reservasi_anggota` (`anggota_id`,`status`),
  KEY `idx_perpus_reservasi_buku` (`buku_id`,`status`),
  KEY `idx_perpus_reservasi_batas` (`status`,`batas_ambil`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_saran`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_saran` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `anggota_id` bigint(20) unsigned DEFAULT NULL,
  `nama_pengirim` varchar(150) DEFAULT NULL,
  `kontak` varchar(150) DEFAULT NULL,
  `kategori` enum('kritik','saran','keluhan','apresiasi') NOT NULL DEFAULT 'saran',
  `judul` varchar(180) NOT NULL,
  `pesan` text NOT NULL,
  `status` enum('baru','diproses','selesai','ditolak') NOT NULL DEFAULT 'baru',
  `jawaban` text DEFAULT NULL,
  `sumber` enum('opac','admin') NOT NULL DEFAULT 'opac',
  `ip_address` varchar(45) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `ditanggapi_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_perpus_saran_status` (`status`,`created_at`),
  KEY `idx_perpus_saran_anggota` (`anggota_id`),
  KEY `idx_perpus_saran_kategori` (`kategori`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_subyek`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_subyek` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legacy_id` int(11) DEFAULT NULL,
  `nama` varchar(150) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_subyek_legacy` (`legacy_id`),
  KEY `idx_perpus_subyek_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_tempat`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_tempat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legacy_id` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_tempat_legacy` (`legacy_id`),
  KEY `idx_perpus_tempat_nama` (`nama`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_tipe_koleksi`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_tipe_koleksi` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legacy_id` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_tipe_koleksi_nama` (`nama`),
  UNIQUE KEY `uq_perpus_tipe_koleksi_legacy` (`legacy_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_tipe_member`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_tipe_member` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `legacy_kode_tipe` int(11) DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `jumlah_peminjaman` int(11) NOT NULL DEFAULT 2,
  `periode_peminjaman` int(11) NOT NULL DEFAULT 7,
  `denda_per_hari` decimal(14,2) NOT NULL DEFAULT 0.00,
  `maksimal_perpanjangan` int(11) NOT NULL DEFAULT 1,
  `hari_perpanjangan` int(11) NOT NULL DEFAULT 7,
  `status_aktif` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_tipe_nama` (`nama`),
  UNIQUE KEY `uq_perpus_tipe_legacy` (`legacy_kode_tipe`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `perpus_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `perpus_users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `sds_admin_id` int(10) unsigned DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(120) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(120) NOT NULL,
  `role` enum('admin','staf') NOT NULL DEFAULT 'staf',
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `last_login_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_perpus_users_username` (`username`),
  UNIQUE KEY `uq_perpus_users_email` (`email`),
  UNIQUE KEY `uq_perpus_users_sds_admin` (`sds_admin_id`),
  KEY `idx_perpus_users_status_role` (`status`,`role`)
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

