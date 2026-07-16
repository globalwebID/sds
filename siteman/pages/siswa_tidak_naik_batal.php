<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: siswa_tidak_naik');
    exit;
}

$siswaId = isset($_POST['siswa_id']) ? (int) $_POST['siswa_id'] : 0;
$kelasId = isset($_POST['kelas_id']) ? (int) $_POST['kelas_id'] : 0;
$tahunAjaran = trim((string) ($_POST['tahun_ajaran'] ?? ''));

if ($siswaId <= 0 || $kelasId <= 0 || $tahunAjaran === '') {
    $_SESSION['error'] = 'Data batal tidak naik tidak lengkap.';
    header('Location: siswa_tidak_naik');
    exit;
}

$stmtCek = $conn->prepare("SELECT id FROM siswa_kelas WHERE siswa_id = ? AND kelas_id = ? AND tahun_ajaran = ? AND naik_kelas = 0 LIMIT 1");
if (!$stmtCek) {
    $_SESSION['error'] = 'Query cek siswa gagal: ' . $conn->error;
    header('Location: siswa_tidak_naik');
    exit;
}
$stmtCek->bind_param('iis', $siswaId, $kelasId, $tahunAjaran);
$stmtCek->execute();
$ada = $stmtCek->get_result()->fetch_assoc();
$stmtCek->close();

if (!$ada) {
    $_SESSION['error'] = 'Data siswa tidak naik pada tahun ajaran aktif tidak ditemukan, atau statusnya sudah dibatalkan.';
    header('Location: siswa_tidak_naik');
    exit;
}

$stmt = $conn->prepare("UPDATE siswa_kelas SET naik_kelas = 1 WHERE siswa_id = ? AND kelas_id = ? AND tahun_ajaran = ?");
if (!$stmt) {
    $_SESSION['error'] = 'Query batal tidak naik gagal: ' . $conn->error;
    header('Location: siswa_tidak_naik');
    exit;
}
$stmt->bind_param('iis', $siswaId, $kelasId, $tahunAjaran);
if ($stmt->execute()) {
    $stmt->close();

    $stmtPs = $conn->prepare("UPDATE pendaftaran_siswa SET kelas_id = ? WHERE id = ?");
    if ($stmtPs) {
        $stmtPs->bind_param('ii', $kelasId, $siswaId);
        $stmtPs->execute();
        $stmtPs->close();
    }

    $stmtSync = $conn->prepare("UPDATE kelas k SET terisi = (SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id = k.id AND sk.tahun_ajaran = k.tahun_ajaran) WHERE k.id = ?");
    if ($stmtSync) {
        $stmtSync->bind_param('i', $kelasId);
        $stmtSync->execute();
        $stmtSync->close();
    }

    $_SESSION['success'] = 'Status tidak naik kelas berhasil dibatalkan. Siswa kembali menjadi siswa naik kelas aktif.';
} else {
    $_SESSION['error'] = 'Gagal membatalkan status tidak naik: ' . $stmt->error;
    $stmt->close();
}

header('Location: siswa_tidak_naik');
exit;
?>
