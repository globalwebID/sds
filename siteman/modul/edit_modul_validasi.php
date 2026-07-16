<?php
$id = intval(post('id'));
if ($id <= 0) die("ID tidak valid.");

$tahunAjaran = post('tahun_ajaran');
$nisn = post('nisn');

$stmt = $conn->prepare("SELECT kelas_id, file_kip, file_kk, file_ijazah FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->bind_result($old_kelas_id, $old_kip, $old_kk, $old_ijazah);
$stmt->fetch();
$stmt->close();

$new_kelas_id = intval(post('kelas'));
if ($new_kelas_id <= 0) die("Kelas tidak valid.");

$cek = $conn->prepare("SELECT COUNT(*) FROM kelas WHERE id = ?");
$cek->bind_param("i", $new_kelas_id);
$cek->execute();
$cek->bind_result($found);
$cek->fetch();
$cek->close();

if (!$found) die("Pilihan kelas tidak ditemukan.");

$subfolder = "$tahunAjaran/$nisn";
$file_kip    = uploadFile('file_kip',   ['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_kip);
$file_kk     = uploadFile('file_kk',    ['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_kk);
$file_ijazah = uploadFile('file_ijazah',['pdf', 'jpg', 'jpeg', 'png'], 5, $subfolder, $old_ijazah);
