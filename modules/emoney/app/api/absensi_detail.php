<?php
require '_config.php';
requireAuth();

@mysqli_set_charset($conn_absen, 'utf8mb4');

$nisn = trim((string)($_SESSION['nisn'] ?? ''));
if ($nisn === '') response(false, 'Session nisn kosong');

$absen_id = (int)($_GET['id'] ?? 0);
if ($absen_id <= 0) response(false, 'ID absensi tidak valid');

// ambil user_id dari session (nisn)
$stmtU = mysqli_prepare($conn_absen, "SELECT user_id FROM user WHERE nisn=? LIMIT 1");
if(!$stmtU) response(false, 'Prepare user gagal', ['db_error'=>mysqli_error($conn_absen)]);
mysqli_stmt_bind_param($stmtU, "s", $nisn);
mysqli_stmt_execute($stmtU);
$resU = mysqli_stmt_get_result($stmtU);

if(!$resU || mysqli_num_rows($resU) === 0){
  response(false, 'User tidak ditemukan di DB absensi');
}
$user = mysqli_fetch_assoc($resU);
$user_id = $user['user_id'];

// ambil detail (PASTIKAN milik user tsb)
$stmt = mysqli_prepare($conn_absen, "
  SELECT absen_id, tanggal, absen_in, absen_out, foto_in, foto_out, status_masuk, status_pulang, keterangan
  FROM absen
  WHERE user_id=? AND absen_id=?
  LIMIT 1
");
if(!$stmt) response(false, 'Prepare detail gagal', ['db_error'=>mysqli_error($conn_absen)]);

mysqli_stmt_bind_param($stmt, "si", $user_id, $absen_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if(!$res || mysqli_num_rows($res) === 0){
  response(false, 'Detail absensi tidak ditemukan');
}

$row = mysqli_fetch_assoc($res);

$data = [
  'absen_id'      => (int)$row['absen_id'],
  'tanggal'       => $row['tanggal'],
  'absen_in'      => $row['absen_in'] ?? '',
  'absen_out'     => $row['absen_out'] ?? '',
  'foto_in'       => $row['foto_in'] ?? '',
  'foto_out'      => $row['foto_out'] ?? '',
  'status_masuk'  => $row['status_masuk'] ?? '',
  'status_pulang' => $row['status_pulang'] ?? '',
  'keterangan'    => $row['keterangan'] ?? '',
];

response(true, 'Detail absensi', $data);
