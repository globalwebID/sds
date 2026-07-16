<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$file=null;$checksum=null;
foreach(array_slice($argv,1) as $arg){if(str_starts_with($arg,'--file='))$file=substr($arg,7);elseif(str_starts_with($arg,'--checksum='))$checksum=substr($arg,11);}
if(!$file){fwrite(STDERR,"Pemakaian: php tools/inspect-module-package.php --file=paket.zip [--checksum=sha256]\n");exit(2);}
require dirname(__DIR__).'/app/Core/Modules/PackageInspector.php';
try{$result=(new SdsPackageInspector(dirname(__DIR__)))->inspect($file,$checksum);echo json_encode(['status'=>'aman','id'=>$result['metadata']['id'],'package'=>$result['metadata']['package'],'version'=>$result['metadata']['version'],'files'=>$result['files'],'size'=>$result['size'],'sha256'=>$result['checksum'],'dependencies'=>$result['manifest']['dependencies']??[]],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;}catch(Throwable $e){fwrite(STDERR,'Paket ditolak: '.$e->getMessage().PHP_EOL);exit(1);}
