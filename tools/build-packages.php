<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
date_default_timezone_set('Asia/Jakarta');
$root=dirname(__DIR__);$definitions=require $root.'/packaging/packages.php';
$requested='all';foreach(array_slice($argv,1) as $arg)if(str_starts_with($arg,'--module='))$requested=substr($arg,9);
$targets=$requested==='all'?array_keys($definitions):array_filter(array_map('trim',explode(',',$requested)));
foreach($targets as $target)if(!isset($definitions[$target]))throw new RuntimeException('Paket tidak dikenal: '.$target);
$outputDir=$root.'/storage/packages';if(!is_dir($outputDir)&&!mkdir($outputDir,0750,true)&&!is_dir($outputDir))throw new RuntimeException('Folder output paket tidak dapat dibuat.');

$normalize=static fn(string $path):string=>str_replace('\\','/',ltrim($path,'/\\'));
$match=static function(string $path,string $pattern):bool{$path=str_replace('\\','/',$path);$pattern=str_replace(['\\','**','*'],['/','__DOUBLE__','[^/]*'],$pattern);$pattern=str_replace('__DOUBLE__','.*',$pattern);return preg_match('#^'.$pattern.'$#i',$path)===1;};
$alwaysBlocked=['config/app.php','config/app.php.tmp','.env','.git/**','storage/**','uploads/**','tmp_dompdf/**','*.log','*.lock','*.state.json','*.sql','packaging/output/**'];
$allowedSqlPrefixes=['install/database.sql','install/schema/','install/seeds/','install/migrations/','install/uninstall_','modules/'];

foreach($targets as $id){
 $definition=$definitions[$id];$files=[];$missing=[];
 foreach($definition['include'] as $include){$relative=$normalize($include);$absolute=$root.'/'.$relative;if(!file_exists($absolute)){$missing[]=$relative;continue;}if(is_file($absolute)){$files[$relative]=$absolute;continue;}$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute,FilesystemIterator::SKIP_DOTS));foreach($iterator as $file){if(!$file->isFile())continue;$path=$normalize(substr($file->getPathname(),strlen($root)+1));$files[$path]=$file->getPathname();}}
 if($missing)throw new RuntimeException($id.' kehilangan file wajib: '.implode(', ',$missing));
 foreach(array_keys($files) as $path){
  $blocked=false;foreach($alwaysBlocked as $pattern){if($match($path,$pattern)){$blocked=true;break;}}
  if(in_array($path,['uploads/.htaccess','storage/.htaccess'],true))$blocked=false;
  if(str_ends_with(strtolower($path),'.sql')){$blocked=true;foreach($allowedSqlPrefixes as $prefix)if(str_starts_with($path,$prefix)){$blocked=false;break;}}
  foreach((array)($definition['exclude']??[]) as $pattern)if($match($path,$pattern)){$blocked=true;break;}
  if($blocked)unset($files[$path]);
 }
 ksort($files);$stamp=date('Ymd-His');$base=$definition['package'].'-'.$definition['version'].'-'.$stamp;$zipPath=$outputDir.'/'.$base.'.zip';
 $zip=new ZipArchive();if($zip->open($zipPath,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true)throw new RuntimeException('ZIP tidak dapat dibuat: '.$zipPath);
 $overrides=[];foreach($files as $path=>$absolute){if(!$zip->addFile($absolute,$path)){ $zip->close();throw new RuntimeException('Gagal menambahkan '.$path);}}
 if($id==='core')foreach(['vendor/composer/autoload_files.php','vendor/composer/autoload_static.php'] as $autoloadPath){if(!isset($files[$autoloadPath]))continue;$contents=(string)file_get_contents($files[$autoloadPath]);$contents=(string)preg_replace('/^.*google\\/apiclient-services\\/autoload\.php.*\R/m','',$contents);$overrides[$autoloadPath]=$contents;$zip->deleteName($autoloadPath);if(!$zip->addFromString($autoloadPath,$contents)){$zip->close();throw new RuntimeException('Gagal menormalisasi '.$autoloadPath);}}
 $fileHashes=[];foreach($files as $path=>$absolute)$fileHashes[$path]=isset($overrides[$path])?hash('sha256',$overrides[$path]):hash_file('sha256',$absolute);
 $metadata=['format'=>2,'id'=>$id,'package'=>$definition['package'],'version'=>$definition['version'],'type'=>$definition['type']??'overlay','built_at'=>date(DATE_ATOM),'php'=>PHP_VERSION,'files'=>count($files),'file_hashes'=>$fileHashes];
 $zip->addFromString('package.json',json_encode($metadata,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");$zip->close();
 if(!is_file($zipPath)||filesize($zipPath)<100)throw new RuntimeException('Paket kosong: '.$zipPath);
 $hash=hash_file('sha256',$zipPath);file_put_contents($zipPath.'.sha256',$hash.'  '.basename($zipPath).PHP_EOL,LOCK_EX);
 echo strtoupper($id).': '.basename($zipPath).' | '.count($files).' file | '.number_format(filesize($zipPath)/1048576,2).' MB | '.$hash.PHP_EOL;
}
