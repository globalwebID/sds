<?php
require '../../db.php';
require '../fungsi.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $tahun = trim((string)($_POST['tahun_ajaran'] ?? ''));
    $kode_jurusan = trim((string)($_POST['kode_jurusan'] ?? ''));
    $nama_jurusan = trim((string)($_POST['nama_jurusan'] ?? ''));

    if ($id <= 0 || $tahun === '' || $kode_jurusan === '' || $nama_jurusan === '') {
        $_SESSION['error'] = 'Data jurusan tidak lengkap.';
        header("Location: ../jurusan");
        exit;
    }

    if (!preg_match('/^[0-9]+([.][0-9]+)*$/', $kode_jurusan)) {
        $_SESSION['error'] = "Format kode jurusan/spektrum tidak valid. Contoh yang benar: 4.1.1 atau 8.2.1.";
        header("Location: ../jurusan?tahun=" . urlencode($tahun));
        exit;
    }

    // Proses update
    $stmt = $conn->prepare("UPDATE jurusan SET tahun_ajaran=?, kode_jurusan=?, nama_jurusan=? WHERE id=?");
    $stmt->bind_param("sssi", $tahun, $kode_jurusan, $nama_jurusan, $id);
    $stmt->execute();
    $stmt->close();

    if (isset($_SESSION['admin_id'])) {
        $keterangan = "Mengubah data jurusan ($nama_jurusan), Kode Spektrum: $kode_jurusan";
        catatLog($conn, $_SESSION['admin_id'], 'Edit Jurusan', $keterangan);
    }

    $_SESSION['success'] = "Jurusan <strong>$nama_jurusan</strong> berhasil di ubah.";
    header("Location: ../jurusan?tahun=" . urlencode($tahun));
    exit;
}
