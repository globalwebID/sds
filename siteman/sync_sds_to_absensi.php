<?php
require_once __DIR__ . '/../config/runtime.php';
sds_require_installed();
$runtimeMain = sds_database_config('main');
$runtimeAttendance = sds_database_config('attendance');
/**
 * sync_sds_to_absensi.php
 * Sync DB SDS -> DB Absensi
 *
 * Sumber (SDS):
 *   DB  : dikonfigurasi melalui installer
 *   Tbl : pendaftaran_siswa
 *
 * Target (ABSENSI):
 *   DB  : dikonfigurasi melalui installer
 *   Tbl : user
 *
 * Jalankan via cron (disarankan) atau via web ?token=...
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Jakarta');

// =========================
// KONFIGURASI
// =========================
$config = [
  'sds' => [
    'host' => $runtimeMain['host'],
    'user' => $runtimeMain['username'],
    'pass' => $runtimeMain['password'],
    'db'   => $runtimeMain['database'],
    'charset' => 'utf8mb4',
  ],
  'absen' => [
    'host' => $runtimeAttendance['host'],
    'user' => $runtimeAttendance['username'],
    'pass' => $runtimeAttendance['password'],
    'db'   => $runtimeAttendance['database'],
    'charset' => 'utf8mb4',
  ],

  'allow_web' => true,
  'token' => (string)sds_config('security.sync_token', ''),

  'batch' => 2000,

  'state_file' => __DIR__ . '/sync_sds_to_absensi.state.json',
  'lock_file'  => __DIR__ . '/sync_sds_to_absensi.lock',
  'log_file'   => __DIR__ . '/sync_sds_to_absensi.log',

  // Jika di Absensi field RFID bernama "rfid" (sesuai script Anda), dan di SDS "rfid_uid"
  'map' => [
    'sds_id'    => 'id',            // PK pendaftaran_siswa
    'nisn'      => 'nisn',
    'nama'      => 'nama_lengkap',
    'kelas'     => 'kelas',         // sesuaikan kalau di SDS berbeda (mis: nama_kelas)
    'email'     => 'email',         // optional
    'rfid_uid'  => 'rfid_uid',      // optional
  ],

  // Default untuk user Absensi jika field tertentu kosong
  'defaults' => [
    'status' => 'Aktif',
    'active' => 'Y',
  ],
];

// =========================
// UTIL
// =========================
function respond($msg, $ok=true) {
  echo ($ok ? "[OK] " : "[ERR] ") . $msg . "\n";
}
function log_line($file, $line) {
  @file_put_contents($file, '['.date('Y-m-d H:i:s').'] '.$line."\n", FILE_APPEND);
}
function connect_mysqli($cfg) {
  $mysqli = @new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['db']);
  if ($mysqli->connect_error) {
    throw new RuntimeException("Koneksi gagal {$cfg['user']}@{$cfg['host']} DB {$cfg['db']}: ".$mysqli->connect_error);
  }
  @$mysqli->set_charset($cfg['charset'] ?? 'utf8mb4');
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
  if ($fp) { @flock($fp, LOCK_UN); @fclose($fp); }
}
function load_state($file) {
  if (!file_exists($file)) return ['last_pendaftaran_id' => 0];
  $raw = @file_get_contents($file);
  $j = json_decode((string)$raw, true);
  if (!is_array($j)) return ['last_pendaftaran_id' => 0];
  return array_merge(['last_pendaftaran_id'=>0], $j);
}
function save_state($file, $state) {
  @file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
}
function is_cli() { return (php_sapi_name() === 'cli'); }

// =========================
// SECURITY: web token
// =========================
if (!$config['allow_web'] && !is_cli()) {
  http_response_code(403); echo "Forbidden\n"; exit;
}
if (!is_cli()) {
  $t = $_GET['token'] ?? '';
  if ($t !== $config['token']) { http_response_code(403); echo "Forbidden\n"; exit; }
  header('Content-Type: text/plain; charset=UTF-8');
}

// =========================
// MAIN
// =========================
[$locked, $lockFp] = acquire_lock($config['lock_file']);
if (!$locked) { respond("Lock aktif. Sync sedang berjalan / baru saja berjalan.", false); exit; }

$state = load_state($config['state_file']);
log_line($config['log_file'], "START last_pendaftaran_id={$state['last_pendaftaran_id']}");

try {
  $dbS = connect_mysqli($config['sds']);   // SDS
  $dbA = connect_mysqli($config['absen']); // Absensi

  $lastId = (int)$state['last_pendaftaran_id'];
  $batch  = (int)$config['batch'];

  $m = $config['map'];

  // Ambil data baru dari SDS berdasarkan PK incremental
  $sql = "SELECT *
          FROM pendaftaran_siswa
          WHERE {$m['sds_id']} > ?
          ORDER BY {$m['sds_id']} ASC
          LIMIT {$batch}";
  $stmt = $dbS->prepare($sql);
  if (!$stmt) throw new RuntimeException("Prepare SDS gagal: ".$dbS->error);
  $stmt->bind_param('i', $lastId);
  $stmt->execute();
  $res = $stmt->get_result();

  $count = 0;
  $maxId = $lastId;

  /**
   * Upsert ke Absensi berdasarkan nisn (UNIQUE).
   * Kolom target Absensi menyesuaikan tabel user Anda:
   * - user_id (auto) tidak kita isi.
   * - rfid kolomnya "rfid" (sesuai script Anda).
   */
  $sqlUp = "INSERT INTO `user`
    (nisn, rfid, email, nama_lengkap, kelas, tanggal_registrasi, status, active)
    VALUES (?,?,?,?,?, NOW(), ?, ?)
    ON DUPLICATE KEY UPDATE
      rfid=VALUES(rfid),
      email=VALUES(email),
      nama_lengkap=VALUES(nama_lengkap),
      kelas=VALUES(kelas),
      status=VALUES(status),
      active=VALUES(active)";
  $up = $dbA->prepare($sqlUp);
  if (!$up) throw new RuntimeException("Prepare upsert Absensi gagal: ".$dbA->error);

  while ($row = $res->fetch_assoc()) {
    $count++;
    $sid = (int)$row[$m['sds_id']];
    if ($sid > $maxId) $maxId = $sid;

    $nisn = (string)($row[$m['nisn']] ?? '');
    $nama = (string)($row[$m['nama']] ?? '');
    $kelas = (string)($row[$m['kelas']] ?? '');
    $email = (string)($row[$m['email']] ?? '');
    $rfid = (string)($row[$m['rfid_uid']] ?? ''); // SDS rfid_uid -> Absensi rfid

    // Skip jika tidak ada NISN (karena kunci unik kita pakai NISN)
    if ($nisn === '') continue;

    $status = $config['defaults']['status'];
    $active = $config['defaults']['active'];

    $up->bind_param('sssssss', $nisn, $rfid, $email, $nama, $kelas, $status, $active);
    $up->execute();
  }

  $stmt->close();
  $up->close();

  if ($count > 0) {
    $state['last_pendaftaran_id'] = $maxId;
    save_state($config['state_file'], $state);
    log_line($config['log_file'], "SYNC: {$count} rows, last_pendaftaran_id={$maxId}");
  } else {
    log_line($config['log_file'], "SYNC: 0 rows");
  }

  respond("Selesai. rows={$count} (last_pendaftaran_id={$state['last_pendaftaran_id']})", true);
  log_line($config['log_file'], "DONE rows={$count}");

} catch (Throwable $e) {
  respond($e->getMessage(), false);
  log_line($config['log_file'], "ERROR ".$e->getMessage());
} finally {
  release_lock($lockFp);
}
