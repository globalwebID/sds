<?php
if (empty($_SESSION['admin_id']) || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) {
    http_response_code(419); exit('Permintaan penghapusan tidak valid.');
}
$id = (int)($_POST['id'] ?? 0);
$tahun = (string)($_POST['tahun'] ?? '');

// Cek apakah ID valid
$kelasResult = $conn->query("SELECT * FROM kelas WHERE id = $id");
$kelas = $kelasResult->fetch_assoc();

if (!$kelas) {
    $_SESSION['error'] = "Data tidak ditemukan.";
    header("Location: kuota_kelas");
    exit;
}

// Cek apakah ada siswa yang memakai kuota_kelas_id ini di pendaftaran_siswa
$cek = $conn->prepare("SELECT COUNT(*) as jumlah FROM pendaftaran_siswa WHERE kelas_id = ?");
$cek->bind_param("i", $id);
$cek->execute();
$cekResult = $cek->get_result()->fetch_assoc();

if ($cekResult['jumlah'] > 0) {
    $_SESSION['error'] = "<strong>Tidak bisa menghapus.</strong> Masih ada <strong>{$cekResult['jumlah']}</strong> siswa yang menggunakan kelas <strong>{$kelas['nama_kelas']}</strong> tahun ajaran <strong>{$kelas['tahun_ajaran']}</strong>.";
    header("Location: kuota_kelas");
    exit;
}

// ✅ Hapus relasi siswa_kelas terlebih dahulu
$stmtDelRelasi = $conn->prepare("DELETE FROM siswa_kelas WHERE kelas_id = ?");
$stmtDelRelasi->bind_param("i", $id);
$stmtDelRelasi->execute();
$stmtDelRelasi->close();

// ✅ Hapus kelas
$stmtDelete = $conn->prepare("DELETE FROM kelas WHERE id = ?");
$stmtDelete->bind_param("i", $id);
$stmtDelete->execute();
$stmtDelete->close();

// ✅ Catat log aktivitas
if (isset($_SESSION['admin_id'])) {
    $keterangan = "Menghapus kelas '{$kelas['nama_kelas']}' tahun ajaran '{$kelas['tahun_ajaran']}'";
    catatLog($conn, $_SESSION['admin_id'], 'Hapus Kelas', $keterangan);
}

$_SESSION['success'] = "Kelas <strong>{$kelas['nama_kelas']}</strong> berhasil dihapus.";
header("Location: kuota_kelas?tahun=" . urlencode($tahun));
exit;
