<?php
require '_config.php';
requireAuth();

/**
 * POLLING endpoint untuk absensi (pengganti SSE).
 *
 * Cara kerja:
 * - Client memanggil endpoint ini tiap 2-5 detik.
 * - Server membaca mtime flag: absensi_realtime.flag
 * - Jika mtime > last => changed=true (client reload data)
 *
 * Request:
 *   GET absensi_realtime.php?last=TIMESTAMP
 * Response:
 *   JSON { ok, changed, last, ts }
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

@mysqli_set_charset($conn, 'utf8mb4');

// ===============================
// VALIDASI SESSION
// ===============================
$id_siswa = (int)($_SESSION['id_siswa'] ?? 0);
if ($id_siswa <= 0) {
  http_response_code(401);
  echo json_encode(['ok' => false, 'message' => 'Session id_siswa tidak valid']);
  exit;
}

// WAJIB: lepas lock session biar request lain tidak ketahan
if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }

// ===============================
// FLAG FILE
// ===============================
$flagFile = __DIR__ . '/absensi_realtime.flag';
if (!file_exists($flagFile)) {
  @file_put_contents($flagFile, (string)time(), LOCK_EX);
}

$last = (int)($_GET['last'] ?? 0);

// baca mtime flag
$mtime = (int)@filemtime($flagFile);
if ($mtime <= 0) $mtime = time();

// changed kalau client sudah punya last dan flag lebih baru
$changed = ($last > 0 && $mtime > $last);

echo json_encode([
  'ok'      => true,
  'changed' => $changed,
  'last'    => $mtime,
  'ts'      => time(),
]);