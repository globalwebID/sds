<?php
if(($_SERVER['REQUEST_METHOD']??'')!=='POST'){header('Location: teachers_rfid');exit;}
require_once __DIR__.'/../../config/perpus.php';
try{
 $token=(string)($_POST['csrf']??'');$session=(string)($_SESSION['csrf_teacher_rfid']??'');if($token===''||$session===''||!hash_equals($session,$token))throw new RuntimeException('Token formulir tidak valid.');
 $id=(int)($_POST['pegawai_id']??0);if($id<=0)throw new RuntimeException('Data pegawai tidak valid.');$stmt=$conn->prepare('SELECT nama_lengkap,nip FROM pegawai WHERE pegawai_id=? LIMIT 1');$stmt->bind_param('i',$id);$stmt->execute();$employee=$stmt->get_result()->fetch_assoc();$stmt->close();if(!$employee)throw new RuntimeException('Pegawai tidak ditemukan.');
 $action=(string)($_POST['action']??'save');$adminId=(int)($_SESSION['admin_id']??0);
 if($action==='remove'){sds_rfid_remove($conn,'pegawai',$id,$adminId,'dilepas','Dilepaskan melalui SDS');$_SESSION['success']='Kartu '.$employee['nama_lengkap'].' berhasil dilepaskan.';}
 else{$uid=trim((string)($_POST['rfid_uid']??''));sds_rfid_assign($conn,'pegawai',$id,$uid,$adminId,'Dipasangkan melalui SDS');$_SESSION['success']='Kode kartu '.$employee['nama_lengkap'].' berhasil disimpan.';}
}catch(Throwable $e){$_SESSION['error']=$e->getMessage();}
header('Location: teachers_rfid');exit;
