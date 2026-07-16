<?php
header('Content-Type: application/json');
include '../config/db.php';

$uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';

if (empty($uid)) {
    echo json_encode(['found' => false, 'error' => 'UID kosong']);
    exit;
}

// Gunakan prepared statement agar aman
$stmt = $conn->prepare("SELECT nama_lengkap FROM pendaftaran_siswa WHERE rfid_uid = ? LIMIT 1");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode(['found' => true, 'nama' => $row['nama_lengkap']]);
} else {
    echo json_encode(['found' => false]);
}

$stmt->close();
$conn->close();
