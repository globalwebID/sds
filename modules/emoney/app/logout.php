<?php
require_once __DIR__ . '/../config/runtime.php';
sds_session_start();
sds_session_destroy();
header("Location: index.php");
exit;
