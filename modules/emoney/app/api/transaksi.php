<?php
require '_config.php';
requireAuth();

$id_siswa  = (int)$_SESSION['id_siswa'];
$id_kantin = (int)($_POST['id_kantin'] ?? 0);
$nominal   = (int)($_POST['nominal'] ?? 0);

if ($nominal <= 0) {
    response(false, 'Nominal tidak valid');
}

// Ambil saldo
$s = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT saldo FROM pendaftaran_siswa WHERE id=$id_siswa")
);

if ($s['saldo'] < $nominal) {
    response(false, 'Saldo tidak cukup');
}

$newSaldo = $s['saldo'] - $nominal;

mysqli_query($conn, "UPDATE pendaftaran_siswa SET saldo=$newSaldo WHERE id=$id_siswa");

mysqli_query($conn, "
    INSERT INTO transaksi_kantin (id_siswa, id_kantin, nominal, tanggal)
    VALUES ($id_siswa, $id_kantin, $nominal, NOW())
");

response(true, 'Transaksi berhasil', [
    'sisa_saldo' => $newSaldo
]);
