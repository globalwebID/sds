<?php
// slider_feed.php
// Output: JSON { ok, version, html }
// NOTE: versi = hash dari data slider aktif (id|nama|foto) agar akurat, tanpa perlu kolom updated_at

header('Content-Type: application/json; charset=utf-8');

require_once '../sw-library/sw-config.php'; // sesuaikan bila path berbeda

try {
  // Ambil slider aktif
  $sql = "SELECT slider_id, slider_nama, foto
          FROM slider
          WHERE active='Y'
          ORDER BY slider_id DESC";
  $res = $connection->query($sql);

  $rows = [];
  if ($res) {
    while ($r = $res->fetch_assoc()) $rows[] = $r;
  }

  // Buat version hash dari isi slider aktif
  $raw = '';
  foreach ($rows as $r) {
    $raw .= ($r['slider_id'] ?? '').'|'.($r['slider_nama'] ?? '').'|'.($r['foto'] ?? '').'__';
  }
  $version = md5($raw);

  // Build HTML carousel-inner (ikuti logika render kamu)
  $html = '';
  $i = 0;
  foreach ($rows as $r) {
    $i++;
    $active = ($i === 1) ? ' active' : '';
    $nama = strip_tags((string)($r['slider_nama'] ?? ''));

    $foto = (string)($r['foto'] ?? '');
    $imgTag = '';

    // mengikuti logika asli kamu (tidak diubah)
    if (file_exists('../../sw-content/slider/'.$foto)) {
      $imgTag = '<img src="../template/img/sw-big.jpg" alt="'.$nama.'" class="d-block w-100">';
    } else {
      if ($foto === '') {
        $imgTag = '<img src="../template/img/sw-big.jpg" alt="'.$nama.'" class="d-block w-100">';
      } else {
        $path = '../sw-content/slider/'.$foto;
        if (is_file($path)) {
          $b64 = base64_encode(file_get_contents($path));
          $imgTag = '<img src="data:image/png;base64,'.$b64.'" alt="'.$nama.'" class="d-block w-100">';
        } else {
          $imgTag = '<img src="../template/img/sw-big.jpg" alt="'.$nama.'" class="d-block w-100">';
        }
      }
    }

    $html .= '<div class="carousel-item'.$active.'">'.$imgTag.'</div>';
  }

  echo json_encode([
    'ok' => true,
    'version' => $version,
    'html' => $html
  ]);
} catch (Throwable $e) {
  echo json_encode([
    'ok' => false,
    'message' => 'Error: '.$e->getMessage()
  ]);
}
