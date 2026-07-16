<?php
require '_config.php';
requireAuth();
requireCsrf();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  response(false, 'Method tidak diizinkan');
}

@mysqli_set_charset($conn, 'utf8mb4');

$id_siswa   = (int)($_SESSION['id_siswa'] ?? 0);
$pin_lama   = trim((string)($_POST['pin_lama'] ?? ''));
$pin_baru   = trim((string)($_POST['pin_baru'] ?? ''));
$pin_konfir = trim((string)($_POST['pin_konfirmasi'] ?? ''));

if ($id_siswa <= 0) response(false, 'Session tidak valid');

// Validasi
if ($pin_lama === '' || $pin_baru === '' || $pin_konfir === '') {
  response(false, 'Semua field wajib diisi');
}
if (!preg_match('/^[0-9]{6}$/', $pin_lama)) {
  response(false, 'PIN lama harus 6 digit angka');
}
if (!preg_match('/^[0-9]{6}$/', $pin_baru)) {
  response(false, 'PIN baru harus 6 digit angka');
}
if ($pin_baru !== $pin_konfir) {
  response(false, 'Konfirmasi PIN tidak sama');
}
if ($pin_baru === $pin_lama) {
  response(false, 'PIN baru tidak boleh sama dengan PIN lama');
}

// Cek PIN lama; mendukung data legacy plaintext dan hash baru.
$stmt = mysqli_prepare($conn, "SELECT id,pin FROM pendaftaran_siswa WHERE id=? LIMIT 1");
if(!$stmt) response(false, 'Prepare cek PIN gagal', ['db_error'=>mysqli_error($conn)]);

mysqli_stmt_bind_param($stmt, "i", $id_siswa);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$student = $res ? mysqli_fetch_assoc($res) : null;

if(!$student || !emoneyVerifyPin($pin_lama, $student['pin'] ?? null)){
  response(false, 'PIN lama salah');
}

// Update PIN baru
$pin_baru_tersimpan = emoneyHashPin($conn, $pin_baru);
$stmt2 = mysqli_prepare($conn, "UPDATE pendaftaran_siswa SET pin=? WHERE id=? LIMIT 1");
if(!$stmt2) response(false, 'Prepare update PIN gagal', ['db_error'=>mysqli_error($conn)]);

mysqli_stmt_bind_param($stmt2, "si", $pin_baru_tersimpan, $id_siswa);

if(!mysqli_stmt_execute($stmt2)){
  response(false, 'Gagal mengubah PIN', ['db_error'=>mysqli_error($conn)]);
}

response(true, 'PIN berhasil diubah');
