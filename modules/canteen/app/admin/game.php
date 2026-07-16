<?php
include 'inc/fungsi.php';
include 'inc/game_helpers.php';
game_admin_role_guard(['admin', 'superadmin']);

$hasProducts = game_admin_table_exists($conn, 'game_products');
$hasTransactions = game_admin_table_exists($conn, 'game_transactions');
$hasBrands = game_admin_table_exists($conn, 'game_brands');

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

$totalProducts = $activeProducts = $inactiveProducts = $totalBrands = 0;
$totalTransactions = $totalSuccess = $totalProcessing = $totalFailed = $totalRefunded = 0;
$omzetSuccess = $modalSuccess = $labaSuccess = 0;
$recentTransactions = [];
$topBrands = [];
$lowMarginBrands = [];

if ($hasProducts) {
    $totalProducts    = game_admin_scalar($conn, "SELECT COUNT(*) AS total FROM game_products");
    $activeProducts   = game_admin_scalar($conn, "SELECT COUNT(*) AS total FROM game_products WHERE is_active = 1");
    $inactiveProducts = game_admin_scalar($conn, "SELECT COUNT(*) AS total FROM game_products WHERE is_active = 0");

    if ($hasBrands) {
        $totalBrands = game_admin_scalar($conn, "SELECT COUNT(*) AS total FROM game_brands");
    } else {
        $totalBrands = game_admin_scalar($conn, "SELECT COUNT(DISTINCT brand) AS total FROM game_products");
    }

    if ($hasBrands) {
        $qMargin = mysqli_query($conn, "
            SELECT
                gp.brand,
                COUNT(*) AS total,
                MIN(gp.profit) AS min_profit,
                MAX(gp.profit) AS max_profit,
                MAX(gb.logo) AS logo
            FROM game_products gp
            LEFT JOIN game_brands gb ON gp.brand_id = gb.id
            GROUP BY gp.brand
            ORDER BY min_profit ASC, gp.brand ASC
            LIMIT 8
        ");
    } else {
        $qMargin = mysqli_query($conn, "
            SELECT
                brand,
                COUNT(*) AS total,
                MIN(profit) AS min_profit,
                MAX(profit) AS max_profit,
                '' AS logo
            FROM game_products
            GROUP BY brand
            ORDER BY min_profit ASC, brand ASC
            LIMIT 8
        ");
    }

    if ($qMargin) {
        while ($row = mysqli_fetch_assoc($qMargin)) {
            $lowMarginBrands[] = $row;
        }
    }
}

if ($hasTransactions) {
    $totalTransactions = game_admin_scalar($conn, "SELECT COUNT(*) AS total FROM game_transactions");
    $totalSuccess      = game_admin_scalar($conn, "SELECT COUNT(*) AS total FROM game_transactions WHERE status = 'SUCCESS'");
    $totalProcessing   = game_admin_scalar($conn, "SELECT COUNT(*) AS total FROM game_transactions WHERE status = 'PROCESSING'");
    $totalFailed       = game_admin_scalar($conn, "SELECT COUNT(*) AS total FROM game_transactions WHERE status = 'FAILED'");
    $totalRefunded     = game_admin_scalar($conn, "SELECT COUNT(*) AS total FROM game_transactions WHERE status = 'REFUNDED'");
    $omzetSuccess      = game_admin_scalar($conn, "SELECT COALESCE(SUM(price_sell),0) AS total FROM game_transactions WHERE status = 'SUCCESS'");
    $modalSuccess      = game_admin_scalar($conn, "SELECT COALESCE(SUM(price_buy),0) AS total FROM game_transactions WHERE status = 'SUCCESS'");
    $labaSuccess       = game_admin_scalar($conn, "SELECT COALESCE(SUM(profit),0) AS total FROM game_transactions WHERE status = 'SUCCESS'");

    if ($hasBrands && $hasProducts) {
        $qRecent = mysqli_query($conn, "
            SELECT
                gt.id,
                gt.ref_id,
                gt.brand,
                gt.product_name,
                gt.user_id_game,
                gt.zone_id,
                gt.price_sell,
                gt.status,
                gt.created_at,
                MAX(gb.logo) AS logo
            FROM game_transactions gt
            LEFT JOIN game_products gp ON gt.product_id = gp.id
            LEFT JOIN game_brands gb ON gp.brand_id = gb.id
            GROUP BY
                gt.id,
                gt.ref_id,
                gt.brand,
                gt.product_name,
                gt.user_id_game,
                gt.zone_id,
                gt.price_sell,
                gt.status,
                gt.created_at
            ORDER BY gt.id DESC
            LIMIT 10
        ");
    } else {
        $qRecent = mysqli_query($conn, "
            SELECT
                id,
                ref_id,
                brand,
                product_name,
                user_id_game,
                zone_id,
                price_sell,
                status,
                created_at,
                '' AS logo
            FROM game_transactions
            ORDER BY id DESC
            LIMIT 10
        ");
    }

    if ($qRecent) {
        while ($row = mysqli_fetch_assoc($qRecent)) {
            $recentTransactions[] = $row;
        }
    }

    if ($hasBrands && $hasProducts) {
        $qTop = mysqli_query($conn, "
            SELECT
                gt.brand,
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN gt.status='SUCCESS' THEN gt.price_sell ELSE 0 END),0) AS omzet,
                MAX(gb.logo) AS logo
            FROM game_transactions gt
            LEFT JOIN game_products gp ON gt.product_id = gp.id
            LEFT JOIN game_brands gb ON gp.brand_id = gb.id
            GROUP BY gt.brand
            ORDER BY omzet DESC, total DESC
            LIMIT 8
        ");
    } else {
        $qTop = mysqli_query($conn, "
            SELECT
                brand,
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN status='SUCCESS' THEN price_sell ELSE 0 END),0) AS omzet,
                '' AS logo
            FROM game_transactions
            GROUP BY brand
            ORDER BY omzet DESC, total DESC
            LIMIT 8
        ");
    }

    if ($qTop) {
        while ($row = mysqli_fetch_assoc($qTop)) {
            $topBrands[] = $row;
        }
    }
}

include 'inc/header.php';
include 'inc/navbar.php';
?>

<div class="container">
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0 overflow-hidden">
                <div class="card-body p-4" style="background:linear-gradient(135deg,#1d4ed8,#2563eb); color:#fff;">
                    <div class="justify-content-between align-items-lg-center gap-3">
                        <div>
                            <h3 class="mb-2">Pusat Admin Game</h3>
                            <p class="mb-3 opacity-75">Kelola sinkron produk Digiflazz, margin per brand, logo brand, laporan profit, dan transaksi top-up game dari satu panel admin.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="game_sync.php" class="btn btn-light btn-sm fw-semibold">Sinkron Produk</a>
                            <a href="https://member.digiflazz.com/buyer-area/product" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm fw-semibold">Kelola Produk</a>
                            <a href="game_margin.php" class="btn btn-warning btn-sm fw-semibold">Margin Brand</a>
                            <a href="game_brands.php" class="btn btn-info btn-sm fw-semibold">Logo Brand</a>
                            <a href="game_profit.php" class="btn btn-success btn-sm fw-semibold">Profit Game</a>
                            <a href="game_transactions.php" class="btn btn-dark btn-sm fw-semibold">Detail Transaksi</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!$hasProducts): ?>
            <div class="col-12">
                <div class="alert alert-warning shadow-sm border-0">Tabel <strong>game_products</strong> belum ditemukan di database ini. Pastikan modul game sudah terpasang.</div>
            </div>
        <?php endif; ?>
        <?php if (!$hasTransactions): ?>
            <div class="col-12">
                <div class="alert alert-warning shadow-sm border-0">Tabel <strong>game_transactions</strong> belum ditemukan di database ini. Data transaksi game belum bisa ditampilkan.</div>
            </div>
        <?php endif; ?>

        <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Total Produk Game</div><div class="fs-3 fw-bold text-primary"><?= number_format($totalProducts, 0, ',', '.') ?></div></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Produk Aktif</div><div class="fs-3 fw-bold text-success"><?= number_format($activeProducts, 0, ',', '.') ?></div></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Brand Game</div><div class="fs-3 fw-bold text-dark"><?= number_format($totalBrands, 0, ',', '.') ?></div></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Transaksi Game</div><div class="fs-3 fw-bold text-danger"><?= number_format($totalTransactions, 0, ',', '.') ?></div></div></div></div>

        <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Berhasil</div><div class="fs-3 fw-bold text-success"><?= number_format($totalSuccess, 0, ',', '.') ?></div></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Diproses</div><div class="fs-3 fw-bold text-warning"><?= number_format($totalProcessing, 0, ',', '.') ?></div></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Gagal / Refund</div><div class="fs-3 fw-bold text-secondary"><?= number_format($totalFailed + $totalRefunded, 0, ',', '.') ?></div></div></div></div>
        <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Laba Sukses</div><div class="fs-4 fw-bold text-success"><?= game_admin_rupiah($labaSuccess) ?></div></div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <h5 class="mb-3">Ringkasan Keuangan</h5>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Omzet Sukses</span><strong><?= game_admin_rupiah($omzetSuccess) ?></strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Modal Sukses</span><strong><?= game_admin_rupiah($modalSuccess) ?></strong></div>
                    <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Laba Bersih</span><strong class="text-success"><?= game_admin_rupiah($labaSuccess) ?></strong></div>
                    <div class="d-flex justify-content-between pt-2"><span class="text-muted">Produk Nonaktif</span><strong><?= number_format($inactiveProducts, 0, ',', '.') ?></strong></div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Brand Terlaris</h5>
                        <a href="game_profit.php" class="btn btn-outline-primary btn-sm">Detail</a>
                    </div>
                    <?php if (empty($topBrands)): ?>
                        <div class="text-muted">Belum ada transaksi game.</div>
                    <?php else: ?>
                        <?php foreach ($topBrands as $item): ?>
                            <?php $logoUrl = game_brand_logo_admin_url($item['logo'] ?? ''); ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($logoUrl !== ''): ?>
                                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($item['brand']) ?>" style="width:34px;height:34px;object-fit:contain;border-radius:10px;background:#fff;border:1px solid #e5e7eb;padding:4px;">
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($item['brand']) ?></div>
                                        <small class="text-muted"><?= number_format((int)$item['total'], 0, ',', '.') ?> transaksi</small>
                                    </div>
                                </div>
                                <strong><?= game_admin_rupiah((int)$item['omzet']) ?></strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Margin Brand</h5>
                        <a href="game_margin.php" class="btn btn-outline-success btn-sm">Kelola</a>
                    </div>
                    <?php if (empty($lowMarginBrands)): ?>
                        <div class="text-muted">Belum ada data margin produk.</div>
                    <?php else: ?>
                        <?php foreach ($lowMarginBrands as $item): ?>
                            <?php $logoUrl = game_brand_logo_admin_url($item['logo'] ?? ''); ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($logoUrl !== ''): ?>
                                        <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($item['brand']) ?>" style="width:34px;height:34px;object-fit:contain;border-radius:10px;background:#fff;border:1px solid #e5e7eb;padding:4px;">
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($item['brand']) ?></div>
                                        <small class="text-muted"><?= number_format((int)$item['total'], 0, ',', '.') ?> produk</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold"><?= game_admin_rupiah((int)$item['min_profit']) ?></div>
                                    <small class="text-muted">s/d <?= game_admin_rupiah((int)$item['max_profit']) ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">Transaksi Game Terbaru</h5>
                <div class="d-flex gap-2">
                    <span class="badge bg-primary-subtle text-primary-emphasis">10 Terakhir</span>
                    <a href="game_transactions.php" class="btn btn-outline-dark btn-sm">Lihat Semua</a>
                </div>
            </div>
            <?php if (empty($recentTransactions)): ?>
                <div class="text-muted">Belum ada transaksi game.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Ref ID</th>
                                <th>Brand</th>
                                <th>Produk</th>
                                <th>Target</th>
                                <th>Nominal</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentTransactions as $trx): ?>
                            <?php $logoUrl = game_brand_logo_admin_url($trx['logo'] ?? ''); ?>
                            <tr>
                                <td><small><?= htmlspecialchars($trx['ref_id']) ?></small></td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($logoUrl !== ''): ?>
                                            <img src="<?= htmlspecialchars($logoUrl) ?>" alt="<?= htmlspecialchars($trx['brand']) ?>" style="width:32px;height:32px;object-fit:contain;border-radius:8px;background:#fff;border:1px solid #e5e7eb;padding:3px;">
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($trx['brand']) ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($trx['product_name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($trx['user_id_game']) ?>
                                    <?php if (!empty($trx['zone_id'])): ?>
                                        <small class="text-muted d-block">Zone: <?= htmlspecialchars($trx['zone_id']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= game_admin_rupiah((int)$trx['price_sell']) ?></td>
                                <td><?= game_admin_status_badge((string)$trx['status']) ?></td>
                                <td><small><?= htmlspecialchars($trx['created_at']) ?></small></td>
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