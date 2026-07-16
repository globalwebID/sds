<?php
require_once '../sw-library/sw-config.php';
require_once '../sw-library/sw-function.php';

header('Content-Type: text/plain; charset=utf-8');

$uploadDir = '../sw-content/absen/';
if (!is_dir($uploadDir)) {
  @mkdir($uploadDir, 0755, true);
}

// ===== Realtime (SSE) helper: sentuh flag agar layar update =====
function triggerRealtimeUpdate(): void {
  $flag = __DIR__ . '/realtime.flag';
  @file_put_contents($flag, (string)time());
}

function resizeImage($resourceType, int $image_width, int $image_height): GdImage {
  $resizeWidth  = 700;
  $resizeHeight = (int)(($image_height / max(1, $image_width)) * $resizeWidth);
  $imageLayer   = imagecreatetruecolor($resizeWidth, $resizeHeight);
  if ($imageLayer === false) {
    throw new RuntimeException("Failed to create a true color image.");
  }
  if (!imagecopyresampled($imageLayer, $resourceType, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $image_width, $image_height)) {
    throw new RuntimeException("Failed to resample the image.");
  }
  return $imageLayer;
}

/**
 * ===== WA POST helper (media + caption) =====
 * Dibuat DI FILE INI supaya Anda tidak perlu ubah sw-function.php
 * - Jika $imageUrl diisi: kirim foto sebagai media + caption $msg
 * - Jika kosong: kirim teks biasa
 */
function KirimWaPostMedia(string $phone, string $msg, string $link, string $token, string $secret_key, ?string $imageUrl = null): ?string {
  $item = [
    'phone'   => $phone,
    'message' => $msg,
    'delay'   => '1',
  ];
  if (!empty($imageUrl)) {
    $item['image'] = $imageUrl;
  }

  $payload = ["data" => [$item]];

  $curl = curl_init();
  curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Authorization: $token.$secret_key",
    "Content-Type: application/json"
  ]);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
  curl_setopt($curl, CURLOPT_URL, $link);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

  $result = curl_exec($curl);
  curl_close($curl);
  return is_string($result) ? $result : null;
}

function finishRequestFast(): void {
  // pastikan output langsung dikirim ke client agar layar cepat lanjut
  @ob_end_flush();
  @ob_flush();
  @flush();

  if (function_exists('fastcgi_finish_request')) {
    @fastcgi_finish_request();
  }
}

$data_jam = getJam($connection, $hari_ini, 'Siswa');

/**
 * ===== Base URL publik untuk akses foto (dipakai kirim media WA) =====
 */
$SITE_URL = rtrim((string)($row_site['site_url'] ?? $row_site['site_domain'] ?? ''), '/');
if ($SITE_URL === '') {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host   = $_SERVER['HTTP_HOST'] ?? '';
  $SITE_URL = ($host !== '') ? ($scheme . '://' . $host) : '';
}

$action = (string)($_GET['action'] ?? '');

switch ($action) {

  // =========================================================
  // ABSEN (RFID / QR + FOTO)
  // =========================================================
  case 'absen': {
    $error = [];

    $qrcode   = isset($_POST['qrcode']) ? trim((string)$_POST['qrcode']) : '';
    $latitude = isset($_POST['latitude']) ? trim((string)$_POST['latitude']) : '';

    if ($qrcode === '')   $error[] = 'Qrcode tidak boleh kosong';
    if ($latitude === '') $error[] = 'Latitude tidak boleh kosong';

    // Validasi file gambar
    $thumb = null;
    $source = null;

    if (empty($_FILES['img']['tmp_name'])) {
      $error[] = 'Foto tidak dapat diunggah!';
    } else {
      $file_tmp = $_FILES['img']['tmp_name'];

      // lebih aman: pastikan memang jpeg
      $info = @getimagesize($file_tmp);
      if (!$info || empty($info['mime']) || stripos($info['mime'], 'jpeg') === false) {
        $error[] = 'Format foto harus JPG/JPEG!';
      } else {
        $source = @imagecreatefromjpeg($file_tmp);
        if (!$source) {
          $error[] = 'Gagal membuat resource gambar!';
        } else {
          $source_width  = imagesx($source);
          $source_height = imagesy($source);

          $new_width  = 350;
          $new_height = (int)(($source_height / max(1, $source_width)) * $new_width);

          $thumb = imagecreatetruecolor($new_width, $new_height);
          if ($thumb) {
            @imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $source_width, $source_height);
          } else {
            $error[] = 'Gagal membuat thumbnail!';
          }
        }
      }
    }

    if (!empty($error)) {
      if ($thumb) @imagedestroy($thumb);
      if ($source) @imagedestroy($source);
      echo 'error/' . implode("\n", $error);
      break;
    }

    if (!$data_jam) {
      if ($thumb) @imagedestroy($thumb);
      if ($source) @imagedestroy($source);
      echo 'error/Jam Absensi tidak ditemukan!';
      break;
    }

    // Filter user by tipe absen layar
    $tipe = (string)($row_site['tipe_absen_layar'] ?? '');
    $col  = ($tipe === 'rfid') ? 'rfid' : 'nisn'; // default nisn

    // pakai prepared statement
    $stmt = $connection->prepare("
      SELECT user.user_id, user.telp, user.nama_lengkap, lokasi.*
      FROM user
      LEFT JOIN lokasi ON user.lokasi = lokasi.lokasi_id
      WHERE user.$col = ?
      LIMIT 1
    ");
    if (!$stmt) {
      if ($thumb) @imagedestroy($thumb);
      if ($source) @imagedestroy($source);
      echo 'error/Query error (prepare user).';
      break;
    }
    $stmt->bind_param('s', $qrcode);
    $stmt->execute();
    $result_user = $stmt->get_result();
    $stmt->close();

    if (!$result_user || $result_user->num_rows <= 0) {
      if ($thumb) @imagedestroy($thumb);
      if ($source) @imagedestroy($source);
      echo 'error/Data pengguna tidak ditemukan!';
      break;
    }

    $data_user = $result_user->fetch_assoc();
    $penerima  = (string)($data_user['telp'] ?? '');
    $user_id   = (string)($data_user['user_id'] ?? '');

    $status_in  = ($time_sekarang <= $data_jam['jam_telat']) ? 'Tepat Waktu' : 'Terlambat';
    $status_out = ($time_sekarang <  $data_jam['jam_pulang']) ? 'Pulang Cepat' : 'Tepat Waktu';

    // Aturan:
    $absen_masuk  = date('H:i:s', strtotime($data_jam['jam_masuk'] . ' - 60 minute'));
    $absen_pulang = $data_jam['jam_pulang'];

    if ($time_sekarang < $absen_masuk) {
      if ($thumb) @imagedestroy($thumb);
      if ($source) @imagedestroy($source);
      echo 'error/Waktu absen masuk belum dimulai!';
      break;
    }

    // =============== ABSEN MASUK ===============
    if ($time_sekarang >= $absen_masuk && $time_sekarang < $absen_pulang) {

      // cek sudah ada record hari ini?
      $stmt = $connection->prepare("SELECT absen_id, absen_in FROM absen WHERE tanggal=? AND user_id=? LIMIT 1");
      $stmt->bind_param('ss', $date, $user_id);
      $stmt->execute();
      $resAbs = $stmt->get_result();
      $stmt->close();

      if ($resAbs && $resAbs->num_rows > 0) {
        if ($thumb) @imagedestroy($thumb);
        if ($source) @imagedestroy($source);
        echo 'error/Sekarang Belum Waktunya Absen Pulang! Absen PULANG bisa mulai pukul ' . $absen_pulang . '.';
        break;
      }

      $watermark = strip_tags((string)$data_user['nama_lengkap']) . "\n" . $time_sekarang . " - " . tanggal_ind($date);
      $foto      = 'in_' . $user_id . '_' . date('Y-m-d') . '_' . uniqid() . '.png';
      $filename  = $uploadDir . $foto;
      $foto_url  = ($SITE_URL !== '') ? ($SITE_URL . '/sw-content/absen/' . rawurlencode($foto)) : '';

      // WA template (MASUK)
      $doWa = (($row_site['whatsapp_active'] ?? '') === 'Y');
      $isipesan = '';
      if ($doWa) {
        $pesan = str_replace(
          ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}', '{{foto}}'],
          [
            (string)$data_user['nama_lengkap'],
            tanggal_ind($date),
            'MASUK',
            $data_jam['jam_masuk'] . ' - ' . $data_jam['jam_pulang'],
            $time_absen,
            $status_in,
            'https://www.google.com/maps/place/' . $latitude,
            $foto_url
          ],
          (string)($row_site['whatsapp_template'] ?? '')
        );

        if (($whatsapp_tipe ?? '') === 'POST') {
          $isipesan = $pesan;
        } else {
          $pesan    = str_replace(["\r\n", "\n"], "%0A", $pesan);
          $isipesan = str_replace(" ", "%20", $pesan);
        }
      }

      // INSERT cepat (DB dulu)
      $stmt = $connection->prepare("
        INSERT INTO absen (
          user_id, tanggal, lokasi_id, jam_masuk, jam_toleransi, jam_pulang,
          absen_in, foto_in, status_masuk, map_in, kehadiran, radius
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Hadir', '0')
      ");
      $lokasi_id = (string)($data_user['lokasi_id'] ?? '');
      $stmt->bind_param(
        'ssssssssss',
        $user_id,
        $date,
        $lokasi_id,
        $data_jam['jam_masuk'],
        $data_jam['jam_telat'],
        $data_jam['jam_pulang'],
        $time_absen,
        $foto,
        $status_in,
        $latitude
      );

      if ($stmt->execute() === false) {
        $stmt->close();
        if ($thumb) @imagedestroy($thumb);
        if ($source) @imagedestroy($source);
        echo 'error/Sepertinya Sistem Kami sedang error!';
        break;
      }
      $stmt->close();

      // trigger realtime + KIRIM RESPONSE CEPAT
      triggerRealtimeUpdate();
      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . "!";

      // ===== lanjut proses berat di belakang =====
      ignore_user_abort(true);
      @set_time_limit(0);
      finishRequestFast();

      // Simpan Foto + Watermark
      if ($thumb) {
        @addTextWatermark($thumb, $watermark, $filename);
      }

      // Kirim WA (POST: media + caption, GET: teks)
      if ($doWa) {
        if (($whatsapp_tipe ?? '') === 'POST') {
          KirimWaPostMedia($penerima, $isipesan, $whatsapp_domain, $whatsapp_token, $secret_key, $foto_url);
        } else {
          KirimWa($whatsapp_sender, $penerima, $isipesan, $whatsapp_domain, $whatsapp_token);
        }
      }

      if ($thumb) @imagedestroy($thumb);
      if ($source) @imagedestroy($source);
      break;
    }

    // =============== ABSEN PULANG ===============
    if ($time_sekarang >= $absen_pulang) {

      $stmt = $connection->prepare("SELECT absen_id, absen_in, absen_out FROM absen WHERE tanggal=? AND user_id=? LIMIT 1");
      $stmt->bind_param('ss', $date, $user_id);
      $stmt->execute();
      $resAbs = $stmt->get_result();
      $stmt->close();

      if (!$resAbs || $resAbs->num_rows <= 0) {
        if ($thumb) @imagedestroy($thumb);
        if ($source) @imagedestroy($source);
        echo 'error/Data Absensi Anda tidak ditemukan, Silahkan absen masuk terlebih dahulu!';
        break;
      }

      $data_absen = $resAbs->fetch_assoc();

      if (!empty($data_absen['absen_out']) && $data_absen['absen_out'] !== '00:00:00') {
        if ($thumb) @imagedestroy($thumb);
        if ($source) @imagedestroy($source);
        echo 'success/Anda sudah absen pulang hari ini!';
        break;
      }

      $watermark = strip_tags((string)$data_user['nama_lengkap']) . "\n" . $time_sekarang . " - " . tanggal_ind($date);
      $foto      = 'out_' . $user_id . '_' . date('Y-m-d') . '_' . uniqid() . '.png';
      $filename  = $uploadDir . $foto;
      $foto_url  = ($SITE_URL !== '') ? ($SITE_URL . '/sw-content/absen/' . rawurlencode($foto)) : '';

      // WA template (PULANG)
      $doWa = (($row_site['whatsapp_active'] ?? '') === 'Y');
      $isipesan = '';
      if ($doWa) {
        $pesan = str_replace(
          ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}', '{{foto}}'],
          [
            (string)$data_user['nama_lengkap'],
            tanggal_ind($date),
            'PULANG',
            $data_jam['jam_masuk'] . ' - ' . $data_jam['jam_pulang'],
            $time_absen,
            $status_out,
            'https://www.google.com/maps/place/' . $latitude,
            $foto_url
          ],
          (string)($row_site['whatsapp_template'] ?? '')
        );

        if (($whatsapp_tipe ?? '') === 'POST') {
          $isipesan = $pesan;
        } else {
          $pesan    = str_replace(["\r\n", "\n"], "%0A", $pesan);
          $isipesan = str_replace(" ", "%20", $pesan);
        }
      }

      // UPDATE cepat (DB dulu)
      $stmt = $connection->prepare("
        UPDATE absen SET
          absen_out=?, foto_out=?, status_pulang=?, map_out=?, radius_out='0'
        WHERE tanggal=? AND user_id=? AND absen_id=?
      ");
      $absen_id = (string)$data_absen['absen_id'];
      $stmt->bind_param('sssssss', $time_absen, $foto, $status_out, $latitude, $date, $user_id, $absen_id);

      if ($stmt->execute() === false) {
        $stmt->close();
        if ($thumb) @imagedestroy($thumb);
        if ($source) @imagedestroy($source);
        echo 'error/Sepertinya Sistem Kami sedang error!';
        break;
      }
      $stmt->close();

      triggerRealtimeUpdate();
      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Pulang telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . ".";

      // ===== lanjut proses berat di belakang =====
      ignore_user_abort(true);
      @set_time_limit(0);
      finishRequestFast();

      if ($thumb) {
        @addTextWatermark($thumb, $watermark, $filename);
      }

      if ($doWa) {
        if (($whatsapp_tipe ?? '') === 'POST') {
          KirimWaPostMedia($penerima, $isipesan, $whatsapp_domain, $whatsapp_token, $secret_key, $foto_url);
        } else {
          KirimWa($whatsapp_sender, $penerima, $isipesan, $whatsapp_domain, $whatsapp_token);
        }
      }

      if ($thumb) @imagedestroy($thumb);
      if ($source) @imagedestroy($source);
      break;
    }

    if ($thumb) @imagedestroy($thumb);
    if ($source) @imagedestroy($source);
    echo 'error/Waktu absen tidak valid!';
    break;
  }

  // =========================================================
  // ABSEN WEBCAM (tanpa foto, tetap dipercepat)
  // =========================================================
  case 'absen-webcame': {
    $qrcode   = isset($_POST['qrcode']) ? trim((string)$_POST['qrcode']) : '';
    $latitude = isset($_POST['latitude']) ? trim((string)$_POST['latitude']) : '';

    if ($qrcode === '' || $latitude === '') {
      echo 'error/Qrcode dan Latitude tidak boleh kosong';
      break;
    }

    if (!$data_jam) {
      echo 'error/Jam Absensi tidak ditemukan!';
      break;
    }

    $stmt = $connection->prepare("
      SELECT user.user_id, user.telp, user.nama_lengkap, lokasi.*
      FROM user
      LEFT JOIN lokasi ON user.lokasi = lokasi.lokasi_id
      WHERE user.nisn = ?
      LIMIT 1
    ");
    $stmt->bind_param('s', $qrcode);
    $stmt->execute();
    $result_user = $stmt->get_result();
    $stmt->close();

    if (!$result_user || $result_user->num_rows <= 0) {
      echo 'error/Data pengguna tidak ditemukan!';
      break;
    }

    $data_user = $result_user->fetch_assoc();
    $penerima  = (string)($data_user['telp'] ?? '');
    $user_id   = (string)($data_user['user_id'] ?? '');

    $status_in  = ($time_sekarang <= $data_jam['jam_telat']) ? 'Tepat Waktu' : 'Terlambat';
    $status_out = ($time_sekarang <  $data_jam['jam_pulang']) ? 'Pulang Cepat' : 'Tepat Waktu';

    $absen_masuk  = date('H:i:s', strtotime($data_jam['jam_masuk'] . ' - 60 minute'));
    $absen_pulang = $data_jam['jam_pulang'];

    if ($time_sekarang < $absen_masuk) {
      echo 'error/Waktu absen masuk belum dimulai!';
      break;
    }

    // WA template
    $doWa = (($row_site['whatsapp_active'] ?? '') === 'Y');
    $isipesan = '';

    // MASUK
    if ($time_sekarang >= $absen_masuk && $time_sekarang < $absen_pulang) {

      $stmt = $connection->prepare("SELECT absen_id FROM absen WHERE tanggal=? AND user_id=? LIMIT 1");
      $stmt->bind_param('ss', $date, $user_id);
      $stmt->execute();
      $resAbs = $stmt->get_result();
      $stmt->close();

      if ($resAbs && $resAbs->num_rows > 0) {
        echo 'error/Sekarang Belum Waktunya Absen Pulang!';
        break;
      }

      if ($doWa) {
        $pesan = str_replace(
          ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
          [
            (string)$data_user['nama_lengkap'],
            tanggal_ind($date),
            'MASUK',
            $data_jam['jam_masuk'] . ' - ' . $data_jam['jam_pulang'],
            $time_absen,
            $status_in,
            'https://www.google.com/maps/place/' . $latitude
          ],
          (string)($row_site['whatsapp_template'] ?? '')
        );

        if (($whatsapp_tipe ?? '') === 'POST') {
          $isipesan = $pesan;
        } else {
          $pesan    = str_replace(["\r\n", "\n"], "%0A", $pesan);
          $isipesan = str_replace(" ", "%20", $pesan);
        }
      }

      $stmt = $connection->prepare("
        INSERT INTO absen (
          user_id, tanggal, lokasi_id, jam_masuk, jam_toleransi, jam_pulang,
          absen_in, status_masuk, map_in, kehadiran, radius
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Hadir', '0')
      ");
      $lokasi_id = (string)($data_user['lokasi_id'] ?? '');
      $stmt->bind_param(
        'sssssssss',
        $user_id,
        $date,
        $lokasi_id,
        $data_jam['jam_masuk'],
        $data_jam['jam_telat'],
        $data_jam['jam_pulang'],
        $time_absen,
        $status_in,
        $latitude
      );

      if ($stmt->execute() === false) {
        $stmt->close();
        echo 'error/Sepertinya Sistem Kami sedang error!';
        break;
      }
      $stmt->close();

      triggerRealtimeUpdate();
      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . "!";

      // lanjut WA di belakang
      ignore_user_abort(true);
      @set_time_limit(0);
      finishRequestFast();

      if ($doWa) {
        if (($whatsapp_tipe ?? '') === 'POST') {
          KirimWa($penerima, $isipesan, $whatsapp_domain, $whatsapp_token, $secret_key);
        } else {
          KirimWa($whatsapp_sender, $penerima, $isipesan, $whatsapp_domain, $whatsapp_token);
        }
      }

      break;
    }

    // PULANG
    if ($time_sekarang >= $absen_pulang) {

      $stmt = $connection->prepare("SELECT absen_id, absen_out FROM absen WHERE tanggal=? AND user_id=? LIMIT 1");
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

      if ($doWa) {
        $pesan = str_replace(
          ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
          [
            (string)$data_user['nama_lengkap'],
            tanggal_ind($date),
            'PULANG',
            $data_jam['jam_masuk'] . ' - ' . $data_jam['jam_pulang'],
            $time_absen,
            $status_out,
            'https://www.google.com/maps/place/' . $latitude
          ],
          (string)($row_site['whatsapp_template'] ?? '')
        );

        if (($whatsapp_tipe ?? '') === 'POST') {
          $isipesan = $pesan;
        } else {
          $pesan    = str_replace(["\r\n", "\n"], "%0A", $pesan);
          $isipesan = str_replace(" ", "%20", $pesan);
        }
      }

      $stmt = $connection->prepare("
        UPDATE absen SET
          absen_out=?, status_pulang=?, map_out=?, radius_out='0'
        WHERE tanggal=? AND user_id=? AND absen_id=?
      ");
      $absen_id = (string)$data_absen['absen_id'];
      $stmt->bind_param('ssssss', $time_absen, $status_out, $latitude, $date, $user_id, $absen_id);

      if ($stmt->execute() === false) {
        $stmt->close();
        echo 'error/Sepertinya Sistem Kami sedang error!';
        break;
      }
      $stmt->close();

      triggerRealtimeUpdate();
      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Pulang telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . ".";

      ignore_user_abort(true);
      @set_time_limit(0);
      finishRequestFast();

      if ($doWa) {
        if (($whatsapp_tipe ?? '') === 'POST') {
          KirimWa($penerima, $isipesan, $whatsapp_domain, $whatsapp_token, $secret_key);
        } else {
          KirimWa($whatsapp_sender, $penerima, $isipesan, $whatsapp_domain, $whatsapp_token);
        }
      }

      break;
    }

    echo 'error/Waktu absen tidak valid!';
    break;
  }

  // =========================================================
  // DATA ABSENSI (DEFAULT 5 TERBARU) - TANPA BASE64
  // =========================================================
  case 'data-absensi': {
    // default 5 (sesuai layar), bisa override ?limit=25
    $limit = (int)($_GET['limit'] ?? 8);
    if ($limit < 1) $limit = 8;
    if ($limit > 50) $limit = 50;

    // gunakan ORDER BY yang konsisten: terbaru dulu
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

    if ($result_absen && $result_absen->num_rows > 0) {
      while ($data_absen = $result_absen->fetch_assoc()) {

        $nama = htmlspecialchars((string)($data_absen['nama_lengkap'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $kelas = htmlspecialchars((string)($data_absen['kelas'] ?? '-'), ENT_QUOTES, 'UTF-8');

        $absen_in  = htmlspecialchars((string)($data_absen['absen_in'] ?? '-'), ENT_QUOTES, 'UTF-8');
        $absen_out_raw = (string)($data_absen['absen_out'] ?? '');
        $absen_out = ($absen_out_raw === '' || $absen_out_raw === '00:00:00') ? '-' : htmlspecialchars($absen_out_raw, ENT_QUOTES, 'UTF-8');

        $fotoInFile  = (string)($data_absen['foto_in'] ?? '');
        $fotoOutFile = (string)($data_absen['foto_out'] ?? '');

        $foto_in_path  = '../sw-content/absen/' . $fotoInFile;
        $foto_out_path = '../sw-content/absen/' . $fotoOutFile;

        $avatar = '../sw-content/avatar/avatar.jpg';
        $src_in  = ($fotoInFile !== '' && file_exists($foto_in_path)) ? $foto_in_path : $avatar;
        $src_out = ($fotoOutFile !== '' && file_exists($foto_out_path)) ? $foto_out_path : $avatar;

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
                  <img src="'.htmlspecialchars($src_in, ENT_QUOTES, 'UTF-8').'" height="40" loading="lazy" decoding="async" onerror="this.onerror=null;this.src=\''.htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8').'\';">
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
                  <img src="'.htmlspecialchars($src_out, ENT_QUOTES, 'UTF-8').'" height="40" loading="lazy" decoding="async" onerror="this.onerror=null;this.src=\''.htmlspecialchars($avatar, ENT_QUOTES, 'UTF-8').'\';">
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
    // Total siswa aktif
    $qTotal = $connection->query("SELECT COUNT(*) AS total FROM user WHERE active='Y'");
    $rowTotal = $qTotal ? $qTotal->fetch_assoc() : ['total' => 0];
    $total_siswa = (int)($rowTotal['total'] ?? 0);

    $sqlRekap = "
      SELECT
        SUM(CASE WHEN a.kehadiran = 'Hadir' THEN 1 ELSE 0 END) AS hadir_count,
        SUM(CASE WHEN a.kehadiran = 'Izin'  THEN 1 ELSE 0 END) AS izin_count,
        SUM(CASE WHEN a.kehadiran = 'Hadir' AND a.status_masuk = 'Tepat Waktu' THEN 1 ELSE 0 END) AS ontime_count,
        SUM(CASE WHEN a.kehadiran = 'Hadir' AND a.status_masuk = 'Terlambat' THEN 1 ELSE 0 END) AS telat_count
      FROM user u
      LEFT JOIN absen a
        ON u.user_id = a.user_id
       AND a.tanggal = '$date'
      WHERE u.active = 'Y'
    ";

    $resRekap = $connection->query($sqlRekap);
    $row = $resRekap ? $resRekap->fetch_assoc() : [];

    $hadir_count  = (int)($row['hadir_count']  ?? 0);
    $izin_count   = (int)($row['izin_count']   ?? 0);
    $ontime_count = (int)($row['ontime_count'] ?? 0);
    $telat_count  = (int)($row['telat_count']  ?? 0);

    $belum_absen = max(0, $total_siswa - $hadir_count - $izin_count);
    $persentase  = ($total_siswa > 0) ? (int)round(($hadir_count / $total_siswa) * 100, 0) : 0;

    $data_counter = [
      'total_siswa' => $total_siswa,
      'on_time'     => $ontime_count,
      'terlambat'   => $telat_count,
      'izin'        => $izin_count,
      'belum_absen' => $belum_absen,
      'total_absen' => $hadir_count,
      'persentase'  => $persentase
    ];

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data_counter);
    break;
  }

  default:
    echo 'error/Action tidak dikenal!';
    break;
}
