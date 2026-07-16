<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$root=dirname(__DIR__);
require_once $root.'/config/runtime.php';
$conn=sds_mysqli('main');
$settings=$conn->query('SELECT id,backup_schedule,backup_retention_days,last_backup_at FROM pengaturan ORDER BY id LIMIT 1')->fetch_assoc()?:[];
$schedule=(string)($settings['backup_schedule']??'disabled');
if ($schedule==='disabled') { echo "Backup terjadwal nonaktif.\n"; exit(0); }
$last=!empty($settings['last_backup_at'])?strtotime((string)$settings['last_backup_at']):0;
$dueSeconds=$schedule==='weekly'?604800:86400;
if ($last>0 && time()-$last<$dueSeconds) { echo "Backup belum jatuh tempo.\n"; exit(0); }

$command=[PHP_BINARY,$root.'/tools/backup.php'];
$pipes=[];$process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root);
if(!is_resource($process)) throw new RuntimeException('Runner backup tidak dapat dijalankan.');
fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);$code=proc_close($process);
if($code!==0) throw new RuntimeException('Backup terjadwal gagal: '.trim((string)$err));
$id=(int)($settings['id']??0);$stmt=$conn->prepare('UPDATE pengaturan SET last_backup_at=NOW() WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();
$retention=max(7,(int)($settings['backup_retention_days']??30));$cutoff=time()-($retention*86400);
foreach(glob($root.'/storage/backups/*')?:[] as $file){if(is_file($file)&&filemtime($file)<$cutoff&&preg_match('/\.(sql|zip|sha256)$/i',$file))@unlink($file);}
echo $out;
