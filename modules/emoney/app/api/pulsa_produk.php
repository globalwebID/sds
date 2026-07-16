<?php
require_once '_digiflazz_helper.php';

$type = $_GET['type'] ?? 'pulsa'; // pulsa / data
$operator = $_GET['operator'] ?? '';

$res = digiflazz_fetch_pricelist();

if(!$res['success']){
    echo json_encode([]);
    exit;
}

$output = [];

foreach($res['items'] as $item){

    $category = strtolower($item['category']);
    $brand    = $item['brand'];

    // filter type
    if($type == 'pulsa' && $category != 'pulsa') continue;
    if($type == 'data' && $category != 'data') continue;

    // filter operator (jika dipilih)
    if($operator && stripos($brand, $operator) === false) continue;

    $harga = digiflazz_make_sell_price(
        $brand,
        (int)$item['price']
    );

    $output[] = [
        "kode"  => $item['buyer_sku_code'],
        "nama"  => $item['product_name'],
        "harga" => $harga['price_sell'],
        "brand" => $brand
    ];
}

echo json_encode($output);