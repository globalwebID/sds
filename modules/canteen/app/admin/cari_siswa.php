<?php
include 'inc/fungsi.php';
checkRole(['superadmin', 'admin']);

$search = trim($_GET['q'] ?? '');
$response = [];

$sql = "SELECT id, nama_lengkap, nipd, nisn, rfid_uid, saldo, blokir 
        FROM pendaftaran_siswa ";

$params = [];
$types = '';

if (!empty($search)) {
    $sql .= "WHERE nama_lengkap LIKE ? OR nipd LIKE ? OR nisn LIKE ? OR rfid_uid LIKE ? ";
    $like = '%' . $search . '%';
    $params = [$like, $like, $like, $like];
    $types = 'ssss';
}

$sql .= "ORDER BY nama_lengkap ASC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Query preparation failed']);
    exit;
}

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$response = $result->fetch_all(MYSQLI_ASSOC);

$stmt->close();

header('Content-Type: application/json');
echo json_encode($response);
