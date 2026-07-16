<?php
if (!defined('SDS_PERPUSTAKAAN_APP')) { http_response_code(403); exit('Akses langsung tidak diizinkan.'); }
$page = 'master';
require __DIR__ . '/../partials/bootstrap.php';
if (empty($perpusAccess['allowed'])) return;

$message = '';
$error = '';
$tabs = [
    'kategori' => ['Kategori Buku','folder'],
    'koleksi' => ['Tipe Koleksi','archive'],
    'pengarang' => ['Pengarang','edit-3'],
    'penerbit' => ['Penerbit','briefcase'],
    'subyek' => ['Subyek','tag'],
    'gmd' => ['GMD','file-text'],
    'bahasa' => ['Bahasa','globe'],
    'tempat' => ['Tempat Terbit','map-pin'],
    'anggota' => ['Tipe Anggota','users'],
];
$tab = strtolower((string)($_GET['tab'] ?? 'kategori'));
if (!isset($tabs[$tab])) $tab = 'kategori';

function perpus_master_dependency(mysqli $conn, string $entity, int $id): int
{
    $queries = [
        'kategori' => 'SELECT COUNT(*) total FROM perpus_buku WHERE kategori_id=?',
        'koleksi' => 'SELECT (SELECT COUNT(*) FROM perpus_buku WHERE tipe_koleksi_id=?)+(SELECT COUNT(*) FROM perpus_buku_eksemplar WHERE tipe_koleksi_id=?) total',
        'pengarang' => 'SELECT (SELECT COUNT(*) FROM perpus_buku WHERE pengarang_id=?)+(SELECT COUNT(*) FROM perpus_buku_pengarang WHERE pengarang_id=?) total',
        'penerbit' => 'SELECT COUNT(*) total FROM perpus_buku WHERE penerbit_id=?',
        'subyek' => 'SELECT COUNT(*) total FROM perpus_buku_subyek WHERE subyek_id=?',
        'gmd' => 'SELECT COUNT(*) total FROM perpus_buku WHERE gmd_id=?',
        'anggota' => 'SELECT COUNT(*) total FROM perpus_anggota WHERE tipe_member_id=?',
    ];
    if (!isset($queries[$entity])) return 0;
    $stmt = $conn->prepare($queries[$entity]);
    if (!$stmt) return 0;
    if (substr_count($queries[$entity], '?') === 2) $stmt->bind_param('ii', $id, $id); else $stmt->bind_param('i', $id);
    $stmt->execute();
    $total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
    return $total;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        perpus_check_csrf();
        if (!$perpusCanManage) throw new RuntimeException('Hanya admin Perpustakaan yang dapat mengubah data master.');
        $entity = strtolower((string)($_POST['entity'] ?? ''));
        if (!isset($tabs[$entity])) throw new RuntimeException('Jenis data master tidak valid.');
        $action = (string)($_POST['action'] ?? 'save');
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'delete') {
            if ($id <= 0) throw new RuntimeException('Data yang akan dihapus tidak valid.');
            $used = perpus_master_dependency($conn, $entity, $id);
            if ($used > 0) throw new RuntimeException('Data sudah dipakai oleh ' . $used . ' data lain sehingga tidak dapat dihapus. Edit namanya atau nonaktifkan bila tersedia.');
            $tables = [
                'kategori'=>'perpus_kategori_buku','koleksi'=>'perpus_tipe_koleksi','pengarang'=>'perpus_pengarang',
                'penerbit'=>'perpus_penerbit','subyek'=>'perpus_subyek','gmd'=>'perpus_gmd',
                'bahasa'=>'perpus_bahasa','tempat'=>'perpus_tempat','anggota'=>'perpus_tipe_member',
            ];
            $stmt = $conn->prepare('DELETE FROM ' . $tables[$entity] . ' WHERE id=?');
            $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
            $message = $tabs[$entity][0] . ' berhasil dihapus.';
        } else {
            $nama = trim((string)($_POST['nama'] ?? ''));
            if ($nama === '') throw new RuntimeException('Nama data wajib diisi.');
            switch ($entity) {
                case 'kategori':
                    $kode = trim((string)($_POST['kode'] ?? ''));
                    $kodeInt = $kode === '' ? null : (int)$kode;
                    $aktif = !empty($_POST['status_aktif']) ? 1 : 0;
                    if ($id > 0) { $stmt=$conn->prepare('UPDATE perpus_kategori_buku SET nama=?,kode_kategori=?,status_aktif=? WHERE id=?'); $stmt->bind_param('siii',$nama,$kodeInt,$aktif,$id); }
                    else { $stmt=$conn->prepare('INSERT INTO perpus_kategori_buku (nama,kode_kategori,status_aktif) VALUES (?,?,?)'); $stmt->bind_param('sii',$nama,$kodeInt,$aktif); }
                    break;
                case 'koleksi':
                    if ($id > 0) { $stmt=$conn->prepare('UPDATE perpus_tipe_koleksi SET nama=? WHERE id=?'); $stmt->bind_param('si',$nama,$id); }
                    else { $stmt=$conn->prepare('INSERT INTO perpus_tipe_koleksi (nama) VALUES (?)'); $stmt->bind_param('s',$nama); }
                    break;
                case 'pengarang':
                    $tipe = in_array((string)($_POST['tipe'] ?? 'p'), ['p','o','c'], true) ? (string)$_POST['tipe'] : 'p';
                    if ($id > 0) { $stmt=$conn->prepare('UPDATE perpus_pengarang SET nama=?,tipe=? WHERE id=?'); $stmt->bind_param('ssi',$nama,$tipe,$id); }
                    else { $stmt=$conn->prepare('INSERT INTO perpus_pengarang (nama,tipe) VALUES (?,?)'); $stmt->bind_param('ss',$nama,$tipe); }
                    break;
                case 'penerbit':
                    if ($id > 0) { $stmt=$conn->prepare('UPDATE perpus_penerbit SET nama=? WHERE id=?'); $stmt->bind_param('si',$nama,$id); }
                    else { $stmt=$conn->prepare('INSERT INTO perpus_penerbit (nama) VALUES (?)'); $stmt->bind_param('s',$nama); }
                    break;
                case 'subyek':
                    if ($id > 0) { $stmt=$conn->prepare('UPDATE perpus_subyek SET nama=? WHERE id=?'); $stmt->bind_param('si',$nama,$id); }
                    else { $stmt=$conn->prepare('INSERT INTO perpus_subyek (nama) VALUES (?)'); $stmt->bind_param('s',$nama); }
                    break;
                case 'gmd':
                    $kode = trim((string)($_POST['kode'] ?? ''));
                    if ($id > 0) { $stmt=$conn->prepare('UPDATE perpus_gmd SET nama=?,kode=? WHERE id=?'); $stmt->bind_param('ssi',$nama,$kode,$id); }
                    else { $stmt=$conn->prepare('INSERT INTO perpus_gmd (nama,kode) VALUES (?,?)'); $stmt->bind_param('ss',$nama,$kode); }
                    break;
                case 'bahasa':
                    $kode = trim((string)($_POST['kode'] ?? ''));
                    if ($id > 0) { $stmt=$conn->prepare('UPDATE perpus_bahasa SET nama=?,legacy_kode=? WHERE id=?'); $stmt->bind_param('ssi',$nama,$kode,$id); }
                    else { $stmt=$conn->prepare('INSERT INTO perpus_bahasa (nama,legacy_kode) VALUES (?,?)'); $stmt->bind_param('ss',$nama,$kode); }
                    break;
                case 'tempat':
                    if ($id > 0) { $stmt=$conn->prepare('UPDATE perpus_tempat SET nama=? WHERE id=?'); $stmt->bind_param('si',$nama,$id); }
                    else { $stmt=$conn->prepare('INSERT INTO perpus_tempat (nama) VALUES (?)'); $stmt->bind_param('s',$nama); }
                    break;
                case 'anggota':
                    $maksimal = max(1, min(100, (int)($_POST['jumlah_peminjaman'] ?? 2)));
                    $periode = max(1, min(365, (int)($_POST['periode_peminjaman'] ?? 7)));
                    $denda = max(0, (float)($_POST['denda_per_hari'] ?? 0));
                    $maksPerpanjang = max(0, min(10, (int)($_POST['maksimal_perpanjangan'] ?? 1)));
                    $hariPerpanjang = max(1, min(365, (int)($_POST['hari_perpanjangan'] ?? 7)));
                    $aktif = !empty($_POST['status_aktif']) ? 1 : 0;
                    if ($id > 0) { $stmt=$conn->prepare('UPDATE perpus_tipe_member SET nama=?,jumlah_peminjaman=?,periode_peminjaman=?,denda_per_hari=?,maksimal_perpanjangan=?,hari_perpanjangan=?,status_aktif=? WHERE id=?'); $stmt->bind_param('siidiiii',$nama,$maksimal,$periode,$denda,$maksPerpanjang,$hariPerpanjang,$aktif,$id); }
                    else { $stmt=$conn->prepare('INSERT INTO perpus_tipe_member (nama,jumlah_peminjaman,periode_peminjaman,denda_per_hari,maksimal_perpanjangan,hari_perpanjangan,status_aktif) VALUES (?,?,?,?,?,?,?)'); $stmt->bind_param('siidiii',$nama,$maksimal,$periode,$denda,$maksPerpanjang,$hariPerpanjang,$aktif); }
                    break;
            }
            if (!$stmt->execute()) throw new RuntimeException($stmt->error ?: 'Data master gagal disimpan.');
            $stmt->close();
            $message = $tabs[$entity][0] . ' berhasil disimpan.';
        }
        $tab = $entity;
    } catch (Throwable $e) {
        $error = stripos($e->getMessage(), 'duplicate') !== false ? 'Nama atau kode tersebut sudah digunakan.' : $e->getMessage();
    }
}

$tableMap = [
    'kategori' => 'SELECT id,nama,kode_kategori kode,status_aktif FROM perpus_kategori_buku ORDER BY nama',
    'koleksi' => 'SELECT id,nama FROM perpus_tipe_koleksi ORDER BY nama',
    'pengarang' => 'SELECT id,nama,tipe FROM perpus_pengarang ORDER BY nama',
    'penerbit' => 'SELECT id,nama FROM perpus_penerbit ORDER BY nama',
    'subyek' => 'SELECT id,nama FROM perpus_subyek ORDER BY nama',
    'gmd' => 'SELECT id,nama,kode FROM perpus_gmd ORDER BY nama',
    'bahasa' => 'SELECT id,nama,legacy_kode kode FROM perpus_bahasa ORDER BY nama',
    'tempat' => 'SELECT id,nama FROM perpus_tempat ORDER BY nama',
    'anggota' => 'SELECT id,nama,jumlah_peminjaman,periode_peminjaman,denda_per_hari,maksimal_perpanjangan,hari_perpanjangan,status_aktif FROM perpus_tipe_member ORDER BY nama',
];
$rows = [];
$result = $conn->query($tableMap[$tab]);
while ($result && ($row = $result->fetch_assoc())) { $row['dependency'] = perpus_master_dependency($conn,$tab,(int)$row['id']); $rows[] = $row; }
$counts = [];
foreach ($tableMap as $key => $sql) {
    $table = [
        'kategori'=>'perpus_kategori_buku','koleksi'=>'perpus_tipe_koleksi','pengarang'=>'perpus_pengarang','penerbit'=>'perpus_penerbit',
        'subyek'=>'perpus_subyek','gmd'=>'perpus_gmd','bahasa'=>'perpus_bahasa','tempat'=>'perpus_tempat','anggota'=>'perpus_tipe_member'
    ][$key];
    $q=$conn->query('SELECT COUNT(*) total FROM '.$table); $counts[$key]=$q?(int)($q->fetch_assoc()['total']??0):0;
}
require __DIR__ . '/../partials/master_page_style.php';
?>
<div class="sds-master-page perpus-master-page">
    <div class="sds-hero">
        <div><h2>Data Master Perpustakaan</h2><p>Kelola referensi bibliografi dan aturan keanggotaan yang digunakan oleh koleksi dan transaksi.</p></div>
        <div class="sds-hero-actions"><?php if($perpusCanManage):?><button class="btn btn-primary" type="button" onclick="openMasterModal()"><i data-feather="plus" class="me-1"></i>Tambah <?=perpus_h($tabs[$tab][0])?></button><?php endif;?></div>
    </div>
    <?php if($message):?><div class="alert alert-success"><?=perpus_h($message)?></div><?php endif;?>
    <?php if($error):?><div class="alert alert-danger"><?=perpus_h($error)?></div><?php endif;?>

    <div class="card card-outline card-primary">
        <div class="perpus-nav-tabs nav nav-tabs flex-nowrap overflow-auto">
            <?php foreach($tabs as $key=>[$label,$icon]):?><a class="nav-link <?=$tab===$key?'active':''?>" href="master?tab=<?=perpus_h($key)?>"><i data-feather="<?=perpus_h($icon)?>" class="me-1"></i><?=perpus_h($label)?> <span class="badge bg-light text-dark ms-1"><?=number_format($counts[$key],0,',','.')?></span></a><?php endforeach;?>
        </div>
        <div class="card-header"><h5><?=perpus_h($tabs[$tab][0])?></h5><div class="input-group input-group-sm" style="max-width:300px"><span class="input-group-text"><i data-feather="search"></i></span><input id="masterSearch" class="form-control" placeholder="Cari data..."></div></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="masterTable">
                <thead><tr><th style="width:60px">No</th><th>Nama</th><?php if(in_array($tab,['kategori','gmd','bahasa'],true)):?><th>Kode</th><?php endif;?><?php if($tab==='pengarang'):?><th>Tipe</th><?php endif;?><?php if($tab==='anggota'):?><th>Aturan Peminjaman</th><th>Perpanjangan</th><th>Denda/Hari</th><?php endif;?><th>Dipakai</th><?php if(in_array($tab,['kategori','anggota'],true)):?><th>Status</th><?php endif;?><th class="text-end">Aksi</th></tr></thead>
                <tbody>
                <?php if(!$rows):?><tr><td colspan="9" class="text-center text-muted py-4">Belum ada data.</td></tr><?php endif;?>
                <?php foreach($rows as $i=>$row):$payload=htmlspecialchars(json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),ENT_QUOTES,'UTF-8');?>
                    <tr data-search="<?=perpus_h(mb_strtolower(implode(' ',array_map('strval',$row))))?>">
                        <td><?=number_format($i+1,0,',','.')?></td><td><strong><?=perpus_h($row['nama'])?></strong></td>
                        <?php if(in_array($tab,['kategori','gmd','bahasa'],true)):?><td><code><?=perpus_h($row['kode']?:'-')?></code></td><?php endif;?>
                        <?php if($tab==='pengarang'):?><td><?=($row['tipe']??'p')==='o'?'Organisasi':(($row['tipe']??'p')==='c'?'Konferensi':'Personal')?></td><?php endif;?>
                        <?php if($tab==='anggota'):?><td><?=(int)$row['jumlah_peminjaman']?> item / <?=(int)$row['periode_peminjaman']?> hari</td><td><?=(int)$row['maksimal_perpanjangan']?>× / <?=(int)$row['hari_perpanjangan']?> hari</td><td><?=perpus_money($row['denda_per_hari'])?></td><?php endif;?>
                        <td><span class="badge bg-light text-dark"><?=number_format((int)$row['dependency'],0,',','.')?></span></td>
                        <?php if(in_array($tab,['kategori','anggota'],true)):?><td><?=!empty($row['status_aktif'])?'<span class="badge bg-success">Aktif</span>':'<span class="badge bg-secondary">Nonaktif</span>'?></td><?php endif;?>
                        <td class="text-end"><?php if($perpusCanManage):?><button class="btn btn-sm btn-outline-primary" type="button" data-row="<?=$payload?>" onclick="openMasterModal(this)"><i data-feather="edit-2"></i></button><?php endif;?></td>
                    </tr>
                <?php endforeach;?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="perpus-help"><strong>Catatan:</strong> Data master yang sudah dipakai tidak dapat dihapus untuk menjaga riwayat koleksi dan transaksi. Ubah nama atau statusnya bila tidak lagi digunakan.</div>
</div>

<div class="modal fade sds-master-modal" id="masterModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="post" id="masterForm">
<div class="modal-header"><h5 class="modal-title" id="masterModalTitle">Tambah Data</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><input type="hidden" name="csrf" value="<?=perpus_h(perpus_csrf())?>"><input type="hidden" name="action" value="save"><input type="hidden" name="entity" value="<?=perpus_h($tab)?>"><input type="hidden" name="id" id="masterId" value="0">
<div class="mb-3"><label class="form-label">Nama</label><input class="form-control" name="nama" id="masterName" required maxlength="150"></div>
<div class="mb-3 field-code" style="display:none"><label class="form-label">Kode</label><input class="form-control" name="kode" id="masterCode" maxlength="30"></div>
<div class="mb-3 field-author" style="display:none"><label class="form-label">Tipe Pengarang</label><select class="form-select" name="tipe" id="masterAuthorType"><option value="p">Personal</option><option value="o">Organisasi</option><option value="c">Konferensi</option></select></div>
<div class="field-member" style="display:none"><div class="row g-3"><div class="col-6"><label class="form-label">Maksimal Peminjaman</label><input type="number" min="1" max="100" class="form-control" name="jumlah_peminjaman" id="masterMaxLoan" value="2"></div><div class="col-6"><label class="form-label">Periode Pinjam (hari)</label><input type="number" min="1" max="365" class="form-control" name="periode_peminjaman" id="masterLoanDays" value="7"></div><div class="col-6"><label class="form-label">Maks. Perpanjangan</label><input type="number" min="0" max="10" class="form-control" name="maksimal_perpanjangan" id="masterMaxRenew" value="1"></div><div class="col-6"><label class="form-label">Tambahan Hari</label><input type="number" min="1" max="365" class="form-control" name="hari_perpanjangan" id="masterRenewDays" value="7"></div><div class="col-12"><label class="form-label">Denda per Hari</label><input type="number" min="0" step="100" class="form-control" name="denda_per_hari" id="masterFine" value="0"></div></div></div>
<div class="form-check mt-3 field-active" style="display:none"><input class="form-check-input" type="checkbox" name="status_aktif" id="masterActive" checked><label class="form-check-label" for="masterActive">Aktif</label></div>
</div>
<div class="modal-footer justify-content-between"><button class="btn btn-danger" type="submit" name="action" value="delete" id="masterDelete" style="display:none" onclick="return confirm('Hapus data master ini?')"><i data-feather="trash-2" class="me-1"></i>Hapus</button><div class="ms-auto"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button> <button class="btn btn-primary" type="submit"><i data-feather="save" class="me-1"></i>Simpan</button></div></div>
</form></div></div></div>
<script>
const masterEntity=<?=json_encode($tab)?>;
function openMasterModal(button){const row=button?JSON.parse(button.dataset.row||'{}'):{};document.getElementById('masterModalTitle').textContent=(row.id?'Edit ':'Tambah ')+<?=json_encode($tabs[$tab][0])?>;document.getElementById('masterId').value=row.id||0;document.getElementById('masterName').value=row.nama||'';document.getElementById('masterCode').value=row.kode||'';document.getElementById('masterAuthorType').value=row.tipe||'p';document.getElementById('masterMaxLoan').value=row.jumlah_peminjaman||2;document.getElementById('masterLoanDays').value=row.periode_peminjaman||7;document.getElementById('masterMaxRenew').value=row.maksimal_perpanjangan??1;document.getElementById('masterRenewDays').value=row.hari_perpanjangan||7;document.getElementById('masterFine').value=row.denda_per_hari||0;document.getElementById('masterActive').checked=!row.id||String(row.status_aktif)==='1';document.querySelector('.field-code').style.display=['kategori','gmd','bahasa'].includes(masterEntity)?'block':'none';document.querySelector('.field-author').style.display=masterEntity==='pengarang'?'block':'none';document.querySelector('.field-member').style.display=masterEntity==='anggota'?'block':'none';document.querySelector('.field-active').style.display=['kategori','anggota'].includes(masterEntity)?'block':'none';document.getElementById('masterDelete').style.display=row.id&&Number(row.dependency||0)===0?'inline-flex':'none';perpusModal('masterModal')?.show();setTimeout(()=>document.getElementById('masterName').focus(),180)}
document.getElementById('masterSearch')?.addEventListener('input',function(){const q=this.value.toLowerCase().trim();document.querySelectorAll('#masterTable tbody tr[data-search]').forEach(row=>row.style.display=!q||row.dataset.search.includes(q)?'':'none')});
</script>
