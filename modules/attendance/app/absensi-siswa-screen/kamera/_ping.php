<?php
header('Content-Type: text/plain; charset=utf-8');

echo "PING OK\n";
echo "PHP: " . PHP_VERSION . "\n";

$base = __DIR__;
echo "DIR: $base\n";

$cfg = __DIR__ . '/../sw-library/sw-config.php';
echo "config exists? " . (file_exists($cfg) ? "YES" : "NO") . "\n";

if (file_exists($cfg)) {
  require $cfg;
  echo "config loaded\n";
}
