<?php
include 'inc/fungsi.php';
include 'inc/game_helpers.php';
game_admin_role_guard(['admin', 'superadmin']);

$hasTransactions = game_admin_table_exists($conn, 'game_transactions');
$range = (int)($_GET['range'] ?? 30);
if (!in_array($range, [7, 30, 90], true)) $range = 30;
$whereDate = "created_at >= DATE_SUB(NOW(), INTERVAL {$range} DAY)";

$summary = [
    'total_transaksi' => 0,
    'omzet_sukses' => 0,
    'modal_sukses' => 0,
    'laba_sukses' => 0,
    'total_sukses' => 0,
    'total_processing' => 0,
    'total_failed' => 0,
    'total_refunded' => 0,
];
$topBrands = [];
$daily = [];

if ($hasTransactions) {
    $qSummary = mysqli_query($conn, "
        SELECT
          COUNT(*) AS total_transaksi,
          SUM(CASE WHEN status='SUCCESS' THEN price_sell ELSE 0 END) AS omzet_sukses,
          SUM(CASE WHEN status='SUCCESS' THEN price_buy ELSE 0 END) AS modal_sukses,
          SUM(CASE WHEN status='SUCCESS' THEN profit ELSE 0 END) AS laba_sukses,
          SUM(CASE WHEN status='SUCCESS' THEN 1 ELSE 0 END) AS total_sukses,
          SUM(CASE WHEN status='PROCESSING' THEN 1 ELSE 0 END) AS total_processing,
          SUM(CASE WHEN status='FAILED' THEN 1 ELSE 0 END) AS total_failed,
          SUM(CASE WHEN status='REFUNDED' THEN 1 ELSE 0 END) AS total_refunded
        FROM game_transactions
        WHERE {$whereDate}
    ");
    if ($qSummary) {
        $summary = array_merge($summary, mysqli_fetch_assoc($qSummary) ?: []);
    }

    $qBrands = mysqli_query($conn, "
        SELECT brand, COUNT(*) AS total,
               SUM(CASE WHEN status='SUCCESS' THEN price_sell ELSE 0 END) AS omzet,
               SUM(CASE WHEN status='SUCCESS' THEN profit ELSE 0 END) AS laba
        FROM game_transactions
        WHERE {$whereDate}
        GROUP BY brand
        ORDER BY omzet DESC, total DESC
        LIMIT 15
    ");
    if ($qBrands) {
        while ($row = mysqli_fetch_assoc($qBrands)) $topBrands[] = $row;
    }

    $qDaily = mysqli_query($conn, "
        SELECT DATE(created_at) AS tgl,
               SUM(CASE WHEN status='SUCCESS' THEN price_sell ELSE 0 END) AS omzet,
               SUM(CASE WHEN status='SUCCESS' THEN profit ELSE 0 END) AS laba,
               COUNT(*) AS total
        FROM game_transactions
        WHERE {$whereDate}
        GROUP BY DATE(created_at)
        ORDER BY tgl ASC
    ");
    if ($qDaily) {
        while ($row = mysqli_fetch_assoc($qDaily)) $daily[] = $row;
    }
}

include 'inc/header.php';
include 'inc/navbar.php';
?>
<div class="container">
    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-body p-4" style="background:linear-gradient(135deg,#7c3aed,#4f46e5); color:#fff;">
            <div class="justify-content-between align-items-lg-center gap-3">
                <div>
                    <h3 class="mb-2">Profit Top-up Game</h3>
                    <p class="mb-3 opacity-75">Pantau omzet, laba, dan performa brand game berdasarkan periode waktu.</p>
                </div>
                <div class="d-flex flex-wrap gap-2" style="justify-content: space-between;">
                <a href="game.php" class="btn btn-light btn-sm fw-semibold">Kembali ke Ringkasan</a>
                <form method="get" class="d-flex gap-2 align-items-center">
                    <select name="range" class="form-select form-select-sm" style="min-width:120px">
                        <option value="7" <?= $range === 7 ? 'selected' : '' ?>>7 Hari</option>
                        <option value="30" <?= $range === 30 ? 'selected' : '' ?>>30 Hari</option>
                        <option value="90" <?= $range === 90 ? 'selected' : '' ?>>90 Hari</option>
                    </select>
                    <button class="btn btn-light btn-sm fw-semibold">Terapkan</button>
                </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$hasTransactions): ?>
        <div class="alert alert-warning shadow-sm border-0">Tabel <strong>game_transactions</strong> belum ditemukan di database ini.</div>
    <?php else: ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Total Transaksi</div><div class="fs-3 fw-bold text-primary"><?= number_format((int)$summary['total_transaksi'], 0, ',', '.') ?></div></div></div></div>
            <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Omzet Sukses</div><div class="fs-5 fw-bold text-success"><?= game_admin_rupiah((int)$summary['omzet_sukses']) ?></div></div></div></div>
            <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Modal Sukses</div><div class="fs-5 fw-bold text-dark"><?= game_admin_rupiah((int)$summary['modal_sukses']) ?></div></div></div></div>
            <div class="col-md-3 col-sm-6"><div class="card shadow-sm border-0 h-100"><div class="card-body"><div class="text-muted small mb-2">Laba Sukses</div><div class="fs-5 fw-bold text-success"><?= game_admin_rupiah((int)$summary['laba_sukses']) ?></div></div></div></div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="mb-3">Status Transaksi</h5>
                        <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Berhasil</span><strong><?= number_format((int)$summary['total_sukses'], 0, ',', '.') ?></strong></div>
                        <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Diproses</span><strong><?= number_format((int)$summary['total_processing'], 0, ',', '.') ?></strong></div>
                        <div class="d-flex justify-content-between border-bottom py-2"><span class="text-muted">Gagal</span><strong><?= number_format((int)$summary['total_failed'], 0, ',', '.') ?></strong></div>
                        <div class="d-flex justify-content-between pt-2"><span class="text-muted">Refund</span><strong><?= number_format((int)$summary['total_refunded'], 0, ',', '.') ?></strong></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body">
                        <h5 class="mb-3">Top Brand</h5>
                        <?php if (empty($topBrands)): ?>
                            <div class="text-muted">Belum ada data brand untuk periode ini.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light"><tr><th>Brand</th><th>Total</th><th>Omzet</th><th>Laba</th></tr></thead>
                                    <tbody>
                                    <?php foreach ($topBrands as $item): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['brand']) ?></td>
                                            <td><?= number_format((int)$item['total'], 0, ',', '.') ?></td>
                                            <td><?= game_admin_rupiah((int)$item['omzet']) ?></td>
                                            <td><?= game_admin_rupiah((int)$item['laba']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body">
                <h5 class="mb-3">Omzet Harian</h5>
                <?php if (empty($daily)): ?>
                    <div class="text-muted">Belum ada data harian untuk periode ini.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr><th>Tanggal</th><th>Total</th><th>Omzet</th><th>Laba</th></tr></thead>
                            <tbody>
                            <?php foreach ($daily as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['tgl']) ?></td>
                                    <td><?= number_format((int)$item['total'], 0, ',', '.') ?></td>
                                    <td><?= game_admin_rupiah((int)$item['omzet']) ?></td>
                                    <td><?= game_admin_rupiah((int)$item['laba']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php include 'inc/footer.php'; ?>
