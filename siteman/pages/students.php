<?php
$page = 'students';
$filterTahun = $_GET['tahun'] ?? '';
$filterTingkat = $_GET['tingkat'] ?? '';
$filterKelas = $_GET['kelas'] ?? '';
$filterAsalSekolah = trim($_GET['asal_sekolah'] ?? '');
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$statusFilter = in_array($statusFilter, ['aktif', 'nonaktif'], true) ? $statusFilter : '';
$statusValue = $statusFilter === 'aktif' ? 1 : ($statusFilter === 'nonaktif' ? 0 : null);

$tahunActive = $filterTahun !== '';
$tingkatActive = $filterTingkat !== '' && ctype_digit((string)$filterTingkat);
$kelasActive = $filterKelas !== '';
$asalSekolahActive = $filterAsalSekolah !== '';
$searchActive = $search !== '';
$statusActive = $statusFilter !== '';

// Pagination
$limit = 10;
$currentPage = isset($_GET['halaman']) && is_numeric($_GET['halaman']) ? (int)$_GET['halaman'] : 1;
$offset = ($currentPage - 1) * $limit;

// Filter builder
$filterConditions = [];
$params = [];
$types = '';

if ($tahunActive) {
    $filterConditions[] = "k.tahun_ajaran = ?";
    $params[] = $filterTahun;
    $types .= 's';
}
if ($tingkatActive) {
    $filterConditions[] = "k.tingkat_id = ?";
    $params[] = (int)$filterTingkat;
    $types .= 'i';
}
if ($kelasActive) {
    $filterConditions[] = "k.nama_kelas = ?";
    $params[] = $filterKelas;
    $types .= 's';
}
if ($asalSekolahActive) {
    $filterConditions[] = "ps.sekolah_asal = ?";
    $params[] = $filterAsalSekolah;
    $types .= 's';
}
if ($searchActive) {
    $filterConditions[] = "(ps.nama_lengkap LIKE ? OR ps.nisn LIKE ? OR ps.nipd LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}
if ($statusActive) {
    $filterConditions[] = "ps.status_aktif = ?";
    $params[] = $statusValue;
    $types .= 'i';
}

// Buat dua versi filter clause
// $whereForCount = count($filterConditions) ? 'WHERE ' . implode(' AND ', $filterConditions) : '';
$whereForData = count($filterConditions) ? ' AND ' . implode(' AND ', $filterConditions) : '';

// ====================
// HITUNG TOTAL DATA
// ====================
// $totalSql = "
//     SELECT COUNT(*) AS total 
//     FROM pendaftaran_siswa ps
//     JOIN kelas k ON ps.kelas_id = k.id
//     $whereForCount
// ";

$whereForCount = count($filterConditions) ? 'WHERE ' . implode(' AND ', $filterConditions) : 'WHERE 1=1';

$totalSql = "
  SELECT COUNT(*) AS total
  FROM pendaftaran_siswa ps
  JOIN siswa_kelas sk ON sk.siswa_id = ps.id
  JOIN kelas k ON k.id = sk.kelas_id
  $whereForCount
  AND sk.tahun_ajaran = (
      SELECT MAX(sk2.tahun_ajaran)
      FROM siswa_kelas sk2
      WHERE sk2.siswa_id = ps.id
  )
";

$totalSql = "
    SELECT COUNT(*) AS total 
    FROM pendaftaran_siswa ps
    JOIN siswa_kelas sk ON sk.siswa_id = ps.id
    JOIN kelas k ON k.id = sk.kelas_id
    $whereForCount
    AND sk.tahun_ajaran = (
        SELECT MAX(sk2.tahun_ajaran)
        FROM siswa_kelas sk2
        WHERE sk2.siswa_id = ps.id
    )
";


$totalStmt = $conn->prepare($totalSql);
if (count($params)) {
    $totalStmt->bind_param($types, ...$params);
}
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalData = $totalResult ? $totalResult->fetch_assoc()['total'] : 0;
$totalPages = ceil($totalData / $limit);

// ====================
// AMBIL DATA SISWA
// ====================
$dataSql = "
    SELECT 
        ps.id, 
        ps.nama_lengkap, 
        ps.foto,
        ps.nisn,
        ps.nipd, 
        ps.tanggal_input,
        ps.sudah_dapodik,
        ps.status_aktif,
        ps.alasan_nonaktif,
        ps.sekolah_asal,
        tk.nama_tingkat,
        k.nama_kelas, 
        k.tahun_ajaran
    FROM pendaftaran_siswa ps
    JOIN siswa_kelas sk ON sk.siswa_id = ps.id
    JOIN kelas k ON k.id = sk.kelas_id
    JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    WHERE sk.tahun_ajaran = (
        SELECT MAX(sk2.tahun_ajaran)
        FROM siswa_kelas sk2
        WHERE sk2.siswa_id = ps.id
    )
    $whereForData
    ORDER BY ps.tanggal_input DESC
    LIMIT ? OFFSET ?
";

$paramsWithLimit = $params;
$paramsWithLimit[] = $limit;
$paramsWithLimit[] = $offset;
$typesWithLimit = $types . 'ii';

$dataStmt = $conn->prepare($dataSql);
$dataStmt->bind_param($typesWithLimit, ...$paramsWithLimit);
$dataStmt->execute();
$result = $dataStmt->get_result();

// Ambil tahun ajaran dan kelas yang tersedia
$tahunQuery = $conn->query("SELECT DISTINCT tahun_ajaran FROM kelas ORDER BY tahun_ajaran DESC");
$tahunList = $tahunQuery ? $tahunQuery->fetch_all(MYSQLI_ASSOC) : [];

$tingkatQuery = $conn->query("SELECT id, nama_tingkat FROM tingkat_kelas ORDER BY id ASC");
$tingkatList = $tingkatQuery ? $tingkatQuery->fetch_all(MYSQLI_ASSOC) : [];

$kelasQuery = $conn->query("SELECT DISTINCT nama_kelas FROM kelas ORDER BY nama_kelas ASC");
$kelasList = $kelasQuery ? $kelasQuery->fetch_all(MYSQLI_ASSOC) : [];

$asalSekolahQuery = $conn->query("SELECT DISTINCT sekolah_asal FROM pendaftaran_siswa WHERE sekolah_asal IS NOT NULL AND TRIM(sekolah_asal) <> '' ORDER BY sekolah_asal ASC");
$asalSekolahList = $asalSekolahQuery ? $asalSekolahQuery->fetch_all(MYSQLI_ASSOC) : [];

function buildUrlWithout($keysToRemove)
{
    $query = $_GET;
    foreach ((array)$keysToRemove as $key) {
        unset($query[$key]);
    }
    return '?' . http_build_query($query);
}

$exportQuery = $_GET;
unset($exportQuery['halaman']);
$exportUrl = 'students_export.php' . (count($exportQuery) ? '?' . http_build_query($exportQuery) : '');
?>

<style>
    /* Style disamakan dengan update Jurusan v13: kotak, ringan, compact, dan dekat dengan dashboard. */
    .sds-dashboard-ref{padding:0}
    .sds-dashboard-ref .sds-hero{background:#fff;border:1px solid #dee2e6;border-radius:0;padding:1rem;display:flex;align-items:center;justify-content:space-between;gap:.75rem;margin-bottom:0;box-shadow:unset}
    .sds-dashboard-ref .sds-hero h2{margin:0 0 .25rem;font-size:1.25rem;font-weight:600;color:#334151}
    .sds-dashboard-ref .sds-hero p{margin:0;color:#6c757d;font-size:.875rem}
    .sds-dashboard-ref .sds-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end}
    .sds-dashboard-ref .sds-card,.sds-dashboard-ref .sds-stat-card{background:#fff;border:1px solid #dee2e6;border-radius:0;box-shadow:unset}
    .sds-dashboard-ref .sds-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:0;margin-bottom:0}
    .sds-dashboard-ref .sds-stats.three{grid-template-columns:repeat(3,minmax(0,1fr))}
    .sds-dashboard-ref .sds-stat-card{padding:1rem;min-height:104px}
    .sds-dashboard-ref .sds-stat-card small{display:block;color:#6c757d;font-weight:700;text-transform:uppercase;letter-spacing:.04em;font-size:.72rem}
    .sds-dashboard-ref .sds-stat-card strong{display:block;font-size:1.55rem;line-height:1.1;margin-top:.25rem;color:#212529;font-weight:700}
    .sds-dashboard-ref .sds-stat-card span{display:block;color:#6c757d;font-size:.78rem;margin-top:.25rem}
    .sds-dashboard-ref .sds-card-header{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.9rem 1rem;border-bottom:1px solid #dee2e6;background:#f8f9fa}
    .sds-dashboard-ref .sds-card-header h5{margin:0;font-weight:600;color:#334151;font-size:1rem}
    .sds-dashboard-ref .sds-card-body{padding:1rem}
    .sds-dashboard-ref .sds-toolbar{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;margin-bottom:.75rem}
    .sds-dashboard-ref .sds-toolbar form{margin:0}
    .sds-dashboard-ref .sds-toolbar .form-select,.sds-dashboard-ref .sds-toolbar .form-control{min-height:31px}
    .sds-dashboard-ref .sds-table-wrap{overflow:auto}
    .sds-dashboard-ref .sds-table{width:100%;border-collapse:collapse;background:#fff;min-width:980px;border:1px solid #eee}
    .sds-dashboard-ref .sds-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;background:#f8f9fa;padding:.55rem .65rem;border-bottom:1px solid #dee2e6;white-space:nowrap}
    .sds-dashboard-ref .sds-table td{padding:.5rem .65rem;border-bottom:1px solid #edf1f5;vertical-align:middle;color:#334151}
    .sds-dashboard-ref .sds-table tfoot th,.sds-dashboard-ref .sds-table tfoot td{background:#f8f9fa;border-top:1px solid #dee2e6;border-bottom:0;color:#334151;font-weight:700}
    .sds-dashboard-ref .sds-table tr:last-child td{border-bottom:0}
    .sds-dashboard-ref .sds-mini{font-size:.78rem;color:#6c757d}
    .sds-dashboard-ref .sds-code{display:inline-flex;align-items:center;border:1px solid #dee2e6;background:#f8f9fa;border-radius:.25rem;padding:.25rem .45rem;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;font-size:.78rem;color:#334151;white-space:nowrap}
    .sds-dashboard-ref .sds-badge{display:inline-flex;align-items:center;gap:5px;border-radius:.25rem;padding:.35rem .5rem;font-size:.75rem;font-weight:600;white-space:nowrap}
    .sds-dashboard-ref .sds-badge.ok{background:#d1e7dd;color:#0f5132}
    .sds-dashboard-ref .sds-badge.warn{background:#fff3cd;color:#664d03}
    .sds-dashboard-ref .sds-badge.info{background:#cff4fc;color:#055160}
    .sds-dashboard-ref .sds-badge.danger{background:#f8d7da;color:#842029}
    .sds-dashboard-ref .sds-actions{display:flex;align-items:center;gap:.35rem;justify-content:flex-end;white-space:nowrap;flex-wrap:wrap}
    .sds-dashboard-ref .sds-actions .btn{padding:.28rem .55rem;font-size:.78rem}
    .sds-dashboard-ref .sds-filter-chip{display:inline-flex;align-items:center;gap:.35rem;background:#f8f9fa;border:1px solid #dee2e6;color:#495057;padding:.25rem .5rem;border-radius:.25rem;font-size:.78rem;margin:.15rem}
    .sds-dashboard-ref .sds-filter-chip a{color:#dc3545;text-decoration:none;font-weight:700}
    .sds-dashboard-ref .sds-photo{width:34px;height:40px;object-fit:cover;border-radius:0;border:1px solid #dee2e6;background:#f8f9fa}
    .sds-dashboard-ref .alert{margin:1rem 1rem 0}
    @media(max-width:1200px){.sds-dashboard-ref .sds-stats,.sds-dashboard-ref .sds-stats.three{grid-template-columns:repeat(2,1fr)}.sds-dashboard-ref .sds-hero{display:block}.sds-dashboard-ref .sds-hero-actions{justify-content:flex-start;margin-top:.75rem}}
    @media(max-width:700px){.sds-dashboard-ref{padding:0 6px}.sds-dashboard-ref .sds-stats,.sds-dashboard-ref .sds-stats.three{grid-template-columns:1fr}.sds-dashboard-ref .sds-toolbar{display:block}.sds-dashboard-ref .sds-toolbar form{margin-top:.75rem}.sds-dashboard-ref .sds-hero-actions .btn{width:100%}.sds-dashboard-ref .sds-toolbar .form-select,.sds-dashboard-ref .sds-toolbar .form-control,.sds-dashboard-ref .sds-toolbar .btn{width:100%}}
</style>

<div class="sds-dashboard-ref sds-students-page">
    <div class="sds-hero">
        <div>
            <h2><?= $statusFilter === 'nonaktif' ? 'Siswa Non Aktif' : 'Data Peserta Didik' ?></h2>
            <p>Kelola biodata, rombel, status, NIS/NIPD, impor, dan ekspor data siswa.</p>
        </div>
        <div class="sds-hero-actions">
            <a href="../formulir" class="btn btn-primary" target="_blank">Tambah Siswa</a>
            <?php if ($statusFilter === 'nonaktif'): ?>
                <a href="students" class="btn btn-secondary">Semua Siswa</a>
            <?php else: ?>
                <a href="students?status=nonaktif" class="btn btn-danger">Siswa Non Aktif</a>
            <?php endif; ?>
            <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#generateNIPDModal">Generate NIS Kelas X</button>
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#resetNIPDModal">Reset NIS Kelas X</button>
        </div>
    </div>

    <div class="sds-stats">
        <div class="sds-stat-card"><small>Total Data Filter</small><strong><?= number_format((int)$totalData, 0, ',', '.') ?></strong><span>Data sesuai filter aktif</span></div>
        <div class="sds-stat-card"><small>Halaman</small><strong><?= number_format((int)$currentPage, 0, ',', '.') ?>/<?= number_format(max(1, (int)$totalPages), 0, ',', '.') ?></strong><span>Navigasi daftar siswa</span></div>
        <div class="sds-stat-card"><small>Per Halaman</small><strong><?= number_format((int)$limit, 0, ',', '.') ?></strong><span>Jumlah baris tampil</span></div>
        <div class="sds-stat-card"><small>Status Tampilan</small><strong style="font-size:1.1rem;"><?= $statusFilter === 'nonaktif' ? 'Non Aktif' : 'Aktif/Semua' ?></strong><span>Mode data siswa</span></div>
    </div>

    <div class="sds-card">
        <div class="sds-card-header">
            <h5>Daftar Peserta Didik</h5>
            <span class="sds-mini"><?= number_format((int)$totalData, 0, ',', '.') ?> data</span>
        </div>

        <?php if (isset($_GET['generate']) && $_GET['generate'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-message">Generate NIS/NIPD kelas X tahun ajaran aktif berhasil dijalankan.</div>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['reset_nipd']) && $_GET['reset_nipd'] === 'success'): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-message">NIS/NIPD kelas X tahun ajaran aktif berhasil direset.</div>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-message"><?= $_SESSION['error'] ?></div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <div class="alert-message"><?= $_SESSION['success'] ?></div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="sds-card-body">
            <div class="sds-toolbar">
                <div class="d-flex flex-wrap gap-2">
                    <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-success">Ekspor Excel</a>
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importStudentsModal">Impor</button>
                </div>
                <form action="students" method="get" class="d-flex align-items-center gap-2 flex-wrap justify-content-end">
                    <?php if ($statusActive): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
                    <?php endif; ?>
                    <div>
                        <label class="form-label mb-1 small text-muted d-none">Pencarian</label>
                        <input type="text" class="form-control" name="search" placeholder="Ketik Nama, NISN, NIS..." value="<?= htmlspecialchars($search ?? '') ?>" style="min-width:220px">
                    </div>
                    <div>
                        <label class="form-label mb-1 small text-muted d-none">Tahun</label>
                        <select name="tahun" class="form-select">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($tahunList as $th): ?>
                                <option value="<?= htmlspecialchars($th['tahun_ajaran']) ?>" <?= ($filterTahun ?? '') == $th['tahun_ajaran'] ? 'selected' : '' ?>><?= htmlspecialchars($th['tahun_ajaran']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label mb-1 small text-muted d-none">Tingkat</label>
                        <select name="tingkat" class="form-select">
                            <option value="">Semua Tingkat</option>
                            <?php foreach ($tingkatList as $tg): ?>
                                <option value="<?= (int)$tg['id'] ?>" <?= $tingkatActive && (int)$filterTingkat === (int)$tg['id'] ? 'selected' : '' ?>><?= htmlspecialchars($tg['nama_tingkat']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label mb-1 small text-muted d-none">Kelas</label>
                        <select name="kelas" class="form-select">
                            <option value="">Semua Kelas</option>
                            <?php foreach ($kelasList as $kl): ?>
                                <option value="<?= htmlspecialchars($kl['nama_kelas']) ?>" <?= ($filterKelas ?? '') == $kl['nama_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($kl['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="form-label mb-1 small text-muted d-none">Asal Sekolah</label>
                        <select name="asal_sekolah" class="form-select" style="width:180px">
                            <option value="">Semua Asal Sekolah</option>
                            <?php foreach ($asalSekolahList as $asal): ?>
                                <?php $asalNama = trim($asal['sekolah_asal'] ?? ''); ?>
                                <?php if ($asalNama === '') continue; ?>
                                <option value="<?= htmlspecialchars($asalNama) ?>" <?= $asalSekolahActive && $filterAsalSekolah === $asalNama ? 'selected' : '' ?>><?= htmlspecialchars($asalNama) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">Tampilkan</button>
                    <?php if ($searchActive || $tahunActive || $tingkatActive || $kelasActive || $asalSekolahActive || $statusActive): ?>
                        <a href="students" class="btn btn-danger">Reset</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($searchActive || $tahunActive || $tingkatActive || $kelasActive || $asalSekolahActive || $statusActive): ?>
                <div class="mb-2">
                    <strong class="me-2 small text-muted">Filter aktif:</strong>
                    <?php if ($searchActive): ?><span class="sds-filter-chip">Nama/NIS: <?= htmlspecialchars($search) ?> <a href="<?= buildUrlWithout('search') ?>">×</a></span><?php endif; ?>
                    <?php if ($tahunActive): ?><span class="sds-filter-chip">Tahun: <?= htmlspecialchars($filterTahun) ?> <a href="<?= buildUrlWithout('tahun') ?>">×</a></span><?php endif; ?>
                    <?php if ($tingkatActive): ?><span class="sds-filter-chip">Tingkat: <?= htmlspecialchars($filterTingkat) ?> <a href="<?= buildUrlWithout('tingkat') ?>">×</a></span><?php endif; ?>
                    <?php if ($kelasActive): ?><span class="sds-filter-chip">Kelas: <?= htmlspecialchars($filterKelas) ?> <a href="<?= buildUrlWithout('kelas') ?>">×</a></span><?php endif; ?>
                    <?php if ($asalSekolahActive): ?><span class="sds-filter-chip">Asal Sekolah: <?= htmlspecialchars($filterAsalSekolah) ?> <a href="<?= buildUrlWithout('asal_sekolah') ?>">×</a></span><?php endif; ?>
                    <?php if ($statusActive): ?><span class="sds-filter-chip">Status: <?= htmlspecialchars($statusFilter) ?> <a href="<?= buildUrlWithout('status') ?>">×</a></span><?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="sds-table-wrap">
                <table class="sds-table">
                    <thead>
                        <tr>
                            <th style="width:48px;">#</th>
                            <th class="text-center">Foto</th>
                            <th>Nama Peserta Didik</th>
                            <th>NISN</th>
                            <th>NIPD</th>
                            <th>Tingkat</th>
                            <th>Rombel</th>
                            <th class="text-center">Status</th>
                            <?php if ($statusFilter === 'nonaktif'): ?><th>Alasan Non Aktif</th><?php endif; ?>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0):
                            $no = $offset + 1;
                            while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="text-center">
                                        <?php if ($row['foto']): ?>
                                            <a href="../uploads/<?= htmlspecialchars($row['foto']) ?>" data-lightbox="foto-<?= htmlspecialchars($row['nisn']) ?>">
                                                <img src="../uploads/<?= htmlspecialchars($row['foto']) ?>" class="sds-photo" alt="Foto">
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="student_view?id=<?= (int)$row['id'] ?>"><strong><?= htmlspecialchars($row['nama_lengkap']) ?></strong></a>
                                        <?php if (!empty($row['sekolah_asal'])): ?>
                                            <div class="sds-mini">Asal: <?= htmlspecialchars($row['sekolah_asal']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="sds-code"><?= htmlspecialchars($row['nisn'] ?: '-') ?></span></td>
                                    <td><span class="sds-code"><?= htmlspecialchars($row['nipd'] ?: '-') ?></span></td>
                                    <td><?= htmlspecialchars($row['nama_tingkat']) ?></td>
                                    <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                    <td class="text-center">
                                        <label class="switch mb-0">
                                            <input type="checkbox" onchange="toggleStatus(this, <?= (int)$row['id'] ?>)" <?= $row['status_aktif'] ? 'checked' : '' ?>>
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <?php if ($statusFilter === 'nonaktif'): ?><td><?= htmlspecialchars($row['alasan_nonaktif'] ?: '-') ?></td><?php endif; ?>
                                    <td>
                                        <div class="sds-actions">
                                            <a href="student_view?id=<?= (int)$row['id'] ?>" title="Lihat Data" class="btn btn-primary btn-sm">Lihat</a>
                                            <form method="post" action="student_delete" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus data ini?')"><input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>"><input type="hidden" name="id" value="<?= (int)$row['id'] ?>"><button type="submit" title="Hapus Data" class="btn btn-danger btn-sm">Hapus</button></form>
                                            <a href="student_pdf.php?id=<?= (int)$row['id'] ?>" class="btn btn-success btn-sm">PDF</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr><td colspan="<?= $statusFilter === 'nonaktif' ? 10 : 9 ?>" class="text-center text-muted py-4">Tidak ada data siswa.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                <small class="text-muted">
                    Menampilkan <?= $totalData > 0 ? number_format($offset + 1, 0, ',', '.') : 0 ?>–<?= number_format(min($offset + $limit, $totalData), 0, ',', '.') ?> dari <?= number_format((int)$totalData, 0, ',', '.') ?> data
                </small>
                <nav aria-label="Page navigation">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $baseQuery = $_GET;
                        unset($baseQuery['halaman']);
                        $baseUrl = 'students';
                        $range = 2;
                        ?>
                        <?php if ($currentPage > 1): ?>
                            <li class="page-item"><a class="page-link" href="<?= $baseUrl . '?' . http_build_query(array_merge($baseQuery, ['halaman' => $currentPage - 1])) ?>">&laquo;</a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">&laquo;</span></li>
                        <?php endif; ?>
                        <?php
                        $startPage = max(1, $currentPage - $range);
                        $endPage = min($totalPages, $currentPage + $range);
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <?php if ($i == $currentPage): ?>
                                <li class="page-item active"><span class="page-link"><?= $i ?></span></li>
                            <?php else: ?>
                                <li class="page-item"><a class="page-link" href="<?= $baseUrl . '?' . http_build_query(array_merge($baseQuery, ['halaman' => $i])) ?>"><?= $i ?></a></li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <li class="page-item"><a class="page-link" href="<?= $baseUrl . '?' . http_build_query(array_merge($baseQuery, ['halaman' => $currentPage + 1])) ?>">&raquo;</a></li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">&raquo;</span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Modal Generate NIS/NIPD -->

<div class="modal fade" id="generateNIPDModal" tabindex="-1" aria-labelledby="generateNIPDModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index?page=generate_nipd" method="POST" class="modal-content" onsubmit="return confirm('Generate NIS/NIPD hanya untuk siswa aktif kelas X tahun ajaran aktif. Lanjutkan?')">
            <div class="modal-header">
                <h5 class="modal-title" id="generateNIPDModalLabel">Generate NIS/NIPD Kelas X</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3 d-block p-2">
                    Generate hanya berlaku untuk <strong>siswa aktif kelas X</strong> pada tahun ajaran aktif:
                    <strong><?= htmlspecialchars($tahunAjaran) ?></strong>.<br>
                    Siswa kelas XI/XII, siswa nonaktif, tahun ajaran lama, dan NIS/NIPD yang sudah terisi tidak akan disentuh.
                </div>

                <div class="mb-3">
                    <label for="nomor_awal_nipd" class="form-label">Nomor Awal Global</label>
                    <input type="number" class="form-control" id="nomor_awal_nipd" name="nomor_awal" value="16712" min="1" required>
                    <div class="form-text">
                        Ini bagian depan NIS/NIPD. Contoh: <strong>16712</strong>/1303.8.2.1. Nomor ini lanjut global dan tidak reset per jurusan.
                    </div>
                </div>

                <div class="small text-muted">
                    Format hasil: <strong>nomor_global/nomor_tengah.kode_jurusan</strong>.<br>
                    Contoh MP: <strong>16721/1303.8.2.1</strong>, dengan <strong>8.2.1</strong> berasal dari Kode Jurusan/Spektrum di menu Jurusan.<br>
                    Nomor tengah seperti <strong>1303</strong> diatur per jurusan melalui tombol <strong>Set NIPD</strong> pada halaman Jurusan.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Generate NIS/NIPD</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reset NIS/NIPD -->
<div class="modal fade" id="resetNIPDModal" tabindex="-1" aria-labelledby="resetNIPDModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="index?page=reset_nipd" method="POST" class="modal-content" onsubmit="return confirm('Reset NIS/NIPD hanya untuk siswa aktif kelas X tahun ajaran aktif. Data kelas XI/XII dan tahun lama tidak disentuh. Lanjutkan?')">
            <div class="modal-header">
                <h5 class="modal-title" id="resetNIPDModalLabel">Reset NIS/NIPD Kelas X</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning mb-0">
                    Reset hanya mengosongkan NIS/NIPD <strong>siswa aktif kelas X</strong> pada tahun ajaran aktif:
                    <strong><?= htmlspecialchars($tahunAjaran) ?></strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger">Reset NIS/NIPD Kelas X</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Input Alasan -->
<div id="modalNonAktif" style="display:none; position:fixed; top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);align-items:center;justify-content:center;">
    <div style="background:#fff;padding:20px;border-radius:8px;width:400px;">
        <h3>Alasan Menonaktifkan Siswa</h3>
        <form id="formNonAktif">
            <input type="hidden" name="siswa_id" id="modalSiswaId" value="52">
            <textarea name="alasan" id="alasan" rows="4" style="width:100%;padding: 10px;" required=""></textarea>
            <div style="margin-top:10px;text-align:right;">
                <button type="button" onclick="closeModal()" class="btn-warning">Batal</button>
                <button type="submit" class="btn-success">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Import Peserta Didik -->
<div class="modal fade" id="importStudentsModal" tabindex="-1" aria-labelledby="importStudentsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form action="index?page=students_import" method="POST" enctype="multipart/form-data" class="modal-content">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(sds_csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-1" id="importStudentsModalLabel">Import Peserta Didik</h5>
          <div class="small text-muted">Tambah atau perbarui data berdasarkan NISN.</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body">
        <a href="index?page=students_import_template" class="btn btn-outline-success w-100 mb-3">
          <i class="align-middle me-1" data-feather="download"></i>
          Download Template Excel
        </a>

        <div class="mb-2">
          <label for="studentImportExcel" class="form-label">Pilih file Excel</label>
          <input
            type="file"
            id="studentImportExcel"
            name="excel"
            class="form-control"
            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            required
          >
        </div>

        <div class="form-text">
          Gunakan file <strong>.xlsx</strong> dari template. NISN yang sudah ada akan diperbarui, sedangkan NISN baru akan ditambahkan.
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary" onclick="return confirm('Mulai import peserta didik dari file Excel ini?')">
          <i class="align-middle me-1" data-feather="upload"></i>
          Import Data
        </button>
      </div>
    </form>
  </div>
</div>

<script>
    const studentStatusCsrf = <?= json_encode(sds_csrf_token()) ?>;
    function toggleStatus(checkbox, siswaId) {
        if (!checkbox.checked) {
            // Jika dimatikan, tampilkan popup alasan
            document.getElementById('modalNonAktif').style.display = 'flex';
            document.getElementById('modalSiswaId').value = siswaId;
            checkbox.checked = true; // kembalikan dulu
        } else {
            // Jika diaktifkan ulang, langsung simpan
            fetch('index?page=update_status_siswa', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `siswa_id=${siswaId}&status=1&csrf=${encodeURIComponent(studentStatusCsrf)}`
            }).then(() => location.reload());
        }
    }

    function closeModal() {
        document.getElementById('modalNonAktif').style.display = 'none';
    }

    document.getElementById('formNonAktif').onsubmit = function(e) {
        e.preventDefault();
        const siswaId = document.getElementById('modalSiswaId').value;
        const alasan = document.getElementById('alasan').value;

        fetch('index?page=update_status_siswa', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: `siswa_id=${siswaId}&status=0&alasan=${encodeURIComponent(alasan)}&csrf=${encodeURIComponent(studentStatusCsrf)}`
        }).then(() => location.reload());
    }
</script>
