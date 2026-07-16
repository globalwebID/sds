<?php
if (!defined('SDS_PERPUSTAKAAN_APP')) {
    http_response_code(403);
    exit('Akses langsung tidak diizinkan.');
}

require_once dirname(__DIR__, 2) . '/config/perpus.php';

try {
    sds_perpus_ensure_schema($conn);
} catch (Throwable $e) {
    error_log('[Perpustakaan schema] ' . $e->getMessage());
    throw new RuntimeException('Struktur database Perpustakaan belum dapat disiapkan. Periksa hak akses database atau jalankan migrasi instalasi.');
}

$perpusRole = (string)($_SESSION['perpus_user_role'] ?? 'staf');
$perpusAccess = ['allowed' => true, 'role' => $perpusRole];
$perpusIsAdmin = $perpusRole === 'admin';
$perpusCanManage = in_array($perpusRole, ['admin', 'staf'], true);

if (!function_exists('perpus_h')) {
    function perpus_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('perpus_money')) {
    function perpus_money($value): string
    {
        return 'Rp ' . number_format((float)$value, 0, ',', '.');
    }
}
if (!function_exists('perpus_csrf')) {
    function perpus_csrf(): string
    {
        if (empty($_SESSION['perpus_csrf'])) {
            $_SESSION['perpus_csrf'] = bin2hex(random_bytes(24));
        }
        return (string)$_SESSION['perpus_csrf'];
    }
}
if (!function_exists('perpus_check_csrf')) {
    function perpus_check_csrf(): void
    {
        $session = (string)($_SESSION['perpus_csrf'] ?? '');
        $posted = (string)($_POST['csrf'] ?? '');
        if ($session === '' || $posted === '' || !hash_equals($session, $posted)) {
            throw new RuntimeException('Token formulir tidak valid. Muat ulang halaman lalu coba kembali.');
        }
    }
}
