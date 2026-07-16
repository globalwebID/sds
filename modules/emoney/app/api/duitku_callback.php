<?php
require '_config.php';
// healthcheck: kalau dibuka manual via browser (GET), jangan 400
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  http_response_code(200);
  header('Content-Type: text/plain; charset=utf-8');
  echo "OK - duitku callback endpoint ready";
  exit;
}

// Jangan requireAuth() karena ini dipanggil Duitku (bukan user login)

$cfg = require __DIR__ . '/_duitku_config.php';

$merchantCode    = (string)($_POST['merchantCode'] ?? '');
$amount          = (string)($_POST['amount'] ?? ($_POST['paymentAmount'] ?? '0'));
$merchantOrderId = (string)($_POST['merchantOrderId'] ?? '');
$resultCode      = (string)($_POST['resultCode'] ?? '');
$signature       = (string)($_POST['signature'] ?? '');

if ($merchantCode==='' || $merchantOrderId==='' || $amount==='0' || $signature==='') {
  http_response_code(400);
  echo "BAD_REQUEST";
  exit;
}
if (!hash_equals((string)$cfg['merchantCode'], $merchantCode)
    || !preg_match('/^\d+(?:\.00)?$/', $amount)
    || (int)$amount <= 0
    || strlen($merchantOrderId) > 64) {
  error_log('[SDS Duitku] Merchant atau nominal callback tidak valid untuk order ' . $merchantOrderId);
  http_response_code(400);
  echo "BAD_REQUEST";
  exit;
}

// Signature callback VA: MD5(merchantCode + amount + merchantOrderId + merchantKey) :contentReference[oaicite:4]{index=4}
$expected = md5($merchantCode . $amount . $merchantOrderId . $cfg['apiKey']);
if (!hash_equals($expected, $signature)) {
  http_response_code(403);
  echo "INVALID_SIGNATURE";
  exit;
}

// jika pembayaran gagal
if ($resultCode !== '00') {
  $failed = $conn->prepare("UPDATE topup SET status='FAILED' WHERE merchant_order_id=? AND status='PENDING'");
  $failed->bind_param('s', $merchantOrderId);
  $failed->execute();
  $failed->close();
  echo "OK";
  exit;
}

// sukses: idempotent + transaksi aman
mysqli_begin_transaction($conn);

// lock row topup
$lock = $conn->prepare('SELECT id,id_siswa,nominal,status FROM topup WHERE merchant_order_id=? FOR UPDATE');
$lock->bind_param('s', $merchantOrderId);
$lock->execute();
$top = $lock->get_result()->fetch_assoc();
$lock->close();
if(!$top){
  mysqli_commit($conn);
  // tetap balas sukses agar Duitku tidak retry terus
  echo "SUCCESS";
  exit;
}

if ($top['status'] === 'PAID') {
  mysqli_commit($conn);
  echo "SUCCESS";
  exit;
}

$idSiswa = (int)$top['id_siswa'];
$nominal = (int)$top['nominal'];
if ($nominal !== (int)$amount || $nominal <= 0) {
  mysqli_rollback($conn);
  error_log('[SDS Duitku] Nominal callback tidak cocok untuk order ' . $merchantOrderId);
  http_response_code(409);
  echo "AMOUNT_MISMATCH";
  exit;
}
if ($top['status'] !== 'PENDING') {
  mysqli_rollback($conn);
  error_log('[SDS Duitku] Callback sukses untuk order berstatus ' . $top['status'] . ': ' . $merchantOrderId);
  http_response_code(409);
  echo "INVALID_STATUS";
  exit;
}

// 1) tambah saldo ke pendaftaran_siswa (sesuai sistem Anda)
$credit = $conn->prepare('UPDATE pendaftaran_siswa SET saldo=saldo+? WHERE id=? LIMIT 1');
$credit->bind_param('ii', $nominal, $idSiswa);
$credit->execute();
if($credit->affected_rows !== 1){
  $credit->close();
  mysqli_rollback($conn);
  http_response_code(500);
  echo "ERROR";
  exit;
}
$credit->close();

$balance = $conn->prepare('SELECT saldo FROM pendaftaran_siswa WHERE id=?');
$balance->bind_param('i', $idSiswa);
$balance->execute();
$saldoAkhir = (int)$balance->get_result()->fetch_row()[0];
$balance->close();

// 2) tandai topup PAID
$paid = $conn->prepare("UPDATE topup SET status='PAID',paid_at=NOW(),saldo_akhir=? WHERE id=? AND status='PENDING'");
$topupId = (int)$top['id'];
$paid->bind_param('ii', $saldoAkhir, $topupId);
$paid->execute();
if($paid->affected_rows !== 1){
  $paid->close();
  mysqli_rollback($conn);
  http_response_code(500);
  echo "ERROR";
  exit;
}
$paid->close();

mysqli_commit($conn);

// Duitku VA: “Please response with SUCCESS if transaction is success.” :contentReference[oaicite:5]{index=5}
echo "SUCCESS";
