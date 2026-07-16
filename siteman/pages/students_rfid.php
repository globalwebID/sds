<?php
$page = 'students_rfid';
require_once __DIR__ . '/../../config/perpus.php';
sds_perpus_ensure_schema($conn);

$filterTahun = trim((string)($_GET['tahun'] ?? ''));
$filterKelas = trim((string)($_GET['kelas'] ?? ''));
$filterKartu = trim((string)($_GET['kartu'] ?? ''));
$filterStatus = trim((string)($_GET['status'] ?? ''));
$search = trim((string)($_GET['search'] ?? ''));

$filterKartu = in_array($filterKartu, ['terpasang', 'kosong'], true) ? $filterKartu : '';
$filterStatus = in_array($filterStatus, ['aktif', 'nonaktif'], true) ? $filterStatus : '';

$allowedLimits = [10, 25, 50, 100];
$limit = (int)($_GET['per_page'] ?? 25);
if (!in_array($limit, $allowedLimits, true)) {
    $limit = 25;
}
$currentPage = max(1, (int)($_GET['halaman'] ?? 1));

if (empty($_SESSION['csrf_rfid'])) {
    $_SESSION['csrf_rfid'] = bin2hex(random_bytes(24));
}
$csrfToken = (string)$_SESSION['csrf_rfid'];

function rfidBuildUrlWithout($keys): string
{
    $query = $_GET;
    foreach ((array)$keys as $key) {
        unset($query[$key]);
    }
    unset($query['halaman']);
    return 'students_rfid' . ($query ? '?' . http_build_query($query) : '');
}

function rfidBind(mysqli_stmt $stmt, string $types, array &$params): void
{
    if ($types === '' || !$params) {
        return;
    }
    $refs = [];
    $refs[] = $types;
    foreach ($params as $key => &$value) {
        $refs[] = &$value;
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$baseConditions = [];
$baseParams = [];
$baseTypes = '';

if ($filterTahun !== '') {
    $baseConditions[] = 'k.tahun_ajaran = ?';
    $baseParams[] = $filterTahun;
    $baseTypes .= 's';
}
if ($filterKelas !== '') {
    $baseConditions[] = 'k.nama_kelas = ?';
    $baseParams[] = $filterKelas;
    $baseTypes .= 's';
}
if ($filterStatus === 'aktif') {
    $baseConditions[] = 'ps.status_aktif = 1';
} elseif ($filterStatus === 'nonaktif') {
    $baseConditions[] = 'ps.status_aktif = 0';
}
if ($search !== '') {
    $baseConditions[] = '(ps.nama_lengkap LIKE ? OR ps.nisn LIKE ? OR ps.nipd LIKE ? OR kr.uid LIKE ?)';
    $like = '%' . $search . '%';
    array_push($baseParams, $like, $like, $like, $like);
    $baseTypes .= 'ssss';
}

$baseWhere = $baseConditions ? 'WHERE ' . implode(' AND ', $baseConditions) : 'WHERE 1=1';
$latestClassJoin = "
    LEFT JOIN siswa_kelas sk ON sk.id = (
        SELECT sk2.id
        FROM siswa_kelas sk2
        WHERE sk2.siswa_id = ps.id
        ORDER BY sk2.tahun_ajaran DESC, sk2.id DESC
        LIMIT 1
    )
    LEFT JOIN kelas k ON k.id = sk.kelas_id
    LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    LEFT JOIN kartu_rfid kr ON kr.pemilik_tipe='siswa' AND kr.pemilik_id=ps.id
";

$statsSql = "
    SELECT
        COUNT(*) AS total_scope,
        SUM(CASE WHEN NULLIF(TRIM(kr.uid), '') IS NOT NULL THEN 1 ELSE 0 END) AS total_assigned,
        SUM(CASE WHEN NULLIF(TRIM(kr.uid), '') IS NULL THEN 1 ELSE 0 END) AS total_empty
    FROM pendaftaran_siswa ps
    $latestClassJoin
    $baseWhere
";
$statsStmt = $conn->prepare($statsSql);
$statsParams = $baseParams;
rfidBind($statsStmt, $baseTypes, $statsParams);
$statsStmt->execute();
$stats = $statsStmt->get_result()->fetch_assoc() ?: [];
$statsStmt->close();

$resultConditions = $baseConditions;
$resultParams = $baseParams;
$resultTypes = $baseTypes;
if ($filterKartu === 'terpasang') {
    $resultConditions[] = "NULLIF(TRIM(kr.uid), '') IS NOT NULL";
} elseif ($filterKartu === 'kosong') {
    $resultConditions[] = "NULLIF(TRIM(kr.uid), '') IS NULL";
}
$resultWhere = $resultConditions ? 'WHERE ' . implode(' AND ', $resultConditions) : 'WHERE 1=1';

$countSql = "SELECT COUNT(*) AS total FROM pendaftaran_siswa ps $latestClassJoin $resultWhere";
$countStmt = $conn->prepare($countSql);
$countParams = $resultParams;
rfidBind($countStmt, $resultTypes, $countParams);
$countStmt->execute();
$totalData = (int)(($countStmt->get_result()->fetch_assoc()['total'] ?? 0));
$countStmt->close();

$totalPages = max(1, (int)ceil($totalData / $limit));
if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
}
$offset = ($currentPage - 1) * $limit;

$dataSql = "
    SELECT
        ps.id,
        ps.nama_lengkap,
        ps.foto,
        ps.nisn,
        ps.nipd,
        ps.status_aktif,
        kr.uid AS rfid_uid,
        COALESCE(tk.nama_tingkat, '-') AS nama_tingkat,
        COALESCE(k.nama_kelas, '-') AS nama_kelas,
        COALESCE(k.tahun_ajaran, ps.tahun_ajaran, '-') AS tahun_ajaran
    FROM pendaftaran_siswa ps
    $latestClassJoin
    $resultWhere
    ORDER BY
        CASE WHEN NULLIF(TRIM(kr.uid), '') IS NULL THEN 0 ELSE 1 END ASC,
        ps.nama_lengkap ASC
    LIMIT ? OFFSET ?
";
$dataParams = $resultParams;
$dataParams[] = $limit;
$dataParams[] = $offset;
$dataTypes = $resultTypes . 'ii';
$dataStmt = $conn->prepare($dataSql);
rfidBind($dataStmt, $dataTypes, $dataParams);
$dataStmt->execute();
$result = $dataStmt->get_result();

$tahunList = [];
$tahunResult = $conn->query("SELECT DISTINCT tahun_ajaran FROM kelas WHERE tahun_ajaran IS NOT NULL AND TRIM(tahun_ajaran) <> '' ORDER BY tahun_ajaran DESC");
if ($tahunResult) {
    $tahunList = $tahunResult->fetch_all(MYSQLI_ASSOC);
}

$kelasSql = "SELECT DISTINCT nama_kelas FROM kelas WHERE nama_kelas IS NOT NULL AND TRIM(nama_kelas) <> ''";
$kelasParams = [];
$kelasTypes = '';
if ($filterTahun !== '') {
    $kelasSql .= ' AND tahun_ajaran = ?';
    $kelasParams[] = $filterTahun;
    $kelasTypes = 's';
}
$kelasSql .= ' ORDER BY nama_kelas ASC';
$kelasStmt = $conn->prepare($kelasSql);
rfidBind($kelasStmt, $kelasTypes, $kelasParams);
$kelasStmt->execute();
$kelasList = $kelasStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$kelasStmt->close();

$activeFilters = $search !== '' || $filterTahun !== '' || $filterKelas !== '' || $filterKartu !== '' || $filterStatus !== '';
$returnQuery = $_GET;
unset($returnQuery['halaman']);
$returnQueryString = http_build_query($returnQuery);
?>

<?php include __DIR__ . '/partials/shared/master_page_style.php'; ?>
<style>
    .sds-rfid-page .sds-filter-chip{display:inline-flex;align-items:center;gap:.35rem;background:#f8f9fa;border:1px solid #dee2e6;color:#495057;padding:.25rem .5rem;border-radius:.25rem;font-size:.78rem;margin:.12rem}
    .sds-rfid-page .sds-filter-chip a{color:#dc3545;text-decoration:none;font-weight:700}
    .sds-rfid-page .sds-photo{width:34px;height:40px;object-fit:cover;border:1px solid #dee2e6;background:#f8f9fa}
    .sds-rfid-page .sds-student-name{font-weight:600;color:#334151;text-decoration:none}
    .sds-rfid-page .sds-student-name:hover{text-decoration:underline}
    .sds-rfid-page .sds-code-wrap{display:flex;align-items:center;gap:.35rem;flex-wrap:wrap}
    .sds-rfid-page .sds-card-note{padding:.7rem .9rem;background:#f8f9fa;border-left:3px solid #0d6efd;color:#495057;font-size:.82rem}
    .sds-rfid-page .sds-pagination-row{display:flex;align-items:center;justify-content:space-between;gap:.75rem;flex-wrap:wrap;padding-top:1rem}
    .sds-rfid-page .sds-scan-input{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,"Liberation Mono",monospace;font-size:1.05rem;letter-spacing:.03em}

    /* Toolbar filter Kode Kartu: sejajar pada desktop, tetap responsif pada layar kecil. */
    .sds-rfid-page .sds-toolbar{align-items:center;flex-wrap:nowrap}
    .sds-rfid-page .sds-toolbar-left{flex:0 0 auto;flex-wrap:nowrap;white-space:nowrap}
    .sds-rfid-page .sds-toolbar-left form{flex-wrap:nowrap}
    .sds-rfid-page .sds-toolbar-left .form-select{width:72px}
    .sds-rfid-page .sds-toolbar-right{flex:1 1 auto;min-width:0}
    .sds-rfid-page .sds-rfid-filter-form{
        display:grid!important;
        grid-template-columns:minmax(170px,1.5fr) minmax(105px,.75fr) minmax(115px,.85fr) minmax(120px,.9fr) minmax(135px,1fr) max-content max-content;
        align-items:center;
        gap:.45rem!important;
        width:100%;
        min-width:0;
    }
    .sds-rfid-page .sds-rfid-filter-form>.form-control,
    .sds-rfid-page .sds-rfid-filter-form>.form-select{width:100%!important;min-width:0;margin:0}
    .sds-rfid-page .sds-rfid-filter-form>.btn{width:auto!important;min-width:max-content;margin:0;white-space:nowrap}

    @media(max-width:900px){
        .sds-rfid-page .sds-toolbar{display:block;width:100%}
        .sds-rfid-page .sds-toolbar-left{display:flex;width:100%;margin-bottom:.75rem}
        .sds-rfid-page .sds-toolbar-right{display:block;width:100%}
        .sds-rfid-page .sds-rfid-filter-form{grid-template-columns:repeat(2,minmax(0,1fr))}
        .sds-rfid-page .sds-rfid-filter-form>.sds-search{grid-column:1/-1}
        .sds-rfid-page .sds-rfid-filter-form>.btn{width:100%!important}
    }
    @media(max-width:700px){
        .sds-rfid-page .sds-rfid-filter-form{display:grid!important;grid-template-columns:1fr}
        .sds-rfid-page .sds-rfid-filter-form>.sds-search{grid-column:auto}
        .sds-rfid-page .sds-rfid-filter-form>.form-control,
        .sds-rfid-page .sds-rfid-filter-form>.form-select,
        .sds-rfid-page .sds-rfid-filter-form>.btn{margin-top:0!important;width:100%!important}
        .sds-rfid-page .sds-pagination-row{display:block}
        .sds-rfid-page .pagination{margin-top:.75rem}
    }
</style>

<div class="sds-master-page sds-rfid-page">
    <div class="sds-hero">
        <div>
            <h2>Kode Kartu Peserta Didik</h2>
            <p>Pasangkan, perbarui, atau lepaskan UID kartu peserta didik tanpa mengubah data siswa.</p>
        </div>
        <div class="sds-hero-actions">
            <a href="students" class="btn btn-secondary">Data Peserta Didik</a>
            <a href="rfid_history?jenis=siswa" class="btn btn-outline-primary">Riwayat Kartu</a>
            <a href="students_rfid?kartu=kosong<?= $filterTahun !== '' ? '&tahun=' . urlencode($filterTahun) : '' ?>" class="btn btn-primary">Kartu Belum Terpasang</a>
        </div>
    </div>

    <div class="sds-stats">
        <div class="sds-stat-card">
            <small>Total Dalam Cakupan</small>
            <strong><?= number_format((int)($stats['total_scope'] ?? 0), 0, ',', '.') ?></strong>
            <span>Sesuai pencarian, tahun, kelas, dan status</span>
        </div>
        <div class="sds-stat-card">
            <small>Kartu Terpasang</small>
            <strong><?= number_format((int)($stats['total_assigned'] ?? 0), 0, ',', '.') ?></strong>
            <span>Peserta didik sudah memiliki kode kartu</span>
        </div>
        <div class="sds-stat-card">
            <small>Belum Terpasang</small>
            <strong><?= number_format((int)($stats['total_empty'] ?? 0), 0, ',', '.') ?></strong>
            <span>Perlu dipasangkan melalui reader RFID</span>
        </div>
        <div class="sds-stat-card">
            <small>Data Ditampilkan</small>
            <strong><?= number_format($totalData, 0, ',', '.') ?></strong>
            <span>Setelah seluruh filter diterapkan</span>
        </div>
    </div>

    <div class="sds-card">
        <div class="sds-card-header">
            <h5>Daftar Kode Kartu</h5>
            <span class="sds-mini">Halaman <?= number_format($currentPage, 0, ',', '.') ?> dari <?= number_format($totalPages, 0, ',', '.') ?></span>
        </div>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="btn-close" aria-label="Tutup"></button>
                <div class="alert-message"><?= htmlspecialchars((string)$_SESSION['error']) ?></div>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="btn-close" aria-label="Tutup"></button>
                <div class="alert-message"><?= htmlspecialchars((string)$_SESSION['success']) ?></div>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <div class="sds-card-body">
            <div class="sds-card-note mb-3">
                Gunakan kartu fisik yang sebenarnya. Kode tidak dibuat otomatis karena UID harus berasal dari hasil pembacaan kartu RFID.
            </div>

            <div class="sds-toolbar">
                <div class="sds-toolbar-left">
                    <span class="sds-mini">Tampilkan</span>
                    <form action="students_rfid" method="get" class="d-flex align-items-center gap-2">
                        <?php foreach (['search', 'tahun', 'kelas', 'kartu', 'status'] as $key): ?>
                            <?php if (isset($_GET[$key]) && $_GET[$key] !== ''): ?>
                                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars((string)$_GET[$key]) ?>">
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <select name="per_page" class="form-select form-select-sm" onchange="this.form.submit()" aria-label="Jumlah data per halaman">
                            <?php foreach ($allowedLimits as $option): ?>
                                <option value="<?= $option ?>" <?= $limit === $option ? 'selected' : '' ?>><?= $option ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="sds-mini">baris</span>
                    </form>
                </div>
                <div class="sds-toolbar-right">
                    <form action="students_rfid" method="get" class="sds-rfid-filter-form">
                        <input type="hidden" name="per_page" value="<?= $limit ?>">
                        <input type="search" name="search" class="form-control form-control-sm sds-search" placeholder="Nama, NISN, NIPD, atau kode kartu" value="<?= htmlspecialchars($search) ?>">
                        <select name="tahun" class="form-select form-select-sm">
                            <option value="">Semua Tahun</option>
                            <?php foreach ($tahunList as $item): ?>
                                <option value="<?= htmlspecialchars($item['tahun_ajaran']) ?>" <?= $filterTahun === $item['tahun_ajaran'] ? 'selected' : '' ?>><?= htmlspecialchars($item['tahun_ajaran']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="kelas" class="form-select form-select-sm">
                            <option value="">Semua Rombel</option>
                            <?php foreach ($kelasList as $item): ?>
                                <option value="<?= htmlspecialchars($item['nama_kelas']) ?>" <?= $filterKelas === $item['nama_kelas'] ? 'selected' : '' ?>><?= htmlspecialchars($item['nama_kelas']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="kartu" class="form-select form-select-sm">
                            <option value="">Semua Kartu</option>
                            <option value="terpasang" <?= $filterKartu === 'terpasang' ? 'selected' : '' ?>>Sudah Terpasang</option>
                            <option value="kosong" <?= $filterKartu === 'kosong' ? 'selected' : '' ?>>Belum Terpasang</option>
                        </select>
                        <select name="status" class="form-select form-select-sm">
                            <option value="">Semua Status Siswa</option>
                            <option value="aktif" <?= $filterStatus === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                            <option value="nonaktif" <?= $filterStatus === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                        </select>
                        <button type="submit" class="btn btn-success btn-sm">Tampilkan</button>
                        <?php if ($activeFilters): ?><a href="students_rfid" class="btn btn-danger btn-sm">Reset</a><?php endif; ?>
                    </form>
                </div>
            </div>

            <?php if ($activeFilters): ?>
                <div class="mb-2">
                    <strong class="me-2 small text-muted">Filter aktif:</strong>
                    <?php if ($search !== ''): ?><span class="sds-filter-chip">Pencarian: <?= htmlspecialchars($search) ?> <a href="<?= htmlspecialchars(rfidBuildUrlWithout('search')) ?>">×</a></span><?php endif; ?>
                    <?php if ($filterTahun !== ''): ?><span class="sds-filter-chip">Tahun: <?= htmlspecialchars($filterTahun) ?> <a href="<?= htmlspecialchars(rfidBuildUrlWithout('tahun')) ?>">×</a></span><?php endif; ?>
                    <?php if ($filterKelas !== ''): ?><span class="sds-filter-chip">Rombel: <?= htmlspecialchars($filterKelas) ?> <a href="<?= htmlspecialchars(rfidBuildUrlWithout('kelas')) ?>">×</a></span><?php endif; ?>
                    <?php if ($filterKartu !== ''): ?><span class="sds-filter-chip">Kartu: <?= $filterKartu === 'terpasang' ? 'Terpasang' : 'Belum Terpasang' ?> <a href="<?= htmlspecialchars(rfidBuildUrlWithout('kartu')) ?>">×</a></span><?php endif; ?>
                    <?php if ($filterStatus !== ''): ?><span class="sds-filter-chip">Status: <?= ucfirst(htmlspecialchars($filterStatus)) ?> <a href="<?= htmlspecialchars(rfidBuildUrlWithout('status')) ?>">×</a></span><?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="sds-table-wrap">
                <table class="sds-table wide">
                    <thead>
                        <tr>
                            <th style="width:48px">#</th>
                            <th class="text-center">Foto</th>
                            <th>Peserta Didik</th>
                            <th>NISN / NIPD</th>
                            <th>Rombel</th>
                            <th>Tahun Ajaran</th>
                            <th>Kode Kartu</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php $no = $offset + 1; while ($row = $result->fetch_assoc()): ?>
                                <?php $hasCard = trim((string)$row['rfid_uid']) !== ''; ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['foto'])): ?>
                                            <img src="../uploads/<?= htmlspecialchars($row['foto']) ?>" class="sds-photo" alt="Foto <?= htmlspecialchars($row['nama_lengkap']) ?>">
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="sds-student-name" href="student_view?id=<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['nama_lengkap']) ?></a>
                                        <div class="sds-mini"><?= $row['status_aktif'] ? 'Siswa aktif' : 'Siswa nonaktif' ?></div>
                                    </td>
                                    <td>
                                        <div class="sds-code"><?= htmlspecialchars($row['nisn'] ?: '-') ?></div>
                                        <div class="sds-mini mt-1">NIPD: <?= htmlspecialchars($row['nipd'] ?: '-') ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($row['nama_kelas']) ?><div class="sds-mini"><?= htmlspecialchars($row['nama_tingkat']) ?></div></td>
                                    <td><?= htmlspecialchars($row['tahun_ajaran']) ?></td>
                                    <td>
                                        <?php if ($hasCard): ?>
                                            <div class="sds-code-wrap">
                                                <span class="sds-code"><?= htmlspecialchars($row['rfid_uid']) ?></span>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyCardCode(this.dataset.code)" data-code="<?= htmlspecialchars($row['rfid_uid'], ENT_QUOTES) ?>">Salin</button>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">Belum dipasangkan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="sds-badge <?= $hasCard ? 'ok' : 'warn' ?>"><?= $hasCard ? 'Terpasang' : 'Kosong' ?></span>
                                    </td>
                                    <td>
                                        <div class="sds-actions">
                                            <button type="button" class="btn <?= $hasCard ? 'btn-warning' : 'btn-primary' ?> btn-sm js-rfid-manage"
                                                data-id="<?= (int)$row['id'] ?>"
                                                data-name="<?= htmlspecialchars($row['nama_lengkap'], ENT_QUOTES) ?>"
                                                data-nisn="<?= htmlspecialchars($row['nisn'] ?: '-', ENT_QUOTES) ?>"
                                                data-class="<?= htmlspecialchars($row['nama_kelas'], ENT_QUOTES) ?>"
                                                data-card="<?= htmlspecialchars($row['rfid_uid'] ?: '', ENT_QUOTES) ?>">
                                                <?= $hasCard ? 'Edit Kartu' : 'Scan Kartu' ?>
                                            </button>
                                            <a href="student_view?id=<?= (int)$row['id'] ?>" class="btn btn-secondary btn-sm">Lihat</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="9" class="sds-empty">Tidak ada peserta didik yang sesuai dengan filter.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="sds-pagination-row">
                <small class="text-muted">
                    Menampilkan <?= $totalData > 0 ? number_format($offset + 1, 0, ',', '.') : 0 ?>–<?= number_format(min($offset + $limit, $totalData), 0, ',', '.') ?> dari <?= number_format($totalData, 0, ',', '.') ?> data
                </small>
                <nav aria-label="Navigasi halaman kode kartu">
                    <ul class="pagination pagination-sm mb-0">
                        <?php
                        $pageQuery = $_GET;
                        unset($pageQuery['halaman']);
                        $range = 2;
                        ?>
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $currentPage > 1 ? 'students_rfid?' . http_build_query(array_merge($pageQuery, ['halaman' => $currentPage - 1])) : '#' ?>">&laquo;</a>
                        </li>
                        <?php for ($i = max(1, $currentPage - $range); $i <= min($totalPages, $currentPage + $range); $i++): ?>
                            <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                                <a class="page-link" href="students_rfid?<?= htmlspecialchars(http_build_query(array_merge($pageQuery, ['halaman' => $i]))) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= $currentPage < $totalPages ? 'students_rfid?' . http_build_query(array_merge($pageQuery, ['halaman' => $currentPage + 1])) : '#' ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sds-master-modal" id="inputRFIDModal" tabindex="-1" aria-labelledby="inputRFIDModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="students_rfid_input" method="post" class="modal-content" id="rfidCardForm" autocomplete="off">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title" id="inputRFIDModalLabel">Pasangkan Kode Kartu</h5>
                    <div class="sds-mini" id="rfidModalSubtitle">Tempelkan kartu pada reader RFID.</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="siswa_id" id="rfidSiswaId">
                <input type="hidden" name="action" id="rfidAction" value="save">
                <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQueryString) ?>">

                <div class="border p-3 mb-3 bg-light">
                    <strong id="rfidNama">-</strong>
                    <div class="sds-mini mt-1"><span id="rfidNisn">-</span> · <span id="rfidKelas">-</span></div>
                </div>

                <div class="mb-3">
                    <label for="rfid_uid" class="form-label">Kode/UID Kartu</label>
                    <input type="text" class="form-control text-center sds-scan-input" id="rfid_uid" name="rfid_uid" maxlength="50" placeholder="Tempelkan kartu ke reader" required>
                    <div class="form-text">Kursor otomatis ditempatkan pada kolom ini. Reader biasanya mengirim kode lalu menekan Enter.</div>
                </div>

                <div class="alert alert-info sds-toast-ignore mb-0 p-2" id="rfidCurrentInfo">
                    Belum ada kartu yang terpasang pada peserta didik ini.
                </div>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-danger d-none" id="removeRFIDButton">Lepaskan Kartu</button>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="saveRFIDButton">Simpan Kode</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modalElement = document.getElementById('inputRFIDModal');
    const form = document.getElementById('rfidCardForm');
    const idInput = document.getElementById('rfidSiswaId');
    const actionInput = document.getElementById('rfidAction');
    const cardInput = document.getElementById('rfid_uid');
    const nameElement = document.getElementById('rfidNama');
    const nisnElement = document.getElementById('rfidNisn');
    const classElement = document.getElementById('rfidKelas');
    const currentInfo = document.getElementById('rfidCurrentInfo');
    const removeButton = document.getElementById('removeRFIDButton');
    const modalTitle = document.getElementById('inputRFIDModalLabel');

    document.querySelectorAll('.js-rfid-manage').forEach(function (button) {
        button.addEventListener('click', function () {
            const currentCard = String(button.dataset.card || '');
            idInput.value = button.dataset.id || '';
            actionInput.value = 'save';
            nameElement.textContent = button.dataset.name || '-';
            nisnElement.textContent = 'NISN: ' + (button.dataset.nisn || '-');
            classElement.textContent = 'Rombel: ' + (button.dataset.class || '-');
            cardInput.value = currentCard;
            modalTitle.textContent = currentCard ? 'Edit Kode Kartu' : 'Pasangkan Kode Kartu';
            currentInfo.textContent = currentCard
                ? 'Kartu saat ini: ' + currentCard + '. Scan kartu lain untuk menggantinya.'
                : 'Belum ada kartu yang terpasang pada peserta didik ini.';
            removeButton.classList.toggle('d-none', !currentCard);

            if (typeof window.sdsShowModal === 'function') {
                window.sdsShowModal(modalElement);
            } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                new window.bootstrap.Modal(modalElement).show();
            }
            window.setTimeout(function () {
                cardInput.focus();
                cardInput.select();
            }, 350);
        });
    });

    removeButton.addEventListener('click', function () {
        if (!window.confirm('Lepaskan kartu dari peserta didik ini? Data siswa tidak akan dihapus.')) {
            return;
        }
        actionInput.value = 'remove';
        cardInput.required = false;
        form.submit();
    });

    form.addEventListener('submit', function () {
        if (actionInput.value !== 'remove') {
            actionInput.value = 'save';
            cardInput.required = true;
        }
    });

    if (modalElement) {
        modalElement.addEventListener('hidden.bs.modal', function () {
            form.reset();
            actionInput.value = 'save';
            cardInput.required = true;
            removeButton.classList.add('d-none');
        });
    }

    window.copyCardCode = function (code) {
        const value = String(code || '');
        if (!value) return;
        const done = function () {
            if (typeof window.sdsNotify === 'function') window.sdsNotify('Kode kartu berhasil disalin.', 'success');
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(value).then(done).catch(function () {});
            return;
        }
        const temp = document.createElement('textarea');
        temp.value = value;
        temp.style.position = 'fixed';
        temp.style.opacity = '0';
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        temp.remove();
        done();
    };
})();
</script>
