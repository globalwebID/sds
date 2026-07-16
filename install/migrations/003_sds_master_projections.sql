-- Kelas SDS adalah sumber utama; tabel ini hanya proyeksi kompatibilitas Absensi.
DELETE FROM `absensi_kelas`;
INSERT INTO `absensi_kelas` (`kelas_id`,`parent_id`,`nama_kelas`)
SELECT `id`, CAST(COALESCE(`tingkat_id`,0) AS CHAR), LEFT(COALESCE(`nama_kelas`,''),40) FROM `kelas`;

DROP TRIGGER IF EXISTS `trg_sds_kelas_ai`;
DROP TRIGGER IF EXISTS `trg_sds_kelas_au`;
DROP TRIGGER IF EXISTS `trg_sds_kelas_ad`;
DELIMITER $$
CREATE TRIGGER `trg_sds_kelas_ai` AFTER INSERT ON `kelas` FOR EACH ROW
BEGIN
  INSERT INTO `absensi_kelas` (`kelas_id`,`parent_id`,`nama_kelas`)
  VALUES (NEW.id,CAST(COALESCE(NEW.tingkat_id,0) AS CHAR),LEFT(COALESCE(NEW.nama_kelas,''),40))
  ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`nama_kelas`=VALUES(`nama_kelas`);
END$$
CREATE TRIGGER `trg_sds_kelas_au` AFTER UPDATE ON `kelas` FOR EACH ROW
BEGIN
  INSERT INTO `absensi_kelas` (`kelas_id`,`parent_id`,`nama_kelas`)
  VALUES (NEW.id,CAST(COALESCE(NEW.tingkat_id,0) AS CHAR),LEFT(COALESCE(NEW.nama_kelas,''),40))
  ON DUPLICATE KEY UPDATE `parent_id`=VALUES(`parent_id`),`nama_kelas`=VALUES(`nama_kelas`);
END$$
CREATE TRIGGER `trg_sds_kelas_ad` AFTER DELETE ON `kelas` FOR EACH ROW
BEGIN
  DELETE FROM `absensi_kelas` WHERE `kelas_id`=OLD.id;
END$$
DELIMITER ;

-- Peserta didik SDS adalah sumber utama; user merupakan proyeksi untuk mesin Absensi lama.
INSERT INTO `user` (`nisn`,`rfid`,`email`,`nama_lengkap`,`tempat_lahir`,`tanggal_lahir`,`jenis_kelamin`,`kelas`,`tahun_ajaran`,`alamat`,`telp`,`avatar`,`tanggal_registrasi`,`tanggal_login`,`ip`,`status`,`active`)
SELECT p.`nisn`,p.`rfid_uid`,COALESCE(p.`email`,''),p.`nama_lengkap`,p.`tempat_lahir`,CAST(p.`tanggal_lahir` AS CHAR),p.`jenis_kelamin`,k.`nama_kelas`,p.`tahun_ajaran`,p.`alamat`,p.`nohp_siswa`,p.`foto`,NOW(),NOW(),'','Offline',IF(p.`status_aktif`=1,'Y','N')
FROM `pendaftaran_siswa` p
LEFT JOIN `kelas` k ON k.`id`=p.`kelas_id`
WHERE p.`nisn` IS NOT NULL AND p.`nisn`<>''
ON DUPLICATE KEY UPDATE `rfid`=VALUES(`rfid`),`email`=VALUES(`email`),`nama_lengkap`=VALUES(`nama_lengkap`),`tempat_lahir`=VALUES(`tempat_lahir`),`tanggal_lahir`=VALUES(`tanggal_lahir`),`jenis_kelamin`=VALUES(`jenis_kelamin`),`kelas`=VALUES(`kelas`),`tahun_ajaran`=VALUES(`tahun_ajaran`),`alamat`=VALUES(`alamat`),`telp`=VALUES(`telp`),`avatar`=VALUES(`avatar`),`active`=VALUES(`active`);

DROP TRIGGER IF EXISTS `trg_sds_siswa_ai`;
DROP TRIGGER IF EXISTS `trg_sds_siswa_au`;
DROP TRIGGER IF EXISTS `trg_sds_siswa_ad`;
DELIMITER $$
CREATE TRIGGER `trg_sds_siswa_ai` AFTER INSERT ON `pendaftaran_siswa` FOR EACH ROW
BEGIN
  INSERT INTO `user` (`nisn`,`rfid`,`email`,`nama_lengkap`,`tempat_lahir`,`tanggal_lahir`,`jenis_kelamin`,`kelas`,`tahun_ajaran`,`alamat`,`telp`,`avatar`,`tanggal_registrasi`,`tanggal_login`,`ip`,`status`,`active`)
  VALUES (NEW.nisn,NEW.rfid_uid,COALESCE(NEW.email,''),NEW.nama_lengkap,NEW.tempat_lahir,CAST(NEW.tanggal_lahir AS CHAR),NEW.jenis_kelamin,(SELECT k.nama_kelas FROM kelas k WHERE k.id=NEW.kelas_id LIMIT 1),NEW.tahun_ajaran,NEW.alamat,NEW.nohp_siswa,NEW.foto,NOW(),NOW(),'','Offline',IF(NEW.status_aktif=1,'Y','N'))
  ON DUPLICATE KEY UPDATE `rfid`=VALUES(`rfid`),`email`=VALUES(`email`),`nama_lengkap`=VALUES(`nama_lengkap`),`tempat_lahir`=VALUES(`tempat_lahir`),`tanggal_lahir`=VALUES(`tanggal_lahir`),`jenis_kelamin`=VALUES(`jenis_kelamin`),`kelas`=VALUES(`kelas`),`tahun_ajaran`=VALUES(`tahun_ajaran`),`alamat`=VALUES(`alamat`),`telp`=VALUES(`telp`),`avatar`=VALUES(`avatar`),`active`=VALUES(`active`);
END$$
CREATE TRIGGER `trg_sds_siswa_au` AFTER UPDATE ON `pendaftaran_siswa` FOR EACH ROW
BEGIN
  UPDATE `user` SET `nisn`=NEW.nisn,`rfid`=NEW.rfid_uid,`email`=COALESCE(NEW.email,''),`nama_lengkap`=NEW.nama_lengkap,`tempat_lahir`=NEW.tempat_lahir,`tanggal_lahir`=CAST(NEW.tanggal_lahir AS CHAR),`jenis_kelamin`=NEW.jenis_kelamin,`kelas`=(SELECT k.nama_kelas FROM kelas k WHERE k.id=NEW.kelas_id LIMIT 1),`tahun_ajaran`=NEW.tahun_ajaran,`alamat`=NEW.alamat,`telp`=NEW.nohp_siswa,`avatar`=NEW.foto,`active`=IF(NEW.status_aktif=1,'Y','N') WHERE `nisn`=OLD.nisn;
  IF ROW_COUNT()=0 THEN
    INSERT INTO `user` (`nisn`,`rfid`,`email`,`nama_lengkap`,`tempat_lahir`,`tanggal_lahir`,`jenis_kelamin`,`kelas`,`tahun_ajaran`,`alamat`,`telp`,`avatar`,`tanggal_registrasi`,`tanggal_login`,`ip`,`status`,`active`)
    VALUES (NEW.nisn,NEW.rfid_uid,COALESCE(NEW.email,''),NEW.nama_lengkap,NEW.tempat_lahir,CAST(NEW.tanggal_lahir AS CHAR),NEW.jenis_kelamin,(SELECT k.nama_kelas FROM kelas k WHERE k.id=NEW.kelas_id LIMIT 1),NEW.tahun_ajaran,NEW.alamat,NEW.nohp_siswa,NEW.foto,NOW(),NOW(),'','Offline',IF(NEW.status_aktif=1,'Y','N'))
    ON DUPLICATE KEY UPDATE `rfid`=VALUES(`rfid`),`nama_lengkap`=VALUES(`nama_lengkap`),`kelas`=VALUES(`kelas`),`active`=VALUES(`active`);
  END IF;
END$$
CREATE TRIGGER `trg_sds_siswa_ad` AFTER DELETE ON `pendaftaran_siswa` FOR EACH ROW
BEGIN
  UPDATE `user` SET `active`='N' WHERE `nisn`=OLD.nisn;
END$$
DELIMITER ;
