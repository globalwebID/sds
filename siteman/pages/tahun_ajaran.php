<?php
if (($_SESSION['admin_role'] ?? '') !== 'superadmin') {
    http_response_code(403);
    echo '<div class="alert alert-danger">Hanya superadmin.</div>';
    return;
}

if (empty($_SESSION['master_csrf'])) {
    $_SESSION['master_csrf'] = bin2hex(random_bytes(24));
}

$message = '';
$error = '';
$openModal = false;
$adminId = (int)($_SESSION['admin_id'] ?? 0);

try {
    sds_academic_year_ensure_schema($conn);
    $activeForDraft = sds_academic_year_get_active($conn);
    sds_academic_year_maybe_create_next_draft($conn, $activeForDraft);
    @file_put_contents(dirname(__DIR__, 2) . '/storage/academic_year_v1_4.lock', date('c'));
} catch (Throwable $e) {
    $error = 'Struktur Tahun Ajaran belum dapat diperbarui otomatis: ' . $e->getMessage();
}

function ta_fetch_row(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare('SELECT * FROM tahun_ajaran WHERE tahun_ajaran_id=? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function ta_default_dates(string $label): array
{
    $parsed = sds_academic_year_parse_label($label);
    if (!$parsed) {
        return ['', ''];
    }
    return [$parsed['start'] . '-07-01', $parsed['end'] . '-06-30'];
}

$edit = [
    'tahun_ajaran_id' => 0,
    'tahun_ajaran' => '',
    'status' => 'draft',
    'semester_aktif' => 'ganjil',
    'tanggal_mulai' => '',
    'tanggal_selesai' => '',
    'is_active' => 0,
];

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && $error === '') {
    try {
        if (!hash_equals((string)$_SESSION['master_csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Token keamanan tidak valid. Muat ulang halaman lalu coba kembali.');
        }

        $action = trim((string)($_POST['action'] ?? 'save'));
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'create_next') {
            $active = sds_academic_year_get_active($conn);
            $baseLabel = (string)($active['tahun_ajaran'] ?? '');
            if ($baseLabel === '') {
                $latest = $conn->query('SELECT tahun_ajaran FROM tahun_ajaran ORDER BY tahun_ajaran DESC LIMIT 1')->fetch_assoc();
                $baseLabel = (string)($latest['tahun_ajaran'] ?? sds_academic_year_default_label());
            }
            $nextLabel = sds_academic_year_next_label($baseLabel);
            if ($nextLabel === '') {
                throw new RuntimeException('Tahun ajaran dasar tidak valid.');
            }
            [$startDate, $endDate] = ta_default_dates($nextLabel);
            $stmt = $conn->prepare('SELECT tahun_ajaran_id FROM tahun_ajaran WHERE tahun_ajaran=? LIMIT 1');
            $stmt->bind_param('s', $nextLabel);
            $stmt->execute();
            $exists = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($exists) {
                throw new RuntimeException('Draft tahun ajaran ' . $nextLabel . ' sudah tersedia.');
            }
            $stmt = $conn->prepare("INSERT INTO tahun_ajaran (tahun_ajaran,status,semester_aktif,tanggal_mulai,tanggal_selesai,is_active) VALUES (?,'draft','ganjil',?,?,0)");
            $stmt->bind_param('sss', $nextLabel, $startDate, $endDate);
            $stmt->execute();
            $stmt->close();
            $message = 'Draft tahun ajaran ' . $nextLabel . ' berhasil dibuat.';
            if (function_exists('catatLog')) {
                catatLog($conn, $adminId, 'Tambah Tahun Ajaran', 'Membuat draft tahun ajaran ' . $nextLabel);
            }
        } elseif ($action === 'activate') {
            $target = ta_fetch_row($conn, $id);
            if (!$target) {
                throw new RuntimeException('Tahun ajaran tidak ditemukan.');
            }
            if ((string)$target['status'] === 'archived') {
                throw new RuntimeException('Tahun ajaran yang sudah diarsipkan tidak dapat diaktifkan kembali.');
            }
            if ((int)$target['is_active'] === 1) {
                throw new RuntimeException('Tahun ajaran tersebut sudah aktif.');
            }

            $conn->begin_transaction();
            try {
                $current = $conn->query("SELECT tahun_ajaran_id,tahun_ajaran FROM tahun_ajaran WHERE is_active=1 FOR UPDATE")->fetch_assoc();
                if ($current && (int)$current['tahun_ajaran_id'] !== $id) {
                    $stmt = $conn->prepare("UPDATE tahun_ajaran SET is_active=0,status='completed',completed_at=NOW(),completed_by=? WHERE tahun_ajaran_id=?");
                    $currentId = (int)$current['tahun_ajaran_id'];
                    $stmt->bind_param('ii', $adminId, $currentId);
                    $stmt->execute();
                    $stmt->close();
                }
                $conn->query('UPDATE tahun_ajaran SET is_active=0 WHERE tahun_ajaran_id<>' . $id);
                $stmt = $conn->prepare("UPDATE tahun_ajaran SET is_active=1,status='active',activated_at=NOW(),activated_by=?,completed_at=NULL,completed_by=NULL WHERE tahun_ajaran_id=?");
                $stmt->bind_param('ii', $adminId, $id);
                $stmt->execute();
                $stmt->close();
                $conn->commit();
            } catch (Throwable $e) {
                $conn->rollback();
                throw $e;
            }

            $message = 'Tahun ajaran ' . (string)$target['tahun_ajaran'] . ' berhasil diaktifkan. Tahun aktif sebelumnya otomatis diselesaikan.';
            if (function_exists('catatLog')) {
                catatLog($conn, $adminId, 'Aktifkan Tahun Ajaran', 'Mengaktifkan tahun ajaran ' . (string)$target['tahun_ajaran']);
            }
        } elseif ($action === 'archive') {
            $target = ta_fetch_row($conn, $id);
            if (!$target) {
                throw new RuntimeException('Tahun ajaran tidak ditemukan.');
            }
            if ((int)$target['is_active'] === 1 || (string)$target['status'] === 'active') {
                throw new RuntimeException('Tahun ajaran aktif tidak dapat diarsipkan. Aktifkan tahun ajaran berikutnya terlebih dahulu.');
            }
            if ((string)$target['status'] !== 'completed') {
                throw new RuntimeException('Hanya tahun ajaran berstatus Selesai yang dapat diarsipkan.');
            }
            $stmt = $conn->prepare("UPDATE tahun_ajaran SET status='archived',is_active=0 WHERE tahun_ajaran_id=?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $message = 'Tahun ajaran ' . (string)$target['tahun_ajaran'] . ' berhasil diarsipkan.';
            if (function_exists('catatLog')) {
                catatLog($conn, $adminId, 'Arsip Tahun Ajaran', 'Mengarsipkan tahun ajaran ' . (string)$target['tahun_ajaran']);
            }
        } elseif ($action === 'delete') {
            $target = ta_fetch_row($conn, $id);
            if (!$target) {
                throw new RuntimeException('Tahun ajaran tidak ditemukan.');
            }
            if ((string)$target['status'] !== 'draft' || (int)$target['is_active'] === 1) {
                throw new RuntimeException('Hanya tahun ajaran Draft yang belum aktif yang dapat dihapus.');
            }
            $usage = sds_academic_year_usage_summary($conn, (string)$target['tahun_ajaran']);
            if ((int)$usage['total'] > 0) {
                $parts = [];
                foreach ($usage['tables'] as $table => $count) {
                    $parts[] = $table . ' (' . number_format((int)$count, 0, ',', '.') . ')';
                }
                throw new RuntimeException('Tahun ajaran tidak dapat dihapus karena sudah digunakan pada: ' . implode(', ', $parts) . '.');
            }
            $stmt = $conn->prepare('DELETE FROM tahun_ajaran WHERE tahun_ajaran_id=?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $message = 'Draft tahun ajaran ' . (string)$target['tahun_ajaran'] . ' berhasil dihapus.';
            if (function_exists('catatLog')) {
                catatLog($conn, $adminId, 'Hapus Tahun Ajaran', 'Menghapus draft tahun ajaran ' . (string)$target['tahun_ajaran']);
            }
        } else {
            $label = trim((string)($_POST['tahun_ajaran'] ?? ''));
            $semester = strtolower(trim((string)($_POST['semester_aktif'] ?? 'ganjil')));
            $startDate = trim((string)($_POST['tanggal_mulai'] ?? ''));
            $endDate = trim((string)($_POST['tanggal_selesai'] ?? ''));

            $parsed = sds_academic_year_parse_label($label);
            if (!$parsed) {
                throw new RuntimeException('Format tahun ajaran harus seperti 2026/2027 dan tahun kedua harus berurutan.');
            }
            if (!in_array($semester, ['ganjil', 'genap'], true)) {
                throw new RuntimeException('Semester aktif tidak valid.');
            }
            [$defaultStart, $defaultEnd] = ta_default_dates($label);
            $startDate = $startDate !== '' ? $startDate : $defaultStart;
            $endDate = $endDate !== '' ? $endDate : $defaultEnd;
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                throw new RuntimeException('Tanggal mulai dan selesai tidak valid.');
            }
            if ($startDate >= $endDate) {
                throw new RuntimeException('Tanggal selesai harus setelah tanggal mulai.');
            }

            $edit = [
                'tahun_ajaran_id' => $id,
                'tahun_ajaran' => $label,
                'status' => (string)($_POST['current_status'] ?? 'draft'),
                'semester_aktif' => $semester,
                'tanggal_mulai' => $startDate,
                'tanggal_selesai' => $endDate,
                'is_active' => (int)($_POST['current_is_active'] ?? 0),
            ];

            $stmt = $conn->prepare('SELECT tahun_ajaran_id FROM tahun_ajaran WHERE tahun_ajaran=? AND tahun_ajaran_id<>? LIMIT 1');
            $stmt->bind_param('si', $label, $id);
            $stmt->execute();
            $duplicate = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($duplicate) {
                throw new RuntimeException('Tahun ajaran tersebut sudah tersedia.');
            }

            if ($id > 0) {
                $existing = ta_fetch_row($conn, $id);
                if (!$existing) {
                    throw new RuntimeException('Tahun ajaran tidak ditemukan.');
                }
                if ((string)$existing['status'] !== 'draft' && (string)$existing['tahun_ajaran'] !== $label) {
                    throw new RuntimeException('Nama tahun ajaran yang sudah aktif atau selesai tidak boleh diubah.');
                }
                if ((string)$existing['status'] === 'archived') {
                    throw new RuntimeException('Tahun ajaran yang sudah diarsipkan bersifat baca saja.');
                }
                $stmt = $conn->prepare('UPDATE tahun_ajaran SET tahun_ajaran=?,semester_aktif=?,tanggal_mulai=?,tanggal_selesai=? WHERE tahun_ajaran_id=?');
                $stmt->bind_param('ssssi', $label, $semester, $startDate, $endDate, $id);
                $stmt->execute();
                $stmt->close();
                $message = 'Tahun ajaran berhasil diperbarui.';
                if (function_exists('catatLog')) {
                    catatLog($conn, $adminId, 'Edit Tahun Ajaran', 'Memperbarui tahun ajaran ' . $label . ', semester ' . ucfirst($semester));
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO tahun_ajaran (tahun_ajaran,status,semester_aktif,tanggal_mulai,tanggal_selesai,is_active) VALUES (?,'draft',?,?,?,0)");
                $stmt->bind_param('ssss', $label, $semester, $startDate, $endDate);
                $stmt->execute();
                $stmt->close();
                $message = 'Tahun ajaran berhasil ditambahkan sebagai Draft.';
                if (function_exists('catatLog')) {
                    catatLog($conn, $adminId, 'Tambah Tahun Ajaran', 'Menambahkan draft tahun ajaran ' . $label);
                }
            }
            $edit = [
                'tahun_ajaran_id' => 0,
                'tahun_ajaran' => '',
                'status' => 'draft',
                'semester_aktif' => 'ganjil',
                'tanggal_mulai' => '',
                'tanggal_selesai' => '',
                'is_active' => 0,
            ];
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $openModal = in_array((string)($_POST['action'] ?? ''), ['save'], true);
    }
}

$yearRows = [];
if ($error === '' || sds_academic_year_schema_ready($conn)) {
    $result = $conn->query('SELECT * FROM tahun_ajaran ORDER BY tahun_ajaran DESC, tahun_ajaran_id DESC');
    while ($row = $result->fetch_assoc()) {
        $yearRows[] = $row;
    }
}

$activeYear = null;
foreach ($yearRows as $row) {
    if ((int)($row['is_active'] ?? 0) === 1 && (string)($row['status'] ?? '') === 'active') {
        $activeYear = $row;
        break;
    }
}
if (!$activeYear && $yearRows) {
    $activeYear = sds_academic_year_get_active($conn);
}

$totalYears = count($yearRows);
$statusCounts = ['draft' => 0, 'active' => 0, 'completed' => 0, 'archived' => 0];
foreach ($yearRows as $row) {
    $status = (string)($row['status'] ?? 'draft');
    if (array_key_exists($status, $statusCounts)) {
        $statusCounts[$status]++;
    }
}

$currentYear = (string)($activeYear['tahun_ajaran'] ?? '-');
$currentSemester = ucfirst((string)($activeYear['semester_aktif'] ?? '-'));
$totalClassesCurrent = 0;
if ($currentYear !== '-') {
    $stmt = $conn->prepare('SELECT COUNT(*) AS total FROM kelas WHERE tahun_ajaran=?');
    $stmt->bind_param('s', $currentYear);
    $stmt->execute();
    $totalClassesCurrent = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
    $stmt->close();
}

$nextLabel = $currentYear !== '-' ? sds_academic_year_next_label($currentYear) : '';
$nextExists = false;
foreach ($yearRows as $row) {
    if ((string)$row['tahun_ajaran'] === $nextLabel) {
        $nextExists = true;
        break;
    }
}

$statusLabels = [
    'draft' => 'Draft',
    'active' => 'Aktif',
    'completed' => 'Selesai',
    'archived' => 'Arsip',
];

require __DIR__ . '/partials/shared/master_page_style.php';
?>
<style>
    .sds-badge.draft {
        background: #fff3cd;
        color: #8a6300
    }

    .sds-badge.active {
        background: #d1e7dd;
        color: #0f5132
    }

    .sds-badge.completed {
        background: #cff4fc;
        color: #055160
    }

    .sds-badge.archived {
        background: #e2e3e5;
        color: #41464b
    }

    .ta-meta {
        display: flex;
        gap: .45rem;
        flex-wrap: wrap;
        color: #6c757d;
        font-size: .79rem
    }

    .ta-meta span {
        display: inline-flex;
        align-items: center;
        gap: .25rem
    }

    .ta-active-card {
        border: 1px solid #badbcc;
        background: #f2fbf6;
        border-radius: 0px;
        padding: 14px 16px;
        margin-bottom: 0px
    }

    .ta-active-card strong {
        display: block;
        font-size: 1.05rem;
        color: #0f5132
    }

    .ta-readonly-note {
        font-size: .8rem;
        color: #6c757d;
        margin-top: .4rem
    }
</style>
<div class="sds-master-page" id="tahunAjaranPage">
    <div class="sds-hero">
        <div>
            <h2>Tahun Ajaran</h2>
            <p>Satu sumber periode akademik untuk SDS, Absensi, Perpustakaan, E-KBM, dan seluruh laporan.</p>
        </div>
        <div class="sds-hero-actions">
            <?php if ($nextLabel !== '' && !$nextExists): ?>
                <form method="post" class="d-inline" onsubmit="return confirm('Buat <?= htmlspecialchars($nextLabel) ?> sebagai Draft?')">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['master_csrf']) ?>">
                    <button type="submit" name="action" value="create_next" class="btn btn-outline-primary">Buat Tahun Berikutnya</button>
                </form>
            <?php endif; ?>
            <button type="button" class="btn btn-primary" onclick="resetTahunAjaranForm(); showTahunAjaranModal();">Tambah Tahun Ajaran</button>
        </div>
    </div>

    <?php if ($activeYear): ?>
        <div class="ta-active-card">
            <strong>Tahun ajaran aktif: <?= htmlspecialchars($currentYear) ?> · Semester <?= htmlspecialchars($currentSemester) ?></strong>
            <span>Semua input baru dan modul operasional menggunakan periode ini. Mengaktifkan draft lain otomatis menyelesaikan periode aktif sekarang.</span>
        </div>
    <?php endif; ?>

    <div class="sds-stats three">
        <div class="sds-stat-card">
            <small>Tahun Aktif</small>
            <strong style="font-size:1.2rem"><?= htmlspecialchars($currentYear) ?></strong>
            <span>Semester <?= htmlspecialchars($currentSemester) ?></span>
        </div>
        <div class="sds-stat-card">
            <small>Draft Berikutnya</small>
            <strong><?= number_format($statusCounts['draft'], 0, ',', '.') ?></strong>
            <span>Belum dipakai untuk transaksi</span>
        </div>
        <div class="sds-stat-card">
            <small>Rombel Tahun Aktif</small>
            <strong><?= number_format($totalClassesCurrent, 0, ',', '.') ?></strong>
            <span>Selesai/arsip: <?= number_format($statusCounts['completed'] + $statusCounts['archived'], 0, ',', '.') ?></span>
        </div>
    </div>

    <div class="sds-card">
        <div class="sds-card-header">
            <h5>Daftar Tahun Ajaran</h5>
            <span class="sds-mini">Alur status: <strong>Draft → Aktif → Selesai → Arsip</strong></span>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible" role="alert">
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="sds-card-body">
            <div class="sds-toolbar">
                <div class="sds-toolbar-left"><span class="sds-mini">Tahun aktif tidak berubah otomatis hanya karena tanggal server berganti.</span></div>
                <div class="sds-toolbar-right"><input type="search" id="searchTahunAjaran" class="form-control form-control-sm sds-search" placeholder="Cari tahun/status/semester..."></div>
            </div>

            <div class="sds-table-wrap">
                <table class="sds-table" id="tableTahunAjaran">
                    <thead>
                        <tr>
                            <th style="width:60px">No.</th>
                            <th>Tahun Ajaran</th>
                            <th>Periode</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$yearRows): ?>
                            <tr>
                                <td colspan="6" class="sds-empty">Belum ada data tahun ajaran.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($yearRows as $index => $row):
                                $status = (string)($row['status'] ?? 'draft');
                                $isActive = (int)($row['is_active'] ?? 0) === 1;
                                $searchText = strtolower(implode(' ', [(string)$row['tahun_ajaran'], $statusLabels[$status] ?? $status, (string)$row['semester_aktif']]));
                            ?>
                                <tr data-search="<?= htmlspecialchars($searchText) ?>">
                                    <td><?= number_format($index + 1, 0, ',', '.') ?></td>
                                    <td>
                                        <span class="sds-code"><?= htmlspecialchars((string)$row['tahun_ajaran']) ?></span>
                                        <?php if ($isActive): ?><div class="ta-meta"><span>Dipakai seluruh modul</span></div><?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="ta-meta">
                                            <span><?= htmlspecialchars((string)($row['tanggal_mulai'] ?: '-')) ?></span>
                                            <span>s.d.</span>
                                            <span><?= htmlspecialchars((string)($row['tanggal_selesai'] ?: '-')) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars(ucfirst((string)$row['semester_aktif'])) ?></td>
                                    <td><span class="sds-badge <?= htmlspecialchars($status) ?>"><?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status)) ?></span></td>
                                    <td>
                                        <div class="sds-actions">
                                            <?php if ($status === 'draft'): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Aktifkan <?= htmlspecialchars((string)$row['tahun_ajaran']) ?>? Tahun aktif sebelumnya akan otomatis menjadi Selesai.')">
                                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['master_csrf']) ?>">
                                                    <input type="hidden" name="id" value="<?= (int)$row['tahun_ajaran_id'] ?>">
                                                    <button type="submit" name="action" value="activate" class="btn btn-sm btn-success">Aktifkan</button>
                                                </form>
                                            <?php elseif ($status === 'completed'): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Arsipkan tahun ajaran ini? Data historis tetap tersimpan.')">
                                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['master_csrf']) ?>">
                                                    <input type="hidden" name="id" value="<?= (int)$row['tahun_ajaran_id'] ?>">
                                                    <button type="submit" name="action" value="archive" class="btn btn-sm btn-outline-secondary">Arsipkan</button>
                                                </form>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                data-id="<?= (int)$row['tahun_ajaran_id'] ?>"
                                                data-tahun-ajaran="<?= htmlspecialchars((string)$row['tahun_ajaran'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-status="<?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>"
                                                data-semester="<?= htmlspecialchars((string)$row['semester_aktif'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-tanggal-mulai="<?= htmlspecialchars((string)$row['tanggal_mulai'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-tanggal-selesai="<?= htmlspecialchars((string)$row['tanggal_selesai'], ENT_QUOTES, 'UTF-8') ?>"
                                                data-is-active="<?= $isActive ? '1' : '0' ?>"
                                                onclick="openTahunAjaranEdit(this)"><?= $status === 'archived' ? 'Lihat' : 'Edit' ?></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade sds-master-modal" id="modalTahunAjaran" tabindex="-1" aria-labelledby="modalTahunAjaranLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" id="formTahunAjaran">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTahunAjaranLabel">Tambah Tahun Ajaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['master_csrf']) ?>">
                    <input type="hidden" name="id" id="tahunAjaranId" value="0">
                    <input type="hidden" name="current_status" id="tahunAjaranCurrentStatus" value="draft">
                    <input type="hidden" name="current_is_active" id="tahunAjaranCurrentActive" value="0">
                    <div class="mb-3">
                        <label for="tahunAjaranInput" class="form-label">Tahun Ajaran</label>
                        <input type="text" class="form-control" id="tahunAjaranInput" name="tahun_ajaran" placeholder="Contoh: 2027/2028" maxlength="9" pattern="\d{4}/\d{4}" required>
                        <div class="form-text">Tahun ajaran baru selalu disimpan sebagai Draft dan tidak langsung aktif.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6"><label for="tahunAjaranMulai" class="form-label">Tanggal Mulai</label><input type="date" class="form-control" id="tahunAjaranMulai" name="tanggal_mulai" required></div>
                        <div class="col-md-6"><label for="tahunAjaranSelesai" class="form-label">Tanggal Selesai</label><input type="date" class="form-control" id="tahunAjaranSelesai" name="tanggal_selesai" required></div>
                        <div class="col-md-6"><label for="tahunAjaranSemester" class="form-label">Semester Aktif</label><select class="form-select" id="tahunAjaranSemester" name="semester_aktif">
                                <option value="ganjil">Ganjil</option>
                                <option value="genap">Genap</option>
                            </select></div>
                        <div class="col-md-6"><label class="form-label">Status</label><input type="text" class="form-control" id="tahunAjaranStatusLabel" value="Draft" readonly></div>
                    </div>
                    <div class="ta-readonly-note" id="tahunAjaranReadonlyNote"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="action" value="delete" id="deleteTahunAjaranButton" class="btn btn-outline-danger me-auto d-none" formnovalidate onclick="return confirm('Hapus draft ini? Penghapusan ditolak jika sudah dipakai oleh data lain.')">Hapus</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="action" value="save" id="saveTahunAjaranButton" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showTahunAjaranModal() {
        const run = function() {
            const modalElement = document.getElementById('modalTahunAjaran');
            if (modalElement && typeof window.sdsShowModal === 'function') window.sdsShowModal(modalElement);
        };
        if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run, {
            once: true
        });
        else run();
    }

    function getDefaultDates(label) {
        const match = String(label || '').match(/^(\d{4})\/(\d{4})$/);
        if (!match || Number(match[2]) !== Number(match[1]) + 1) return {
            start: '',
            end: ''
        };
        return {
            start: match[1] + '-07-01',
            end: match[2] + '-06-30'
        };
    }

    function applyYearModalMode(status, isActive) {
        const nameInput = document.getElementById('tahunAjaranInput');
        const startInput = document.getElementById('tahunAjaranMulai');
        const endInput = document.getElementById('tahunAjaranSelesai');
        const semesterInput = document.getElementById('tahunAjaranSemester');
        const saveButton = document.getElementById('saveTahunAjaranButton');
        const deleteButton = document.getElementById('deleteTahunAjaranButton');
        const note = document.getElementById('tahunAjaranReadonlyNote');
        const archived = status === 'archived';
        const draft = status === 'draft';

        if (nameInput) nameInput.readOnly = !draft;
        [startInput, endInput, semesterInput].forEach(el => {
            if (el) el.disabled = archived;
        });
        if (saveButton) saveButton.classList.toggle('d-none', archived);
        if (deleteButton) deleteButton.classList.toggle('d-none', !(draft && !isActive));
        if (note) {
            note.textContent = archived ? 'Data arsip bersifat baca saja.' : (!draft ? 'Nama tahun ajaran dikunci karena periode sudah pernah diaktifkan.' : 'Draft dapat diedit atau dihapus selama belum dipakai data lain.');
        }
    }

    function openTahunAjaranEdit(button) {
        const status = button.dataset.status || 'draft';
        const isActive = button.dataset.isActive === '1';
        document.getElementById('tahunAjaranId').value = button.dataset.id || '0';
        document.getElementById('tahunAjaranInput').value = button.dataset.tahunAjaran || '';
        document.getElementById('tahunAjaranMulai').value = button.dataset.tanggalMulai || '';
        document.getElementById('tahunAjaranSelesai').value = button.dataset.tanggalSelesai || '';
        document.getElementById('tahunAjaranSemester').value = button.dataset.semester || 'ganjil';
        document.getElementById('tahunAjaranCurrentStatus').value = status;
        document.getElementById('tahunAjaranCurrentActive').value = isActive ? '1' : '0';
        document.getElementById('tahunAjaranStatusLabel').value = ({
            draft: 'Draft',
            active: 'Aktif',
            completed: 'Selesai',
            archived: 'Arsip'
        })[status] || status;
        document.getElementById('modalTahunAjaranLabel').textContent = status === 'archived' ? 'Detail Tahun Ajaran' : 'Edit Tahun Ajaran';
        applyYearModalMode(status, isActive);
        showTahunAjaranModal();
    }

    function resetTahunAjaranForm() {
        const activeLabel = <?= json_encode($currentYear !== '-' ? $currentYear : sds_academic_year_default_label()) ?>;
        const next = (function(label) {
            const m = String(label).match(/^(\d{4})\/(\d{4})$/);
            return m ? m[2] + '/' + (Number(m[2]) + 1) : '';
        })(activeLabel);
        const dates = getDefaultDates(next);
        document.getElementById('tahunAjaranId').value = '0';
        document.getElementById('tahunAjaranInput').value = next;
        document.getElementById('tahunAjaranMulai').value = dates.start;
        document.getElementById('tahunAjaranSelesai').value = dates.end;
        document.getElementById('tahunAjaranSemester').value = 'ganjil';
        document.getElementById('tahunAjaranCurrentStatus').value = 'draft';
        document.getElementById('tahunAjaranCurrentActive').value = '0';
        document.getElementById('tahunAjaranStatusLabel').value = 'Draft';
        document.getElementById('modalTahunAjaranLabel').textContent = 'Tambah Tahun Ajaran';
        applyYearModalMode('draft', false);
    }

    (function() {
        const search = document.getElementById('searchTahunAjaran');
        if (search) search.addEventListener('input', function() {
            const keyword = this.value.toLowerCase().trim();
            document.querySelectorAll('#tableTahunAjaran tbody tr[data-search]').forEach(row => row.style.display = row.dataset.search.includes(keyword) ? '' : 'none');
        });
        const input = document.getElementById('tahunAjaranInput');
        if (input) input.addEventListener('input', function() {
            if (document.getElementById('tahunAjaranId').value !== '0') return;
            const dates = getDefaultDates(this.value);
            if (dates.start) document.getElementById('tahunAjaranMulai').value = dates.start;
            if (dates.end) document.getElementById('tahunAjaranSelesai').value = dates.end;
        });
        <?php if ($openModal): ?>
            document.addEventListener('DOMContentLoaded', function() {
                resetTahunAjaranForm();
                showTahunAjaranModal();
            }, {
                once: true
            });
        <?php endif; ?>
    })();
</script>