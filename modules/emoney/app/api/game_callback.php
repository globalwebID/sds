<?php
require __DIR__ . '/_config.php';
require __DIR__ . '/_digiflazz_helper.php';

header('Content-Type: application/json; charset=utf-8');

$cfg = digiflazz_cfg();

/*
|--------------------------------------------------------------------------
| Helper response
|--------------------------------------------------------------------------
*/
function cb_ok(string $message = 'OK', array $extra = []): void
{
    echo json_encode(array_merge([
        'success' => true,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function cb_fail(string $message = 'ERROR', int $httpCode = 400, array $extra = []): void
{
    unset($extra['db_error'], $extra['error'], $extra['sql'], $extra['query']);
    http_response_code($httpCode);
    echo json_encode(array_merge([
        'success' => false,
        'message' => $message,
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/*
|--------------------------------------------------------------------------
| Helper: log callback
|--------------------------------------------------------------------------
*/
function cb_log_payload(mysqli $conn, ?string $refId, string $payload): void
{
    $ip = trim((string)($_SERVER['REMOTE_ADDR'] ?? ''));
    $ua = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

    $sql = "
        INSERT INTO game_callback_logs (
            ref_id,
            payload,
            ip_address,
            user_agent,
            created_at
        ) VALUES (?, ?, ?, ?, NOW())
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'ssss', $refId, $payload, $ip, $ua);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

/*
|--------------------------------------------------------------------------
| Helper: verify webhook secret if configured
|--------------------------------------------------------------------------
*/
function cb_verify_webhook_secret(string $rawBody, array $cfg): array
{
    $secret = trim((string)($cfg['webhook_secret'] ?? ''));
    if ($secret === '') {
        return [
            'ok' => false,
            'checked' => false,
            'message' => 'Webhook belum dikonfigurasi',
        ];
    }

    $header = trim((string)($_SERVER['HTTP_X_HUB_SIGNATURE'] ?? ''));
    if ($header === '') {
        return [
            'ok' => false,
            'checked' => true,
            'message' => 'Header X-Hub-Signature tidak ditemukan',
        ];
    }

    $expected = 'sha1=' . hash_hmac('sha1', $rawBody, $secret);

    if (!hash_equals($expected, $header)) {
        return [
            'ok' => false,
            'checked' => true,
            'message' => 'Signature webhook tidak valid',
        ];
    }

    return [
        'ok' => true,
        'checked' => true,
        'message' => 'Signature valid',
    ];
}

/*
|--------------------------------------------------------------------------
| Ambil raw JSON
|--------------------------------------------------------------------------
*/
$raw = file_get_contents('php://input');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') cb_fail('Method tidak diizinkan', 405);
if (strlen((string)$raw) > 1024 * 1024) cb_fail('Payload terlalu besar', 413);
if (!$raw || trim($raw) === '') {
    cb_fail('Payload kosong', 400);
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    cb_fail('Invalid JSON', 400, [
        'json_error' => json_last_error_msg(),
    ]);
}

/*
|--------------------------------------------------------------------------
| Validasi webhook secret (jika diisi)
|--------------------------------------------------------------------------
*/
$verify = cb_verify_webhook_secret($raw, $cfg);
if (!$verify['ok']) {
    cb_log_payload($conn, null, $raw);
    cb_fail($verify['message'], 403);
}

/*
|--------------------------------------------------------------------------
| Ambil payload callback Digiflazz
|--------------------------------------------------------------------------
*/
$payload = $data['data'] ?? null;
if (!is_array($payload)) {
    cb_log_payload($conn, null, $raw);
    cb_fail('Payload data tidak ditemukan', 400);
}

$refId        = trim((string)($payload['ref_id'] ?? ''));
$buyerSkuCode = trim((string)($payload['buyer_sku_code'] ?? ''));
$statusRaw    = trim((string)($payload['status'] ?? ''));
$sn           = trim((string)($payload['sn'] ?? ''));
$message      = trim((string)($payload['message'] ?? ''));
$rc           = trim((string)($payload['rc'] ?? ''));
$customerNo   = trim((string)($payload['customer_no'] ?? ''));

cb_log_payload($conn, $refId !== '' ? $refId : null, $raw);

if ($refId === '') {
    cb_fail('ref_id tidak ditemukan pada callback', 400);
}

/*
|--------------------------------------------------------------------------
| Ambil transaksi lokal
|--------------------------------------------------------------------------
*/
$stmt = mysqli_prepare($conn, "
    SELECT
        id,
        ref_id,
        id_siswa,
        sku_code,
        price_sell,
        status,
        refund_amount,
        refunded_at
    FROM game_transactions
    WHERE ref_id = ?
    LIMIT 1
");

if (!$stmt) {
    cb_fail('Prepare query transaksi gagal', 500, [
        'db_error' => mysqli_error($conn),
    ]);
}

mysqli_stmt_bind_param($stmt, 's', $refId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$trx = $res ? mysqli_fetch_assoc($res) : null;
mysqli_stmt_close($stmt);

if (!$trx) {
    cb_fail('Transaction not found', 404, [
        'ref_id' => $refId,
    ]);
}

/*
|--------------------------------------------------------------------------
| Validasi ringan SKU jika ada
|--------------------------------------------------------------------------
*/
if ($buyerSkuCode !== '' && strcasecmp((string)$trx['sku_code'], $buyerSkuCode) !== 0) {
    cb_fail('SKU callback tidak cocok', 400, [
        'ref_id' => $refId,
        'local_sku' => (string)$trx['sku_code'],
        'callback_sku' => $buyerSkuCode,
    ]);
}

/*
|--------------------------------------------------------------------------
| Mapping status
|--------------------------------------------------------------------------
*/
$mappedStatus = digiflazz_map_status($statusRaw);
if ($mappedStatus === 'CREATED') {
    $mappedStatus = 'PROCESSING';
}

$callbackLog = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$finalMessage = $message !== '' ? $message : ('Callback status: ' . $mappedStatus);
if ($rc !== '') {
    $finalMessage .= ' | RC: ' . $rc;
}

/*
|--------------------------------------------------------------------------
| Update transaksi utama
|--------------------------------------------------------------------------
*/
$stmtUpd = mysqli_prepare($conn, "
    UPDATE game_transactions
    SET
        status = ?,
        provider_status = ?,
        sn = CASE WHEN ? <> '' THEN ? ELSE sn END,
        message = ?,
        callback_response = ?,
        updated_at = NOW()
    WHERE id = ? AND status NOT IN ('SUCCESS','REFUNDED')
    LIMIT 1
");

if (!$stmtUpd) {
    cb_fail('Prepare update transaksi gagal', 500, [
        'db_error' => mysqli_error($conn),
    ]);
}

$providerStatus = $statusRaw !== '' ? $statusRaw : strtolower($mappedStatus);
$trxId = (int)$trx['id'];

mysqli_stmt_bind_param(
    $stmtUpd,
    'ssssssi',
    $mappedStatus,
    $providerStatus,
    $sn,
    $sn,
    $finalMessage,
    $callbackLog,
    $trxId
);

if (!mysqli_stmt_execute($stmtUpd)) {
    $err = mysqli_stmt_error($stmtUpd);
    mysqli_stmt_close($stmtUpd);
    cb_fail('Execute update transaksi gagal', 500, [
        'db_error' => $err,
    ]);
}
mysqli_stmt_close($stmtUpd);

/*
|--------------------------------------------------------------------------
| Jika gagal dan belum refund -> refund saldo
|--------------------------------------------------------------------------
*/
if ($mappedStatus === 'FAILED') {
    $refundAmount = (int)($trx['price_sell'] ?? 0);
    $alreadyRefunded = !empty($trx['refunded_at']) || (int)($trx['refund_amount'] ?? 0) > 0;

    if (!$alreadyRefunded && $refundAmount > 0) {
        mysqli_begin_transaction($conn);

        try {
            $idSiswa = (int)$trx['id_siswa'];

            $stmtTrxLock = $conn->prepare('SELECT refund_amount,refunded_at,status FROM game_transactions WHERE id=? FOR UPDATE');
            $stmtTrxLock->bind_param('i', $trxId);
            $stmtTrxLock->execute();
            $freshTrx = $stmtTrxLock->get_result()->fetch_assoc();
            $stmtTrxLock->close();
            if (!$freshTrx) throw new RuntimeException('Transaksi tidak ditemukan saat refund');
            if (!empty($freshTrx['refunded_at']) || (int)$freshTrx['refund_amount'] > 0 || $freshTrx['status'] === 'REFUNDED') {
                mysqli_commit($conn);
                cb_ok('Callback already refunded', ['ref_id'=>$refId,'status'=>'REFUNDED']);
            }

            $stmtUser = mysqli_prepare($conn, "
                SELECT saldo
                FROM pendaftaran_siswa
                WHERE id = ?
                FOR UPDATE
            ");
            if (!$stmtUser) {
                throw new Exception('Prepare lock saldo gagal: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmtUser, 'i', $idSiswa);
            mysqli_stmt_execute($stmtUser);
            $resUser = mysqli_stmt_get_result($stmtUser);
            $user = $resUser ? mysqli_fetch_assoc($resUser) : null;
            mysqli_stmt_close($stmtUser);

            if (!$user) {
                throw new Exception('User tidak ditemukan');
            }

            $saldoBaru = (int)$user['saldo'] + $refundAmount;

            $stmtBal = mysqli_prepare($conn, "
                UPDATE pendaftaran_siswa
                SET saldo = ?
                WHERE id = ?
            ");
            if (!$stmtBal) {
                throw new Exception('Prepare update saldo gagal: ' . mysqli_error($conn));
            }

            mysqli_stmt_bind_param($stmtBal, 'ii', $saldoBaru, $idSiswa);
            if (!mysqli_stmt_execute($stmtBal)) {
                $err = mysqli_stmt_error($stmtBal);
                mysqli_stmt_close($stmtBal);
                throw new Exception('Update saldo gagal: ' . $err);
            }
            mysqli_stmt_close($stmtBal);

            $stmtRefund = mysqli_prepare($conn, "
                UPDATE game_transactions
                SET
                    refund_amount = ?,
                    refunded_at = NOW(),
                    status = 'REFUNDED',
                    provider_status = ?,
                    message = ?,
                    updated_at = NOW()
                WHERE id = ?
                LIMIT 1
            ");
            if (!$stmtRefund) {
                throw new Exception('Prepare update refund gagal: ' . mysqli_error($conn));
            }

            $refundProviderStatus = $providerStatus;
            $refundMessage = 'Transaksi gagal di provider. Saldo dikembalikan.';
            if ($finalMessage !== '') {
                $refundMessage .= ' ' . $finalMessage;
            }

            mysqli_stmt_bind_param(
                $stmtRefund,
                'issi',
                $refundAmount,
                $refundProviderStatus,
                $refundMessage,
                $trxId
            );

            if (!mysqli_stmt_execute($stmtRefund)) {
                $err = mysqli_stmt_error($stmtRefund);
                mysqli_stmt_close($stmtRefund);
                throw new Exception('Execute refund gagal: ' . $err);
            }
            mysqli_stmt_close($stmtRefund);

            mysqli_commit($conn);
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            cb_fail('Callback masuk tapi refund gagal', 500, [
                'ref_id' => $refId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

cb_ok('Callback processed', [
    'ref_id' => $refId,
    'status' => $mappedStatus,
    'customer_no' => $customerNo,
]);
