<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/config/runtime.php';
sds_session_start();
define('SDS_PERPUSTAKAAN_APP', true);
$root = dirname(__DIR__, 2);
require_once $root . '/db.php';
require_once $root . '/config/perpus.php';
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
try { sds_perpus_ensure_schema($conn); } catch (Throwable) { http_response_code(503); exit('Kiosk belum dapat digunakan.'); }
function kh($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
$school=['nama_sekolah'=>'Sekolah','logo'=>''];$r=$conn->query('SELECT nama_sekolah,logo FROM pengaturan LIMIT 1');if($r&&($x=$r->fetch_assoc()))$school=array_merge($school,$x);
$active=sds_perpus_setting_value($conn,'kiosk_kunjungan_aktif','1')==='1';$title=sds_perpus_setting_value($conn,'kiosk_judul','Kunjungan Perpustakaan');$message='';$error='';$profile=null;$already=false;
if(!$active){http_response_code(503);$error='Kiosk kunjungan sedang dinonaktifkan oleh petugas.';}
elseif(($_SERVER['REQUEST_METHOD']??'')==='POST'){
 try{
  if(!sds_csrf_verify($_POST['csrf']??null))throw new RuntimeException('Sesi kiosk tidak valid. Muat ulang halaman.');
  $identifier=trim((string)($_POST['identifier']??''));if($identifier==='')throw new RuntimeException('Tempelkan kartu RFID atau masukkan nomor anggota.');
  $identity=sds_perpus_resolve_identity($conn,$identifier);if(!$identity)throw new RuntimeException('Kartu atau nomor anggota tidak ditemukan.');$member=$identity['member'];
  if(($member['status_keanggotaan']??'')!=='aktif')throw new RuntimeException('Keanggotaan Perpustakaan tidak aktif.');
  $profile=$identity['profile']??sds_perpus_identity_profile($conn,(string)$identity['owner_type'],(int)$identity['owner_id'],$member);
  if(($identity['owner_type']??'')!=='legacy'&&empty($profile['aktif']))throw new RuntimeException('Pemilik kartu sedang nonaktif pada master SDS.');
  $memberId=(int)$member['id'];$reject=sds_perpus_setting_value($conn,'kiosk_tolak_ganda','1')==='1';
  if($reject){$stmt=$conn->prepare('SELECT id FROM perpus_kunjungan WHERE anggota_id=? AND DATE(waktu_kunjungan)=CURDATE() LIMIT 1');$stmt->bind_param('i',$memberId);$stmt->execute();$already=(bool)$stmt->get_result()->fetch_assoc();$stmt->close();}
  if(!$already){$stmt=$conn->prepare("INSERT INTO perpus_kunjungan (anggota_id,waktu_kunjungan,sumber) VALUES (?,NOW(),'kiosk')");$stmt->bind_param('i',$memberId);$stmt->execute();$stmt->close();$message='Kunjungan berhasil dicatat.';}else{$message='Kunjungan Anda hari ini sudah tercatat.';}
 }catch(Throwable $e){$error=$e->getMessage();}
}
$logo=!empty($school['logo'])?sds_base_url('uploads/logo/'.rawurlencode(basename((string)$school['logo']))):'';
?><!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=kh($title)?> · <?=kh($school['nama_sekolah'])?></title><link rel="stylesheet" href="kiosk.css?v=2.6.1"></head><body><main class="kiosk-shell"><section class="kiosk-brand"><?php if($logo):?><img src="<?=kh($logo)?>" alt="Logo"><?php else:?><div class="logo-fallback">📚</div><?php endif;?><div><small><?=kh($school['nama_sekolah'])?></small><h1><?=kh($title)?></h1><p>Tempelkan kartu RFID pada reader untuk mencatat kunjungan.</p></div></section><section class="kiosk-card"><?php if($profile):?><div class="kiosk-result <?=$error?'error':'success'?>"><div class="result-icon">✓</div><h2><?=kh($profile['nama'])?></h2><p><?=kh($profile['identitas'])?> · <?=kh($profile['unit'])?></p><strong><?=kh($message)?></strong></div><?php elseif($error):?><div class="kiosk-alert"><?=kh($error)?></div><?php endif;?><form method="post" autocomplete="off"><input type="hidden" name="csrf" value="<?=kh(sds_csrf_token())?>"><label>RFID / Nomor Anggota</label><input name="identifier" autofocus required inputmode="numeric" placeholder="Tempel kartu di sini"><button>Catat Kunjungan</button></form><div class="kiosk-clock" id="clock"></div></section></main><script>const input=document.querySelector('input[name=identifier]');setInterval(()=>{document.getElementById('clock').textContent=new Date().toLocaleString('id-ID',{dateStyle:'full',timeStyle:'medium'});if(document.activeElement!==input)input.focus()},1000);if(<?= $profile?'true':'false' ?>)setTimeout(()=>location.href=location.pathname,4500);</script></body></html>
