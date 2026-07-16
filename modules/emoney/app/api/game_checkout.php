<?php
require __DIR__ . '/_config.php';
require __DIR__ . '/_digiflazz_helper.php';

requireAuth();
requireCsrf();

header('Content-Type: application/json; charset=utf-8');

/*
|--------------------------------------------------------------------------
| Helper Response
|--------------------------------------------------------------------------
*/
function game_checkout_fail(string $message, $data = null, int $httpCode = 200): void
{
    http_response_code($httpCode);
    response(false, $message, $data);
}

function game_checkout_success(string $message, $data = null, int $httpCode = 200): void
{
    http_response_code($httpCode);
    response(true, $message, $data);
}

/*
|--------------------------------------------------------------------------
| Helper Input
|--------------------------------------------------------------------------
*/
function game_checkout_json_input(): array
{
    static $json = null;

    if ($json !== null) {
        return $json;
    }

    $raw = file_get_contents('php://input');
    if (!$raw) {
        $json = [];
        return $json;
    }

    $decoded = json_decode($raw, true);
    $json = is_array($decoded) ? $decoded : [];
    return $json;
}

function game_checkout_input(string $key, $default = '')
{
    if (isset($_POST[$key])) {
        return $_POST[$key];
    }

    $json = game_checkout_json_input();
    if (isset($json[$key])) {
        return $json[$key];
    }

    return $default;
}

function game_checkout_clean(?string $value): string
{
    return trim((string)$value);
}

/*
|--------------------------------------------------------------------------
| Helper DB
|--------------------------------------------------------------------------
*/
function game_checkout_begin(mysqli $conn): void
{
    mysqli_begin_transaction($conn);
}

function game_checkout_commit(mysqli $conn): void
{
    mysqli_commit($conn);
}

function game_checkout_rollback(mysqli $conn): void
{
    mysqli_rollback($conn);
}

function game_checkout_fetch_user_for_update(mysqli $conn, int $idSiswa): array
{
    $stmt = mysqli_prepare($conn, "SELECT id, saldo FROM pendaftaran_siswa WHERE id = ? FOR UPDATE");
    if (!$stmt) {
        throw new Exception('Prepare lock saldo gagal: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'i', $idSiswa);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    mysqli_stmt_close($stmt);

    if (!$row) {
        throw new Exception('Data siswa tidak ditemukan');
    }

    return $row;
}

function game_checkout_update_user_balance(mysqli $conn, int $idSiswa, int $newBalance): void
{
    $stmt = mysqli_prepare($conn, "UPDATE pendaftaran_siswa SET saldo = ? WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Prepare update saldo gagal: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, 'ii', $newBalance, $idSiswa);

    if (!mysqli_stmt_execute($stmt)) {
        $err = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception('Update saldo gagal: ' . $err);
    }

    mysqli_stmt_close($stmt);
}

/*
|--------------------------------------------------------------------------
| Refund Helper
|--------------------------------------------------------------------------
*/
function game_checkout_refund_balance(
    mysqli $conn,
    int $idSiswa,
    string $refId,
    int $refundAmount,
    string $refundMessage = ''
): array {
    game_checkout_begin($conn);

    try {
        $userRow = game_checkout_fetch_user_for_update($conn, $idSiswa);

        $beforeBalance = (int)($userRow['saldo'] ?? 0);
        $afterBalance  = $beforeBalance + $refundAmount;

        game_checkout_update_user_balance($conn, $idSiswa, $afterBalance);

        $status = 'REFUNDED';
        $providerStatus = 'failed';
        $refundedAt = date('Y-m-d H:i:s');

        $refundLog = json_encode([
            'type' => 'initial_refund',
            'time' => $refundedAt,
            'message' => $refundMessage,
            'refund_amount' => $refundAmount,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $stmtTrx = mysqli_prepare($conn, "
            UPDATE game_transactions
            SET
                status = ?,
                provider_status = ?,
                message = CASE
                    WHEN COALESCE(message, '') = '' THEN ?
                    ELSE CONCAT(message, '\n', ?)
                END,
                refund_amount = ?,
                refunded_at = ?,
                callback_response = CASE
                    WHEN COALESCE(callback_response, '') = '' THEN ?
                    ELSE CONCAT(callback_response, '\n', ?)
                END,
                updated_at = NOW()
            WHERE ref_id = ?
              AND (refunded_at IS NULL OR refund_amount = 0)
            LIMIT 1
        ");

        if (!$stmtTrx) {
            throw new Exception('Prepare update transaksi refund gagal: ' . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmtTrx,
            'ssssissss',
            $status,
            $providerStatus,
            $refundMessage,
            $refundMessage,
            $refundAmount,
            $refundedAt,
            $refundLog,
            $refundLog,
            $refId
        );

        if (!mysqli_stmt_execute($stmtTrx)) {
            $err = mysqli_stmt_error($stmtTrx);
            mysqli_stmt_close($stmtTrx);
            throw new Exception('Execute update transaksi refund gagal: ' . $err);
        }

        $affected = mysqli_stmt_affected_rows($stmtTrx);
        mysqli_stmt_close($stmtTrx);

        game_checkout_commit($conn);

        return [
            'success' => true,
            'before_balance' => $beforeBalance,
            'after_balance' => $afterBalance,
            'refund_amount' => $refundAmount,
            'transaction_updated' => $affected > 0,
        ];
    } catch (Throwable $e) {
        game_checkout_rollback($conn);

        return [
            'success' => false,
            'message' => $e->getMessage(),
        ];
    }
}

/*
|--------------------------------------------------------------------------
| Update transaksi lokal setelah provider response
|--------------------------------------------------------------------------
*/
function game_checkout_update_transaction_after_provider(
    mysqli $conn,
    string $refId,
    ?string $providerTrxId,
    string $providerStatus,
    string $status,
    ?string $sn,
    string $message,
    string $providerResponse
): bool {
    $stmt = mysqli_prepare($conn, "
        UPDATE game_transactions
        SET
            provider_trx_id = ?,
            provider_status = ?,
            status = ?,
            sn = ?,
            message = ?,
            provider_response = ?,
            updated_at = NOW()
        WHERE ref_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return false;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'sssssss',
        $providerTrxId,
        $providerStatus,
        $status,
        $sn,
        $message,
        $providerResponse,
        $refId
    );

    $ok = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    return (bool)$ok;
}

function game_checkout_mark_failed_before_refund(
    mysqli $conn,
    string $refId,
    ?string $providerTrxId,
    string $providerStatus,
    ?string $sn,
    string $message,
    string $providerResponse
): void {
    $stmt = mysqli_prepare($conn, "
        UPDATE game_transactions
        SET
            provider_trx_id = ?,
            provider_status = ?,
            status = 'FAILED',
            sn = ?,
            message = ?,
            provider_response = ?,
            updated_at = NOW()
        WHERE ref_id = ?
        LIMIT 1
    ");

    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssssss',
        $providerTrxId,
        $providerStatus,
        $sn,
        $message,
        $providerResponse,
        $refId
    );

    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

/*
|--------------------------------------------------------------------------
| Request Method
|--------------------------------------------------------------------------
*/
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    game_checkout_fail('Method harus POST', null, 405);
}

/*
|--------------------------------------------------------------------------
| Session
|--------------------------------------------------------------------------
*/
$idSiswa = (int)($_SESSION['id_siswa'] ?? 0);
if ($idSiswa <= 0) {
    game_checkout_fail('Session tidak valid');
}

/*
|--------------------------------------------------------------------------
| Input
|--------------------------------------------------------------------------
*/
$productId  = (int)game_checkout_input('product_id', 0);
$userIdGame = game_checkout_clean(game_checkout_input('user_id_game', ''));
$zoneId     = game_checkout_clean(game_checkout_input('zone_id', ''));
$serverId   = game_checkout_clean(game_checkout_input('server_id', ''));
$nickname   = game_checkout_clean(game_checkout_input('nickname', ''));

if ($productId <= 0) {
    game_checkout_fail('Produk belum dipilih');
}

if ($userIdGame === '') {
    game_checkout_fail('User ID game wajib diisi');
}

if (strlen($userIdGame) > 100) {
    game_checkout_fail('User ID game terlalu panjang');
}

if (strlen($zoneId) > 100) {
    game_checkout_fail('Zone ID terlalu panjang');
}

if (strlen($serverId) > 100) {
    game_checkout_fail('Server ID terlalu panjang');
}

if (strlen($nickname) > 150) {
    game_checkout_fail('Nickname terlalu panjang');
}

/*
|--------------------------------------------------------------------------
| Ambil produk aktif
|--------------------------------------------------------------------------
*/
$stmtProd = mysqli_prepare($conn, "
    SELECT
        id,
        provider,
        brand,
        category,
        type,
        sku_code,
        product_name,
        price_buy,
        price_sell,
        profit,
        is_active
    FROM game_products
    WHERE id = ?
      AND provider = 'digiflazz'
      AND is_active = 1
    LIMIT 1
");

if (!$stmtProd) {
    game_checkout_fail('Prepare produk gagal', ['db_error' => mysqli_error($conn)]);
}

mysqli_stmt_bind_param($stmtProd, 'i', $productId);
mysqli_stmt_execute($stmtProd);
$resProd = mysqli_stmt_get_result($stmtProd);
$product = $resProd ? mysqli_fetch_assoc($resProd) : null;
mysqli_stmt_close($stmtProd);

if (!$product) {
    game_checkout_fail('Produk tidak ditemukan atau tidak aktif');
}

$brand       = (string)$product['brand'];
$skuCode     = (string)$product['sku_code'];
$productName = (string)$product['product_name'];
$priceBuy    = (int)$product['price_buy'];
$priceSell   = (int)$product['price_sell'];
$profit      = (int)$product['profit'];

$needZoneId = (int)digiflazz_brand_need_zone_id($brand);
if ($needZoneId === 1 && $zoneId === '') {
    game_checkout_fail('Zone ID wajib diisi untuk game ini');
}

$customerNo = digiflazz_customer_no($userIdGame, $zoneId);
$refId      = digiflazz_make_ref_id($idSiswa);

/*
|--------------------------------------------------------------------------
| Potong saldo + simpan transaksi internal
|--------------------------------------------------------------------------
*/
$beforeBalance = 0;
$afterBalance  = 0;
$createdTransactionId = 0;

game_checkout_begin($conn);

try {
    $userRow = game_checkout_fetch_user_for_update($conn, $idSiswa);

    $beforeBalance = (int)($userRow['saldo'] ?? 0);

    if ($beforeBalance < $priceSell) {
        throw new Exception('Saldo e-Money tidak cukup');
    }

    $afterBalance = $beforeBalance - $priceSell;

    game_checkout_update_user_balance($conn, $idSiswa, $afterBalance);

    $status = 'CREATED';
    $providerStatus = 'created';
    $invoiceNo = $refId;
    $message = 'Transaksi dibuat dan menunggu request ke provider';
    $providerResponse = '';

    $stmtIns = mysqli_prepare($conn, "
        INSERT INTO game_transactions (
            ref_id,
            id_siswa,
            product_id,
            provider,
            provider_trx_id,
            invoice_no,
            brand,
            product_name,
            sku_code,
            user_id_game,
            zone_id,
            server_id,
            nickname,
            price_buy,
            price_sell,
            profit,
            before_balance,
            after_balance,
            status,
            provider_status,
            sn,
            message,
            provider_response,
            callback_response,
            refund_amount,
            refunded_at,
            created_at,
            updated_at
        ) VALUES (
            ?, ?, ?, 'digiflazz', NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, NULL, 0, NULL, NOW(), NOW()
        )
    ");

    if (!$stmtIns) {
        throw new Exception('Prepare insert transaksi gagal: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmtIns,
        'siissssssssiiiiissss',
        $refId,
        $idSiswa,
        $productId,
        $invoiceNo,
        $brand,
        $productName,
        $skuCode,
        $userIdGame,
        $zoneId,
        $serverId,
        $nickname,
        $priceBuy,
        $priceSell,
        $profit,
        $beforeBalance,
        $afterBalance,
        $status,
        $providerStatus,
        $message,
        $providerResponse
    );

    if (!mysqli_stmt_execute($stmtIns)) {
        $err = mysqli_stmt_error($stmtIns);
        mysqli_stmt_close($stmtIns);
        throw new Exception('Insert transaksi gagal: ' . $err);
    }

    $createdTransactionId = (int)mysqli_insert_id($conn);
    mysqli_stmt_close($stmtIns);

    game_checkout_commit($conn);
} catch (Throwable $e) {
    game_checkout_rollback($conn);
    game_checkout_fail($e->getMessage());
}

/*
|--------------------------------------------------------------------------
| Kirim request ke Digiflazz
|--------------------------------------------------------------------------
*/
$providerResult = digiflazz_create_transaction($skuCode, $customerNo, $refId, $priceBuy);
$providerData   = $providerResult['provider'] ?? null;
$providerRaw    = $providerResult['debug']['raw'] ?? null;
$providerRawStr = is_string($providerRaw)
    ? $providerRaw
    : json_encode($providerResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

$providerStatusRaw = '';
$providerMappedStatus = 'PROCESSING';
$providerTrxId = null;
$sn = null;
$providerMessage = 'Order diteruskan ke provider';

if (is_array($providerData)) {
    $providerStatusRaw = (string)($providerData['status'] ?? '');
    $providerMappedStatus = digiflazz_map_status($providerStatusRaw);
    $providerTrxId = (string)($providerData['tr_id'] ?? $providerData['transaction_id'] ?? '');
    $sn = (string)($providerData['sn'] ?? '');
    $providerMessage = (string)($providerData['message'] ?? $providerData['rc'] ?? 'Order diteruskan ke provider');
} else {
    if (!empty($providerResult['success'])) {
        $providerMappedStatus = 'PROCESSING';
        $providerStatusRaw = 'pending';
        $providerMessage = 'Provider menerima request, menunggu update status';
    } else {
        $providerMappedStatus = 'FAILED';
        $providerStatusRaw = 'failed';
        $providerMessage = (string)($providerResult['message'] ?? 'Gagal request ke provider');
    }
}

/*
|--------------------------------------------------------------------------
| Jika request provider gagal / failed langsung refund
|--------------------------------------------------------------------------
*/
if (empty($providerResult['success']) || $providerMappedStatus === 'FAILED') {
    $refundMessage = 'Transaksi gagal di provider. Saldo dikembalikan.';
    if ($providerMessage !== '') {
        $refundMessage .= ' ' . $providerMessage;
    }

    game_checkout_mark_failed_before_refund(
        $conn,
        $refId,
        $providerTrxId,
        $providerStatusRaw,
        $sn,
        $providerMessage,
        $providerRawStr
    );

    $refund = game_checkout_refund_balance($conn, $idSiswa, $refId, $priceSell, $refundMessage);

    if (!$refund['success']) {
        game_checkout_fail('Provider gagal dan refund juga gagal', [
            'ref_id' => $refId,
            'provider_status' => $providerStatusRaw,
            'provider_message' => $providerMessage,
            'refund_error' => $refund['message'] ?? 'Unknown refund error',
        ]);
    }

    game_checkout_fail('Transaksi gagal, saldo sudah dikembalikan', [
        'transaction_id'  => $createdTransactionId,
        'ref_id'          => $refId,
        'status'          => 'REFUNDED',
        'provider_status' => $providerStatusRaw,
        'provider_trx_id' => $providerTrxId,
        'sn'              => $sn,
        'message'         => $refundMessage,
        'brand'           => $brand,
        'product_name'    => $productName,
        'sku_code'        => $skuCode,
        'target' => [
            'user_id_game' => $userIdGame,
            'zone_id'      => $zoneId,
            'server_id'    => $serverId,
            'nickname'     => $nickname,
            'customer_no'  => $customerNo,
        ],
        'payment' => [
            'before_balance' => $beforeBalance,
            'after_balance'  => $beforeBalance,
            'price_sell'     => $priceSell,
            'refund_amount'  => $priceSell,
        ],
        'refund' => $refund,
    ]);
}

/*
|--------------------------------------------------------------------------
| Update status awal transaksi setelah provider menerima request
|--------------------------------------------------------------------------
*/
$finalStatus = $providerMappedStatus;
if ($finalStatus === 'CREATED') {
    $finalStatus = 'PROCESSING';
}

$updated = game_checkout_update_transaction_after_provider(
    $conn,
    $refId,
    $providerTrxId,
    $providerStatusRaw,
    $finalStatus,
    $sn,
    $providerMessage,
    $providerRawStr
);

if (!$updated) {
    game_checkout_fail('Request provider berhasil, tapi update transaksi lokal gagal', [
        'ref_id' => $refId,
        'db_error' => mysqli_error($conn),
    ]);
}

/*
|--------------------------------------------------------------------------
| Sukses response ke frontend
|--------------------------------------------------------------------------
*/
game_checkout_success('Checkout top-up game berhasil dibuat', [
    'transaction_id'   => $createdTransactionId,
    'ref_id'           => $refId,
    'status'           => $finalStatus,
    'provider_status'  => $providerStatusRaw,
    'provider_trx_id'  => $providerTrxId,
    'sn'               => $sn,
    'message'          => $providerMessage,
    'brand'            => $brand,
    'product_name'     => $productName,
    'sku_code'         => $skuCode,
    'target' => [
        'user_id_game' => $userIdGame,
        'zone_id'      => $zoneId,
        'server_id'    => $serverId,
        'nickname'     => $nickname,
        'customer_no'  => $customerNo,
    ],
    'payment' => [
        'before_balance' => $beforeBalance,
        'after_balance'  => $afterBalance,
        'price_sell'     => $priceSell,
    ],
]);
