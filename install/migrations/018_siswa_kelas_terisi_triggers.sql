DROP TRIGGER IF EXISTS `update_terisi_after_insert`;
DROP TRIGGER IF EXISTS `update_terisi_after_delete`;
DROP TRIGGER IF EXISTS `update_terisi_after_update`;

DELIMITER $$
CREATE TRIGGER `update_terisi_after_insert`
AFTER INSERT ON `siswa_kelas`
FOR EACH ROW
BEGIN
  UPDATE `kelas`
  SET `terisi`=(SELECT COUNT(*) FROM `siswa_kelas` WHERE `kelas_id`=NEW.`kelas_id`)
  WHERE `id`=NEW.`kelas_id`;
END$$

CREATE TRIGGER `update_terisi_after_delete`
AFTER DELETE ON `siswa_kelas`
FOR EACH ROW
BEGIN
  UPDATE `kelas`
  SET `terisi`=(SELECT COUNT(*) FROM `siswa_kelas` WHERE `kelas_id`=OLD.`kelas_id`)
  WHERE `id`=OLD.`kelas_id`;
END$$

CREATE TRIGGER `update_terisi_after_update`
AFTER UPDATE ON `siswa_kelas`
FOR EACH ROW
BEGIN
  IF OLD.`kelas_id`<>NEW.`kelas_id` THEN
    UPDATE `kelas`
    SET `terisi`=(SELECT COUNT(*) FROM `siswa_kelas` WHERE `kelas_id`=OLD.`kelas_id`)
    WHERE `id`=OLD.`kelas_id`;
    UPDATE `kelas`
    SET `terisi`=(SELECT COUNT(*) FROM `siswa_kelas` WHERE `kelas_id`=NEW.`kelas_id`)
    WHERE `id`=NEW.`kelas_id`;
  END IF;
END$$
DELIMITER ;

UPDATE `kelas` k
SET k.`terisi`=(SELECT COUNT(*) FROM `siswa_kelas` sk WHERE sk.`kelas_id`=k.`id`);
