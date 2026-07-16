<?php
// realtime-absensi.php (POLLING JSON - ringan & aman)
// Client polling tiap 2-5 detik, cek perubahan via realtime.flag (mtime)

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
date_default_timezone_set('Asia/Jakarta');

// jangan tahan session lock
if (session_status() === PHP_SESSION_ACTIVE) {
  session_write_close();
}

// hard timeout (biar tidak ada proses nyangkut)
@ini_set('max_execution_time', '3');
@set_time_limit(3);

$flag  = __DIR__ . '/realtime.flag';
$mtime = (is_file($flag) ? (int)@filemtime($flag) : 0);

// client kirim "last" (epoch seconds)
$last = isset($_GET['last']) ? (int)$_GET['last'] : 0;
if ($last < 0) $last = 0;

// kalau client kirim last yang aneh (lebih besar dari server), clamp
$now = time();
if ($last > $now + 5) $last = $now;

echo json_encode([
  'ok'      => true,
  'changed' => ($mtime > $last),
  'last'    => $mtime,
  'server'  => $now,
], JSON_UNESCAPED_SLASHES);