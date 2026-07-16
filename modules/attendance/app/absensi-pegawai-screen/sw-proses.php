<?php
declare(strict_types=1);

require_once '../sw-library/sw-config.php';
require_once '../sw-library/sw-function.php';

/**
 * SW-PROSES SCREEN PEGAWAI (REFRACTOR FULL - SIAP TEMPEL)
 *
 * Actions:
 * - ?action=absen          : selfie (scanner qrcode/rfid input + upload img)
 * - ?action=absen-webcame  : scan qrcode via webcam
 * - ?action=data-absensi   : list absen hari ini
 * - ?action=data-counter   : counter dashboard
 */

// Timezone (fallback Asia/Jakarta)
if (!empty($row_site['timezone'])) {
  @date_default_timezone_set((string)$row_site['timezone']);
} else {
  @date_default_timezone_set('Asia/Jakarta');
}

// Folder upload foto
$uploadDir = __DIR__ . '/../sw-content/absen/';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0755, true);
}

function e(string $s): string {
  return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function respond_success(string $msg): void {
  echo "success/" . $msg;
  exit;
}

function respond_error(string $msg): void {
  echo $msg;
  exit;
}

function today_ymd(): string { return date('Y-m-d'); }
function now_his(): string { return date('H:i:s'); }

/**
 * Normalisasi jam dari DB:
 * - "07.00" -> "07:00:00"
 * - "7:0"   -> "07:00:00"
 * - "07:00" -> "07:00:00"
 * - "7"     -> "07:00:00" (anggap jam saja)
 */
function normalize_time_his(string $raw): string {
  $s = trim($raw);
  if ($s === '') return '';

  // Ganti titik jadi titik dua
  $s = str_replace('.', ':', $s);

  // Buang karakter aneh selain angka dan :
  $s = preg_replace('/[^0-9:]/', '', $s) ?? '';

  // Jika cuma jam (mis "7" atau "07")
  if (preg_match('/^\d{1,2}$/', $s)) {
    $h = (int)$s;
    return sprintf('%02d:00:00', $h);
  }

  // Jika format H:MM atau HH:MM
  if (preg_match('/^(\d{1,2}):(\d{2})$/', $s, $m)) {
    $h = (int)$m[1];
    $i = (int)$m[2];
    if ($h < 0 || $h > 23 || $i < 0 || $i > 59) return '';
    return sprintf('%02d:%02d:00', $h, $i);
  }

  // Jika format H:M (mis 7:0)
  if (preg_match('/^(\d{1,2}):(\d{1})$/', $s, $m)) {
    $h = (int)$m[1];
    $i = (int)$m[2];
    if ($h < 0 || $h > 23 || $i < 0 || $i > 9) return '';
    return sprintf('%02d:%02d:00', $h, $i * 10);
  }

  // Jika format H:MM:SS atau HH:MM:SS
  if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $s, $m)) {
    $h = (int)$m[1];
    $i = (int)$m[2];
    $sec = (int)$m[3];
    if ($h < 0 || $h > 23 || $i < 0 || $i > 59 || $sec < 0 || $sec > 59) return '';
    return sprintf('%02d:%02d:%02d', $h, $i, $sec);
  }

  return '';
}

/** Timestamp pakai tanggal + jam */
function ts_on_date(string $date, string $his): int {
  $his = normalize_time_his($his);
  if ($his === '') return 0;

  $dt = DateTime::createFromFormat('Y-m-d H:i:s', $date . ' ' . $his);
  if (!$dt) return 0;
  return $dt->getTimestamp();
}

/** Ambil POST wajib */
function post_required(string $key, string $label, array &$errors): string {
  if (!isset($_POST[$key]) || trim((string)$_POST[$key]) === '') {
    $errors[] = "$label tidak boleh kosong";
    return '';
  }
  return trim((string)$_POST[$key]);
}

/** Buat thumbnail dari upload (jpeg/png/webp) */
function createThumbFromUpload(string $tmpPath, array &$errors) {
  if (!is_file($tmpPath)) {
    $errors[] = 'Foto tidak dapat diunggah!';
    return null;
  }

  $info = @getimagesize($tmpPath);
  if (!$info || empty($info['mime'])) {
    $errors[] = 'File gambar tidak valid!';
    return null;
  }

  $mime = $info['mime'];
  $src = false;

  if ($mime === 'image/jpeg') $src = @imagecreatefromjpeg($tmpPath);
  elseif ($mime === 'image/png') $src = @imagecreatefrompng($tmpPath);
  elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($tmpPath);

  if (!$src) {
    $errors[] = 'Gagal memproses gambar (format tidak didukung / GD error).';
    return null;
  }

  $sw = imagesx($src);
  $sh = imagesy($src);
  if ($sw <= 0 || $sh <= 0) {
    $errors[] = 'Ukuran gambar tidak valid!';
    return null;
  }

  $newW = 350;
  $newH = (int)round(($sh / $sw) * $newW);
  $thumb = imagecreatetruecolor($newW, $newH);
  if (!$thumb) {
    $errors[] = 'Gagal membuat canvas gambar!';
    return null;
  }

  imagecopyresampled($thumb, $src, 0, 0, 0, 0, $newW, $newH, $sw, $sh);
  return $thumb;
}

function mode_screen_pegawai(): string {
  global $row_site;
  $mode = $row_site['tipe_absen_layar_pegawai'] ?? 'qrcode';
  $allow = ['qrcode', 'rfid', 'qrcode-webcame'];
  return in_array($mode, $allow, true) ? $mode : 'qrcode';
}

function getPegawaiByCode(string $mode, string $code): ?array {
  global $connection;

  $code = trim($code);
  if ($code === '') return null;

  if ($mode === 'rfid') {
    $filter = "WHERE pegawai.rfid='" . e($code) . "' LIMIT 1";
  } else {
    // qrcode / qrcode-webcame
    $filter = "WHERE pegawai.qrcode='" . e($code) . "' LIMIT 1";
  }

  $sql = "SELECT pegawai.pegawai_id, pegawai.telp, pegawai.nama_lengkap, pegawai.jabatan, pegawai.lokasi, lokasi.*
          FROM pegawai
          LEFT JOIN lokasi ON pegawai.lokasi = lokasi.lokasi_id
          $filter";

  $res = $connection->query($sql);
  if ($res && $res->num_rows > 0) {
    return $res->fetch_assoc();
  }
  return null;
}

function getJamPegawaiOrNull(array $pegawai): ?array {
  global $connection, $hari_ini;

  // getJam(conn, hari, jabatan) ada di sw-function.php (sesuai kode Anda)
  $jabatan = (string)($pegawai['jabatan'] ?? '');
  $jam = getJam($connection, $hari_ini, $jabatan);
  if (!$jam) return null;

  // Normalisasi jamnya (supaya stabil)
  $jam['jam_masuk']  = normalize_time_his((string)($jam['jam_masuk'] ?? ''));
  $jam['jam_telat']  = normalize_time_his((string)($jam['jam_telat'] ?? ''));
  $jam['jam_pulang'] = normalize_time_his((string)($jam['jam_pulang'] ?? ''));

  if ($jam['jam_masuk'] === '' || $jam['jam_telat'] === '' || $jam['jam_pulang'] === '') {
    return null;
  }

  return $jam;
}

/** Buat pesan WA sesuai template (opsional) */
function buildWhatsappMessage(string $tipe, array $pegawai, array $jam, string $time_absen, string $status, string $latitude): string {
  global $row_site;

  $template = (string)($row_site['whatsapp_template'] ?? '');
  if ($template === '') return '';

  return str_replace(
    ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
    [
      (string)($pegawai['nama_lengkap'] ?? '-'),
      tanggal_ind(today_ymd()),
      $tipe,
      $jam['jam_masuk'] . ' - ' . $jam['jam_pulang'],
      $time_absen,
      $status,
      'https://www.google.com/maps/place/' . $latitude
    ],
    $template
  );
}

/** Kirim WA jika aktif */
function maybeSendWA(string $penerima, string $pesan): void {
  global $row_site, $whatsapp_tipe, $whatsapp_domain, $whatsapp_token, $secret_key, $whatsapp_sender;

  if (($row_site['whatsapp_active'] ?? 'N') !== 'Y') return;
  if ($penerima === '' || $pesan === '') return;

  if (($whatsapp_tipe ?? 'POST') === 'POST') {
    KirimWa($penerima, $pesan, $whatsapp_domain, $whatsapp_token, $secret_key);
  } else {
    $p = str_replace(["\r\n", "\n"], "%0A", $pesan);
    $p = str_replace(" ", "%20", $p);
    KirimWa($whatsapp_sender, $penerima, $p, $whatsapp_domain, $whatsapp_token);
  }
}

$action = $_GET['action'] ?? '';

switch ($action) {

  /** ABSEN SELFIE (scanner qrcode/rfid input + upload img) */
  case 'absen': {
    $errors = [];
    $qrcode   = post_required('qrcode', 'Qrcode', $errors);
    $latitude = post_required('latitude', 'Latitude', $errors);

    $thumb = null;
    if (empty($_FILES['img']['tmp_name'])) {
      $errors[] = 'Foto tidak dapat diunggah!';
    } else {
      $thumb = createThumbFromUpload($_FILES['img']['tmp_name'], $errors);
    }

    if ($errors) respond_error(implode("\n", $errors));

    $mode = mode_screen_pegawai();
    $pegawai = getPegawaiByCode($mode, $qrcode);
    if (!$pegawai) respond_error('Data pengguna tidak ditemukan!');

    $jam = getJamPegawaiOrNull($pegawai);
    if (!$jam) respond_error('Jam Abensi tidak ditemukan!');

    $date = today_ymd();
    $time_sekarang = now_his();
    $time_absen = $time_sekarang;

    $ts_now = ts_on_date($date, $time_sekarang);
    if (!$ts_now) respond_error('Waktu server tidak valid!');

    $ts_masuk  = ts_on_date($date, $jam['jam_masuk']);
    $ts_telat  = ts_on_date($date, $jam['jam_telat']);
    $ts_pulang = ts_on_date($date, $jam['jam_pulang']);
    if (!$ts_masuk || !$ts_telat || !$ts_pulang) {
      respond_error('Format jam kerja tidak valid. Cek jam di database.');
    }

    // Window:
    $ts_start_masuk  = $ts_masuk - 3600;            // 60 menit sebelum masuk
    $ts_start_pulang = $ts_pulang - 3600;           // 60 menit sebelum pulang
    $ts_end_pulang   = ts_on_date($date, '23:59:59'); // sampai akhir hari (aman)

    $status_in  = ($ts_now <= $ts_telat) ? 'Tepat Waktu' : 'Telat';
    $status_out = ($ts_now <  $ts_pulang) ? 'Pulang Cepat' : 'Tepat Waktu';

    $pegawai_id = (int)$pegawai['pegawai_id'];
    $penerima   = (string)($pegawai['telp'] ?? '');

    // Cek data absen hari ini
    $sqlCek = "SELECT absen_id, absen_in, absen_out
               FROM absen_pegawai
               WHERE tanggal='$date' AND pegawai_id='{$pegawai_id}'
               LIMIT 1";
    $resCek = $connection->query($sqlCek);
    $rowAbsen = ($resCek && $resCek->num_rows > 0) ? $resCek->fetch_assoc() : null;

    // === MASUK ===
    if (!$rowAbsen) {
      if ($ts_now < $ts_start_masuk) {
        respond_error('Waktu absen belum dimulai!');
      }
      if ($ts_now > $ts_end_pulang) {
        respond_error('Waktu absen tidak valid!');
      }

      $watermark = strip_tags((string)$pegawai['nama_lengkap']) . "\n" . $time_sekarang . " - " . tanggal_ind($date);
      $foto = 'masuk_' . $pegawai_id . '_' . $date . '_' . uniqid('', true) . '.png';
      $filename = $uploadDir . $foto;

      $lokasi_id = (string)($pegawai['lokasi_id'] ?? '0');

      $sqlIns = "INSERT INTO absen_pegawai (
                  pegawai_id, tanggal, lokasi_id,
                  jam_masuk, jam_toleransi, jam_pulang,
                  absen_in, foto_in, status_masuk, map_in,
                  kehadiran, radius
                ) VALUES (
                  '{$pegawai_id}', '$date', '" . e($lokasi_id) . "',
                  '" . e($jam['jam_masuk']) . "', '" . e($jam['jam_telat']) . "', '" . e($jam['jam_pulang']) . "',
                  '" . e($time_absen) . "', '" . e($foto) . "', '" . e($status_in) . "', '" . e($latitude) . "',
                  'Hadir', '0'
                )";

      if ($connection->query($sqlIns) === false) {
        respond_error('Sepertinya Sistem Kami sedang error!');
      }

      // simpan foto + watermark
      if ($thumb) {
        addTextWatermark($thumb, $watermark, $filename);
      }

      // WA
      $pesan = buildWhatsappMessage('MASUK', $pegawai, $jam, $time_absen, $status_in, $latitude);
      maybeSendWA($penerima, $pesan);

      respond_success("Terima kasih, {$pegawai['nama_lengkap']},\nAbsensi Masuk berhasil pada " . tanggal_ind($date) . " pukul $time_sekarang!");
    }

    // === PULANG ===
    $absen_out_val = (string)($rowAbsen['absen_out'] ?? '');
    $sudahPulang = ($absen_out_val !== '' && $absen_out_val !== '00:00:00');
    if ($sudahPulang) {
      respond_error('Anda sudah absen pulang hari ini!');
    }

    if ($ts_now < $ts_start_pulang) {
      respond_error('Waktu absen pulang belum dimulai!');
    }
    if ($ts_now > $ts_end_pulang) {
      respond_error('Waktu absen tidak valid!');
    }

    $absen_id = (int)$rowAbsen['absen_id'];

    $watermark = strip_tags((string)$pegawai['nama_lengkap']) . "\n" . $time_sekarang . " - " . tanggal_ind($date);
    $foto = 'pulang_' . $pegawai_id . '_' . $date . '_' . uniqid('', true) . '.png';
    $filename = $uploadDir . $foto;

    $sqlUp = "UPDATE absen_pegawai SET
                absen_out='" . e($time_absen) . "',
                foto_out='" . e($foto) . "',
                status_pulang='" . e($status_out) . "',
                map_out='" . e($latitude) . "',
                radius_out='0'
              WHERE tanggal='$date'
                AND pegawai_id='{$pegawai_id}'
                AND absen_id='{$absen_id}'";

    if ($connection->query($sqlUp) === false) {
      respond_error('Sepertinya Sistem Kami sedang error!');
    }

    if ($thumb) {
      addTextWatermark($thumb, $watermark, $filename);
    }

    $pesan = buildWhatsappMessage('PULANG', $pegawai, $jam, $time_absen, $status_out, $latitude);
    maybeSendWA($penerima, $pesan);

    respond_success("Terima kasih, {$pegawai['nama_lengkap']},\nAbsensi Pulang berhasil pada " . tanggal_ind($date) . " pukul $time_sekarang.");
  }

  /** ABSEN WEBCAM QR (tanpa selfie) */
  case 'absen-webcame': {
    $errors = [];
    $qrcode   = post_required('qrcode', 'Qrcode', $errors);
    $latitude = post_required('latitude', 'Latitude', $errors);
    if ($errors) respond_error(implode("\n", $errors));

    // webcam scan -> selalu qrcode
    $pegawai = getPegawaiByCode('qrcode', $qrcode);
    if (!$pegawai) respond_error('Data pengguna tidak ditemukan!');

    // FIX BUG: ambil jam kerja (sebelumnya kosong / undefined)
    $jam = getJamPegawaiOrNull($pegawai);
    if (!$jam) respond_error('Jam Abensi tidak ditemukan!');

    $date = today_ymd();
    $time_sekarang = now_his();
    $time_absen = $time_sekarang;

    $ts_now = ts_on_date($date, $time_sekarang);
    if (!$ts_now) respond_error('Waktu server tidak valid!');

    $ts_masuk  = ts_on_date($date, $jam['jam_masuk']);
    $ts_telat  = ts_on_date($date, $jam['jam_telat']);
    $ts_pulang = ts_on_date($date, $jam['jam_pulang']);
    if (!$ts_masuk || !$ts_telat || !$ts_pulang) {
      respond_error('Format jam kerja tidak valid. Cek jam di database.');
    }

    $ts_start_masuk  = $ts_masuk - 3600;
    $ts_start_pulang = $ts_pulang - 3600;
    $ts_end_pulang   = ts_on_date($date, '23:59:59');

    $status_in  = ($ts_now <= $ts_telat) ? 'Tepat Waktu' : 'Telat';
    $status_out = ($ts_now <  $ts_pulang) ? 'Pulang Cepat' : 'Tepat Waktu';

    $pegawai_id = (int)$pegawai['pegawai_id'];
    $penerima   = (string)($pegawai['telp'] ?? '');

    $sqlCek = "SELECT absen_id, absen_in, absen_out
               FROM absen_pegawai
               WHERE tanggal='$date' AND pegawai_id='{$pegawai_id}'
               LIMIT 1";
    $resCek = $connection->query($sqlCek);
    $rowAbsen = ($resCek && $resCek->num_rows > 0) ? $resCek->fetch_assoc() : null;

    // MASUK
    if (!$rowAbsen) {
      if ($ts_now < $ts_start_masuk) respond_error('Waktu absen belum dimulai!');
      if ($ts_now > $ts_end_pulang) respond_error('Waktu absen tidak valid!');

      $lokasi_id = (string)($pegawai['lokasi_id'] ?? '0');

      $sqlIns = "INSERT INTO absen_pegawai (
                  pegawai_id, tanggal, lokasi_id,
                  jam_masuk, jam_toleransi, jam_pulang,
                  absen_in, status_masuk, map_in,
                  kehadiran, radius
                ) VALUES (
                  '{$pegawai_id}', '$date', '" . e($lokasi_id) . "',
                  '" . e($jam['jam_masuk']) . "', '" . e($jam['jam_telat']) . "', '" . e($jam['jam_pulang']) . "',
                  '" . e($time_absen) . "', '" . e($status_in) . "', '" . e($latitude) . "',
                  'Hadir', '0'
                )";

      if ($connection->query($sqlIns) === false) {
        respond_error('Sepertinya Sistem Kami sedang error!');
      }

      $pesan = buildWhatsappMessage('MASUK', $pegawai, $jam, $time_absen, $status_in, $latitude);
      maybeSendWA($penerima, $pesan);

      respond_success("Terima kasih, {$pegawai['nama_lengkap']},\nAbsensi Masuk berhasil pada " . tanggal_ind($date) . " pukul $time_sekarang!");
    }

    // PULANG
    $absen_out_val = (string)($rowAbsen['absen_out'] ?? '');
    $sudahPulang = ($absen_out_val !== '' && $absen_out_val !== '00:00:00');
    if ($sudahPulang) respond_error('Anda sudah absen pulang hari ini!');

    if ($ts_now < $ts_start_pulang) respond_error('Waktu absen pulang belum dimulai!');
    if ($ts_now > $ts_end_pulang) respond_error('Waktu absen tidak valid!');

    $absen_id = (int)$rowAbsen['absen_id'];

    $sqlUp = "UPDATE absen_pegawai SET
                absen_out='" . e($time_absen) . "',
                status_pulang='" . e($status_out) . "',
                map_out='" . e($latitude) . "',
                radius_out='0'
              WHERE tanggal='$date'
                AND pegawai_id='{$pegawai_id}'
                AND absen_id='{$absen_id}'";

    if ($connection->query($sqlUp) === false) {
      respond_error('Sepertinya Sistem Kami sedang error!');
    }

    $pesan = buildWhatsappMessage('PULANG', $pegawai, $jam, $time_absen, $status_out, $latitude);
    maybeSendWA($penerima, $pesan);

    respond_success("Terima kasih, {$pegawai['nama_lengkap']},\nAbsensi Pulang berhasil pada " . tanggal_ind($date) . " pukul $time_sekarang.");
  }

  /** DATA ABSENSI (list card) */
  case 'data-absensi': {
    $date = today_ymd();

    $sql = "SELECT a.*, p.nama_lengkap, p.jabatan
            FROM absen_pegawai a
            LEFT JOIN pegawai p ON a.pegawai_id=p.pegawai_id
            WHERE a.tanggal='$date'
            ORDER BY a.absen_in DESC, a.absen_out DESC
            LIMIT 8";
    $res = $connection->query($sql);

    if ($res && $res->num_rows > 0) {
      while ($row = $res->fetch_assoc()) {
        $fotoIn = (string)($row['foto_in'] ?? '');
        $fotoOut = (string)($row['foto_out'] ?? '');

        $fotoInPath = __DIR__ . '/../sw-content/absen/' . $fotoIn;
        $fotoOutPath = __DIR__ . '/../sw-content/absen/' . $fotoOut;

        $imgIn = (is_file($fotoInPath) && $fotoIn !== '')
          ? '<img src="data:image/png;base64,' . base64_encode(file_get_contents($fotoInPath)) . '" height="40">'
          : '<img src="../sw-content/avatar/avatar.jpg" height="40">';

        $imgOut = (is_file($fotoOutPath) && $fotoOut !== '')
          ? '<img src="data:image/png;base64,' . base64_encode(file_get_contents($fotoOutPath)) . '" height="40">'
          : '<img src="../sw-content/avatar/avatar.jpg" height="40">';

        $absen_in = e((string)($row['absen_in'] ?? '-'));
        $absen_out_val = (string)($row['absen_out'] ?? '');
        $absen_out = (empty($absen_out_val) || $absen_out_val === '00:00:00') ? '-' : e($absen_out_val);

        echo '
        <div class="card border-1 mb-2" style="border:solid 1px #e3e3e3;">
          <div class="card-body pt-2">
            <div class="row align-items-center">
              <div class="col align-self-center">
                <p class="text-secondary p-0 m-0">' . e((string)($row['nama_lengkap'] ?? '-')) . '</p>
                <small class="badge badge-primary">' . e(ucfirst((string)($row['jabatan'] ?? '-'))) . '</small>
              </div>

              <div class="col-auto align-self-center">
                <figure class="avatar avatar-40 rounded mb-0">' . $imgIn . '</figure>
              </div>

              <div class="col-2 align-self-center">
                <small class="text-info">MASUK</small>
                <p class="text-secondary">' . $absen_in . '</p>
              </div>

              <div class="col-auto align-self-center">
                <figure class="avatar avatar-40 rounded mb-0">' . $imgOut . '</figure>
              </div>

              <div class="col-2 align-self-center">
                <small class="text-danger">PULANG</small>
                <p class="text-secondary">' . $absen_out . '</p>
              </div>
            </div>
          </div>
        </div>';
      }
      exit;
    }

    echo '<div class="alert alert-info text-center">Data absensi masih kosong!</div>';
    exit;
  }

  /** DATA COUNTER (JSON) */
  case 'data-counter': {
    $date = today_ymd();

    // 1 baris ringkasan (tanpa GROUP BY)
    $sql = "SELECT
              (SELECT COUNT(*) FROM pegawai WHERE active='Y') AS total_pegawai,
              SUM(CASE WHEN a.kehadiran='Hadir' THEN 1 ELSE 0 END) AS hadir_count,
              SUM(CASE WHEN a.kehadiran='Izin'  THEN 1 ELSE 0 END) AS izin_count,
              SUM(CASE WHEN a.kehadiran='Hadir' AND a.status_masuk='Tepat Waktu' THEN 1 ELSE 0 END) AS ontime_count,
              SUM(CASE WHEN a.kehadiran='Hadir' AND a.status_masuk='Telat'       THEN 1 ELSE 0 END) AS telat_count
            FROM absen_pegawai a
            WHERE a.tanggal='$date'";

    $res = $connection->query($sql);
    if (!$res) {
      echo json_encode(['error' => 'Query failed']);
      exit;
    }

    $row = $res->fetch_assoc() ?: [];
    $total = (int)($row['total_pegawai'] ?? 0);
    $hadir = (int)($row['hadir_count'] ?? 0);
    $izin  = (int)($row['izin_count'] ?? 0);
    $ontime= (int)($row['ontime_count'] ?? 0);
    $telat = (int)($row['telat_count'] ?? 0);

    $belum = max(0, $total - $hadir - $izin);
    $persen = ($total > 0) ? (int)round(($hadir / $total) * 100, 0) : 0;

    echo json_encode([
      'total_pegawai' => $total,
      'on_time'       => $ontime,
      'terlambat'     => $telat,
      'izin'          => $izin,
      'belum_absen'   => $belum,
      'total_absen'   => $hadir,
      'persentase'    => $persen
    ]);
    exit;
  }

  default:
    respond_error('Invalid action');
}