ALTER TABLE `siswa_kelas`
  ADD UNIQUE INDEX IF NOT EXISTS `uq_siswa_kelas_tahun` (`siswa_id`,`tahun_ajaran`),
  ADD INDEX IF NOT EXISTS `idx_siswa_kelas_kelas_tahun` (`kelas_id`,`tahun_ajaran`);
