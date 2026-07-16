<?php
require_once __DIR__ . '/../../config/runtime.php';
sds_session_start();
if (empty($_SESSION['admin_id'])) {
  http_response_code(401);
  exit('Akses ditolak');
}
// 1) WAJIB: include koneksi (sesuaikan file koneksi SDS Anda)
require_once __DIR__ . '/../../config/db.php'; // <-- UBAH sesuai project Anda
if (!sds_csrf_verify((string)($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
  http_response_code(419);
  exit('Sesi formulir tidak valid');
}

// 2) Guard untuk test via browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Content-Type: text/plain; charset=utf-8');
  exit('Endpoint upload foto OK. Gunakan POST multipart (foto,id_siswa,tahun_ajaran,nisn).');
}

$id_siswa     = intval($_POST['id_siswa'] ?? 0);
if (!$id_siswa || !isset($_FILES['foto'])) {
  http_response_code(400);
  exit('Data tidak lengkap');
}

$q = $conn->prepare('SELECT foto,nisn FROM pendaftaran_siswa WHERE id=? LIMIT 1');
$q->bind_param('i', $id_siswa);
$q->execute();
$student = $q->get_result()->fetch_assoc();
$q->close();
if (!$student) {
  http_response_code(404);
  exit('Siswa tidak ditemukan');
}
$subfolder = preg_replace('/[^0-9A-Za-z_-]/', '_', (string)($student['nisn'] ?: $id_siswa));

// Upload dir (relative terhadap lokasi file ini!)
$uploadDir = __DIR__ . "/../../uploads/$subfolder/";  // <-- lebih aman daripada "../uploads/.."
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$file = $_FILES['foto'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['jpg','jpeg','png'];

if (!in_array($ext, $allowed, true)) {
  http_response_code(415);
  exit('Format tidak didukung');
}
if ($file['size'] > 5 * 1024 * 1024) {
  http_response_code(413);
  exit('Ukuran maksimal 5MB');
}
if ((int)$file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
  http_response_code(400);
  exit('Upload tidak valid');
}
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
$mimeMap = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png'];
if (($mimeMap[$ext] ?? '') !== $mime) {
  http_response_code(415);
  exit('Isi file tidak sesuai format gambar');
}

$namaFile = "edit_" . uniqid() . '.' . $ext;
$namaBaru = $subfolder . '/' . $namaFile; // disimpan ke DB
$pathBaru = $uploadDir . $namaFile;       // lokasi file fisik

$foto_lama = (string)($student['foto'] ?? '');

if ($foto_lama) {
  $uploadsRoot = dirname(__DIR__, 2) . '/uploads/';
  $oldRelative = ltrim(str_replace('\\', '/', $foto_lama), '/');
  $oldPath = realpath($uploadsRoot . $oldRelative);
  $rootReal = realpath($uploadsRoot);
  if ($oldPath && $rootReal && str_starts_with($oldPath, $rootReal . DIRECTORY_SEPARATOR) && is_file($oldPath)) @unlink($oldPath);
}

// Simpan file baru
if (move_uploaded_file($file['tmp_name'], $pathBaru)) {
  $stmt = $conn->prepare("UPDATE pendaftaran_siswa SET foto = ? WHERE id = ? LIMIT 1");
  $stmt->bind_param("si", $namaBaru, $id_siswa);
  if ($stmt->execute()) {
    echo "Upload berhasil";
  } else {
    error_log('[SDS upload foto] ' . $stmt->error);
    @unlink($pathBaru);
    http_response_code(500);
    echo 'Gagal menyimpan foto';
  }
  $stmt->close();
} else {
  http_response_code(500);
  echo "Gagal upload file";
}
