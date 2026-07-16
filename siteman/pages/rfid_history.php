<?php
$page = 'rfid_history';
require_once __DIR__ . '/../../config/perpus.php';
sds_perpus_ensure_schema($conn);

$q = trim((string)($_GET['q'] ?? ''));
$type = (string)($_GET['jenis'] ?? '');
$status = (string)($_GET['status'] ?? '');
if (!in_array($type, ['', 'siswa', 'pegawai'], true)) $type = '';
if (!in_array($status, ['', 'diganti', 'dilepas', 'hilang', 'rusak', 'migrasi'], true)) $status = '';

$where = ['1=1'];
$params = [];
$types = '';
if ($type !== '') { $where[] = 'r.pemilik_tipe=?'; $params[] = $type; $types .= 's'; }
if ($status !== '') { $where[] = 'r.status_akhir=?'; $params[] = $status; $types .= 's'; }
if ($q !== '') {
    $where[] = "(r.uid LIKE ? OR ps.nama_lengkap LIKE ? OR ps.nisn LIKE ? OR ps.nipd LIKE ? OR p.nama_lengkap LIKE ? OR p.nip LIKE ?)";
    $like = '%' . $q . '%';
    for ($i = 0; $i < 6; $i++) $params[] = $like;
    $types .= 'ssssss';
}

$sql = "SELECT r.*,
    CASE WHEN r.pemilik_tipe='siswa' THEN ps.nama_lengkap ELSE p.nama_lengkap END nama,
    CASE WHEN r.pemilik_tipe='siswa' THEN COALESCE(NULLIF(ps.nisn,''),ps.nipd) ELSE p.nip END identitas,
    CASE WHEN r.pemilik_tipe='siswa' THEN COALESCE(k.nama_kelas,'-') ELSE COALESCE(NULLIF(p.jabatan,''),'-') END unit,
    a.full_name diproses_nama
    FROM kartu_rfid_riwayat r
    LEFT JOIN pendaftaran_siswa ps ON r.pemilik_tipe='siswa' AND ps.id=r.pemilik_id
    LEFT JOIN siswa_kelas sk ON r.pemilik_tipe='siswa' AND sk.id=(SELECT sk2.id FROM siswa_kelas sk2 WHERE sk2.siswa_id=ps.id ORDER BY sk2.tahun_ajaran DESC,sk2.id DESC LIMIT 1)
    LEFT JOIN kelas k ON k.id=sk.kelas_id
    LEFT JOIN pegawai p ON r.pemilik_tipe='pegawai' AND p.pegawai_id=r.pemilik_id
    LEFT JOIN admins a ON a.id=r.diproses_oleh
    WHERE " . implode(' AND ', $where) . " ORDER BY r.id DESC LIMIT 1000";
$stmt = $conn->prepare($sql);
if ($types !== '') {
    $refs = [$types]; foreach ($params as $index => &$value) $refs[] = &$value;
    call_user_func_array([$stmt, 'bind_param'], $refs); unset($value);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stats = ['active'=>0,'student'=>0,'employee'=>0,'history'=>0];
$result = $conn->query("SELECT COUNT(*) active,SUM(pemilik_tipe='siswa') student,SUM(pemilik_tipe='pegawai') employee FROM kartu_rfid");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['active'] = (int)($row['active'] ?? 0);
    $stats['student'] = (int)($row['student'] ?? 0);
    $stats['employee'] = (int)($row['employee'] ?? 0);
}
$result = $conn->query('SELECT COUNT(*) total FROM kartu_rfid_riwayat');
if ($result) $stats['history'] = (int)($result->fetch_assoc()['total'] ?? 0);

require __DIR__ . '/partials/shared/master_page_style.php';
?>
<style>
.rfid-history .filter-grid{display:grid;grid-template-columns:minmax(250px,1.3fr) minmax(160px,.65fr) minmax(160px,.65fr) auto auto;gap:.55rem}.rfid-history .name{font-weight:700;color:#1e293b}.rfid-history .meta{font-size:.78rem;color:#64748b}.rfid-history code{font-size:.82rem}@media(max-width:800px){.rfid-history .filter-grid{grid-template-columns:1fr 1fr}.rfid-history .filter-grid .search{grid-column:1/-1}}@media(max-width:560px){.rfid-history .filter-grid{grid-template-columns:1fr}}
</style>
<div class="sds-master-page rfid-history">
    <div class="sds-hero"><div><h2>Riwayat Kartu RFID</h2><p>Riwayat pergantian, pelepasan, kartu hilang, dan kartu rusak untuk seluruh warga sekolah.</p></div><div class="sds-hero-actions"><a class="btn btn-outline-primary" href="students_rfid">Kartu Peserta Didik</a><a class="btn btn-primary" href="teachers_rfid">Kartu Pegawai</a></div></div>
    <div class="sds-stats"><div class="sds-stat-card"><small>Kartu Aktif</small><strong><?= number_format($stats['active'],0,',','.') ?></strong><span>Satu kartu aktif per pemilik</span></div><div class="sds-stat-card"><small>Peserta Didik</small><strong><?= number_format($stats['student'],0,',','.') ?></strong><span>Kartu aktif siswa</span></div><div class="sds-stat-card"><small>Pengajar/Pegawai</small><strong><?= number_format($stats['employee'],0,',','.') ?></strong><span>Kartu aktif pegawai</span></div><div class="sds-stat-card"><small>Total Riwayat</small><strong><?= number_format($stats['history'],0,',','.') ?></strong><span>Pergantian dan pelepasan kartu</span></div></div>
    <div class="card"><div class="card-header"><form class="filter-grid"><input class="form-control form-control-sm search" name="q" value="<?= htmlspecialchars($q,ENT_QUOTES,'UTF-8') ?>" placeholder="Cari UID, nama, NISN, NIPD, atau NIP..."><select class="form-select form-select-sm" name="jenis"><option value="">Semua Pemilik</option><option value="siswa" <?= $type==='siswa'?'selected':'' ?>>Peserta Didik</option><option value="pegawai" <?= $type==='pegawai'?'selected':'' ?>>Pengajar/Pegawai</option></select><select class="form-select form-select-sm" name="status"><option value="">Semua Status</option><?php foreach(['diganti'=>'Diganti','dilepas'=>'Dilepas','hilang'=>'Hilang','rusak'=>'Rusak','migrasi'=>'Migrasi'] as $value=>$label): ?><option value="<?= $value ?>" <?= $status===$value?'selected':'' ?>><?= $label ?></option><?php endforeach; ?></select><button class="btn btn-sm btn-primary">Tampilkan</button><a class="btn btn-sm btn-outline-secondary" href="rfid_history">Reset</a></form></div><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Waktu</th><th>Pemilik</th><th>UID Lama</th><th>Status Akhir</th><th>Keterangan</th><th>Diproses Oleh</th></tr></thead><tbody><?php if(!$rows): ?><tr><td colspan="6" class="text-center text-muted py-4">Belum ada riwayat kartu.</td></tr><?php endif; ?><?php foreach($rows as $row): ?><tr><td><?= htmlspecialchars(date('d/m/Y H:i',strtotime($row['tanggal_selesai'])),ENT_QUOTES,'UTF-8') ?></td><td><div class="name"><?= htmlspecialchars((string)($row['nama'] ?: 'Pemilik sudah dihapus'),ENT_QUOTES,'UTF-8') ?></div><div class="meta"><?= $row['pemilik_tipe']==='siswa'?'Peserta Didik':'Pengajar/Pegawai' ?> · <?= htmlspecialchars((string)($row['identitas'] ?: '-'),ENT_QUOTES,'UTF-8') ?> · <?= htmlspecialchars((string)($row['unit'] ?: '-'),ENT_QUOTES,'UTF-8') ?></div></td><td><code><?= htmlspecialchars($row['uid'],ENT_QUOTES,'UTF-8') ?></code></td><td><span class="badge <?= in_array($row['status_akhir'],['hilang','rusak'],true)?'bg-danger':($row['status_akhir']==='diganti'?'bg-primary':'bg-secondary') ?>"><?= htmlspecialchars(ucfirst($row['status_akhir']),ENT_QUOTES,'UTF-8') ?></span></td><td><?= htmlspecialchars((string)($row['keterangan'] ?: '-'),ENT_QUOTES,'UTF-8') ?></td><td><?= htmlspecialchars((string)($row['diproses_nama'] ?: 'Sistem'),ENT_QUOTES,'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div></div>
</div>
