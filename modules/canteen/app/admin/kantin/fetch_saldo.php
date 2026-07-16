<?php
include 'inc/fungsi.php';

header('Content-Type: application/json');

$id_kantin = $_SESSION['id_kantin'] ?? 0;

// Ambil saldo langsung dari tabel kantin
$result = mysqli_query($conn, "SELECT saldo FROM kantin WHERE id = $id_kantin");
$row = mysqli_fetch_assoc($result);

echo json_encode([
    'saldo' => (int)($row['saldo'] ?? 0)
]);
