<?php
include '../../../config/db.php';

$uid = $_GET['uid'] ?? '';

if ($uid === '') {
    echo json_encode(['error' => 'UID kosong']);
    exit;
}

$stmt = $conn->prepare("SELECT nama_lengkap, saldo FROM pendaftaran_siswa WHERE rfid_uid = ?");
$stmt->bind_param("s", $uid);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'nama' => $row['nama_lengkap'],
        'saldo' => is_numeric($row['saldo']) ? (int) $row['saldo'] : 0
    ]);
} else {
    echo json_encode(['error' => 'Siswa tidak ditemukan']);
}
