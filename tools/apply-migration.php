<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$name=$argv[1]??'';
if(!preg_match('/^[0-9]{3}_[a-z0-9_-]+\.sql$/',$name)){fwrite(STDERR,"Nama migrasi tidak valid.\n");exit(2);}
$root=dirname(__DIR__);$path=$root.'/install/migrations/'.$name;
if(!is_file($path)){fwrite(STDERR,"Migrasi tidak ditemukan.\n");exit(2);}
$sql=(string)file_get_contents($path);
if(preg_match('/^\s*DELIMITER\b/im',$sql)){fwrite(STDERR,"Migrasi dengan DELIMITER wajib dijalankan melalui klien MySQL.\n");exit(2);}
require $root.'/config/runtime.php';
try{
    $db=sds_mysqli('main');
    if(!$db->multi_query($sql))throw new RuntimeException($db->error);
    do{if($result=$db->store_result())$result->free();if($db->errno)throw new RuntimeException($db->error);}while($db->more_results()&&$db->next_result());
    echo 'Migrasi berhasil: '.$name.PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,'Migrasi gagal: '.$e->getMessage().PHP_EOL);exit(1);}
