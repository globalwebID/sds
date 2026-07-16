<?php
// device_ping.php (FINAL, scalable, ketat + throttle)
// - UPDATE-only ke app_devices
// - Validasi device lewat app_device_allowlist
// - Optional token header X-PING-TOKEN
// - Throttle ping per device (min 5 detik) untuk mencegah 503 akibat spam

include_once '../sw-library/sw-config.php';
include_once '../sw-library/sw-function.php';

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');

// jangan tahan session (hindari session lock bikin request lain ngantri)
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

// hard timeout biar proses tidak nyangkut
@ini_set('max_execution_time', '3');
@set_time_limit(3);
ignore_user_abort(false);

/**
 * =========================================================
 * TUJUAN:
 * - Monitoring device ke tabel app_devices (UPDATE-only)
 * - Otorisasi DID ambil dari app_device_allowlist (tanpa hardcode)
 * - Optional: token header X-PING-TOKEN untuk menghindari spam ping
 * - Throttle 5 detik per device (mengurangi proses numpuk/503)
 * =========================================================
 *
 * REQUIRE:
 * - Tabel app_device_allowlist: did, token, is_active, label
 * - Tabel app_devices sudah punya row untuk tiap device_id (karena UPDATE-only)
 */

// =========================================================
// 1) Optional token header global (anti spam ping)
// =========================================================
$REQUIRE_PING_TOKEN = true; // set false kalau tidak mau pakai token header
$PING_TOKEN = 'c99a133ae06d17a6d4d0b80cb6ddb048';

if ($REQUIRE_PING_TOKEN) {
  $token = (string)($_SERVER['HTTP_X_PING_TOKEN'] ?? '');
  if ($PING_TOKEN !== '' && !hash_equals($PING_TOKEN, $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Forbidden']);
    exit;
  }
}

// =========================================================
// 2) Helper IP (aman)
// =========================================================
function getClientIp(): string {
  return (string)($_SERVER['REMOTE_ADDR'] ?? '-');
}

// =========================================================
// 3) Input
// =========================================================
$device_id = strtoupper(trim((string)($_POST['device_id'] ?? '')));
$label     = trim((string)($_POST['label'] ?? ''));
$page      = trim((string)($_POST['page'] ?? ''));

if ($device_id === '' || strlen($device_id) > 64) {
  http_response_code(400);
  echo json_encode(['ok' => false, 'message' => 'device_id invalid']);
  exit;
}

if (strlen($label) > 100) $label = substr($label, 0, 100);
if (strlen($page) > 255)  $page  = substr($page, 0, 255);

// =========================================================
// 3b) Throttle: minimal 5 detik per device (file cache sederhana)
// NOTE: diletakkan setelah validasi device_id & sebelum query DB.
// =========================================================
$thDir = __DIR__ . '/sw-jobs/absen';
if (!is_dir($thDir)) { @mkdir($thDir, 0755, true); }

$safeDid = preg_replace('/[^A-Z0-9_\-]/', '_', $device_id);
$thFile  = $thDir . '/ping_' . $safeDid . '.ts';

$lastTs = is_file($thFile) ? (int)@file_get_contents($thFile) : 0;
$nowTs  = time();

if ($lastTs > 0 && ($nowTs - $lastTs) < 5) {
  // balas cepat tanpa sentuh DB
  echo json_encode([
    'ok'        => true,
    'did'       => $device_id,
    'throttled' => true,
    'time'      => date('Y-m-d H:i:s')
  ]);
  exit;
}

@file_put_contents($thFile, (string)$nowTs, LOCK_EX);

// =========================================================
// 4) Validasi DID dari allowlist (tanpa hardcode)
// =========================================================
$stmt = $connection->prepare("
  SELECT did, label AS label_db, is_active
  FROM app_device_allowlist
  WHERE did=?
  LIMIT 1
");
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Prepare failed (allowlist)']);
  exit;
}

$stmt->bind_param('s', $device_id);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

if (!$res || $res->num_rows <= 0) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'message' => 'Device tidak terdaftar di allowlist']);
  exit;
}

$allow = $res->fetch_assoc();
if ((int)($allow['is_active'] ?? 0) !== 1) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'message' => 'Device non-aktif']);
  exit;
}

// kalau label POST kosong, fallback ke label DB (biar konsisten)
if ($label === '') $label = (string)($allow['label_db'] ?? '');

// =========================================================
// 5) UPDATE ONLY ke app_devices (ketat)
// =========================================================
$ip  = getClientIp();
$ua  = substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
$now = date('Y-m-d H:i:s');

$sql = "
UPDATE app_devices
SET
  label     = IF(? <> '' , ?, label),
  ip        = ?,
  ua        = ?,
  last_seen = ?,
  last_page = ?,
  hits      = hits + 1
WHERE device_id = ?
";

$stmt = $connection->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Prepare failed (update)']);
  exit;
}

$stmt->bind_param('sssssss', $label, $label, $ip, $ua, $now, $page, $device_id);
$ok = $stmt->execute();
$affected = (int)$stmt->affected_rows;
$stmt->close();

if (!$ok) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'message' => 'Execute failed']);
  exit;
}

// kalau belum ada record di app_devices -> tolak (tetap ketat)
if ($affected <= 0) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'message' => 'Device belum terdaftar di app_devices']);
  exit;
}

echo json_encode([
  'ok'   => true,
  'did'  => $device_id,
  'ip'   => $ip,
  'time' => $now
]);