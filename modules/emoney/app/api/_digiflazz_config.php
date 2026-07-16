<?php
require_once dirname(__DIR__, 2) . '/config/runtime.php';
$service = (array)sds_config('services.digiflazz', []);
return [
    /*
    |--------------------------------------------------------------------------
    | Kredensial Digiflazz
    |--------------------------------------------------------------------------
    | Ambil dari menu Pengaturan Koneksi API Digiflazz
    */
    'username' => (string)($service['username'] ?? ''),
    'api_key'  => (string)($service['api_key'] ?? ''),

    /*
    |--------------------------------------------------------------------------
    | Endpoint
    |--------------------------------------------------------------------------
    */
    'base_url' => (string)($service['base_url'] ?? 'https://api.digiflazz.com/v1'),

    /*
    |--------------------------------------------------------------------------
    | Callback
    |--------------------------------------------------------------------------
    | Pastikan URL ini bisa diakses publik
    */
    'callback_url' => sds_base_url('emoney/api/game_callback.php'),

    /*
    |--------------------------------------------------------------------------
    | Keamanan sinkron produk
    |--------------------------------------------------------------------------
    | Pakai key ini saat memanggil:
    | https://domain-anda/api/game_sync_produk.php?key=ISI_SYNC_KEY
    */
    'sync_key' => (string)($service['sync_key'] ?? ''),
    'webhook_secret' => (string)($service['webhook_secret'] ?? ''),

    /*
    |--------------------------------------------------------------------------
    | Mode testing
    |--------------------------------------------------------------------------
    | true  = request transaksi kirim "testing": true
    | false = live production
    */
    'testing' => (bool)($service['testing'] ?? false),

    /*
    |--------------------------------------------------------------------------
    | Margin default
    |--------------------------------------------------------------------------
    | Dipakai kalau brand tidak ada di margin_rules
    */
    'margin_default' => 2000,

    /*
    |--------------------------------------------------------------------------
    | Margin per brand
    |--------------------------------------------------------------------------
    | Key brand harus sama seperti brand dari Digiflazz
    */
    'margin_rules' => [
        'Mobile Legends' => 2000,
        'Free Fire'      => 1500,
        'PUBG MOBILE'    => 2000,
        'Valorant'       => 2500,
        'Roblox'         => 2500,
        'Steam Wallet'   => 3000,
        'Honor of Kings' => 2000,
    ],

    /*
    |--------------------------------------------------------------------------
    | Filter game yang ingin diambil
    |--------------------------------------------------------------------------
    | Kalau kosong, helper akan coba ambil semua category/brand bertema game.
    | Saya sarankan isi brand yang memang mau Anda jual dulu.
    */
    'allowed_brands' => [
        'Mobile Legends',
        'Free Fire',
        'PUBG MOBILE',
        'Valorant',
        'Roblox',
        'Steam Wallet',
        'Honor of Kings',
    ],

    /*
    |--------------------------------------------------------------------------
    | Brand yang butuh zone_id
    |--------------------------------------------------------------------------
    */
    'brands_need_zone_id' => [
        'Mobile Legends',
    ],

    /*
    |--------------------------------------------------------------------------
    | Timeout cURL
    |--------------------------------------------------------------------------
    */
    'connect_timeout' => 15,
    'timeout'         => 45,
];
