<?php
// slider_feed.php (CACHE + ETAG + TANPA BASE64)
// Output: JSON { ok, version, html }
// - Cache file 3 detik (anti spam request)
// - ETag (kalau version sama -> 304)
// - Gambar pakai URL file, bukan base64 (jauh lebih ringan)

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');
date_default_timezone_set('Asia/Jakarta');

// jangan tahan session
if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

// hard timeout
@ini_set('max_execution_time', '3');
@set_time_limit(3);
ignore_user_abort(false);

require_once '../sw-library/sw-config.php';

$cacheDir  = __DIR__ . '/sw-jobs/absen';
$cacheFile = $cacheDir . '/slider_feed.cache.json';
$ttl       = 3; // detik

if (!is_dir($cacheDir)) { @mkdir($cacheDir, 0755, true); }

// Serve cache kalau masih fresh
if (is_file($cacheFile) && (time() - (int)@filemtime($cacheFile) <= $ttl)) {
  $cached = @file_get_contents($cacheFile);
  if ($cached !== false && $cached !== '') {
    $arr = json_decode($cached, true);
    if (is_array($arr)) {
      $etag = '"' . ($arr['version'] ?? '') . '"';
      $ifNoneMatch = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
      if ($ifNoneMatch !== '' && $etag !== '""' && $ifNoneMatch === $etag) {
        http_response_code(304);
        exit;
      }
      header('ETag: ' . $etag);
      echo $cached;
      exit;
    }
  }
}

try {
  $sql = "SELECT slider_id, slider_nama, foto
          FROM slider
          WHERE active='Y'
          ORDER BY slider_id DESC";
  $res = $connection->query($sql);

  $rows = [];
  if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
  }

  // Version hash dari isi slider aktif
  $raw = '';
  foreach ($rows as $r) {
    $raw .= ($r['slider_id'] ?? '').'|'.($r['slider_nama'] ?? '').'|'.($r['foto'] ?? '').'__';
  }
  $version = md5($raw);

  // ETag
  $etag = '"' . $version . '"';
  $ifNoneMatch = trim((string)($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
  if ($ifNoneMatch !== '' && $ifNoneMatch === $etag) {
    http_response_code(304);
    exit;
  }
  header('ETag: ' . $etag);

  // Build HTML carousel-inner (tanpa base64)
  $html = '';
  $i = 0;

  foreach ($rows as $r) {
    $i++;
    $active = ($i === 1) ? ' active' : '';
    $nama   = htmlspecialchars(strip_tags((string)($r['slider_nama'] ?? '')), ENT_QUOTES, 'UTF-8');
    $foto   = (string)($r['foto'] ?? '');

    $src = '../template/img/sw-big.jpg';

    if ($foto !== '') {
      $diskPath = __DIR__ . '/../sw-content/slider/' . $foto;
      if (is_file($diskPath)) {
        // URL ke file (lebih ringan daripada base64)
        $src = '../sw-content/slider/' . rawurlencode($foto);
      }
    }

    $html .= '<div class="carousel-item'.$active.'"><img src="'.$src.'" alt="'.$nama.'" class="d-block w-100"></div>';
  }

  $payload = [
    'ok'      => true,
    'version' => $version,
    'html'    => $html
  ];

  $json = json_encode($payload);
  @file_put_contents($cacheFile, $json, LOCK_EX);

  echo $json;

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode([
    'ok' => false,
    'message' => 'Error: '.$e->getMessage()
  ]);
}