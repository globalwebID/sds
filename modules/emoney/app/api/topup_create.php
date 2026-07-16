<?php
require '_config.php';
requireAuth();
requireCsrf();

$cfg = require __DIR__ . '/_duitku_config.php';

$id = (int)($_SESSION['id_siswa'] ?? 0);
if ($id <= 0) response(false, 'Session tidak valid');

$amount = (int)($_POST['amount'] ?? 0);
if ($amount < 1000) response(false, 'Nominal minimal 1000');

// Ambil data siswa (pakai tabel Anda)
$q = mysqli_query($conn,"SELECT nama_lengkap, saldo FROM pendaftaran_siswa WHERE id=$id LIMIT 1");
if(!$q) response(false,'Query siswa gagal',['db_error'=>mysqli_error($conn)]);
$row = mysqli_fetch_assoc($q);
if(!$row) response(false,'Data siswa tidak ditemukan');

$nama = trim((string)$row['nama_lengkap']);
if ($nama === '') $nama = 'Siswa';

$currentSaldo = (int)$row['saldo'];
$saldoAkhir   = $currentSaldo + $amount;

$merchantCode = $cfg['merchantCode'];
$apiKey       = $cfg['apiKey'];
$baseUrl      = rtrim($cfg['baseUrl'], '/');
if ($merchantCode === '' || $apiKey === '') {
  error_log('[SDS Duitku] Kredensial pembayaran belum dikonfigurasi.');
  response(false, 'Layanan pembayaran belum dikonfigurasi');
}

// order id unik
$merchantOrderId = 'TOPUP-' . $id . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3));

// simpan topup PENDING
$stmt = mysqli_prepare($conn, "
  INSERT INTO topup (id_siswa, tanggal, nominal, saldo_akhir, merchant_order_id, status)
  VALUES (?, NOW(), ?, ?, ?, 'PENDING')
");
if(!$stmt) response(false,'Prepare insert topup gagal',['db_error'=>mysqli_error($conn)]);
mysqli_stmt_bind_param($stmt, 'iiis', $id, $amount, $saldoAkhir, $merchantOrderId);
if(!mysqli_stmt_execute($stmt)) response(false,'Insert topup gagal',['db_error'=>mysqli_error($conn)]);
mysqli_stmt_close($stmt);

// ===== POP AUTH (HEADER) =====
// timestamp ms (Jakarta)
$timestamp = (string)round(microtime(true) * 1000);

// signature header: SHA256(merchantCode + timestamp + apiKey) :contentReference[oaicite:4]{index=4}
$signature = hash('sha256', $merchantCode . $timestamp . $apiKey);

// ===== BODY =====
// email wajib (kalau Anda punya kolom email, silakan ambil dari DB)
$email = "siswa{$id}@smkn1probolinggo.sch.id";

// itemDetails wajib & total harus sama persis dengan paymentAmount :contentReference[oaicite:5]{index=5}
$itemDetails = [
  [
    'name'     => 'Top Up e-Money',
    'price'    => $amount,
    'quantity' => 1,
  ]
];

$params = [
  'paymentAmount'   => $amount,
  'merchantOrderId' => $merchantOrderId,
  'productDetails'  => 'Top Up e-Money',
  'additionalParam' => '',
  'merchantUserInfo'=> '',
  'paymentMethod'   => '',          // kosong = tampilkan semua metode (umumnya)
  'customerVaName'  => $nama,        // opsional, tapi bagus untuk VA
  'email'           => $email,       // WAJIB :contentReference[oaicite:6]{index=6}
  'phoneNumber'     => '0000000000', // opsional; ganti kalau punya no HP
  'itemDetails'     => $itemDetails,
  'callbackUrl'     => $cfg['callbackUrl'],
  'returnUrl'       => $cfg['returnUrl'],
  'expiryPeriod'    => 60,           // menit
];

$params_string = json_encode($params);

$url = $baseUrl . '/merchant/createInvoice';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
curl_setopt($ch, CURLOPT_POSTFIELDS, $params_string);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
  'Accept: application/json',
  'Content-Type: application/json',
  'Content-Length: ' . strlen($params_string),
  'x-duitku-signature: ' . $signature,
  'x-duitku-timestamp: ' . $timestamp,
  'x-duitku-merchantcode: ' . $merchantCode
]);
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //sandbox
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

$res  = curl_exec($ch);
$err  = curl_error($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($res === false) {
  error_log('[SDS Duitku createInvoice] ' . $err);
  response(false, 'Layanan pembayaran tidak dapat dihubungi');
}

$data = json_decode($res, true);

// sukses kalau dapat paymentUrl & reference :contentReference[oaicite:7]{index=7}
$paymentUrl = $data['paymentUrl'] ?? '';
$reference  = $data['reference'] ?? '';
$statusCode = $data['statusCode'] ?? '';
$statusMsg  = $data['statusMessage'] ?? '';

if ($http !== 200 || !$paymentUrl) {
  $oidEsc = mysqli_real_escape_string($conn, $merchantOrderId);
  mysqli_query($conn, "UPDATE topup SET status='FAILED' WHERE merchant_order_id='{$oidEsc}'");

  error_log('[SDS Duitku createInvoice] HTTP ' . $http . ' status ' . $statusCode . ': ' . $statusMsg);
  response(false, 'Gagal membuat transaksi pembayaran', [
    'http' => $http,
    'statusCode' => $statusCode,
    'statusMessage' => $statusMsg
  ]);
}

if (!filter_var($paymentUrl, FILTER_VALIDATE_URL) || parse_url($paymentUrl, PHP_URL_SCHEME) !== 'https') {
  error_log('[SDS Duitku createInvoice] URL pembayaran tidak valid untuk ' . $merchantOrderId);
  response(false, 'URL pembayaran dari penyedia tidak valid');
}

if ($reference) {
  $refEsc = mysqli_real_escape_string($conn, $reference);
  $oidEsc = mysqli_real_escape_string($conn, $merchantOrderId);
  mysqli_query($conn, "UPDATE topup SET duitku_reference='{$refEsc}' WHERE merchant_order_id='{$oidEsc}'");
}

response(true, 'ok', [
  'paymentUrl'      => $paymentUrl,
  'merchantOrderId' => $merchantOrderId
]);
