<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$root=dirname(__DIR__);$map=require $root.'/packaging/schema-map.php';
$sql=(string)file_get_contents($root.'/install/database.sql');
preg_match_all('/CREATE\s+TABLE\s+`([^`]+)`/i',$sql,$matches);
$tables=array_values(array_unique($matches[1]??[]));sort($tables);
$owners=[];$duplicates=[];
foreach($map as $module=>$moduleTables){
    foreach($moduleTables as $table){
        if(isset($owners[$table]))$duplicates[$table]=[$owners[$table],$module];
        $owners[$table]=$module;
    }
}
$unmapped=array_values(array_diff($tables,array_keys($owners)));
$stale=array_values(array_diff(array_keys($owners),$tables));
echo 'Tabel database.sql : '.count($tables).PHP_EOL;
foreach($map as $module=>$moduleTables)echo str_pad(strtoupper($module),12).': '.count($moduleTables).PHP_EOL;
if($unmapped)echo 'BELUM DIPETAKAN : '.implode(', ',$unmapped).PHP_EOL;
if($duplicates)foreach($duplicates as $table=>$modules)echo 'DUPLIKAT         : '.$table.' ('.implode(', ',$modules).')'.PHP_EOL;
if($stale)echo 'HANYA DI PETA    : '.implode(', ',$stale).PHP_EOL;
if($unmapped||$duplicates){fwrite(STDERR,'Audit schema gagal.'.PHP_EOL);exit(1);}
echo 'STATUS           : aman, seluruh tabel memiliki tepat satu pemilik.'.PHP_EOL;
