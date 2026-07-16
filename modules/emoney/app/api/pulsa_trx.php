<?php
require_once __DIR__ . '/_config.php';
require_once __DIR__ . '/_digiflazz_helper.php';
requireAuth();
requireCsrf();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') response(false, 'Method tidak diizinkan');
$idSiswa = (int)($_SESSION['id_siswa'] ?? 0);
$kode = trim((string)($_POST['kode'] ?? ''));
$nomor = preg_replace('/\D+/', '', (string)($_POST['nomor'] ?? ''));
$requestKey = (string)($_POST['request_key'] ?? '');
if ($idSiswa <= 0 || !preg_match('/^[A-Za-z0-9._-]{2,64}$/', $kode) || !preg_match('/^\d{8,16}$/', $nomor)) response(false, 'Produk atau nomor tujuan tidak valid');
if (empty($_SESSION['pulsa_request_key']) || !hash_equals((string)$_SESSION['pulsa_request_key'], $requestKey)) response(false, 'Permintaan transaksi sudah digunakan. Muat ulang halaman.');
unset($_SESSION['pulsa_request_key']);

$product = digiflazz_fetch_pricelist(['code'=>$kode]);
if (empty($product['success']) || empty($product['items'][0])) response(false, 'Produk tidak tersedia');
$item = $product['items'][0];
$price = digiflazz_make_sell_price((string)($item['brand'] ?? ''), (int)($item['price'] ?? 0));
$hargaJual = (int)($price['price_sell'] ?? 0);
if ($hargaJual <= 0) response(false, 'Harga produk tidak valid');
$refId = digiflazz_make_ref_id($idSiswa);

try {
    $conn->begin_transaction();
    $debit = $conn->prepare('UPDATE pendaftaran_siswa SET saldo=saldo-? WHERE id=? AND blokir=0 AND saldo>=?');
    $debit->bind_param('iii', $hargaJual, $idSiswa, $hargaJual);
    $debit->execute();
    if ($debit->affected_rows !== 1) throw new RuntimeException('Saldo tidak cukup atau kartu diblokir');
    $debit->close();
    $created = 'CREATED';
    $insert = $conn->prepare('INSERT INTO game_transactions (ref_id,id_siswa,sku_code,price_sell,status,created_at) VALUES (?,?,?,?,?,NOW())');
    $insert->bind_param('sisis', $refId, $idSiswa, $kode, $hargaJual, $created);
    $insert->execute();
    $insert->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('[SDS pulsa debit] ' . $e->getMessage());
    response(false, $e instanceof RuntimeException ? $e->getMessage() : 'Transaksi tidak dapat diproses');
}

$provider = digiflazz_create_transaction($kode, $nomor, $refId);
$status = digiflazz_map_status((string)($provider['provider']['status'] ?? ($provider['success'] ? 'pending' : 'failed')));
$message = (string)($provider['message'] ?? '');

try {
    $conn->begin_transaction();
    $lock = $conn->prepare('SELECT status,refund_amount,refunded_at FROM game_transactions WHERE ref_id=? FOR UPDATE');
    $lock->bind_param('s', $refId); $lock->execute(); $trx=$lock->get_result()->fetch_assoc(); $lock->close();
    if (!$trx) throw new RuntimeException('Transaksi lokal tidak ditemukan');
    if ($status === 'FAILED' && empty($trx['refunded_at']) && (int)$trx['refund_amount'] === 0) {
        $credit = $conn->prepare('UPDATE pendaftaran_siswa SET saldo=saldo+? WHERE id=?');
        $credit->bind_param('ii', $hargaJual, $idSiswa); $credit->execute();
        if ($credit->affected_rows !== 1) throw new RuntimeException('Refund saldo gagal');
        $credit->close();
        $status = 'REFUNDED';
        $update = $conn->prepare('UPDATE game_transactions SET status=?,provider_status=?,message=?,refund_amount=?,refunded_at=NOW(),updated_at=NOW() WHERE ref_id=?');
        $providerStatus='failed';
        $update->bind_param('sssis', $status, $providerStatus, $message, $hargaJual, $refId);
    } else {
        $update = $conn->prepare('UPDATE game_transactions SET status=?,provider_status=?,message=?,updated_at=NOW() WHERE ref_id=?');
        $providerStatus=(string)($provider['provider']['status'] ?? strtolower($status));
        $update->bind_param('ssss', $status, $providerStatus, $message, $refId);
    }
    $update->execute(); $update->close();
    $conn->commit();
} catch (Throwable $e) {
    $conn->rollback();
    error_log('[SDS pulsa provider] ' . $e->getMessage());
    response(false, 'Status transaksi sedang diproses. Periksa riwayat sebelum mencoba lagi.');
}

response($status !== 'REFUNDED', $status === 'REFUNDED' ? 'Transaksi gagal dan saldo telah dikembalikan' : ($message ?: 'Transaksi sedang diproses'), ['ref_id'=>$refId,'status'=>$status]);
