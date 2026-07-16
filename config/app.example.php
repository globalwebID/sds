<?php
// Contoh saja. File config/app.php dibuat otomatis oleh installer dan jangan dipublikasikan.
return [
    'app' => ['name'=>'SDS','base_url'=>'http://localhost/sds','timezone'=>'Asia/Jakarta'],
    'databases' => [
        'main' => ['host'=>'127.0.0.1','port'=>3306,'database'=>'sds','username'=>'root','password'=>'','charset'=>'utf8mb4'],
        // Modul Absensi memakai database utama yang sama dengan SDS.
        'attendance' => ['host'=>'127.0.0.1','port'=>3306,'database'=>'sds','username'=>'root','password'=>'','charset'=>'utf8mb4'],
    ],
    'security' => ['sync_token'=>'','print_secret'=>'','sso_secret'=>''],
    'services' => [
        'whatsapp' => ['url'=>'','api_key'=>'','sender'=>''],
        'duitku' => ['environment'=>'production','merchant_code'=>'','api_key'=>'','base_url'=>'https://api-prod.duitku.com/api'],
        'digiflazz' => ['username'=>'','api_key'=>'','webhook_secret'=>'','sync_key'=>'','base_url'=>'https://api.digiflazz.com/v1','testing'=>false],
    ],
];
