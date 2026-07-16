<?php
if (empty($_SESSION['admin_id']) || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Permintaan tidak valid.'); }
$id           = intval($_POST['id']);
$id_siswa     = intval($_POST['id_siswa']);
$file         = $_POST['file'] ?? '';
$tahun_ajaran = $_POST['tahun_ajaran'] ?? '';
$nisn         = $_POST['nisn'] ?? '';


if ($id <= 0 || empty($id_siswa) || empty($file) || empty($tahun_ajaran) || empty($nisn)) {
    die("Data tidak lengkap.");
}

$file = basename(str_replace('\\', '/', $file));
$path = dirname(__DIR__, 2) . "/uploads/" . preg_replace('/[^A-Za-z0-9_.-]/', '_', $tahun_ajaran) . '/' . preg_replace('/[^A-Za-z0-9_.-]/', '_', $nisn) . "/berkas_tambahan/$file";
if (file_exists($path)) {
    unlink($path);
}

$stmt = $conn->prepare("DELETE FROM berkas_tambahan WHERE id = ?");
$stmt->bind_param("i", $id);
if ($stmt->execute()) {
    $_SESSION['success'] = "Berkas Berhasil Dihapus.";
    header("Location: student_view?id=$id_siswa#berkas");
    $stmt->close();
    exit;
} else {
    $_SESSION['error'] = "Berkas Gagal Dihapus.";
    header("Location: student_view?id=$id_siswa#berkas");
    $stmt->close();
    exit;
}
