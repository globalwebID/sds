<?php
require_once __DIR__ . '/../../config/runtime.php';
sds_session_start();
require_once __DIR__ . '/../../config/db.php';
if (empty($_SESSION['admin_id']) || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Permintaan tidak valid.'); }
$id_siswa     = intval($_POST['id_siswa'] ?? 0);
$nama_berkas  = trim($_POST['nama_berkas']);
$tahun_ajaran = (string)($_POST['tahun_ajaran'] ?? '');

if ($id_siswa <= 0 || empty($nama_berkas) || empty($tahun_ajaran)) {
    die("Data tidak lengkap.");
}

// Ambil data siswa untuk validasi
$q = $conn->prepare("SELECT id,nisn FROM pendaftaran_siswa WHERE id = ? LIMIT 1");
$q->bind_param("i", $id_siswa);
$q->execute();
$student = $q->get_result()->fetch_assoc();
if (!$student) {
    die("Data siswa tidak valid.");
}
$q->close();
$nisn = preg_replace('/[^A-Za-z0-9_.-]/', '_', (string)($student['nisn'] ?: $id_siswa));
$tahun_ajaran = preg_replace('/[^A-Za-z0-9_.-]/', '_', $tahun_ajaran);

// File upload validasi
$allowed = ['pdf', 'jpg', 'jpeg', 'png'];
$maxSize = 10 * 1024 * 1024; // 10MB
$uploadDir = dirname(__DIR__, 2) . "/uploads/$tahun_ajaran/$nisn/berkas_tambahan/";

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] != 0) {
    die("Gagal upload file.");
}

try { $ext = sds_validate_upload($_FILES['file'], $allowed, $maxSize); }
catch (Throwable $e) { http_response_code(400); exit($e->getMessage()); }

// Simpan file
$uniqueName = uniqid('berkas_') . '.' . $ext;
$destination = $uploadDir . $uniqueName;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
    die("Gagal menyimpan file.");
}

// Simpan ke database
$stmt = $conn->prepare("INSERT INTO berkas_tambahan (id_siswa, nama_berkas, file) VALUES (?, ?, ?)");
$stmt->bind_param("iss", $id_siswa, $nama_berkas, $uniqueName);
if ($stmt->execute()) {
    $_SESSION['success'] = "Berkas Berhasil Ditambahkan.";
    header("Location: student_view?id=$id_siswa#berkas");
} else {
    @unlink($destination);
    $_SESSION['error'] = "Berkas gagal ditambahkan";
    header("Location: student_view?id=$id_siswa#berkas");
}
$stmt->close();
