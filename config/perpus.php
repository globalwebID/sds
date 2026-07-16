<?php
/**
 * Modul Perpustakaan terintegrasi SDS.
 *
 * - Satu koneksi database SDS.
 * - Seluruh tabel domain perpustakaan memakai prefix perpus_.
 * - Data identitas siswa/pegawai tetap dibaca dari tabel master SDS.
 * - RFID aktif disimpan terpusat pada kartu_rfid dan disinkronkan ke kolom lama
 *   untuk menjaga kompatibilitas modul Absensi yang sudah berjalan.
 */

if (!function_exists('sds_perpus_table_exists')) {
    function sds_perpus_table_exists(mysqli $conn, string $table): bool
    {
        $stmt = $conn->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }
}


if (!function_exists('sds_perpus_ensure_access_schema')) {
    /**
     * Tabel akses aplikasi dipakai oleh menu Akun & Akses Aplikasi, sehingga
     * harus dapat dibuat tanpa menunggu halaman Perpustakaan dibuka dahulu.
     */
    function sds_perpus_ensure_access_schema(mysqli $conn): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS app_admin_access (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        if (!$conn->query($sql)) {
            throw new RuntimeException('Tabel akses aplikasi tidak dapat disiapkan: ' . $conn->error);
        }
    }
}

if (!function_exists('sds_perpus_index_exists')) {
    function sds_perpus_index_exists(mysqli $conn, string $table, string $index): bool
    {
        $stmt = $conn->prepare('SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=? LIMIT 1');
        $stmt->bind_param('ss', $table, $index);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }
}

if (!function_exists('sds_perpus_column_exists')) {
    function sds_perpus_column_exists(mysqli $conn, string $table, string $column): bool
    {
        $stmt = $conn->prepare('SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('ss', $table, $column);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }
}

if (!function_exists('sds_perpus_trigger_exists')) {
    function sds_perpus_trigger_exists(mysqli $conn, string $trigger): bool
    {
        $stmt = $conn->prepare('SELECT 1 FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA=DATABASE() AND TRIGGER_NAME=? LIMIT 1');
        if (!$stmt) return false;
        $stmt->bind_param('s', $trigger);
        $stmt->execute();
        $exists = (bool)$stmt->get_result()->fetch_row();
        $stmt->close();
        return $exists;
    }
}

if (!function_exists('sds_perpus_ensure_rfid_triggers')) {
    /**
     * Menjaga tabel kartu_rfid tetap sinkron saat modul lama masih menulis
     * langsung ke kolom pendaftaran_siswa.rfid_uid atau pegawai.rfid.
     *
     * CREATE TRIGGER dijalankan best-effort karena sebagian hosting tidak
     * memberikan hak TRIGGER kepada user database. Jalur utama SDS tetap
     * memakai sds_rfid_assign()/sds_rfid_remove() sehingga tetap aman.
     */
    function sds_perpus_ensure_rfid_triggers(mysqli $conn): void
    {
        $definitions = [
            'trg_sds_rfid_student_insert' => "CREATE TRIGGER trg_sds_rfid_student_insert AFTER INSERT ON pendaftaran_siswa
                FOR EACH ROW BEGIN
                    IF COALESCE(@sds_skip_rfid_trigger,0)=0 AND NEW.rfid_uid IS NOT NULL AND TRIM(NEW.rfid_uid)<>'' THEN
                        INSERT INTO kartu_rfid (uid,pemilik_tipe,pemilik_id,tanggal_terbit,keterangan)
                        VALUES (TRIM(NEW.rfid_uid),'siswa',NEW.id,CURDATE(),'Sinkron otomatis dari master SDS');
                    END IF;
                END",
            'trg_sds_rfid_student_update' => "CREATE TRIGGER trg_sds_rfid_student_update AFTER UPDATE ON pendaftaran_siswa
                FOR EACH ROW BEGIN
                    IF COALESCE(@sds_skip_rfid_trigger,0)=0 AND NOT (OLD.rfid_uid <=> NEW.rfid_uid) THEN
                        IF OLD.rfid_uid IS NOT NULL AND TRIM(OLD.rfid_uid)<>'' THEN
                            INSERT INTO kartu_rfid_riwayat (uid,pemilik_tipe,pemilik_id,status_akhir,tanggal_mulai,keterangan)
                            VALUES (TRIM(OLD.rfid_uid),'siswa',OLD.id,IF(NEW.rfid_uid IS NULL OR TRIM(NEW.rfid_uid)='','dilepas','diganti'),NOW(),'Sinkron otomatis dari master SDS');
                            DELETE FROM kartu_rfid WHERE pemilik_tipe='siswa' AND pemilik_id=OLD.id;
                        END IF;
                        IF NEW.rfid_uid IS NOT NULL AND TRIM(NEW.rfid_uid)<>'' THEN
                            INSERT INTO kartu_rfid (uid,pemilik_tipe,pemilik_id,tanggal_terbit,keterangan)
                            VALUES (TRIM(NEW.rfid_uid),'siswa',NEW.id,CURDATE(),'Sinkron otomatis dari master SDS');
                        END IF;
                    END IF;
                END",
            'trg_sds_rfid_student_delete' => "CREATE TRIGGER trg_sds_rfid_student_delete BEFORE DELETE ON pendaftaran_siswa
                FOR EACH ROW BEGIN
                    IF COALESCE(@sds_skip_rfid_trigger,0)=0 AND OLD.rfid_uid IS NOT NULL AND TRIM(OLD.rfid_uid)<>'' THEN
                        INSERT INTO kartu_rfid_riwayat (uid,pemilik_tipe,pemilik_id,status_akhir,tanggal_mulai,keterangan)
                        VALUES (TRIM(OLD.rfid_uid),'siswa',OLD.id,'dilepas',NOW(),'Pemilik dihapus dari master SDS');
                        DELETE FROM kartu_rfid WHERE pemilik_tipe='siswa' AND pemilik_id=OLD.id;
                    END IF;
                END",
            'trg_sds_rfid_employee_insert' => "CREATE TRIGGER trg_sds_rfid_employee_insert AFTER INSERT ON pegawai
                FOR EACH ROW BEGIN
                    IF COALESCE(@sds_skip_rfid_trigger,0)=0 AND NEW.rfid IS NOT NULL AND TRIM(NEW.rfid)<>'' THEN
                        INSERT INTO kartu_rfid (uid,pemilik_tipe,pemilik_id,tanggal_terbit,keterangan)
                        VALUES (TRIM(NEW.rfid),'pegawai',NEW.pegawai_id,CURDATE(),'Sinkron otomatis dari master SDS');
                    END IF;
                END",
            'trg_sds_rfid_employee_update' => "CREATE TRIGGER trg_sds_rfid_employee_update AFTER UPDATE ON pegawai
                FOR EACH ROW BEGIN
                    IF COALESCE(@sds_skip_rfid_trigger,0)=0 AND NOT (OLD.rfid <=> NEW.rfid) THEN
                        IF OLD.rfid IS NOT NULL AND TRIM(OLD.rfid)<>'' THEN
                            INSERT INTO kartu_rfid_riwayat (uid,pemilik_tipe,pemilik_id,status_akhir,tanggal_mulai,keterangan)
                            VALUES (TRIM(OLD.rfid),'pegawai',OLD.pegawai_id,IF(NEW.rfid IS NULL OR TRIM(NEW.rfid)='','dilepas','diganti'),NOW(),'Sinkron otomatis dari master SDS');
                            DELETE FROM kartu_rfid WHERE pemilik_tipe='pegawai' AND pemilik_id=OLD.pegawai_id;
                        END IF;
                        IF NEW.rfid IS NOT NULL AND TRIM(NEW.rfid)<>'' THEN
                            INSERT INTO kartu_rfid (uid,pemilik_tipe,pemilik_id,tanggal_terbit,keterangan)
                            VALUES (TRIM(NEW.rfid),'pegawai',NEW.pegawai_id,CURDATE(),'Sinkron otomatis dari master SDS');
                        END IF;
                    END IF;
                END",
            'trg_sds_rfid_employee_delete' => "CREATE TRIGGER trg_sds_rfid_employee_delete BEFORE DELETE ON pegawai
                FOR EACH ROW BEGIN
                    IF COALESCE(@sds_skip_rfid_trigger,0)=0 AND OLD.rfid IS NOT NULL AND TRIM(OLD.rfid)<>'' THEN
                        INSERT INTO kartu_rfid_riwayat (uid,pemilik_tipe,pemilik_id,status_akhir,tanggal_mulai,keterangan)
                        VALUES (TRIM(OLD.rfid),'pegawai',OLD.pegawai_id,'dilepas',NOW(),'Pemilik dihapus dari master SDS');
                        DELETE FROM kartu_rfid WHERE pemilik_tipe='pegawai' AND pemilik_id=OLD.pegawai_id;
                    END IF;
                END",
        ];

        foreach ($definitions as $name => $sql) {
            if (!sds_perpus_trigger_exists($conn, $name)) {
                @$conn->query($sql);
            }
        }
    }
}

if (!function_exists('sds_perpus_ensure_schema')) {
    function sds_perpus_ensure_schema(mysqli $conn): void
    {
        static $ready = false;
        if ($ready) return;

        // Menu Akun & Akses Aplikasi dapat dibuka sebelum modul Perpustakaan.
        sds_perpus_ensure_access_schema($conn);

        // Hindari menjalankan puluhan DDL dan sinkronisasi seluruh kartu pada
        // setiap request. Versi dianggap siap hanya jika semua tabel inti masih ada.
        $schemaVersion = '2.6.0';
        $requiredTables = [
            'app_admin_access', 'kartu_rfid', 'kartu_rfid_riwayat',
            'perpus_tipe_member', 'perpus_anggota', 'perpus_kategori_buku',
            'perpus_tipe_koleksi', 'perpus_pengarang', 'perpus_penerbit',
            'perpus_bahasa', 'perpus_gmd', 'perpus_tempat', 'perpus_subyek',
            'perpus_buku', 'perpus_buku_pengarang', 'perpus_buku_subyek',
            'perpus_buku_eksemplar', 'perpus_peminjaman',
            'perpus_peminjaman_detail', 'perpus_perpanjangan', 'perpus_denda_pembayaran', 'perpus_kunjungan',
            'perpus_pengaturan', 'perpus_migrasi_log', 'perpus_import_batch',
            'perpus_reservasi', 'perpus_notifikasi', 'perpus_pengingat_log',
            'perpus_saran', 'perpus_audit_log', 'perpus_audit_check'
        ];
        $quotedTables = implode(',', array_map(static fn(string $table): string => "'" . $table . "'", $requiredTables));
        $tableResult = $conn->query("SELECT COUNT(DISTINCT TABLE_NAME) total FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ({$quotedTables})");
        $allRequiredTablesExist = $tableResult && (int)($tableResult->fetch_assoc()['total'] ?? 0) === count($requiredTables);

        if ($allRequiredTablesExist) {
            $requiredColumns = [
                ['perpus_tipe_member','maksimal_perpanjangan'],
                ['perpus_tipe_member','hari_perpanjangan'],
                ['perpus_peminjaman_detail','jumlah_perpanjangan'],
                ['perpus_peminjaman_detail','kondisi_kembali'],
                ['perpus_peminjaman_detail','catatan_kembali'],
                ['perpus_peminjaman_detail','denda_status'],
                ['perpus_buku_eksemplar','nomor_inventaris'],
                ['perpus_buku_eksemplar','lokasi_rak'],
                ['perpus_buku_eksemplar','kondisi_fisik'],
                ['perpus_buku_eksemplar','sumber_pengadaan'],
                ['perpus_buku_eksemplar','harga'],
                ['perpus_buku_eksemplar','tanggal_pengadaan'],
            ];
            $columnConditions = array_map(
                static fn(array $item): string => "(TABLE_NAME='" . $item[0] . "' AND COLUMN_NAME='" . $item[1] . "')",
                $requiredColumns
            );
            $columnResult = $conn->query('SELECT COUNT(*) total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND (' . implode(' OR ', $columnConditions) . ')');
            $allRequiredColumnsExist = $columnResult && (int)($columnResult->fetch_assoc()['total'] ?? 0) === count($requiredColumns);
            $stmt = $conn->prepare("SELECT nilai FROM perpus_pengaturan WHERE kode='schema_version' LIMIT 1");
            if ($stmt) {
                $stmt->execute();
                $installedVersion = (string)($stmt->get_result()->fetch_assoc()['nilai'] ?? '');
                $stmt->close();
                if ($installedVersion === $schemaVersion && $allRequiredColumnsExist) {
                    $ready = true;
                    return;
                }
            }
        }

        // Trigger lama menulis ke database Perpustakaan/Absensi terpisah dan tidak
        // boleh dipakai setelah modul dilebur ke SDS. Penghapusan bersifat best effort.
        @$conn->query('DROP TRIGGER IF EXISTS `trg_sync_rfid_after_update`');

        $queries = [
            "CREATE TABLE IF NOT EXISTS app_admin_access (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            "CREATE TABLE IF NOT EXISTS kartu_rfid (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS kartu_rfid_riwayat (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_tipe_member (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_anggota (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_kategori_buku (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                kode_kategori INT DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                status_aktif TINYINT(1) NOT NULL DEFAULT 1,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_kategori_legacy (legacy_id),
                KEY idx_perpus_kategori_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_tipe_koleksi (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_tipe_koleksi_legacy (legacy_id),
                UNIQUE KEY uq_perpus_tipe_koleksi_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_pengarang (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(150) NOT NULL,
                tipe VARCHAR(10) DEFAULT 'p',
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_pengarang_legacy (legacy_id),
                KEY idx_perpus_pengarang_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_penerbit (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(150) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_penerbit_legacy (legacy_id),
                KEY idx_perpus_penerbit_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_bahasa (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_kode VARCHAR(30) DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_bahasa_legacy (legacy_kode),
                KEY idx_perpus_bahasa_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_gmd (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                kode VARCHAR(30) DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_gmd_legacy (legacy_id),
                KEY idx_perpus_gmd_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_tempat (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(100) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_tempat_legacy (legacy_id),
                KEY idx_perpus_tempat_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_subyek (
                id INT NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                nama VARCHAR(150) NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_subyek_legacy (legacy_id),
                KEY idx_perpus_subyek_nama (nama)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_buku (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_buku_pengarang (
                buku_id BIGINT UNSIGNED NOT NULL,
                pengarang_id INT NOT NULL,
                level_pengarang VARCHAR(30) DEFAULT NULL,
                PRIMARY KEY (buku_id,pengarang_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_buku_subyek (
                buku_id BIGINT UNSIGNED NOT NULL,
                subyek_id INT NOT NULL,
                PRIMARY KEY (buku_id,subyek_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_buku_eksemplar (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_peminjaman (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_peminjaman_detail (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_perpanjangan (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                detail_id BIGINT UNSIGNED NOT NULL,
                tanggal_perpanjang DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                jatuh_tempo_lama DATE NOT NULL,
                jatuh_tempo_baru DATE NOT NULL,
                admin_id INT DEFAULT NULL,
                catatan VARCHAR(255) DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_perpus_perpanjangan_detail (detail_id),
                KEY idx_perpus_perpanjangan_tanggal (tanggal_perpanjang)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_denda_pembayaran (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                detail_id BIGINT UNSIGNED NOT NULL,
                anggota_id BIGINT UNSIGNED NOT NULL,
                jenis ENUM('pembayaran','keringanan','pembebasan','koreksi') NOT NULL DEFAULT 'pembayaran',
                nominal DECIMAL(14,2) NOT NULL DEFAULT 0,
                catatan VARCHAR(255) DEFAULT NULL,
                admin_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_perpus_denda_detail (detail_id),
                KEY idx_perpus_denda_anggota (anggota_id),
                KEY idx_perpus_denda_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_kunjungan (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                legacy_id INT DEFAULT NULL,
                anggota_id BIGINT UNSIGNED NOT NULL,
                waktu_kunjungan DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                sumber ENUM('rfid','manual','migrasi') NOT NULL DEFAULT 'rfid',
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_kunjungan_legacy (legacy_id),
                KEY idx_perpus_kunjungan_anggota (anggota_id),
                KEY idx_perpus_kunjungan_waktu (waktu_kunjungan)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_pengaturan (
                kode VARCHAR(80) NOT NULL,
                nilai TEXT DEFAULT NULL,
                keterangan VARCHAR(255) DEFAULT NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (kode)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_migrasi_log (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_reservasi (
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
                PRIMARY KEY (id),
                KEY idx_perpus_reservasi_anggota (anggota_id,status),
                KEY idx_perpus_reservasi_buku (buku_id,status),
                KEY idx_perpus_reservasi_batas (status,batas_ambil)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_notifikasi (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                anggota_id BIGINT UNSIGNED NOT NULL,
                tipe VARCHAR(40) NOT NULL DEFAULT 'informasi',
                judul VARCHAR(180) NOT NULL,
                pesan TEXT NOT NULL,
                referensi_tipe VARCHAR(40) DEFAULT NULL,
                referensi_id BIGINT UNSIGNED DEFAULT NULL,
                status ENUM('baru','dibaca') NOT NULL DEFAULT 'baru',
                dibaca_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_perpus_notifikasi_anggota (anggota_id,status,created_at),
                KEY idx_perpus_notifikasi_referensi (referensi_tipe,referensi_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_pengingat_log (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                detail_id BIGINT UNSIGNED NOT NULL,
                anggota_id BIGINT UNSIGNED NOT NULL,
                jenis ENUM('akan_jatuh_tempo','jatuh_tempo','terlambat') NOT NULL,
                tanggal_proses DATE NOT NULL,
                pesan VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uq_perpus_pengingat_harian (detail_id,jenis,tanggal_proses),
                KEY idx_perpus_pengingat_anggota (anggota_id,tanggal_proses)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_saran (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                anggota_id BIGINT UNSIGNED DEFAULT NULL,
                nama_pengirim VARCHAR(150) DEFAULT NULL,
                kontak VARCHAR(150) DEFAULT NULL,
                kategori ENUM('kritik','saran','keluhan','apresiasi') NOT NULL DEFAULT 'saran',
                judul VARCHAR(180) NOT NULL,
                pesan TEXT NOT NULL,
                status ENUM('baru','diproses','selesai','ditolak') NOT NULL DEFAULT 'baru',
                jawaban TEXT DEFAULT NULL,
                sumber ENUM('opac','admin') NOT NULL DEFAULT 'opac',
                ip_address VARCHAR(45) DEFAULT NULL,
                admin_id INT DEFAULT NULL,
                ditanggapi_at DATETIME DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_perpus_saran_status (status,created_at),
                KEY idx_perpus_saran_anggota (anggota_id),
                KEY idx_perpus_saran_kategori (kategori,created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_audit_log (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                admin_id INT DEFAULT NULL,
                aksi VARCHAR(40) NOT NULL,
                entitas VARCHAR(60) NOT NULL,
                entitas_id VARCHAR(80) DEFAULT NULL,
                ringkasan VARCHAR(255) DEFAULT NULL,
                data_lama LONGTEXT DEFAULT NULL,
                data_baru LONGTEXT DEFAULT NULL,
                ip_address VARCHAR(45) DEFAULT NULL,
                user_agent VARCHAR(255) DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_perpus_audit_waktu (created_at),
                KEY idx_perpus_audit_entitas (entitas,entitas_id),
                KEY idx_perpus_audit_admin (admin_id,created_at),
                KEY idx_perpus_audit_aksi (aksi,created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_audit_check (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                kode VARCHAR(60) NOT NULL,
                tingkat ENUM('info','peringatan','kritis') NOT NULL DEFAULT 'info',
                judul VARCHAR(180) NOT NULL,
                jumlah_temuan INT NOT NULL DEFAULT 0,
                detail_json LONGTEXT DEFAULT NULL,
                checked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                checked_by INT DEFAULT NULL,
                PRIMARY KEY (id),
                KEY idx_perpus_check_waktu (checked_at),
                KEY idx_perpus_check_kode (kode,checked_at),
                KEY idx_perpus_check_tingkat (tingkat,jumlah_temuan)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            "CREATE TABLE IF NOT EXISTS perpus_import_batch (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                jenis ENUM('koleksi','eksemplar') NOT NULL,
                nama_file VARCHAR(255) DEFAULT NULL,
                total_baris INT NOT NULL DEFAULT 0,
                berhasil INT NOT NULL DEFAULT 0,
                diperbarui INT NOT NULL DEFAULT 0,
                dilewati INT NOT NULL DEFAULT 0,
                gagal INT NOT NULL DEFAULT 0,
                ringkasan LONGTEXT DEFAULT NULL,
                admin_id INT DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_perpus_import_jenis (jenis),
                KEY idx_perpus_import_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        ];

        foreach ($queries as $sql) {
            if (!$conn->query($sql)) {
                throw new RuntimeException('Gagal menyiapkan tabel Perpustakaan: ' . $conn->error);
            }
        }

        // Upgrade aman bila schema pernah dibuat oleh versi prarilis patch.
        if (!sds_perpus_column_exists($conn, 'perpus_buku', 'gmd_id')) {
            if (!$conn->query('ALTER TABLE perpus_buku ADD COLUMN gmd_id INT DEFAULT NULL AFTER tipe_koleksi_id')) {
                throw new RuntimeException('Gagal memperbarui struktur perpus_buku: ' . $conn->error);
            }
        }

        // Upgrade v2.3: perpanjangan, kondisi pengembalian, dan pembayaran denda.
        $v23Columns = [
            ['perpus_tipe_member','maksimal_perpanjangan',"ALTER TABLE perpus_tipe_member ADD COLUMN maksimal_perpanjangan INT NOT NULL DEFAULT 1 AFTER denda_per_hari"],
            ['perpus_tipe_member','hari_perpanjangan',"ALTER TABLE perpus_tipe_member ADD COLUMN hari_perpanjangan INT NOT NULL DEFAULT 7 AFTER maksimal_perpanjangan"],
            ['perpus_peminjaman_detail','jumlah_perpanjangan',"ALTER TABLE perpus_peminjaman_detail ADD COLUMN jumlah_perpanjangan INT NOT NULL DEFAULT 0 AFTER tanggal_jatuh_tempo"],
            ['perpus_peminjaman_detail','kondisi_kembali',"ALTER TABLE perpus_peminjaman_detail ADD COLUMN kondisi_kembali ENUM('baik','rusak','hilang') NOT NULL DEFAULT 'baik' AFTER status"],
            ['perpus_peminjaman_detail','catatan_kembali',"ALTER TABLE perpus_peminjaman_detail ADD COLUMN catatan_kembali VARCHAR(255) DEFAULT NULL AFTER kondisi_kembali"],
            ['perpus_peminjaman_detail','denda_status',"ALTER TABLE perpus_peminjaman_detail ADD COLUMN denda_status ENUM('belum_lunas','lunas','dibebaskan') NOT NULL DEFAULT 'belum_lunas' AFTER denda"],
        ];
        foreach ($v23Columns as [$tableName,$columnName,$alterSql]) {
            if (!sds_perpus_column_exists($conn,$tableName,$columnName) && !$conn->query($alterSql)) {
                throw new RuntimeException('Gagal memperbarui struktur ' . $tableName . ': ' . $conn->error);
            }
        }
        $conn->query("UPDATE perpus_peminjaman_detail SET denda_status=CASE WHEN denda<=0 THEN 'lunas' ELSE denda_status END WHERE status<>'dipinjam'");

        // Upgrade v2.4: metadata eksemplar untuk import massal dan OPAC.
        $v24Columns = [
            ['perpus_buku_eksemplar','nomor_inventaris',"ALTER TABLE perpus_buku_eksemplar ADD COLUMN nomor_inventaris VARCHAR(100) DEFAULT NULL AFTER barcode"],
            ['perpus_buku_eksemplar','lokasi_rak',"ALTER TABLE perpus_buku_eksemplar ADD COLUMN lokasi_rak VARCHAR(100) DEFAULT NULL AFTER tipe_koleksi_id"],
            ['perpus_buku_eksemplar','kondisi_fisik',"ALTER TABLE perpus_buku_eksemplar ADD COLUMN kondisi_fisik ENUM('baik','rusak','hilang') NOT NULL DEFAULT 'baik' AFTER lokasi_rak"],
            ['perpus_buku_eksemplar','sumber_pengadaan',"ALTER TABLE perpus_buku_eksemplar ADD COLUMN sumber_pengadaan VARCHAR(100) DEFAULT NULL AFTER kondisi_fisik"],
            ['perpus_buku_eksemplar','harga',"ALTER TABLE perpus_buku_eksemplar ADD COLUMN harga DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER sumber_pengadaan"],
            ['perpus_buku_eksemplar','tanggal_pengadaan',"ALTER TABLE perpus_buku_eksemplar ADD COLUMN tanggal_pengadaan DATE DEFAULT NULL AFTER harga"],
        ];
        foreach ($v24Columns as [$tableName,$columnName,$alterSql]) {
            if (!sds_perpus_column_exists($conn,$tableName,$columnName) && !$conn->query($alterSql)) {
                throw new RuntimeException('Gagal memperbarui struktur ' . $tableName . ': ' . $conn->error);
            }
        }
        if (!sds_perpus_index_exists($conn,'perpus_buku_eksemplar','idx_perpus_eksemplar_inventaris')) {
            $conn->query('ALTER TABLE perpus_buku_eksemplar ADD KEY idx_perpus_eksemplar_inventaris (nomor_inventaris)');
        }
        if (!sds_perpus_index_exists($conn,'perpus_buku_eksemplar','idx_perpus_eksemplar_rak')) {
            $conn->query('ALTER TABLE perpus_buku_eksemplar ADD KEY idx_perpus_eksemplar_rak (lokasi_rak)');
        }


        // Upgrade v2.5: reservasi, notifikasi, pengingat, dan kiosk kunjungan.
        // ENUM lama diperluas secara idempotent agar scan kiosk dapat dibedakan dari scan petugas.
        if (!$conn->query("ALTER TABLE perpus_kunjungan MODIFY COLUMN sumber ENUM('rfid','manual','migrasi','kiosk') NOT NULL DEFAULT 'rfid'")) {
            throw new RuntimeException('Gagal memperluas sumber kunjungan untuk kiosk: ' . $conn->error);
        }

        // Menutup jalur sinkronisasi ke database eksternal lama dan, bila hak
        // database mengizinkan, memasang trigger sinkronisasi RFID lokal.
        sds_perpus_ensure_rfid_triggers($conn);

        // Nilai awal tipe anggota. Data lama akan memperbarui aturan ini saat migrasi.
        $conn->query("INSERT INTO perpus_tipe_member (legacy_kode_tipe,nama,jumlah_peminjaman,periode_peminjaman,denda_per_hari,status_aktif) VALUES
            (1,'GURU',3,14,0,1),(2,'KARYAWAN',3,14,0,1),(3,'SISWA',2,7,1000,1),(4,'KEPALA SEKOLAH',3,14,0,1)
            ON DUPLICATE KEY UPDATE nama=VALUES(nama)");

        $conn->query("INSERT INTO perpus_pengaturan (kode,nilai,keterangan) VALUES
            ('nomor_anggota_prefix_siswa','S','Prefix nomor anggota siswa'),
            ('nomor_anggota_prefix_pegawai','P','Prefix nomor anggota pegawai'),
            ('kunjungan_ganda_hari_ini','0','0 menolak scan kunjungan ganda pada hari yang sama'),
            ('otomatis_aktifkan_anggota','1','Buat keanggotaan saat RFID pertama digunakan'),
            ('opac_aktif','1','Status katalog publik OPAC'),
            ('opac_judul','Katalog Perpustakaan','Judul halaman OPAC publik'),
            ('opac_tampilkan_populer','1','Tampilkan koleksi populer pada OPAC'),
            ('reservasi_aktif','1','Aktifkan reservasi atau inden melalui OPAC'),
            ('reservasi_maks_per_anggota','3','Maksimal reservasi aktif per anggota'),
            ('reservasi_hari_ambil','2','Batas pengambilan setelah reservasi siap'),
            ('pengingat_aktif','1','Aktifkan notifikasi pengingat jatuh tempo'),
            ('pengingat_hari_sebelum','2','Jumlah hari pengingat sebelum jatuh tempo'),
            ('kiosk_kunjungan_aktif','1','Aktifkan kiosk kunjungan mandiri'),
            ('kiosk_judul','Kunjungan Perpustakaan','Judul halaman kiosk kunjungan'),
            ('kiosk_tolak_ganda','1','Tolak pencatatan kunjungan ganda pada hari yang sama'),
            ('reminder_token','','Token endpoint pengingat terjadwal'),
            ('saran_aktif','1','Aktifkan formulir kritik dan saran pada OPAC'),
            ('saran_wajib_identitas','0','Wajibkan identitas anggota pada kritik dan saran'),
            ('retensi_audit_hari','365','Lama penyimpanan log audit dalam hari')
            ON DUPLICATE KEY UPDATE keterangan=VALUES(keterangan)");

        sds_rfid_sync_existing($conn);

        $stmt = $conn->prepare("INSERT INTO perpus_pengaturan (kode,nilai,keterangan) VALUES ('schema_version',?,'Versi schema modul Perpustakaan SDS') ON DUPLICATE KEY UPDATE nilai=VALUES(nilai),keterangan=VALUES(keterangan)");
        if (!$stmt) throw new RuntimeException('Gagal menyiapkan penanda versi schema Perpustakaan: ' . $conn->error);
        $stmt->bind_param('s', $schemaVersion);
        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new RuntimeException('Gagal menyimpan versi schema Perpustakaan: ' . $error);
        }
        $stmt->close();
        $ready = true;
    }
}

if (!function_exists('sds_perpus_admin_access')) {
    function sds_perpus_admin_access(mysqli $conn, int $adminId, string $adminRole): array
    {
        if ($adminRole === 'superadmin') {
            return ['allowed' => true, 'role' => 'admin'];
        }
        if (!sds_perpus_table_exists($conn, 'app_admin_access')) {
            return ['allowed' => false, 'role' => ''];
        }
        $stmt = $conn->prepare("SELECT app_role FROM app_admin_access WHERE admin_id=? AND application='library' AND active='Y' LIMIT 1");
        if (!$stmt) return ['allowed' => false, 'role' => ''];
        $stmt->bind_param('i', $adminId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return ['allowed' => (bool)$row, 'role' => (string)($row['app_role'] ?? '')];
    }
}

if (!function_exists('sds_perpus_require_access')) {
    function sds_perpus_require_access(mysqli $conn, string $minimum = 'operator'): array
    {
        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        $adminRole = (string)($_SESSION['admin_role'] ?? '');
        $access = sds_perpus_admin_access($conn, $adminId, $adminRole);
        $rank = ['operator' => 1, 'admin' => 2, 'superadmin' => 3];
        $current = $adminRole === 'superadmin' ? 3 : ($rank[$access['role']] ?? 0);
        $required = $rank[$minimum] ?? 1;
        if (!$access['allowed'] || $current < $required) {
            http_response_code(403);
            echo '<div class="alert alert-danger">Akun Anda tidak memiliki akses ke modul Perpustakaan.</div>';
            return ['allowed' => false, 'role' => ''];
        }
        return $access;
    }
}

if (!function_exists('sds_rfid_sync_existing')) {
    function sds_rfid_sync_existing(mysqli $conn): void
    {
        if (!sds_perpus_table_exists($conn, 'kartu_rfid')) return;

        if (sds_perpus_table_exists($conn, 'pendaftaran_siswa')) {
            $result = $conn->query("SELECT id,TRIM(rfid_uid) uid FROM pendaftaran_siswa WHERE rfid_uid IS NOT NULL AND TRIM(rfid_uid)<>''");
            if ($result) {
                $stmt = $conn->prepare("INSERT IGNORE INTO kartu_rfid (uid,pemilik_tipe,pemilik_id,tanggal_terbit,keterangan) VALUES (?,'siswa',?,CURDATE(),'Migrasi kolom RFID SDS')");
                while ($row = $result->fetch_assoc()) {
                    $uid = (string)$row['uid'];
                    $id = (int)$row['id'];
                    $stmt->bind_param('si', $uid, $id);
                    $stmt->execute();
                }
                $stmt->close();
            }
        }

        if (sds_perpus_table_exists($conn, 'pegawai')) {
            $result = $conn->query("SELECT pegawai_id,TRIM(rfid) uid FROM pegawai WHERE rfid IS NOT NULL AND TRIM(rfid)<>''");
            if ($result) {
                $stmt = $conn->prepare("INSERT IGNORE INTO kartu_rfid (uid,pemilik_tipe,pemilik_id,tanggal_terbit,keterangan) VALUES (?,'pegawai',?,CURDATE(),'Migrasi kolom RFID SDS')");
                while ($row = $result->fetch_assoc()) {
                    $uid = (string)$row['uid'];
                    $id = (int)$row['pegawai_id'];
                    $stmt->bind_param('si', $uid, $id);
                    $stmt->execute();
                }
                $stmt->close();
            }
        }
    }
}

if (!function_exists('sds_rfid_assign')) {
    function sds_rfid_assign(mysqli $conn, string $ownerType, int $ownerId, string $uid, int $adminId = 0, string $note = ''): void
    {
        if (!in_array($ownerType, ['siswa', 'pegawai'], true) || $ownerId <= 0) {
            throw new InvalidArgumentException('Pemilik kartu tidak valid.');
        }
        $uid = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', $uid) ?? '');
        if ($uid === '') throw new RuntimeException('UID kartu wajib diisi.');
        if (strlen($uid) > 50) throw new RuntimeException('UID kartu maksimal 50 karakter.');

        sds_perpus_ensure_schema($conn);
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('SELECT id,pemilik_tipe,pemilik_id FROM kartu_rfid WHERE uid=? LIMIT 1 FOR UPDATE');
            $stmt->bind_param('s', $uid);
            $stmt->execute();
            $used = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($used && ((string)$used['pemilik_tipe'] !== $ownerType || (int)$used['pemilik_id'] !== $ownerId)) {
                throw new RuntimeException('UID kartu sudah digunakan oleh pemilik lain.');
            }

            $stmt = $conn->prepare('SELECT * FROM kartu_rfid WHERE pemilik_tipe=? AND pemilik_id=? LIMIT 1 FOR UPDATE');
            $stmt->bind_param('si', $ownerType, $ownerId);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($old && (string)$old['uid'] !== $uid) {
                $status = 'diganti';
                $historyNote = $note !== '' ? $note : 'Kartu diganti';
                $stmt = $conn->prepare('INSERT INTO kartu_rfid_riwayat (uid,pemilik_tipe,pemilik_id,status_akhir,tanggal_mulai,keterangan,diproses_oleh) VALUES (?,?,?,?,?,?,?)');
                $started = (string)($old['created_at'] ?? date('Y-m-d H:i:s'));
                $oldUid = (string)$old['uid'];
                $stmt->bind_param('ssisssi', $oldUid, $ownerType, $ownerId, $status, $started, $historyNote, $adminId);
                $stmt->execute();
                $stmt->close();
                $stmt = $conn->prepare('DELETE FROM kartu_rfid WHERE id=?');
                $oldId = (int)$old['id'];
                $stmt->bind_param('i', $oldId);
                $stmt->execute();
                $stmt->close();
            }

            if (!$used) {
                $stmt = $conn->prepare('INSERT INTO kartu_rfid (uid,pemilik_tipe,pemilik_id,tanggal_terbit,keterangan,created_by) VALUES (?,?,?,CURDATE(),?,?)');
                $stmt->bind_param('ssisi', $uid, $ownerType, $ownerId, $note, $adminId);
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare('UPDATE kartu_rfid SET keterangan=?,created_by=?,updated_at=NOW() WHERE uid=?');
                $stmt->bind_param('sis', $note, $adminId, $uid);
                $stmt->execute();
                $stmt->close();
            }

            $conn->query('SET @sds_skip_rfid_trigger = 1');
            if ($ownerType === 'siswa') {
                $stmt = $conn->prepare('UPDATE pendaftaran_siswa SET rfid_uid=? WHERE id=?');
            } else {
                $stmt = $conn->prepare('UPDATE pegawai SET rfid=? WHERE pegawai_id=?');
            }
            $stmt->bind_param('si', $uid, $ownerId);
            $stmt->execute();
            $stmt->close();
            $conn->query('SET @sds_skip_rfid_trigger = 0');

            $conn->commit();
        } catch (Throwable $e) {
            @$conn->query('SET @sds_skip_rfid_trigger = 0');
            $conn->rollback();
            throw $e;
        }
    }
}

if (!function_exists('sds_rfid_remove')) {
    function sds_rfid_remove(mysqli $conn, string $ownerType, int $ownerId, int $adminId = 0, string $status = 'dilepas', string $note = ''): void
    {
        if (!in_array($ownerType, ['siswa', 'pegawai'], true) || $ownerId <= 0) {
            throw new InvalidArgumentException('Pemilik kartu tidak valid.');
        }
        if (!in_array($status, ['dilepas', 'hilang', 'rusak', 'diganti'], true)) $status = 'dilepas';
        sds_perpus_ensure_schema($conn);
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare('SELECT * FROM kartu_rfid WHERE pemilik_tipe=? AND pemilik_id=? LIMIT 1 FOR UPDATE');
            $stmt->bind_param('si', $ownerType, $ownerId);
            $stmt->execute();
            $card = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($card) {
                $stmt = $conn->prepare('INSERT INTO kartu_rfid_riwayat (uid,pemilik_tipe,pemilik_id,status_akhir,tanggal_mulai,keterangan,diproses_oleh) VALUES (?,?,?,?,?,?,?)');
                $uid = (string)$card['uid'];
                $started = (string)($card['created_at'] ?? date('Y-m-d H:i:s'));
                $stmt->bind_param('ssisssi', $uid, $ownerType, $ownerId, $status, $started, $note, $adminId);
                $stmt->execute();
                $stmt->close();
                $stmt = $conn->prepare('DELETE FROM kartu_rfid WHERE id=?');
                $cardId = (int)$card['id'];
                $stmt->bind_param('i', $cardId);
                $stmt->execute();
                $stmt->close();
            }
            $conn->query('SET @sds_skip_rfid_trigger = 1');
            if ($ownerType === 'siswa') {
                $stmt = $conn->prepare('UPDATE pendaftaran_siswa SET rfid_uid=NULL WHERE id=?');
            } else {
                $stmt = $conn->prepare('UPDATE pegawai SET rfid=NULL WHERE pegawai_id=?');
            }
            $stmt->bind_param('i', $ownerId);
            $stmt->execute();
            $stmt->close();
            $conn->query('SET @sds_skip_rfid_trigger = 0');
            $conn->commit();
        } catch (Throwable $e) {
            @$conn->query('SET @sds_skip_rfid_trigger = 0');
            $conn->rollback();
            throw $e;
        }
    }
}

if (!function_exists('sds_perpus_member_number')) {
    function sds_perpus_member_number(mysqli $conn, string $ownerType, int $ownerId): string
    {
        $prefix = $ownerType === 'pegawai' ? 'P' : 'S';
        $code = $ownerType === 'pegawai' ? 'nomor_anggota_prefix_pegawai' : 'nomor_anggota_prefix_siswa';
        $stmt = $conn->prepare('SELECT nilai FROM perpus_pengaturan WHERE kode=? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (trim((string)($row['nilai'] ?? '')) !== '') $prefix = trim((string)$row['nilai']);
        }
        return $prefix . str_pad((string)$ownerId, 7, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('sds_perpus_unique_member_number')) {
    function sds_perpus_unique_member_number(mysqli $conn, string $preferred, int $ignoreId = 0): string
    {
        $base = trim($preferred);
        if ($base === '') $base = 'ANGGOTA';
        $candidate = $base;
        for ($suffix = 1; $suffix <= 9999; $suffix++) {
            $stmt = $conn->prepare('SELECT id FROM perpus_anggota WHERE nomor_anggota=? AND id<>? LIMIT 1');
            $stmt->bind_param('si', $candidate, $ignoreId);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$exists) return $candidate;
            $candidate = $base . '-' . ($suffix + 1);
        }
        throw new RuntimeException('Nomor anggota unik tidak dapat dibentuk.');
    }
}

if (!function_exists('sds_perpus_ensure_member')) {
    function sds_perpus_ensure_member(mysqli $conn, string $ownerType, int $ownerId, bool $activate = true): array
    {
        if (!in_array($ownerType, ['siswa', 'pegawai'], true) || $ownerId <= 0) {
            throw new InvalidArgumentException('Pemilik anggota tidak valid.');
        }
        sds_perpus_ensure_schema($conn);

        $stmt = $conn->prepare('SELECT * FROM perpus_anggota WHERE pemilik_tipe=? AND pemilik_id=? LIMIT 1');
        $stmt->bind_param('si', $ownerType, $ownerId);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $masterActive = true;
        if ($ownerType === 'siswa') {
            $legacyType = 3;
            $stmt = $conn->prepare('SELECT status_aktif FROM pendaftaran_siswa WHERE id=? LIMIT 1');
            $stmt->bind_param('i', $ownerId);
            $stmt->execute();
            $master = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$master) throw new RuntimeException('Peserta didik tidak ditemukan pada master SDS.');
            $masterActive = (int)$master['status_aktif'] === 1;
        } else {
            $stmt = $conn->prepare('SELECT jabatan,active FROM pegawai WHERE pegawai_id=? LIMIT 1');
            $stmt->bind_param('i', $ownerId);
            $stmt->execute();
            $master = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$master) throw new RuntimeException('Pengajar atau pegawai tidak ditemukan pada master SDS.');
            $position = mb_strtolower(trim((string)($master['jabatan'] ?? '')));
            if (str_contains($position, 'kepala sekolah')) $legacyType = 4;
            elseif (str_contains($position, 'guru') || str_contains($position, 'pengajar')) $legacyType = 1;
            else $legacyType = 2;
            $masterActive = (string)$master['active'] === 'Y';
        }

        $stmt = $conn->prepare('SELECT id FROM perpus_tipe_member WHERE legacy_kode_tipe=? LIMIT 1');
        $stmt->bind_param('i', $legacyType);
        $stmt->execute();
        $typeId = (int)($stmt->get_result()->fetch_assoc()['id'] ?? 0);
        $stmt->close();
        if ($typeId <= 0) {
            $fallbackName = $ownerType === 'siswa' ? 'SISWA' : 'KARYAWAN';
            $stmt = $conn->prepare('SELECT id FROM perpus_tipe_member WHERE nama=? LIMIT 1');
            $stmt->bind_param('s', $fallbackName);
            $stmt->execute();
            $typeId = (int)($stmt->get_result()->fetch_assoc()['id'] ?? 0);
            $stmt->close();
        }

        if ($member) {
            $memberId = (int)$member['id'];
            $nextStatus = (string)$member['status_keanggotaan'];
            // Status master SDS selalu menang untuk penonaktifan. Aktivasi kembali
            // tetap dilakukan dari menu Anggota agar penonaktifan manual tidak hilang.
            if (!$masterActive && $nextStatus === 'aktif') $nextStatus = 'nonaktif';
            if ((int)($member['tipe_member_id'] ?? 0) !== $typeId || $nextStatus !== (string)$member['status_keanggotaan']) {
                $stmt = $conn->prepare('UPDATE perpus_anggota SET tipe_member_id=?,status_keanggotaan=? WHERE id=?');
                $stmt->bind_param('isi', $typeId, $nextStatus, $memberId);
                $stmt->execute();
                $stmt->close();
                $stmt = $conn->prepare('SELECT * FROM perpus_anggota WHERE id=? LIMIT 1');
                $stmt->bind_param('i', $memberId);
                $stmt->execute();
                $member = $stmt->get_result()->fetch_assoc() ?: $member;
                $stmt->close();
            }
            return $member;
        }

        $number = sds_perpus_unique_member_number($conn, sds_perpus_member_number($conn, $ownerType, $ownerId));
        $status = ($activate && $masterActive) ? 'aktif' : 'nonaktif';
        $stmt = $conn->prepare('INSERT INTO perpus_anggota (pemilik_tipe,pemilik_id,nomor_anggota,tipe_member_id,status_keanggotaan,tanggal_daftar) VALUES (?,?,?,?,?,CURDATE())');
        $stmt->bind_param('sisis', $ownerType, $ownerId, $number, $typeId, $status);
        $stmt->execute();
        $id = (int)$conn->insert_id;
        $stmt->close();
        $stmt = $conn->prepare('SELECT * FROM perpus_anggota WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();
        return $member;
    }
}

if (!function_exists('sds_perpus_resolve_identity')) {
    function sds_perpus_resolve_identity(mysqli $conn, string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') return null;
        sds_perpus_ensure_schema($conn);

        $stmt = $conn->prepare('SELECT pemilik_tipe,pemilik_id,uid FROM kartu_rfid WHERE uid=? LIMIT 1');
        $stmt->bind_param('s', $identifier);
        $stmt->execute();
        $card = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($card) {
            $autoActivate = true;
            $setting = $conn->query("SELECT nilai FROM perpus_pengaturan WHERE kode='otomatis_aktifkan_anggota' LIMIT 1");
            if ($setting) $autoActivate = (string)($setting->fetch_assoc()['nilai'] ?? '1') === '1';
            $member = sds_perpus_ensure_member($conn, (string)$card['pemilik_tipe'], (int)$card['pemilik_id'], $autoActivate);
            $profile = sds_perpus_identity_profile($conn, (string)$card['pemilik_tipe'], (int)$card['pemilik_id'], $member);
            return ['owner_type' => (string)$card['pemilik_tipe'], 'owner_id' => (int)$card['pemilik_id'], 'member' => $member, 'rfid' => (string)$card['uid'], 'profile' => $profile];
        }

        $stmt = $conn->prepare('SELECT * FROM perpus_anggota WHERE nomor_anggota=? OR legacy_id_anggota=? LIMIT 1');
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $member = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($member) {
            $profile = sds_perpus_identity_profile($conn, (string)$member['pemilik_tipe'], (int)($member['pemilik_id'] ?? 0), $member);
            return ['owner_type' => (string)$member['pemilik_tipe'], 'owner_id' => (int)($member['pemilik_id'] ?? 0), 'member' => $member, 'rfid' => '', 'profile' => $profile];
        }
        return null;
    }
}

if (!function_exists('sds_perpus_identity_profile')) {
    function sds_perpus_identity_profile(mysqli $conn, string $ownerType, int $ownerId, array $member = []): array
    {
        if ($ownerType === 'siswa') {
            $stmt = $conn->prepare("SELECT ps.id,ps.nama_lengkap,ps.nisn,ps.nipd,ps.foto,ps.status_aktif,
                COALESCE(k.nama_kelas,'-') nama_kelas,COALESCE(j.nama_jurusan,'-') nama_jurusan
                FROM pendaftaran_siswa ps
                LEFT JOIN siswa_kelas sk ON sk.id=(SELECT sk2.id FROM siswa_kelas sk2 WHERE sk2.siswa_id=ps.id ORDER BY sk2.tahun_ajaran DESC,sk2.id DESC LIMIT 1)
                LEFT JOIN kelas k ON k.id=sk.kelas_id
                LEFT JOIN jurusan j ON j.id=ps.jurusan_id
                WHERE ps.id=? LIMIT 1");
            $stmt->bind_param('i', $ownerId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return ['nama' => (string)$row['nama_lengkap'], 'identitas' => (string)($row['nisn'] ?: $row['nipd']), 'unit' => (string)$row['nama_kelas'], 'detail' => (string)$row['nama_jurusan'], 'foto' => (string)($row['foto'] ?? ''), 'aktif' => (int)$row['status_aktif'] === 1];
            }
        } elseif ($ownerType === 'pegawai') {
            $stmt = $conn->prepare('SELECT pegawai_id,nama_lengkap,nip,jabatan,avatar,active FROM pegawai WHERE pegawai_id=? LIMIT 1');
            $stmt->bind_param('i', $ownerId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($row) {
                return ['nama' => (string)$row['nama_lengkap'], 'identitas' => (string)$row['nip'], 'unit' => (string)($row['jabatan'] ?? '-'), 'detail' => 'Pengajar/Pegawai', 'foto' => (string)($row['avatar'] ?? ''), 'aktif' => (string)$row['active'] === 'Y'];
            }
        }
        return ['nama' => (string)($member['legacy_nama'] ?? 'Anggota lama'), 'identitas' => (string)($member['legacy_nis'] ?? $member['legacy_id_anggota'] ?? '-'), 'unit' => (string)($member['legacy_kelas'] ?? '-'), 'detail' => (string)($member['legacy_jurusan'] ?? 'Data perlu verifikasi'), 'foto' => '', 'aktif' => false];
    }
}

if (!function_exists('sds_perpus_setting_value')) {
    function sds_perpus_setting_value(mysqli $conn, string $code, string $default = ''): string
    {
        $stmt = $conn->prepare('SELECT nilai FROM perpus_pengaturan WHERE kode=? LIMIT 1');
        if (!$stmt) return $default;
        $stmt->bind_param('s', $code);
        $stmt->execute();
        $value = $stmt->get_result()->fetch_assoc()['nilai'] ?? null;
        $stmt->close();
        return $value === null ? $default : (string)$value;
    }
}

if (!function_exists('sds_perpus_save_setting')) {
    function sds_perpus_save_setting(mysqli $conn, string $code, string $value, string $description = ''): void
    {
        $stmt = $conn->prepare('INSERT INTO perpus_pengaturan (kode,nilai,keterangan) VALUES (?,?,?) ON DUPLICATE KEY UPDATE nilai=VALUES(nilai),keterangan=CASE WHEN VALUES(keterangan)<>\'\' THEN VALUES(keterangan) ELSE keterangan END');
        if (!$stmt) throw new RuntimeException('Gagal menyiapkan penyimpanan pengaturan: ' . $conn->error);
        $stmt->bind_param('sss', $code, $value, $description);
        if (!$stmt->execute()) { $error=$stmt->error; $stmt->close(); throw new RuntimeException('Gagal menyimpan pengaturan: ' . $error); }
        $stmt->close();
    }
}

if (!function_exists('sds_perpus_notify_member')) {
    function sds_perpus_notify_member(mysqli $conn, int $memberId, string $type, string $title, string $message, string $referenceType = '', int $referenceId = 0): int
    {
        if ($memberId <= 0) return 0;
        $stmt = $conn->prepare('INSERT INTO perpus_notifikasi (anggota_id,tipe,judul,pesan,referensi_tipe,referensi_id) VALUES (?,?,?,?,NULLIF(?,\'\'),NULLIF(?,0))');
        if (!$stmt) return 0;
        $stmt->bind_param('issssi', $memberId, $type, $title, $message, $referenceType, $referenceId);
        if (!$stmt->execute()) { $stmt->close(); return 0; }
        $id = (int)$conn->insert_id;
        $stmt->close();
        return $id;
    }
}

if (!function_exists('sds_perpus_reservation_available_count')) {
    function sds_perpus_reservation_available_count(mysqli $conn, int $bookId): int
    {
        $stmt = $conn->prepare("SELECT COUNT(*) total FROM perpus_buku_eksemplar WHERE buku_id=? AND status='tersedia'");
        if (!$stmt) return 0;
        $stmt->bind_param('i', $bookId); $stmt->execute();
        $count = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0); $stmt->close();
        return $count;
    }
}

if (!function_exists('sds_perpus_promote_next_reservation')) {
    function sds_perpus_promote_next_reservation(mysqli $conn, int $bookId, int $adminId = 0): ?array
    {
        if ($bookId <= 0 || sds_perpus_reservation_available_count($conn, $bookId) <= 0) return null;
        $stmt = $conn->prepare("SELECT id,anggota_id FROM perpus_reservasi WHERE buku_id=? AND status='siap' AND (batas_ambil IS NULL OR batas_ambil>=NOW()) ORDER BY tanggal_siap,id LIMIT 1");
        $stmt->bind_param('i',$bookId); $stmt->execute(); $already=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if ($already) return $already;
        $stmt = $conn->prepare("SELECT id,anggota_id FROM perpus_reservasi WHERE buku_id=? AND status='menunggu' ORDER BY tanggal_reservasi,id LIMIT 1 FOR UPDATE");
        if (!$stmt) return null;
        $stmt->bind_param('i',$bookId); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close();
        if (!$row) return null;
        $days=max(1,(int)sds_perpus_setting_value($conn,'reservasi_hari_ambil','2'));
        $deadline=(new DateTimeImmutable())->modify('+'.$days.' days')->format('Y-m-d H:i:s');
        $reservationId=(int)$row['id']; $memberId=(int)$row['anggota_id'];
        $stmt=$conn->prepare("UPDATE perpus_reservasi SET status='siap',tanggal_siap=NOW(),batas_ambil=?,admin_id=CASE WHEN ?>0 THEN ? ELSE admin_id END WHERE id=? AND status='menunggu'");
        $stmt->bind_param('siii',$deadline,$adminId,$adminId,$reservationId); $stmt->execute(); $affected=$stmt->affected_rows; $stmt->close();
        if ($affected<=0) return null;
        $stmt=$conn->prepare('SELECT judul FROM perpus_buku WHERE id=? LIMIT 1');$stmt->bind_param('i',$bookId);$stmt->execute();$title=(string)($stmt->get_result()->fetch_assoc()['judul']??'Koleksi');$stmt->close();
        sds_perpus_notify_member($conn,$memberId,'reservasi_siap','Reservasi siap diambil','Koleksi “'.$title.'” sudah tersedia. Ambil sebelum '.date('d/m/Y H:i',strtotime($deadline)).'.','reservasi',$reservationId);
        return ['id'=>$reservationId,'anggota_id'=>$memberId,'batas_ambil'=>$deadline];
    }
}

if (!function_exists('sds_perpus_expire_reservations')) {
    function sds_perpus_expire_reservations(mysqli $conn, int $limit = 200): array
    {
        $limit=max(1,min(1000,$limit)); $expired=0; $books=[];
        $result=$conn->query("SELECT id,anggota_id,buku_id FROM perpus_reservasi WHERE status='siap' AND batas_ambil IS NOT NULL AND batas_ambil<NOW() ORDER BY batas_ambil LIMIT ".$limit);
        while($result&&($row=$result->fetch_assoc())){
            $id=(int)$row['id'];$memberId=(int)$row['anggota_id'];$bookId=(int)$row['buku_id'];
            $stmt=$conn->prepare("UPDATE perpus_reservasi SET status='kedaluwarsa',tanggal_selesai=NOW(),catatan=CONCAT_WS(' · ',NULLIF(catatan,''),'Batas pengambilan berakhir') WHERE id=? AND status='siap'");$stmt->bind_param('i',$id);$stmt->execute();$affected=$stmt->affected_rows;$stmt->close();
            if($affected>0){$expired++;$books[$bookId]=true;sds_perpus_notify_member($conn,$memberId,'reservasi_kedaluwarsa','Reservasi kedaluwarsa','Batas pengambilan reservasi Anda telah berakhir.','reservasi',$id);}
        }
        foreach(array_keys($books) as $bookId) sds_perpus_promote_next_reservation($conn,(int)$bookId,0);
        return ['expired'=>$expired,'books'=>count($books)];
    }
}

if (!function_exists('sds_perpus_process_due_reminders')) {
    function sds_perpus_process_due_reminders(mysqli $conn, int $limit = 500): array
    {
        if (sds_perpus_setting_value($conn,'pengingat_aktif','1')!=='1') return ['created'=>0,'skipped'=>0,'expired'=>0];
        $expired=sds_perpus_expire_reservations($conn,200);
        $days=max(0,min(30,(int)sds_perpus_setting_value($conn,'pengingat_hari_sebelum','2')));
        $limit=max(1,min(2000,$limit)); $created=0; $skipped=0;
        $sql="SELECT d.id detail_id,d.tanggal_jatuh_tempo,p.anggota_id,b.judul FROM perpus_peminjaman_detail d JOIN perpus_peminjaman p ON p.id=d.peminjaman_id LEFT JOIN perpus_buku b ON b.id=d.buku_id WHERE d.status='dipinjam' AND d.tanggal_jatuh_tempo IS NOT NULL AND d.tanggal_jatuh_tempo<=DATE_ADD(CURDATE(),INTERVAL ? DAY) ORDER BY d.tanggal_jatuh_tempo LIMIT ".$limit;
        $stmt=$conn->prepare($sql);$stmt->bind_param('i',$days);$stmt->execute();$result=$stmt->get_result();
        while($row=$result->fetch_assoc()){
            $detailId=(int)$row['detail_id'];$memberId=(int)$row['anggota_id'];$due=(string)$row['tanggal_jatuh_tempo'];$title=(string)($row['judul']??'Koleksi');
            if($due<date('Y-m-d')){$kind='terlambat';$head='Peminjaman terlambat';$msg='Koleksi “'.$title.'” telah melewati jatuh tempo '.date('d/m/Y',strtotime($due)).'. Segera kembalikan ke Perpustakaan.';}
            elseif($due===date('Y-m-d')){$kind='jatuh_tempo';$head='Jatuh tempo hari ini';$msg='Koleksi “'.$title.'” jatuh tempo hari ini. Silakan kembalikan atau hubungi petugas.';}
            else{$kind='akan_jatuh_tempo';$head='Pengingat jatuh tempo';$msg='Koleksi “'.$title.'” akan jatuh tempo pada '.date('d/m/Y',strtotime($due)).'.';}
            $today=date('Y-m-d');$log=$conn->prepare('INSERT IGNORE INTO perpus_pengingat_log (detail_id,anggota_id,jenis,tanggal_proses,pesan) VALUES (?,?,?,?,?)');$log->bind_param('iisss',$detailId,$memberId,$kind,$today,$msg);$log->execute();$inserted=$log->affected_rows;$log->close();
            if($inserted>0){sds_perpus_notify_member($conn,$memberId,$kind,$head,$msg,'pinjaman',$detailId);$created++;}else{$skipped++;}
        }
        $stmt->close();
        return ['created'=>$created,'skipped'=>$skipped,'expired'=>(int)($expired['expired']??0)];
    }
}



if (!function_exists('sds_perpus_audit_log')) {
    function sds_perpus_audit_log(mysqli $conn, string $action, string $entity, $entityId = '', string $summary = '', ?array $before = null, ?array $after = null, ?int $adminId = null): void
    {
        if (!sds_perpus_table_exists($conn, 'perpus_audit_log')) return;
        $action = mb_substr(trim($action) ?: 'aktivitas', 0, 40);
        $entity = mb_substr(trim($entity) ?: 'sistem', 0, 60);
        $entityValue = mb_substr((string)$entityId, 0, 80);
        $summary = mb_substr(trim($summary), 0, 255);
        $beforeJson = $before === null ? null : json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $afterJson = $after === null ? null : json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $adminId = $adminId ?? (int)($_SESSION['admin_id'] ?? 0);
        $ip = mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $agent = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
        $stmt = $conn->prepare('INSERT INTO perpus_audit_log (admin_id,aksi,entitas,entitas_id,ringkasan,data_lama,data_baru,ip_address,user_agent) VALUES (NULLIF(?,0),?,?,?,?,?,?,?,?)');
        if (!$stmt) return;
        $stmt->bind_param('issssssss', $adminId, $action, $entity, $entityValue, $summary, $beforeJson, $afterJson, $ip, $agent);
        @$stmt->execute();
        $stmt->close();
    }
}

if (!function_exists('sds_perpus_run_integrity_audit')) {
    function sds_perpus_run_integrity_audit(mysqli $conn, int $adminId = 0): array
    {
        $definitions = [
            [
                'EKSEMPLAR_DIPINJAM_TANPA_TRANSAKSI','kritis','Eksemplar berstatus dipinjam tanpa transaksi aktif',
                "SELECT e.id,e.barcode,b.judul FROM perpus_buku_eksemplar e LEFT JOIN perpus_buku b ON b.id=e.buku_id WHERE e.status='dipinjam' AND NOT EXISTS(SELECT 1 FROM perpus_peminjaman_detail d WHERE d.eksemplar_id=e.id AND d.status='dipinjam')"
            ],
            [
                'TRANSAKSI_AKTIF_STATUS_EKSEMPLAR','kritis','Transaksi aktif tetapi status eksemplar tidak dipinjam',
                "SELECT d.id,e.barcode,e.status,b.judul FROM perpus_peminjaman_detail d LEFT JOIN perpus_buku_eksemplar e ON e.id=d.eksemplar_id LEFT JOIN perpus_buku b ON b.id=d.buku_id WHERE d.status='dipinjam' AND (e.id IS NULL OR e.status<>'dipinjam')"
            ],
            [
                'DETAIL_TANPA_HEADER','kritis','Detail peminjaman kehilangan transaksi induk',
                "SELECT d.id,d.peminjaman_id,d.kode_resi FROM perpus_peminjaman_detail d LEFT JOIN perpus_peminjaman p ON p.id=d.peminjaman_id WHERE p.id IS NULL"
            ],
            [
                'PINJAMAN_TANPA_ANGGOTA','kritis','Transaksi peminjaman kehilangan anggota',
                "SELECT p.id,p.anggota_id,p.tanggal_pinjam FROM perpus_peminjaman p LEFT JOIN perpus_anggota a ON a.id=p.anggota_id WHERE a.id IS NULL"
            ],
            [
                'HEADER_AKTIF_TANPA_ITEM','peringatan','Transaksi aktif tidak memiliki item aktif',
                "SELECT p.id,p.anggota_id,p.tanggal_pinjam FROM perpus_peminjaman p WHERE p.status='aktif' AND NOT EXISTS(SELECT 1 FROM perpus_peminjaman_detail d WHERE d.peminjaman_id=p.id AND d.status='dipinjam')"
            ],
            [
                'DENDA_STATUS_TIDAK_SESUAI','peringatan','Status denda tidak sesuai nilai pembayaran',
                "SELECT d.id,d.denda,d.denda_status,COALESCE(SUM(x.nominal),0) diselesaikan FROM perpus_peminjaman_detail d LEFT JOIN perpus_denda_pembayaran x ON x.detail_id=d.id WHERE d.denda>0 GROUP BY d.id,d.denda,d.denda_status HAVING (d.denda_status='belum_lunas' AND diselesaikan>=d.denda) OR (d.denda_status IN ('lunas','dibebaskan') AND diselesaikan<d.denda)"
            ],
            [
                'RESERVASI_DUPLIKAT','peringatan','Anggota memiliki reservasi aktif ganda pada buku yang sama',
                "SELECT anggota_id,buku_id,COUNT(*) jumlah FROM perpus_reservasi WHERE status IN ('menunggu','siap') GROUP BY anggota_id,buku_id HAVING COUNT(*)>1"
            ],
            [
                'RESERVASI_REFERENSI_HILANG','kritis','Reservasi kehilangan anggota atau buku',
                "SELECT r.id,r.anggota_id,r.buku_id,r.status FROM perpus_reservasi r LEFT JOIN perpus_anggota a ON a.id=r.anggota_id LEFT JOIN perpus_buku b ON b.id=r.buku_id WHERE a.id IS NULL OR b.id IS NULL"
            ],
            [
                'RFID_PEMILIK_HILANG','peringatan','Kartu RFID tidak memiliki pemilik master SDS',
                "SELECT k.id,k.uid,k.pemilik_tipe,k.pemilik_id FROM kartu_rfid k LEFT JOIN pendaftaran_siswa s ON k.pemilik_tipe='siswa' AND s.id=k.pemilik_id LEFT JOIN pegawai p ON k.pemilik_tipe='pegawai' AND p.pegawai_id=k.pemilik_id WHERE (k.pemilik_tipe='siswa' AND s.id IS NULL) OR (k.pemilik_tipe='pegawai' AND p.pegawai_id IS NULL)"
            ],
            [
                'ANGGOTA_MASTER_NONAKTIF','info','Anggota aktif tetapi master SDS sudah nonaktif',
                "SELECT a.id,a.nomor_anggota,a.pemilik_tipe,a.pemilik_id FROM perpus_anggota a LEFT JOIN pendaftaran_siswa s ON a.pemilik_tipe='siswa' AND s.id=a.pemilik_id LEFT JOIN pegawai p ON a.pemilik_tipe='pegawai' AND p.pegawai_id=a.pemilik_id WHERE a.status_keanggotaan='aktif' AND ((a.pemilik_tipe='siswa' AND COALESCE(s.status_aktif,0)<>1) OR (a.pemilik_tipe='pegawai' AND COALESCE(p.active,'N')<>'Y'))"
            ],
        ];
        $results = [];
        foreach ($definitions as [$code,$level,$title,$sql]) {
            $rows = [];
            $result = $conn->query($sql . ' LIMIT 25');
            if ($result) while ($row = $result->fetch_assoc()) $rows[] = $row;
            $countSql = 'SELECT COUNT(*) total FROM (' . $sql . ') audit_source';
            $countResult = $conn->query($countSql);
            $count = $countResult ? (int)($countResult->fetch_assoc()['total'] ?? count($rows)) : count($rows);
            $json = json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $stmt = $conn->prepare('INSERT INTO perpus_audit_check (kode,tingkat,judul,jumlah_temuan,detail_json,checked_by) VALUES (?,?,?,?,?,NULLIF(?,0))');
            if ($stmt) {
                $stmt->bind_param('sssisi', $code, $level, $title, $count, $json, $adminId);
                $stmt->execute();
                $stmt->close();
            }
            $results[] = ['kode'=>$code,'tingkat'=>$level,'judul'=>$title,'jumlah'=>$count,'detail'=>$rows];
        }
        sds_perpus_audit_log($conn, 'audit_integritas', 'sistem', '', 'Pemeriksaan integritas Perpustakaan dijalankan', null, ['jumlah_pemeriksaan'=>count($results)], $adminId);
        return $results;
    }
}
