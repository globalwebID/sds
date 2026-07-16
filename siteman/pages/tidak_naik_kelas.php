<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswa_id = isset($_POST['siswa_id']) ? (int) $_POST['siswa_id'] : 0;
    $kelas_id = isset($_POST['kelas_id']) ? (int) $_POST['kelas_id'] : 0;
    $tahun_ajaran = trim((string) ($_POST['tahun_ajaran'] ?? ''));

    if ($siswa_id <= 0 || $kelas_id <= 0 || $tahun_ajaran === '') {
        $_SESSION['error'] = "Data tidak lengkap atau tidak valid.";
        header("Location: kuota_kelas");
        exit;
    }

    // Pastikan data siswa_kelas yang ditandai adalah data kelas/tahun ajaran aktif yang sedang dibuka.
    // Sebelumnya sistem mengirim tahun ajaran lama, sehingga siswa tidak muncul pada menu Siswa Tidak Naik Kelas tahun ini.
    $stmtCek = $conn->prepare("SELECT id FROM siswa_kelas WHERE siswa_id = ? AND kelas_id = ? AND tahun_ajaran = ? LIMIT 1");
    if (!$stmtCek) {
        $_SESSION['error'] = "Query cek siswa gagal: " . $conn->error;
        header("Location: kuota_kelas_siswa?kelas_id=" . $kelas_id);
        exit;
    }
    $stmtCek->bind_param('iis', $siswa_id, $kelas_id, $tahun_ajaran);
    $stmtCek->execute();
    $cek = $stmtCek->get_result()->fetch_assoc();
    $stmtCek->close();

    if (!$cek) {
        $_SESSION['error'] = "Data siswa pada kelas dan tahun ajaran ini tidak ditemukan.";
        header("Location: kuota_kelas_siswa?kelas_id=" . $kelas_id);
        exit;
    }

    $stmt = $conn->prepare("UPDATE siswa_kelas SET naik_kelas = 0 WHERE siswa_id = ? AND kelas_id = ? AND tahun_ajaran = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Query gagal disiapkan: " . $conn->error;
        header("Location: kuota_kelas_siswa?kelas_id=" . $kelas_id);
        exit;
    }
    $stmt->bind_param('iis', $siswa_id, $kelas_id, $tahun_ajaran);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Siswa berhasil ditandai tidak naik kelas pada tahun ajaran {$tahun_ajaran}. Silakan atur kelasnya melalui menu Siswa Tidak Naik Kelas.";
    } else {
        $_SESSION['error'] = "Gagal menandai siswa tidak naik kelas: " . $stmt->error;
    }
    $stmt->close();

    header("Location: siswa_tidak_naik");
    exit;
}
?>
