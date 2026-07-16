<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
perpus_session_start();
define('SDS_PERPUSTAKAAN_APP', true);

$root = dirname(__DIR__);
require_once $root . '/db.php';
require_once $root . '/config/perpus.php';

$perpusUser = perpus_require_login($conn);

$aliases = [
    'perpus_dashboard' => 'dashboard', 'perpus_anggota' => 'anggota',
    'perpus_buku' => 'buku', 'perpus_katalog' => 'katalog',
    'perpus_sirkulasi' => 'sirkulasi', 'perpus_kunjungan' => 'kunjungan',
    'perpus_laporan' => 'laporan', 'perpus_pengaturan' => 'pengaturan',
    'perpus_migrasi' => 'migrasi', 'perpus_master' => 'master',
    'perpus_cetak' => 'cetak', 'perpus_data_massal' => 'data_massal',
    'perpus_reservasi' => 'reservasi', 'perpus_notifikasi' => 'notifikasi',
];

$page = strtolower(trim((string)($_GET['page'] ?? 'dashboard')));
$page = preg_replace('/[^a-z0-9_-]/', '', $page) ?: 'dashboard';
if (isset($aliases[$page])) {
    header('Location: ' . sds_base_url('perpustakaan/' . $aliases[$page]));
    exit;
}

$routes = [
    'dashboard' => 'dashboard.php', 'anggota' => 'anggota.php',
    'buku' => 'buku.php', 'katalog' => 'katalog.php',
    'master' => 'master.php', 'sirkulasi' => 'sirkulasi.php',
    'kunjungan' => 'kunjungan.php', 'laporan' => 'laporan.php',
    'cetak' => 'cetak.php', 'data_massal' => 'data_massal.php', 'pengaturan' => 'pengaturan.php',
    'reservasi' => 'reservasi.php', 'notifikasi' => 'notifikasi.php',
    'saran' => 'saran.php', 'audit' => 'audit.php',
    'migrasi' => 'migrasi.php', 'cleanup' => 'cleanup.php', 'users' => 'users.php',
];
$pageMeta = [
    'dashboard' => ['Dashboard', 'Ringkasan aktivitas Perpustakaan'],
    'anggota' => ['Data Anggota', 'Keanggotaan siswa dan pegawai SDS'],
    'buku' => ['Koleksi Buku', 'Bibliografi dan eksemplar koleksi'],
    'katalog' => ['Katalog', 'Pencarian dan ketersediaan koleksi'],
    'master' => ['Data Master', 'Referensi bibliografi dan aturan anggota'],
    'sirkulasi' => ['Sirkulasi', 'Peminjaman, pengembalian, perpanjangan, dan denda'],
    'kunjungan' => ['Kunjungan', 'Pencatatan pengunjung Perpustakaan'],
    'laporan' => ['Laporan', 'Rekap aktivitas dan transaksi'],
    'cetak' => ['Pusat Cetak', 'Kartu anggota, barcode, dan label koleksi'],
    'data_massal' => ['Data Massal & Excel', 'Import, export, dan aktivasi anggota massal'],
    'reservasi' => ['Reservasi & Inden', 'Antrean koleksi dan pengambilan reservasi anggota'],
    'notifikasi' => ['Notifikasi & Pengingat', 'Pesan anggota dan pengingat jatuh tempo'],
    'saran' => ['Kritik & Saran', 'Masukan dan tindak lanjut layanan Perpustakaan'],
    'audit' => ['Audit & Integritas', 'Pemeriksaan konsistensi data dan log aktivitas'],
    'pengaturan' => ['Pengaturan', 'Konfigurasi operasional Perpustakaan'],
    'migrasi' => ['Migrasi Data Lama', 'Pemindahan data aplikasi Perpustakaan sebelumnya'],
    'cleanup' => ['Pembersihan Struktur', 'Membersihkan file integrasi versi lama'],
    'users' => ['Pengguna Perpustakaan', 'Kelola admin dan staf perpustakaan'],
];
if (!isset($routes[$page])) { http_response_code(404); $page = 'not_found'; }
if (in_array($page, ['migrasi','cleanup','users','audit'], true) && $perpusUser['role'] !== 'admin') {
    http_response_code(403); $page = 'forbidden';
}

try {
    sds_perpus_ensure_schema($conn);
    $perpusAccess = ['allowed' => true, 'role' => $perpusUser['role']];
} catch (Throwable $e) {
    error_log('[Perpustakaan startup] ' . $e->getMessage());
    $startupError = $e->getMessage();
    $perpusAccess = ['allowed' => false, 'role' => ''];
}
$allowed = !empty($perpusAccess['allowed']);
if (!$allowed && empty($startupError)) http_response_code(403);

$adminName = $perpusUser['name'];
$adminRole = $perpusUser['role'];
$pengaturan = ['nama_sekolah' => 'Sekolah', 'logo' => ''];
$result = $conn->query('SELECT nama_sekolah,logo FROM pengaturan LIMIT 1');
if ($result && ($row = $result->fetch_assoc())) $pengaturan = array_merge($pengaturan, $row);

$appBase = sds_base_url('perpustakaan/');
$logoUrl = !empty($pengaturan['logo']) ? sds_base_url('uploads/logo/' . rawurlencode(basename((string)$pengaturan['logo']))) : '';
$pageTitle = $pageMeta[$page][0] ?? 'Perpustakaan';
$pageSubtitle = $pageMeta[$page][1] ?? '';

$sidebarBadges = ['terlambat' => 0, 'verifikasi' => 0, 'reservasi' => 0, 'notifikasi' => 0, 'saran' => 0];
if ($allowed && empty($startupError)) {
    $q = $conn->query("SELECT COUNT(*) total FROM perpus_peminjaman_detail WHERE status='dipinjam' AND tanggal_jatuh_tempo<CURDATE()");
    if ($q) $sidebarBadges['terlambat'] = (int)($q->fetch_assoc()['total'] ?? 0);
    $q = $conn->query("SELECT COUNT(*) total FROM perpus_anggota WHERE status_keanggotaan='perlu_verifikasi' OR pemilik_tipe='legacy'");
    if ($q) $sidebarBadges['verifikasi'] = (int)($q->fetch_assoc()['total'] ?? 0);
    $q = $conn->query("SELECT COUNT(*) total FROM perpus_reservasi WHERE status IN ('menunggu','siap')");
    if ($q) $sidebarBadges['reservasi'] = (int)($q->fetch_assoc()['total'] ?? 0);
    $q = $conn->query("SELECT COUNT(*) total FROM perpus_notifikasi WHERE status='baru' AND created_at>=DATE_SUB(NOW(),INTERVAL 7 DAY)");
    if ($q) $sidebarBadges['notifikasi'] = (int)($q->fetch_assoc()['total'] ?? 0);
    $q = $conn->query("SELECT COUNT(*) total FROM perpus_saran WHERE status='baru'");
    if ($q) $sidebarBadges['saran'] = (int)($q->fetch_assoc()['total'] ?? 0);
}

$legacyInternalFiles = array_map(fn($f) => $root . '/siteman/pages/' . $f, [
    'perpus_dashboard.php','perpus_anggota.php','perpus_buku.php','perpus_katalog.php',
    'perpus_sirkulasi.php','perpus_kunjungan.php','perpus_laporan.php','perpus_pengaturan.php','perpus_migrasi.php'
]);
$hasLegacyInternalFiles = count(array_filter($legacyInternalFiles, 'is_file')) > 0;

function perpus_layout_h($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function perpus_nav_active(string $current, array $pages): string { return in_array($current, $pages, true) ? ' active' : ''; }
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= perpus_layout_h($pageTitle) ?> · Perpustakaan <?= perpus_layout_h($pengaturan['nama_sekolah']) ?></title>
    <?php if ($logoUrl !== ''): ?><link rel="shortcut icon" href="<?= perpus_layout_h($logoUrl) ?>" type="image/png"><?php endif; ?>
    <link class="js-stylesheet" href="<?= perpus_layout_h($appBase) ?>assets/vendor/app.css" rel="stylesheet">
    <link href="<?= perpus_layout_h($appBase) ?>assets/css/perpustakaan.css?v=2.7.1" rel="stylesheet">
</head>
<body class="hold-transition sidebar-mini layout-fixed" data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
<div class="wrapper perpus-app-shell">
    <nav id="sidebar" class="sidebar js-sidebar perpus-sidebar sidebar-dark-primary elevation-4">
        <div class="sidebar-content js-simplebar">
            <a class="perpus-school-brand" href="<?= perpus_layout_h($appBase) ?>dashboard">
                <div class="perpus-school-logo"><?php if ($logoUrl !== ''): ?><img src="<?= perpus_layout_h($logoUrl) ?>" alt="Logo"><?php else: ?><i data-feather="home"></i><?php endif; ?></div>
                <div class="perpus-school-copy"><strong><?= perpus_layout_h($pengaturan['nama_sekolah']) ?></strong><small>Ruang kerja mandiri</small></div>
            </a>
            <ul class="sidebar-nav nav nav-pills nav-sidebar flex-column" role="menu">
                <li class="sidebar-header nav-header">UTAMA</li>
                <li class="sidebar-item<?= perpus_nav_active($page,['dashboard']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>dashboard"><i data-feather="home"></i><span>Dashboard</span></a></li>

                <li class="sidebar-header nav-header">DATA PERPUSTAKAAN</li>
                <li class="sidebar-item<?= perpus_nav_active($page,['anggota']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>anggota"><i data-feather="users"></i><span>Anggota</span><?php if($sidebarBadges['verifikasi']):?><span class="right badge bg-warning text-dark"><?= $sidebarBadges['verifikasi'] ?></span><?php endif;?></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['buku']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>buku"><i data-feather="book"></i><span>Koleksi & Eksemplar</span></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['katalog']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>katalog"><i data-feather="search"></i><span>Katalog</span></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['master']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>master"><i data-feather="layers"></i><span>Data Master</span></a></li>

                <li class="sidebar-header nav-header">TRANSAKSI</li>
                <li class="sidebar-item<?= perpus_nav_active($page,['sirkulasi']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>sirkulasi"><i data-feather="repeat"></i><span>Sirkulasi</span><?php if($sidebarBadges['terlambat']):?><span class="right badge bg-danger"><?= $sidebarBadges['terlambat'] ?></span><?php endif;?></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['kunjungan']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>kunjungan"><i data-feather="log-in"></i><span>Kunjungan</span></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['reservasi']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>reservasi"><i data-feather="bookmark"></i><span>Reservasi & Inden</span><?php if($sidebarBadges['reservasi']):?><span class="right badge bg-info"><?= $sidebarBadges['reservasi'] ?></span><?php endif;?></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['notifikasi']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>notifikasi"><i data-feather="bell"></i><span>Notifikasi</span><?php if($sidebarBadges['notifikasi']):?><span class="right badge bg-warning text-dark"><?= $sidebarBadges['notifikasi'] ?></span><?php endif;?></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['saran']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>saran"><i data-feather="message-square"></i><span>Kritik & Saran</span><?php if($sidebarBadges['saran']):?><span class="right badge bg-danger"><?= $sidebarBadges['saran'] ?></span><?php endif;?></a></li>

                <li class="sidebar-header nav-header">LAPORAN & CETAK</li>
                <li class="sidebar-item<?= perpus_nav_active($page,['laporan']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>laporan"><i data-feather="bar-chart-2"></i><span>Laporan</span></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['cetak']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>cetak"><i data-feather="printer"></i><span>Pusat Cetak</span></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['data_massal']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>data_massal"><i data-feather="file-plus"></i><span>Data Massal & Excel</span></a></li>

                <li class="sidebar-header nav-header">SISTEM</li>
                <li class="sidebar-item<?= perpus_nav_active($page,['pengaturan']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>pengaturan"><i data-feather="settings"></i><span>Pengaturan</span></a></li>
                <?php if ($adminRole === 'admin'): ?>
                <li class="sidebar-item<?= perpus_nav_active($page,['audit']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>audit"><i data-feather="shield"></i><span>Audit & Integritas</span></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['users']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>users"><i data-feather="user-check"></i><span>Pengguna & Staf</span></a></li>
                <li class="sidebar-item<?= perpus_nav_active($page,['migrasi','cleanup']) ?>"><a class="sidebar-link nav-link" href="<?= perpus_layout_h($appBase) ?>migrasi"><i data-feather="database"></i><span>Migrasi Data Lama</span></a></li><?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="main content-wrapper">
        <nav class="navbar navbar-expand navbar-white navbar-light navbar-bg perpus-topbar main-header">
            <a class="sidebar-toggle js-sidebar-toggle nav-link" href="#" aria-label="Buka/tutup sidebar"><i class="hamburger align-self-center"></i></a>
            <div class="perpus-top-title d-none d-md-block"><strong><?= perpus_layout_h($pageTitle) ?></strong><small><?= perpus_layout_h($pageSubtitle) ?></small></div>
            <div class="navbar-collapse collapse">
                <ul class="navbar-nav navbar-align ms-auto">
                    <li class="nav-item d-none d-md-inline-block"><a class="nav-link position-relative" href="<?= perpus_layout_h($appBase) ?>notifikasi" title="Notifikasi & pengingat"><i data-feather="bell"></i><?php if($sidebarBadges['notifikasi']):?><span class="perpus-top-badge"><?= min(99,$sidebarBadges['notifikasi']) ?></span><?php endif;?></a></li>
                    <li class="nav-item d-none d-md-inline-block"><a class="nav-link" href="<?= perpus_layout_h($appBase) ?>sirkulasi" title="Sirkulasi cepat"><i data-feather="repeat"></i></a></li>
                    <li class="nav-item d-none d-md-inline-block"><a class="nav-link" href="<?= perpus_layout_h(sds_base_url('perpustakaan/opac/')) ?>" target="_blank" title="Buka OPAC publik"><i data-feather="globe"></i></a></li>
                    <li class="nav-item d-none d-md-inline-block"><a class="nav-link" href="<?= perpus_layout_h($appBase) ?>katalog" title="Cari katalog"><i data-feather="search"></i></a></li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle perpus-user-dropdown" href="#" data-bs-toggle="dropdown"><span class="perpus-avatar-top"><?= perpus_layout_h(mb_strtoupper(mb_substr($adminName,0,1))) ?></span><span class="d-none d-sm-inline-block"><?= perpus_layout_h($adminName) ?></span></a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <div class="dropdown-item-text"><strong><?= perpus_layout_h($adminName) ?></strong><div class="small text-muted"><?= perpus_layout_h(ucfirst($adminRole)) ?> · <?= perpus_layout_h(ucfirst((string)($perpusAccess['role'] ?? 'operator'))) ?></div></div>
                            <div class="dropdown-divider"></div>
                            <?php if($adminRole==='admin'):?><a class="dropdown-item" href="<?= perpus_layout_h($appBase) ?>users"><i data-feather="shield" class="me-2"></i>Pengguna & Staf</a><?php endif;?>
                            <form method="post" action="<?= perpus_layout_h($appBase) ?>logout.php"><input type="hidden" name="csrf" value="<?= perpus_layout_h(sds_csrf_token()) ?>"><button class="dropdown-item" type="submit"><i data-feather="log-out" class="me-2"></i>Keluar</button></form>
                        </div>
                    </li>
                </ul>
            </div>
        </nav>

        <main class="content perpus-content content-area">
            <div class="container-fluid p-0">
                <?php if (!empty($startupError)): ?>
                    <div class="alert alert-danger"><strong>Modul belum dapat dibuka.</strong><br><?= perpus_layout_h($startupError) ?></div>
                <?php elseif (!$allowed): ?>
                    <div class="card card-outline card-danger"><div class="card-body text-center py-5"><h3>Akses perpustakaan tidak tersedia</h3></div></div>
                <?php elseif ($page === 'not_found'): ?>
                    <div class="card card-outline card-warning"><div class="card-body text-center py-5"><h3>Halaman tidak ditemukan</h3><a class="btn btn-primary" href="<?= perpus_layout_h($appBase) ?>dashboard">Kembali ke Dashboard</a></div></div>
                <?php elseif ($page === 'forbidden'): ?>
                    <div class="card card-outline card-danger"><div class="card-body text-center py-5"><h3>Akses ditolak</h3><p class="text-muted">Halaman ini hanya tersedia untuk admin perpustakaan.</p><a class="btn btn-primary" href="<?= perpus_layout_h($appBase) ?>dashboard">Kembali ke Dashboard</a></div></div>
                <?php else: ?>
                    <?php if ($hasLegacyInternalFiles && $adminRole === 'admin' && $page !== 'cleanup'): ?>
                        <div class="alert alert-warning perpus-structure-warning"><strong>Struktur v2.0 lama terdeteksi.</strong> File lama di <code>siteman/pages</code> dapat dibersihkan. <a class="alert-link" href="<?= perpus_layout_h($appBase) ?>cleanup">Bersihkan sekarang</a>.</div>
                    <?php endif; ?>
                    <?php require __DIR__ . '/pages/' . $routes[$page]; ?>
                <?php endif; ?>
            </div>
        </main>
        <footer class="footer main-footer"><div class="container-fluid d-flex justify-content-between gap-2 flex-wrap"><span>&copy; <?= date('Y') ?> <?= perpus_layout_h($pengaturan['nama_sekolah']) ?></span><span class="text-muted">Perpustakaan v2.6</span></div></footer>
    </div>
</div>
<div id="sds-toast-container" aria-live="polite" aria-atomic="false"></div>
<script src="<?= perpus_layout_h($appBase) ?>assets/vendor/app.js"></script>
<script src="<?= perpus_layout_h($appBase) ?>assets/js/perpustakaan.js?v=2.6.0"></script>
</body>
</html>
