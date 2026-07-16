<?php
require_once '../config/runtime.php';
sds_session_start();
require '../db.php';
$page = $_GET['page'] ?? 'dashboard';
if (!in_array($page, ['login','login_proses','logout'], true)) {
    if (sds_modules()->isEnabled('library')) require_once '../config/perpus.php';
    require '../vendor/autoload.php'; // autoload fitur dokumen hanya setelah login
}
include 'fungsi.php'; // fungsi umum

// Ambil page dari query string, default ke dashboard

$legacyPerpusRoutes = [
    'perpus_dashboard' => 'dashboard',
    'perpus_anggota' => 'anggota',
    'perpus_buku' => 'buku',
    'perpus_katalog' => 'katalog',
    'perpus_sirkulasi' => 'sirkulasi',
    'perpus_kunjungan' => 'kunjungan',
    'perpus_laporan' => 'laporan',
    'perpus_pengaturan' => 'pengaturan',
    'perpus_migrasi' => 'migrasi',
];
if (isset($legacyPerpusRoutes[$page])) {
    header('Location: ' . sds_base_url('perpustakaan/' . $legacyPerpusRoutes[$page]));
    exit;
}



// ===============================
// Hak akses role admin
// Role kesiswaan hanya boleh membuka Dashboard, Master Data, dan Peserta Didik.
// Role lain tetap mengikuti akses lama.
// ===============================
function adminCanAccessPage($page)
{
    $role = $_SESSION['admin_role'] ?? '';

    if ($role === 'superadmin') return true;
    if ($role !== 'kesiswaan') return false;

    $kesiswaanPages = [
        'dashboard',

        // Master Data
        'jurusan',
        'jurusan_tambah',
        'jurusan_edit',
        'jurusan_hapus',
        'kuota_kelas',
        'kuota_kelas_tambah',
        'kuota_kelas_edit',
        'kuota_kelas_hapus',
        'kuota_kelas_siswa',
        'ekskul',
        'ekskul_tambah',
        'ekskul_tambah_siswa',
        'ekskul_lihat_siswa',
        'ekskul_edit',
        'ekskul_hapus',
        'ekskul_hapus_siswa',
        'ekskul_tabel',
        'ekskul_salin',

        // Peserta Didik
        'students',
        'koreksi_kelas_siswa',
        'koreksi_kelas_siswa_proses',
        'generate_nipd',
        'reset_nipd',
        'simpan_pengaturan_nipd',
        'students_import',
        'students_import_template',
        'student_view',
        'student_edit',
        'edit_proses',
        'student_delete',
        'update_dapodik_status',
        'update_status_siswa',
        'hapus_wali',
        'upload_berkas_tambahan_siswa',
        'hapus_berkas_tambahan',
        'upload_foto_ajax',
        'upload_berkas_pelanggaran_siswa',
        'hapus_berkas_pelanggaran'
    ];

    return in_array($page, $kesiswaanPages, true);
}

// ===============================
// EARLY EXIT untuk output file (CSV/PDF/dll) agar tidak masuk layout HTML
// ===============================
if ($page === 'students_import_template') {
    // pastikan sudah login dan role diizinkan
    if (!isset($_SESSION['admin_id'])) { header('Location: login'); exit; }
    if (!adminCanAccessPage($page)) { header('Location: dashboard'); exit; }
    include 'pages/students_import_template.php';
    exit;

}
if ($page === 'students_import') {
    // ini proses POST, sebaiknya juga tidak ikut layout
    if (!isset($_SESSION['admin_id'])) { header('Location: login'); exit; }
    if (!adminCanAccessPage($page)) { header('Location: dashboard'); exit; }
    include 'pages/students_import.php';
    exit;
}


// Daftar halaman yang valid (boleh diakses)
$allowed_pages = [
    'login',
    'login_proses',
    'logout',
    'dashboard',
    'kuota_kelas',
    'students',
    'log_update',
    'kuota_kelas_tambah',
    'kuota_kelas_edit',
    'kuota_kelas_hapus',
    'kuota_kelas_siswa',
    'student_view',
    'student_edit',
    'edit_proses',
    'student_delete',
    'update_dapodik_status',
    'update_status_siswa',
    'pengaturan',
    'pengaturan_simpan',
    'log_aktivitas',
    'generate_penempatan_kelas',
    'reset_penempatan',
    'generate_nipd',
    'reset_nipd',
    'jurusan',
    'jurusan_tambah',
    'jurusan_edit',
    'jurusan_hapus',
    'simpan_pengaturan_nipd',
    'ekskul',
    'ekskul_tambah',
    'ekskul_tambah_siswa',
    'ekskul_lihat_siswa',
    'ekskul_edit',
    'ekskul_hapus',
    'ekskul_hapus_siswa',
    'ekskul_tabel',
    'ekskul_salin',
    'hapus_wali',
    'upload_berkas_tambahan_siswa',
    'hapus_berkas_tambahan',
    'upload_foto_ajax',
    'upload_berkas_pelanggaran_siswa',
    'hapus_berkas_pelanggaran',
    'ekskul_rekap_nilai',
    'ekskul_nilai_siswa',
    'naik_kelas',
    'tidak_naik_kelas',
    'naikkan_siswa',
    'siswa_tidak_naik',
    'siswa_tidak_naik_pindah_kelas_prosess',
    'admin_fields',
    'ekskul_absen_siswa',
    'ekskul_rekap_absen',
    'ekskul_rekap_absen_siswa',
    'students_rfid',
    'generate_rfid',
    'ekskul_absen_rfid',
    'ekskul_simpan_materi',
    'anjungan_admin',
    'students_rfid_input',
    'students_import',
    'sync_absensi',
    'students_import_template',
    'application_accounts',
    'modules',
    'tahun_ajaran',
    'teachers',
    'teachers_rfid',
    'teachers_rfid_input',
    'rfid_history',
    'ekbm',
    'koreksi_kelas_siswa',
    'koreksi_kelas_siswa_proses',
    'siswa_tidak_naik_batal',
];

// Jika page tidak valid, arahkan ke not_found
if (!in_array($page, $allowed_pages)) {
    $page = 'not_found';
}

// Halaman yang bisa diakses tanpa login
$halamanBebas = ['login', 'login_proses'];

// Jika belum login dan bukan halaman bebas, redirect ke login
if (!in_array($page, $halamanBebas) && !isset($_SESSION['admin_id'])) {
    header('Location: login');
    exit;
}

// Jika sudah login tetapi role tidak boleh membuka halaman ini, arahkan ke dashboard.
if (!in_array($page, $halamanBebas) && !adminCanAccessPage($page)) {
    header('Location: dashboard');
    exit;
}

// Data admin untuk ditampilkan
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? '-';

// Tahun ajaran aktif disediakan oleh db.php dari master Tahun Ajaran SDS.
// Jangan menghitung ulang dari tanggal server agar seluruh modul konsisten.
$tahunAjaran = (string)($tahunAjaran ?? '');
$semesterAktif = (string)($semesterAktif ?? 'ganjil');

// Ambil pengaturan sekolah (misalnya hanya ada 1 baris data)
$pengaturan = [];
$result = $conn->query("SELECT * FROM pengaturan LIMIT 1");
$pengaturan = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : ['nama_sekolah' => '', 'logo' => ''];

if (!empty($pengaturan['system_timezone']) && in_array((string)$pengaturan['system_timezone'], timezone_identifiers_list(), true)) {
    date_default_timezone_set((string)$pengaturan['system_timezone']);
}

if (isset($_SESSION['admin_id'])) {
    $_SESSION['_idle_timeout'] = max(600, min(86400, (int)($pengaturan['admin_session_minutes'] ?? 30) * 60));
    if (($_SESSION['auth_type'] ?? 'admin') === 'admin') {
        $sessionHash = hash('sha256', session_id());
        $sessionAdminId = (int)$_SESSION['admin_id'];
        $sessionIp = substr((string)($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
        $sessionUa = mb_substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
        $sessionStmt = $conn->prepare('INSERT INTO sds_admin_sessions (session_hash,admin_id,ip_address,user_agent,last_activity,created_at) VALUES (?,?,?,?,NOW(),NOW()) ON DUPLICATE KEY UPDATE admin_id=VALUES(admin_id),ip_address=VALUES(ip_address),user_agent=VALUES(user_agent),last_activity=NOW()');
        $sessionStmt->bind_param('siss', $sessionHash, $sessionAdminId, $sessionIp, $sessionUa);
        $sessionStmt->execute();
        $sessionStmt->close();
        $conn->query("DELETE FROM sds_admin_sessions WHERE last_activity<DATE_SUB(NOW(),INTERVAL 7 DAY)");
    }
}

if (!empty($_SESSION['password_expired']) && !in_array($page, ['pengaturan','pengaturan_simpan','logout'], true)) {
    $_SESSION['error'] = 'Password administrator sudah melewati masa berlaku. Silakan ganti password untuk melanjutkan.';
    header('Location: pengaturan#admin');
    exit;
}

ob_start();

// Perpustakaan mengenali sesi superadmin SDS, sedangkan staf memiliki login mandiri.
// Pada halaman login config/perpus.php sengaja belum dimuat agar halaman tetap ringan,
// sehingga pemeriksaan akses hanya dijalankan setelah sesi admin tersedia.
$perpusSidebarAccess = ['allowed' => false, 'role' => ''];
if (isset($_SESSION['admin_id']) && function_exists('sds_perpus_admin_access')) {
    $perpusSidebarAccess = sds_perpus_admin_access(
        $conn,
        (int)$_SESSION['admin_id'],
        (string)$adminRole
    );
}
$perpusSidebarAllowed = !empty($perpusSidebarAccess['allowed']);
?>
           
<?php
// SDS dan Absensi berada pada domain serta sesi PHP yang sama.
$absensiAdminUrl = sds_base_url('absensi/sw-admin/');
$moduleRegistry = sds_modules();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Responsive Admin &amp; Dashboard Template">
    <meta name="author" content="Affan">
    <meta name="keywords" content="Sds, bootstrap, admin, dashboard">

    <link rel="shortcut icon" href="../uploads/logo/<?= $pengaturan['logo'] ?>" type="image/png">

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <title>SDS - Sistem Data Siswa</title>


    <script>
        (function () {
            try {
                if (localStorage.getItem('sds_admin_sidebar_icon_only') === '1') {
                    document.documentElement.classList.add('sds-sidebar-icon-only');
                }
            } catch (e) {}
        })();
    </script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/css/lightbox.min.css" rel="stylesheet">


    <!-- <link href="../assets/css/css2.css?family=Arial:wght@300;400;600&display=swap" rel="stylesheet"> -->
    <link class="js-stylesheet" href="../assets/css/light.css" rel="stylesheet">
    <link href="../assets/css/custom.css" rel="stylesheet">
    <style>
        body {
            opacity: 0;
        }

        /* SDS Admin sidebar icon-only mode - stable override */
        .sidebar.js-sidebar {
            display: flex;
            flex-direction: column;
            height: 100vh;
            position: sticky;
            top: 0;
            overflow: visible;
        }

        .sidebar.js-sidebar > .sidebar-brand {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            height: 58px;
            min-height: 58px;
            box-sizing: border-box;
            position: relative;
            z-index: 1050;
        }

        .sidebar.js-sidebar > .sidebar-content {
            flex: 0 0 calc(100vh - 58px);
            height: calc(100vh - 58px) !important;
            max-height: calc(100vh - 58px);
            min-height: 0;
            overflow: hidden;
        }

        .sidebar.js-sidebar > .sidebar-content .simplebar-wrapper,
        .sidebar.js-sidebar > .sidebar-content .simplebar-mask,
        .sidebar.js-sidebar > .sidebar-content .simplebar-offset {
            height: 100%;
            max-height: 100%;
        }

        .sidebar.js-sidebar > .sidebar-content .simplebar-content-wrapper {
            height: 100%;
            max-height: 100%;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            overscroll-behavior: contain;
        }

        @media (min-width: 992px) {
            html.sds-sidebar-icon-only body:not([data-sidebar-position=right]) .sidebar,
            html.sds-sidebar-icon-only body:not([data-sidebar-position=right]) .sidebar.collapsed {
                min-width: 74px !important;
                max-width: 74px !important;
                width: 74px !important;
                margin-left: 0 !important;
                overflow: visible !important;
                z-index: 1040;
            }

            html.sds-sidebar-icon-only .sidebar-content,
            html.sds-sidebar-icon-only .simplebar-wrapper,
            html.sds-sidebar-icon-only .simplebar-mask,
            html.sds-sidebar-icon-only .simplebar-offset {
                overflow: hidden !important;
            }

            html.sds-sidebar-icon-only .simplebar-content-wrapper {
                overflow-x: hidden !important;
                overflow-y: auto !important;
            }

            html.sds-sidebar-icon-only .simplebar-content {
                overflow: visible !important;
            }

            html.sds-sidebar-icon-only .sidebar-brand {
                padding-left: 0 !important;
                padding-right: 0 !important;
                text-align: center;
                min-height: 58px;
            }

            html.sds-sidebar-icon-only .sidebar-brand-text,
            html.sds-sidebar-icon-only .sidebar-link span.align-middle,
            html.sds-sidebar-icon-only .sidebar-header {
                display: none !important;
            }

            html.sds-sidebar-icon-only .sidebar-brand-icon {
                display: inline-flex !important;
                align-items: center;
                justify-content: center;
                width: 50px;
                height: 23px;
                border-radius: 12px;
                background: rgba(255,255,255,.12);
                color: #fff;
                font-size: 12px;
                font-weight: 800;
                letter-spacing: .03em;
                margin: 0 auto;
            }

            html.sds-sidebar-icon-only .sidebar-link,
            html.sds-sidebar-icon-only a.sidebar-link {
                display: flex !important;
                align-items: center;
                justify-content: center;
                min-height: 52px;
                padding: .75rem 0 !important;
            }

            html.sds-sidebar-icon-only .sidebar-link i,
            html.sds-sidebar-icon-only .sidebar-link svg,
            html.sds-sidebar-icon-only a.sidebar-link i,
            html.sds-sidebar-icon-only a.sidebar-link svg {
                margin-right: 0 !important;
            }

            html.sds-sidebar-icon-only .sidebar [data-bs-toggle=collapse]::after {
                display: none !important;
            }

            html.sds-sidebar-icon-only .sidebar-item {
                position: relative;
            }

            html.sds-sidebar-icon-only .sidebar-dropdown {
                position: fixed !important;
                left: 74px;
                top: 0;
                min-width: 250px;
                max-width: 290px;
                padding: .45rem 0;
                background: #1a4b60;
                border: 1px solid rgba(15, 23, 42, .08);
                border-radius: 0px;
                box-shadow: 0 18px 42px rgba(15, 23, 42, .18);
                z-index: 3000;
                height: auto !important;
            }

            html.sds-sidebar-icon-only .sidebar-dropdown.collapse,
            html.sds-sidebar-icon-only .sidebar-dropdown.collapsing,
            html.sds-sidebar-icon-only .sidebar-dropdown.collapse.show {
                display: none !important;
            }

            html.sds-sidebar-icon-only .sidebar-item.sds-flyout-open > .sidebar-dropdown {
                display: block !important;
            }

            html.sds-sidebar-icon-only .sidebar-dropdown .sidebar-link,
            html.sds-sidebar-icon-only .sidebar-dropdown a.sidebar-link {
                justify-content: flex-start;
                min-height: auto;
                padding: .5rem 1.5rem !important;
                color: #fff !important;
                background: #1a4b60 !important;
                border-left: 0 !important;
                white-space: normal;
                text-align: left;
            }

            html.sds-sidebar-icon-only .sidebar-dropdown .sidebar-link:hover,
            html.sds-sidebar-icon-only .sidebar-dropdown .sidebar-item.active > .sidebar-link {
                color: #0f5ed7 !important;
                background: #eff6ff !important;
            }
            

            html.sds-sidebar-icon-only .sidebar-dropdown .sidebar-link::before {
                content: '→';
                margin-right: .55rem;
                color: #fff;
            }
        }
    </style>


    <style id="sds-floating-toast-style">
        #sds-toast-container {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 20000;
            width: min(390px, calc(100vw - 36px));
            pointer-events: none;
        }

        #sds-toast-container .sds-floating-alert {
            position: relative;
            width: 100%;
            margin: 0 0 10px !important;
            border: 0;
            border-left: 4px solid currentColor;
            border-radius: 10px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, .20);
            pointer-events: auto;
            animation: sdsToastIn .22s ease-out both;
        }

        #sds-toast-container .sds-floating-alert.sds-toast-out {
            animation: sdsToastOut .18s ease-in both;
        }

        #sds-toast-container .alert-message {
            margin: 0;
        }

        @keyframes sdsToastIn {
            from { opacity: 0; transform: translate3d(24px, -8px, 0); }
            to { opacity: 1; transform: translate3d(0, 0, 0); }
        }

        @keyframes sdsToastOut {
            from { opacity: 1; transform: translate3d(0, 0, 0); }
            to { opacity: 0; transform: translate3d(24px, -8px, 0); }
        }

        @media (max-width: 575.98px) {
            #sds-toast-container {
                top: 12px;
                right: 12px;
                width: calc(100vw - 24px);
            }
        }
    </style>

    <!-- Bootstrap & Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.css" />
    <!-- Font Awesome 4 CDN -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css"> -->

    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-papNpU5W10EK0TGPw3jvU0k0A9Tf64Lq59ltjT9OEqVWtJh7DYQdeDgIqNQ5KbwT8IQh9ozMHU0bTxezl3Qw7Q==" crossorigin="anonymous" referrerpolicy="no-referrer" /> -->

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script src="../assets/js/settings.js?v=20260713-3"></script>
    <!-- <script src="../assets/js/app.js"></script> -->
</head>

<body data-theme="default" data-layout="fluid" data-sidebar-position="left" data-sidebar-layout="default">
    <?php if (!in_array($page, $halamanBebas)) : ?>
            <div class="wrapper">
                <nav id="sidebar" class="sidebar js-sidebar">
                    <a class="sidebar-brand" href="dashboard">
                        <span class="sidebar-brand-icon align-middle">SDS</span><span class="sidebar-brand-text align-middle">Admin Panel</span>
                    </a>
                    <div class="sidebar-content js-simplebar">
                    <ul class="sidebar-nav">
                        <li class="sidebar-header">UTAMA</li>
                        <li class="sidebar-item <?= $page === 'dashboard' ? 'active' : '' ?>"><a href="dashboard" class="sidebar-link"><i class="align-middle" data-feather="layout"></i><span class="align-middle">Dashboard</span></a></li>

                        <?php $dataPages = $adminRole === 'superadmin'
                            ? ['tahun_ajaran','jurusan','kuota_kelas','students','teachers','koreksi_kelas_siswa']
                            : ['jurusan','kuota_kelas','students','koreksi_kelas_siswa']; ?>
                        <li class="sidebar-header">DATA INDUK</li>
                        <li class="sidebar-item <?= in_array($page, $dataPages, true) ? 'active' : '' ?>">
                            <a href="#dataInduk" data-bs-toggle="collapse" class="sidebar-link <?= in_array($page, $dataPages, true) ? '' : 'collapsed' ?>" aria-expanded="<?= in_array($page, $dataPages, true) ? 'true' : 'false' ?>" aria-controls="dataInduk"><i class="align-middle" data-feather="database"></i><span class="align-middle">Data Induk</span></a>
                            <ul id="dataInduk" class="sidebar-dropdown list-unstyled collapse <?= in_array($page, $dataPages, true) ? 'show' : '' ?>" data-bs-parent="#sidebar">
                                <?php if ($adminRole === 'superadmin') : ?><li class="sidebar-item <?= $page === 'tahun_ajaran' ? 'active' : '' ?>"><a class="sidebar-link" href="tahun_ajaran">Tahun Ajaran</a></li><?php endif; ?>
                                <li class="sidebar-item <?= $page === 'jurusan' ? 'active' : '' ?>"><a class="sidebar-link" href="jurusan">Kompetensi Keahlian</a></li>
                                <li class="sidebar-item <?= $page === 'kuota_kelas' ? 'active' : '' ?>"><a class="sidebar-link" href="kuota_kelas">Rombel</a></li>
                                <li class="sidebar-item <?= $page === 'students' ? 'active' : '' ?>"><a class="sidebar-link" href="students">Peserta Didik</a></li>
                                <?php if ($adminRole === 'superadmin') : ?><li class="sidebar-item <?= $page === 'teachers' ? 'active' : '' ?>"><a class="sidebar-link" href="teachers">Pengajar & Pegawai</a></li><?php endif; ?>
                                <li class="sidebar-item <?= $page === 'koreksi_kelas_siswa' ? 'active' : '' ?>"><a class="sidebar-link" href="koreksi_kelas_siswa">Koreksi Kelas Siswa</a></li>
                            </ul>
                        </li>

                        <?php if ($adminRole === 'superadmin') : ?>
                        <li class="sidebar-header">AKADEMIK</li>
                        <li class="sidebar-item <?= $page === 'ekbm' ? 'active' : '' ?>"><a href="ekbm" class="sidebar-link"><i class="align-middle" data-feather="calendar"></i><span class="align-middle">Mapel & Jadwal</span></a></li>
                        <li class="sidebar-item <?= $page === 'ekskul' ? 'active' : '' ?>"><a href="ekskul" class="sidebar-link"><i class="align-middle" data-feather="award"></i><span class="align-middle">Ekstrakurikuler</span></a></li>
                        <?php $rfidPages = ['students_rfid','teachers_rfid','rfid_history']; ?>
                        <li class="sidebar-header">IDENTITAS & RFID</li>
                        <li class="sidebar-item <?= $page === 'students_rfid' ? 'active' : '' ?>"><a href="students_rfid" class="sidebar-link"><i class="align-middle" data-feather="credit-card"></i><span class="align-middle">RFID Peserta Didik</span></a></li>
                        <li class="sidebar-item <?= $page === 'teachers_rfid' ? 'active' : '' ?>"><a href="teachers_rfid" class="sidebar-link"><i class="align-middle" data-feather="briefcase"></i><span class="align-middle">RFID Pengajar & Pegawai</span></a></li>
                        <li class="sidebar-item <?= $page === 'rfid_history' ? 'active' : '' ?>"><a href="rfid_history" class="sidebar-link"><i class="align-middle" data-feather="clock"></i><span class="align-middle">Riwayat Kartu RFID</span></a></li>

                        <li class="sidebar-header">APLIKASI</li>
                        <?php if ($moduleRegistry->isEnabled('attendance')) : ?><li class="sidebar-item"><a href="<?= htmlspecialchars($absensiAdminUrl, ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link" target="_blank" rel="noopener"><i class="align-middle" data-feather="user-check"></i><span class="align-middle">Absensi</span></a></li><?php endif; ?>
                        <?php if ($moduleRegistry->isEnabled('canteen')) : ?><li class="sidebar-item"><a href="../mkantin/admin/login.php" class="sidebar-link" target="_blank" rel="noopener"><i class="align-middle" data-feather="shopping-bag"></i><span class="align-middle">Kantin & E-Money</span></a></li><?php endif; ?>
                        <?php if ($moduleRegistry->isEnabled('library') && $perpusSidebarAllowed) : ?><li class="sidebar-item"><a href="<?= htmlspecialchars(sds_base_url('perpustakaan/'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-link" target="_blank" rel="noopener"><i class="align-middle" data-feather="book-open"></i><span class="align-middle">Perpustakaan</span></a></li><?php endif; ?>
                        <?php if ($moduleRegistry->isEnabled('kiosk')) : ?><li class="sidebar-item <?= $page === 'anjungan_admin' ? 'active' : '' ?>"><a href="anjungan_admin" class="sidebar-link"><i class="align-middle" data-feather="monitor"></i><span class="align-middle">Anjungan</span></a></li><?php endif; ?>

                        <li class="sidebar-header">SISTEM</li>
                        <li class="sidebar-item <?= $page === 'modules' ? 'active' : '' ?>"><a href="modules" class="sidebar-link"><i class="align-middle" data-feather="package"></i><span class="align-middle">Modul & Produk</span></a></li>
                        <li class="sidebar-item <?= $page === 'application_accounts' ? 'active' : '' ?>"><a href="application_accounts" class="sidebar-link"><i class="align-middle" data-feather="shield"></i><span class="align-middle">Akun & Akses Aplikasi</span></a></li>
                        <li class="sidebar-item <?= $page === 'admin_fields' ? 'active' : '' ?>"><a href="admin_fields" class="sidebar-link"><i class="align-middle" data-feather="edit-3"></i><span class="align-middle">Pengaturan Formulir</span></a></li>
                        <li class="sidebar-item <?= $page === 'pengaturan' ? 'active' : '' ?>"><a href="pengaturan" class="sidebar-link"><i class="align-middle" data-feather="settings"></i><span class="align-middle">Pengaturan Sistem</span></a></li>
                        <li class="sidebar-item <?= $page === 'log_aktivitas' ? 'active' : '' ?>"><a href="log_aktivitas" class="sidebar-link"><i class="align-middle" data-feather="activity"></i><span class="align-middle">Log Aktivitas</span></a></li>
                        <li class="sidebar-item <?= $page === 'log_update' ? 'active' : '' ?>"><a href="log_update" class="sidebar-link"><i class="align-middle" data-feather="download-cloud"></i><span class="align-middle">Pembaruan Sistem</span></a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
            <div class="main">
                <nav class="navbar navbar-expand navbar-light navbar-bg d-print-none">
                    <a class="sidebar-toggle js-sidebar-toggle">
                        <i class="hamburger align-self-center"></i>
                    </a>
                    <h4 class="mb-0 text-white"><?= !empty($pengaturan['nama_sekolah']) ? $pengaturan['nama_sekolah'] : 'Sekolah' ?> Tahun Ajaran : <?= $tahunAjaran ?></h4>
                    <div class="navbar-collapse collapse">
                        <ul class="navbar-nav navbar-align">
                            <li class="nav-item">
                                <a class="nav-icon js-fullscreen d-none d-lg-block white" href="#">
                                    <div class="position-relative"><i class="align-middle text-white" data-feather="maximize"></i></div>
                                </a>
                            </li>
                            <li class="nav-item dropdown">
                                <a href="index?page=logout" class="btn btn-danger">Keluar</a>
                            </li>
                        </ul>
                    </div>
                </nav>
            <?php endif; ?>
            <main class="content mt-6 p-0 mb-0">
                <?php
                switch ($page) {
                    case 'login':
                        include 'pages/login.php';
                        break;
                    case 'login_proses':
                        include 'pages/login_process.php';
                        break;
                    case 'logout':
                        include 'pages/logout.php';
                        break;
                    case 'dashboard':
                        include 'pages/dashboard.php';
                        break;
                    case 'kuota_kelas':
                        include 'pages/kuota_kelas_index.php';
                        break;
                    case 'students':
                        include 'pages/students.php';
                        break; 
                    case 'sync_absensi':
                        include 'pages/sync_absensi.php';
                        break;
                    case 'log_update':
                        include 'pages/log_update.php';
                        break;
                    case 'kuota_kelas_tambah':
                        include 'pages/kuota_kelas_tambah.php';
                        break;
                    case 'kuota_kelas_siswa':
                        include 'pages/kuota_kelas_siswa.php';
                        break;
                    case 'kuota_kelas_edit':
                        include 'pages/kuota_kelas_edit.php';
                        break;
                    case 'kuota_kelas_hapus':
                        include 'pages/kuota_kelas_hapus.php';
                        break;
                    case 'student_view':
                        include 'pages/student_view.php';
                        break;
                    case 'student_edit':
                        include 'pages/student_edit.php';
                        break;
                    case 'edit_proses':
                        include 'pages/edit_proses.php';
                        break;
                    case 'student_delete':
                        include 'pages/student_delete.php';
                        break;
                    case 'update_dapodik_status':
                        include 'pages/update_dapodik_status.php';
                        break;
                    case 'update_status_siswa':
                        include 'pages/update_status_siswa.php';
                        break;
                    case 'pengaturan':
                        include 'pages/pengaturan.php';
                        break;
                    case 'pengaturan_simpan':
                        include 'pages/pengaturan_simpan.php';
                        break;
                    case 'log_aktivitas':
                        include 'pages/log_aktivitas.php';
                        break;
                    case 'generate_penempatan_kelas':
                        include 'pages/generate_penempatan_kelas.php';
                        break;
                    case 'reset_penempatan':
                        include 'pages/reset_penempatan.php';
                        break;
                    case 'generate_nipd':
                        include 'pages/generate_nipd.php';
                        break;
                    case 'reset_nipd':
                        include 'pages/reset_nipd.php';
                        break;
                    case 'jurusan':
                        include 'pages/jurusan.php';
                        break;
                    case 'jurusan_tambah':
                        include 'pages/jurusan_tambah.php';
                        break;
                    case 'jurusan_edit':
                        include 'pages/jurusan_edit.php';
                        break;
                    case 'jurusan_hapus':
                        include 'pages/jurusan_hapus.php';
                        break;
                    case 'simpan_pengaturan_nipd':
                        include 'pages/simpan_pengaturan_nipd.php';
                        break;
                    case 'ekskul':
                        include 'pages/ekskul.php';
                        break;
                    case 'ekskul_tambah':
                        include 'pages/ekskul_tambah.php';
                        break;
                    case 'ekskul_tambah_siswa':
                        include 'pages/ekskul_tambah_siswa.php';
                        break;
                    case 'ekskul_lihat_siswa':
                        include 'pages/ekskul_lihat_siswa.php';
                        break;
                    case 'ekskul_hapus_siswa':
                        include 'pages/ekskul_hapus_siswa.php';
                        break;
                    case 'ekskul_tabel':
                        include 'pages/ekskul_tabel.php';
                        break;
                    case 'ekskul_edit':
                        include 'pages/ekskul_edit.php';
                        break;
                    case 'ekskul_hapus':
                        include 'pages/ekskul_hapus.php';
                        break;
                    case 'ekskul_salin':
                        include 'pages/ekskul_salin.php';
                        break;
                    case 'hapus_wali':
                        include 'pages/hapus_wali.php';
                        break;
                    case 'upload_berkas_tambahan_siswa':
                        include 'pages/upload_berkas_tambahan_siswa.php';
                        break;
                    case 'hapus_berkas_tambahan':
                        include 'pages/hapus_berkas_tambahan.php';
                        break;
                    case 'upload_foto_ajax':
                        include 'pages/upload_foto_ajax.php';
                        break;
                    case 'upload_berkas_pelanggaran_siswa':
                        include 'pages/upload_berkas_pelanggaran_siswa.php';
                        break;
                    case 'hapus_berkas_pelanggaran':
                        include 'pages/hapus_berkas_pelanggaran.php';
                        break;
                    case 'ekskul_rekap_nilai':
                        include 'pages/ekskul_rekap_nilai.php';
                        break;
                    case 'ekskul_nilai_siswa':
                        include 'pages/ekskul_nilai_siswa.php';
                        break;
                    case 'naik_kelas':
                        include 'pages/naik_kelas.php';
                        break;
                    case 'tidak_naik_kelas':
                        include 'pages/tidak_naik_kelas.php';
                        break;
                    case 'naikkan_siswa':
                        include 'pages/naikkan_siswa.php';
                        break;
                    case 'siswa_tidak_naik':
                        include 'pages/siswa_tidak_naik.php';
                        break;
                    case 'siswa_tidak_naik_pindah_kelas_prosess':
                        include 'pages/siswa_tidak_naik_pindah_kelas_prosess.php';
                        break;
                    case 'admin_fields':
                        include 'pages/admin_fields.php';
                        break;
                    case 'ekskul_absen_siswa':
                        include 'pages/ekskul_absen_siswa.php';
                        break;
                    case 'ekskul_rekap_absen':
                        include 'pages/ekskul_rekap_absen.php';
                        break;
                    case 'ekskul_rekap_absen_siswa':
                        include 'pages/ekskul_rekap_absen_siswa.php';
                        break;
                    case 'students_rfid':
                        include 'pages/students_rfid.php';
                        break;
                    case 'generate_rfid':
                        include 'pages/generate_rfid.php';
                        break;
                    case 'ekskul_absen_rfid':
                        include 'pages/ekskul_absen_rfid.php';
                        break;
                    case 'ekskul_simpan_materi':
                        include 'pages/ekskul_simpan_materi.php';
                        break;
                    case 'anjungan_admin':
                        include 'pages/anjungan_admin.php';
                        break;
                    case 'students_rfid_input':
                        include 'pages/students_rfid_input.php';
                        break;
                    case 'application_accounts':
                        include 'pages/application_accounts.php';
                        break;
                    case 'modules':
                        include 'pages/modules.php';
                        break;
                    case 'tahun_ajaran':
                        include 'pages/tahun_ajaran.php';
                        break;
                    case 'teachers':
                        include 'pages/teachers.php';
                        break;
                    case 'teachers_rfid':
                        include 'pages/teachers_rfid.php';
                        break;
                    case 'teachers_rfid_input':
                        include 'pages/teachers_rfid_input.php';
                        break;
                    case 'rfid_history':
                        include 'pages/rfid_history.php';
                        break;
                    case 'ekbm':
                        include 'pages/ekbm.php';
                        break;
                    case 'koreksi_kelas_siswa':
                        include 'pages/koreksi_kelas_siswa.php';
                        break;
                    case 'koreksi_kelas_siswa_proses':
                        include 'pages/koreksi_kelas_siswa_proses.php';
                        break;
                    case 'siswa_tidak_naik_batal':
                        include 'pages/siswa_tidak_naik_batal.php';
                        break;
                    default:
                        // Sebagai fallback (meskipun sudah di-handle)
                        echo "<p>Halaman tidak ditemukan!</p>";
                        break;
                }
                ?>
            </main>
            <?php if (!in_array($page, $halamanBebas)) : ?>
                <footer class="footer">
                    <div class="container-fluid">
                        <div class="row text-muted">
                            <div class="col-12 text-start">
                                <p class="mb-0">
                                    <a href="index?page=dashboard" class="text-muted"><strong>
                                            &copy; <?= date('Y') ?> <?= !empty($pengaturan['nama_sekolah']) ? $pengaturan['nama_sekolah'] : 'Sekolah' ?>
                                        </strong></a>
                                </p>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    <?php endif; ?>

    <script id="sds-bootstrap-modal-compat">
        (function () {
            const modalInstances = typeof WeakMap === 'function' ? new WeakMap() : null;

            function resolveElement(target) {
                if (!target) return null;
                if (target instanceof Element) return target;
                if (typeof target === 'string') {
                    return document.getElementById(target.replace(/^#/, '')) || document.querySelector(target);
                }
                return null;
            }

            window.sdsGetModalInstance = function (target, options) {
                const element = resolveElement(target);
                if (!element) {
                    console.error('[SDS Modal] Elemen modal tidak ditemukan:', target);
                    return null;
                }

                const Modal = window.bootstrap && window.bootstrap.Modal;
                if (typeof Modal !== 'function') {
                    console.error('[SDS Modal] Bootstrap Modal belum tersedia.');
                    return null;
                }

                let instance = null;

                try {
                    if (typeof Modal.getOrCreateInstance === 'function') {
                        instance = Modal.getOrCreateInstance(element, options || {});
                    } else if (typeof Modal.getInstance === 'function') {
                        instance = Modal.getInstance(element);
                        if (!instance) instance = new Modal(element, options || {});
                    } else if (modalInstances && modalInstances.has(element)) {
                        instance = modalInstances.get(element);
                    } else if (element.__sdsModalInstance) {
                        instance = element.__sdsModalInstance;
                    } else {
                        instance = new Modal(element, options || {});
                    }
                } catch (error) {
                    console.error('[SDS Modal] Gagal membuat instance modal:', error);
                    return null;
                }

                if (instance) {
                    if (modalInstances) modalInstances.set(element, instance);
                    element.__sdsModalInstance = instance;
                }

                return instance;
            };

            window.sdsShowModal = function (target, options) {
                function showNow() {
                    const instance = window.sdsGetModalInstance(target, options);
                    if (instance && typeof instance.show === 'function') {
                        instance.show();
                        return true;
                    }
                    return false;
                }

                if (window.bootstrap && typeof window.bootstrap.Modal === 'function') {
                    return showNow();
                }

                let attempts = 0;
                const timer = window.setInterval(function () {
                    attempts++;
                    if ((window.bootstrap && typeof window.bootstrap.Modal === 'function' && showNow()) || attempts >= 40) {
                        window.clearInterval(timer);
                    }
                }, 50);

                return false;
            };

            window.sdsHideModal = function (target) {
                const instance = window.sdsGetModalInstance(target);
                if (instance && typeof instance.hide === 'function') {
                    instance.hide();
                    return true;
                }
                return false;
            };
        })();
    </script>

    <script id="sds-floating-toast-script">
        (function () {
            function getToastContainer() {
                let container = document.getElementById('sds-toast-container');
                if (!container) {
                    container = document.createElement('div');
                    container.id = 'sds-toast-container';
                    container.setAttribute('aria-live', 'polite');
                    container.setAttribute('aria-atomic', 'false');
                    document.body.appendChild(container);
                }
                return container;
            }

            function closeToast(alertElement) {
                if (!alertElement || !alertElement.isConnected) return;
                alertElement.classList.add('sds-toast-out');
                window.setTimeout(function () {
                    if (alertElement.isConnected) alertElement.remove();
                }, 190);
            }

            function prepareToast(alertElement) {
                if (!(alertElement instanceof Element)) return;
                if (!alertElement.matches('.alert-success, .alert-danger')) return;
                if (alertElement.dataset.sdsToastProcessed === '1') return;
                if (alertElement.classList.contains('sds-toast-ignore')) return;

                alertElement.dataset.sdsToastProcessed = '1';
                alertElement.classList.add('sds-floating-alert', 'alert-dismissible', 'fade', 'show');
                alertElement.setAttribute('role', 'alert');

                let closeButton = alertElement.querySelector(':scope > .btn-close');
                if (!closeButton) {
                    closeButton = document.createElement('button');
                    closeButton.type = 'button';
                    closeButton.className = 'btn-close';
                    closeButton.setAttribute('aria-label', 'Tutup');
                    alertElement.prepend(closeButton);
                }
                closeButton.removeAttribute('data-bs-dismiss');
                closeButton.addEventListener('click', function () {
                    closeToast(alertElement);
                });

                getToastContainer().appendChild(alertElement);

                const configuredDuration = Number(alertElement.dataset.sdsToastDuration || 0);
                const defaultDuration = alertElement.classList.contains('alert-danger') ? 7500 : 4500;
                const duration = configuredDuration > 0 ? configuredDuration : defaultDuration;
                let timer = window.setTimeout(function () { closeToast(alertElement); }, duration);

                alertElement.addEventListener('mouseenter', function () {
                    window.clearTimeout(timer);
                });
                alertElement.addEventListener('mouseleave', function () {
                    window.clearTimeout(timer);
                    timer = window.setTimeout(function () { closeToast(alertElement); }, 2000);
                });
            }

            function scanAlerts(root) {
                if (!root) return;
                if (root instanceof Element && root.matches('.alert-success, .alert-danger')) {
                    prepareToast(root);
                }
                if (root.querySelectorAll) {
                    root.querySelectorAll('.alert-success, .alert-danger').forEach(prepareToast);
                }
            }

            window.sdsNotify = function (message, type, options) {
                options = options || {};
                const alertElement = document.createElement('div');
                const normalizedType = type === 'danger' || type === 'error' || type === 'failed' ? 'danger' : 'success';
                alertElement.className = 'alert alert-' + normalizedType;
                alertElement.textContent = String(message || '');
                if (options.duration) alertElement.dataset.sdsToastDuration = String(options.duration);
                prepareToast(alertElement);
                return alertElement;
            };

            function startToastSystem() {
                scanAlerts(document);
                const observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutation) {
                        mutation.addedNodes.forEach(function (node) {
                            if (node.nodeType === 1) scanAlerts(node);
                        });
                    });
                });
                observer.observe(document.body, { childList: true, subtree: true });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', startToastSystem, { once: true });
            } else {
                startToastSystem();
            }
        })();
    </script>

    <script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>
    <script src="../assets/js/app.js"></script>
    <script src="../assets/js/datatables.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.4/js/lightbox.min.js"></script>


    <script>
        (function () {
            const storageKey = 'sds_admin_sidebar_icon_only';
            const root = document.documentElement;

            function isDesktop() {
                return window.matchMedia('(min-width: 992px)').matches;
            }

            function getSidebar() {
                return document.querySelector('.js-sidebar');
            }

            function closeFlyouts() {
                document.querySelectorAll('.sidebar-item.sds-flyout-open').forEach(function (item) {
                    item.classList.remove('sds-flyout-open');
                });
            }

            function applySidebarState(collapsed) {
                const sidebar = getSidebar();
                if (!sidebar) return;

                if (collapsed && isDesktop()) {
                    root.classList.add('sds-sidebar-icon-only');
                    sidebar.classList.add('collapsed');
                } else {
                    root.classList.remove('sds-sidebar-icon-only');
                    if (isDesktop()) sidebar.classList.remove('collapsed');
                    closeFlyouts();
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const savedCollapsed = localStorage.getItem(storageKey) === '1';
                applySidebarState(savedCollapsed);

                document.addEventListener('click', function (event) {
                    const toggle = event.target.closest('.js-sidebar-toggle');
                    if (toggle) {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();

                        const nextCollapsed = !root.classList.contains('sds-sidebar-icon-only');
                        try { localStorage.setItem(storageKey, nextCollapsed ? '1' : '0'); } catch (e) {}
                        applySidebarState(nextCollapsed);
                        return false;
                    }

                    const collapseLink = event.target.closest('.sidebar [data-bs-toggle="collapse"]');
                    if (collapseLink && root.classList.contains('sds-sidebar-icon-only') && isDesktop()) {
                        event.preventDefault();
                        event.stopPropagation();
                        event.stopImmediatePropagation();

                        const item = collapseLink.closest('.sidebar-item');
                        if (!item) return false;
                        const willOpen = !item.classList.contains('sds-flyout-open');
                        closeFlyouts();
                        if (willOpen) {
                            const flyout = item.querySelector(':scope > .sidebar-dropdown');
                            if (flyout) {
                                const itemRect = item.getBoundingClientRect();
                                const estimatedHeight = Math.min(420, Math.max(120, flyout.scrollHeight || 120));
                                const top = Math.max(8, Math.min(itemRect.top, window.innerHeight - estimatedHeight - 8));
                                flyout.style.top = top + 'px';
                            }
                            item.classList.add('sds-flyout-open');
                        }
                        return false;
                    }

                    if (root.classList.contains('sds-sidebar-icon-only') && !event.target.closest('.js-sidebar')) {
                        closeFlyouts();
                    }
                }, true);

                window.addEventListener('resize', function () {
                    applySidebarState(localStorage.getItem(storageKey) === '1');
                });

                const sidebarContent = document.querySelector('.sidebar-content');
                if (sidebarContent) sidebarContent.addEventListener('scroll', closeFlyouts, true);
            });
        })();
    </script>

</body>

</html>
<?php ob_end_flush(); ?>
