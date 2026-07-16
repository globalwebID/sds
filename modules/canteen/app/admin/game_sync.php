<?php
include 'inc/fungsi.php';
include 'inc/game_helpers.php';
game_admin_role_guard(['admin', 'superadmin']);

$syncUrlDefault = sds_base_url('emoney/api/game_sync_produk.php');
$syncKeyDefault = (string)sds_config('services.digiflazz.sync_key', '');
$digiflazzManageUrl = 'https://member.digiflazz.com/buyer-area/product';
$syncUrl = trim((string)($_POST['sync_url'] ?? $syncUrlDefault));
$syncKey = trim((string)($_POST['sync_key'] ?? $syncKeyDefault));
$result = null;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($syncUrl === '' || $syncKey === '') {
        $errorMsg = 'URL sinkron dan sync key wajib diisi.';
    } else {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        if (!preg_match('~^https?://~i', $syncUrl)) {
            $syncUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $syncUrl;
        }
        $url = $syncUrl . (strpos($syncUrl, '?') !== false ? '&' : '?') . 'key=' . rawurlencode($syncKey);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);
        $raw = curl_exec($ch);
        $curlErr = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            $errorMsg = 'cURL error: ' . $curlErr;
        } else {
            $decoded = json_decode($raw, true);
            $result = [
                'url' => $url,
                'http_code' => $httpCode,
                'raw' => $raw,
                'json' => is_array($decoded) ? $decoded : null,
            ];
        }
    }
}

include 'inc/header.php';
include 'inc/navbar.php';
?>
<div class="container">
    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
        <div class="card-body p-4" style="background:linear-gradient(135deg,#ea580c,#f97316); color:#fff;">
            <div class="justify-content-between align-items-xl-center gap-3">
                <div>
                    <div class="text-uppercase small fw-semibold opacity-75 mb-2">Integrasi Digiflazz</div>
                    <h3 class="mb-2">Sinkron Produk Digiflazz</h3>
                    <p class="mb-3 opacity-75">Jalankan sinkron produk game dari panel admin, buka pengaturan produk Digiflazz, dan pantau hasil respons API dalam satu halaman.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="game.php" class="btn btn-light btn-sm fw-semibold">
                        <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Ringkasan
                    </a>
                    <a href="<?= htmlspecialchars($digiflazzManageUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm fw-semibold">
                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Kelola Produk
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php if ($errorMsg !== ''): ?>
        <div class="alert alert-danger shadow-sm border-0"><i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:52px;height:52px;background:rgba(249,115,22,.15);color:#ea580c;">
                            <i class="fa-solid fa-link fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Endpoint Sinkron</div>
                            <div class="fw-semibold text-break"><?= htmlspecialchars($syncUrlDefault) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:52px;height:52px;background:rgba(37,99,235,.14);color:#2563eb;">
                            <i class="fa-solid fa-repeat fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Jadwal Otomatis</div>
                            <div class="fw-semibold">Setiap 30 menit</div>
                            <small class="text-muted">Disarankan lewat cron job server.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:52px;height:52px;background:rgba(16,185,129,.15);color:#059669;">
                            <i class="fa-solid fa-shield-halved fs-5"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Keamanan</div>
                            <div class="fw-semibold">Gunakan sync key dan role admin</div>
                            <small class="text-muted">Jangan bagikan sync key di luar panel internal.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Form Sinkron</h5>
                        <span class="badge text-bg-warning">Manual Trigger</span>
                    </div>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">URL Endpoint Sinkron</label>
                            <input type="text" class="form-control" name="sync_url" value="<?= htmlspecialchars($syncUrl) ?>" required>
                            <small class="text-muted">Contoh: /sds/emoney/api/game_sync_produk.php</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Sync Key</label>
                            <input type="text" class="form-control" name="sync_key" value="<?= htmlspecialchars($syncKey) ?>" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning fw-semibold">
                                <i class="fa-solid fa-rotate me-1"></i> Jalankan Sinkron Sekarang
                            </button>
                            <a href="<?= htmlspecialchars($digiflazzManageUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark fw-semibold">
                                <i class="fa-solid fa-box-open me-1"></i> Buka Kelola Produk Digiflazz
                            </a>
                        </div>
                    </form>
                    <hr>
                    <div class="small text-muted">
                        <div class="fw-semibold mb-2">Rekomendasi cron setiap 30 menit</div>
                        <code class="d-block bg-light border rounded p-2">*/30 * * * * php /home/USERNAME/public_html/sds/emoney/api/game_sync_produk_cli.php</code>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Hasil Sinkron</h5>
                        <?php if ($result !== null): ?>
                            <span class="badge <?= ($result['http_code'] >= 200 && $result['http_code'] < 300) ? 'text-bg-success' : 'text-bg-danger' ?>">HTTP <?= (int)$result['http_code'] ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if ($result === null): ?>
                        <div class="border rounded-3 p-4 bg-light-subtle text-muted">
                            Belum ada proses sinkron dijalankan dari halaman ini. Gunakan form di samping untuk melakukan sinkron manual.
                        </div>
                    <?php else: ?>
                        <div class="mb-3 small">
                            <div class="mb-1"><strong>URL:</strong> <span class="text-break"><?= htmlspecialchars($result['url']) ?></span></div>
                            <div><strong>HTTP Code:</strong> <?= (int)$result['http_code'] ?></div>
                        </div>
                        <?php if (is_array($result['json'])): ?>
                            <pre class="bg-light border rounded p-3 small mb-0" style="max-height:460px;overflow:auto"><?= htmlspecialchars(json_encode($result['json'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                        <?php else: ?>
                            <pre class="bg-light border rounded p-3 small mb-0" style="max-height:460px;overflow:auto"><?= htmlspecialchars($result['raw']) ?></pre>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'inc/footer.php'; ?>
