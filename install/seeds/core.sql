INSERT INTO `pengaturan` (`id`,`nama_sekolah`,`system_timezone`,`date_format`,`number_locale`)
VALUES (1,'Sekolah','Asia/Jakarta','d/m/Y','id_ID')
ON DUPLICATE KEY UPDATE `id`=`id`;

INSERT INTO `setting` (`site_id`,`site_name`,`nama_sekolah`,`timezone`,`whatsapp_active`)
VALUES (1,'SDS','Sekolah','Asia/Jakarta','N')
ON DUPLICATE KEY UPDATE `site_id`=`site_id`;

INSERT INTO `tahun_ajaran` (`tahun_ajaran`,`status`,`semester_aktif`,`is_active`,`activated_at`)
VALUES (CONCAT(YEAR(CURDATE()),'/',YEAR(CURDATE())+1),'active','ganjil',1,NOW())
ON DUPLICATE KEY UPDATE `tahun_ajaran_id`=`tahun_ajaran_id`;
