<?php
session_start();
include '../middleware/auth.php';
include '../../../config/db.php';

if ($_SESSION['role'] !== 'kantin') {
    header('Location: ../login.php?error=Akses ditolak');
    exit;
}
date_default_timezone_set('Asia/Jakarta');
$hariIni = date('d-m-Y');

// $id_kantin = $_SESSION['id_kantin'];
$id_kantin = $_SESSION['id_kantin'] ?? 0;
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$cekNotif = $conn->query("SELECT COUNT(*) AS total FROM informasi_user WHERE user_id = $id_kantin AND dibaca = 0");
$hasilNotif = $cekNotif->fetch_assoc();
$adaInformasiBaru = $hasilNotif['total'] > 0;
