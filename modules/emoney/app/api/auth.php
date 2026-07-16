<?php
require '_config.php';

/* ===============================
   VALIDASI METHOD
================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(false, 'Method tidak diizinkan');
}

/* ===============================
   AMBIL & SANITASI INPUT
================================ */
$nisn = trim((string)($_POST['nisn'] ?? ''));
$pin  = trim((string)($_POST['pin'] ?? ''));

/* ===============================
   VALIDASI INPUT
================================ */
if ($nisn === '' || $pin === '') {
    response(false, 'NISN dan PIN wajib diisi');
}

if (!preg_match('/^[0-9]{6}$/', $pin)) {
    response(false, 'PIN harus 6 digit angka');
}

$retryAfter = sds_rate_limit_check('emoney_login', $nisn);
if ($retryAfter > 0) {
    http_response_code(429);
    header('Retry-After: ' . $retryAfter);
    response(false, 'Terlalu banyak percobaan. Coba lagi dalam ' . $retryAfter . ' detik');
}

/* ===============================
   QUERY LOGIN
================================ */
$stmt = $conn->prepare('SELECT id, nisn, nama_lengkap, pin FROM pendaftaran_siswa WHERE nisn=? LIMIT 1');
$stmt->bind_param('s', $nisn);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa || !emoneyVerifyPin($pin, $siswa['pin'] ?? null)) {
    sds_rate_limit_fail('emoney_login', $nisn);
    response(false, 'NISN atau PIN salah');
}

if (password_get_info((string)$siswa['pin'])['algo'] === null && emoneyEnsurePinHashSchema($conn)) {
    $hash = password_hash($pin, PASSWORD_DEFAULT);
    $upgrade = $conn->prepare('UPDATE pendaftaran_siswa SET pin=? WHERE id=? AND pin=?');
    $studentId = (int)$siswa['id'];
    $oldPin = (string)$siswa['pin'];
    $upgrade->bind_param('sis', $hash, $studentId, $oldPin);
    $upgrade->execute();
    $upgrade->close();
}

/* ===============================
   SET SESSION (MINIMAL & AMAN)
================================ */
session_regenerate_id(true);
sds_rate_limit_clear('emoney_login', $nisn);
$_SESSION['login']    = true;
$_SESSION['id_siswa'] = (int)$siswa['id'];
$_SESSION['nisn']     = $siswa['nisn'];
$_SESSION['nama']     = $siswa['nama_lengkap'];

/* ===============================
   RESPONSE
================================ */
response(true, 'Login berhasil', [
    'nama' => $siswa['nama_lengkap'],
    'nisn' => $siswa['nisn']
]);
