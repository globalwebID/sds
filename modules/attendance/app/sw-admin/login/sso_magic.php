<?php
http_response_code(410);
exit('SSO Absensi dinonaktifkan. Gunakan sesi login SDS melalui menu Absensi.');

// sw-admin/login/magic.php
require_once __DIR__ . '/../../sw-library/sw-config.php';
include_once __DIR__ . '/../../sw-library/sw-function.php';

// =========================
// KONFIG SSO bersama dari installer SDS.
// =========================
$SSO_SECRET = (string)sds_config('security.sso_secret', '');
$TTL_MAX    = 120; // batas aman (opsional)
// =========================

$u     = isset($_GET['u']) ? (string)$_GET['u'] : '';
$exp   = isset($_GET['exp']) ? (int)$_GET['exp'] : 0;
$nonce = isset($_GET['nonce']) ? (string)$_GET['nonce'] : '';
$sig   = isset($_GET['sig']) ? (string)$_GET['sig'] : '';

if ($u === '' || $exp <= 0 || $nonce === '' || $sig === '') {
  http_response_code(400);
  exit('Parameter SSO tidak lengkap.');
}

$now = time();
if ($now > $exp) {
  http_response_code(401);
  exit('Link SSO sudah kadaluarsa.');
}
if (($exp - $now) > $TTL_MAX) {
  http_response_code(400);
  exit('TTL terlalu panjang.');
}

// verify signature
$payload  = $u.'|'.$exp.'|'.$nonce;
$expected = hash_hmac('sha256', $payload, $SSO_SECRET);
if (!hash_equals($expected, $sig)) {
  http_response_code(403);
  exit('Signature SSO tidak valid.');
}

// pastikan tabel nonce ada: sso_nonces (lihat SQL di bawah)
$stmt = $connection->prepare("SELECT id FROM sso_nonces WHERE nonce=? LIMIT 1");
$stmt->bind_param("s", $nonce);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
  http_response_code(409);
  exit('Link SSO sudah pernah digunakan.');
}
$stmt->close();

$stmt = $connection->prepare("INSERT INTO sso_nonces (nonce, exp, created_at) VALUES (?, ?, NOW())");
$stmt->bind_param("si", $nonce, $exp);
$stmt->execute();
$stmt->close();

// ambil admin (sesuai login normal: username/email)
if (filter_var($u, FILTER_VALIDATE_EMAIL)) {
  $q = $connection->prepare("SELECT admin_id, username, active FROM admin WHERE email=? LIMIT 1");
} else {
  $q = $connection->prepare("SELECT admin_id, username, active FROM admin WHERE username=? LIMIT 1");
}
$q->bind_param("s", $u);
$q->execute();
$admin = $q->get_result()->fetch_assoc();
$q->close();

if (!$admin) {
  http_response_code(404);
  exit('Admin tidak ditemukan.');
}
if (($admin['active'] ?? 'N') !== 'Y') {
  http_response_code(403);
  exit('Akun belum aktif.');
}

// set cookie persis seperti proses login absensi
$ADMIN_KEY = htmlentities(epm_encode($admin['admin_id']));
$KEY       = hash('sha256', $admin['username']);
$expired_cookie = time() + 60*60*24*7;

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

setcookie('ADMIN_KEY', $ADMIN_KEY, [
  'expires'  => $expired_cookie,
  'path'     => '/',
  'secure'   => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);
setcookie('KEY', $KEY, [
  'expires'  => $expired_cookie,
  'path'     => '/',
  'secure'   => $secure,
  'httponly' => true,
  'samesite' => 'Lax',
]);

// update status online (ikut gaya login normal)
$date = date('Y-m-d');
$time = date('H:i:s');
$dt = $date.' '.$time;
$up = $connection->prepare("UPDATE admin SET tanggal_login=?, time=?, status='Online' WHERE admin_id=?");
$up->bind_param("ssi", $dt, $dt, $admin['admin_id']);
$up->execute();
$up->close();

// redirect ke dashboard admin
header('Location: ../'); // dari /login/ kembali ke /sw-admin/
exit;
