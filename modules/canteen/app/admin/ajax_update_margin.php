<?php
require 'inc/fungsi.php';

header('Content-Type: application/json');

$brand = trim($_POST['brand'] ?? '');
$margin = (int)($_POST['margin'] ?? 0);

if($brand == ''){
    echo json_encode(['success'=>false,'message'=>'Brand kosong']);
    exit;
}

// update master margin
mysqli_query($conn, "
    INSERT INTO game_margin_brand (brand, margin)
    VALUES ('".mysqli_real_escape_string($conn,$brand)."',$margin)
    ON DUPLICATE KEY UPDATE margin=$margin
");

// update produk
mysqli_query($conn, "
    UPDATE game_products
    SET 
        profit = $margin,
        price_sell = price_buy + $margin
    WHERE brand = '".mysqli_real_escape_string($conn,$brand)."'
");

echo json_encode(['success'=>true]);