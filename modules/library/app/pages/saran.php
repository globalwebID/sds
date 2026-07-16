<?php
if (!defined('SDS_PERPUSTAKAAN_APP')) { http_response_code(403); exit('Akses langsung tidak diizinkan.'); }
$page='saran';
require __DIR__.'/../partials/bootstrap.php';
if (empty($perpusAccess['allowed'])) return;
$message='';$error='';

if (($_SERVER['REQUEST_METHOD']??'')==='POST') {
    try {
        perpus_check_csrf();
        $action=(string)($_POST['action']??'update');
        $id=(int)($_POST['id']??0);
        if($id<=0) throw new RuntimeException('Data kritik/saran tidak valid.');
        $stmt=$conn->prepare('SELECT * FROM perpus_saran WHERE id=? LIMIT 1');$stmt->bind_param('i',$id);$stmt->execute();$before=$stmt->get_result()->fetch_assoc();$stmt->close();
        if(!$before) throw new RuntimeException('Data kritik/saran tidak ditemukan.');
        if($action==='delete'){
            if(!$perpusCanManage) throw new RuntimeException('Hanya admin Perpustakaan yang dapat menghapus data.');
            $stmt=$conn->prepare('DELETE FROM perpus_saran WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();
            sds_perpus_audit_log($conn,'delete','saran',$id,'Menghapus kritik/saran: '.($before['judul']??''),$before,null);
            $message='Kritik/saran berhasil dihapus.';
        }else{
            $status=(string)($_POST['status']??'baru');
            if(!in_array($status,['baru','diproses','selesai','ditolak'],true)) throw new RuntimeException('Status tidak valid.');
            $answer=trim((string)($_POST['jawaban']??''));
            if(mb_strlen($answer)>5000) throw new RuntimeException('Jawaban terlalu panjang.');
            $adminId=(int)($_SESSION['admin_id']??0);
            $stmt=$conn->prepare("UPDATE perpus_saran SET status=?,jawaban=NULLIF(?,''),admin_id=?,ditanggapi_at=CASE WHEN ?<>'' THEN NOW() ELSE ditanggapi_at END WHERE id=?");
            $stmt->bind_param('ssisi',$status,$answer,$adminId,$answer,$id);$stmt->execute();$stmt->close();
            if($answer!=='' && !empty($before['anggota_id'])){
                sds_perpus_notify_member($conn,(int)$before['anggota_id'],'tanggapan_saran','Tanggapan Perpustakaan','Petugas menanggapi “'.($before['judul']??'Kritik/Saran').'”: '.$answer,'saran',$id);
            }
            sds_perpus_audit_log($conn,'update','saran',$id,'Menanggapi kritik/saran: '.($before['judul']??''),['status'=>$before['status'],'jawaban'=>$before['jawaban']],['status'=>$status,'jawaban'=>$answer]);
            $message='Status dan tanggapan berhasil disimpan.';
        }
    } catch(Throwable $e){$error=$e->getMessage();}
}

$status=trim((string)($_GET['status']??''));if(!in_array($status,['','baru','diproses','selesai','ditolak'],true))$status='';
$category=trim((string)($_GET['kategori']??''));if(!in_array($category,['','kritik','saran','keluhan','apresiasi'],true))$category='';
$q=trim((string)($_GET['q']??''));
$where=['1=1'];$params=[];$types='';
if($status!==''){$where[]='s.status=?';$params[]=$status;$types.='s';}
if($category!==''){$where[]='s.kategori=?';$params[]=$category;$types.='s';}
if($q!==''){$where[]='(s.judul LIKE ? OR s.pesan LIKE ? OR s.nama_pengirim LIKE ? OR s.kontak LIKE ? OR a.nomor_anggota LIKE ?)';$like='%'.$q.'%';for($i=0;$i<5;$i++)$params[]=$like;$types.='sssss';}
$sql="SELECT s.*,a.nomor_anggota,a.pemilik_tipe,a.pemilik_id,a.legacy_nama,a.legacy_nis,a.legacy_kelas,a.legacy_jurusan FROM perpus_saran s LEFT JOIN perpus_anggota a ON a.id=s.anggota_id WHERE ".implode(' AND ',$where)." ORDER BY FIELD(s.status,'baru','diproses','selesai','ditolak'),s.created_at DESC LIMIT 500";
$stmt=$conn->prepare($sql);if($types!==''){$refs=[$types];foreach($params as $k=>&$v)$refs[]=&$v;call_user_func_array([$stmt,'bind_param'],$refs);} $stmt->execute();$result=$stmt->get_result();$rows=[];while($r=$result->fetch_assoc()){if(!empty($r['anggota_id']))$r['profile']=sds_perpus_identity_profile($conn,(string)$r['pemilik_tipe'],(int)($r['pemilik_id']??0),$r);else $r['profile']=['nama'=>$r['nama_pengirim']?:'Anonim','identitas'=>$r['kontak']?:'-','unit'=>'Publik','detail'=>'OPAC'];$rows[]=$r;}$stmt->close();unset($v);
$stats=['baru'=>0,'diproses'=>0,'selesai'=>0,'total'=>0];$rs=$conn->query("SELECT status,COUNT(*) total FROM perpus_saran GROUP BY status");while($rs&&($x=$rs->fetch_assoc())){$stats[$x['status']]=(int)$x['total'];$stats['total']+=(int)$x['total'];}
require __DIR__.'/../partials/master_page_style.php';
?>
<div class="sds-master-page perpus-feedback">
<div class="sds-hero"><div><h2>Kritik & Saran</h2><p>Masukan anggota dan pengunjung OPAC untuk peningkatan layanan Perpustakaan.</p></div><div class="sds-hero-actions"><a class="btn btn-outline-primary" target="_blank" href="<?=perpus_h(sds_base_url('perpustakaan/opac/saran.php'))?>"><i data-feather="external-link" class="me-1"></i>Buka Form Publik</a></div></div>
<?php if($message):?><div class="alert alert-success"><?=perpus_h($message)?></div><?php endif;?><?php if($error):?><div class="alert alert-danger"><?=perpus_h($error)?></div><?php endif;?>
<div class="sds-stats"><div class="sds-stat-card"><small>Total Masukan</small><strong><?=number_format($stats['total'],0,',','.')?></strong><span>Seluruh kritik dan saran</span></div><div class="sds-stat-card"><small>Baru</small><strong><?=number_format($stats['baru'],0,',','.')?></strong><span>Belum ditindaklanjuti</span></div><div class="sds-stat-card"><small>Diproses</small><strong><?=number_format($stats['diproses'],0,',','.')?></strong><span>Sedang ditangani</span></div><div class="sds-stat-card"><small>Selesai</small><strong><?=number_format($stats['selesai'],0,',','.')?></strong><span>Sudah ditanggapi</span></div></div>
<div class="card no-print"><div class="card-body"><form class="filter-grid" method="get"><div><label class="form-label">Pencarian</label><input class="form-control form-control-sm" name="q" value="<?=perpus_h($q)?>" placeholder="Judul, pesan, nama, kontak..."></div><div><label class="form-label">Kategori</label><select class="form-select form-select-sm" name="kategori"><option value="">Semua kategori</option><?php foreach(['kritik'=>'Kritik','saran'=>'Saran','keluhan'=>'Keluhan','apresiasi'=>'Apresiasi'] as $v=>$l):?><option value="<?=$v?>" <?=$category===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></div><div><label class="form-label">Status</label><select class="form-select form-select-sm" name="status"><option value="">Semua status</option><?php foreach(['baru'=>'Baru','diproses'=>'Diproses','selesai'=>'Selesai','ditolak'=>'Ditolak'] as $v=>$l):?><option value="<?=$v?>" <?=$status===$v?'selected':''?>><?=$l?></option><?php endforeach;?></select></div><button class="btn btn-sm btn-primary">Tampilkan</button><a class="btn btn-sm btn-outline-secondary" href="saran">Reset</a></form></div></div>
<div class="card card-outline card-primary"><div class="card-header"><h5>Daftar Masukan</h5><span class="small text-muted"><?=number_format(count($rows),0,',','.')?> data ditampilkan</span></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Pengirim</th><th>Kategori</th><th>Judul & Pesan</th><th>Status</th><th>Waktu</th><th class="text-end">Aksi</th></tr></thead><tbody><?php if(!$rows):?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada masukan.</td></tr><?php endif;?><?php foreach($rows as $r):$p=$r['profile'];$payload=htmlspecialchars(json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),ENT_QUOTES,'UTF-8');?><tr><td><strong><?=perpus_h($p['nama'])?></strong><div class="meta"><?=perpus_h($r['nomor_anggota']?:$p['identitas'])?> · <?=perpus_h($p['unit'])?></div><?php if($r['kontak']):?><div class="meta"><?=perpus_h($r['kontak'])?></div><?php endif;?></td><td><span class="badge category-badge <?=$r['kategori']==='keluhan'?'bg-danger':($r['kategori']==='apresiasi'?'bg-success':($r['kategori']==='kritik'?'bg-warning text-dark':'bg-info'))?>"><?=perpus_h($r['kategori'])?></span></td><td class="feedback-message"><strong><?=perpus_h($r['judul'])?></strong><div class="mt-1"><?=nl2br(perpus_h(mb_strimwidth((string)$r['pesan'],0,280,'…')))?></div><?php if($r['jawaban']):?><div class="perpus-help mt-2"><strong>Tanggapan:</strong> <?=nl2br(perpus_h($r['jawaban']))?></div><?php endif;?></td><td><span class="badge <?=$r['status']==='baru'?'bg-danger':($r['status']==='diproses'?'bg-warning text-dark':($r['status']==='selesai'?'bg-success':'bg-secondary'))?>"><?=perpus_h(ucfirst($r['status']))?></span></td><td><?=date('d/m/Y H:i',strtotime($r['created_at']))?><div class="meta"><?=perpus_h(ucfirst($r['sumber']))?></div></td><td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-row="<?=$payload?>" onclick="openFeedbackModal(this)"><i data-feather="edit-2"></i> Tanggapi</button></td></tr><?php endforeach;?></tbody></table></div></div>
</div>
<div class="modal fade" id="feedbackModal" tabindex="-1"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content"><form method="post"><div class="modal-header"><div><h5 class="modal-title">Tanggapi Kritik & Saran</h5><div class="small text-muted" id="feedbackSender"></div></div><button class="btn-close" type="button" data-bs-dismiss="modal"></button></div><div class="modal-body"><input type="hidden" name="csrf" value="<?=perpus_h(perpus_csrf())?>"><input type="hidden" name="action" value="update" id="feedbackAction"><input type="hidden" name="id" id="feedbackId"><div class="perpus-help mb-3"><strong id="feedbackTitle"></strong><div class="mt-2" id="feedbackMessage"></div></div><div class="row g-3"><div class="col-md-4"><label class="form-label">Status</label><select class="form-select" name="status" id="feedbackStatus"><option value="baru">Baru</option><option value="diproses">Diproses</option><option value="selesai">Selesai</option><option value="ditolak">Ditolak</option></select></div><div class="col-md-8"><label class="form-label">Tanggapan</label><textarea class="form-control" name="jawaban" id="feedbackAnswer" rows="5" placeholder="Tulis jawaban atau tindak lanjut..."></textarea></div></div></div><div class="modal-footer justify-content-between"><button class="btn btn-outline-danger" type="submit" id="feedbackDelete" onclick="return deleteFeedback()"><i data-feather="trash-2" class="me-1"></i>Hapus</button><div><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary"><i data-feather="save" class="me-1"></i>Simpan Tanggapan</button></div></div></form></div></div></div>
<script>
function openFeedbackModal(btn){const r=JSON.parse(btn.dataset.row||'{}');document.getElementById('feedbackId').value=r.id||0;document.getElementById('feedbackAction').value='update';document.getElementById('feedbackSender').textContent=((r.profile||{}).nama||r.nama_pengirim||'Anonim')+' · '+new Date((r.created_at||'').replace(' ','T')).toLocaleString('id-ID');document.getElementById('feedbackTitle').textContent=(r.kategori||'Saran').toUpperCase()+' · '+(r.judul||'');document.getElementById('feedbackMessage').textContent=r.pesan||'';document.getElementById('feedbackStatus').value=r.status||'baru';document.getElementById('feedbackAnswer').value=r.jawaban||'';document.getElementById('feedbackDelete').style.display=<?=json_encode($perpusCanManage)?>?'inline-block':'none';perpusModal('feedbackModal')?.show()}
function deleteFeedback(){if(!confirm('Hapus kritik/saran ini secara permanen?'))return false;document.getElementById('feedbackAction').value='delete';return true}
</script>
