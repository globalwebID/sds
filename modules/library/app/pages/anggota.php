<?php
if (!defined('SDS_PERPUSTAKAAN_APP')) { http_response_code(403); exit('Akses langsung tidak diizinkan.'); }
$page = 'anggota';
require __DIR__ . '/../partials/bootstrap.php';
if (empty($perpusAccess['allowed'])) return;

$message = '';
$error = '';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    try {
        perpus_check_csrf();
        if (!$perpusCanManage) throw new RuntimeException('Hanya admin Perpustakaan yang dapat mengubah keanggotaan.');
        $action = (string)($_POST['action'] ?? 'save');

        if ($action === 'generate_all') {
            $created = 0;
            $result = $conn->query('SELECT id FROM pendaftaran_siswa WHERE status_aktif=1');
            while ($result && ($row = $result->fetch_assoc())) {
                $before = $conn->query("SELECT id FROM perpus_anggota WHERE pemilik_tipe='siswa' AND pemilik_id=".(int)$row['id']." LIMIT 1")->num_rows;
                sds_perpus_ensure_member($conn, 'siswa', (int)$row['id'], true);
                if (!$before) $created++;
            }
            $result = $conn->query("SELECT pegawai_id FROM pegawai WHERE active='Y'");
            while ($result && ($row = $result->fetch_assoc())) {
                $before = $conn->query("SELECT id FROM perpus_anggota WHERE pemilik_tipe='pegawai' AND pemilik_id=".(int)$row['pegawai_id']." LIMIT 1")->num_rows;
                sds_perpus_ensure_member($conn, 'pegawai', (int)$row['pegawai_id'], true);
                if (!$before) $created++;
            }
            $message = $created . ' keanggotaan baru berhasil dibuat dari master SDS.';
        } else {
            $memberId = (int)($_POST['id'] ?? 0);
            $ownerType = (string)($_POST['pemilik_tipe'] ?? '');
            $ownerId = (int)($_POST['pemilik_id'] ?? 0);
            $targetOwner = trim((string)($_POST['target_owner'] ?? ''));
            $number = trim((string)($_POST['nomor_anggota'] ?? ''));
            $typeId = (int)($_POST['tipe_member_id'] ?? 0);
            $status = (string)($_POST['status_keanggotaan'] ?? 'aktif');
            $endDate = trim((string)($_POST['tanggal_berakhir'] ?? '')) ?: null;
            $note = trim((string)($_POST['catatan'] ?? ''));
            if (!in_array($status, ['aktif','nonaktif','perlu_verifikasi'], true)) throw new RuntimeException('Status keanggotaan tidak valid.');
            if ($number === '') throw new RuntimeException('Nomor anggota wajib diisi.');
            if ($typeId <= 0) throw new RuntimeException('Tipe anggota wajib dipilih.');

            if ($memberId <= 0) {
                if (!in_array($ownerType, ['siswa','pegawai'], true) || $ownerId <= 0) throw new RuntimeException('Pemilik anggota tidak valid.');
                $member = sds_perpus_ensure_member($conn, $ownerType, $ownerId, $status === 'aktif');
                $memberId = (int)$member['id'];
            }

            $stmt = $conn->prepare('SELECT * FROM perpus_anggota WHERE id=? LIMIT 1');
            $stmt->bind_param('i', $memberId);
            $stmt->execute();
            $currentMember = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$currentMember) throw new RuntimeException('Data keanggotaan tidak ditemukan.');

            // Data lama yang belum cocok dapat dipasangkan manual ke master SDS.
            if ((string)$currentMember['pemilik_tipe'] === 'legacy' && $targetOwner !== '') {
                if (!preg_match('/^(siswa|pegawai):(\d+)$/', $targetOwner, $match)) {
                    throw new RuntimeException('Pilihan master SDS tidak valid.');
                }
                $targetType = (string)$match[1];
                $targetId = (int)$match[2];
                if ($targetId <= 0) throw new RuntimeException('Pilihan master SDS tidak valid.');

                if ($targetType === 'siswa') {
                    $stmt = $conn->prepare('SELECT id FROM pendaftaran_siswa WHERE id=? LIMIT 1');
                } else {
                    $stmt = $conn->prepare('SELECT pegawai_id FROM pegawai WHERE pegawai_id=? LIMIT 1');
                }
                $stmt->bind_param('i', $targetId);
                $stmt->execute();
                $targetExists = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!$targetExists) throw new RuntimeException('Master SDS yang dipilih tidak ditemukan.');

                $conn->begin_transaction();
                try {
                    $stmt = $conn->prepare('SELECT * FROM perpus_anggota WHERE pemilik_tipe=? AND pemilik_id=? AND id<>? LIMIT 1 FOR UPDATE');
                    $stmt->bind_param('sii', $targetType, $targetId, $memberId);
                    $stmt->execute();
                    $targetMember = $stmt->get_result()->fetch_assoc();
                    $stmt->close();

                    if ($targetMember) {
                        $targetMemberId = (int)$targetMember['id'];
                        $stmt = $conn->prepare('UPDATE perpus_peminjaman SET anggota_id=? WHERE anggota_id=?');
                        $stmt->bind_param('ii', $targetMemberId, $memberId);
                        $stmt->execute();
                        $stmt->close();
                        $stmt = $conn->prepare('UPDATE perpus_kunjungan SET anggota_id=? WHERE anggota_id=?');
                        $stmt->bind_param('ii', $targetMemberId, $memberId);
                        $stmt->execute();
                        $stmt->close();

                        $stmt = $conn->prepare("UPDATE perpus_anggota SET
                            legacy_id_anggota=COALESCE(NULLIF(legacy_id_anggota,''),?),
                            legacy_nis=COALESCE(NULLIF(legacy_nis,''),?),
                            legacy_nama=COALESCE(NULLIF(legacy_nama,''),?),
                            legacy_kelas=COALESCE(NULLIF(legacy_kelas,''),?),
                            legacy_jurusan=COALESCE(NULLIF(legacy_jurusan,''),?),
                            legacy_tanggal_lahir=COALESCE(NULLIF(legacy_tanggal_lahir,''),?)
                            WHERE id=?");
                        $stmt->bind_param('ssssssi',
                            $currentMember['legacy_id_anggota'],
                            $currentMember['legacy_nis'],
                            $currentMember['legacy_nama'],
                            $currentMember['legacy_kelas'],
                            $currentMember['legacy_jurusan'],
                            $currentMember['legacy_tanggal_lahir'],
                            $targetMemberId
                        );
                        $stmt->execute();
                        $stmt->close();
                        $stmt = $conn->prepare('DELETE FROM perpus_anggota WHERE id=?');
                        $stmt->bind_param('i', $memberId);
                        $stmt->execute();
                        $stmt->close();
                        $memberId = $targetMemberId;
                        $number = (string)$targetMember['nomor_anggota'];
                    } else {
                        $stmt = $conn->prepare('UPDATE perpus_anggota SET pemilik_tipe=?,pemilik_id=? WHERE id=?');
                        $stmt->bind_param('sii', $targetType, $targetId, $memberId);
                        $stmt->execute();
                        $stmt->close();
                    }
                    $ownerType = $targetType;
                    $ownerId = $targetId;
                    if ($status === 'perlu_verifikasi') $status = 'aktif';
                    $conn->commit();
                } catch (Throwable $linkError) {
                    $conn->rollback();
                    throw $linkError;
                }
            } else {
                $ownerType = (string)$currentMember['pemilik_tipe'];
                $ownerId = (int)($currentMember['pemilik_id'] ?? 0);
            }

            $nullableOwner = $ownerId > 0 ? $ownerId : null;
            $stmt = $conn->prepare('UPDATE perpus_anggota SET pemilik_tipe=?,pemilik_id=?,nomor_anggota=?,tipe_member_id=?,status_keanggotaan=?,tanggal_berakhir=?,catatan=? WHERE id=?');
            $stmt->bind_param('sisisssi', $ownerType, $nullableOwner, $number, $typeId, $status, $endDate, $note, $memberId);
            $stmt->execute();
            $stmt->close();
            $message = $targetOwner !== '' ? 'Data lama berhasil dihubungkan ke master SDS.' : 'Keanggotaan berhasil diperbarui.';
        }
    } catch (Throwable $e) {
        $raw = $e->getMessage();
        $error = stripos($raw, 'duplicate') !== false ? 'Nomor anggota atau pemilik sudah digunakan.' : $raw;
    }
}

$types = [];
$result = $conn->query('SELECT * FROM perpus_tipe_member ORDER BY nama');
while ($result && ($row = $result->fetch_assoc())) $types[] = $row;

$studentCandidates = [];
$employeeCandidates = [];
if ($perpusCanManage) {
    $result = $conn->query("SELECT ps.id,ps.nama_lengkap,COALESCE(NULLIF(ps.nisn,''),ps.nipd) identitas,COALESCE(k.nama_kelas,'Belum ada rombel') unit
        FROM pendaftaran_siswa ps
        LEFT JOIN siswa_kelas sk ON sk.id=(SELECT sk2.id FROM siswa_kelas sk2 WHERE sk2.siswa_id=ps.id ORDER BY sk2.tahun_ajaran DESC,sk2.id DESC LIMIT 1)
        LEFT JOIN kelas k ON k.id=sk.kelas_id
        ORDER BY ps.nama_lengkap");
    while ($result && ($row = $result->fetch_assoc())) $studentCandidates[] = $row;
    $result = $conn->query("SELECT pegawai_id id,nama_lengkap,COALESCE(NULLIF(nip,''),'-') identitas,COALESCE(NULLIF(jabatan,''),'Pengajar/Pegawai') unit FROM pegawai ORDER BY nama_lengkap");
    while ($result && ($row = $result->fetch_assoc())) $employeeCandidates[] = $row;
}

$q = trim((string)($_GET['q'] ?? ''));
$filterType = (string)($_GET['jenis'] ?? '');
$filterStatus = (string)($_GET['status'] ?? '');
$validTypes = ['siswa','pegawai','legacy'];
$validStatus = ['aktif','nonaktif','perlu_verifikasi'];
if (!in_array($filterType, $validTypes, true)) $filterType = '';
if (!in_array($filterStatus, $validStatus, true)) $filterStatus = '';

$allRows = [];

// Jangan menyatukan master siswa, pegawai, dan data lama memakai UNION.
// Database SDS berasal dari beberapa modul lama yang memakai collation berbeda
// (utf8mb3/utf8mb4 general/unicode). Pada MySQL tertentu UNION dapat gagal
// dengan pesan "Illegal mix of collations". Query dipisah lalu digabung di PHP.
$memberListQueries = [
    'peserta didik' => "SELECT pa.id anggota_id,'siswa' pemilik_tipe,ps.id pemilik_id,ps.nama_lengkap nama,
        COALESCE(NULLIF(ps.nisn,''),NULLIF(ps.nipd,''),'-') identitas,COALESCE(k.nama_kelas,'-') unit,
        COALESCE(j.nama_jurusan,'-') detail,ps.status_aktif master_aktif,kr.uid,
        pa.nomor_anggota,pa.tipe_member_id,tm.nama tipe_member,pa.status_keanggotaan,pa.tanggal_berakhir,pa.catatan,
        pa.legacy_id_anggota,pa.legacy_nis,pa.legacy_nama,pa.legacy_kelas,pa.legacy_jurusan
        FROM pendaftaran_siswa ps
        LEFT JOIN siswa_kelas sk ON sk.id=(SELECT sk2.id FROM siswa_kelas sk2 WHERE sk2.siswa_id=ps.id ORDER BY sk2.tahun_ajaran DESC,sk2.id DESC LIMIT 1)
        LEFT JOIN kelas k ON k.id=sk.kelas_id
        LEFT JOIN jurusan j ON j.id=COALESCE(k.jurusan_id,ps.jurusan_id)
        LEFT JOIN kartu_rfid kr ON kr.pemilik_tipe='siswa' AND kr.pemilik_id=ps.id
        LEFT JOIN perpus_anggota pa ON pa.pemilik_tipe='siswa' AND pa.pemilik_id=ps.id
        LEFT JOIN perpus_tipe_member tm ON tm.id=pa.tipe_member_id",
    'pengajar dan pegawai' => "SELECT pa.id anggota_id,'pegawai' pemilik_tipe,p.pegawai_id pemilik_id,p.nama_lengkap nama,
        COALESCE(NULLIF(p.nip,''),'-') identitas,COALESCE(NULLIF(p.jabatan,''),'-') unit,'Pengajar/Pegawai' detail,
        (p.active='Y') master_aktif,kr.uid,
        pa.nomor_anggota,pa.tipe_member_id,tm.nama tipe_member,pa.status_keanggotaan,pa.tanggal_berakhir,pa.catatan,
        pa.legacy_id_anggota,pa.legacy_nis,pa.legacy_nama,pa.legacy_kelas,pa.legacy_jurusan
        FROM pegawai p
        LEFT JOIN kartu_rfid kr ON kr.pemilik_tipe='pegawai' AND kr.pemilik_id=p.pegawai_id
        LEFT JOIN perpus_anggota pa ON pa.pemilik_tipe='pegawai' AND pa.pemilik_id=p.pegawai_id
        LEFT JOIN perpus_tipe_member tm ON tm.id=pa.tipe_member_id",
    'anggota lama' => "SELECT pa.id anggota_id,'legacy' pemilik_tipe,0 pemilik_id,COALESCE(NULLIF(pa.legacy_nama,''),'Anggota lama') nama,
        COALESCE(NULLIF(pa.legacy_nis,''),NULLIF(pa.legacy_id_anggota,''),'-') identitas,COALESCE(NULLIF(pa.legacy_kelas,''),'-') unit,
        COALESCE(NULLIF(pa.legacy_jurusan,''),'Perlu verifikasi') detail,0 master_aktif,NULL uid,
        pa.nomor_anggota,pa.tipe_member_id,tm.nama tipe_member,pa.status_keanggotaan,pa.tanggal_berakhir,pa.catatan,
        pa.legacy_id_anggota,pa.legacy_nis,pa.legacy_nama,pa.legacy_kelas,pa.legacy_jurusan
        FROM perpus_anggota pa
        LEFT JOIN perpus_tipe_member tm ON tm.id=pa.tipe_member_id
        WHERE pa.pemilik_tipe='legacy'",
];

$listLoadErrors = [];
foreach ($memberListQueries as $sourceLabel => $memberSql) {
    try {
        $result = $conn->query($memberSql);
        while ($result && ($memberRow = $result->fetch_assoc())) {
            $allRows[] = $memberRow;
        }
        if ($result instanceof mysqli_result) {
            $result->free();
        }
    } catch (Throwable $listError) {
        $listLoadErrors[] = $sourceLabel;
        error_log('[SDS Perpustakaan] Gagal memuat ' . $sourceLabel . ': ' . $listError->getMessage());
    }
}

if ($listLoadErrors && $error === '') {
    $error = 'Sebagian data anggota belum dapat dimuat: ' . implode(', ', $listLoadErrors) . '. Periksa struktur database atau log PHP.';
}

// Variabel result tidak lagi dipakai sebagai hasil UNION; loop filter bekerja
// terhadap array hasil gabungan dari ketiga query di atas.
$result = null;
$filteredRows = [];
foreach ($allRows as $row) {
    $effectiveStatus = (string)($row['status_keanggotaan'] ?? '');
    if ($effectiveStatus === '') $effectiveStatus = 'belum_terdaftar';
    $haystack = mb_strtolower(implode(' ', [
        (string)($row['nama'] ?? ''),
        (string)($row['identitas'] ?? ''),
        (string)($row['unit'] ?? ''),
        (string)($row['uid'] ?? ''),
        (string)($row['nomor_anggota'] ?? ''),
    ]));
    if ($q !== '' && mb_strpos($haystack, mb_strtolower($q)) === false) continue;
    if ($filterType !== '' && (string)($row['pemilik_tipe'] ?? '') !== $filterType) continue;
    if ($filterStatus !== '' && $effectiveStatus !== $filterStatus) continue;
    $row['status_effective'] = $effectiveStatus;
    $filteredRows[] = $row;
}
$allRows = $filteredRows;
unset($filteredRows);
usort($allRows, fn($a,$b) => strcasecmp((string)($a['nama'] ?? ''), (string)($b['nama'] ?? '')));

$totalRegistered = 0; $totalActive = 0; $totalNoCard = 0; $totalVerify = 0;
foreach ($allRows as $row) {
    if ((int)$row['anggota_id'] > 0) $totalRegistered++;
    if ($row['status_effective'] === 'aktif') $totalActive++;
    if (trim((string)$row['uid']) === '') $totalNoCard++;
    if ($row['status_effective'] === 'perlu_verifikasi' || $row['pemilik_tipe'] === 'legacy') $totalVerify++;
}

require __DIR__ . '/../partials/master_page_style.php';
?>
<div class="sds-master-page perpus-member-page">
    <div class="sds-hero">
        <div><h2>Anggota Perpustakaan</h2><p>Keanggotaan mengambil identitas peserta didik dan pegawai langsung dari master SDS.</p></div>
        <div class="sds-hero-actions">
            <?php if ($perpusCanManage): ?>
            <form method="post" class="d-inline" onsubmit="return confirm('Buat keanggotaan untuk seluruh siswa dan pegawai aktif yang belum terdaftar?')">
                <input type="hidden" name="csrf" value="<?= perpus_h(perpus_csrf()) ?>"><input type="hidden" name="action" value="generate_all">
                <button class="btn btn-primary"><i data-feather="user-plus" class="me-1"></i>Sinkronkan Anggota SDS</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php require __DIR__ . '/../partials/nav.php'; ?>
    <?php if ($message): ?><div class="alert alert-success"><?= perpus_h($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= perpus_h($error) ?></div><?php endif; ?>

    <div class="sds-stats">
        <div class="sds-stat-card"><small>Ditampilkan</small><strong><?= number_format(count($allRows),0,',','.') ?></strong><span>Master SDS dan anggota lama</span></div>
        <div class="sds-stat-card"><small>Terdaftar</small><strong><?= number_format($totalRegistered,0,',','.') ?></strong><span>Memiliki nomor anggota</span></div>
        <div class="sds-stat-card"><small>Keanggotaan Aktif</small><strong><?= number_format($totalActive,0,',','.') ?></strong><span>Dapat melakukan transaksi</span></div>
        <div class="sds-stat-card"><small>Perlu Verifikasi</small><strong><?= number_format($totalVerify,0,',','.') ?></strong><span>Data lama belum terhubung ke master</span></div>
    </div>

    <div class="card">
        <div class="card-header"><form method="get" class="filter-grid">
            <input class="form-control form-control-sm search" name="q" value="<?= perpus_h($q) ?>" placeholder="Cari nama, NISN/NIP, nomor anggota, RFID...">
            <select class="form-select form-select-sm" name="jenis"><option value="">Semua Jenis</option><option value="siswa" <?= $filterType==='siswa'?'selected':'' ?>>Peserta Didik</option><option value="pegawai" <?= $filterType==='pegawai'?'selected':'' ?>>Pengajar/Pegawai</option><option value="legacy" <?= $filterType==='legacy'?'selected':'' ?>>Data Lama</option></select>
            <select class="form-select form-select-sm" name="status"><option value="">Semua Status</option><option value="aktif" <?= $filterStatus==='aktif'?'selected':'' ?>>Aktif</option><option value="nonaktif" <?= $filterStatus==='nonaktif'?'selected':'' ?>>Nonaktif</option><option value="perlu_verifikasi" <?= $filterStatus==='perlu_verifikasi'?'selected':'' ?>>Perlu Verifikasi</option></select>
            <button class="btn btn-sm btn-primary">Tampilkan</button><a href="anggota" class="btn btn-sm btn-outline-secondary">Reset</a>
        </form></div>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th>Anggota</th><th>Jenis / Unit</th><th>Nomor Anggota</th><th>RFID</th><th>Status</th><th class="text-end">Aksi</th></tr></thead>
            <tbody>
            <?php if (!$allRows): ?><tr><td colspan="6" class="text-center text-muted py-4">Data tidak ditemukan.</td></tr><?php endif; ?>
            <?php foreach ($allRows as $row):
                $isRegistered=(int)$row['anggota_id']>0; $status=$row['status_effective'];
                $payload = htmlspecialchars(json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8');
            ?>
            <tr>
                <td><div class="member-name"><?= perpus_h($row['nama']) ?></div><div class="member-meta"><?= perpus_h($row['identitas'] ?: '-') ?></div></td>
                <td><span class="badge bg-light text-dark border"><?= $row['pemilik_tipe']==='siswa'?'Peserta Didik':($row['pemilik_tipe']==='pegawai'?'Pegawai':'Data Lama') ?></span><div class="member-meta mt-1"><?= perpus_h($row['unit']) ?> · <?= perpus_h($row['detail']) ?></div></td>
                <td><?= $isRegistered?'<code>'.perpus_h($row['nomor_anggota']).'</code>':'<span class="text-muted">Belum terdaftar</span>' ?><div class="member-meta"><?= perpus_h($row['tipe_member'] ?: '-') ?></div></td>
                <td><?= trim((string)$row['uid'])!==''?'<code>'.perpus_h($row['uid']).'</code>':'<span class="badge bg-warning text-dark">Belum ada kartu</span>' ?></td>
                <td><?php if ($status==='aktif'): ?><span class="badge bg-success">Aktif</span><?php elseif($status==='nonaktif'): ?><span class="badge bg-secondary">Nonaktif</span><?php elseif($status==='perlu_verifikasi'): ?><span class="badge bg-danger">Perlu Verifikasi</span><?php else: ?><span class="badge bg-light text-dark border">Belum Terdaftar</span><?php endif; ?></td>
                <td class="text-end"><?php if ($perpusCanManage): ?><button type="button" class="btn btn-sm btn-outline-primary" onclick="openMemberModal(this)" data-row="<?= $payload ?>"><?= $isRegistered?'Edit':'Aktifkan' ?></button><?php endif; ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table></div>
    </div>
</div>

<div class="modal fade" id="memberModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">
<form method="post"><div class="modal-header"><h5 class="modal-title" id="memberModalTitle">Keanggotaan Perpustakaan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" name="csrf" value="<?= perpus_h(perpus_csrf()) ?>"><input type="hidden" name="action" value="save"><input type="hidden" name="id" id="memberId"><input type="hidden" name="pemilik_tipe" id="memberOwnerType"><input type="hidden" name="pemilik_id" id="memberOwnerId">
<div class="alert alert-light border"><strong id="memberName">-</strong><div class="small text-muted" id="memberIdentity">-</div></div>
<div class="mb-3" id="memberTargetWrap" style="display:none"><label class="form-label">Hubungkan Data Lama ke Master SDS</label><select class="form-select" name="target_owner" id="memberTarget"><option value="">Belum ditemukan — tetap tandai Perlu Verifikasi</option><optgroup label="Peserta Didik"><?php foreach($studentCandidates as $candidate): ?><option value="siswa:<?= (int)$candidate['id'] ?>"><?= perpus_h($candidate['nama_lengkap']) ?> — <?= perpus_h($candidate['identitas']) ?> — <?= perpus_h($candidate['unit']) ?></option><?php endforeach; ?></optgroup><optgroup label="Pengajar & Pegawai"><?php foreach($employeeCandidates as $candidate): ?><option value="pegawai:<?= (int)$candidate['id'] ?>"><?= perpus_h($candidate['nama_lengkap']) ?> — <?= perpus_h($candidate['identitas']) ?> — <?= perpus_h($candidate['unit']) ?></option><?php endforeach; ?></optgroup></select><div class="form-text">Saat dihubungkan, seluruh riwayat pinjaman dan kunjungan data lama dipindahkan ke identitas SDS yang dipilih.</div></div>
<div class="row g-3"><div class="col-md-6"><label class="form-label">Nomor Anggota</label><input class="form-control" name="nomor_anggota" id="memberNumber" required></div>
<div class="col-md-6"><label class="form-label">Tipe Anggota</label><select class="form-select" name="tipe_member_id" id="memberType" required><?php foreach($types as $type): ?><option value="<?= (int)$type['id'] ?>"><?= perpus_h($type['nama']) ?></option><?php endforeach; ?></select></div>
<div class="col-md-6"><label class="form-label">Status</label><select class="form-select" name="status_keanggotaan" id="memberStatus"><option value="aktif">Aktif</option><option value="nonaktif">Nonaktif</option><option value="perlu_verifikasi">Perlu Verifikasi</option></select></div>
<div class="col-md-6"><label class="form-label">Berlaku Sampai</label><input type="date" class="form-control" name="tanggal_berakhir" id="memberEnd"></div>
<div class="col-12"><label class="form-label">Catatan</label><textarea class="form-control" name="catatan" id="memberNote" rows="2"></textarea></div></div>
</div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button class="btn btn-primary">Simpan</button></div></form>
</div></div></div>
<script>
function perpusModal(id){const el=document.getElementById(id);if(!el)return null;if(window.bootstrap&&bootstrap.Modal){return typeof bootstrap.Modal.getOrCreateInstance==='function'?bootstrap.Modal.getOrCreateInstance(el):new bootstrap.Modal(el);}return null;}
function openMemberModal(button){const row=JSON.parse(button.dataset.row||'{}');document.getElementById('memberId').value=row.anggota_id||0;document.getElementById('memberOwnerType').value=row.pemilik_tipe||'';document.getElementById('memberOwnerId').value=row.pemilik_id||0;document.getElementById('memberName').textContent=row.nama||'-';document.getElementById('memberIdentity').textContent=(row.identitas||'-')+' · '+(row.unit||'-');let number=row.nomor_anggota||'';if(!number&&row.pemilik_id){number=(row.pemilik_tipe==='pegawai'?'P':'S')+String(row.pemilik_id).padStart(7,'0');}document.getElementById('memberNumber').value=number;document.getElementById('memberType').value=row.tipe_member_id||<?= (int)($types[0]['id']??0) ?>;document.getElementById('memberStatus').value=row.status_effective==='belum_terdaftar'?'aktif':(row.status_effective||'aktif');document.getElementById('memberEnd').value=row.tanggal_berakhir||'';document.getElementById('memberNote').value=row.catatan||'';const isLegacy=row.pemilik_tipe==='legacy';document.getElementById('memberTargetWrap').style.display=isLegacy?'block':'none';document.getElementById('memberTarget').value='';const modal=perpusModal('memberModal');if(modal)modal.show();}
</script>
