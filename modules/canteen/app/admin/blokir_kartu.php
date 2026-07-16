<?php
include 'inc/fungsi.php';
checkRole(['superadmin', 'admin']);

$id = intval($_GET['id'] ?? 0);
$aksi = $_GET['aksi'] ?? '';

if (!in_array($aksi, ['blokir', 'buka']) || $id <= 0) {
    header("Location: siswa.php");
    exit;
}

$status = $aksi === 'blokir' ? 1 : 0;

// Cek apakah siswa ada
$stmt = $conn->prepare("SELECT rfid_uid FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$siswa = $result->fetch_assoc();
$stmt->close();

if (!$siswa) {
    // Data tidak ditemukan
    header("Location: siswa.php?error=notfound");
    exit;
}

// Jika ingin memblokir, pastikan ada RFID
if ($status === 1 && empty($siswa['rfid_uid'])) {
    header("Location: siswa.php?error=norfid");
    exit;
}

// Update status blokir
$stmt = $conn->prepare("UPDATE pendaftaran_siswa SET blokir = ? WHERE id = ?");
$stmt->bind_param("ii", $status, $id);
$stmt->execute();
$stmt->close();

header("Location: siswa.php?success=" . ($status ? 'blocked' : 'unblocked'));
exit;
