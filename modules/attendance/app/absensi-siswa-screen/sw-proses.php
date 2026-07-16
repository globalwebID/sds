<?php
require_once '../sw-library/sw-config.php';
require_once '../sw-library/sw-function.php';

header('Content-Type: text/plain; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');

/**
 * =========================================================
 * sw-proses.php FINAL (JPG TMP -> Worker buat JPG FINAL + update DB)
 * =========================================================
 * - Insert/Update DB cepat, foto awal = JPG TMP (langsung tampil)
 * - Worker: buat JPG final (thumb+watermark) -> UPDATE DB foto_in/out = f_*.jpg -> hapus JPG tmp
 * - Kompat data lama PNG (jika masih ada)
 * - Idempotency: client_request_id_in/out UNIQUE
 * - KIOSK key via app_device_allowlist (did + k)
 */

// ---------------------------------------------------------
// (Opsional) Debug signature untuk memastikan file benar-benar dipakai
// set true hanya saat troubleshooting
// ---------------------------------------------------------
$DEBUG_SIGNATURE = false;
if ($DEBUG_SIGNATURE) {
  error_log("SW_PROSES_SIGNATURE=JPG_V2 file=" . __FILE__);
}

// -----------------------------
// Kiosk key URL
// -----------------------------
$KIOSK_REQUIRE_KEY = true; // set false jika belum mau kunci
$KIOSK_DID_PARAM   = 'did';
$KIOSK_KEY_PARAM   = 'k';
$KIOSK_LABEL_PARAM = 'label';

// -----------------------------
// PATH BASE (ABSOLUT) agar aman dipakai cron/CLI
// file ini berada di .../absensi-siswa-screen/sw-proses.php
// folder sw-content berada 1 level di atasnya (../sw-content)
// -----------------------------
$BASE_FS = realpath(__DIR__ . '/..');
if ($BASE_FS === false) { $BASE_FS = rtrim(__DIR__ . '/..', '/\\'); }

// -----------------------------
// Folder (FS ABSOLUT)
// -----------------------------
$uploadDirFs = $BASE_FS . '/sw-content/absen/';       // JPG final (+ kompat PNG lama)
$tmpDirFs    = $BASE_FS . '/sw-content/absen/tmp/';   // JPG tmp
$jobDirFs    = __DIR__ . '/sw-jobs/absen/';           // job dir

foreach ([$uploadDirFs, $tmpDirFs, $jobDirFs] as $d) {
  if (!is_dir($d)) { @mkdir($d, 0755, true); }
}

// -----------------------------
// Realtime flag (SSE)
// -----------------------------
function triggerRealtimeUpdate(): void {
  $flag = __DIR__ . '/realtime.flag';
  @file_put_contents($flag, (string)time(), LOCK_EX);
}

// -----------------------------
// Fast response
// -----------------------------
function finishRequestFast(): void {
  while (ob_get_level() > 0) { @ob_end_flush(); }
  @flush();
  if (function_exists('fastcgi_finish_request')) { @fastcgi_finish_request(); }
}

// -----------------------------
// enqueue job (atomic)
// -----------------------------
function enqueueJob(string $jobDir, array $payload): bool {
  if (!is_dir($jobDir)) { @mkdir($jobDir, 0755, true); }

  $name = 'job_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.json';
  $tmp  = rtrim($jobDir, '/\\') . '/' . $name . '.tmp';
  $dst  = rtrim($jobDir, '/\\') . '/' . $name;

  $json = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
  if (!is_string($json)) return false;

  if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
  return @rename($tmp, $dst);
}

// -----------------------------
// Validasi kiosk via app_device_allowlist
// -----------------------------
function requireValidKiosk(mysqli $connection, bool $enabled, string $didParam, string $keyParam, string $labelParam): array {
  if (!$enabled) return ['did'=>'', 'label'=>''];

  $did   = isset($_GET[$didParam]) ? trim((string)$_GET[$didParam]) : '';
  $key   = isset($_GET[$keyParam]) ? (string)$_GET[$keyParam] : '';
  $label = isset($_GET[$labelParam]) ? trim((string)$_GET[$labelParam]) : '';

  if ($did === '' || $key === '') {
    echo "error/Perangkat tidak valid (kiosk key diperlukan).";
    exit;
  }

  $stmt = $connection->prepare("
    SELECT did, label, token, is_active
    FROM app_device_allowlist
    WHERE did=? LIMIT 1
  ");
  if (!$stmt) { echo "error/Query error (prepare device)."; exit; }
  $stmt->bind_param('s', $did);
  $stmt->execute();
  $res = $stmt->get_result();
  $stmt->close();

  if (!$res || $res->num_rows <= 0) { echo "error/Perangkat tidak terdaftar."; exit; }
  $row = $res->fetch_assoc();

  if ((int)($row['is_active'] ?? 0) !== 1) { echo "error/Perangkat non-aktif."; exit; }

  $dbToken = (string)($row['token'] ?? '');
  if (!hash_equals($dbToken, $key)) { echo "error/Perangkat tidak terotorisasi."; exit; }

  $finalLabel = ($label !== '') ? $label : (string)($row['label'] ?? '');
  return ['did'=>$did, 'label'=>$finalLabel];
}

// -----------------------------
// Base URL publik
// -----------------------------
$SITE_URL = rtrim((string)($row_site['site_url'] ?? $row_site['site_domain'] ?? ''), '/');
if ($SITE_URL === '') {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? '';
  $SITE_URL = ($host !== '') ? ($scheme . '://' . $host) : '';
}

// -----------------------------
// Jam absensi
// -----------------------------
$data_jam = getJam($connection, $hari_ini, 'Siswa');

// -----------------------------
// upload -> tmp jpg : return tmpPath + tmpName
// -----------------------------
function saveUploadToTmp(string $tmpDirFs, string $prefix, string $user_id): array {
  if (empty($_FILES['img']['tmp_name'])) {
    return [false, '', '', 'Foto tidak dapat diunggah!'];
  }

  $file_tmp = (string)$_FILES['img']['tmp_name'];
  $info = @getimagesize($file_tmp);
  if (!$info || empty($info['mime']) || stripos((string)$info['mime'], 'jpeg') === false) {
    return [false, '', '', 'Format foto harus JPG/JPEG!'];
  }

  $tmpName = $prefix . '_' . $user_id . '_' . date('Y-m-d') . '_' . uniqid('', true) . '.jpg';
  $tmpPath = rtrim($tmpDirFs, '/\\') . '/' . $tmpName;

  if (!@move_uploaded_file($file_tmp, $tmpPath)) {
    if (!@copy($file_tmp, $tmpPath)) {
      return [false, '', '', 'Gagal menyimpan foto upload!'];
    }
  }

  return [true, $tmpPath, $tmpName, ''];
}

// -----------------------------
// Helper URL foto (JPG tmp / JPG final / PNG lama)
// -----------------------------
function _endsWith(string $haystack, string $needle): bool {
  $len = strlen($needle);
  if ($len === 0) return true;
  return substr($haystack, -$len) === $needle;
}

function fotoUrl(string $SITE_URL, string $file, string $cb): string {
  $file = trim($file);
  if ($file === '') return '../sw-content/avatar/avatar.jpg';

  $lower = strtolower($file);
  $isJpg = (_endsWith($lower, '.jpg') || _endsWith($lower, '.jpeg'));
  $isPng = _endsWith($lower, '.png');
  $isFinalJpg = $isJpg && (strpos($lower, 'f_') === 0);

  if ($isFinalJpg || $isPng) {
    $path = '/sw-content/absen/' . rawurlencode($file);
  } else if ($isJpg) {
    $path = '/sw-content/absen/tmp/' . rawurlencode($file);
  } else {
    $path = '/sw-content/absen/' . rawurlencode($file);
  }

  if ($SITE_URL !== '') return $SITE_URL . $path . '?v=' . $cb;
  return '../' . ltrim($path, '/') . '?v=' . $cb;
}

// -----------------------------
// Router
// -----------------------------
$action = (string)($_GET['action'] ?? '');

switch ($action) {

  // =========================================================
  // ABSEN (RFID / QR + FOTO)
  // =========================================================
  case 'absen': {
    $kiosk = requireValidKiosk($connection, $KIOSK_REQUIRE_KEY, $KIOSK_DID_PARAM, $KIOSK_KEY_PARAM, $KIOSK_LABEL_PARAM);

    $error = [];
    $qrcode   = isset($_POST['qrcode']) ? trim((string)$_POST['qrcode']) : '';
    $latitude = isset($_POST['latitude']) ? trim((string)$_POST['latitude']) : '';

    $client_request_id = isset($_POST['client_request_id']) ? trim((string)$_POST['client_request_id']) : '';
    if ($client_request_id !== '' && strlen($client_request_id) > 64) {
      $client_request_id = substr($client_request_id, 0, 64);
    }

    if ($qrcode === '')   $error[] = 'Qrcode tidak boleh kosong';
    if ($latitude === '') $error[] = 'Latitude tidak boleh kosong';

    if (!empty($error)) { echo 'error/' . implode("\n", $error); break; }
    if (!$data_jam) { echo 'error/Jam Absensi tidak ditemukan!'; break; }

    $tipe = (string)($row_site['tipe_absen_layar'] ?? '');
    $col  = ($tipe === 'rfid') ? 'rfid' : 'nisn';

    $stmt = $connection->prepare("
      SELECT user.user_id, user.telp, user.nama_lengkap, lokasi.*
      FROM user
      LEFT JOIN lokasi ON user.lokasi = lokasi.lokasi_id
      WHERE user.$col = ?
      LIMIT 1
    ");
    if (!$stmt) { echo 'error/Query error (prepare user).'; break; }
    $stmt->bind_param('s', $qrcode);
    $stmt->execute();
    $result_user = $stmt->get_result();
    $stmt->close();

    if (!$result_user || $result_user->num_rows <= 0) { echo 'error/Data pengguna tidak ditemukan!'; break; }

    $data_user = $result_user->fetch_assoc();
    $penerima  = (string)($data_user['telp'] ?? '');
    $user_id   = (string)($data_user['user_id'] ?? '');

    $status_in  = ($time_sekarang <= $data_jam['jam_telat']) ? 'Tepat Waktu' : 'Terlambat';
    $status_out = ($time_sekarang <  $data_jam['jam_pulang']) ? 'Pulang Cepat' : 'Tepat Waktu';

    $absen_masuk  = date('H:i:s', strtotime($data_jam['jam_masuk'] . ' - 60 minute'));
    $absen_pulang = $data_jam['jam_pulang'];

    if ($time_sekarang < $absen_masuk) { echo 'error/Waktu absen masuk belum dimulai!'; break; }

    // -------------------------------------------------------
    // ABSEN MASUK
    // -------------------------------------------------------
    if ($time_sekarang >= $absen_masuk && $time_sekarang < $absen_pulang) {

      // idempotency
      if ($client_request_id !== '') {
        $chk = $connection->prepare("SELECT absen_id FROM absen WHERE client_request_id_in = ? LIMIT 1");
        if ($chk) {
          $chk->bind_param('s', $client_request_id);
          $chk->execute();
          $r = $chk->get_result();
          $chk->close();
          if ($r && $r->num_rows > 0) {
            triggerRealtimeUpdate();
            echo 'success/Absensi sudah tercatat.';
            break;
          }
        }
      }

      // sudah absen masuk?
      $stmt = $connection->prepare("SELECT absen_id FROM absen WHERE tanggal=? AND user_id=? LIMIT 1");
      $stmt->bind_param('ss', $date, $user_id);
      $stmt->execute();
      $resAbs = $stmt->get_result();
      $stmt->close();

      if ($resAbs && $resAbs->num_rows > 0) {
        echo 'error/Sekarang Belum Waktunya Absen Pulang! Absen PULANG bisa mulai pukul ' . $absen_pulang . '.';
        break;
      }

      // simpan upload JPG tmp
      [$okUp, $tmpPathFs, $tmpName, $errUp] = saveUploadToTmp($tmpDirFs, 'in', $user_id);
      if (!$okUp) { echo 'error/' . $errUp; break; }

      // FINAL JPG (dibuat worker)
      $finalJpg   = 'f_in_' . $user_id . '_' . date('Y-m-d') . '_' . uniqid('', true) . '.jpg';
      $outJpgFs   = rtrim($uploadDirFs, '/\\') . '/' . $finalJpg;
      $fotoUrlJpg = ($SITE_URL !== '') ? ($SITE_URL . '/sw-content/absen/' . rawurlencode($finalJpg)) : '';

      $lokasi_id = (string)($data_user['lokasi_id'] ?? '');

      // WA payload untuk worker
      $doWa = (($row_site['whatsapp_active'] ?? '') === 'Y');
      $wa = null;
      if ($doWa) {
        $msgRaw = str_replace(
          ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}', '{{foto}}'],
          [
            (string)$data_user['nama_lengkap'],
            tanggal_ind($date),
            'MASUK',
            $data_jam['jam_masuk'] . ' - ' . $data_jam['jam_pulang'],
            $time_absen,
            $status_in,
            'https://www.google.com/maps/place/' . $latitude,
            $fotoUrlJpg
          ],
          (string)($row_site['whatsapp_template'] ?? '')
        );

        $wa = [
          'enabled' => true,
          'tipe'    => (string)($whatsapp_tipe ?? ''),
          'to'      => $penerima,
          'msg_raw' => $msgRaw,
          'domain'  => (string)($whatsapp_domain ?? ''),
          'token'   => (string)($whatsapp_token ?? ''),
          'secret'  => (string)($secret_key ?? ''),
          'sender'  => (string)($whatsapp_sender ?? ''),
          'image'   => $fotoUrlJpg,
        ];
      }

      // INSERT cepat: simpan foto_in = tmpName (agar langsung tampil)
      $stmt = $connection->prepare("
        INSERT INTO absen (
          user_id, tanggal, lokasi_id, jam_masuk, jam_toleransi, jam_pulang,
          absen_in, foto_in, status_masuk, map_in, kehadiran, radius,
          client_request_id_in
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Hadir', '0', ?)
      ");
      if (!$stmt) { echo 'error/Query error (insert absen).'; break; }

      $stmt->bind_param(
        'sssssssssss',
        $user_id, $date, $lokasi_id,
        $data_jam['jam_masuk'], $data_jam['jam_telat'], $data_jam['jam_pulang'],
        $time_absen, $tmpName, $status_in, $latitude,
        $client_request_id
      );

      if ($stmt->execute() === false) {
        $stmt->close();

        if ($connection->errno == 1062) {
          triggerRealtimeUpdate();
          echo 'success/Absensi sudah tercatat.';
          break;
        }

        echo 'error/Koneksi atau server sedang padat. Absensi Anda akan diproses otomatis. Silakan tunggu, jangan scan ulang.';
        break;
      }

      // ambil absen_id untuk job (INI PENTING)
      $newAbsenId = (int)$connection->insert_id;
      $stmt->close();

      triggerRealtimeUpdate();
      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . "!";

      ignore_user_abort(true);
      @set_time_limit(0);
      finishRequestFast();

      $watermark = strip_tags((string)$data_user['nama_lengkap']) . "\n" . $time_sekarang . " - " . tanggal_ind($date);

      enqueueJob($jobDirFs, [
        'type'       => 'absen',
        'mode'       => 'in',
        'absen_id'   => $newAbsenId,           // <--- FIX UTAMA
        'user_id'    => (string)$user_id,
        'tanggal'    => (string)$date,

        'tmp_jpg'    => (string)$tmpPathFs,
        'tmp_name'   => (string)$tmpName,
        'final_name' => (string)$finalJpg,
        'out_jpg'    => (string)$outJpgFs,

        'watermark'  => (string)$watermark,
        'wa'         => $wa,
        'kiosk_did'  => (string)($kiosk['did'] ?? ''),
        'kiosk_label'=> (string)($kiosk['label'] ?? ''),
        'created_at' => date('c'),
      ]);

      break;
    }

    // -------------------------------------------------------
    // ABSEN PULANG
    // -------------------------------------------------------
    if ($time_sekarang >= $absen_pulang) {

      // idempotency
      if ($client_request_id !== '') {
        $chk = $connection->prepare("SELECT absen_id FROM absen WHERE client_request_id_out = ? LIMIT 1");
        if ($chk) {
          $chk->bind_param('s', $client_request_id);
          $chk->execute();
          $r = $chk->get_result();
          $chk->close();
          if ($r && $r->num_rows > 0) {
            triggerRealtimeUpdate();
            echo 'success/Absensi sudah tercatat.';
            break;
          }
        }
      }

      $stmt = $connection->prepare("SELECT absen_id, absen_out, foto_out FROM absen WHERE tanggal=? AND user_id=? LIMIT 1");
      $stmt->bind_param('ss', $date, $user_id);
      $stmt->execute();
      $resAbs = $stmt->get_result();
      $stmt->close();

      if (!$resAbs || $resAbs->num_rows <= 0) {
        echo 'error/Data Absensi Anda tidak ditemukan, Silahkan absen masuk terlebih dahulu!';
        break;
      }

      $data_absen = $resAbs->fetch_assoc();
      if (!empty($data_absen['absen_out']) && $data_absen['absen_out'] !== '00:00:00') {
        echo 'success/Anda sudah absen pulang hari ini!';
        break;
      }

      // simpan JPG tmp
      [$okUp, $tmpPathFs, $tmpName, $errUp] = saveUploadToTmp($tmpDirFs, 'out', $user_id);
      if (!$okUp) { echo 'error/' . $errUp; break; }

      // FINAL JPG (dibuat worker)
      $finalJpg   = 'f_out_' . $user_id . '_' . date('Y-m-d') . '_' . uniqid('', true) . '.jpg';
      $outJpgFs   = rtrim($uploadDirFs, '/\\') . '/' . $finalJpg;
      $fotoUrlJpg = ($SITE_URL !== '') ? ($SITE_URL . '/sw-content/absen/' . rawurlencode($finalJpg)) : '';

      $doWa = (($row_site['whatsapp_active'] ?? '') === 'Y');
      $wa = null;
      if ($doWa) {
        $msgRaw = str_replace(
          ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}', '{{foto}}'],
          [
            (string)$data_user['nama_lengkap'],
            tanggal_ind($date),
            'PULANG',
            $data_jam['jam_masuk'] . ' - ' . $data_jam['jam_pulang'],
            $time_absen,
            $status_out,
            'https://www.google.com/maps/place/' . $latitude,
            $fotoUrlJpg
          ],
          (string)($row_site['whatsapp_template'] ?? '')
        );

        $wa = [
          'enabled' => true,
          'tipe'    => (string)($whatsapp_tipe ?? ''),
          'to'      => $penerima,
          'msg_raw' => $msgRaw,
          'domain'  => (string)($whatsapp_domain ?? ''),
          'token'   => (string)($whatsapp_token ?? ''),
          'secret'  => (string)($secret_key ?? ''),
          'sender'  => (string)($whatsapp_sender ?? ''),
          'image'   => $fotoUrlJpg,
        ];
      }

      $absen_id = (int)($data_absen['absen_id'] ?? 0);

      // UPDATE cepat: simpan foto_out = tmpName
      $stmt = $connection->prepare("
        UPDATE absen SET
          absen_out=?, foto_out=?, status_pulang=?, map_out=?, radius_out='0',
          client_request_id_out=?
        WHERE absen_id=? AND tanggal=? AND user_id=?
        LIMIT 1
      ");
      if (!$stmt) { echo 'error/Query error (update absen).'; break; }

      $stmt->bind_param(
        'ssssssss',
        $time_absen, $tmpName, $status_out, $latitude, $client_request_id,
        $absen_id, $date, $user_id
      );

      if ($stmt->execute() === false) {
        $stmt->close();

        if ($connection->errno == 1062) {
          triggerRealtimeUpdate();
          echo 'success/Absensi sudah tercatat.';
          break;
        }

        echo 'error/Koneksi atau server sedang padat. Absensi Anda akan diproses otomatis. Silakan tunggu, jangan scan ulang.';
        break;
      }
      $stmt->close();

      triggerRealtimeUpdate();
      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Pulang telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . ".";

      ignore_user_abort(true);
      @set_time_limit(0);
      finishRequestFast();

      $watermark = strip_tags((string)$data_user['nama_lengkap']) . "\n" . $time_sekarang . " - " . tanggal_ind($date);

      enqueueJob($jobDirFs, [
        'type'       => 'absen',
        'mode'       => 'out',
        'absen_id'   => $absen_id,             // <--- FIX UTAMA
        'user_id'    => (string)$user_id,
        'tanggal'    => (string)$date,

        'tmp_jpg'    => (string)$tmpPathFs,
        'tmp_name'   => (string)$tmpName,
        'final_name' => (string)$finalJpg,
        'out_jpg'    => (string)$outJpgFs,

        'watermark'  => (string)$watermark,
        'wa'         => $wa,
        'kiosk_did'  => (string)($kiosk['did'] ?? ''),
        'kiosk_label'=> (string)($kiosk['label'] ?? ''),
        'created_at' => date('c'),
      ]);

      break;
    }

    echo 'error/Waktu absen tidak valid!';
    break;
  }

  // =========================================================
  // DATA ABSENSI (TAMPILAN TETAP)
  // =========================================================
  case 'data-absensi': {
    requireValidKiosk($connection, $KIOSK_REQUIRE_KEY, $KIOSK_DID_PARAM, $KIOSK_KEY_PARAM, $KIOSK_LABEL_PARAM);

    $limit = (int)($_GET['limit'] ?? 8);
    if ($limit < 1) $limit = 8;
    if ($limit > 50) $limit = 50;

    $stmt = $connection->prepare("
      SELECT a.*, u.nama_lengkap, u.kelas
      FROM absen a
      LEFT JOIN user u ON a.user_id = u.user_id
      WHERE a.tanggal = ?
      ORDER BY GREATEST(IFNULL(a.absen_in,'00:00:00'), IFNULL(a.absen_out,'00:00:00')) DESC
      LIMIT $limit
    ");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $result_absen = $stmt->get_result();
    $stmt->close();

    header('Content-Type: text/html; charset=utf-8');

    $avatarRel = '../sw-content/avatar/avatar.jpg';
    $cb = (string)time();

    if ($result_absen && $result_absen->num_rows > 0) {
      while ($data_absen = $result_absen->fetch_assoc()) {
        $nama  = htmlspecialchars((string)($data_absen['nama_lengkap'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $kelas = htmlspecialchars((string)($data_absen['kelas'] ?? '-'), ENT_QUOTES, 'UTF-8');

        $absen_in  = htmlspecialchars((string)($data_absen['absen_in'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $absen_out_raw = (string)($data_absen['absen_out'] ?? '');
        $absen_out = ($absen_out_raw === '' || $absen_out_raw === '00:00:00') ? '-' : htmlspecialchars($absen_out_raw, ENT_QUOTES, 'UTF-8');

        $fotoInFile  = (string)($data_absen['foto_in'] ?? '');
        $fotoOutFile = (string)($data_absen['foto_out'] ?? '');

        $src_in  = ($fotoInFile !== '')  ? fotoUrl($SITE_URL, $fotoInFile, $cb)  : $avatarRel;
        $src_out = ($fotoOutFile !== '') ? fotoUrl($SITE_URL, $fotoOutFile, $cb) : $avatarRel;

        echo '
        <div class="card border-1 mb-2" style="border:solid 1px #e3e3e3;">
          <div class="card-body pt-2">
            <div class="row align-items-center">
              <div class="col-md-6">
                <div class="col align-self-center">
                  <p class="text-secondary p-0 m-0">'.$nama.'</p>
                  <small class="badge badge-primary">'.$kelas.'</small>
                </div>
              </div>

              <div class="col-md-3" style="display:flex">
                <div class="col-auto align-self-center">
                  <figure class="avatar avatar-40 rounded mb-0">
                    <img src="'.htmlspecialchars($src_in, ENT_QUOTES, 'UTF-8').'" height="40" loading="lazy" decoding="async"
                      onerror="this.onerror=null;this.src=\''.htmlspecialchars($avatarRel, ENT_QUOTES, 'UTF-8').'\';">
                  </figure>
                </div>
                <div class="col-4 align-self-center">
                  <small class="text-info">MASUK</small>
                  <p class="text-secondary">'.$absen_in.'</p>
                </div>
              </div>

              <div class="col-md-3" style="display:flex">
                <div class="col-auto align-self-center">
                  <figure class="avatar avatar-40 rounded mb-0">
                    <img src="'.htmlspecialchars($src_out, ENT_QUOTES, 'UTF-8').'" height="40" loading="lazy" decoding="async"
                      onerror="this.onerror=null;this.src=\''.htmlspecialchars($avatarRel, ENT_QUOTES, 'UTF-8').'\';">
                  </figure>
                </div>
                <div class="col align-self-center">
                  <small class="text-danger">PULANG</small>
                  <p class="text-secondary">'.$absen_out.'</p>
                </div>
              </div>

            </div>
          </div>
        </div>';
      }
    } else {
      echo '<div class="alert alert-info text-center">Data absensi masih kosong!</div>';
    }
    break;
  }

  // =========================================================
  // DATA COUNTER
  // =========================================================
  case 'data-counter': {
    requireValidKiosk($connection, $KIOSK_REQUIRE_KEY, $KIOSK_DID_PARAM, $KIOSK_KEY_PARAM, $KIOSK_LABEL_PARAM);

    $qTotal = $connection->query("SELECT COUNT(*) AS total FROM user WHERE active='Y'");
    $rowTotal = $qTotal ? $qTotal->fetch_assoc() : ['total' => 0];
    $total_siswa = (int)($rowTotal['total'] ?? 0);

    $stmt = $connection->prepare("
      SELECT
        SUM(CASE WHEN a.kehadiran = 'Hadir' THEN 1 ELSE 0 END) AS hadir_count,
        SUM(CASE WHEN a.kehadiran = 'Izin'  THEN 1 ELSE 0 END) AS izin_count,
        SUM(CASE WHEN a.kehadiran = 'Hadir' AND a.status_masuk = 'Tepat Waktu' THEN 1 ELSE 0 END) AS ontime_count,
        SUM(CASE WHEN a.kehadiran = 'Hadir' AND a.status_masuk = 'Terlambat' THEN 1 ELSE 0 END) AS telat_count
      FROM user u
      LEFT JOIN absen a
        ON u.user_id = a.user_id
       AND a.tanggal = ?
      WHERE u.active = 'Y'
    ");
    $stmt->bind_param('s', $date);
    $stmt->execute();
    $resRekap = $stmt->get_result();
    $stmt->close();

    $row = ($resRekap) ? ($resRekap->fetch_assoc() ?: []) : [];

    $hadir_count  = (int)($row['hadir_count']  ?? 0);
    $izin_count   = (int)($row['izin_count']   ?? 0);
    $ontime_count = (int)($row['ontime_count'] ?? 0);
    $telat_count  = (int)($row['telat_count']  ?? 0);

    $belum_absen = max(0, $total_siswa - $hadir_count - $izin_count);
    $persentase  = ($total_siswa > 0) ? (int)round(($hadir_count / $total_siswa) * 100, 0) : 0;

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'total_siswa' => $total_siswa,
      'on_time'     => $ontime_count,
      'terlambat'   => $telat_count,
      'izin'        => $izin_count,
      'belum_absen' => $belum_absen,
      'total_absen' => $hadir_count,
      'persentase'  => $persentase
    ]);
    break;
  }

  default:
    echo 'error/Action tidak dikenal!';
    break;
}