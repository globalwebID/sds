<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$file=null;$checksum=null;$apply=false;$confirmed=false;
foreach(array_slice($argv,1) as $arg){if(str_starts_with($arg,'--file='))$file=substr($arg,7);elseif(str_starts_with($arg,'--checksum='))$checksum=substr($arg,11);elseif($arg==='--apply')$apply=true;elseif($arg==='--yes')$confirmed=true;}
if(!$file){fwrite(STDERR,"Pemakaian: php tools/install-module-package.php --file=paket.zip [--checksum=sha256] [--apply --yes]\n");exit(2);}
if($apply&&!$confirmed){fwrite(STDERR,"Pemasangan nyata memerlukan --apply --yes. Jalankan tanpa --apply untuk staging aman.\n");exit(2);}
require dirname(__DIR__).'/app/Core/Modules/PackageInstaller.php';
try{$installer=new SdsPackageInstaller(dirname(__DIR__));$result=$apply?$installer->apply($file,$checksum):$installer->stage($file,$checksum);echo json_encode(['status'=>$apply?'terpasang':'staging_lulus','module'=>$result['metadata']['id'],'version'=>$result['metadata']['version'],'files'=>$result['files'],'changed'=>count($result['changed']??[]),'created'=>count($result['created']??[]),'migrations'=>$result['migrations']??[],'database_backup'=>$result['database_backup']??null,'work_directory'=>$result['work_directory'],'backup_directory'=>$result['backup_directory']??null],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;}catch(Throwable $e){fwrite(STDERR,'Instalasi gagal: '.$e->getMessage().PHP_EOL);exit(1);}
