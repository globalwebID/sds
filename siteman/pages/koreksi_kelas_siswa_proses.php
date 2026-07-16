<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: koreksi_kelas_siswa');
    exit;
}

$siswaId = isset($_POST['siswa_id']) ? (int) $_POST['siswa_id'] : 0;
$kelasBaruId = isset($_POST['kelas_id']) ? (int) $_POST['kelas_id'] : 0;
$tahunAjaran = trim((string) ($_POST['tahun_ajaran'] ?? ''));

if ($siswaId <= 0 || $kelasBaruId <= 0 || $tahunAjaran === '') {
    $_SESSION['error'] = 'Data koreksi kelas tidak lengkap.';
    header('Location: koreksi_kelas_siswa');
    exit;
}

$stmtKelas = $conn->prepare("SELECT id FROM kelas WHERE id = ? AND tahun_ajaran = ? LIMIT 1");
if (!$stmtKelas) {
    $_SESSION['error'] = 'Query kelas gagal: ' . $conn->error;
    header('Location: koreksi_kelas_siswa');
    exit;
}
$stmtKelas->bind_param('is', $kelasBaruId, $tahunAjaran);
$stmtKelas->execute();
$kelasAda = $stmtKelas->get_result()->fetch_assoc();
$stmtKelas->close();

if (!$kelasAda) {
    $_SESSION['error'] = 'Kelas tujuan tidak valid untuk tahun ajaran aktif.';
    header('Location: koreksi_kelas_siswa');
    exit;
}

$kelasLamaId = 0;
$stmtOld = $conn->prepare("SELECT kelas_id FROM siswa_kelas WHERE siswa_id = ? AND tahun_ajaran = ? LIMIT 1");
if ($stmtOld) {
    $stmtOld->bind_param('is', $siswaId, $tahunAjaran);
    $stmtOld->execute();
    $stmtOld->bind_result($kelasLamaId);
    $stmtOld->fetch();
    $stmtOld->close();
}

if ($kelasLamaId > 0) {
    $stmt = $conn->prepare("UPDATE siswa_kelas SET kelas_id = ?, naik_kelas = 1 WHERE siswa_id = ? AND tahun_ajaran = ?");
    if (!$stmt) {
        $_SESSION['error'] = 'Query update kelas gagal: ' . $conn->error;
        header('Location: koreksi_kelas_siswa');
        exit;
    }
    $stmt->bind_param('iis', $kelasBaruId, $siswaId, $tahunAjaran);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, naik_kelas) VALUES (?, ?, ?, 1)");
    if (!$stmt) {
        $_SESSION['error'] = 'Query tambah relasi kelas gagal: ' . $conn->error;
        header('Location: koreksi_kelas_siswa');
        exit;
    }
    $stmt->bind_param('iis', $siswaId, $kelasBaruId, $tahunAjaran);
    $ok = $stmt->execute();
    $err = $stmt->error;
    $stmt->close();
}

if ($ok) {
    $stmtPs = $conn->prepare("UPDATE pendaftaran_siswa SET kelas_id = ? WHERE id = ?");
    if ($stmtPs) {
        $stmtPs->bind_param('ii', $kelasBaruId, $siswaId);
        $stmtPs->execute();
        $stmtPs->close();
    }

    foreach (array_unique([(int)$kelasLamaId, (int)$kelasBaruId]) as $kid) {
        if ($kid <= 0) continue;
        $stmtSync = $conn->prepare("UPDATE kelas k SET terisi = (SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id = k.id AND sk.tahun_ajaran = k.tahun_ajaran) WHERE k.id = ?");
        if ($stmtSync) {
            $stmtSync->bind_param('i', $kid);
            $stmtSync->execute();
            $stmtSync->close();
        }
    }

    $_SESSION['success'] = 'Kelas siswa berhasil dikoreksi. Siswa sudah masuk ke kelas tujuan dan statusnya aktif/naik.';
} else {
    $_SESSION['error'] = 'Gagal mengoreksi kelas siswa: ' . $err;
}

header('Location: koreksi_kelas_siswa?q=' . urlencode((string)$siswaId));
exit;
?>
