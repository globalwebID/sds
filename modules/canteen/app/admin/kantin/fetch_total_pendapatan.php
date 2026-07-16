<?php
include 'inc/fungsi.php';

$query = mysqli_query($conn, "SELECT saldo FROM kantin WHERE id = $id_kantin");
$row = mysqli_fetch_assoc($query);

echo json_encode([
    'total' => (int)($row['saldo'] ?? 0)
]);
