<?php
require_once dirname(__DIR__,2).'/config/runtime.php';
$centralControlConnection=$centralControlConnection??sds_mysqli('main');
sds_apply_central_controls($centralControlConnection,'E-Money');
