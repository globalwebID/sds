<?php
require '_config.php';

header('Content-Type: application/json; charset=utf-8');

// ===============================
// API KEY (GANTI SENDIRI)
// ===============================
$API_KEY = 'SMK1PROBOLINGGO_READONLY_2026';

if (!isset($_GET['key']) || $_GET['key'] !== $API_KEY) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized'
    ]);
    exit;
}

@mysqli_set_charset($conn_absen, 'utf8mb4');

// ===============================
// PARAMETER WAJIB
// ===============================
$nisn = trim($_GET['nisn'] ?? '');
if ($nisn === '') {
    echo json_encode(['success'=>false,'message'=>'Parameter nisn wajib']);
    exit;
}

// ===============================
// FILTER TANGGAL
// ===============================
$tglAwal  = $_GET['tglAwal'] ?? date('Y-m-d', strtotime('-6 days'));
$tglAkhir = $_GET['tglAkhir'] ?? date('Y-m-d');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAwal))  $tglAwal  = date('Y-m-d', strtotime('-6 days'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tglAkhir)) $tglAkhir = date('Y-m-d');

// ===============================
// AMBIL user_id
// ===============================
$stmtU = mysqli_prepare($conn_absen, "SELECT user_id, nama_lengkap FROM user WHERE nisn=? LIMIT 1");
mysqli_stmt_bind_param($stmtU, "s", $nisn);
mysqli_stmt_execute($stmtU);
$resU = mysqli_stmt_get_result($stmtU);

if(!$resU || mysqli_num_rows($resU) === 0){
    echo json_encode(['success'=>false,'message'=>'User tidak ditemukan']);
    exit;
}

$user = mysqli_fetch_assoc($resU);
$user_id = $user['user_id'];

// ===============================
// QUERY ABSENSI
// ===============================
$stmt = mysqli_prepare($conn_absen, "
  SELECT absen_id, tanggal, absen_in, absen_out, status_masuk, status_pulang, keterangan
  FROM absen
  WHERE user_id=?
    AND tanggal BETWEEN ? AND ?
  ORDER BY tanggal DESC
");

mysqli_stmt_bind_param($stmt, "iss", $user_id, $tglAwal, $tglAkhir);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

$data = [];
while($row = mysqli_fetch_assoc($res)){
  $data[] = [
    'absen_id'  => (int)$row['absen_id'],
    'tanggal'   => $row['tanggal'],
    'absen_in'  => $row['absen_in'] ?? '',
    'absen_out' => $row['absen_out'] ?? '',
    'status_masuk'  => $row['status_masuk'] ?? '',
    'status_pulang' => $row['status_pulang'] ?? '',
    'keterangan'    => $row['keterangan'] ?? '',
  ];
}

echo json_encode([
    'success' => true,
    'nama'    => $user['nama_lengkap'],
    'nisn'    => $nisn,
    'periode' => [$tglAwal, $tglAkhir],
    'total'   => count($data),
    'data'    => $data
]);