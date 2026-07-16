<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun_ajaran = trim((string)($_POST['tahun_ajaran'] ?? ''));
    $kode_jurusan = trim((string)($_POST['kode_jurusan'] ?? ''));
    $nama_jurusan = trim((string)($_POST['nama_jurusan'] ?? ''));

    if ($tahun_ajaran === '' || $kode_jurusan === '' || $nama_jurusan === '') {
        $_SESSION['error'] = 'Kode jurusan/spektrum, nama jurusan, dan tahun ajaran wajib diisi.';
        header("Location: jurusan");
        exit;
    }

    if (!preg_match('/^[0-9]+([.][0-9]+)*$/', $kode_jurusan)) {
        $_SESSION['error'] = "Format kode jurusan/spektrum tidak valid. Contoh yang benar: 4.1.1 atau 8.2.1.";
        header("Location: jurusan?tahun=" . urlencode($tahun_ajaran));
        exit;
    }

    // Cek apakah jurusan sudah ada untuk tahun ajaran yang sama
    $cek = $conn->prepare("SELECT COUNT(*) FROM jurusan WHERE kode_jurusan = ? AND nama_jurusan = ? AND tahun_ajaran = ?");
    $cek->bind_param("sss", $kode_jurusan, $nama_jurusan, $tahun_ajaran);
    $cek->execute();
    $cek->bind_result($jumlah);
    $cek->fetch();
    $cek->close();

    if ($jumlah > 0) {
        $_SESSION['error'] = "Jurusan '$nama_jurusan' dengan kode '$kode_jurusan' sudah terdaftar di tahun ajaran $tahun_ajaran.";
        header("Location: jurusan?tahun=" . urlencode($tahun_ajaran));
        exit;
    }

    if (isset($_SESSION['admin_id'])) {
        $keterangan = "Menambah jurusan ($nama_jurusan) - Kode Spektrum: $kode_jurusan - Tahun Ajaran: $tahun_ajaran";
        catatLog($conn, $_SESSION['admin_id'], 'Tambah Jurusan', $keterangan);
    }

    $stmt = $conn->prepare("INSERT INTO jurusan (kode_jurusan, nama_jurusan, tahun_ajaran) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $kode_jurusan, $nama_jurusan, $tahun_ajaran);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = "Jurusan berhasil <strong>ditambahkan.</strong>";
    header("Location: jurusan?tahun=" . urlencode($tahun_ajaran));
    exit;
}
