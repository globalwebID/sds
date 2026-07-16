<?php
if (!defined('SDS_PERPUSTAKAAN_APP')) { http_response_code(403); exit('Akses langsung tidak diizinkan.'); }
$page = 'dashboard';
require __DIR__ . '/../partials/bootstrap.php';
if (empty($perpusAccess['allowed'])) return;

$stats = [
    'judul' => 0,
    'eksemplar' => 0,
    'tersedia' => 0,
    'anggota' => 0,
    'pinjaman_aktif' => 0,
    'terlambat' => 0,
    'kunjungan_hari_ini' => 0,
    'perlu_verifikasi' => 0,
];
$queries = [
    'judul' => 'SELECT COUNT(*) total FROM perpus_buku',
    'eksemplar' => 'SELECT COUNT(*) total FROM perpus_buku_eksemplar WHERE status<>\'nonaktif\'',
    'tersedia' => "SELECT COUNT(*) total FROM perpus_buku_eksemplar WHERE status='tersedia'",
    'anggota' => "SELECT COUNT(*) total FROM perpus_anggota WHERE status_keanggotaan='aktif'",
    'pinjaman_aktif' => "SELECT COUNT(*) total FROM perpus_peminjaman_detail WHERE status='dipinjam'",
    'terlambat' => "SELECT COUNT(*) total FROM perpus_peminjaman_detail WHERE status='dipinjam' AND tanggal_jatuh_tempo<CURDATE()",
    'kunjungan_hari_ini' => 'SELECT COUNT(*) total FROM perpus_kunjungan WHERE DATE(waktu_kunjungan)=CURDATE()',
    'perlu_verifikasi' => "SELECT COUNT(*) total FROM perpus_anggota WHERE status_keanggotaan='perlu_verifikasi' OR pemilik_tipe='legacy'",
    'reservasi_aktif' => "SELECT COUNT(*) total FROM perpus_reservasi WHERE status IN ('menunggu','siap')",
    'notifikasi_baru' => "SELECT COUNT(*) total FROM perpus_notifikasi WHERE status='baru'",
];
foreach ($queries as $key => $sql) {
    $result = $conn->query($sql);
    if ($result) $stats[$key] = (int)($result->fetch_assoc()['total'] ?? 0);
}

$recentLoans = [];
$result = $conn->query("SELECT pd.id,pd.kode_resi,pd.tanggal_jatuh_tempo,pd.status,pd.denda,p.tanggal_pinjam,
    a.id anggota_id,a.pemilik_tipe,a.pemilik_id,a.legacy_nama,a.legacy_nis,a.legacy_kelas,a.legacy_jurusan,
    b.judul,e.barcode
    FROM perpus_peminjaman_detail pd
    JOIN perpus_peminjaman p ON p.id=pd.peminjaman_id
    JOIN perpus_anggota a ON a.id=p.anggota_id
    LEFT JOIN perpus_buku b ON b.id=pd.buku_id
    LEFT JOIN perpus_buku_eksemplar e ON e.id=pd.eksemplar_id
    ORDER BY pd.id DESC LIMIT 8");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['profile'] = sds_perpus_identity_profile($conn, (string)$row['pemilik_tipe'], (int)$row['pemilik_id'], $row);
        $recentLoans[] = $row;
    }
}

$recentVisits = [];
$result = $conn->query("SELECT k.waktu_kunjungan,a.pemilik_tipe,a.pemilik_id,a.legacy_nama,a.legacy_nis,a.legacy_kelas,a.legacy_jurusan
    FROM perpus_kunjungan k JOIN perpus_anggota a ON a.id=k.anggota_id
    ORDER BY k.waktu_kunjungan DESC LIMIT 6");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $row['profile'] = sds_perpus_identity_profile($conn, (string)$row['pemilik_tipe'], (int)$row['pemilik_id'], $row);
        $recentVisits[] = $row;
    }
}

$monthly = [];
for ($i=5; $i>=0; $i--) {
    $key = date('Y-m', strtotime('-'.$i.' month'));
    $monthly[$key] = ['label'=>date('M y', strtotime($key.'-01')), 'loan'=>0, 'visit'=>0];
}
$result = $conn->query("SELECT DATE_FORMAT(tanggal_pinjam,'%Y-%m') ym,COUNT(*) total FROM perpus_peminjaman WHERE tanggal_pinjam>=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m-01') GROUP BY ym");
while($result&&($row=$result->fetch_assoc())) if(isset($monthly[$row['ym']])) $monthly[$row['ym']]['loan']=(int)$row['total'];
$result = $conn->query("SELECT DATE_FORMAT(waktu_kunjungan,'%Y-%m') ym,COUNT(*) total FROM perpus_kunjungan WHERE waktu_kunjungan>=DATE_FORMAT(DATE_SUB(CURDATE(),INTERVAL 5 MONTH),'%Y-%m-01') GROUP BY ym");
while($result&&($row=$result->fetch_assoc())) if(isset($monthly[$row['ym']])) $monthly[$row['ym']]['visit']=(int)$row['total'];
$maxLoan=max(1,...array_column($monthly,'loan'));$maxVisit=max(1,...array_column($monthly,'visit'));
$popularBooks=[];
$result=$conn->query("SELECT b.judul,COUNT(pd.id) total FROM perpus_peminjaman_detail pd JOIN perpus_buku b ON b.id=pd.buku_id GROUP BY b.id,b.judul ORDER BY total DESC,b.judul LIMIT 5");
while($result&&($row=$result->fetch_assoc()))$popularBooks[]=$row;

require __DIR__ . '/../partials/master_page_style.php';
?>
<div class="sds-master-page perpus-dashboard">
    <div class="sds-hero"><div><h2>Dashboard Perpustakaan</h2><p>Ringkasan koleksi, transaksi, anggota, dan aktivitas kunjungan hari ini.</p></div><div class="sds-hero-actions"><a href="reservasi" class="btn btn-outline-info"><i data-feather="bookmark" class="me-1"></i>Reservasi</a><a href="sirkulasi" class="btn btn-primary"><i data-feather="repeat" class="me-1"></i>Sirkulasi Cepat</a><a href="kunjungan" class="btn btn-success"><i data-feather="log-in" class="me-1"></i>Scan Kunjungan</a></div></div>
    <div class="perpus-smallbox-grid">
        <div class="perpus-small-box bg-info-box"><div class="inner"><h3><?=number_format($stats['judul'],0,',','.')?></h3><p>Judul Buku<br><small><?=number_format($stats['eksemplar'],0,',','.')?> eksemplar</small></p></div><div class="icon"><i data-feather="book-open"></i></div><a class="small-box-footer" href="buku">Kelola koleksi <span>→</span></a></div>
        <div class="perpus-small-box bg-success-box"><div class="inner"><h3><?=number_format($stats['tersedia'],0,',','.')?></h3><p>Eksemplar Tersedia<br><small>siap dipinjam</small></p></div><div class="icon"><i data-feather="check-circle"></i></div><a class="small-box-footer" href="katalog">Lihat katalog <span>→</span></a></div>
        <div class="perpus-small-box bg-warning-box"><div class="inner"><h3><?=number_format($stats['anggota'],0,',','.')?></h3><p>Anggota Aktif<br><small>siswa dan pegawai</small></p></div><div class="icon"><i data-feather="users"></i></div><a class="small-box-footer" href="anggota">Kelola anggota <span>→</span></a></div>
        <div class="perpus-small-box bg-danger-box"><div class="inner"><h3><?=number_format($stats['terlambat'],0,',','.')?></h3><p>Pinjaman Terlambat<br><small>dari <?=number_format($stats['pinjaman_aktif'],0,',','.')?> aktif</small></p></div><div class="icon"><i data-feather="alert-triangle"></i></div><a class="small-box-footer" href="sirkulasi">Proses transaksi <span>→</span></a></div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-lg-4"><div class="card card-outline card-primary h-100"><div class="card-body d-flex align-items-center gap-3"><span class="perpus-avatar-top" style="width:48px;height:48px"><i data-feather="log-in"></i></span><div><div class="text-muted small">Kunjungan Hari Ini</div><strong class="fs-3"><?=number_format($stats['kunjungan_hari_ini'],0,',','.')?></strong></div></div></div></div>
        <div class="col-lg-4"><div class="card card-outline card-warning h-100"><div class="card-body d-flex align-items-center gap-3"><span class="perpus-avatar-top bg-warning text-dark" style="width:48px;height:48px"><i data-feather="user-check"></i></span><div><div class="text-muted small">Perlu Verifikasi</div><strong class="fs-3"><?=number_format($stats['perlu_verifikasi'],0,',','.')?></strong><div class="small text-muted">Anggota lama belum dipasangkan</div></div></div></div></div>
        <div class="col-lg-4"><div class="card card-outline card-success h-100"><div class="card-body d-flex align-items-center gap-3"><span class="perpus-avatar-top bg-success" style="width:48px;height:48px"><i data-feather="bookmark"></i></span><div><div class="text-muted small">Reservasi Aktif</div><strong class="fs-3"><?=number_format($stats['reservasi_aktif'],0,',','.')?></strong><div class="small text-muted"><?=number_format($stats['notifikasi_baru'],0,',','.')?> notifikasi belum dibaca</div></div></div></div></div>
    </div>
    <div class="row g-3 mb-3">
        <div class="col-xl-7"><div class="card card-outline card-primary h-100"><div class="card-header"><h5>Aktivitas 6 Bulan Terakhir</h5><div class="metric-legend"><span><i style="background:#2563eb"></i>Peminjaman</span><span><i style="background:#16a085"></i>Kunjungan</span></div></div><div class="card-body"><div class="metric-chart"><?php foreach($monthly as $m):?><div class="metric-col"><div class="metric-bar-wrap"><span class="metric-bar loan" style="height:<?=max(3,round($m['loan']/$maxLoan*100))?>%" title="<?=$m['loan']?> peminjaman"></span><span class="metric-bar visit" style="height:<?=max(3,round($m['visit']/$maxVisit*100))?>%" title="<?=$m['visit']?> kunjungan"></span></div><small><?=perpus_h($m['label'])?></small></div><?php endforeach;?></div></div></div></div>
        <div class="col-xl-5"><div class="card card-outline card-info h-100"><div class="card-header"><h5>Koleksi Paling Sering Dipinjam</h5></div><div class="card-body"><?php if(!$popularBooks):?><div class="text-center text-muted py-4">Belum ada data peminjaman.</div><?php else:foreach($popularBooks as $i=>$book):?><div class="d-flex align-items-center gap-2 py-2 border-bottom"><span class="popular-rank"><?=$i+1?></span><div class="flex-grow-1 text-truncate"><strong><?=perpus_h($book['judul'])?></strong></div><span class="badge bg-light text-dark border"><?=number_format((int)$book['total'],0,',','.')?></span></div><?php endforeach;endif;?></div></div></div>
    </div>
    <div class="perpus-two-col">
        <div class="card card-outline card-primary"><div class="card-header"><h5>Transaksi Terbaru</h5><a href="sirkulasi" class="btn btn-sm btn-outline-primary">Lihat Sirkulasi</a></div><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>Anggota</th><th>Buku</th><th>Jatuh Tempo</th><th>Status</th></tr></thead><tbody><?php if(!$recentLoans):?><tr><td colspan="4" class="text-center text-muted py-4">Belum ada transaksi.</td></tr><?php else:foreach($recentLoans as $row):$p=$row['profile'];?><tr><td><div class="perpus-person"><span class="perpus-avatar"><?=perpus_h(mb_substr($p['nama'],0,1))?></span><div><strong><?=perpus_h($p['nama'])?></strong><div class="text-muted small"><?=perpus_h($p['identitas'])?> · <?=perpus_h($p['unit'])?></div></div></div></td><td><strong><?=perpus_h($row['judul']?:'Buku lama')?></strong><div class="small text-muted"><?=perpus_h($row['barcode']?:($row['kode_resi']??'-'))?></div></td><td><?=$row['tanggal_jatuh_tempo']?date('d/m/Y',strtotime($row['tanggal_jatuh_tempo'])):'-'?></td><td><span class="badge <?=$row['status']==='dipinjam'?((string)$row['tanggal_jatuh_tempo']<date('Y-m-d')?'bg-danger':'bg-warning text-dark'):'bg-success'?>"><?=perpus_h(ucfirst($row['status']))?></span></td></tr><?php endforeach;endif;?></tbody></table></div></div>
        <div class="card card-outline card-success"><div class="card-header"><h5>Kunjungan Terbaru</h5><a href="kunjungan" class="btn btn-sm btn-outline-success">Buka Kunjungan</a></div><div class="card-body"><?php if(!$recentVisits):?><div class="text-center text-muted py-4">Belum ada kunjungan.</div><?php else:foreach($recentVisits as $row):$p=$row['profile'];?><div class="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom"><div class="perpus-person"><span class="perpus-avatar"><?=perpus_h(mb_substr($p['nama'],0,1))?></span><div><strong><?=perpus_h($p['nama'])?></strong><div class="small text-muted"><?=perpus_h($p['unit'])?></div></div></div><span class="small text-muted text-nowrap"><?=date('H:i',strtotime($row['waktu_kunjungan']))?></span></div><?php endforeach;endif;?></div></div>
    </div>
</div>
