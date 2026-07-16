<?php
include_once '../../sw-library/sw-config.php';
include_once '../../sw-library/sw-function.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

@mysqli_set_charset($connection, 'utf8mb4');

/* Proteksi: monitor hanya untuk admin */
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  http_response_code(401);
  echo json_encode(['ok'=>false,'message'=>'Unauthorized']);
  exit;
}

$rows = [];
$q = $connection->query("
  SELECT
    device_id, cam_name, lokasi, last_seen,
    UNIX_TIMESTAMP(last_seen) AS last_seen_ts,
    last_image
  FROM cameras
  ORDER BY cam_name ASC
");

if ($q) {
  while($r = $q->fetch_assoc()){
    $rows[] = $r;
  }
}

echo json_encode([
  'ok' => true,
  'server_ts' => time(),
  'data' => $rows
]);
