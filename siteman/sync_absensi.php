<?php
require_once __DIR__ . '/../config/runtime.php';
sds_require_installed();
$runtimeMain = sds_database_config('main');
$runtimeAttendance = sds_database_config('attendance');
/**
 * sync_absensi.php
 * Sync DB Absensi -> DB SDS (mirror)
 *
 * Sumber (ABSENSI):
 *   DB  : dikonfigurasi melalui installer
 *   Tbl : user, absen
 *
 * Target (SDS):
 *   DB  : dikonfigurasi melalui installer
 *   Tbl : absensi_user, absensi_absen
 *
 * Cara pakai:
 * - Cron (disarankan): php -q /path/to/sync_absensi.php
 * - Manual via browser (opsional): sync_absensi.php?token=ISI_TOKEN
 */

// =========================
// KONFIGURASI (WAJIB DIISI)
// =========================
$config = [
  // DB ABSENSI (isi dengan user yang PUNYA AKSES ke DB absensi)
  'absen' => [
    'host' => $runtimeAttendance['host'],
    'user' => $runtimeAttendance['username'],
    'pass' => $runtimeAttendance['password'],
    'db'   => $runtimeAttendance['database'],
    'charset' => 'utf8mb4',
  ],

  // DB SDS (biasanya sudah ada; user ini akses DB SDS)
  'sds' => [
    'host' => $runtimeMain['host'],
    'user' => $runtimeMain['username'],
    'pass' => $runtimeMain['password'],
    'db'   => $runtimeMain['database'],
    'charset' => 'utf8mb4',
  ],

  // keamanan manual run via browser
  'allow_web' => true,
  'token' => (string)sds_config('security.sync_token', ''),

  // batas batch (biar ringan)
  'batch_user' => 2000,   // max user per sync
  'batch_absen' => 5000,  // max absen per sync

  // file state+lock+log (pastikan folder ini writable)
  'state_file' => __DIR__ . '/sync_absensi.state.json',
  'lock_file'  => __DIR__ . '/sync_absensi.lock',
  'log_file'   => __DIR__ . '/sync_absensi.log',
];

// =========================
// UTIL
// =========================
function respond($msg, $ok=true) {
  $prefix = $ok ? "[OK] " : "[ERR] ";
  echo $prefix . $msg . "\n";
}

function log_line($file, $line) {
  @file_put_contents($file, '['.date('Y-m-d H:i:s').'] '.$line."\n", FILE_APPEND);
}

function connect_mysqli($cfg) {
  $mysqli = @new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db']);
  if ($mysqli->connect_error) {
    throw new RuntimeException("Koneksi gagal {$cfg['user']}@{$cfg['host']} DB {$cfg['db']}: ".$mysqli->connect_error);
  }
  if (!$mysqli->set_charset($cfg['charset'] ?? 'utf8mb4')) {
    // tidak fatal
  }
  return $mysqli;
}

function acquire_lock($lockFile) {
  $fp = @fopen($lockFile, 'c+');
  if (!$fp) return [false, null];
  if (!flock($fp, LOCK_EX | LOCK_NB)) return [false, $fp];
  ftruncate($fp, 0);
  fwrite($fp, (string)getmypid());
  fflush($fp);
  return [true, $fp];
}

function release_lock($fp) {
  if ($fp) {
    @flock($fp, LOCK_UN);
    @fclose($fp);
  }
}

function load_state($file) {
  if (!file_exists($file)) {
    return ['last_user_id' => 0, 'last_absen_id' => 0];
  }
  $raw = @file_get_contents($file);
  $j = json_decode((string)$raw, true);
  if (!is_array($j)) return ['last_user_id' => 0, 'last_absen_id' => 0];
  return array_merge(['last_user_id'=>0,'last_absen_id'=>0], $j);
}

function save_state($file, $state) {
  @file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}

function is_cli() {
  return (php_sapi_name() === 'cli');
}

// =========================
// SECURITY: web token
// =========================
if (!$config['allow_web'] && !is_cli()) {
  http_response_code(403);
  echo "Forbidden\n";
  exit;
}
if (!is_cli()) {
  $t = $_GET['token'] ?? '';
  if ($t !== $config['token']) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
  }
  header('Content-Type: text/plain; charset=UTF-8');
}

// =========================
// MAIN
// =========================
[$locked, $lockFp] = acquire_lock($config['lock_file']);
if (!$locked) {
  respond("Lock aktif. Sync sedang berjalan / baru saja berjalan.", false);
  exit;
}

$state = load_state($config['state_file']);
log_line($config['log_file'], "START last_user_id={$state['last_user_id']} last_absen_id={$state['last_absen_id']}");

try {
  $dbA = connect_mysqli($config['absen']); // absensi
  $dbS = connect_mysqli($config['sds']);   // sds

  // =========================
  // 1) SYNC USER (incremental by user_id)
  // =========================
  $lastUserId = (int)$state['last_user_id'];
  $batchUser  = (int)$config['batch_user'];

  $sqlUser = "SELECT user_id, nisn, rfid, email, password, nama_lengkap, tempat_lahir, tanggal_lahir,
                     jenis_kelamin, kelas, tahun_ajaran, lokasi, alamat, telp, avatar, tanggal_registrasi,
                     tanggal_login, ip, browser, status, active
              FROM `user`
              WHERE user_id > ?
              ORDER BY user_id ASC
              LIMIT {$batchUser}";
  $stmtU = $dbA->prepare($sqlUser);
  if (!$stmtU) throw new RuntimeException("Prepare user gagal: ".$dbA->error);
  $stmtU->bind_param('i', $lastUserId);
  $stmtU->execute();
  $resU = $stmtU->get_result();

  $countUser = 0;
  $maxUserId = $lastUserId;

  // upsert ke SDS
  $sqlUpUser = "INSERT INTO absensi_user
    (user_id, nisn, rfid, email, password, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, kelas,
     tahun_ajaran, lokasi, alamat, telp, avatar, tanggal_registrasi, tanggal_login, ip, browser, status, active)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      nisn=VALUES(nisn), rfid=VALUES(rfid), email=VALUES(email), password=VALUES(password),
      nama_lengkap=VALUES(nama_lengkap), tempat_lahir=VALUES(tempat_lahir), tanggal_lahir=VALUES(tanggal_lahir),
      jenis_kelamin=VALUES(jenis_kelamin), kelas=VALUES(kelas), tahun_ajaran=VALUES(tahun_ajaran),
      lokasi=VALUES(lokasi), alamat=VALUES(alamat), telp=VALUES(telp), avatar=VALUES(avatar),
      tanggal_registrasi=VALUES(tanggal_registrasi), tanggal_login=VALUES(tanggal_login),
      ip=VALUES(ip), browser=VALUES(browser), status=VALUES(status), active=VALUES(active)";
  $stmtUpU = $dbS->prepare($sqlUpUser);
  if (!$stmtUpU) throw new RuntimeException("Prepare upsert user SDS gagal: ".$dbS->error);

  while ($row = $resU->fetch_assoc()) {
    $countUser++;
    $uid = (int)$row['user_id'];
    if ($uid > $maxUserId) $maxUserId = $uid;

    // bind (21 kolom)
    $stmtUpU->bind_param(
      'issssssssssssssssssss',
      $uid,
      $row['nisn'],
      $row['rfid'],
      $row['email'],
      $row['password'],
      $row['nama_lengkap'],
      $row['tempat_lahir'],
      $row['tanggal_lahir'],
      $row['jenis_kelamin'],
      $row['kelas'],
      $row['tahun_ajaran'],
      $row['lokasi'],
      $row['alamat'],
      $row['telp'],
      $row['avatar'],
      $row['tanggal_registrasi'],
      $row['tanggal_login'],
      $row['ip'],
      $row['browser'],
      $row['status'],
      $row['active']
    );
    $stmtUpU->execute();
  }
  $stmtU->close();
  $stmtUpU->close();

  if ($countUser > 0) {
    $state['last_user_id'] = $maxUserId;
    log_line($config['log_file'], "SYNC USER: {$countUser} rows, last_user_id={$maxUserId}");
  } else {
    log_line($config['log_file'], "SYNC USER: 0 rows");
  }

  // =========================
  // 2) SYNC ABSEN (incremental by absen_id)
  // =========================
  $lastAbsenId = (int)$state['last_absen_id'];
  $batchAbsen  = (int)$config['batch_absen'];

  $sqlAbsen = "SELECT absen_id, user_id, tanggal, lokasi_id, jam_masuk, jam_toleransi, jam_pulang,
                      absen_in, absen_out, foto_in, foto_out, status_masuk, status_pulang,
                      map_in, map_out, kehadiran, radius, radius_out, keterangan
               FROM absen
               WHERE absen_id > ?
               ORDER BY absen_id ASC
               LIMIT {$batchAbsen}";
  $stmtA = $dbA->prepare($sqlAbsen);
  if (!$stmtA) throw new RuntimeException("Prepare absen gagal: ".$dbA->error);
  $stmtA->bind_param('i', $lastAbsenId);
  $stmtA->execute();
  $resA = $stmtA->get_result();

  $countAbsen = 0;
  $maxAbsenId = $lastAbsenId;

  $sqlUpAbsen = "INSERT INTO absensi_absen
    (absen_id, user_id, tanggal, lokasi_id, jam_masuk, jam_toleransi, jam_pulang, absen_in, absen_out,
     foto_in, foto_out, status_masuk, status_pulang, map_in, map_out, kehadiran, radius, radius_out, keterangan)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ON DUPLICATE KEY UPDATE
      user_id=VALUES(user_id), tanggal=VALUES(tanggal), lokasi_id=VALUES(lokasi_id),
      jam_masuk=VALUES(jam_masuk), jam_toleransi=VALUES(jam_toleransi), jam_pulang=VALUES(jam_pulang),
      absen_in=VALUES(absen_in), absen_out=VALUES(absen_out),
      foto_in=VALUES(foto_in), foto_out=VALUES(foto_out),
      status_masuk=VALUES(status_masuk), status_pulang=VALUES(status_pulang),
      map_in=VALUES(map_in), map_out=VALUES(map_out),
      kehadiran=VALUES(kehadiran), radius=VALUES(radius), radius_out=VALUES(radius_out),
      keterangan=VALUES(keterangan)";
  $stmtUpA = $dbS->prepare($sqlUpAbsen);
  if (!$stmtUpA) throw new RuntimeException("Prepare upsert absen SDS gagal: ".$dbS->error);

  while ($row = $resA->fetch_assoc()) {
    $countAbsen++;
    $aid = (int)$row['absen_id'];
    if ($aid > $maxAbsenId) $maxAbsenId = $aid;

    $uid = (int)$row['user_id'];
    $lokasi_id = isset($row['lokasi_id']) ? (int)$row['lokasi_id'] : null;

    // bind 19 kolom
    $stmtUpA->bind_param(
      'iisiissssssssssssss',
      $aid,
      $uid,
      $row['tanggal'],
      $lokasi_id,
      $row['jam_masuk'],
      $row['jam_toleransi'],
      $row['jam_pulang'],
      $row['absen_in'],
      $row['absen_out'],
      $row['foto_in'],
      $row['foto_out'],
      $row['status_masuk'],
      $row['status_pulang'],
      $row['map_in'],
      $row['map_out'],
      $row['kehadiran'],
      $row['radius'],
      $row['radius_out'],
      $row['keterangan']
    );
    $stmtUpA->execute();
  }
  $stmtA->close();
  $stmtUpA->close();

  if ($countAbsen > 0) {
    $state['last_absen_id'] = $maxAbsenId;
    log_line($config['log_file'], "SYNC ABSEN: {$countAbsen} rows, last_absen_id={$maxAbsenId}");
  } else {
    log_line($config['log_file'], "SYNC ABSEN: 0 rows");
  }

  // simpan state
  save_state($config['state_file'], $state);

  // output
  respond("Selesai. user={$countUser} (last_user_id={$state['last_user_id']}), absen={$countAbsen} (last_absen_id={$state['last_absen_id']})", true);
  log_line($config['log_file'], "DONE user={$countUser} absen={$countAbsen}");

} catch (Throwable $e) {
  $msg = $e->getMessage();
  respond($msg, false);
  log_line($config['log_file'], "ERROR ".$msg);
} finally {
  release_lock($lockFp);
}
