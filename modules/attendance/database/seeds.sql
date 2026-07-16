-- Data hak akses awal modul Absensi.
-- Aman dijalankan ulang: master diperbarui berdasarkan ID dan role hanya
-- ditambahkan apabila kombinasi level/modul belum tersedia.

INSERT INTO `level` (`level_id`,`level_nama`) VALUES
(1,'Superadmin'),(2,'User'),(3,'Guru')
ON DUPLICATE KEY UPDATE `level_nama`=VALUES(`level_nama`);

INSERT INTO `modul` (`modul_id`,`modul_nama`) VALUES
(1,'Siswa'),(2,'Wali Murid'),(3,'Kelas'),(4,'Artikel'),(5,'Lokasi'),
(6,'Jam Sekolah'),(7,'Tahun ajaran'),(8,'Libur'),(9,'Izin'),(10,'Laporan'),
(11,'Pengaturan Web'),(13,'Admin'),(14,'Hak Akses'),(15,'Master Data'),
(16,'ID CARD'),(17,'Alumni'),(18,'Pegawai'),(19,'Users'),
(20,'Mata Pelajaran'),(21,'E-KBM'),(22,'Jadwal Mengajar'),(23,'Slider'),
(24,'Absen Manual'),(25,'Pelanggaran'),(26,'Template Surat')
ON DUPLICATE KEY UPDATE `modul_nama`=VALUES(`modul_nama`);

-- Superadmin memperoleh seluruh fitur Absensi.
INSERT INTO `role` (`level_id`,`modul_id`,`lihat`,`modifikasi`,`hapus`)
SELECT 1,m.modul_id,'Y','Y','Y' FROM `modul` m
WHERE NOT EXISTS (
  SELECT 1 FROM `role` r WHERE r.level_id=1 AND r.modul_id=m.modul_id
);

-- Staf/operator: akses operasional, tanpa administrasi akun, hak akses,
-- artikel, E-KBM, pelanggaran, ID Card, dan template surat.
INSERT INTO `role` (`level_id`,`modul_id`,`lihat`,`modifikasi`,`hapus`)
SELECT 2,m.modul_id,
  IF(m.modul_id IN (2,4,13,14,16,21,25,26),'N','Y'),
  IF(m.modul_id IN (2,4,13,14,16,21,25,26),'N','Y'),
  IF(m.modul_id IN (2,4,13,14,16,21,25,26),'N','Y')
FROM `modul` m
WHERE NOT EXISTS (
  SELECT 1 FROM `role` r WHERE r.level_id=2 AND r.modul_id=m.modul_id
);

-- Guru hanya memerlukan jam sekolah, izin, dan laporan.
INSERT INTO `role` (`level_id`,`modul_id`,`lihat`,`modifikasi`,`hapus`)
SELECT 3,m.modul_id,'Y','Y','Y' FROM `modul` m
WHERE m.modul_id IN (6,9,10)
  AND NOT EXISTS (
    SELECT 1 FROM `role` r WHERE r.level_id=3 AND r.modul_id=m.modul_id
  );
