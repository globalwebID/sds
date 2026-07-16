<?php
include 'inc/fungsi.php';
include 'inc/game_helpers.php';
game_admin_role_guard(['admin', 'superadmin']);

if (!function_exists('game_brand_logo_admin_url')) {
    function game_brand_logo_admin_url(?string $logo): string
    {
        $logo = trim((string)$logo);
        if ($logo === '') {
            return '';
        }
        if (preg_match('~^https?://~i', $logo)) {
            return $logo;
        }
        return '../' . ltrim($logo, '/');
    }
}

$hasTransactions = game_admin_table_exists($conn, 'game_transactions');
$hasStudents = game_admin_table_exists($conn, 'pendaftaran_siswa');
$hasProducts = game_admin_table_exists($conn, 'game_products');
$hasBrands = game_admin_table_exists($conn, 'game_brands');

$dateFrom = trim((string)($_GET['date_from'] ?? date('Y-m-01')));
$dateTo = trim((string)($_GET['date_to'] ?? date('Y-m-d')));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$brandFilter = trim((string)($_GET['brand'] ?? ''));
$q = trim((string)($_GET['q'] ?? ''));

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) $dateFrom = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) $dateTo = date('Y-m-d');
if ($dateFrom > $dateTo) [$dateFrom, $dateTo] = [$dateTo, $dateFrom];

$statusOptions = ['CREATED', 'PROCESSING', 'SUCCESS', 'FAILED', 'REFUNDED'];
$brands = [];

if ($hasProducts && $hasBrands) {
    $qBrands = mysqli_query($conn, "
        SELECT DISTINCT COALESCE(gb.name, gp.brand) AS brand
        FROM game_products gp
        LEFT JOIN game_brands gb ON gp.brand_id = gb.id
        WHERE COALESCE(gp.brand, '') <> ''
        ORDER BY brand ASC
    ");
    while ($qBrands && $row = mysqli_fetch_assoc($qBrands)) {
        $brands[] = (string)$row['brand'];
    }
} elseif ($hasProducts) {
    $qBrands = mysqli_query($conn, "SELECT DISTINCT brand FROM game_products WHERE COALESCE(brand,'') <> '' ORDER BY brand ASC");
    while ($qBrands && $row = mysqli_fetch_assoc($qBrands)) {
        $brands[] = (string)$row['brand'];
    }
}

if ($hasTransactions && empty($brands)) {
    $qBrands = mysqli_query($conn, "SELECT DISTINCT brand FROM game_transactions WHERE COALESCE(brand,'') <> '' ORDER BY brand ASC");
    while ($qBrands && $row = mysqli_fetch_assoc($qBrands)) {
        $brands[] = (string)$row['brand'];
    }
}

$where = [];
$where[] = "gt.created_at >= '" . mysqli_real_escape_string($conn, $dateFrom . " 00:00:00") . "'";
$where[] = "gt.created_at <= '" . mysqli_real_escape_string($conn, $dateTo . " 23:59:59") . "'";

if ($statusFilter !== '' && in_array($statusFilter, $statusOptions, true)) {
    $where[] = "gt.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}

if ($brandFilter !== '') {
    $where[] = "gt.brand = '" . mysqli_real_escape_string($conn, $brandFilter) . "'";
}

if ($q !== '') {
    $qEsc = mysqli_real_escape_string($conn, $q);
    if ($hasStudents) {
        $where[] = "(gt.ref_id LIKE '%{$qEsc}%' OR gt.user_id_game LIKE '%{$qEsc}%' OR gt.sku_code LIKE '%{$qEsc}%' OR gt.product_name LIKE '%{$qEsc}%' OR ps.nisn LIKE '%{$qEsc}%' OR ps.nama_lengkap LIKE '%{$qEsc}%')";
    } else {
        $where[] = "(gt.ref_id LIKE '%{$qEsc}%' OR gt.user_id_game LIKE '%{$qEsc}%' OR gt.sku_code LIKE '%{$qEsc}%' OR gt.product_name LIKE '%{$qEsc}%')";
    }
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

$summary = [
    'total_transaksi' => 0,
    'total_nominal' => 0,
    'total_success' => 0,
    'total_processing' => 0,
    'total_refunded' => 0,
    'total_profit' => 0,
    'total_modal' => 0
];

$transactions = [];

if ($hasTransactions) {

    $studentJoin = $hasStudents ? "LEFT JOIN pendaftaran_siswa ps ON gt.id_siswa = ps.id" : "";

    $sqlSummary = "
    SELECT
        COUNT(*) AS total_transaksi,

        COALESCE(SUM(gt.price_sell),0) AS total_nominal,

        -- ✅ MODAL hanya SUCCESS
        COALESCE(SUM(CASE WHEN gt.status = 'SUCCESS' THEN gt.price_buy ELSE 0 END),0) AS total_modal,

        -- ✅ PROFIT hanya SUCCESS (refund = 0 otomatis)
        COALESCE(SUM(CASE WHEN gt.status = 'SUCCESS' THEN gt.profit ELSE 0 END),0) AS total_profit,

        -- ✅ REFUND sekarang NOMINAL (Rp)
        COALESCE(SUM(CASE WHEN gt.status = 'REFUNDED' THEN gt.price_sell ELSE 0 END),0) AS total_refunded,

        SUM(CASE WHEN gt.status = 'SUCCESS' THEN 1 ELSE 0 END) AS total_success,
        SUM(CASE WHEN gt.status = 'PROCESSING' THEN 1 ELSE 0 END) AS total_processing

    FROM game_transactions gt
    $studentJoin
    $whereSql
";

    $qSummary = mysqli_query($conn, $sqlSummary);
    if ($qSummary) {
        $summary = array_merge($summary, mysqli_fetch_assoc($qSummary) ?: []);
    }

    $sqlTransactions = "
        SELECT
            gt.id,
            gt.ref_id,
            gt.brand,
            gt.product_name,
            gt.sku_code,
            gt.user_id_game,
            gt.zone_id,
            gt.nickname,
            gt.price_sell,
            gt.price_buy,
            gt.profit,
            gt.status,
            gt.provider_status,
            gt.sn,
            gt.message,
            gt.created_at,
            gt.updated_at,
            " . ($hasStudents ? "ps.nisn," : "'' AS nisn,") . "
            " . ($hasStudents ? "ps.nama_lengkap," : "'' AS nama_lengkap,") . "
            " . ($hasProducts && $hasBrands ? "gb.logo" : "'' AS logo") . "
        FROM game_transactions gt
        " . ($hasStudents ? "LEFT JOIN pendaftaran_siswa ps ON gt.id_siswa = ps.id" : "") . "
        " . ($hasProducts && $hasBrands ? "LEFT JOIN game_products gp ON gt.product_id = gp.id LEFT JOIN game_brands gb ON gp.brand_id = gb.id" : "") . "
        $whereSql
        ORDER BY gt.id DESC
        LIMIT 500
    ";

    $qTransactions = mysqli_query($conn, $sqlTransactions);
    while ($qTransactions && $row = mysqli_fetch_assoc($qTransactions)) {
        $transactions[] = $row;
    }
}

$successRate = $summary['total_transaksi'] > 0 ? ($summary['total_success'] / $summary['total_transaksi']) * 100 : 0;
$rata2 = $summary['total_transaksi'] > 0 ? $summary['total_nominal'] / $summary['total_transaksi'] : 0;

$statusSystem = 'NORMAL';
if ($summary['total_refunded'] > 0) $statusSystem = 'PERLU CEK';
if ($successRate < 50) $statusSystem = 'ERROR TINGGI';

include 'inc/header.php';
include 'inc/navbar.php';
?>

<div class="container">
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="card-body justify-content-between align-items-center gap-3">
                    <div>
                        <h3 class="mb-2">Detail Transaksi Game</h3>
                        <p class="mb-3 text-muted">Menampilkan semua transaksi game dengan filter rentang waktu, status, brand, dan pencarian NISN / Ref ID / User ID Game.</p>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="game.php" class="btn btn-outline-secondary btn-sm fw-semibold">Kembali ke Ringkasan</a>
                        <a href="game_brands.php" class="btn btn-info btn-sm fw-semibold">Logo Brand</a>
                        <a href="game_profit.php" class="btn btn-success btn-sm fw-semibold">Profit Game</a>
                    </div>
                </div>
            </div>
        </div>

        <?php
       $omzetBersih = $summary['total_nominal'] - $summary['total_refunded'];

$cards = [
    ['Total Transaksi', number_format($summary['total_transaksi'],0,',','.')],
    ['Omzet Kotor', game_admin_rupiah((int)$summary['total_nominal'])],
    ['Profit', game_admin_rupiah((int)$summary['total_profit'])],
    ['Modal', game_admin_rupiah((int)$summary['total_modal'])],
    ['Refund', game_admin_rupiah((int)$summary['total_refunded'])],
    ['Omzet Bersih', game_admin_rupiah((int)$omzetBersih)], // 🔥 ganti ini
    ['Success Rate', number_format($successRate,1).'%'],
    ['Status Sistem', $statusSystem],
];
        ?>

        <?php foreach ($cards as $c): ?>
        <div class="col-md-3 col-sm-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="text-muted small mb-2"><?= $c[0] ?></div>
                    <div class="fs-5 fw-bold"><?= $c[1] ?></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Dari Tanggal</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Sampai Tanggal</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="">Semua Status</option>
                        <?php foreach ($statusOptions as $opt): ?>
                            <option value="<?= htmlspecialchars($opt) ?>" <?= $statusFilter === $opt ? 'selected' : '' ?>><?= htmlspecialchars($opt) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-semibold">Brand</label>
                    <select name="brand" class="form-select">
                        <option value="">Semua Brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?= htmlspecialchars($brand) ?>" <?= $brandFilter === $brand ? 'selected' : '' ?>><?= htmlspecialchars($brand) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-semibold">Cari</label>
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="NISN / Ref ID / User ID Game / Nama Siswa">
                </div>
                <div class="col-md-1 d-grid">
                    <button type="submit" class="btn btn-primary fw-semibold">Filter</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Daftar Transaksi Game</h5>
                <span class="badge bg-primary-subtle text-primary-emphasis"><?= number_format(count($transactions), 0, ',', '.') ?> data</span>
            </div>

            <?php if (empty($transactions)): ?>
                <div class="text-muted">Tidak ada transaksi game pada filter yang dipilih.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>NISN</th>
                                <th>Ref ID</th>
                                <th>Siswa</th>
                                <th>Brand</th>
                                <th>Produk</th>
                                <th>Target</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Provider</th>
                                <th>Tanggal</th>
                                <th>Pesan</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($transactions as $trx): ?>
                            <?php $logoUrl = game_brand_logo_admin_url($trx['logo'] ?? ''); ?>
                            <tr>
                                <td><small><?= htmlspecialchars($trx['nisn'] ?: '-') ?></small></td>
                                <td><small><?= htmlspecialchars($trx['ref_id']) ?></small></td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($trx['nama_lengkap'] ?: '-') ?></div>
                                    <?php if (!empty($trx['nickname'])): ?>
                                        <small class="text-muted">Nickname: <?= htmlspecialchars($trx['nickname']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="align-items-center gap-2">
                                        <?php if ($logoUrl !== ''): ?>
                                            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($trx['brand']) ?>" style="width:36px;height:36px;object-fit:contain;border-radius:10px;background:#fff;border:1px solid #e5e7eb;padding:4px;">
                                        <?php endif; ?>
                                        <div><?= htmlspecialchars($trx['brand']) ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($trx['product_name']) ?></div>
                                    <small class="text-muted">SKU: <?= htmlspecialchars($trx['sku_code']) ?></small>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($trx['user_id_game']) ?></div>
                                    <?php if (!empty($trx['zone_id'])): ?>
                                        <small class="text-muted d-block">Zone: <?= htmlspecialchars($trx['zone_id']) ?></small>
                                    <?php endif; ?>
                                    <?php if (!empty($trx['sn'])): ?>
                                        <small class="text-muted d-block">SN: <?= htmlspecialchars($trx['sn']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= game_admin_rupiah((int)$trx['price_sell']) ?></div>
                                    <small class="text-muted">Modal: <?= game_admin_rupiah((int)$trx['price_buy']) ?></small>
                                </td>
                                <td><?= game_admin_status_badge((string)$trx['status']) ?></td>
                                <td><small><?= htmlspecialchars($trx['provider_status'] ?: '-') ?></small></td>
                                <td>
                                    <small class="d-block"><?= htmlspecialchars($trx['created_at']) ?></small>
                                    <?php if (!empty($trx['updated_at']) && $trx['updated_at'] !== $trx['created_at']): ?>
                                        <small class="text-muted d-block">Update: <?= htmlspecialchars($trx['updated_at']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="min-width:220px"><small><?= nl2br(htmlspecialchars($trx['message'] ?: '-')) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inc/footer.php'; ?>