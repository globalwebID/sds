<?php
if (!defined('SDS_PERPUSTAKAAN_APP')) { http_response_code(403); exit('Akses langsung tidak diizinkan.'); }
$page = 'cleanup';
require __DIR__ . '/../partials/bootstrap.php';
if (empty($perpusAccess['allowed'])) return;
if (($_SESSION['perpus_user_role'] ?? '') !== 'admin') { http_response_code(403); echo '<div class="alert alert-danger">Hanya admin perpustakaan yang dapat menjalankan pembersihan.</div>'; return; }

$root = dirname(__DIR__, 2);
$targets = [
    $root . '/siteman/pages/perpus_dashboard.php',
    $root . '/siteman/pages/perpus_anggota.php',
    $root . '/siteman/pages/perpus_buku.php',
    $root . '/siteman/pages/perpus_katalog.php',
    $root . '/siteman/pages/perpus_sirkulasi.php',
    $root . '/siteman/pages/perpus_kunjungan.php',
    $root . '/siteman/pages/perpus_laporan.php',
    $root . '/siteman/pages/perpus_pengaturan.php',
    $root . '/siteman/pages/perpus_migrasi.php',
];
$message = '';
$error = '';
$remaining = array_values(array_filter($targets, 'is_file'));
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        perpus_check_csrf();
        $removed = 0;
        foreach ($targets as $target) {
            if (!is_file($target)) continue;
            if (!@unlink($target)) throw new RuntimeException('Tidak dapat menghapus ' . basename($target) . '. Periksa izin file hosting.');
            $removed++;
        }
        $partialDir = $root . '/siteman/pages/partials/perpus';
        if (is_dir($partialDir)) {
            foreach (glob($partialDir . '/*') ?: [] as $file) {
                if (is_file($file)) @unlink($file);
            }
            @rmdir($partialDir);
        }
        $message = $removed > 0 ? $removed . ' file UI Perpustakaan lama berhasil dihapus dari folder siteman.' : 'Tidak ada file struktur lama yang perlu dibersihkan.';
        $remaining = array_values(array_filter($targets, 'is_file'));
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
require __DIR__ . '/../partials/master_page_style.php';
?>
<div class="sds-master-page">
    <div class="sds-hero"><div><h2>Pembersihan Struktur Perpustakaan v2.0</h2><p>Menghapus UI lama dari folder siteman setelah modul dipindahkan ke folder perpustakaan.</p></div><div class="sds-hero-actions"><a class="btn btn-outline-secondary" href="dashboard">Kembali</a></div></div>
    <?php require __DIR__ . '/../partials/nav.php'; ?>
    <?php if ($message): ?><div class="alert alert-success"><?= perpus_h($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= perpus_h($error) ?></div><?php endif; ?>
    <div class="card"><div class="card-body">
        <div class="alert alert-info sds-toast-ignore">Pembersihan hanya menghapus file halaman lama <code>siteman/pages/perpus_*.php</code>. Database, tabel <code>perpus_*</code>, koleksi, anggota, transaksi, konfigurasi, dan folder baru tidak dihapus.</div>
        <p><strong>File lama yang masih terdeteksi:</strong> <?= count($remaining) ?></p>
        <?php if ($remaining): ?><ul><?php foreach ($remaining as $file): ?><li><code><?= perpus_h(str_replace($root . '/', '', $file)) ?></code></li><?php endforeach; ?></ul>
        <form method="post" onsubmit="return confirm('Hapus seluruh file UI Perpustakaan lama dari folder siteman?')"><input type="hidden" name="csrf" value="<?= perpus_h(perpus_csrf()) ?>"><button class="btn btn-danger">Bersihkan Struktur Lama</button></form>
        <?php else: ?><div class="alert alert-success sds-toast-ignore">Struktur sudah bersih. Seluruh halaman operasional berada di folder <code>perpustakaan</code>.</div><?php endif; ?>
    </div></div>
</div>
