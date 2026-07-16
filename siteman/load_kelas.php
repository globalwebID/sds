<?php
header('Content-Type: application/json');
include '../db.php';

$tingkat_id = isset($_GET['tingkat_id']) ? intval($_GET['tingkat_id']) : 0;
$tahun_ajaran = isset($_GET['tahun_ajaran']) ? $_GET['tahun_ajaran'] : '';

if ($tingkat_id < 1 || empty($tahun_ajaran)) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

$sql = "SELECT id, nama_kelas FROM kelas WHERE tingkat_id = ? AND tahun_ajaran = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("is", $tingkat_id, $tahun_ajaran);
$stmt->execute();
$result = $stmt->get_result();

$kelas = [];
while ($row = $result->fetch_assoc()) {
    $kelas[] = $row;
}

if (count($kelas) > 0) {
    echo json_encode(['success' => true, 'data' => $kelas]);
} else {
    echo json_encode(['success' => false, 'message' => 'Kelas tidak ditemukan']);
}

