<?php
// realtime-absensi.php
header('Content-Type: text/event-stream; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Connection: keep-alive');

// Nginx: matikan buffering supaya SSE tidak ketahan
header('X-Accel-Buffering: no');

@ini_set('zlib.output_compression', '0');
@ini_set('output_buffering', '0');
@ini_set('implicit_flush', '1');

set_time_limit(0);

// Pastikan tidak ada output buffering aktif
while (ob_get_level() > 0) { @ob_end_flush(); }
@ob_implicit_flush(true);

$flag = __DIR__ . '/realtime.flag';
$last = file_exists($flag) ? (int)@filemtime($flag) : 0;

// Konfigurasi
$interval   = 1;   // cek flag tiap 1 detik (boleh 1-2 detik)
$pingEvery  = 10;  // ping tiap 10 detik
$minGapMs   = 350; // minimal jarak antar "update" terkirim (anti spam)
$lastSentAt = 0;   // microtime ms
$tick = 0;

function sse_send(string $event, string $data): void {
  echo "event: {$event}\n";
  echo "data: {$data}\n\n";
  @flush();
}

sse_send('ping', 'start');

while (true) {
  if (connection_aborted()) break;

  clearstatcache(true, $flag);

  // 1) Kirim update kalau flag berubah
  if (file_exists($flag)) {
    $now = (int)@filemtime($flag);
    if ($now > $last) {
      $ms = (int)floor(microtime(true) * 1000);

      // COALESCE: kalau update datang rapat, tahan sedikit supaya tidak spam client
      if (($ms - $lastSentAt) >= $minGapMs) {
        sse_send('update', 'absen');
        $lastSentAt = $ms;
      } else {
        // tetap ingat ada perubahan, tapi tidak spam
        // next loop akan kirim ketika sudah lewat minGapMs
      }

      $last = $now;
    }
  }

  // 2) Ping berkala supaya koneksi tetap hidup
  $tick++;
  if ($tick >= $pingEvery) {
    sse_send('ping', 'ok');
    $tick = 0;
  }

  sleep($interval);
}
