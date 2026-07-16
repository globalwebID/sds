<?php
$page = 'anjungan_admin';
require_once __DIR__ . '/partials/anjungan/simpan_anjungan.php';

$anjunganResult = $conn->query("SELECT * FROM `anjungan` ORDER BY `id` ASC LIMIT 1");
$anjungan = $anjunganResult instanceof mysqli_result ? ($anjunganResult->fetch_assoc() ?: []) : [];
$anjunganSettings = sdsAnjunganGetSettings($conn);
$csrfToken = (string)$_SESSION['csrf_anjungan'];

$menuStats = $conn->query("SELECT COUNT(*) total, SUM(status='aktif') aktif FROM `anjungan_menu`")->fetch_assoc() ?: [];
$quickStats = $conn->query("SELECT COUNT(*) total, SUM(status='aktif') aktif FROM `anjungan_topright`")->fetch_assoc() ?: [];
$newsPublishedCondition = sdsAnjunganPublishedCondition($conn);
$newsStats = $conn->query("SELECT COUNT(*) total, SUM(CASE WHEN {$newsPublishedCondition} THEN 1 ELSE 0 END) terbit FROM `anjungan_berita`")->fetch_assoc() ?: [];

$anjunganUrl = $baseUrl . 'anjungan/';
$isActive = (int)($anjungan['aktif'] ?? 1) === 1;
$isMaintenance = (int)($anjunganSettings['maintenance'] ?? 0) === 1;
$statusLabel = $isMaintenance ? 'Pemeliharaan' : ($isActive ? 'Aktif' : 'Nonaktif');
$statusClass = $isMaintenance ? 'warn' : ($isActive ? 'ok' : 'danger');
?>

<?php include __DIR__ . '/partials/shared/master_page_style.php'; ?>
<style>
    .sds-anjungan-page .sds-tabs{display:flex;gap:.35rem;overflow:auto;padding:.65rem;background:#f8f9fa;border:1px solid #dee2e6;border-top:0;white-space:nowrap}
    .sds-anjungan-page .sds-tabs .nav-link{border:1px solid transparent;border-radius:.25rem;color:#495057;font-weight:600;font-size:.84rem;padding:.5rem .75rem}
    .sds-anjungan-page .sds-tabs .nav-link.active{background:#fff;border-color:#dee2e6;color:#0d6efd;box-shadow:none}
    .sds-anjungan-page .tab-content>.tab-pane{padding-top:1rem}
    .sds-anjungan-page .sds-preview-box{border:1px solid #dee2e6;background:#f8f9fa;min-height:180px;display:flex;align-items:center;justify-content:center;overflow:hidden}
    .sds-anjungan-page .sds-preview-box img{display:block;width:100%;max-height:320px;object-fit:cover}
    .sds-anjungan-page .sds-icon-preview{width:44px;height:44px;object-fit:contain;border:1px solid #dee2e6;background:#fff;padding:4px}
    .sds-anjungan-page .sds-thumb{width:70px;height:48px;object-fit:cover;border:1px solid #dee2e6;background:#f8f9fa}
    .sds-anjungan-page .sds-section-note{background:#f8f9fa;border-left:3px solid #0d6efd;padding:.75rem .9rem;font-size:.82rem;color:#495057}
    .sds-anjungan-page .sds-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:1rem}
    .sds-anjungan-page .sds-form-grid .full{grid-column:1/-1}
    .sds-anjungan-page .sds-switch-row{display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;padding:.8rem 0;border-bottom:1px solid #edf1f5}
    .sds-anjungan-page .sds-switch-row:last-child{border-bottom:0}
    .sds-anjungan-page .sds-switch-row strong{display:block;color:#334151}
    .sds-anjungan-page .sds-switch-row small{display:block;color:#6c757d;margin-top:.15rem}
    .sds-anjungan-page .sds-url{max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block}
    .sds-anjungan-page .sds-table-filter{min-width:240px}
    .sds-anjungan-page .sds-card+.sds-card{margin-top:1rem}
    .sds-preview-modal .modal-dialog{max-width:calc(100vw - 2rem);height:calc(100vh - 2rem);margin:1rem auto}
    .sds-preview-modal .modal-content{height:100%;border-radius:0}
    .sds-preview-modal .modal-body{padding:0;overflow:hidden}
    .sds-preview-modal iframe{width:100%;height:100%;border:0;background:#f8f9fa}
    @media(max-width:800px){
        .sds-anjungan-page .sds-form-grid{grid-template-columns:1fr}
        .sds-anjungan-page .sds-form-grid .full{grid-column:auto}
        .sds-anjungan-page .sds-table-filter{width:100%;min-width:0}
    }
</style>

<div class="sds-master-page sds-anjungan-page">
    <div class="sds-hero">
        <div>
            <h2>Pengaturan Anjungan</h2>
            <p>Kelola tampilan, layanan, akses cepat, berita, dan perilaku perangkat Anjungan dari satu halaman.</p>
        </div>
        <div class="sds-hero-actions">
            <button type="button" class="btn btn-secondary" id="btnPreviewAnjungan">Preview</button>
            <a href="<?= htmlspecialchars($anjunganUrl) ?>" class="btn btn-primary" target="_blank" rel="noopener">Buka Anjungan</a>
        </div>
    </div>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            <?= htmlspecialchars((string)$_SESSION['error']) ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible" role="alert">
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
            <?= htmlspecialchars((string)$_SESSION['success']) ?>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <div class="sds-stats">
        <div class="sds-stat-card">
            <small>Status Anjungan</small>
            <strong style="font-size:1.1rem;margin-top:.55rem"><span class="sds-badge <?= $statusClass ?>"><?= $statusLabel ?></span></strong>
            <span><?= $isMaintenance ? 'Pengunjung melihat halaman pemeliharaan' : ($isActive ? 'Dapat diakses oleh pengunjung' : 'Akses publik sedang ditutup') ?></span>
        </div>
        <div class="sds-stat-card">
            <small>Menu Layanan</small>
            <strong><?= (int)($menuStats['aktif'] ?? 0) ?></strong>
            <span>dari <?= (int)($menuStats['total'] ?? 0) ?> menu aktif</span>
        </div>
        <div class="sds-stat-card">
            <small>Akses Cepat</small>
            <strong><?= (int)($quickStats['aktif'] ?? 0) ?></strong>
            <span>dari <?= (int)($quickStats['total'] ?? 0) ?> akses aktif</span>
        </div>
        <div class="sds-stat-card">
            <small>Konten Tayang</small>
            <strong><?= (int)($newsStats['terbit'] ?? 0) ?></strong>
            <span>dari <?= (int)($newsStats['total'] ?? 0) ?> berita dan pengumuman</span>
        </div>
    </div>

    <ul class="nav sds-tabs" id="anjunganTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tampilan" type="button">Tampilan Utama</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#layanan" type="button">Menu Layanan</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#akses" type="button">Akses Cepat</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#berita" type="button">Berita &amp; Pengumuman</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#operasional" type="button">Operasional</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tampilan" role="tabpanel">
            <?php include __DIR__ . '/partials/anjungan/nama_anjungan.php'; ?>
        </div>
        <div class="tab-pane fade" id="layanan" role="tabpanel">
            <?php include __DIR__ . '/partials/anjungan/menu_anjungan.php'; ?>
        </div>
        <div class="tab-pane fade" id="akses" role="tabpanel">
            <?php include __DIR__ . '/partials/anjungan/menu_atas.php'; ?>
        </div>
        <div class="tab-pane fade" id="berita" role="tabpanel">
            <?php include __DIR__ . '/partials/anjungan/berita.php'; ?>
        </div>
        <div class="tab-pane fade" id="operasional" role="tabpanel">
            <?php include __DIR__ . '/partials/anjungan/operasional.php'; ?>
        </div>
    </div>
</div>

<div class="modal fade sds-preview-modal" id="modalPreviewAnjungan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Preview Anjungan</h5>
                    <small class="text-muted">Muat ulang preview setelah menyimpan perubahan.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <iframe id="anjunganPreviewFrame" data-src="<?= htmlspecialchars($anjunganUrl) ?>" title="Preview Anjungan"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    function showTabFromHash() {
        const hash = window.location.hash;
        if (!hash) return;
        const trigger = document.querySelector('#anjunganTabs [data-bs-target="' + hash + '"]');
        if (trigger && window.bootstrap && typeof window.bootstrap.Tab === 'function') {
            new window.bootstrap.Tab(trigger).show();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        showTabFromHash();

        document.querySelectorAll('#anjunganTabs [data-bs-toggle="tab"]').forEach(function (button) {
            button.addEventListener('shown.bs.tab', function () {
                const target = button.getAttribute('data-bs-target');
                if (target) history.replaceState(null, '', target);
            });
        });

        const previewButton = document.getElementById('btnPreviewAnjungan');
        const previewModal = document.getElementById('modalPreviewAnjungan');
        const previewFrame = document.getElementById('anjunganPreviewFrame');
        if (previewButton && previewModal && previewFrame) {
            previewButton.addEventListener('click', function () {
                previewFrame.src = previewFrame.dataset.src + (previewFrame.dataset.src.indexOf('?') >= 0 ? '&' : '?') + '_preview=' + Date.now();
                if (typeof window.sdsShowModal === 'function') {
                    window.sdsShowModal(previewModal);
                } else if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                    new window.bootstrap.Modal(previewModal).show();
                }
            });
            previewModal.addEventListener('hidden.bs.modal', function () {
                previewFrame.src = 'about:blank';
            });
        }

        document.querySelectorAll('[data-table-search]').forEach(function (input) {
            const selector = input.getAttribute('data-table-search');
            const table = document.querySelector(selector);
            if (!table) return;
            input.addEventListener('input', function () {
                const keyword = input.value.trim().toLowerCase();
                table.querySelectorAll('tbody tr[data-search-row]').forEach(function (row) {
                    row.style.display = row.textContent.toLowerCase().indexOf(keyword) >= 0 ? '' : 'none';
                });
            });
        });
    });
})();
</script>
