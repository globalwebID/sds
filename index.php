<?php
require_once __DIR__ . '/config/runtime.php';
if (!sds_is_installed()) {
    header('Location: install/');
    exit;
}
session_start();
header("Location: instructions");
exit;
?>
