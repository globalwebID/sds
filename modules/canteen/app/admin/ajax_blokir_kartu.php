<?php
include 'inc/fungsi.php';
checkRole(['superadmin', 'admin']);

header('Content-Type: application/json; charset=UTF-8');

$id   = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$aksi = strtolower(trim((string)($_POST['aksi'] ?? ''))); // 'blokir' | 'buka'

if ($id <= 0 || !in_array($aksi, ['blokir', 'buka'], true)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parameter tidak valid'
    ]);
    exit;
}

// Pastikan data siswa ada + punya RFID
$stmt = $conn->prepare("SELECT rfid_uid FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Data siswa tidak ditemukan'
    ]);
    exit;
}

if (empty($row['rfid_uid'])) {
    http_response_code(422);
    echo json_encode([
        'success' => false,
        'message' => 'Kartu tidak dapat diblokir karena siswa belum memiliki UID RFID'
    ]);
    exit;
}

$blokirValue = ($aksi === 'blokir') ? 1 : 0;

$stmt = $conn->prepare("UPDATE pendaftaran_siswa SET blokir = ? WHERE id = ?");
$stmt->bind_param("ii", $blokirValue, $id);
$ok = $stmt->execute();
$stmt->close();

if ($ok) {
    echo json_encode([
        'success' => true,
        'status'  => $aksi,
        'blokir'  => $blokirValue
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memproses permintaan.'
    ]);
}
