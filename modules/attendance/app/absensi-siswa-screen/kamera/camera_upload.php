<?php
include_once '../../sw-library/sw-config.php';
include_once '../../sw-library/sw-function.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

@mysqli_set_charset($connection, 'utf8mb4');

function jexit(bool $ok, string $msg, array $extra = []): void {
  echo json_encode(array_merge(['ok' => $ok, 'message' => $msg], $extra));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  jexit(false, 'Gunakan POST multipart/form-data: device_id, token, snapshot(optional)');
}

$device_id = trim((string)($_POST['device_id'] ?? ''));
$token     = trim((string)($_POST['token'] ?? ''));

if ($device_id === '' || $token === '') {
  jexit(false, 'device_id/token wajib');
}

// Validasi kamera + token
$stmt = $connection->prepare("SELECT id FROM cameras WHERE device_id=? AND token=? LIMIT 1");
if (!$stmt) jexit(false, 'DB prepare error');
$stmt->bind_param("ss", $device_id, $token);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) jexit(false, 'Unauthorized (device_id/token salah)');

$cam_id = (int)$row['id'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

$uploadDir = __DIR__ . '/../../sw-content/camera/';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0755, true);
}

$filename = null;

// Terima snapshot (field: snapshot)
if (!empty($_FILES['snapshot']['tmp_name'])) {
  $tmp  = $_FILES['snapshot']['tmp_name'];
  $size = (int)($_FILES['snapshot']['size'] ?? 0);

  if ($size <= 0 || $size > 2 * 1024 * 1024) {
    jexit(false, 'Ukuran snapshot tidak valid (max 2MB)');
  }

  $finfo = finfo_open(FILEINFO_MIME_TYPE);
  $mime = $finfo ? finfo_file($finfo, $tmp) : '';
  if ($finfo) finfo_close($finfo);

  if (!in_array($mime, ['image/jpeg','image/jpg','image/png'], true)) {
    jexit(false, 'Format snapshot harus JPG/PNG');
  }

  $ext = ($mime === 'image/png') ? 'png' : 'jpg';
  $filename = 'cam_' . $cam_id . '_' . date('Ymd_His') . '.' . $ext;
  $dest = $uploadDir . $filename;

  if (!@move_uploaded_file($tmp, $dest)) {
    jexit(false, 'Gagal simpan snapshot');
  }

  // Hemat storage: simpan max 20 snapshot terbaru per kamera
  $pattern = $uploadDir . 'cam_' . $cam_id . '_*.{jpg,png}';
  $files = glob($pattern, GLOB_BRACE);
  if ($files && count($files) > 20) {
    usort($files, fn($a,$b)=> filemtime($b) <=> filemtime($a));
    $toDel = array_slice($files, 20);
    foreach ($toDel as $f) @unlink($f);
  }
}

// Update heartbeat + path gambar (ABSOLUT dari root)
if ($filename) {
  $rel = '/sw-content/camera/' . $filename; // ✅ penting: path absolut
  $stmt = $connection->prepare("UPDATE cameras SET last_seen=NOW(), last_ip=?, last_image=? WHERE id=?");
  if (!$stmt) jexit(false, 'DB prepare update error');
  $stmt->bind_param("ssi", $ip, $rel, $cam_id);
} else {
  $stmt = $connection->prepare("UPDATE cameras SET last_seen=NOW(), last_ip=? WHERE id=?");
  if (!$stmt) jexit(false, 'DB prepare update error');
  $stmt->bind_param("si", $ip, $cam_id);
}

$ok = $stmt->execute();
$stmt->close();

if (!$ok) jexit(false, 'Gagal update last_seen');

jexit(true, 'OK', ['device_id' => $device_id]);
