<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/runtime.php';
sds_session_start();
define('SDS_PERPUSTAKAAN_APP', true);
$root=dirname(__DIR__,2);require_once $root.'/db.php';require_once $root.'/config/perpus.php';
try{sds_perpus_ensure_schema($conn);}catch(Throwable $e){http_response_code(503);exit('Layanan belum tersedia.');}
function feedback_h($v):string{return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$school=['nama_sekolah'=>'Sekolah','logo'=>''];$r=$conn->query('SELECT nama_sekolah,logo FROM pengaturan LIMIT 1');if($r&&($x=$r->fetch_assoc()))$school=array_merge($school,$x);
$active=sds_perpus_setting_value($conn,'saran_aktif','1')==='1';$required=sds_perpus_setting_value($conn,'saran_wajib_identitas','0')==='1';
if(!$active){http_response_code(503);exit('Form kritik dan saran sedang dinonaktifkan.');}
if(empty($_SESSION['perpus_public_csrf']))$_SESSION['perpus_public_csrf']=bin2hex(random_bytes(24));
$message='';$error='';
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 try{
  $posted=(string)($_POST['csrf']??'');if($posted===''||!hash_equals((string)$_SESSION['perpus_public_csrf'],$posted))throw new RuntimeException('Sesi formulir tidak valid. Muat ulang halaman.');
  $category=(string)($_POST['kategori']??'saran');if(!in_array($category,['kritik','saran','keluhan','apresiasi'],true))throw new RuntimeException('Kategori tidak valid.');
  $title=trim((string)($_POST['judul']??''));$text=trim((string)($_POST['pesan']??''));$name=trim((string)($_POST['nama']??''));$contact=trim((string)($_POST['kontak']??''));$memberNo=trim((string)($_POST['nomor_anggota']??''));$rfid=trim((string)($_POST['rfid']??''));
  if(mb_strlen($title)<4||mb_strlen($title)>180)throw new RuntimeException('Judul harus berisi 4–180 karakter.');if(mb_strlen($text)<10||mb_strlen($text)>5000)throw new RuntimeException('Pesan harus berisi 10–5.000 karakter.');
  $ip=mb_substr((string)($_SERVER['REMOTE_ADDR']??''),0,45);$stmt=$conn->prepare('SELECT COUNT(*) total FROM perpus_saran WHERE ip_address=? AND created_at>=DATE_SUB(NOW(),INTERVAL 1 HOUR)');$stmt->bind_param('s',$ip);$stmt->execute();$sent=(int)($stmt->get_result()->fetch_assoc()['total']??0);$stmt->close();if($sent>=5)throw new RuntimeException('Batas pengiriman tercapai. Silakan mencoba kembali satu jam lagi.');
  $memberId=0;
  if($memberNo!==''||$rfid!==''){
   if($memberNo===''||$rfid==='')throw new RuntimeException('Nomor anggota dan RFID harus diisi bersama untuk verifikasi identitas.');
   $stmt=$conn->prepare('SELECT * FROM perpus_anggota WHERE nomor_anggota=? AND status_keanggotaan=\'aktif\' LIMIT 1');$stmt->bind_param('s',$memberNo);$stmt->execute();$member=$stmt->get_result()->fetch_assoc();$stmt->close();if(!$member)throw new RuntimeException('Nomor anggota tidak ditemukan atau tidak aktif.');
   if(!in_array($member['pemilik_tipe'],['siswa','pegawai'],true))throw new RuntimeException('Anggota lama perlu diperbarui ke master SDS untuk verifikasi RFID.');
   $owner=(string)$member['pemilik_tipe'];$ownerId=(int)$member['pemilik_id'];$stmt=$conn->prepare('SELECT id FROM kartu_rfid WHERE uid=? AND pemilik_tipe=? AND pemilik_id=? LIMIT 1');$stmt->bind_param('ssi',$rfid,$owner,$ownerId);$stmt->execute();$valid=(bool)$stmt->get_result()->fetch_assoc();$stmt->close();if(!$valid)throw new RuntimeException('RFID tidak sesuai dengan nomor anggota.');
   $memberId=(int)$member['id'];$profile=sds_perpus_identity_profile($conn,$owner,$ownerId,$member);$name=(string)$profile['nama'];
  }elseif($required)throw new RuntimeException('Identitas anggota diwajibkan. Isi nomor anggota dan RFID.');
  if($memberId===0 && mb_strlen($name)>150)throw new RuntimeException('Nama terlalu panjang.');if(mb_strlen($contact)>150)throw new RuntimeException('Kontak terlalu panjang.');
  $source='opac';$stmt=$conn->prepare("INSERT INTO perpus_saran (anggota_id,nama_pengirim,kontak,kategori,judul,pesan,sumber,ip_address) VALUES (NULLIF(?,0),NULLIF(?,''),NULLIF(?,''),?,?,?, ?,?)");$stmt->bind_param('isssssss',$memberId,$name,$contact,$category,$title,$text,$source,$ip);$stmt->execute();$id=(int)$conn->insert_id;$stmt->close();
  sds_perpus_audit_log($conn,'create','saran',$id,'Masukan publik diterima: '.$title,null,['kategori'=>$category,'anggota_id'=>$memberId],0);
  $message='Terima kasih. Kritik atau saran Anda sudah diterima dengan nomor #'.$id.'.';$_SESSION['perpus_public_csrf']=bin2hex(random_bytes(24));$_POST=[];
 }catch(Throwable $e){$error=$e->getMessage();}
}
$logo=!empty($school['logo'])?sds_base_url('uploads/logo/'.rawurlencode(basename((string)$school['logo']))):'';$base=sds_base_url('perpustakaan/opac/');
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kritik & Saran · <?=feedback_h($school['nama_sekolah'])?></title><link rel="stylesheet" href="assets/opac.css?v=2.6.1"></head><body>
<header class="opac-header"><div class="opac-container opac-header-inner"><a class="opac-brand" href="<?=$base?>"><?php if($logo):?><img src="<?=feedback_h($logo)?>" alt="Logo"><?php else:?><span class="opac-logo">📚</span><?php endif;?><span><strong>Kritik & Saran</strong><small><?=feedback_h($school['nama_sekolah'])?></small></span></a><nav><a href="<?=$base?>">Katalog</a><a href="akun.php">Akun Saya</a><a class="active" href="saran.php">Kritik & Saran</a></nav></div></header>
<main class="opac-feedback-page"><section class="opac-feedback-hero"><div class="opac-container"><h1>Bantu kami meningkatkan layanan</h1><p>Sampaikan kritik, saran, keluhan, atau apresiasi untuk Perpustakaan sekolah.</p></div></section><div class="opac-container opac-feedback-wrap">
<?php if($message):?><div class="opac-alert success"><?=feedback_h($message)?></div><?php endif;?><?php if($error):?><div class="opac-alert danger"><?=feedback_h($error)?></div><?php endif;?>
<form method="post" class="opac-feedback-form"><input type="hidden" name="csrf" value="<?=feedback_h($_SESSION['perpus_public_csrf'])?>"><div class="opac-feedback-note"><strong>Identitas anggota bersifat <?= $required?'wajib':'opsional' ?>.</strong> <?= $required?'Masukkan nomor anggota dan RFID yang sesuai.':'Tanpa identitas, masukan akan dicatat sebagai anonim.' ?></div><div class="feedback-grid"><label>Kategori<select name="kategori" required><?php foreach(['saran'=>'Saran','kritik'=>'Kritik','keluhan'=>'Keluhan','apresiasi'=>'Apresiasi'] as $v=>$l):?><option value="<?=$v?>" <?=($_POST['kategori']??'saran')===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></label><label>Nama<input name="nama" maxlength="150" value="<?=feedback_h($_POST['nama']??'')?>" placeholder="Opsional untuk pengirim anonim"></label><label>Kontak<input name="kontak" maxlength="150" value="<?=feedback_h($_POST['kontak']??'')?>" placeholder="Email atau nomor HP (opsional)"></label><label>Nomor Anggota<input name="nomor_anggota" maxlength="64" value="<?=feedback_h($_POST['nomor_anggota']??'')?>" autocomplete="off"></label><label>RFID<input name="rfid" maxlength="64" value="<?=feedback_h($_POST['rfid']??'')?>" autocomplete="off" placeholder="Tempel kartu bila identitas digunakan"></label><label class="wide">Judul<input name="judul" maxlength="180" value="<?=feedback_h($_POST['judul']??'')?>" required></label><label class="wide">Pesan<textarea name="pesan" rows="7" maxlength="5000" required><?=feedback_h($_POST['pesan']??'')?></textarea></label></div><button type="submit">Kirim Kritik & Saran</button></form></div></main><footer><div class="opac-container">E-Perpustakaan SDS · <?=feedback_h($school['nama_sekolah'])?></div></footer></body></html>
