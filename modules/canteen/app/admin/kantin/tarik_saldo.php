<?php
include 'inc/fungsi.php'; // pastikan $conn dan $id_kantin tersedia
header('Content-Type: application/json');

$jumlah = intval($_POST['jumlah'] ?? 0);

if ($jumlah < 1000) {
    echo json_encode(['success' => false, 'message' => 'Jumlah terlalu kecil']);
    exit;
}

// Ambil saldo terkini
$cekSaldo = $conn->prepare("SELECT saldo FROM kantin WHERE id = ?");
$cekSaldo->bind_param("i", $id_kantin);
$cekSaldo->execute();
$cekSaldo->bind_result($saldoSaatIni);
$cekSaldo->fetch();
$cekSaldo->close();

if ($jumlah > $saldoSaatIni) {
    echo json_encode(['success' => false, 'message' => 'Saldo tidak mencukupi']);
    exit;
}

// Cek duplikat dalam 1 menit
$cek = $conn->prepare("SELECT id FROM penarikan 
    WHERE id_kantin = ? AND jumlah = ? AND status = 'diproses' 
    AND timestampdiff(SECOND, tanggal, now()) < 60");
$cek->bind_param("ii", $id_kantin, $jumlah);
$cek->execute();
$cek->store_result();

if ($cek->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Permintaan duplikat terdeteksi.']);
    exit;
}
$cek->close();

// Mulai transaksi database
$conn->begin_transaction();

try {
    // Insert permintaan penarikan
    $stmt = $conn->prepare("INSERT INTO penarikan (id_kantin, jumlah, status) VALUES (?, ?, 'diproses')");
    $stmt->bind_param("ii", $id_kantin, $jumlah);
    $stmt->execute();

    // Kurangi saldo kantin
    $update = $conn->prepare("UPDATE kantin SET saldo = saldo - ? WHERE id = ?");
    $update->bind_param("ii", $jumlah, $id_kantin);
    $update->execute();

    // Commit transaksi
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback jika gagal
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Gagal memproses penarikan']);
}
