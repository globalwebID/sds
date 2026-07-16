<?php
include 'inc/fungsi.php';

if (!isset($_SESSION['id_kantin'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$id_kantin = $_SESSION['id_kantin'];
$status = $_POST['status'] ?? '';

if (!in_array($status, ['buka', 'tutup'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Status tidak valid']);
    exit;
}

$query = mysqli_query($conn, "UPDATE kantin SET status_toko = '$status' WHERE id = $id_kantin");

if ($query) {
    echo json_encode(['success' => true, 'status' => $status]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal memperbarui status']);
}
