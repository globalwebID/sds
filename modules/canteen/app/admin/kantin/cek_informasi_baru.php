<?php
include 'inc/fungsi.php';
$id_kantin = $_SESSION['id_kantin'] ?? 0;

$cek = $conn->query("SELECT COUNT(*) as total FROM informasi_user WHERE user_id = $id_kantin AND dibaca = 0");
$row = $cek->fetch_assoc();

echo json_encode(['baru' => $row['total'] > 0]);
