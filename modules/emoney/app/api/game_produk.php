<?php
require __DIR__ . '/_config.php';
require __DIR__ . '/_digiflazz_helper.php';

requireAuth();
header('Content-Type: application/json; charset=utf-8');

if (!function_exists('game_produk_fail')) {
    function game_produk_fail(string $message, $data = null, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        response(false, $message, $data);
    }
}

if (!function_exists('game_produk_clean')) {
    function game_produk_clean(?string $value): string
    {
        return trim((string)$value);
    }
}

if (!function_exists('game_produk_table_exists')) {
    function game_produk_table_exists(mysqli $conn, string $table): bool
    {
        $table = trim($table);
        if ($table === '') {
            return false;
        }

        $safe = mysqli_real_escape_string($conn, $table);
        $sql  = "SHOW TABLES LIKE '{$safe}'";
        $q    = mysqli_query($conn, $sql);

        return $q && mysqli_num_rows($q) > 0;
    }
}

if (!function_exists('game_logo_public_url')) {
    function game_logo_public_url(?string $logo): string
    {
        $logo = trim((string)$logo);
        if ($logo === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $logo)) {
            return $logo;
        }

        /*
         |--------------------------------------------------------------
         | SESUAIKAN JIKA PERLU
         |--------------------------------------------------------------
         | Contoh 1:
         | Logo tersimpan dan diakses lewat:
         | https://domainanda.com/mkantin/uploads/game-logo/xxx.png
         | maka gunakan '/mkantin/'
         |
         | Contoh 2:
         | Logo diakses lewat:
         | https://domainanda.com/uploads/game-logo/xxx.png
         | maka ubah jadi '/'
         */
        $basePrefix = '/sds/mkantin';

        return rtrim($basePrefix, '/') . '/' . ltrim($logo, '/');
    }
}

/*
|--------------------------------------------------------------------------
| Validasi tabel
|--------------------------------------------------------------------------
*/
$hasProducts = game_produk_table_exists($conn, 'game_products');
if (!$hasProducts) {
    game_produk_fail('Tabel game_products tidak ditemukan');
}

$hasBrands = game_produk_table_exists($conn, 'game_brands');

/*
|--------------------------------------------------------------------------
| Filter opsional
|--------------------------------------------------------------------------
*/
$brandFilter  = game_produk_clean($_GET['brand'] ?? '');
$searchFilter = game_produk_clean($_GET['search'] ?? '');
$activeOnly   = isset($_GET['active']) ? (int)$_GET['active'] : 1;

/*
|--------------------------------------------------------------------------
| Ambil brand summary
|--------------------------------------------------------------------------
*/
$whereBrand   = ["gp.provider = 'digiflazz'"];
$typesBrand   = '';
$paramsBrand  = [];

if ($activeOnly === 1) {
    $whereBrand[] = "gp.is_active = 1";
}

if ($searchFilter !== '') {
    $whereBrand[] = "gp.brand LIKE ?";
    $typesBrand  .= 's';
    $paramsBrand[] = '%' . $searchFilter . '%';
}

$whereBrandSql = implode(' AND ', $whereBrand);

if ($hasBrands) {
    $sqlBrands = "
        SELECT
            gp.brand,
            COUNT(*) AS total,
            MAX(gb.logo) AS logo
        FROM game_products gp
        LEFT JOIN game_brands gb ON gp.brand_id = gb.id
        WHERE $whereBrandSql
        GROUP BY gp.brand
        ORDER BY gp.brand ASC
    ";
} else {
    $sqlBrands = "
        SELECT
            gp.brand,
            COUNT(*) AS total,
            '' AS logo
        FROM game_products gp
        WHERE $whereBrandSql
        GROUP BY gp.brand
        ORDER BY gp.brand ASC
    ";
}

$stmtBrands = mysqli_prepare($conn, $sqlBrands);
if (!$stmtBrands) {
    game_produk_fail('Prepare query brand gagal', [
        'db_error' => mysqli_error($conn)
    ]);
}

if ($typesBrand !== '') {
    $bindValues   = [];
    $bindValues[] = $typesBrand;
    foreach ($paramsBrand as $k => $v) {
        $bindValues[] = &$paramsBrand[$k];
    }
    call_user_func_array([$stmtBrands, 'bind_param'], $bindValues);
}

if (!mysqli_stmt_execute($stmtBrands)) {
    $err = mysqli_stmt_error($stmtBrands);
    mysqli_stmt_close($stmtBrands);
    game_produk_fail('Execute query brand gagal', [
        'db_error' => $err
    ]);
}

$resBrands = mysqli_stmt_get_result($stmtBrands);
if (!$resBrands) {
    $err = mysqli_stmt_error($stmtBrands);
    mysqli_stmt_close($stmtBrands);
    game_produk_fail('Get result brand gagal', [
        'db_error' => $err
    ]);
}

$brands = [];
while ($row = mysqli_fetch_assoc($resBrands)) {
    $brandName = (string)($row['brand'] ?? '');
    $logoPath  = (string)($row['logo'] ?? '');

    $brands[] = [
        'brand'        => $brandName,
        'total'        => (int)($row['total'] ?? 0),
        'need_zone_id' => (int)digiflazz_brand_need_zone_id($brandName),
        'logo'         => $logoPath,
        'logo_url'     => game_logo_public_url($logoPath),
    ];
}
mysqli_stmt_close($stmtBrands);

/*
|--------------------------------------------------------------------------
| Ambil produk
|--------------------------------------------------------------------------
*/
$whereProducts  = ["gp.provider = 'digiflazz'"];
$typesProducts  = '';
$paramsProducts = [];

if ($activeOnly === 1) {
    $whereProducts[] = "gp.is_active = 1";
}

if ($brandFilter !== '') {
    $whereProducts[] = "gp.brand = ?";
    $typesProducts  .= 's';
    $paramsProducts[] = $brandFilter;
}

if ($searchFilter !== '') {
    $whereProducts[] = "(gp.brand LIKE ? OR gp.product_name LIKE ? OR gp.sku_code LIKE ?)";
    $typesProducts  .= 'sss';
    $paramsProducts[] = '%' . $searchFilter . '%';
    $paramsProducts[] = '%' . $searchFilter . '%';
    $paramsProducts[] = '%' . $searchFilter . '%';
}

$whereProductsSql = implode(' AND ', $whereProducts);

if ($hasBrands) {
    $sqlProducts = "
        SELECT
            gp.id,
            gp.provider,
            gp.brand,
            gp.category,
            gp.type,
            gp.sku_code,
            gp.product_name,
            gp.price_buy,
            gp.price_sell,
            gp.profit,
            gp.seller_name,
            gp.buyer_product_status,
            gp.buyer_last_update,
            gp.is_active,
            gp.sort_order,
            gp.created_at,
            gp.updated_at,
            gb.logo
        FROM game_products gp
        LEFT JOIN game_brands gb ON gp.brand_id = gb.id
        WHERE $whereProductsSql
        ORDER BY gp.brand ASC, gp.sort_order ASC, gp.price_sell ASC, gp.id ASC
    ";
} else {
    $sqlProducts = "
        SELECT
            gp.id,
            gp.provider,
            gp.brand,
            gp.category,
            gp.type,
            gp.sku_code,
            gp.product_name,
            gp.price_buy,
            gp.price_sell,
            gp.profit,
            gp.seller_name,
            gp.buyer_product_status,
            gp.buyer_last_update,
            gp.is_active,
            gp.sort_order,
            gp.created_at,
            gp.updated_at,
            '' AS logo
        FROM game_products gp
        WHERE $whereProductsSql
        ORDER BY gp.brand ASC, gp.sort_order ASC, gp.price_sell ASC, gp.id ASC
    ";
}

$stmtProducts = mysqli_prepare($conn, $sqlProducts);
if (!$stmtProducts) {
    game_produk_fail('Prepare query produk gagal', [
        'db_error' => mysqli_error($conn)
    ]);
}

if ($typesProducts !== '') {
    $bindValues   = [];
    $bindValues[] = $typesProducts;
    foreach ($paramsProducts as $k => $v) {
        $bindValues[] = &$paramsProducts[$k];
    }
    call_user_func_array([$stmtProducts, 'bind_param'], $bindValues);
}

if (!mysqli_stmt_execute($stmtProducts)) {
    $err = mysqli_stmt_error($stmtProducts);
    mysqli_stmt_close($stmtProducts);
    game_produk_fail('Execute query produk gagal', [
        'db_error' => $err
    ]);
}

$resProducts = mysqli_stmt_get_result($stmtProducts);
if (!$resProducts) {
    $err = mysqli_stmt_error($stmtProducts);
    mysqli_stmt_close($stmtProducts);
    game_produk_fail('Get result produk gagal', [
        'db_error' => $err
    ]);
}

$products = [];
while ($row = mysqli_fetch_assoc($resProducts)) {
    $brandName = (string)($row['brand'] ?? '');
    $logoPath  = (string)($row['logo'] ?? '');

    $products[] = [
        'id'                   => (int)($row['id'] ?? 0),
        'provider'             => (string)($row['provider'] ?? ''),
        'brand'                => $brandName,
        'category'             => (string)($row['category'] ?? ''),
        'type'                 => (string)($row['type'] ?? ''),
        'sku_code'             => (string)($row['sku_code'] ?? ''),
        'product_name'         => (string)($row['product_name'] ?? ''),
        'price_buy'            => (int)($row['price_buy'] ?? 0),
        'price_sell'           => (int)($row['price_sell'] ?? 0),
        'profit'               => (int)($row['profit'] ?? 0),
        'seller_name'          => (string)($row['seller_name'] ?? ''),
        'buyer_product_status' => (int)($row['buyer_product_status'] ?? 0),
        'buyer_last_update'    => $row['buyer_last_update'] ?? null,
        'is_active'            => (int)($row['is_active'] ?? 0),
        'sort_order'           => (int)($row['sort_order'] ?? 0),
        'need_zone_id'         => (int)digiflazz_brand_need_zone_id($brandName),
        'created_at'           => $row['created_at'] ?? null,
        'updated_at'           => $row['updated_at'] ?? null,
        'logo'                 => $logoPath,
        'logo_url'             => game_logo_public_url($logoPath),
    ];
}
mysqli_stmt_close($stmtProducts);

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/
response(true, 'Daftar produk game', [
    'filters' => [
        'brand'  => $brandFilter,
        'search' => $searchFilter,
        'active' => $activeOnly,
    ],
    'brands'         => $brands,
    'products'       => $products,
    'total_brands'   => count($brands),
    'total_products' => count($products),
    'has_brand_logo' => $hasBrands ? 1 : 0,
]);