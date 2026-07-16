-- SDS Perpustakaan Terintegrasi v2.0
-- Satu database SDS, tanpa kodeapp dan tanpa SSO Perpustakaan.
DROP TRIGGER IF EXISTS `trg_sync_rfid_after_update`;

CREATE TABLE IF NOT EXISTS app_admin_access (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                admin_id INT NOT NULL,
                application VARCHAR(30) NOT NULL,
                app_role VARCHAR(30) NOT NULL DEFAULT 'operator',
                active ENUM('Y','N') NOT NULL DEFAULT 'Y',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_app_admin_access (admin_id,application),
                KEY idx_app_admin_application (application,active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kartu_rfid (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                uid VARCHAR(64) NOT NULL,
                pemilik_tipe ENUM('siswa','pegawai') NOT NULL,
                pemilik_id INT NOT NULL,
                tanggal_terbit DATE DEFAULT NULL,
                tanggal_berakhir DATE DEFAULT NULL,
                keterangan VARCHAR(255) DEFAULT NULL,
                created_by INT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_kartu_uid (uid),
                UNIQUE KEY uq_kartu_pemilik (pemilik_tipe,pemilik_id),
                KEY idx_kartu_pemilik (pemilik_tipe,pemilik_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kartu_rfid_riwayat (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                uid VARCHAR(64) NOT NULL,
                pemilik_tipe ENUM('siswa','pegawai') NOT NULL,
                pemilik_id INT NOT NULL,
                status_akhir ENUM('diganti','dilepas','hilang','rusak','migrasi') NOT NULL DEFAULT 'dilepas',
                tanggal_mulai DATETIME DEFAULT NULL,
                tanggal_selesai DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                keterangan VARCHAR(255) DEFAULT NULL,
                diproses_oleh INT DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_kartu_riwayat_uid (uid),
                KEY idx_kartu_riwayat_pemilik (pemilik_tipe,pemilik_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_tipe_member (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_kode_tipe INT DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                jumlah_peminjaman INT NOT NULL DEFAULT 2,
                periode_peminjaman INT NOT NULL DEFAULT 7,
                denda_per_hari DECIMAL(14,2) NOT NULL DEFAULT 0,
                status_aktif TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_tipe_legacy (legacy_kode_tipe),
                UNIQUE KEY uq_perpus_tipe_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_anggota (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                pemilik_tipe ENUM('siswa','pegawai','legacy') NOT NULL,
                pemilik_id INT DEFAULT NULL,
                nomor_anggota VARCHAR(64) NOT NULL,
                tipe_member_id INT DEFAULT NULL,
                status_keanggotaan ENUM('aktif','nonaktif','perlu_verifikasi') NOT NULL DEFAULT 'aktif',
                tanggal_daftar DATE DEFAULT NULL,
                tanggal_berakhir DATE DEFAULT NULL,
                legacy_id_anggota VARCHAR(64) DEFAULT NULL,
                legacy_nis VARCHAR(64) DEFAULT NULL,
                legacy_nama VARCHAR(150) DEFAULT NULL,
                legacy_kelas VARCHAR(60) DEFAULT NULL,
                legacy_jurusan VARCHAR(100) DEFAULT NULL,
                legacy_tanggal_lahir VARCHAR(30) DEFAULT NULL,
                catatan VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_nomor_anggota (nomor_anggota),
                UNIQUE KEY uq_perpus_pemilik (pemilik_tipe,pemilik_id),
                KEY idx_perpus_anggota_status (status_keanggotaan),
                KEY idx_perpus_anggota_legacy (legacy_id_anggota)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_kategori_buku (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                kode_kategori INT DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                status_aktif TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_kategori_legacy (legacy_id),
                KEY idx_perpus_kategori_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_tipe_koleksi (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_tipe_koleksi_legacy (legacy_id),
                UNIQUE KEY uq_perpus_tipe_koleksi_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_pengarang (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(150) NOT NULL,
                tipe VARCHAR(10) DEFAULT 'p',
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_pengarang_legacy (legacy_id),
                KEY idx_perpus_pengarang_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_penerbit (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(150) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_penerbit_legacy (legacy_id),
                KEY idx_perpus_penerbit_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_bahasa (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_kode VARCHAR(30) DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_bahasa_legacy (legacy_kode),
                KEY idx_perpus_bahasa_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_gmd (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                kode VARCHAR(30) DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_gmd_legacy (legacy_id),
                KEY idx_perpus_gmd_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_tempat (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_tempat_legacy (legacy_id),
                KEY idx_perpus_tempat_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_subyek (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(150) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_subyek_legacy (legacy_id),
                KEY idx_perpus_subyek_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_buku (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                legacy_id_buku INT DEFAULT NULL,
                judul VARCHAR(255) NOT NULL,
                isbn VARCHAR(50) DEFAULT NULL,
                barcode_induk VARCHAR(64) DEFAULT NULL,
                kategori_id INT DEFAULT NULL,
                tipe_koleksi_id INT DEFAULT NULL,
                gmd_id INT DEFAULT NULL,
                pengarang_id INT DEFAULT NULL,
                penerbit_id INT DEFAULT NULL,
                penerbit_teks VARCHAR(150) DEFAULT NULL,
                tahun_terbit VARCHAR(10) DEFAULT NULL,
                edisi VARCHAR(100) DEFAULT NULL,
                klasifikasi VARCHAR(30) DEFAULT NULL,
                nomor_panggil VARCHAR(80) DEFAULT NULL,
                bahasa VARCHAR(50) DEFAULT NULL,
                tempat_terbit VARCHAR(100) DEFAULT NULL,
                deskripsi_fisik TEXT DEFAULT NULL,
                sampul VARCHAR(255) DEFAULT NULL,
                status_opac TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_buku_legacy (legacy_id_buku),
                KEY idx_perpus_buku_judul (judul),
                KEY idx_perpus_buku_isbn (isbn),
                KEY idx_perpus_buku_barcode_induk (barcode_induk)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_buku_pengarang (
                buku_id BIGINT UNSIGNED NOT NULL,
                pengarang_id INT NOT NULL,
                level_pengarang VARCHAR(30) DEFAULT NULL,
                PRIMARY KEY (buku_id,pengarang_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_buku_subyek (
                buku_id BIGINT UNSIGNED NOT NULL,
                subyek_id INT NOT NULL,
                PRIMARY KEY (buku_id,subyek_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_buku_eksemplar (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                legacy_id_detail INT DEFAULT NULL,
                buku_id BIGINT UNSIGNED NOT NULL,
                barcode VARCHAR(64) NOT NULL,
                tipe_koleksi_id INT DEFAULT NULL,
                status ENUM('tersedia','dipinjam','rusak','hilang','nonaktif') NOT NULL DEFAULT 'tersedia',
                tanggal_masuk DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                catatan VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_eksemplar_legacy (legacy_id_detail),
                UNIQUE KEY uq_perpus_eksemplar_barcode (barcode),
                KEY idx_perpus_eksemplar_buku (buku_id),
                KEY idx_perpus_eksemplar_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_peminjaman (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                legacy_id_pinjam VARCHAR(40) DEFAULT NULL,
                anggota_id BIGINT UNSIGNED NOT NULL,
                admin_id INT DEFAULT NULL,
                tanggal_pinjam DATE NOT NULL,
                jumlah_item INT NOT NULL DEFAULT 0,
                status ENUM('aktif','selesai','dibatalkan') NOT NULL DEFAULT 'aktif',
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_pinjam_legacy (legacy_id_pinjam),
                KEY idx_perpus_pinjam_anggota (anggota_id),
                KEY idx_perpus_pinjam_status (status),
                KEY idx_perpus_pinjam_tanggal (tanggal_pinjam)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_peminjaman_detail (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                legacy_id_detail INT DEFAULT NULL,
                peminjaman_id BIGINT UNSIGNED NOT NULL,
                buku_id BIGINT UNSIGNED DEFAULT NULL,
                eksemplar_id BIGINT UNSIGNED DEFAULT NULL,
                kode_resi VARCHAR(64) DEFAULT NULL,
                tanggal_jatuh_tempo DATE DEFAULT NULL,
                tanggal_kembali DATETIME DEFAULT NULL,
                denda_awal DECIMAL(14,2) NOT NULL DEFAULT 0,
                denda DECIMAL(14,2) NOT NULL DEFAULT 0,
                status ENUM('dipinjam','kembali','hilang','rusak') NOT NULL DEFAULT 'dipinjam',
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_detail_legacy (legacy_id_detail),
                KEY idx_perpus_detail_pinjam (peminjaman_id),
                KEY idx_perpus_detail_buku (buku_id),
                KEY idx_perpus_detail_eksemplar (eksemplar_id),
                KEY idx_perpus_detail_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_kunjungan (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                anggota_id BIGINT UNSIGNED NOT NULL,
                waktu_kunjungan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                sumber ENUM('rfid','manual','migrasi') NOT NULL DEFAULT 'rfid',
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_kunjungan_legacy (legacy_id),
                KEY idx_perpus_kunjungan_anggota (anggota_id),
                KEY idx_perpus_kunjungan_waktu (waktu_kunjungan)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_pengaturan (
                kode VARCHAR(80) NOT NULL,
                nilai TEXT DEFAULT NULL,
                keterangan VARCHAR(255) DEFAULT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (kode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS perpus_migrasi_log (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                sumber_database VARCHAR(100) DEFAULT NULL,
                status ENUM('proses','selesai','gagal') NOT NULL DEFAULT 'proses',
                ringkasan LONGTEXT DEFAULT NULL,
                pesan_error TEXT DEFAULT NULL,
                admin_id INT DEFAULT NULL,
                started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                finished_at DATETIME DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_perpus_migrasi_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO perpus_tipe_member (legacy_kode_tipe,nama,jumlah_peminjaman,periode_peminjaman,denda_per_hari,status_aktif) VALUES
(1,'GURU',3,14,0,1),(2,'KARYAWAN',3,14,0,1),(3,'SISWA',2,7,1000,1),(4,'KEPALA SEKOLAH',3,14,0,1)
ON DUPLICATE KEY UPDATE nama=VALUES(nama);

INSERT INTO perpus_pengaturan (kode,nilai,keterangan) VALUES
('nomor_anggota_prefix_siswa','S','Prefix nomor anggota siswa'),
('nomor_anggota_prefix_pegawai','P','Prefix nomor anggota pegawai'),
('kunjungan_ganda_hari_ini','0','0 menolak scan kunjungan ganda pada hari yang sama'),
('otomatis_aktifkan_anggota','1','Buat keanggotaan saat RFID pertama digunakan')
ON DUPLICATE KEY UPDATE keterangan=VALUES(keterangan);
