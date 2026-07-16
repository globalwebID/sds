<?php
require '_config.php';
requireAuth();

@mysqli_set_charset($conn_absen, 'utf8mb4');

// ===============================
// AMBIL PARAMETER FILTER (VALID)
// ===============================
$tglAwal  = $_GET['tglAwal'] ?? date('Y-m-d', strtotime('-6 days'));
$tglAkhir = $_GET['tglAkhir'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAwal))  $tglAwal  = date('Y-m-d', strtotime('-6 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) $tglAkhir = date('Y-m-d');

// ===============================
// AMBIL user_id dari session (nisn)
// ===============================
$nisn = trim((string)($_SESSION['nisn'] ?? ''));
if ($nisn === '') response(false, 'Session nisn kosong');

$stmtU = mysqli_prepare($conn_absen, "SELECT user_id, nama_lengkap FROM user WHERE nisn=? LIMIT 1");
if(!$stmtU) response(false, 'Prepare user gagal', ['db_error'=>mysqli_error($conn_absen)]);
mysqli_stmt_bind_param($stmtU, "s", $nisn);
mysqli_stmt_execute($stmtU);
$resU = mysqli_stmt_get_result($stmtU);

if(!$resU || mysqli_num_rows($resU) === 0){
  response(false, 'User tidak ditemukan di DB absensi');
}

$user = mysqli_fetch_assoc($resU);
$user_id = $user['user_id'];

// ===============================
// QUERY ABSENSI (prepared)
// ===============================
$stmt = mysqli_prepare($conn_absen, "
  SELECT absen_id, tanggal, absen_in, absen_out, status_masuk, status_pulang, keterangan
  FROM absen
  WHERE user_id=?
    AND tanggal BETWEEN ? AND ?
  ORDER BY tanggal DESC
");
if(!$stmt) response(false, 'Prepare absen gagal', ['db_error'=>mysqli_error($conn_absen)]);

mysqli_stmt_bind_param($stmt, "sss", $user_id, $tglAwal, $tglAkhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$data = [];
while($row = mysqli_fetch_assoc($res)){
  $data[] = [
    'absen_id'  => (int)$row['absen_id'],
    'tanggal'   => $row['tanggal'],
    'absen_in'  => $row['absen_in'] ?? '',
    'absen_out' => $row['absen_out'] ?? '',
    'status'    => $row['status_masuk'] ?? 'Hadir',
  ];
}

response(true, 'Data absensi berhasil', $data);

