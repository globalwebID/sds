<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
perpus_session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !sds_csrf_verify($_POST['csrf'] ?? null)) { http_response_code(405); exit('Permintaan tidak valid.'); }
sds_session_destroy();
header('Location: ' . sds_base_url('perpustakaan/login'));
