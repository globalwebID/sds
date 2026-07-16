<?php
require __DIR__ . '/_config.php';

requireAuth();
header('Content-Type: application/json; charset=utf-8');

if (!function_exists('game_history_fail')) {
    function game_history_fail(string $message, $data = null, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        response(false, $message, $data);
    }
}

if (!function_exists('game_history_clean')) {
    function game_history_clean(?string $value): string
    {
        return trim((string)$value);
    }
}

if (!function_exists('game_history_status_label')) {
    function game_history_status_label(string $status): string
    {
        $status = strtoupper(trim($status));

        switch ($status) {
            case 'SUCCESS': return 'Berhasil';
            case 'PROCESSING': return 'Diproses';
            case 'FAILED': return 'Gagal';
            case 'REFUNDED': return 'Refund';
            case 'CREATED': return 'Dibuat';
            default: return $status !== '' ? $status : 'Unknown';
        }
    }
}

$idSiswa = (int)($_SESSION['id_siswa'] ?? 0);
if ($idSiswa <= 0) {
    game_history_fail('Session tidak valid', []);
}

$limit = (int)($_GET['limit'] ?? 100);
if ($limit <= 0) $limit = 100;
if ($limit > 200) $limit = 200;

$sql = "
    SELECT
        id,
        ref_id,
        brand,
        product_name,
        sku_code,
        user_id_game,
        zone_id,
        nickname,
        price_sell,
        status,
        provider_status,
        sn,
        message,
        refund_amount,
        refunded_at,
        created_at,
        updated_at
    FROM game_transactions
    WHERE id_siswa = ?
    ORDER BY id DESC
    LIMIT ?
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    game_history_fail('Prepare query riwayat game gagal', [
        'db_error' => mysqli_error($conn)
    ]);
}

mysqli_stmt_bind_param($stmt, 'ii', $idSiswa, $limit);

if (!mysqli_stmt_execute($stmt)) {
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    game_history_fail('Execute query riwayat game gagal', [
        'db_error' => $err
    ]);
}

$res = mysqli_stmt_get_result($stmt);
if (!$res) {
    $err = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    game_history_fail('Get result riwayat game gagal', [
        'db_error' => $err
    ]);
}

$data = [];
while ($r = mysqli_fetch_assoc($res)) {
    $statusRaw = (string)($r['status'] ?? '');
    $userIdGame = (string)($r['user_id_game'] ?? '');
    $zoneId = (string)($r['zone_id'] ?? '');

    $targetDisplay = $userIdGame;
    if ($zoneId !== '') {
        $targetDisplay .= ' (' . $zoneId . ')';
    }

    $data[] = [
        'id'              => (int)$r['id'],
        'ref_id'          => (string)$r['ref_id'],
        'brand'           => (string)$r['brand'],
        'product_name'    => (string)$r['product_name'],
        'sku_code'        => (string)$r['sku_code'],
        'user_id_game'    => $userIdGame,
        'zone_id'         => $zoneId,
        'nickname'        => (string)$r['nickname'],
        'target_display'  => $targetDisplay,
        'price_sell'      => (int)$r['price_sell'],
        'status'          => strtoupper($statusRaw),
        'status_label'    => game_history_status_label($statusRaw),
        'provider_status' => (string)$r['provider_status'],
        'sn'              => (string)$r['sn'],
        'message'         => (string)$r['message'],
        'refund_amount'   => (int)($r['refund_amount'] ?? 0),
        'refunded_at'     => $r['refunded_at'],
        'created_at'      => $r['created_at'],
        'updated_at'      => $r['updated_at'],
    ];
}

mysqli_stmt_close($stmt);

response(true, 'Riwayat top-up game', $data);