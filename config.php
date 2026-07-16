<?php
require_once __DIR__ . '/config/runtime.php';
sds_require_installed();

try {
    $cfg = sds_database_config('main');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $cfg['host'], $cfg['port'], $cfg['database'], $cfg['charset']);
    $pdo = new PDO($dsn, (string)$cfg['username'], (string)$cfg['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (Throwable $e) {
    error_log('[SDS PDO] ' . $e->getMessage());
    http_response_code(500);
    exit('Koneksi database tidak tersedia. Periksa konfigurasi aplikasi.');
}

