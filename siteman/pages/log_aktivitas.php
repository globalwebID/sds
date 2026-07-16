<?php
declare(strict_types=1);

$h = static fn($value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$search = trim((string)($_GET['q'] ?? ''));
$action = trim((string)($_GET['aksi'] ?? ''));
$date = trim((string)($_GET['tanggal'] ?? ''));
$pageNumber = max(1, (int)($_GET['halaman'] ?? 1));
$perPage = 25;

$conditions = [];
if ($search !== '') {
    $needle = $conn->real_escape_string($search);
    $conditions[] = "(l.aksi LIKE '%{$needle}%' OR l.keterangan LIKE '%{$needle}%' OR COALESCE(a.full_name,a.username,'') LIKE '%{$needle}%' OR l.ip_address LIKE '%{$needle}%')";
}
if ($action !== '') {
    $safeAction = $conn->real_escape_string($action);
    $conditions[] = "l.aksi='{$safeAction}'";
}
if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $conditions[] = "DATE(l.waktu)='{$date}'";
} else {
    $date = '';
}
$where = $conditions ? 'WHERE '.implode(' AND ', $conditions) : '';

$countResult = $conn->query("SELECT COUNT(*) total FROM log_aktivitas l LEFT JOIN admins a ON l.admin_id=a.id {$where}");
$totalRows = (int)($countResult?->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int)ceil($totalRows / $perPage));
$pageNumber = min($pageNumber, $totalPages);
$offset = ($pageNumber - 1) * $perPage;

$result = $conn->query("SELECT l.*,COALESCE(a.full_name,a.username,'Admin tidak tersedia') admin_nama FROM log_aktivitas l LEFT JOIN admins a ON l.admin_id=a.id {$where} ORDER BY l.waktu DESC,l.id DESC LIMIT {$perPage} OFFSET {$offset}");
$actions = [];
$actionResult = $conn->query("SELECT DISTINCT aksi FROM log_aktivitas WHERE aksi IS NOT NULL AND aksi<>'' ORDER BY aksi");
while ($actionResult && ($row = $actionResult->fetch_assoc())) $actions[] = (string)$row['aksi'];

$today = (int)($conn->query("SELECT COUNT(*) total FROM log_aktivitas WHERE DATE(waktu)=CURDATE()")?->fetch_assoc()['total'] ?? 0);
$adminsActive = (int)($conn->query("SELECT COUNT(DISTINCT admin_id) total FROM log_aktivitas WHERE waktu>=DATE_SUB(NOW(),INTERVAL 30 DAY)")?->fetch_assoc()['total'] ?? 0);
$lastActivity = $conn->query("SELECT waktu FROM log_aktivitas ORDER BY waktu DESC LIMIT 1")?->fetch_assoc()['waktu'] ?? null;

$queryForPage = static function (int $page) use ($search, $action, $date): string {
    return http_build_query(array_filter(['q'=>$search,'aksi'=>$action,'tanggal'=>$date,'halaman'=>$page], static fn($v) => $v !== ''));
};
?>
<?php include __DIR__.'/partials/shared/master_page_style.php'; ?>
<style>
.sds-log-page .sds-log-filter{display:grid;grid-template-columns:minmax(240px,1fr) 210px 175px auto;gap:.5rem;width:100%}.sds-log-page .sds-log-filter .form-control,.sds-log-page .sds-log-filter .form-select{min-height:34px}.sds-log-page .sds-log-detail{max-width:430px;white-space:normal;word-break:break-word}.sds-log-page .sds-log-meta{font-size:.76rem;color:#6c757d;white-space:nowrap}.sds-log-page .sds-log-foot{padding:.75rem 1rem;border-top:1px solid #dee2e6;display:flex;align-items:center;justify-content:space-between;gap:1rem}.sds-log-page .pagination{margin:0}
@media(max-width:991.98px){.sds-log-page .sds-log-filter{grid-template-columns:1fr 1fr}.sds-log-page .sds-log-foot{align-items:flex-start;flex-direction:column}}
@media(max-width:575.98px){.sds-log-page .sds-log-filter{grid-template-columns:1fr}.sds-log-page .sds-log-filter .btn{width:100%}}
</style>

<div class="sds-master-page sds-log-page">
 <div class="sds-hero"><div><h2>Log Aktivitas</h2><p>Jejak aktivitas administrator untuk pemantauan dan audit sistem.</p></div><div class="sds-hero-actions"><a href="log_update" class="btn btn-outline-primary btn-sm"><i data-feather="download-cloud" class="me-1"></i> Riwayat Pembaruan</a></div></div>
 <div class="sds-stats three">
  <div class="sds-stat-card"><small>Aktivitas Hari Ini</small><strong><?=number_format($today,0,',','.')?></strong><span>Catatan pada <?=date('d/m/Y')?></span></div>
  <div class="sds-stat-card"><small>Admin Aktif</small><strong><?=number_format($adminsActive,0,',','.')?></strong><span>Beraktivitas dalam 30 hari</span></div>
  <div class="sds-stat-card"><small>Aktivitas Terakhir</small><strong style="font-size:1.05rem"><?=$lastActivity ? $h(date('d/m/Y H:i',strtotime((string)$lastActivity))) : '-'?></strong><span>Waktu server SDS</span></div>
 </div>

 <div class="sds-card">
  <div class="sds-card-header"><h5>Riwayat Aktivitas Administrator</h5><span class="sds-mini"><?=number_format($totalRows,0,',','.')?> catatan ditemukan</span></div>
  <?php if (!empty($_SESSION['error'])):?><div class="alert alert-danger"><?=$h($_SESSION['error'])?></div><?php unset($_SESSION['error']);endif;?>
  <?php if (!empty($_SESSION['success'])):?><div class="alert alert-success"><?=$h($_SESSION['success'])?></div><?php unset($_SESSION['success']);endif;?>
  <div class="sds-card-body">
   <div class="sds-toolbar"><form class="sds-log-filter" method="get" action="log_aktivitas">
    <input class="form-control form-control-sm" name="q" value="<?=$h($search)?>" placeholder="Cari admin, aktivitas, keterangan, atau IP">
    <select class="form-select form-select-sm" name="aksi"><option value="">Semua aktivitas</option><?php foreach($actions as $item):?><option value="<?=$h($item)?>" <?=$action===$item?'selected':''?>><?=$h($item)?></option><?php endforeach;?></select>
    <input type="date" class="form-control form-control-sm" name="tanggal" value="<?=$h($date)?>">
    <div class="d-flex gap-1"><button class="btn btn-primary btn-sm" type="submit">Terapkan</button><a class="btn btn-light btn-sm" href="log_aktivitas">Reset</a></div>
   </form></div>
   <div class="sds-table-wrap">
   <?php if (!$result || $result->num_rows===0):?><div class="sds-empty"><i data-feather="inbox"></i><div>Belum ada aktivitas yang sesuai dengan filter.</div></div>
   <?php else:?><table class="sds-table wide"><thead><tr><th>Waktu</th><th>Administrator</th><th>Aktivitas</th><th>Keterangan</th><th>Perangkat</th></tr></thead><tbody>
   <?php while($log=$result->fetch_assoc()):?><tr><td class="sds-log-meta"><?=$h(date('d/m/Y H:i',strtotime((string)$log['waktu'])))?></td><td><strong><?=$h($log['admin_nama'])?></strong></td><td><span class="sds-badge info"><?=$h($log['aksi']?:'Aktivitas')?></span></td><td class="sds-log-detail"><?=$h($log['keterangan'])?></td><td class="sds-log-meta"><i data-feather="globe" style="width:13px"></i> <?=$h($log['ip_address']?:'-')?></td></tr><?php endwhile;?>
   </tbody></table><?php endif;?>
   </div>
  </div>
  <div class="sds-log-foot"><small class="text-muted">Halaman <?=$pageNumber?> dari <?=$totalPages?></small><?php if($totalPages>1):?><nav><ul class="pagination pagination-sm"><?php if($pageNumber>1):?><li class="page-item"><a class="page-link" href="log_aktivitas?<?=$h($queryForPage($pageNumber-1))?>">Sebelumnya</a></li><?php endif;?><?php if($pageNumber<$totalPages):?><li class="page-item"><a class="page-link" href="log_aktivitas?<?=$h($queryForPage($pageNumber+1))?>">Berikutnya</a></li><?php endif;?></ul></nav><?php endif;?></div>
 </div>
</div>
