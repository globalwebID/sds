<?php
// absen_worker.php (CLI/cron) - SUPER FAST & SAFE UPDATE
require_once __DIR__ . '/../sw-library/sw-config.php';
require_once __DIR__ . '/../sw-library/sw-function.php';

date_default_timezone_set('Asia/Jakarta');

$jobDir = __DIR__ . '/sw-jobs/absen';
if (!is_dir($jobDir)) { @mkdir($jobDir, 0755, true); }

$cronLog   = $jobDir . '/worker_cron.log';
$workerLog = $jobDir . '/worker.log';

function wlog(string $file, string $msg): void {
  @file_put_contents($file, date('c') . ' ' . $msg . "\n", FILE_APPEND);
}

wlog($cronLog, "tick");

$LOCK_TTL_SECONDS = 15 * 60;

function makeThumbFromJpeg(string $jpgPath, int $newWidth = 350) {
  $src = @imagecreatefromjpeg($jpgPath);
  if (!$src) return null;

  $w = imagesx($src);
  $h = imagesy($src);
  $newHeight = (int)(($h / max(1, $w)) * $newWidth);

  $thumb = @imagecreatetruecolor($newWidth, $newHeight);
  if (!$thumb) { @imagedestroy($src); return null; }

  @imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newWidth, $newHeight, $w, $h);
  @imagedestroy($src);
  return $thumb;
}

function drawWatermark($im, string $text): void {
  $text = trim($text);
  if ($text === '') return;

  $lines = preg_split("/\r\n|\n|\r/", $text);
  if (!$lines) $lines = [$text];

  $font = 3; // built-in font
  $lineH = imagefontheight($font) + 2;

  $maxW = 0;
  foreach ($lines as $ln) {
    $w = imagefontwidth($font) * strlen($ln);
    if ($w > $maxW) $maxW = $w;
  }

  $pad = 6;
  $boxW = $maxW + $pad * 2;
  $boxH = (count($lines) * $lineH) + $pad * 2;

  $imgH = imagesy($im);

  $x = 6;
  $y = max(6, $imgH - $boxH - 6);

  imagealphablending($im, true);
  $bg = imagecolorallocatealpha($im, 0, 0, 0, 70);
  imagefilledrectangle($im, $x, $y, $x + $boxW, $y + $boxH, $bg);

  $fg = imagecolorallocate($im, 255, 255, 255);

  $ty = $y + $pad;
  foreach ($lines as $ln) {
    imagestring($im, $font, $x + $pad, $ty, $ln, $fg);
    $ty += $lineH;
  }
}

function safeStr($v, int $maxLen = 255): string {
  $s = trim((string)$v);
  if ($maxLen > 0 && strlen($s) > $maxLen) $s = substr($s, 0, $maxLen);
  return $s;
}

$files = glob($jobDir . '/*.json');
if (!$files) exit;

foreach ($files as $f) {
  $lock = $f . '.lock';

  // lock ttl
  if (file_exists($lock)) {
    $age = time() - (int)@filemtime($lock);
    if ($age > $LOCK_TTL_SECONDS) {
      @unlink($lock);
      wlog($workerLog, "STALE_LOCK_REMOVED file=" . basename($f));
    } else {
      continue;
    }
  }

  if (@file_put_contents($lock, (string)time()) === false) {
    wlog($workerLog, "LOCK_FAIL file=" . basename($f));
    continue;
  }

  $raw = @file_get_contents($f);
  $job = $raw ? json_decode($raw, true) : null;

  if (!is_array($job)) {
    wlog($workerLog, "BAD_JSON file=" . basename($f));
    @unlink($f);
    @unlink($lock);
    continue;
  }

  // job fields
  $mode     = safeStr($job['mode'] ?? '', 10); // in/out
  $absen_id = (int)($job['absen_id'] ?? 0);

  $tmpJpg    = safeStr($job['tmp_jpg'] ?? '', 500);
  $tmpName   = safeStr($job['tmp_name'] ?? '', 255);       // penting untuk fallback update
  $finalName = safeStr($job['final_name'] ?? '', 255);
  $outJpg    = safeStr($job['out_jpg'] ?? '', 500);
  $water     = (string)($job['watermark'] ?? '');

  $user_id   = safeStr($job['user_id'] ?? '', 64);         // fallback
  $tanggal   = safeStr($job['tanggal'] ?? '', 32);         // fallback

  wlog($workerLog, "PROCESS file=" . basename($f) . " mode=$mode absen_id=$absen_id tmp=" . basename($tmpJpg) . " out=" . basename($outJpg));

  $okFinal = false;

  // build final jpg
  if ($tmpJpg !== '' && file_exists($tmpJpg) && $outJpg !== '') {
    $outDir = dirname($outJpg);
    if (!is_dir($outDir)) { @mkdir($outDir, 0755, true); }

    $thumb = makeThumbFromJpeg($tmpJpg, 350);
    if ($thumb) {
      drawWatermark($thumb, $water);

      $ok = @imagejpeg($thumb, $outJpg, 75);
      @imagedestroy($thumb);

      if ($ok && file_exists($outJpg) && filesize($outJpg) > 0) {
        $okFinal = true;
        wlog($workerLog, "JPG_OK out=" . basename($outJpg) . " size=" . filesize($outJpg));
      } else {
        wlog($workerLog, "JPG_FAIL out=" . basename($outJpg));
      }
    } else {
      wlog($workerLog, "THUMB_FAIL tmp=" . basename($tmpJpg));
    }
  } else {
    wlog($workerLog, "MISSING_FILE tmp_exists=" . (file_exists($tmpJpg) ? '1' : '0'));
  }

  // UPDATE DB: primary = by absen_id, fallback = by (tanggal,user_id,tmpName)
  if ($okFinal && $finalName !== '' && ($mode === 'in' || $mode === 'out')) {
    $updated = 0;

    // 1) update by absen_id (paling akurat)
    if ($absen_id > 0) {
      if ($mode === 'in') {
        $stmt = $connection->prepare("UPDATE absen SET foto_in=? WHERE absen_id=? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param('si', $finalName, $absen_id);
          $ok = $stmt->execute();
          $updated = $stmt->affected_rows;
          $stmt->close();
          wlog($workerLog, "UPDATE_BY_ID(in) ok=" . ($ok ? '1':'0') . " affected=$updated errno={$connection->errno} err={$connection->error}");
        } else {
          wlog($workerLog, "PREP_FAIL UPDATE_BY_ID(in) errno={$connection->errno} err={$connection->error}");
        }
      } else {
        $stmt = $connection->prepare("UPDATE absen SET foto_out=? WHERE absen_id=? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param('si', $finalName, $absen_id);
          $ok = $stmt->execute();
          $updated = $stmt->affected_rows;
          $stmt->close();
          wlog($workerLog, "UPDATE_BY_ID(out) ok=" . ($ok ? '1':'0') . " affected=$updated errno={$connection->errno} err={$connection->error}");
        } else {
          wlog($workerLog, "PREP_FAIL UPDATE_BY_ID(out) errno={$connection->errno} err={$connection->error}");
        }
      }
    }

    // 2) fallback (kalau absen_id kosong / atau affected=0)
    if ($updated <= 0 && $tanggal !== '' && $user_id !== '' && $tmpName !== '') {
      if ($mode === 'in') {
        $stmt = $connection->prepare("UPDATE absen SET foto_in=? WHERE tanggal=? AND user_id=? AND foto_in=? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param('ssss', $finalName, $tanggal, $user_id, $tmpName);
          $ok = $stmt->execute();
          $updated = $stmt->affected_rows;
          $stmt->close();
          wlog($workerLog, "UPDATE_FALLBACK(in) ok=" . ($ok ? '1':'0') . " affected=$updated errno={$connection->errno} err={$connection->error}");
        } else {
          wlog($workerLog, "PREP_FAIL UPDATE_FALLBACK(in) errno={$connection->errno} err={$connection->error}");
        }
      } else {
        $stmt = $connection->prepare("UPDATE absen SET foto_out=? WHERE tanggal=? AND user_id=? AND foto_out=? LIMIT 1");
        if ($stmt) {
          $stmt->bind_param('ssss', $finalName, $tanggal, $user_id, $tmpName);
          $ok = $stmt->execute();
          $updated = $stmt->affected_rows;
          $stmt->close();
          wlog($workerLog, "UPDATE_FALLBACK(out) ok=" . ($ok ? '1':'0') . " affected=$updated errno={$connection->errno} err={$connection->error}");
        } else {
          wlog($workerLog, "PREP_FAIL UPDATE_FALLBACK(out) errno={$connection->errno} err={$connection->error}");
        }
      }
    }

    if ($updated <= 0) {
      wlog($workerLog, "WARN_NOT_UPDATED mode=$mode absen_id=$absen_id tanggal=$tanggal user_id=$user_id tmpName=$tmpName finalName=$finalName");
    }
  }

  // cleanup tmp (hanya kalau final sukses)
  if ($okFinal && $tmpJpg !== '' && file_exists($tmpJpg)) {
    @unlink($tmpJpg);
  }

  // remove job
  @unlink($f);
  @unlink($lock);
}