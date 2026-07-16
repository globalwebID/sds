<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$module=null;$apply=false;$confirmed=false;
foreach(array_slice($argv,1) as $arg){if(str_starts_with($arg,'--module='))$module=substr($arg,9);elseif($arg==='--apply')$apply=true;elseif($arg==='--yes')$confirmed=true;}
if(!$module||!preg_match('/^[a-z][a-z0-9_-]*$/',$module)){fwrite(STDERR,"Pemakaian: php tools/reconcile-module-migrations.php --module=library [--apply --yes]\n");exit(2);}
if($apply&&!$confirmed){fwrite(STDERR,"Rekonsiliasi nyata memerlukan --apply --yes.\n");exit(2);}
$root=dirname(__DIR__);$evidenceFile=$root.'/modules/'.$module.'/migration-evidence.php';if(!is_file($evidenceFile)){fwrite(STDERR,"Bukti migrasi modul tidak tersedia.\n");exit(2);}
require $root.'/config/runtime.php';$evidence=require $evidenceFile;$db=sds_mysqli('main');$database=(string)sds_database_config('main')['database'];$failures=[];$verified=[];
$tableExists=$db->prepare('SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? LIMIT 1');
$columnInfo=$db->prepare('SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1');
foreach($evidence as $migration=>$requirements){$missing=[];
 foreach((array)($requirements['tables']??[]) as $table){$tableExists->bind_param('ss',$database,$table);$tableExists->execute();if(!$tableExists->get_result()->fetch_row())$missing[]='table:'.$table;}
 foreach((array)($requirements['columns']??[]) as $table=>$columns)foreach($columns as $column){$columnInfo->bind_param('sss',$database,$table,$column);$columnInfo->execute();if(!$columnInfo->get_result()->fetch_row())$missing[]='column:'.$table.'.'.$column;}
 foreach((array)($requirements['column_contains']??[]) as $qualified=>$needle){[$table,$column]=explode('.',$qualified,2);$columnInfo->bind_param('sss',$database,$table,$column);$columnInfo->execute();$row=$columnInfo->get_result()->fetch_assoc();if(!$row||stripos((string)$row['COLUMN_TYPE'],(string)$needle)===false)$missing[]='definition:'.$qualified.' contains '.$needle;}
 $path=$root.'/'.$migration;if(!is_file($path))$missing[]='file:'.$migration;
 if($missing){$failures[$migration]=$missing;continue;}$verified[$migration]=hash_file('sha256',$path);
}
$tableExists->close();$columnInfo->close();
foreach($verified as $migration=>$checksum)echo '[OK] '.$migration.' '.$checksum.PHP_EOL;
foreach($failures as $migration=>$missing)echo '[GAGAL] '.$migration.' -> '.implode(', ',$missing).PHP_EOL;
if($failures){fwrite(STDERR,'Rekonsiliasi dibatalkan karena bukti schema belum lengkap.'.PHP_EOL);exit(1);}
if(!$apply){$registered=0;$checkRegistry=$db->prepare('SELECT checksum FROM sds_module_migrations WHERE module_id=? AND (migration=? OR migration LIKE ?) ORDER BY id DESC LIMIT 1');foreach($verified as $migration=>$checksum){$like='%/'.basename($migration);$checkRegistry->bind_param('sss',$module,$migration,$like);$checkRegistry->execute();$row=$checkRegistry->get_result()->fetch_assoc();if($row&&hash_equals((string)$row['checksum'],$checksum))$registered++;elseif($row){fwrite(STDERR,'Checksum registry berbeda untuk '.$migration.PHP_EOL);exit(1);}}$checkRegistry->close();if($registered===count($verified))echo 'AUDIT LULUS: seluruh '.count($verified).' migrasi sudah tercatat dan konsisten.'.PHP_EOL;else echo 'AUDIT LULUS: '.($registered).' tercatat, gunakan --apply --yes untuk mencatat '.(count($verified)-$registered).' migrasi lama.'.PHP_EOL;exit;}
$existingStatement=$db->prepare('SELECT checksum FROM sds_module_migrations WHERE module_id=? AND (migration=? OR migration LIKE ?) ORDER BY id DESC LIMIT 1');
foreach($verified as $migration=>$checksum){$like='%/'.basename($migration);$existingStatement->bind_param('sss',$module,$migration,$like);$existingStatement->execute();$existing=$existingStatement->get_result()->fetch_assoc();if($existing&&!hash_equals((string)$existing['checksum'],$checksum)){fwrite(STDERR,'Checksum registry berbeda untuk '.$migration.PHP_EOL);exit(1);}}
$existingStatement->close();$statement=$db->prepare('INSERT IGNORE INTO sds_module_migrations (module_id,migration,checksum) VALUES (?,?,?)');
foreach($verified as $migration=>$checksum){$statement->bind_param('sss',$module,$migration,$checksum);$statement->execute();}
$statement->close();$db->close();echo 'REKONSILIASI SELESAI: '.count($verified).' migrasi dicatat berdasarkan bukti schema.'.PHP_EOL;
