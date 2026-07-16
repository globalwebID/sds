<?php
ob_start();
require '../../db.php';
require '../fungsi.php';
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $tahun = $_POST['tahun_ajaran'] ?? null;
    $kelas = $_POST['nama_kelas'] ?? null;
    $walas = $_POST['wali_kelas'] ?? null;
    $kuota = isset($_POST['kuota']) ? (int)$_POST['kuota'] : null;


    if (!$id || !$tahun || !$kelas || !$walas || !$kuota) {
        die("Data tidak lengkap");
    }


    $stmt = $conn->prepare("UPDATE kelas SET tahun_ajaran=?, nama_kelas=?, wali_kelas=?, kuota=? WHERE id=?");
    $stmt->bind_param("sssii", $tahun, $kelas, $walas, $kuota, $id);
    $stmt->execute();
    $stmt->close();

    // ✅ Tambahkan log aktivitas
    if (isset($_SESSION['admin_id'])) {
        $keterangan = "Mengubah data kelas ($kelas) untuk tahun ajaran $tahun, kuota: $kuota, walas: $walas";
        catatLog($conn, $_SESSION['admin_id'], 'Edit Kelas', $keterangan);
    }
    $_SESSION['success'] = "Kelas <strong>$kelas</strong> berhasil di ubah.";
    header("Location: ../kuota_kelas?tahun=" . urlencode($tahun));
    exit;
}
ob_end_flush();
