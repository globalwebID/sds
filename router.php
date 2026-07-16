<?php
$path=ltrim((string)parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH),'/');$routeName=(string)preg_replace('/\.php$/i','',$path);$enrollment=array (
  0 => 'formulir',
  1 => 'form',
  2 => 'upload',
  3 => 'proses_map',
  4 => 'ambil_field_aktif',
  5 => 'cetak_daftar_ulang',
  6 => 'cetak_dokumen_daftar_ulang',
  7 => 'instructions',
  8 => 'progress',
);if(in_array($routeName,$enrollment,true))$path='modules/enrollment/app/'.$routeName.'.php';$map=array (
  'absensi' => 'modules/attendance/app',
  'anjungan' => 'modules/kiosk/app',
  'mkantin' => 'modules/canteen/app',
  'emoney' => 'modules/emoney/app',
  'perpustakaan' => 'modules/library/app',
);foreach($map as $prefix=>$destination)if($path===$prefix||str_starts_with($path,$prefix.'/')){$path=$destination.substr($path,strlen($prefix));break;}$file=__DIR__.'/'.$path;if(is_dir($file))$file=rtrim($file,'/').'/index.php';if(is_file($file)){if(strtolower(pathinfo($file,PATHINFO_EXTENSION))==='php'){chdir(dirname($file));require $file;}else{readfile($file);}return true;}return false;
