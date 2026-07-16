<?php
require '_config.php';
requireAuth();

$id_siswa = (int)($_SESSION['id_siswa'] ?? 0);
if ($id_siswa <= 0) {
  response(false, 'Session tidak valid');
}

$action = strtolower(trim($_POST['action'] ?? 'toggle')); // lock | unlock | toggle

// ambil status saat ini
$q = mysqli_query($conn, "SELECT blokir, rfid_uid FROM pendaftaran_siswa WHERE id=$id_siswa LIMIT 1");
if (!$q) response(false, 'Query gagal', ['db_error'=>mysqli_error($conn)]);
$row = mysqli_fetch_assoc($q);
if (!$row) response(false, 'Data siswa tidak ditemukan');

$blokir_now = (int)$row['blokir'];

if ($action === 'lock') {
  $blokir_new = 1;
} elseif ($action === 'unlock') {
  $blokir_new = 0;
} else { // toggle
  $blokir_new = $blokir_now ? 0 : 1;
}

$u = mysqli_query($conn, "UPDATE pendaftaran_siswa SET blokir=$blokir_new WHERE id=$id_siswa");
if (!$u) {
  response(false, 'Gagal memperbarui status kartu', ['db_error'=>mysqli_error($conn)]);
}

response(true, 'Status kartu diperbarui', [
  'blokir' => $blokir_new,
  'rfid_uid' => $row['rfid_uid'] ?? null
]);
