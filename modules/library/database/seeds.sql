INSERT INTO `perpus_tipe_member` (`legacy_kode_tipe`,`nama`,`jumlah_peminjaman`,`periode_peminjaman`,`denda_per_hari`,`maksimal_perpanjangan`,`hari_perpanjangan`,`status_aktif`) VALUES
(1,'GURU',3,14,0,1,7,1),(2,'KARYAWAN',3,14,0,1,7,1),(3,'SISWA',2,7,1000,1,7,1),(4,'KEPALA SEKOLAH',3,14,0,1,7,1)
ON DUPLICATE KEY UPDATE `nama`=VALUES(`nama`);

INSERT INTO `perpus_pengaturan` (`kode`,`nilai`,`keterangan`) VALUES
('schema_version','2.6.0','Versi schema modul Perpustakaan SDS'),
('opac_aktif','1','Status katalog publik OPAC'),
('reservasi_aktif','1','Aktifkan reservasi melalui OPAC'),
('saran_aktif','1','Aktifkan kritik dan saran'),
('otomatis_aktifkan_anggota','1','Buat anggota saat RFID pertama digunakan')
ON DUPLICATE KEY UPDATE `keterangan`=VALUES(`keterangan`);
