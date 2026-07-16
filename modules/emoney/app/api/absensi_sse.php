<?php
require '_config.php';
requireAuth();

/**
 * SSE endpoint untuk absensi.
 * Cara kerja:
 * - Server memantau file flag: absensi_realtime.flag
 * - Jika file berubah -> kirim event "update"
 * - Client reload data absensi
 */

header('Content-Type: text/event-stream; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // nginx fix buffering

@mysqli_set_charset($conn, 'utf8mb4');

/* ===============================
   VALIDASI SESSION
================================ */
$id_siswa = (int)($_SESSION['id_siswa'] ?? 0);
if ($id_siswa <= 0) {
  http_response_code(401);
  echo "event: error\n";
  echo "data: " . json_encode(['message' => 'Session id_siswa tidak valid']) . "\n\n";
  @ob_flush(); @flush();
  exit;
}

/* 🔥 WAJIB: LEPAS LOCK SESSION */
session_write_close();

/* ===============================
   FLAG FILE
================================ */
$flagFile = __DIR__ . '/absensi_realtime.flag';

if (!file_exists($flagFile)) {
  @file_put_contents($flagFile, (string)time());
}

/* ===============================
   RUNTIME SETTING
================================ */
@set_time_limit(0);
@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', 'off');
@ini_set('implicit_flush', 1);
while (ob_get_level() > 0) ob_end_flush();

/* ===============================
   LOOP SSE
================================ */
$start      = time();
$maxSeconds = 55; // reconnect sehat
$lastMTime  = (int)($_GET['since'] ?? 0);
if ($lastMTime <= 0) $lastMTime = (int)@filemtime($flagFile);

/* hello event */
echo "event: hello\n";
echo "data: " . json_encode(['since' => $lastMTime, 'ts' => time()]) . "\n\n";
@flush();

while (true) {

  if (connection_aborted()) break;

  clearstatcache(false, $flagFile);
  $mtime = (int)@filemtime($flagFile);

  if ($mtime > $lastMTime) {
    $lastMTime = $mtime;

    echo "event: update\n";
    echo "data: " . json_encode([
      'mtime' => $lastMTime,
      'ts'    => time()
    ]) . "\n\n";
    @flush();

  } else {

    // keep alive
    echo "event: ping\n";
    echo "data: " . json_encode(['ts' => time()]) . "\n\n";
    @flush();
  }

  if ((time() - $start) >= $maxSeconds) break;

  sleep(1);
}
