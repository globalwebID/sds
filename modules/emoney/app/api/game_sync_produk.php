<?php
require __DIR__ . '/_config.php';
require __DIR__ . '/_digiflazz_helper.php';

$cfg = digiflazz_cfg();

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
$isCli = (PHP_SAPI === 'cli');
$keyFromRequest = trim((string)($_GET['key'] ?? $_POST['key'] ?? ''));
$syncKey = trim((string)($cfg['sync_key'] ?? ''));

$authorized = false;

if ($isCli) {
    $authorized = true;
} elseif ($syncKey !== '' && hash_equals($syncKey, $keyFromRequest)) {
    $authorized = true;
} elseif (!empty($_SESSION['login']) && $_SESSION['login'] === true) {
    $authorized = true;
}

if (!$authorized) {
    http_response_code(401);
    response(false, 'Unauthorized sync');
}

/*
|--------------------------------------------------------------------------
| AMBIL DATA DIGIFLAZZ
|--------------------------------------------------------------------------
*/
$fetch = digiflazz_fetch_pricelist();

if (!$fetch['success']) {
    response(false, 'Gagal ambil pricelist', $fetch['debug'] ?? null);
}

$items = $fetch['items'] ?? [];

if (!is_array($items) || count($items) === 0) {
    response(false, 'Pricelist kosong');
}

/*
|--------------------------------------------------------------------------
| PREPARE QUERY (ANTI RESET MARGIN)
|--------------------------------------------------------------------------
*/
$sql = "
INSERT INTO game_products (
    provider,
    brand,
    category,
    type,
    sku_code,
    product_name,
    price_buy,
    price_sell,
    profit,
    seller_name,
    buyer_product_status,
    buyer_last_update,
    is_active,
    sort_order,
    created_at,
    updated_at
) VALUES (
    'digiflazz',
    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
)
ON DUPLICATE KEY UPDATE
    brand = VALUES(brand),
    category = VALUES(category),
    type = VALUES(type),
    product_name = VALUES(product_name),

    -- UPDATE HARGA BELI SAJA
    price_buy = VALUES(price_buy),

    -- 🔥 PENTING: PAKAI PROFIT LAMA (ANTI RESET)
    price_sell = VALUES(price_buy) + game_products.profit,

    seller_name = VALUES(seller_name),
    buyer_product_status = VALUES(buyer_product_status),
    buyer_last_update = VALUES(buyer_last_update),
    is_active = VALUES(is_active),
    sort_order = VALUES(sort_order),
    updated_at = NOW()
";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    response(false, 'Prepare gagal', mysqli_error($conn));
}

$now = date('Y-m-d H:i:s');

$total = 0;
$processedSku = [];

/*
|--------------------------------------------------------------------------
| LOOP DATA
|--------------------------------------------------------------------------
*/
foreach ($items as $item) {

    if (!is_array($item)) continue;
    if (!digiflazz_is_game_product($item)) continue;

    $brand       = digiflazz_normalize_text($item['brand'] ?? '');
    $category    = digiflazz_normalize_text($item['category'] ?? '');
    $type        = digiflazz_normalize_text($item['type'] ?? '');
    $skuCode     = digiflazz_normalize_text($item['buyer_sku_code'] ?? '');
    $productName = digiflazz_normalize_text($item['product_name'] ?? '');
    $sellerName  = digiflazz_normalize_text($item['seller_name'] ?? '');
    $priceBuy    = (int)($item['price'] ?? 0);

    if ($brand === '' || $skuCode === '' || $priceBuy <= 0) continue;

    /*
    |--------------------------------------------------------------------------
    | AMBIL / SET MARGIN DEFAULT
    |--------------------------------------------------------------------------
    */
    mysqli_query($conn, "
        INSERT IGNORE INTO game_margin_brand (brand, margin)
        VALUES ('" . mysqli_real_escape_string($conn, $brand) . "', 500)
    ");

    $margin = 500;

    $qMargin = mysqli_query($conn, "
        SELECT margin FROM game_margin_brand 
        WHERE brand = '" . mysqli_real_escape_string($conn, $brand) . "' 
        LIMIT 1
    ");

    if ($qMargin && mysqli_num_rows($qMargin) > 0) {
        $d = mysqli_fetch_assoc($qMargin);
        $margin = (int)$d['margin'];
    }

    /*
    |--------------------------------------------------------------------------
    | CEK PRODUK SUDAH ADA?
    |--------------------------------------------------------------------------
    */
    $qExist = mysqli_query($conn, "
        SELECT profit FROM game_products 
        WHERE sku_code = '" . mysqli_real_escape_string($conn, $skuCode) . "' 
        LIMIT 1
    ");

    if ($qExist && mysqli_num_rows($qExist) > 0) {
        // 🔥 PAKAI PROFIT LAMA
        $d = mysqli_fetch_assoc($qExist);
        $profit = (int)$d['profit'];
    } else {
        // produk baru
        $profit = $margin;
    }

    $priceSell = $priceBuy + $profit;

    $buyerActive  = !empty($item['buyer_product_status']) ? 1 : 0;
    $sellerActive = !empty($item['seller_product_status']) ? 1 : 0;
    $isActive     = ($buyerActive && $sellerActive) ? 1 : 0;

    $sortOrder = $priceBuy;

    mysqli_stmt_bind_param(
        $stmt,
        'sssssiiisisii',
        $brand,
        $category,
        $type,
        $skuCode,
        $productName,
        $priceBuy,
        $priceSell,
        $profit,
        $sellerName,
        $buyerActive,
        $now,
        $isActive,
        $sortOrder
    );

    if (!mysqli_stmt_execute($stmt)) {
        continue;
    }

    $processedSku[] = $skuCode;
    $total++;
}

mysqli_stmt_close($stmt);

/*
|--------------------------------------------------------------------------
| NONAKTIFKAN PRODUK YANG HILANG
|--------------------------------------------------------------------------
*/
if (!empty($processedSku)) {

    $list = implode(',', array_map(function ($sku) use ($conn) {
        return "'" . mysqli_real_escape_string($conn, $sku) . "'";
    }, $processedSku));

    mysqli_query($conn, "
        UPDATE game_products
        SET is_active = 0, updated_at = NOW()
        WHERE provider = 'digiflazz'
        AND sku_code NOT IN ($list)
    ");
}

/*
|--------------------------------------------------------------------------
| RESPONSE
|--------------------------------------------------------------------------
*/
response(true, 'Sync berhasil (margin aman)', [
    'total_processed' => $total
]);