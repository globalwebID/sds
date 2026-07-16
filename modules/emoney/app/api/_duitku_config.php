<?php
require_once dirname(__DIR__, 2) . '/config/runtime.php';
// return [
//   'environment'  => 'sandbox',
//   'merchantCode' => 'DS17319',
//   'apiKey'       => '7d4863bc71a4df2b6ef15bb1ad749e9b',

//   // URL callback/return dibentuk dari base URL installer.

//   // endpoint POP terbaru (sesuai docs)
//   'baseUrl'      => 'https://api-sandbox.duitku.com/api',
// ];

$duitku = (array)sds_config('services.duitku', []);
return [
  'environment'  => (string)($duitku['environment'] ?? 'production'),
  'merchantCode' => (string)($duitku['merchant_code'] ?? ''),
  'apiKey'       => (string)($duitku['api_key'] ?? ''),

  'callbackUrl'  => sds_base_url('emoney/api/duitku_callback.php'),
  'returnUrl'    => sds_base_url('emoney/dashboard/emoney.php?topup=return'),

  // POP Production Base URL yang benar:
  'baseUrl'      => (string)($duitku['base_url'] ?? 'https://api-prod.duitku.com/api'),
];
