<?php
require_once dirname(__DIR__, 3) . '/config/runtime.php';
sds_session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . sds_base_url('mkantin/admin/login.php?error=' . rawurlencode('Silakan login terlebih dahulu')));
    exit;
}
