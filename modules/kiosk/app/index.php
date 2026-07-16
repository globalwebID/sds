<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require "../db.php";
require_once "../config/anjungan_runtime.php";
sdsAnjunganEnsureSchema($conn);

$pengaturan = [];
$anjunganResult = $conn->query("SELECT * FROM anjungan ORDER BY id ASC LIMIT 1");
$anjungan = $anjunganResult instanceof mysqli_result ? ($anjunganResult->fetch_assoc() ?: []) : [];
$anjunganSettings = sdsAnjunganGetSettings($conn);
$topright = $conn->query("SELECT * FROM anjungan_topright WHERE status = 'aktif' ORDER BY urutan ASC");
$result = $conn->query("SELECT * FROM pengaturan LIMIT 1");

if ($result && $result->num_rows > 0) {
    $pengaturan = $result->fetch_assoc();
} else {
    $pengaturan = [
        'nama_sekolah' => '',
        'logo' => ''
    ];
}

$anjunganAktif = (int)($anjungan['aktif'] ?? 1) === 1;
$maintenance = (int)($anjunganSettings['maintenance'] ?? 0) === 1;
$isAdminPreview = isset($_GET['_preview']) && !empty($_SESSION['admin_id']);
if (!$isAdminPreview && (!$anjunganAktif || $maintenance)) {
    http_response_code(503);
    $judulStatus = $maintenance ? 'Anjungan Sedang Dalam Pemeliharaan' : 'Anjungan Sedang Dinonaktifkan';
    $pesanStatus = $maintenance
        ? 'Layanan informasi sedang dipersiapkan. Silakan mencoba kembali beberapa saat lagi.'
        : 'Layanan Anjungan belum dibuka oleh operator sekolah.';
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?= htmlspecialchars($judulStatus) ?></title>
        <style>
            *{box-sizing:border-box}body{margin:0;background:#f3f6f9;font-family:Arial,sans-serif;color:#334151;min-height:100vh;display:grid;place-items:center;padding:24px}.status-card{width:min(620px,100%);background:#fff;border:1px solid #dfe4ea;padding:42px;text-align:center}.status-card img{max-width:90px;max-height:90px;object-fit:contain;margin-bottom:20px}.status-card h1{font-size:24px;margin:0 0 12px}.status-card p{color:#6c757d;line-height:1.6;margin:0}.status-card small{display:block;margin-top:22px;color:#98a1ab}
        </style>
    </head>
    <body>
        <div class="status-card">
            <?php if (!empty($pengaturan['logo'])): ?><img src="../uploads/logo/<?= rawurlencode(basename((string)$pengaturan['logo'])) ?>" alt="Logo Sekolah"><?php endif; ?>
            <h1><?= htmlspecialchars($judulStatus) ?></h1>
            <p><?= htmlspecialchars($pesanStatus) ?></p>
            <small><?= htmlspecialchars((string)($pengaturan['nama_sekolah'] ?? 'Sekolah')) ?></small>
        </div>
    
    <script>
        (function () {
            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-berita-id]');
                if (!link) return;
                var id = parseInt(link.getAttribute('data-berita-id') || '0', 10);
                if (!id) return;
                try {
                    fetch('track_berita.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                        body: 'id=' + encodeURIComponent(id),
                        keepalive: true
                    }).catch(function () {});
                } catch (error) {}
            }, true);

            if (window.SDS_ANJUNGAN.refreshMs > 0) {
                window.setTimeout(function () { window.location.reload(); }, window.SDS_ANJUNGAN.refreshMs);
            }

            if (window.SDS_ANJUNGAN.idleMs > 0) {
                var idleTimer;
                function returnHome() {
                    try { if (window.Fancybox) Fancybox.close(); } catch (error) {}
                    try {
                        document.querySelectorAll('.modal.show').forEach(function (element) {
                            var instance = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getInstance(element) : null;
                            if (instance) instance.hide();
                        });
                    } catch (error) {}
                    window.location.href = window.location.pathname;
                }
                function resetIdleTimer() {
                    window.clearTimeout(idleTimer);
                    idleTimer = window.setTimeout(returnHome, window.SDS_ANJUNGAN.idleMs);
                }
                ['pointerdown', 'touchstart', 'keydown', 'mousemove'].forEach(function (name) {
                    document.addEventListener(name, resetIdleTimer, {passive: true});
                });
                resetIdleTimer();
            }
        })();
    </script>

</body>
    </html>
    <?php
    exit;
}

$newsPublishedCondition = sdsAnjunganPublishedCondition($conn);
$defaultTheme = in_array($anjunganSettings['tema_default'] ?? '', ['nature','travel','casual'], true)
    ? $anjunganSettings['tema_default']
    : 'nature';
$carouselMs = max(2000, (int)($anjunganSettings['carousel_detik'] ?? 3) * 1000);
$refreshMs = max(0, (int)($anjunganSettings['refresh_menit'] ?? 0) * 60000);
$idleMs = max(0, (int)($anjunganSettings['kembali_home_detik'] ?? 0) * 1000);
?>
<!DOCTYPE html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Anjungan Sekolah">
    <meta name="author" content="Affan">
    <meta name="keywords" content="anjungan, sekolah">

    <title>Anjungan Mandiri</title>
    <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />

    <!-- Fancybox (Versi 5, satu kali saja) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.css" />
    <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui@5.0/dist/fancybox/fancybox.umd.js"></script>

    <!-- Bootstrap & Theme CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/default.css" rel="stylesheet">
    <link href="assets/css/color/nature.css" rel="stylesheet alternate" title="nature" />
    <link href="assets/css/color/travel.css" rel="stylesheet alternate" title="travel" />
    <link href="assets/css/color/casual.css" rel="stylesheet alternate" title="casual" />
    <link href="assets/css/darkmode.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/screen.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">

    <!-- jQuery, Bootstrap, Flickity -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/flickity.js"></script>
    <style>
        .has-iframe .fancybox__content,
        .has-map .fancybox__content,
        .has-pdf .fancybox__content {
            width: 100%;
            height: 100%;
        }

        .fancybox__slide {
            padding: 0;
        }

        .fancybox__content>.f-button.is-close-btn {
            --f-button-width: 34px;
            --f-button-height: 34px;
            --f-button-border-radius: 4px;
            --f-button-color: var(--fancybox-color, #fff);
            --f-button-hover-color: var(--fancybox-color, #fff);
            --f-button-bg: transparent;
            --f-button-hover-bg: transparent;
            --f-button-active-bg: transparent;
            --f-button-svg-width: 0;
            --f-button-svg-height: 0;
            position: absolute;
            left: -5px;
            top: 5%;
            opacity: 1;
            z-index: 1;
            /* Tambahkan di bawah ini */
            background-image: url('assets/exit.png');
            background-size: contain;
            /* atau cover, sesuai kebutuhan */
            background-repeat: no-repeat;
            background-position: center;
            width: 75px;
            height: 75px;
        }
    </style>
</head>


<body>

    <div class="full-container" id="element">

        <?php if (!empty($anjungan['background'])): ?>
            <div class="backg-image"><img src="assets/uploads/background/<?= rawurlencode(basename((string)$anjungan['background'])) ?>" alt="background"></div>
        <?php endif; ?>
        <div class="backg-color"></div>

        <!-- Mulai Header -->
        <div class="anjungan-head plr-master difle-l">
            <!-- Mulai Logo -->
            <a href="">
                <div class="anjungan-head-logo difle-l">
                    <?php if (!empty($pengaturan['logo'])): ?>
                        <img src="../uploads/logo/<?= htmlspecialchars($pengaturan['logo']) ?>" alt="Logo Sekolah">
                    <?php endif; ?>
                    <div>
                        <h1 style="font-size: 25px;"><?= htmlspecialchars($anjungan['nama_anjungan'] ?? '') ?></h1>
                        <p style="font-size:18px"> <?= htmlspecialchars((string)(!empty($pengaturan['nama_sekolah']) ? $pengaturan['nama_sekolah'] : 'Sekolah')) ?>
                        </p>
                    </div>
                </div>
            </a>
            <!-- Batas Logo -->

            <!-- Mulai Icon Kanan -->
            <div class="topright difle-l" style="font-size:20px;">
                <?php while ($row = $topright->fetch_assoc()) : ?>
                    <?php
                    $nama = htmlspecialchars($row['nama']);
                    $deskripsi = htmlspecialchars($row['deskripsi']);
                    $icon_url = htmlspecialchars($row['icon_url']);
                    $link_url = htmlspecialchars($row['link_url']);
                    // $isAbsensi = (strpos($link_url, '../rfid_absen/realtime') !== false);
                    ?>

                    <?php if (in_array($row['tipe'], ['link', 'dropdown'], true)): ?>
                        <?php $openDirect = sdsAnjunganColumnExists($conn, 'anjungan_topright', 'buka_langsung') && (int)($row['buka_langsung'] ?? 0) === 1; ?>
                        <a class="topright-icon radius-4"
                            href="<?= $link_url ?>"
                            <?= $openDirect ? 'target="_blank" rel="noopener"' : 'data-fancybox data-type="iframe" data-preload="false"' ?>
                            title="<?= $deskripsi ?>">
                            <?php if ($icon_url !== ''): ?><img src="assets/uploads/topright/<?= rawurlencode(basename($icon_url)) ?>" alt="<?= $nama ?>"><?php endif; ?>
                            <p style="padding: 0px 10px;"><?= $nama ?></p>
                        </a>

                    <?php elseif ($row['tipe'] === 'modal'): ?>
                        <!-- Modal -->
                        <a class="topright-icon radius-4"
                            data-bs-toggle="modal"
                            data-bs-target="#<?= htmlspecialchars($row['target_modal']) ?>"
                            title="<?= $deskripsi ?>">
                            <?php if ($icon_url !== ''): ?><img src="assets/uploads/topright/<?= rawurlencode(basename($icon_url)) ?>" alt="<?= $nama ?>"><?php endif; ?>
                            <p style="padding: 0px 10px;"><?= $nama ?></p>
                        </a>
                    <?php endif; ?>
                <?php endwhile; ?>


                <div style="position:relative;<?= (int)($anjunganSettings['izinkan_pilih_tema'] ?? 1) === 1 ? '' : 'display:none;' ?>">
                    <div class="topright-icon radius-4" data-bs-toggle="dropdown" title="Pilih Warna Tamu">
                        <div><img src="assets/img/warna.png">
                            <p>Warna<br />Tampilan</p>
                        </div>
                    </div>
                    <div class="dropdown-menu colorstyle" role="menu">
                        <p style="text-align:center;margin:0 auto 15px;"><b>Pilihan Warna</b></p>
                        <div class="colors">
                            <a data-val="nature" href="javascript:void(0);">
                                <div class="changecolor nature difle-l">
                                    <div class="changecolor-box"></div>
                                    <p>Biru & Hijau</p>
                                </div>
                            </a>
                        </div>
                        <div class="colors">
                            <a data-val="travel" href="javascript:void(0);">
                                <div class="changecolor travel difle-l">
                                    <div class="changecolor-box"></div>
                                    <p>Ungu & Pink</p>
                                </div>
                            </a>
                        </div>
                        <div class="colors">
                            <a data-val="casual" href="javascript:void(0);">
                                <div class="changecolor casual difle-l">
                                    <div class="changecolor-box"></div>
                                    <p>Toska & Orange</p>
                                </div>
                            </a>
                        </div>
                        <div class="darklight difle-l" onclick="setDarkMode(true)" id="darkBtn">
                            <div class="darklight-icon radius-4 difle-c"><img src="assets/img/dark.png"></div>
                            <p>Gelapkan Layar</p>
                        </div>
                    </div>
                </div>
                <?php if ((int)($anjunganSettings['tampilkan_fullscreen'] ?? 1) === 1): ?>
                <div class="topright-icon iconhid radius-4" id="openfull" onclick="openFullscreen();" title="Layar Penuh">
                    <div><img src="assets/img/maximize.png">
                        <p>Tampilan<br />Penuh</p>
                    </div>
                </div>
                <div class="topright-icon iconhid radius-4" id="exitfull" onclick="closeFullscreen();">
                    <div><img src="assets/img/minimize.png">
                        <p>Tampilan<br />Normal</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- Batas Icon Kanan -->

        </div>
        <!-- Batas Header -->

        <!-- Mulai Video/Slider, Artikel & Icon Link -->
        <div class="anjungan-middle">
            <div class="anjungan-middle-inner plr-master">
                <div class="grider mainmargin">
                    <!-- Mulai Video/Slider -->
                    <?php if (($anjunganSettings['media_type'] ?? 'video') === 'video' && !empty($anjungan['video'])): ?>
                    <div class="slider-area">
                        <div class="video-container">
                            <iframe class="video-view"
                                src="<?= htmlspecialchars((string)$anjungan['video']) ?><?= strpos((string)$anjungan['video'], '?') === false ? '?' : '&' ?>autoplay=1&mute=1&loop=1"
                                frameborder="0"
                                allow="autoplay; encrypted-media"
                                allowfullscreen>
                            </iframe>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="slider-area">
                        <div class="video-container" style="display:flex;align-items:center;justify-content:center;background:rgba(255,255,255,.12);">
                            <div style="text-align:center;color:#fff;padding:40px;">
                                <h2 style="margin:0 0 10px;"><?= htmlspecialchars((string)($anjungan['nama_anjungan'] ?? 'Anjungan Sekolah')) ?></h2>
                                <p style="margin:0;">Media informasi dan layanan sekolah</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <!-- Mulai Video/Slider -->

                    <!-- Mulai Artikel -->
                    <div class="article-area">
                        <div class="article-head difle-c">
                            <h1 style="margin-bottom:10px">Berita Sekolah</h1>
                        </div>
                        <div class="relhid">
                            <div class="tabs">
                                <input type="radio" id="tab1" name="tab-control" checked>
                                <input type="radio" id="tab2" name="tab-control">
                                <input type="radio" id="tab3" name="tab-control">

                                <ul>
                                    <li>
                                        <label for="tab1" role="button" class="difle-c">
                                            <svg viewBox="0 0 24 24">
                                                <path
                                                    d="M21,16.5C21,16.88 20.79,17.21 20.47,17.38L12.57,21.82C12.41,21.94 12.21,22 12,22C11.79,22 11.59,21.94 11.43,21.82L3.53,17.38C3.21,17.21 3,16.88 3,16.5V7.5C3,7.12 3.21,6.79 3.53,6.62L11.43,2.18C11.59,2.06 11.79,2 12,2C12.21,2 12.41,2.06 12.57,2.18L20.47,6.62C20.79,6.79 21,7.12 21,7.5V16.5M12,4.15L5,8.09V15.91L12,19.85L19,15.91V8.09L12,4.15Z" />
                                            </svg>
                                            <span style="font-size: 17px;">Terbaru</span>
                                        </label>
                                    </li>
                                    <li>
                                        <label for="tab2" role="button" class="difle-c">
                                            <svg viewBox="0 0 24 24">
                                                <path
                                                    d="M12.1,18.55L12,18.65L11.89,18.55C7.14,14.24 4,11.39 4,8.5C4,6.5 5.5,5 7.5,5C9.04,5 10.54,6 11.07,7.36H12.93C13.46,6 14.96,5 16.5,5C18.5,5 20,6.5 20,8.5C20,11.39 16.86,14.24 12.1,18.55M16.5,3C14.76,3 13.09,3.81 12,5.08C10.91,3.81 9.24,3 7.5,3C4.42,3 2,5.41 2,8.5C2,12.27 5.4,15.36 10.55,20.03L12,21.35L13.45,20.03C18.6,15.36 22,12.27 22,8.5C22,5.41 19.58,3 16.5,3Z" />
                                            </svg>
                                            <span style="font-size: 17px;">Populer</span>
                                        </label>
                                    </li>
                                    <li>
                                        <label for="tab3" role="button" class="difle-c">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M3,4H21V6H3V4M3,10H21V12H3V10M3,16H21V18H3V16Z" />
                                            </svg>
                                            <span style="font-size: 17px;">Pengumuman</span>
                                        </label>
                                    </li>

                                </ul>
                                <div class="slider">
                                    <div class="indicator"></div>
                                </div>
                                <div class="content">
                                    <!-- Tab Terbaru -->
                                    <section>
                                        <div class="article-box">
                                            <div id="slide-container">
                                                <div id="slides" style="position:relative;height:600px;overflow:hidden;">
                                                    <?php
                                                    $result = $conn->query("SELECT * FROM anjungan_berita WHERE jenis = 'berita' AND {$newsPublishedCondition} ORDER BY tanggal DESC LIMIT 8");
                                                    $slides = [];
                                                    while ($row = $result->fetch_assoc()) {
                                                        $slides[] = $row;
                                                    }

                                                    for ($i = 0; $i < count($slides); $i += 2):
                                                    ?>
                                                        <article class="featured-article animated" style="position:absolute;width:100%;top:100%;z-index:0;">
                                                            <div class="grider mlr-min5" style="display: flex;">
                                                                <?php for ($j = 0; $j < 2; $j++):
                                                                    if (!isset($slides[$i + $j])) continue;
                                                                    $row = $slides[$i + $j];
                                                                ?>
                                                                    <div class="col-2" style="padding: 10px;">
                                                                        <a href="<?= htmlspecialchars((string)($row['link'] ?: '#')) ?>" data-berita-id="<?= (int)$row['id'] ?>" data-fancybox data-type="iframe" data-preload="false">
                                                                            <div class="card imagecrop-grid" style="border-radius:10px;">
                                                                                <img style="border-radius:10px; width: 100%;" src="../anjungan/assets/uploads/berita/<?= rawurlencode(basename((string)$row['gambar'])) ?>">
                                                                                <div class="text-p posting" style="margin:2px;font-size:12px;border-bottom-left-radius: 10px;border-bottom-right-radius: 10px;">
                                                                                    &emsp;<?= date("d F Y", strtotime($row['tanggal'])) ?>
                                                                                </div>
                                                                            </div>
                                                                            <h2 style="text-align:center;font-weight:bold;font-size:13px">
                                                                                <?= htmlspecialchars(strtoupper((string)$row['judul'])) ?>
                                                                            </h2>
                                                                        </a>
                                                                    </div>
                                                                <?php endfor; ?>
                                                            </div>
                                                        </article>
                                                    <?php endfor; ?>
                                                </div>

                                            </div>
                                        </div>
                                    </section>

                                    <!-- Tab Populer -->
                                    <section>
                                        <div class="article-box">
                                            <div class="marquee-top">
                                                <div class="track-top">
                                                    <?php
                                                    $populer = $conn->query("SELECT * FROM anjungan_berita WHERE jenis = 'berita' AND {$newsPublishedCondition} ORDER BY dilihat DESC, tanggal DESC LIMIT 4");
                                                    while ($row = $populer->fetch_assoc()):
                                                    ?>
                                                        <a href="<?= htmlspecialchars((string)($row['link'] ?: '#')) ?>" data-berita-id="<?= (int)$row['id'] ?>" data-fancybox data-type="iframe" data-preload="false">
                                                            <div class="article-row">
                                                                <div class="relhid mlr-min5">
                                                                    <div class="article-image">
                                                                        <div class="card imagecrop-artikel" style="border-radius:10px">
                                                                            <img class="rounded" src="../anjungan/assets/uploads/berita/<?= rawurlencode(basename((string)$row['gambar'])) ?>">
                                                                        </div>
                                                                    </div>
                                                                    <div class="article-title" style="font-size:14px;font-bold:normal">
                                                                        <p style="margin-bottom: 15px;"><b><?= htmlspecialchars(strtoupper((string)$row['judul'])) ?></b></p>
                                                                        <p style="font-size:12px">
                                                                            <i class="fa fa-eye"></i> <?= number_format($row['dilihat']) ?> kali <br>
                                                                            <i class="fa fa-calendar-o mr-1"></i> <?= date("d F Y", strtotime($row['tanggal'])) ?>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </a>
                                                    <?php endwhile; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </section>

                                    <!-- Tab Pengumuman -->
                                    <section>
                                        <div class="article-box">
                                            <div style="position: absolute;width: 100%;padding: 10px;height: auto;">
                                                <div style="background-color: #ececec;border-radius: 10px;">
                                                    <?php
                                                    $result = $conn->query("SELECT * FROM anjungan_berita WHERE jenis = 'pengumuman' AND {$newsPublishedCondition} ORDER BY tanggal DESC LIMIT 5");
                                                    while ($row = $result->fetch_assoc()):
                                                        $link = $row['link'];
                                                        $isImage = preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $link);
                                                        $type = $isImage ? 'image' : 'iframe';
                                                    ?>
                                                        <div class="announcement-item" style="margin-bottom:15px; padding:10px; border:1px solid #ccc; border-radius:8px;">
                                                            <strong><?= htmlspecialchars(strtoupper((string)$row['judul'])) ?></strong><br>
                                                            <small><?= date("d F Y", strtotime($row['tanggal'])) ?></small><br>
                                                            <a href="<?= htmlspecialchars((string)($link ?: '#')) ?>" data-berita-id="<?= (int)$row['id'] ?>" data-fancybox data-type="<?= $type ?>" data-preload="false">Lihat Selengkapnya</a>
                                                        </div>
                                                    <?php endwhile; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </section>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Mulai Artikel -->

                </div>
                <style>
                    @font-face {
                        font-family: Public Sans;
                        src: url(https://static1.squarespace.com/static/6559118ae845e26d2b81d0a6/t/65593fcd9e8f5c381a4a6255/1700347853092/PublicSans-Bold.ttf);
                    }

                    .menu_link {
                        line-height: 100%;
                        font-family: sans-serif;
                        -webkit-text-size-adjust: 100%;
                        -ms-text-size-adjust: 100%;
                    }
                </style>

                <!-- Mulai Icon Link -->
                <div class="anjungan-bottom" style="font-family:arial">
                    <div class="margin-carousel">
                        <div class="carousel js-flickity" data-flickity='{"pageDots": false, "autoPlay": <?= (int)$carouselMs ?>, "cellAlign": "left", "wrapAround": true }'>
                            <?php
                            $menus = $conn->query("SELECT * FROM anjungan_menu WHERE status = 'aktif' ORDER BY urutan ASC");
                            while ($menu = $menus->fetch_assoc()) {
                                $nama = htmlspecialchars((string)$menu['nama_menu']);
                                $link = htmlspecialchars((string)$menu['link']);
                                $icon = basename((string)$menu['icon']);
                                $imgPath = "assets/uploads/menu/" . rawurlencode($icon);
                                $openExternal = sdsAnjunganColumnExists($conn, 'anjungan_menu', 'jenis_tujuan') && ($menu['jenis_tujuan'] ?? 'iframe') === 'eksternal';
                            ?>
                                <div class="carousel-col">
                                    <a href="<?= $link ?>" <?= $openExternal ? 'target="_blank" rel="noopener"' : 'data-fancybox data-type="iframe" data-preload="false"' ?>>
                                        <div class="icon-stat">
                                            <img src="<?= $imgPath ?>" alt="<?= $nama ?>">
                                            <div class="text-center">
                                                <p class="menu_link" style="padding:5px;font-size:14px;line-height:100%"><?= $nama ?></p>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <!-- Batas Icon Link -->


            </div>
        </div>
        <!-- Batas Slider, Artikel & Icon Link -->

        <!-- Mulai Footer -->
        <div class="bottom-page plr-master">
            <div class="bottom-page-inner">
                <div class="datetime difle-l" <?= (int)($anjunganSettings['tampilkan_jam'] ?? 1) === 1 ? '' : 'style="display:none"' ?>>
                    <div class="datetime-box difle-l">
                        <div id="tanggal"></div>
                        <div id="thistime"></div>
                    </div>
                </div>
                <div class="runtext">
                    <marquee onmouseover="this.stop()" onmouseout="this.start()">MEDIA INFORMASI DAN PUBLIKASI <?= htmlspecialchars((string)(!empty($pengaturan['nama_sekolah']) ? $pengaturan['nama_sekolah'] : 'Sekolah')) ?> </marquee>
                </div>
            </div>
            <footer style="font-size:14px;text-align:center;line-height:100%;">
                © <?= date('Y') ?> <?= htmlspecialchars((string)(!empty($pengaturan['nama_sekolah']) ? $pengaturan['nama_sekolah'] : 'Sekolah')) ?> <div>
                    <small>Develope by <a href="#">
                           PROBO ICT</a>
                    </small>
                </div>
            </footer>
        </div>
        <!-- Batas Footer -->

    </div>


    <div class="modal-custom">
        <div class="modal fade" id="survey" tabindex="-1" role="dialog">
            <div class="modal-dialog" role="document">
                <div class="headmodal difle-c">
                    <h1>Survey Layanan Sekolah</h1>
                </div>
                <?php include 'survey/index.php' ?>
                <div class="footmodal difle-c">
                    <div class="close-modal difle-c" data-bs-dismiss="modal">
                        <svg viewBox="0 0 24 24">
                            <path d="M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z" />
                        </svg>Tutup
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- html -->



    <script>
        var light = 'assets/img/light.png';
        var dark = 'assets/img/dark.png';
        window.SDS_ANJUNGAN = {
            defaultTheme: <?= json_encode($defaultTheme) ?>,
            allowThemePicker: <?= (int)($anjunganSettings['izinkan_pilih_tema'] ?? 1) ?>,
            refreshMs: <?= (int)$refreshMs ?>,
            idleMs: <?= (int)$idleMs ?>
        };

        (function () {
            function applyColorTheme(theme, remember) {
                var allowed = ['nature', 'travel', 'casual'];
                if (allowed.indexOf(theme) < 0) theme = window.SDS_ANJUNGAN.defaultTheme;
                document.querySelectorAll('link[title="nature"],link[title="travel"],link[title="casual"]').forEach(function (link) {
                    link.disabled = link.getAttribute('title') !== theme;
                });
                if (remember && window.SDS_ANJUNGAN.allowThemePicker === 1) {
                    try { localStorage.setItem('anjungan_color_theme', theme); } catch (error) {}
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                var selected = window.SDS_ANJUNGAN.defaultTheme;
                if (window.SDS_ANJUNGAN.allowThemePicker === 1) {
                    try { selected = localStorage.getItem('anjungan_color_theme') || selected; } catch (error) {}
                }
                applyColorTheme(selected, false);
                document.querySelectorAll('[data-val]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        applyColorTheme(button.getAttribute('data-val') || selected, true);
                    });
                });
            });
        })();
    </script>

    <script src="assets/js/support.js"></script>

    <script>
        // Konfigurasi slide manual (tidak diubah)
        var count = -1;
        var slides = jQuery.makeArray($('#slides article')),
            totalSlides = slides.length - 1;
        var startPos = {
                "top": '100%',
                "z-index": "0"
            },
            endPos = {
                'top': '0px',
                "z-index": "2"
            },
            prevPos = {
                'top': '-100%',
                "z-index": "0"
            },
            transit = {
                "transition": "top 800ms ease 0s",
                "transition-delay": "0s"
            },
            nonetrans = {
                "transition": "none"
            },
            timer = null;

        function advance() {
            if (count == totalSlides) {
                $(slides[count]).animate(startPos, 0).css(transit);
                count = 0;
                $(slides[count]).css(prevPos).css(nonetrans);
                $(slides[count]).animate(endPos, 0).css(transit);
            } else {
                $(slides[count]).animate(startPos, 0).css(transit);
                count++;
                $(slides[count]).css(prevPos).css(nonetrans);
                $(slides[count]).animate(endPos, 0).css(transit);
            }
        }

        function rewind() {
            if (count === 0) {
                $(slides[count]).animate(prevPos, 0).css(transit);
                count = totalSlides;
                $(slides[count]).css(startPos).css(nonetrans);
                $(slides[count]).animate(endPos, 0).css(transit);
            } else {
                $(slides[count]).prev().css(startPos).css(nonetrans);
                $(slides[count]).animate(prevPos, 0).css(transit);
                count = count - 1;
                $(slides[count]).animate(endPos, 0).css(transit);
            }
        }

        function selectDots() {
            n = count + 1;
            $('#dots li:nth-child(' + n + ')').addClass('selected');
            $('#dots li:nth-child(' + n + ')').siblings().removeClass('selected');
        }

        function clickDots() {
            $('#dots li').bind('click', function() {
                var index = $(this).index();
                if (count > index) {
                    $(slides[count]).animate(prevPos, 0).css(transit);
                    count = index;
                    $(slides[count]).css(startPos).css(nonetrans);
                    $(slides[count]).animate(endPos, 0).css(transit);
                } else if (count < index) {
                    $(slides[count]).animate(startPos, 0).css(transit);
                    count = index;
                    $(slides[count]).css(prevPos).css(nonetrans);
                    $(slides[count]).animate(endPos, 0).css(transit);
                } else {
                    return false;
                }
                selectDots();
                clearTimeout(timer);
                timer = setTimeout(playSlides, 7500);
                unbindBtn();
            });
        }

        function upDown() {
            $('.next').bind('click', function() {
                advance();
                selectDots();
                clearTimeout(timer);
                timer = setTimeout(playSlides, 7500);
                unbindBtn();
            });
            $('.prev').bind('click', function() {
                if (count == -1) count = 0;
                else rewind();
                selectDots();
                clearTimeout(timer);
                timer = setTimeout(playSlides, 7500);
                unbindBtn();
            });
        }

        function unbindBtn() {
            $('.next,.prev,#dots li').unbind('click');
            setTimeout(upDown, 800);
            setTimeout(clickDots, 800);
        }

        function playSlides() {
            clickDots();
            upDown();

            function loop() {
                advance();
                selectDots();
                timer = setTimeout(loop, 7000);
                unbindBtn();
            }
            loop();
        }

        $(document).ready(function() {
            playSlides();
        });
    </script>

    <!-- Inisialisasi Flickity & aktifkan autoplay ulang setelah Fancybox close -->
    <script>
        let flickityInstance;

        document.addEventListener("DOMContentLoaded", function() {
            const el = document.querySelector('.js-flickity');
            if (el && typeof Flickity !== 'undefined') {
                flickityInstance = new Flickity(el, {
                    pageDots: false,
                    autoPlay: <?= (int)$carouselMs ?>,
                    cellAlign: 'left',
                    wrapAround: true,
                    pauseAutoPlayOnHover: false
                });
            }

            Fancybox.bind("[data-fancybox]", {
                on: {
                    close: () => {
                        if (flickityInstance) {
                            flickityInstance.resize();
                            flickityInstance.playPlayer();
                            console.log("Fancybox ditutup, carousel autoplay dilanjutkan.");
                        }
                    }
                }
            });
        });
    </script>
    <script>
        var elem = document.documentElement;

        function openFullscreen() {
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
            document.getElementById("openfull").style.display = "none";
            document.getElementById("exitfull").style.display = "block";
        }

        function closeFullscreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
            document.getElementById("openfull").style.display = "block";
            document.getElementById("exitfull").style.display = "none";
        }
    </script>


    <script>
  window.addEventListener("message", function(event) {
    // Perintah untuk menyembunyikan tombol close (dikirim dari proses_transaksi.php)
    if (event.data && event.data.hideCloseButton) {
      const closeBtn = document.querySelector(".f-button.is-close-btn");
      if (closeBtn) {
        closeBtn.style.display = "none";
      }
    }

    // Perintah untuk menampilkan kembali tombol close (dikirim dari logout.php)
    if (event.data && event.data.resetCloseButton) {
      const closeBtn = document.querySelector(".f-button.is-close-btn");
      if (closeBtn) {
        closeBtn.style.display = "";
      }
    }
  });
</script>


    <script>
        (function () {
            document.addEventListener('click', function (event) {
                var link = event.target.closest('[data-berita-id]');
                if (!link) return;
                var id = parseInt(link.getAttribute('data-berita-id') || '0', 10);
                if (!id) return;
                try {
                    fetch('track_berita.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                        body: 'id=' + encodeURIComponent(id),
                        keepalive: true
                    }).catch(function () {});
                } catch (error) {}
            }, true);

            if (window.SDS_ANJUNGAN.refreshMs > 0) {
                window.setTimeout(function () { window.location.reload(); }, window.SDS_ANJUNGAN.refreshMs);
            }

            if (window.SDS_ANJUNGAN.idleMs > 0) {
                var idleTimer;
                function returnHome() {
                    try { if (window.Fancybox) Fancybox.close(); } catch (error) {}
                    try {
                        document.querySelectorAll('.modal.show').forEach(function (element) {
                            var instance = window.bootstrap && window.bootstrap.Modal ? window.bootstrap.Modal.getInstance(element) : null;
                            if (instance) instance.hide();
                        });
                    } catch (error) {}
                    window.location.href = window.location.pathname;
                }
                function resetIdleTimer() {
                    window.clearTimeout(idleTimer);
                    idleTimer = window.setTimeout(returnHome, window.SDS_ANJUNGAN.idleMs);
                }
                ['pointerdown', 'touchstart', 'keydown', 'mousemove'].forEach(function (name) {
                    document.addEventListener(name, resetIdleTimer, {passive: true});
                });
                resetIdleTimer();
            }
        })();
    </script>

</body>

</html>