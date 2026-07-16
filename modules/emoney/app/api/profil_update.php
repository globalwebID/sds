<?php
require '_config.php';
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  response(false, 'Method tidak diizinkan');
}

@mysqli_set_charset($conn, 'utf8mb4');

$id_siswa = (int)($_SESSION['id_siswa'] ?? 0);
if ($id_siswa <= 0) response(false, 'Session id_siswa tidak valid');

// Helper sanitize (string)
function s($v){ return trim((string)$v); }

// ===============================
// FIELD YANG BOLEH DIUPDATE SISWA
// (silakan tambah/kurangi sesuai kebijakan)
// ===============================
$allowed = [
  'nama_lengkap',
  'email',
  'tempat_lahir',
  'tanggal_lahir',
  'jenis_kelamin',
  'agama',
  'alamat',
  'desa',
  'kecamatan',
  'kota',
  'provinsi',
  'tempat_tinggal',
  'moda_transportasi',
  'hobi',
  'cita_cita',
  'nohp_siswa',
  'nohp_ortu',

  // data ortu/wali
  'nama_ayah','nik_ayah','tahun_lahir_ayah','pendidikan_ayah','pekerjaan_ayah','penghasilan_ayah',
  'nama_ibu','nik_ibu','tahun_lahir_ibu','pendidikan_ibu','pekerjaan_ibu','penghasilan_ibu',
  'nama_wali','nik_wali','tahun_lahir_wali','pendidikan_wali','pekerjaan_wali','penghasilan_wali',
];

// Ambil input yang ada
$payload = [];
foreach ($allowed as $k) {
  if (isset($_POST[$k])) {
    $payload[$k] = s($_POST[$k]);
  }
}

if (count($payload) === 0) {
  response(false, 'Tidak ada data yang dikirim');
}

// Validasi ringan
if (isset($payload['email']) && $payload['email'] !== '' && !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
  response(false, 'Format email tidak valid');
}
foreach (['nohp_siswa','nohp_ortu'] as $hp) {
  if (isset($payload[$hp]) && $payload[$hp] !== '' && !preg_match('/^[0-9+ ]{8,20}$/', $payload[$hp])) {
    response(false, 'Format nomor HP tidak valid');
  }
}
if (isset($payload['tanggal_lahir']) && $payload['tanggal_lahir'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $payload['tanggal_lahir'])) {
  response(false, 'Format tanggal lahir harus YYYY-MM-DD');
}
foreach (['nik','nik_ayah','nik_ibu','nik_wali'] as $nikKey) {
  if (isset($payload[$nikKey]) && $payload[$nikKey] !== '' && !preg_match('/^[0-9]{8,30}$/', $payload[$nikKey])) {
    response(false, "Format $nikKey tidak valid (hanya angka)");
  }
}

// ===============================
// Build query dinamis + prepared statement
// ===============================
$setParts = [];
$params = [];
$types = '';

foreach ($payload as $k => $v) {
  $setParts[] = "`$k`=?";
  $params[] = $v;
  $types .= 's';
}

$sql = "UPDATE pendaftaran_siswa SET ".implode(',', $setParts)." WHERE id=?";
$params[] = $id_siswa;
$types .= 'i';

$stmt = mysqli_prepare($conn, $sql);
if(!$stmt) response(false, 'Prepare update gagal', ['db_error'=>mysqli_error($conn)]);

mysqli_stmt_bind_param($stmt, $types, ...$params);

if(!mysqli_stmt_execute($stmt)) {
  response(false, 'Gagal menyimpan profil', ['db_error'=>mysqli_error($conn)]);
}

response(true, 'Profil berhasil diperbarui');
