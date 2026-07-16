<?php
// halaman verifikasi_penarikan.php 
include 'inc/fungsi.php';

$id = (int)($_GET['id'] ?? 0);
$aksi = $_GET['aksi'] ?? '';

$query = mysqli_query($conn, "SELECT * FROM penarikan WHERE id = $id AND status = 'diproses'");
$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Penarikan tidak ditemukan atau sudah diverifikasi.");
}

if ($aksi === 'setujui') {
    // Tidak perlu kurangi saldo lagi, karena sudah dikurangi saat permintaan dibuat
    mysqli_query($conn, "UPDATE penarikan SET status = 'berhasil' WHERE id = $id");

    header("Location: penarikan.php?success=1");
} elseif ($aksi === 'tolak') {
    // Kembalikan saldo karena penarikan ditolak
    mysqli_query($conn, "UPDATE kantin SET saldo = saldo + {$data['jumlah']} WHERE id = {$data['id_kantin']}");
    mysqli_query($conn, "UPDATE penarikan SET status = 'ditolak' WHERE id = $id");

    header("Location: dashboard.php?success=2");
} else {
    die("Aksi tidak valid.");
}
