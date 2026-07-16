<?php
// api/_config.php
require_once dirname(__DIR__, 2) . '/config/runtime.php';
sds_require_installed();
ini_set('display_errors', '0');
sds_session_start();
date_default_timezone_set((string)sds_config('app.timezone', 'Asia/Jakarta'));

/* ===============================
   HEADERS
   =============================== */
header('Content-Type: application/json; charset=utf-8');

// Jika hanya dipakai dari domain yang sama, boleh hapus CORS sama sekali.
// Tapi ini versi aman + future-proof (bisa whitelist origin).
$configuredOrigin = (string)(parse_url((string)sds_config('app.base_url', ''), PHP_URL_SCHEME) ?: 'http')
  . '://' . (string)(parse_url((string)sds_config('app.base_url', ''), PHP_URL_HOST) ?: 'localhost');
$configuredPort = parse_url((string)sds_config('app.base_url', ''), PHP_URL_PORT);
if ($configuredPort) $configuredOrigin .= ':' . $configuredPort;
$allowedOrigins = [$configuredOrigin];

if (!empty($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins, true)) {
  header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
  header('Vary: Origin');
} else {
  // fallback ke domain utama (jangan pakai "*")
  header('Access-Control-Allow-Origin: ' . $configuredOrigin);
}

header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

/* ===============================
   KONEKSI DATABASE
   =============================== */

try {
  $conn = sds_mysqli('main');
} catch (Throwable $e) {
  error_log('[SDS API main DB] ' . $e->getMessage());
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Koneksi database SISWA gagal']);
  exit;
}
sds_apply_central_controls($conn, 'E-Money API');

$conn_absen = null;
try { $conn_absen = sds_mysqli('attendance'); }
catch (Throwable $e) { error_log('[SDS API attendance DB] ' . $e->getMessage()); }

$endpoint = basename((string)($_SERVER['SCRIPT_FILENAME'] ?? ''));
if ($conn_absen === null && (str_starts_with($endpoint, 'absensi') || $endpoint === 'api_absen_readonly.php')) {
  http_response_code(503); echo json_encode(['success'=>false,'message'=>'Integrasi database absensi belum dikonfigurasi']); exit;
}

if (str_starts_with($endpoint, 'perpustakaan_')) {
  $perpusConfig = dirname(__DIR__, 2) . '/config/perpus.php';
  if (!sds_modules()->isEnabled('library') || !is_file($perpusConfig)) {
    http_response_code(503);
    echo json_encode(['success'=>false,'message'=>'Modul Perpustakaan tidak terpasang atau sedang dinonaktifkan.']);
    exit;
  }
  require_once $perpusConfig;
  try {
    sds_perpus_ensure_schema($conn);
  } catch (Throwable $e) {
    error_log('[SDS API integrated library] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Modul Perpustakaan SDS belum siap. Buka menu Perpustakaan sebagai admin terlebih dahulu.']);
    exit;
  }
}

function integratedLibraryStudent(mysqli $conn, int $studentId): array {
  if ($studentId <= 0) response(false, 'Session id_siswa tidak valid');
  $stmt = $conn->prepare("SELECT ps.id,ps.nama_lengkap,ps.nisn,ps.nipd,
    COALESCE(kr.uid,'') rfid_uid,COALESCE(k.nama_kelas,'-') nama_kelas
    FROM pendaftaran_siswa ps
    LEFT JOIN kartu_rfid kr ON kr.pemilik_tipe='siswa' AND kr.pemilik_id=ps.id
    LEFT JOIN siswa_kelas sk ON sk.id=(SELECT sk2.id FROM siswa_kelas sk2 WHERE sk2.siswa_id=ps.id ORDER BY sk2.tahun_ajaran DESC,sk2.id DESC LIMIT 1)
    LEFT JOIN kelas k ON k.id=sk.kelas_id
    WHERE ps.id=? LIMIT 1");
  $stmt->bind_param('i', $studentId);
  $stmt->execute();
  $student = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  if (!$student) response(false, 'Data siswa tidak ditemukan');
  $member = sds_perpus_ensure_member($conn, 'siswa', $studentId, true);
  if (($member['status_keanggotaan'] ?? '') !== 'aktif') {
    response(false, 'Keanggotaan Perpustakaan sedang tidak aktif');
  }
  return ['student'=>$student,'member'=>$member];
}

function integratedLibraryStatus(array $row): string {
  $status = (string)($row['status_buku'] ?? $row['status_transaksi'] ?? '');
  if ($status === 'dipinjam') {
    $due = (string)($row['tgl_kembali'] ?? '');
    return ($due !== '' && $due < date('Y-m-d')) ? 'Terlambat' : 'Belum Kembali';
  }
  if ($status === 'kembali' || $status === 'selesai') return 'Sudah Kembali';
  if ($status === 'hilang') return 'Hilang';
  if ($status === 'rusak') return 'Rusak';
  return $status !== '' ? ucfirst($status) : '-';
}

/* ===============================
   HELPER RESPONSE
   =============================== */
function response($success, $message = '', $data = null) {
  if (is_array($data)) {
    $sanitize = function (array $value) use (&$sanitize): array {
      unset($value['db_error'], $value['sql'], $value['query'], $value['exception']);
      foreach ($value as $key => $item) if (is_array($item)) $value[$key] = $sanitize($item);
      return $value;
    };
    $data = $sanitize($data);
  }
  echo json_encode([
    'success' => $success,
    'message' => $message,
    'data'    => $data
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ===============================
   AUTH CHECK (kompatibel)
   =============================== */
function requireAuth() {
  $ok = (!empty($_SESSION['login']) && $_SESSION['login'] === true) || !empty($_SESSION['id_siswa']);
  if (!$ok) {
    http_response_code(401);
    response(false, 'Unauthorized');
  }
}

function requireCsrf(): void {
  $token = (string)($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
  if (empty($_SESSION['emoney_csrf']) || !hash_equals((string)$_SESSION['emoney_csrf'], $token)) {
    http_response_code(419);
    response(false, 'Sesi formulir tidak valid. Muat ulang halaman.');
  }
}

function emoneyEnsurePinHashSchema(mysqli $conn): bool {
  static $ready = null;
  if ($ready !== null) return $ready;
  try {
    $result = $conn->query("SHOW COLUMNS FROM pendaftaran_siswa LIKE 'pin'");
    $column = $result ? $result->fetch_assoc() : null;
    if (!$column) return $ready = false;
    if (preg_match('/varchar\\((\\d+)\\)/i', (string)$column['Type'], $match) && (int)$match[1] < 255) {
      $conn->query("ALTER TABLE pendaftaran_siswa MODIFY pin VARCHAR(255) NULL");
    }
    return $ready = true;
  } catch (Throwable $e) {
    error_log('[SDS e-money PIN schema] ' . $e->getMessage());
    return $ready = false;
  }
}

function emoneyVerifyPin(string $plainPin, ?string $storedPin): bool {
  $storedPin = (string)$storedPin;
  if ($storedPin === '') return false;
  if (password_get_info($storedPin)['algo'] !== null) return password_verify($plainPin, $storedPin);
  return hash_equals($storedPin, $plainPin);
}

function emoneyHashPin(mysqli $conn, string $plainPin): string {
  return emoneyEnsurePinHashSchema($conn) ? password_hash($plainPin, PASSWORD_DEFAULT) : $plainPin;
}
