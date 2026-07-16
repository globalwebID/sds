<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $siswaId = isset($_POST['siswa_id']) ? (int) $_POST['siswa_id'] : 0;
    $kelasBaruId = isset($_POST['kelas_id']) ? (int) $_POST['kelas_id'] : 0;
    $tahunAjaran = trim((string) ($_POST['tahun_ajaran'] ?? ''));

    if ($siswaId <= 0 || $kelasBaruId <= 0 || $tahunAjaran === '') {
        $_SESSION['error'] = "Data tidak lengkap.";
        header("Location: siswa_tidak_naik");
        exit;
    }

    $stmt = $conn->prepare("SELECT kuota, terisi FROM kelas WHERE id = ? AND tahun_ajaran = ? LIMIT 1");
    if (!$stmt) {
        $_SESSION['error'] = "Query kelas gagal: " . $conn->error;
        header("Location: siswa_tidak_naik");
        exit;
    }
    $stmt->bind_param("is", $kelasBaruId, $tahunAjaran);
    $stmt->execute();
    $kelas_baru = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$kelas_baru) {
        $_SESSION['error'] = "Kelas tujuan tidak ditemukan.";
        header("Location: siswa_tidak_naik");
        exit;
    }

    // Cari relasi siswa pada tahun ajaran aktif. Data ini sudah ada karena siswa ditandai tidak naik dari kelas aktif.
    $kelasLamaId = 0;
    $stmtOld = $conn->prepare("SELECT kelas_id FROM siswa_kelas WHERE siswa_id = ? AND tahun_ajaran = ? LIMIT 1");
    if (!$stmtOld) {
        $_SESSION['error'] = "Query data lama gagal: " . $conn->error;
        header("Location: siswa_tidak_naik");
        exit;
    }
    $stmtOld->bind_param("is", $siswaId, $tahunAjaran);
    $stmtOld->execute();
    $stmtOld->bind_result($kelasLamaId);
    $stmtOld->fetch();
    $stmtOld->close();

    if ($kelasLamaId <= 0) {
        $_SESSION['error'] = "Data siswa pada tahun ajaran ini tidak ditemukan.";
        header("Location: siswa_tidak_naik");
        exit;
    }

    // Finalisasi atur kelas: pindahkan siswa ke kelas tujuan pada tahun ajaran aktif
    // dan kembalikan naik_kelas = 1 agar siswa masuk daftar kelas tujuan serta
    // tidak lagi muncul di menu Siswa Tidak Naik Kelas.
    $stmtUpdate = $conn->prepare("UPDATE siswa_kelas SET kelas_id = ?, naik_kelas = 1 WHERE siswa_id = ? AND tahun_ajaran = ?");
    if (!$stmtUpdate) {
        $_SESSION['error'] = "Query pindah kelas gagal: " . $conn->error;
        header("Location: siswa_tidak_naik");
        exit;
    }
    $stmtUpdate->bind_param("iis", $kelasBaruId, $siswaId, $tahunAjaran);

    if ($stmtUpdate->execute()) {
        $stmtUpdate->close();

        $stmtPs = $conn->prepare("UPDATE pendaftaran_siswa SET kelas_id = ? WHERE id = ?");
        if ($stmtPs) {
            $stmtPs->bind_param("ii", $kelasBaruId, $siswaId);
            $stmtPs->execute();
            $stmtPs->close();
        }

        // Sinkronkan terisi kelas lama dan kelas baru berdasarkan data real siswa_kelas.
        foreach (array_unique([(int)$kelasLamaId, (int)$kelasBaruId]) as $kid) {
            if ($kid <= 0) continue;
            $stmtSync = $conn->prepare("UPDATE kelas k SET terisi = (SELECT COUNT(*) FROM siswa_kelas sk WHERE sk.kelas_id = k.id AND sk.tahun_ajaran = k.tahun_ajaran) WHERE k.id = ?");
            if ($stmtSync) {
                $stmtSync->bind_param("i", $kid);
                $stmtSync->execute();
                $stmtSync->close();
            }
        }

        $_SESSION['success'] = "Kelas siswa berhasil diatur. Siswa sudah masuk ke kelas tujuan dan tidak lagi tampil pada daftar Siswa Tidak Naik Kelas.";
    } else {
        $_SESSION['error'] = "Gagal memperbarui kelas siswa: " . $stmtUpdate->error;
        $stmtUpdate->close();
    }

    header("Location: siswa_tidak_naik");
    exit;
}
?>
