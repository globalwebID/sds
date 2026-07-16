-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jul 12, 2026 at 10:12 PM
-- Server version: 10.11.16-MariaDB-cll-lve
-- PHP Version: 8.4.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `wira6492_sds`
--

DELIMITER $$
--
-- Procedures
--
$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `absen`
--

CREATE TABLE `absen` (
  `absen_id` int(11) NOT NULL,
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
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absen`
--


-- --------------------------------------------------------

--
-- Table structure for table `absensi_absen`
--

CREATE TABLE `absensi_absen` (
  `absen_id` int(11) NOT NULL,
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
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absensi_absen`
--


-- --------------------------------------------------------

--
-- Table structure for table `absensi_user`
--

CREATE TABLE `absensi_user` (
  `user_id` int(11) NOT NULL,
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
  `active` enum('Y','N') DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `absensi_user`
--


-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `id` int(10) UNSIGNED NOT NULL,
  `username` varchar(40) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` char(60) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('superadmin','staff','kesiswaan') DEFAULT 'staff',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admins`
--


-- --------------------------------------------------------

--
-- Table structure for table `anjungan`
--

CREATE TABLE `anjungan` (
  `id` int(11) NOT NULL,
  `nama_anjungan` varchar(100) NOT NULL,
  `background` varchar(255) DEFAULT NULL,
  `video` varchar(255) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anjungan`
--


-- --------------------------------------------------------

--
-- Table structure for table `anjungan_berita`
--

CREATE TABLE `anjungan_berita` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `dilihat` int(11) DEFAULT NULL,
  `jenis` enum('berita','pengumuman') DEFAULT 'berita',
  `status` enum('biasa','terbaru','populer') DEFAULT 'biasa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anjungan_berita`
--


-- --------------------------------------------------------

--
-- Table structure for table `anjungan_instagram_video`
--

CREATE TABLE `anjungan_instagram_video` (
  `id` int(11) NOT NULL,
  `url` text NOT NULL,
  `status` enum('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
  `urutan` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anjungan_instagram_video`
--


-- --------------------------------------------------------

--
-- Table structure for table `anjungan_menu`
--

CREATE TABLE `anjungan_menu` (
  `id` int(11) NOT NULL,
  `nama_menu` varchar(100) DEFAULT NULL,
  `link` text DEFAULT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anjungan_menu`
--


-- --------------------------------------------------------

--
-- Table structure for table `anjungan_topright`
--

CREATE TABLE `anjungan_topright` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `deskripsi` text DEFAULT NULL,
  `icon_url` text DEFAULT NULL,
  `link_url` text DEFAULT NULL,
  `tipe` enum('link','modal','dropdown') DEFAULT 'link',
  `target_modal` varchar(100) DEFAULT NULL,
  `urutan` int(11) DEFAULT 0,
  `status` enum('aktif','nonaktif') DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anjungan_topright`
--


-- --------------------------------------------------------

--
-- Table structure for table `berkas_pelanggaran`
--

CREATE TABLE `berkas_pelanggaran` (
  `id` int(11) NOT NULL,
  `id_psiswa` int(11) NOT NULL,
  `nama_pelanggaran` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `berkas_tambahan`
--

CREATE TABLE `berkas_tambahan` (
  `id` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `nama_berkas` varchar(255) NOT NULL,
  `file` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cetak_ttd_daftar_ulang`
--

CREATE TABLE `cetak_ttd_daftar_ulang` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `ttd_mode` varchar(20) NOT NULL DEFAULT 'auto',
  `nama_ttd` varchar(150) DEFAULT NULL,
  `hubungan_ttd` varchar(80) DEFAULT NULL,
  `hp_ttd` varchar(50) DEFAULT NULL,
  `alamat_sama_siswa` tinyint(1) NOT NULL DEFAULT 1,
  `alamat_ttd` text DEFAULT NULL,
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cetak_ttd_daftar_ulang`
--


-- --------------------------------------------------------

--
-- Table structure for table `ekskul_absensi`
--

CREATE TABLE `ekskul_absensi` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) DEFAULT NULL,
  `ekskul_id` int(11) DEFAULT NULL,
  `status` enum('H','I','S','A','P') NOT NULL,
  `keterangan` text DEFAULT NULL,
  `tanggal` date DEFAULT NULL,
  `jam` time DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ekskul_absensi`
--


-- --------------------------------------------------------

--
-- Table structure for table `ekskul_materi`
--

CREATE TABLE `ekskul_materi` (
  `id` int(11) NOT NULL,
  `ekskul_id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ekstrakurikuler`
--

CREATE TABLE `ekstrakurikuler` (
  `id` int(11) NOT NULL,
  `nama_ekskul` varchar(100) NOT NULL,
  `tahun_ajaran` varchar(10) NOT NULL,
  `nama_pembina` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ekstrakurikuler`
--


-- --------------------------------------------------------

--
-- Table structure for table `ekstrakurikuler_siswa`
--

CREATE TABLE `ekstrakurikuler_siswa` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `ekstrakurikuler_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ekstrakurikuler_siswa`
--


-- --------------------------------------------------------

--
-- Table structure for table `expo_visitors`
--

CREATE TABLE `expo_visitors` (
  `id` int(11) NOT NULL,
  `name` varchar(120) DEFAULT NULL,
  `position` varchar(120) DEFAULT NULL,
  `company` varchar(150) DEFAULT NULL,
  `type` varchar(50) DEFAULT NULL,
  `phone` varchar(40) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `interest` varchar(80) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `expo_visitors`
--


-- --------------------------------------------------------

--
-- Table structure for table `fcm_tokens`
--

CREATE TABLE `fcm_tokens` (
  `id` int(11) NOT NULL,
  `id_siswa` int(11) NOT NULL,
  `nisn` varchar(20) DEFAULT NULL,
  `token` text NOT NULL,
  `device` varchar(40) DEFAULT 'android',
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `formulir`
--

CREATE TABLE `formulir` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `nilai` varchar(100) DEFAULT NULL,
  `kirim_pesan` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `formulir`
--


-- --------------------------------------------------------

--
-- Table structure for table `form_fields`
--

CREATE TABLE `form_fields` (
  `id` int(11) NOT NULL,
  `name` varchar(50) DEFAULT NULL,
  `label` varchar(100) DEFAULT NULL,
  `type` enum('text','email','checkbox','file','date','select') DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT NULL,
  `is_required` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `form_fields`
--


-- --------------------------------------------------------

--
-- Table structure for table `game_brands`
--

CREATE TABLE `game_brands` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `need_zone_id` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `game_brands`
--


-- --------------------------------------------------------

--
-- Table structure for table `game_callback_logs`
--

CREATE TABLE `game_callback_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref_id` varchar(100) DEFAULT NULL,
  `payload` longtext NOT NULL,
  `ip_address` varchar(100) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `game_callback_logs`
--


-- --------------------------------------------------------

--
-- Table structure for table `game_margin_brand`
--

CREATE TABLE `game_margin_brand` (
  `id` int(11) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `margin` int(11) DEFAULT 500,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `game_margin_brand`
--


-- --------------------------------------------------------

--
-- Table structure for table `game_products`
--

CREATE TABLE `game_products` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(30) NOT NULL DEFAULT 'digiflazz',
  `brand` varchar(100) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `type` varchar(100) DEFAULT NULL,
  `sku_code` varchar(100) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `price_buy` int(11) NOT NULL DEFAULT 0,
  `price_sell` int(11) NOT NULL DEFAULT 0,
  `profit` int(11) NOT NULL DEFAULT 0,
  `seller_name` varchar(150) DEFAULT NULL,
  `buyer_product_status` tinyint(1) NOT NULL DEFAULT 1,
  `buyer_last_update` datetime DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `brand_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `game_products`
--


-- --------------------------------------------------------

--
-- Table structure for table `game_transactions`
--

CREATE TABLE `game_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `ref_id` varchar(100) NOT NULL,
  `id_siswa` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED NOT NULL,
  `provider` varchar(30) NOT NULL DEFAULT 'digiflazz',
  `provider_trx_id` varchar(100) DEFAULT NULL,
  `invoice_no` varchar(100) DEFAULT NULL,
  `brand` varchar(100) NOT NULL,
  `product_name` varchar(150) NOT NULL,
  `sku_code` varchar(100) NOT NULL,
  `user_id_game` varchar(100) NOT NULL,
  `zone_id` varchar(100) DEFAULT NULL,
  `server_id` varchar(100) DEFAULT NULL,
  `nickname` varchar(150) DEFAULT NULL,
  `price_buy` int(11) NOT NULL DEFAULT 0,
  `price_sell` int(11) NOT NULL DEFAULT 0,
  `profit` int(11) NOT NULL DEFAULT 0,
  `before_balance` int(11) NOT NULL DEFAULT 0,
  `after_balance` int(11) NOT NULL DEFAULT 0,
  `status` enum('CREATED','PROCESSING','SUCCESS','FAILED','REFUNDED') NOT NULL DEFAULT 'CREATED',
  `provider_status` varchar(100) DEFAULT NULL,
  `sn` text DEFAULT NULL,
  `message` text DEFAULT NULL,
  `provider_response` longtext DEFAULT NULL,
  `callback_response` longtext DEFAULT NULL,
  `refund_amount` int(11) NOT NULL DEFAULT 0,
  `refunded_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `game_transactions`
--


-- --------------------------------------------------------

--
-- Table structure for table `informasi`
--

CREATE TABLE `informasi` (
  `id` int(11) NOT NULL,
  `judul` varchar(255) NOT NULL,
  `isi` longtext DEFAULT NULL,
  `tanggal` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `informasi`
--


-- --------------------------------------------------------

--
-- Table structure for table `informasi_user`
--

CREATE TABLE `informasi_user` (
  `user_id` int(11) NOT NULL,
  `informasi_id` int(11) NOT NULL,
  `dibaca` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `informasi_user`
--


-- --------------------------------------------------------

--
-- Table structure for table `jurusan`
--

CREATE TABLE `jurusan` (
  `id` int(11) NOT NULL,
  `kode_jurusan` varchar(20) NOT NULL,
  `nama_jurusan` varchar(100) NOT NULL,
  `tahun_ajaran` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `jurusan`
--


-- --------------------------------------------------------

--
-- Table structure for table `kantin`
--

CREATE TABLE `kantin` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `lokasi` varchar(255) DEFAULT NULL,
  `gambar` varchar(255) DEFAULT NULL,
  `status_toko` enum('buka','tutup') DEFAULT 'tutup',
  `saldo` bigint(20) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kantin`
--


-- --------------------------------------------------------

--
-- Table structure for table `kelas`
--

CREATE TABLE `kelas` (
  `id` int(11) NOT NULL,
  `nama_kelas` varchar(50) DEFAULT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `jurusan_id` int(11) NOT NULL,
  `wali_kelas` varchar(50) NOT NULL,
  `kuota` int(11) NOT NULL,
  `terisi` int(11) NOT NULL DEFAULT 0,
  `tingkat_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `kelas`
--


-- --------------------------------------------------------

--
-- Table structure for table `log_aktivitas`
--

CREATE TABLE `log_aktivitas` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `aksi` text DEFAULT NULL,
  `keterangan` text NOT NULL,
  `waktu` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_aktivitas`
--


-- --------------------------------------------------------

--
-- Table structure for table `log_notifikasi_wa`
--

CREATE TABLE `log_notifikasi_wa` (
  `id` int(11) NOT NULL,
  `log_scan_id` int(11) NOT NULL,
  `waktu_kirim` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_scan_rfid`
--

CREATE TABLE `log_scan_rfid` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) DEFAULT NULL,
  `waktu` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `log_transfer`
--

CREATE TABLE `log_transfer` (
  `id` int(11) NOT NULL,
  `id_pengirim` int(11) NOT NULL,
  `id_penerima` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `log_transfer`
--


-- --------------------------------------------------------

--
-- Table structure for table `nilai_ekskul`
--

CREATE TABLE `nilai_ekskul` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `ekskul_id` int(11) NOT NULL,
  `semester` enum('Ganjil','Genap') NOT NULL,
  `nilai` decimal(5,2) NOT NULL,
  `keterangan` varchar(255) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `penarikan`
--

CREATE TABLE `penarikan` (
  `id` int(11) NOT NULL,
  `id_kantin` int(11) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `tanggal` datetime DEFAULT current_timestamp(),
  `status` enum('diproses','berhasil','ditolak','gagal') DEFAULT 'diproses',
  `keterangan` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `penarikan`
--


-- --------------------------------------------------------

--
-- Table structure for table `pendaftaran_siswa`
--

CREATE TABLE `pendaftaran_siswa` (
  `id` int(11) NOT NULL,
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
  `blokir` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

--
-- Dumping data for table `pendaftaran_siswa`
--


--
-- Triggers `pendaftaran_siswa`
--
DELIMITER $$
CREATE TRIGGER `trg_sync_rfid_after_update` AFTER UPDATE ON `pendaftaran_siswa` FOR EACH ROW trg: BEGIN
  -- cegah loop / pantulan
  IF COALESCE(@SYNC_LOCK,'') <> '' THEN
    LEAVE trg;
  END IF;
  SET @SYNC_LOCK = 'SDS_RFID';

  -- jalan hanya kalau RFID berubah (null-safe compare)
  IF NOT (NEW.rfid_uid <=> OLD.rfid_uid) THEN

    -- pastikan RFID baru valid
    IF NEW.rfid_uid IS NOT NULL AND NEW.rfid_uid <> '' THEN

      -- 1) ABSENSI: update RFID berdasarkan NISN
      UPDATE `wira6492_Absensi-sekolah-V.3`.`user`
      SET rfid = NEW.rfid_uid
      WHERE CONVERT(nisn USING utf8mb4) = CONVERT(NEW.nisn USING utf8mb4);

      -- 2) PERPUS: ganti ID_ANGGOTA lama -> baru
      IF OLD.rfid_uid IS NOT NULL AND OLD.rfid_uid <> '' THEN
        UPDATE `wira6492_mantrila_perpustakaan`.`anggota`
        SET ID_ANGGOTA = NEW.rfid_uid
        WHERE CONVERT(ID_ANGGOTA USING utf8mb4) = CONVERT(OLD.rfid_uid USING utf8mb4);
      END IF;

      -- 3) fallback: update berdasarkan NIS
      UPDATE `wira6492_mantrila_perpustakaan`.`anggota`
      SET ID_ANGGOTA = NEW.rfid_uid
      WHERE NIS IS NOT NULL AND NIS <> ''
        AND CONVERT(NIS USING utf8mb4) = CONVERT(NEW.nisn USING utf8mb4);

    END IF;
  END IF;

  SET @SYNC_LOCK = '';
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `id` int(11) NOT NULL,
  `nama_sekolah` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `kop_surat` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan`
--


-- --------------------------------------------------------

--
-- Table structure for table `pengaturan_nipd`
--

CREATE TABLE `pengaturan_nipd` (
  `jurusan_id` int(11) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `kode_depan` int(11) DEFAULT NULL,
  `urutan_awal` int(11) DEFAULT NULL,
  `kode_akhir` int(11) DEFAULT NULL,
  `urutan_akhir` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pengaturan_nipd`
--


-- --------------------------------------------------------

--
-- Table structure for table `siswa_kelas`
--

CREATE TABLE `siswa_kelas` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `kelas_id` int(11) NOT NULL,
  `tahun_ajaran` varchar(20) NOT NULL,
  `naik_kelas` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `siswa_kelas`
--


--
-- Triggers `siswa_kelas`
--
DELIMITER $$
CREATE TRIGGER `update_terisi_after_delete` AFTER DELETE ON `siswa_kelas` FOR EACH ROW BEGIN
            UPDATE kelas
            SET terisi = (SELECT COUNT(*) FROM siswa_kelas WHERE kelas_id = OLD.kelas_id)
            WHERE id = OLD.kelas_id;
        END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `update_terisi_after_insert` AFTER INSERT ON `siswa_kelas` FOR EACH ROW BEGIN
            UPDATE kelas
            SET terisi = (SELECT COUNT(*) FROM siswa_kelas WHERE kelas_id = NEW.kelas_id)
            WHERE id = NEW.kelas_id;
        END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `sp_inventaris`
--

CREATE TABLE `sp_inventaris` (
  `id` int(11) NOT NULL,
  `nama_barang` varchar(255) NOT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `sub_kategori_id` int(11) DEFAULT NULL,
  `kode_barang` varchar(50) DEFAULT NULL,
  `jumlah` int(11) NOT NULL DEFAULT 1,
  `kondisi` enum('Layak','Tidak Layak','Rusak') DEFAULT 'Layak',
  `ruangan_id` int(11) DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL,
  `tanggal_input` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sp_inventaris`
--


-- --------------------------------------------------------

--
-- Table structure for table `sp_inventaris_ruangan`
--

CREATE TABLE `sp_inventaris_ruangan` (
  `id` int(11) NOT NULL,
  `ruangan_id` int(11) NOT NULL,
  `inventaris_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sp_inventaris_ruangan`
--


-- --------------------------------------------------------

--
-- Table structure for table `sp_kategori`
--

CREATE TABLE `sp_kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sp_kategori`
--


-- --------------------------------------------------------

--
-- Table structure for table `sp_laporan_sarpras`
--

CREATE TABLE `sp_laporan_sarpras` (
  `id` int(11) NOT NULL,
  `siswa_id` int(11) NOT NULL,
  `inventaris_id` int(11) DEFAULT NULL,
  `judul` varchar(255) NOT NULL,
  `keterangan` text NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `status` enum('dalam antrian','diperbaiki','selesai') DEFAULT 'dalam antrian',
  `created_at` datetime NOT NULL,
  `ruangan_id` int(11) DEFAULT NULL,
  `kelas_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sp_laporan_sarpras`
--


-- --------------------------------------------------------

--
-- Table structure for table `sp_masalah`
--

CREATE TABLE `sp_masalah` (
  `id` int(11) NOT NULL,
  `id_inventaris` int(11) NOT NULL,
  `masalah` text NOT NULL,
  `tanggal_input` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sp_ruangan`
--

CREATE TABLE `sp_ruangan` (
  `id` int(11) NOT NULL,
  `kode_ruang` varchar(50) DEFAULT NULL,
  `nama_ruangan` varchar(100) NOT NULL,
  `lokasi` varchar(100) NOT NULL,
  `keterangan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sp_ruangan`
--


-- --------------------------------------------------------

--
-- Table structure for table `sp_sub_kategori`
--

CREATE TABLE `sp_sub_kategori` (
  `id` int(11) NOT NULL,
  `kategori_id` int(11) NOT NULL,
  `nama_sub_kategori` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sp_sub_kategori`
--


-- --------------------------------------------------------

--
-- Table structure for table `sp_users`
--

CREATE TABLE `sp_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `role` enum('superadmin','admin','teknisi','bangunan','siswa') NOT NULL,
  `id_siswa` int(11) DEFAULT NULL,
  `nomor_wa` varchar(20) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `id_ruangan` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sp_users`
--


-- --------------------------------------------------------

--
-- Table structure for table `survey_kepuasan`
--

CREATE TABLE `survey_kepuasan` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `penilaian` int(11) NOT NULL CHECK (`penilaian` between 1 and 5),
  `saran` text DEFAULT NULL,
  `waktu` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `survey_kepuasan`
--


-- --------------------------------------------------------

--
-- Table structure for table `template_kartu`
--

CREATE TABLE `template_kartu` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) DEFAULT NULL,
  `mode` enum('landscape','potrait') DEFAULT 'landscape',
  `front` longtext DEFAULT NULL,
  `back` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `template_kartu`
--


-- --------------------------------------------------------

--
-- Table structure for table `tingkat_kelas`
--

CREATE TABLE `tingkat_kelas` (
  `id` int(11) NOT NULL,
  `nama_tingkat` varchar(10) NOT NULL,
  `urutan_tingkat` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tingkat_kelas`
--


-- --------------------------------------------------------

--
-- Table structure for table `tmp_pendaftaran_siswa`
--

CREATE TABLE `tmp_pendaftaran_siswa` (
  `id` int(11) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `nisn` varchar(20) NOT NULL,
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
  `blokir` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `topup`
--

CREATE TABLE `topup` (
  `id` int(11) NOT NULL,
  `id_siswa` int(11) DEFAULT NULL,
  `petugas_id` int(11) DEFAULT NULL,
  `tanggal` datetime DEFAULT NULL,
  `nominal` int(11) DEFAULT NULL,
  `saldo_akhir` int(11) NOT NULL DEFAULT 0,
  `merchant_order_id` varchar(64) DEFAULT NULL,
  `duitku_reference` varchar(64) DEFAULT NULL,
  `status` enum('PENDING','PAID','FAILED','EXPIRED') NOT NULL DEFAULT 'PENDING',
  `paid_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `topup`
--


-- --------------------------------------------------------

--
-- Table structure for table `transaksi_kantin`
--

CREATE TABLE `transaksi_kantin` (
  `id` int(11) NOT NULL,
  `id_siswa` int(11) DEFAULT NULL,
  `tanggal` datetime DEFAULT NULL,
  `nominal` int(11) DEFAULT NULL,
  `id_kantin` int(11) NOT NULL,
  `status_dilayani` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaksi_kantin`
--


-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
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
  `active` enum('Y','N') DEFAULT 'Y'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--


-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('superadmin','admin','operator','kantin') NOT NULL,
  `id_kantin` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--


--
-- Indexes for dumped tables
--

--
-- Indexes for table `absen`
--
ALTER TABLE `absen`
  ADD PRIMARY KEY (`absen_id`),
  ADD KEY `idx_absen_tanggal_user` (`tanggal`,`user_id`),
  ADD KEY `idx_absen_tanggal_status` (`tanggal`,`status_masuk`);

--
-- Indexes for table `absensi_absen`
--
ALTER TABLE `absensi_absen`
  ADD PRIMARY KEY (`absen_id`),
  ADD KEY `idx_absen_tanggal_user` (`tanggal`,`user_id`),
  ADD KEY `idx_absen_tanggal_status` (`tanggal`,`status_masuk`);

--
-- Indexes for table `absensi_user`
--
ALTER TABLE `absensi_user`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `anjungan`
--
ALTER TABLE `anjungan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `anjungan_berita`
--
ALTER TABLE `anjungan_berita`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `anjungan_instagram_video`
--
ALTER TABLE `anjungan_instagram_video`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `anjungan_menu`
--
ALTER TABLE `anjungan_menu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `anjungan_topright`
--
ALTER TABLE `anjungan_topright`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `berkas_pelanggaran`
--
ALTER TABLE `berkas_pelanggaran`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_psiswa` (`id_psiswa`) USING BTREE;

--
-- Indexes for table `berkas_tambahan`
--
ALTER TABLE `berkas_tambahan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_siswa` (`id_siswa`);

--
-- Indexes for table `cetak_ttd_daftar_ulang`
--
ALTER TABLE `cetak_ttd_daftar_ulang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_siswa_id` (`siswa_id`);

--
-- Indexes for table `ekskul_absensi`
--
ALTER TABLE `ekskul_absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_absen_per_status` (`siswa_id`,`ekskul_id`,`tanggal`,`status`);

--
-- Indexes for table `ekskul_materi`
--
ALTER TABLE `ekskul_materi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ekskul_id` (`ekskul_id`);

--
-- Indexes for table `ekstrakurikuler`
--
ALTER TABLE `ekstrakurikuler`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ekstrakurikuler_siswa`
--
ALTER TABLE `ekstrakurikuler_siswa`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `ekstrakurikuler_id` (`ekstrakurikuler_id`);

--
-- Indexes for table `expo_visitors`
--
ALTER TABLE `expo_visitors`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_siswa` (`id_siswa`);

--
-- Indexes for table `formulir`
--
ALTER TABLE `formulir`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama` (`nama`);

--
-- Indexes for table `form_fields`
--
ALTER TABLE `form_fields`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game_brands`
--
ALTER TABLE `game_brands`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `game_callback_logs`
--
ALTER TABLE `game_callback_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ref_id` (`ref_id`);

--
-- Indexes for table `game_margin_brand`
--
ALTER TABLE `game_margin_brand`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `brand` (`brand`);

--
-- Indexes for table `game_products`
--
ALTER TABLE `game_products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_sku_code` (`sku_code`),
  ADD KEY `idx_brand` (`brand`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_active` (`is_active`),
  ADD KEY `idx_brand_id` (`brand_id`);

--
-- Indexes for table `game_transactions`
--
ALTER TABLE `game_transactions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_ref_id` (`ref_id`),
  ADD KEY `idx_siswa` (`id_siswa`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_brand` (`brand`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `informasi`
--
ALTER TABLE `informasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `informasi_user`
--
ALTER TABLE `informasi_user`
  ADD PRIMARY KEY (`user_id`,`informasi_id`);

--
-- Indexes for table `jurusan`
--
ALTER TABLE `jurusan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kantin`
--
ALTER TABLE `kantin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kelas`
--
ALTER TABLE `kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jurusan_id` (`jurusan_id`);

--
-- Indexes for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_notifikasi_wa`
--
ALTER TABLE `log_notifikasi_wa`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_scan_rfid`
--
ALTER TABLE `log_scan_rfid`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `log_transfer`
--
ALTER TABLE `log_transfer`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_pengirim` (`id_pengirim`),
  ADD KEY `id_penerima` (`id_penerima`);

--
-- Indexes for table `nilai_ekskul`
--
ALTER TABLE `nilai_ekskul`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `ekskul_id` (`ekskul_id`);

--
-- Indexes for table `penarikan`
--
ALTER TABLE `penarikan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_kantin` (`id_kantin`);

--
-- Indexes for table `pendaftaran_siswa`
--
ALTER TABLE `pendaftaran_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD UNIQUE KEY `uniq_pendaftaran_nisn` (`nisn`),
  ADD UNIQUE KEY `nipd` (`nipd`),
  ADD UNIQUE KEY `rfid` (`rfid_uid`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengaturan_nipd`
--
ALTER TABLE `pengaturan_nipd`
  ADD PRIMARY KEY (`jurusan_id`,`tahun_ajaran`);

--
-- Indexes for table `siswa_kelas`
--
ALTER TABLE `siswa_kelas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indexes for table `sp_inventaris`
--
ALTER TABLE `sp_inventaris`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`),
  ADD KEY `sub_kategori_id` (`sub_kategori_id`),
  ADD KEY `ruangan_id` (`ruangan_id`);

--
-- Indexes for table `sp_inventaris_ruangan`
--
ALTER TABLE `sp_inventaris_ruangan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ruangan_id` (`ruangan_id`),
  ADD KEY `inventaris_id` (`inventaris_id`);

--
-- Indexes for table `sp_kategori`
--
ALTER TABLE `sp_kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sp_laporan_sarpras`
--
ALTER TABLE `sp_laporan_sarpras`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sp_masalah`
--
ALTER TABLE `sp_masalah`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_inventaris` (`id_inventaris`);

--
-- Indexes for table `sp_ruangan`
--
ALTER TABLE `sp_ruangan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sp_sub_kategori`
--
ALTER TABLE `sp_sub_kategori`
  ADD PRIMARY KEY (`id`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `sp_users`
--
ALTER TABLE `sp_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_siswa` (`id_siswa`);

--
-- Indexes for table `survey_kepuasan`
--
ALTER TABLE `survey_kepuasan`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `template_kartu`
--
ALTER TABLE `template_kartu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tingkat_kelas`
--
ALTER TABLE `tingkat_kelas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_tingkat` (`nama_tingkat`);

--
-- Indexes for table `tmp_pendaftaran_siswa`
--
ALTER TABLE `tmp_pendaftaran_siswa`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nisn` (`nisn`),
  ADD UNIQUE KEY `nipd` (`nipd`),
  ADD UNIQUE KEY `rfid` (`rfid_uid`),
  ADD KEY `kelas_id` (`kelas_id`);

--
-- Indexes for table `topup`
--
ALTER TABLE `topup`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `merchant_order_id` (`merchant_order_id`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `idx_topup_petugas` (`petugas_id`);

--
-- Indexes for table `transaksi_kantin`
--
ALTER TABLE `transaksi_kantin`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_siswa` (`id_siswa`),
  ADD KEY `id_kantin` (`id_kantin`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fk_users_kantin` (`id_kantin`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absen`
--
ALTER TABLE `absen`
  MODIFY `absen_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2554;

--
-- AUTO_INCREMENT for table `absensi_absen`
--
ALTER TABLE `absensi_absen`
  MODIFY `absen_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19720;

--
-- AUTO_INCREMENT for table `absensi_user`
--
ALTER TABLE `absensi_user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2197;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `anjungan`
--
ALTER TABLE `anjungan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `anjungan_berita`
--
ALTER TABLE `anjungan_berita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `anjungan_instagram_video`
--
ALTER TABLE `anjungan_instagram_video`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `anjungan_menu`
--
ALTER TABLE `anjungan_menu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `anjungan_topright`
--
ALTER TABLE `anjungan_topright`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `berkas_pelanggaran`
--
ALTER TABLE `berkas_pelanggaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `berkas_tambahan`
--
ALTER TABLE `berkas_tambahan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `cetak_ttd_daftar_ulang`
--
ALTER TABLE `cetak_ttd_daftar_ulang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=733;

--
-- AUTO_INCREMENT for table `ekskul_absensi`
--
ALTER TABLE `ekskul_absensi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ekskul_materi`
--
ALTER TABLE `ekskul_materi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ekstrakurikuler`
--
ALTER TABLE `ekstrakurikuler`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `ekstrakurikuler_siswa`
--
ALTER TABLE `ekstrakurikuler_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `expo_visitors`
--
ALTER TABLE `expo_visitors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `fcm_tokens`
--
ALTER TABLE `fcm_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `formulir`
--
ALTER TABLE `formulir`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `form_fields`
--
ALTER TABLE `form_fields`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- AUTO_INCREMENT for table `game_brands`
--
ALTER TABLE `game_brands`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `game_callback_logs`
--
ALTER TABLE `game_callback_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `game_margin_brand`
--
ALTER TABLE `game_margin_brand`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT for table `game_products`
--
ALTER TABLE `game_products`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=273;

--
-- AUTO_INCREMENT for table `game_transactions`
--
ALTER TABLE `game_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=54;

--
-- AUTO_INCREMENT for table `informasi`
--
ALTER TABLE `informasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `jurusan`
--
ALTER TABLE `jurusan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `kantin`
--
ALTER TABLE `kantin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `kelas`
--
ALTER TABLE `kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `log_aktivitas`
--
ALTER TABLE `log_aktivitas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=718;

--
-- AUTO_INCREMENT for table `log_notifikasi_wa`
--
ALTER TABLE `log_notifikasi_wa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_scan_rfid`
--
ALTER TABLE `log_scan_rfid`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `log_transfer`
--
ALTER TABLE `log_transfer`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `nilai_ekskul`
--
ALTER TABLE `nilai_ekskul`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `penarikan`
--
ALTER TABLE `penarikan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pendaftaran_siswa`
--
ALTER TABLE `pendaftaran_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1749;

--
-- AUTO_INCREMENT for table `pengaturan`
--
ALTER TABLE `pengaturan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `siswa_kelas`
--
ALTER TABLE `siswa_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8307;

--
-- AUTO_INCREMENT for table `sp_inventaris`
--
ALTER TABLE `sp_inventaris`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sp_inventaris_ruangan`
--
ALTER TABLE `sp_inventaris_ruangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `sp_kategori`
--
ALTER TABLE `sp_kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `sp_laporan_sarpras`
--
ALTER TABLE `sp_laporan_sarpras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=79;

--
-- AUTO_INCREMENT for table `sp_masalah`
--
ALTER TABLE `sp_masalah`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `sp_ruangan`
--
ALTER TABLE `sp_ruangan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT for table `sp_sub_kategori`
--
ALTER TABLE `sp_sub_kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `sp_users`
--
ALTER TABLE `sp_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `survey_kepuasan`
--
ALTER TABLE `survey_kepuasan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=59;

--
-- AUTO_INCREMENT for table `template_kartu`
--
ALTER TABLE `template_kartu`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tingkat_kelas`
--
ALTER TABLE `tingkat_kelas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tmp_pendaftaran_siswa`
--
ALTER TABLE `tmp_pendaftaran_siswa`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `topup`
--
ALTER TABLE `topup`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=76;

--
-- AUTO_INCREMENT for table `transaksi_kantin`
--
ALTER TABLE `transaksi_kantin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=611;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `berkas_pelanggaran`
--
ALTER TABLE `berkas_pelanggaran`
  ADD CONSTRAINT `berkas_pelanggaran_ibfk_1` FOREIGN KEY (`id_psiswa`) REFERENCES `pendaftaran_siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `berkas_tambahan`
--
ALTER TABLE `berkas_tambahan`
  ADD CONSTRAINT `berkas_tambahan_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `pendaftaran_siswa` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `ekskul_materi`
--
ALTER TABLE `ekskul_materi`
  ADD CONSTRAINT `ekskul_materi_ibfk_1` FOREIGN KEY (`ekskul_id`) REFERENCES `ekstrakurikuler` (`id`);

--
-- Constraints for table `ekstrakurikuler_siswa`
--
ALTER TABLE `ekstrakurikuler_siswa`
  ADD CONSTRAINT `ekstrakurikuler_siswa_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `pendaftaran_siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ekstrakurikuler_siswa_ibfk_2` FOREIGN KEY (`ekstrakurikuler_id`) REFERENCES `ekstrakurikuler` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `kelas`
--
ALTER TABLE `kelas`
  ADD CONSTRAINT `kelas_ibfk_1` FOREIGN KEY (`jurusan_id`) REFERENCES `jurusan` (`id`);

--
-- Constraints for table `nilai_ekskul`
--
ALTER TABLE `nilai_ekskul`
  ADD CONSTRAINT `nilai_ekskul_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `pendaftaran_siswa` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `nilai_ekskul_ibfk_2` FOREIGN KEY (`ekskul_id`) REFERENCES `ekstrakurikuler` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penarikan`
--
ALTER TABLE `penarikan`
  ADD CONSTRAINT `penarikan_ibfk_1` FOREIGN KEY (`id_kantin`) REFERENCES `kantin` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sp_inventaris`
--
ALTER TABLE `sp_inventaris`
  ADD CONSTRAINT `sp_inventaris_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `sp_kategori` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_inventaris_ibfk_2` FOREIGN KEY (`sub_kategori_id`) REFERENCES `sp_sub_kategori` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `sp_inventaris_ibfk_3` FOREIGN KEY (`ruangan_id`) REFERENCES `sp_ruangan` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `sp_masalah`
--
ALTER TABLE `sp_masalah`
  ADD CONSTRAINT `sp_masalah_ibfk_1` FOREIGN KEY (`id_inventaris`) REFERENCES `sp_inventaris` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sp_sub_kategori`
--
ALTER TABLE `sp_sub_kategori`
  ADD CONSTRAINT `sp_sub_kategori_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `sp_kategori` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `topup`
--
ALTER TABLE `topup`
  ADD CONSTRAINT `fk_topup_petugas` FOREIGN KEY (`petugas_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `topup_ibfk_1` FOREIGN KEY (`id_siswa`) REFERENCES `pendaftaran_siswa` (`id`);

-- Data awal yang aman dan diperlukan aplikasi (tanpa data produksi/siswa).
INSERT INTO `formulir` (`id`, `nama`, `nilai`, `kirim_pesan`) VALUES
(1, 'form_aktif', '0', 0);

INSERT INTO `tingkat_kelas` (`id`, `nama_tingkat`, `urutan_tingkat`) VALUES
(1, '10', 1),
(2, '11', 2),
(3, '12', 3);

INSERT INTO `pengaturan` (`id`, `nama_sekolah`, `logo`, `kop_surat`) VALUES
(1, '', NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
