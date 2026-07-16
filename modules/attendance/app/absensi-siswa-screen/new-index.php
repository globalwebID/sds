<?php
include_once '../sw-library/sw-config.php';
include_once '../sw-library/sw-function.php';
ob_start("minify_html");

/**
 * KIOSK UI REFRESH (tanpa mengurangi fungsi)
 * - Layout anjungan lebih modern + tombol fullscreen
 * - Kamera preview lebih “kiosk”
 * - Absensi terbaru & counters lebih rapi
 * - Screensaver slideshow tetap: idle 10 detik + auto update feed
 *
 * WAJIB: slider_feed.php tetap ada di folder yang sama dengan file ini.
 */

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$query_slide  = "SELECT * FROM slider WHERE active='Y' ORDER BY slider_id DESC";
$result_slide = $connection->query($query_slide);

// ===== render item slider (dipakai 1x di awal halaman) =====
$sliderItemsHtml = '';
if ($result_slide && $result_slide->num_rows > 0) {
  $active = 0;
  while ($data_slide = $result_slide->fetch_assoc()) {
    $active++;
    $sliderItemsHtml .= ($active === 1) ? '<div class="carousel-item active">' : '<div class="carousel-item">';

    $nama = strip_tags((string)($data_slide['slider_nama'] ?? ''));
    $foto = (string)($data_slide['foto'] ?? '');
    $path = '../sw-content/slider/'.$foto;

    if ($foto === '' || !is_file($path)) {
      $sliderItemsHtml .= '<img src="../template/img/sw-big.jpg" alt="'.h($nama).'" class="d-block w-100">';
    } else {
      $sliderItemsHtml .= '<img src="data:image/png;base64,'.base64_encode(file_get_contents($path)).'" alt="'.h($nama).'" class="d-block w-100">';
    }

    $sliderItemsHtml .= '</div>';
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title><?= h(strip_tags($site_name)) ?></title>
  <meta name="description" content="<?= h($site_name) ?>">
  <meta name="author" content="s-widodo.com">
  <meta name="robots" content="noindex,nofollow">

  <link rel="icon" href="../sw-content/<?= h($site_favicon) ?>" type="image/png">

  <link rel="stylesheet" href="../template/css/style.css">
  <link rel="stylesheet" href="../template/css/sw-custom.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <link href="../template/vendor/fontawesome/css/all.min.css" rel="stylesheet">

  <link rel="stylesheet" href="./main.css">
  <link rel="stylesheet" href="../template/vendor/webcame/webcam.css">

  <style>
    :root{
      --brand:#e11d48;        /* merah */
      --brand2:#9ca34c;       /* hijau zaitun */
      --bg0:#070A12;
      --bg1:#0B1220;
      --card: rgba(255,255,255,.08);
      --card2: rgba(255,255,255,.06);
      --line: rgba(255,255,255,.10);
      --text:#E5E7EB;
      --muted:#A1A1AA;
      --shadow: 0 14px 40px rgba(0,0,0,.35);
    }

    body{
        display: grid;
      background:
        radial-gradient(1200px 600px at 15% 10%, rgba(225,29,72,.22), transparent 55%),
        radial-gradient(900px 500px at 85% 15%, rgba(156,163,76,.18), transparent 55%),
        radial-gradient(900px 600px at 50% 90%, rgba(59,130,246,.10), transparent 55%),
        linear-gradient(180deg, var(--bg0), var(--bg1));
      color:var(--text);
    }

    /* ==========================
       HEADER KIOSK
       ========================== */
    .kiosk-header{
      position:sticky;
      top:0;
      z-index:50;
      backdrop-filter: blur(10px);
      background: rgba(7,10,18,.55);
      border-bottom: 1px solid rgba(255,255,255,.08);
      padding: 14px 18px;
    }
    .kiosk-header .wrap{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:14px;
    }
    .kiosk-title{
      display:flex;
      gap:12px;
      align-items:center;
      min-width:0;
    }
    .kiosk-badge{
      width:42px;height:42px;
      border-radius:14px;
      background: linear-gradient(135deg, rgba(225,29,72,.95), rgba(225,29,72,.35));
      box-shadow: 0 10px 26px rgba(225,29,72,.25);
      display:flex;
      align-items:center;
      justify-content:center;
      flex:0 0 auto;
    }
    .kiosk-badge i{ color:#fff; font-size:18px; }
    .kiosk-title .txt{
      min-width:0;
      line-height:1.1;
    }
    .kiosk-title .txt strong{
      display:block;
      font-size:16px;
      letter-spacing:.2px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .kiosk-title .txt span{
      display:block;
      font-size:12px;
      color:var(--muted);
      margin-top:3px;
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .kiosk-right{
      display:flex;
      align-items:center;
      gap:12px;
      flex:0 0 auto;
    }
    .kiosk-clock{
      text-align:right;
      line-height:1;
    }
    .kiosk-clock .clock{
      font-size:22px;
      font-weight:900;
      letter-spacing:.6px;
      color:#fff;
    }
    .kiosk-clock .date{
      font-size:12px;
      color:var(--muted);
      margin-top:6px;
    }
    .btn-full{
      border:1px solid rgba(255,255,255,.14);
      background: rgba(255,255,255,.06);
      color:#fff;
      padding:10px 12px;
      border-radius:14px;
      cursor:pointer;
      font-weight:800;
      display:flex;
      align-items:center;
      gap:8px;
    }
    .btn-full:active{ transform: scale(.99); }

    /* ==========================
       MAIN GRID
       ========================== */
    main.main{ padding-top: 10px; }
    .kiosk-grid{
      display:grid;
      grid-template-columns: 420px 1fr;
      gap:14px;
    }
    @media (max-width: 992px){
      .kiosk-grid{ grid-template-columns: 1fr; }
    }

    /* ==========================
       CARD GLASS
       ========================== */
    .kcard{
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 18px;
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    .kcard .khead{
      padding:14px 14px 10px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      border-bottom:1px solid rgba(255,255,255,.08);
      background: rgba(255,255,255,.03);
    }
    .kcard .khead strong{
      font-size:13px;
      letter-spacing:.2px;
    }
    .kcard .kbody{ padding:14px; }

    .hint{
      color: var(--muted);
      font-size:12px;
      line-height:1.35;
    }
    .pill{
      display:inline-flex;
      align-items:center;
      gap:8px;
      padding:8px 10px;
      border-radius:999px;
      background: rgba(34,197,94,.14);
      border:1px solid rgba(34,197,94,.22);
      color:#d1fae5;
      font-size:12px;
      font-weight:900;
      white-space:nowrap;
    }
    .dot{
      width:8px;height:8px;border-radius:99px;
      background:#22c55e;
      box-shadow:0 0 0 4px rgba(34,197,94,.14);
    }

    /* ==========================
       CAMERA PREVIEW
       ========================== */
    .cam-wrap{
      position:relative;
      width:100%;
      border-radius: 18px;
      overflow:hidden;
      aspect-ratio: 4/3;
      background:
        radial-gradient(600px 240px at 30% 20%, rgba(225,29,72,.20), transparent 60%),
        radial-gradient(600px 240px at 70% 20%, rgba(156,163,76,.12), transparent 60%),
        #05070d;
      border:1px solid rgba(255,255,255,.10);
      box-shadow: 0 16px 36px rgba(0,0,0,.35);
    }
    .cam-wrap video,
    .cam-wrap canvas{
      position:absolute;
      inset:0;
      width:100%;
      height:100%;
      object-fit:cover;
      border:0;
    }
    .cam-wrap canvas{ display:none; }
    .cam-top{
      position:absolute;
      top:10px; left:10px; right:10px;
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
      z-index:3;
      pointer-events:none;
    }
    .cam-top .pill{ pointer-events:none; }
    .frame{
      position:absolute; inset:0;
      border-radius:18px;
      box-shadow: inset 0 0 0 2px rgba(225,29,72,.20),
                  inset 0 0 0 8px rgba(0,0,0,.12);
      pointer-events:none;
    }

    /* ==========================
       INPUT SCAN
       ========================== */
    .scan-input{
      width:100%;
      border-radius: 16px !important;
      border: 1px solid rgba(255,255,255,.14) !important;
      background: rgba(0,0,0,.22) !important;
      color:#fff !important;
      font-weight:800;
      letter-spacing:.3px;
      height: 52px;
      text-align:center;
    }
    .scan-input:focus{
      outline:none !important;
      box-shadow: 0 0 0 4px rgba(225,29,72,.22) !important;
      border-color: rgba(225,29,72,.65) !important;
    }

    /* ==========================
       ABSENSI TERBARU
       ========================== */
    .absensi-box{
      position:relative;
    }
    .absensi-title{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:10px;
    }
    .absensi-title h3{
      margin:0;
      font-size:14px;
      font-weight:900;
    }
    .absensi-title .mini{
      font-size:12px;
      color: var(--muted);
    }
    .marquee-container{
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,.10);
      background: rgba(0,0,0,.18);
      padding: 10px 12px;
      min-height: 250px;
    }

    /* ==========================
       COUNTERS (lebih modern)
       ========================== */
    .kcounter-grid .card{
      background: rgba(255,255,255,.06) !important;
      border: 1px solid rgba(255,255,255,.10) !important;
      border-radius: 18px !important;
      box-shadow: 0 14px 34px rgba(0,0,0,.28);
      overflow:hidden;
    }
    .kcounter-grid strong{ letter-spacing:.2px; }
    .kcounter-grid p{ font-size:22px; font-weight:900; margin:6px 0 0; }

    /* ==========================
       FOOTER MARQUEE
       ========================== */
    footer.footer{
      background: transparent !important;
      border-top: 1px solid rgba(255,255,255,.08);
      backdrop-filter: blur(10px);
    }

    /* ==========================
       HIDE SLIDESHOW ON MAIN VIEW
       ========================== */
    .screen-slider{ display:none !important; }

    /* ==========================
       SCREEN SAVER OVERLAY
       ========================== */
    .ss-overlay{
      position:fixed; inset:0;
      z-index:999999;
      background:#000;
      display:none;
    }
    .ss-overlay.is-on{ display:block; }
    .ss-overlay .ss-inner{ position:absolute; inset:0; }

    #ssOverlay .ss-manual, #ssOverlay .ss-manual img{
      width:100vw; height:100vh;
    }
    #ssOverlay .ss-manual img{
      object-fit:cover;
      display:block;
    }
  </style>
</head>

<?php if (($row_site['tipe_absen_layar'] ?? '') === 'qrcode-webcame'): ?>
  <body onload="qrcode_webcame()">
<?php else: ?>
  <body onload="webcame_selfie()">
<?php endif; ?>

<span class="latitude d-none"></span>

<!-- KIOSK HEADER -->
<div class="kiosk-header">
  <div class="wrap">
    <div class="kiosk-title">
      <div class="kiosk-badge"><i class="fa-solid fa-fingerprint"></i></div>
      <div class="txt">
        <strong>Absensi Anjungan • <?= h($row_site['nama_sekolah'] ?? '') ?></strong>
        <span><?= h(format_hari_tanggal($date)) ?></span>
      </div>
    </div>

    <div class="kiosk-right">
      <div class="kiosk-clock">
        <div class="clock"></div>
        <div class="date">Pastikan kartu/QR terbaca dengan jelas</div>
      </div>
      <button type="button" class="btn-full" id="btnFullscreen" title="Mode layar penuh">
        <i class="fa-solid fa-expand"></i> Fullscreen
      </button>
    </div>
  </div>
</div>

<main class="flex-shrink-0 main has-footer s-widodo.com">
  <div class="section">
    <div class="container-fluid">
      <div class="kiosk-grid">

        <!-- LEFT PANEL -->
        <div>
          <!-- INSTRUKSI -->
          <div class="kcard mb-2">
            <div class="khead">
              <strong><i class="fa-solid fa-circle-info"></i> Petunjuk Absensi</strong>
              <span class="pill"><span class="dot"></span> SISTEM AKTIF</span>
            </div>
            <div class="kbody">
              <?php if (($row_site['tipe_absen_layar'] ?? '') === 'rfid'): ?>
                <div style="font-weight:900; font-size:16px; line-height:1.25; margin-bottom:8px;">
                  Dekatkan <span style="color:#fff;background:rgba(225,29,72,.22);border:1px solid rgba(225,29,72,.35);padding:2px 8px;border-radius:999px;">Kartu Pelajar</span>
                  pada alat pembaca.
                </div>
                <div class="hint">Pastikan wajah terlihat jelas di kamera. Proses absensi berjalan otomatis.</div>
              <?php elseif (($row_site['tipe_absen_layar'] ?? '') === 'qrcode'): ?>
                <div style="font-weight:900; font-size:16px; line-height:1.25; margin-bottom:8px;">
                  Scan <span style="color:#fff;background:rgba(225,29,72,.22);border:1px solid rgba(225,29,72,.35);padding:2px 8px;border-radius:999px;">QR Code</span>
                  dengan scanner.
                </div>
                <div class="hint">Tahan posisi sejenak agar wajah tertangkap otomatis.</div>
              <?php else: ?>
                <div style="font-weight:900; font-size:16px; line-height:1.25; margin-bottom:8px;">
                  Arahkan kamera ke <span style="color:#fff;background:rgba(225,29,72,.22);border:1px solid rgba(225,29,72,.35);padding:2px 8px;border-radius:999px;">QR Code</span>.
                </div>
                <div class="hint">Pastikan QR masuk bingkai dan tidak blur.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- CAMERA PREVIEW -->
          <?php if (($row_site['tipe_absen_layar'] ?? '') !== 'qrcode-webcame'): ?>
          <div class="kcard mb-2">
            <div class="khead">
              <strong><i class="fa-solid fa-video"></i> Preview Kamera</strong>
              <span class="pill"><span class="dot"></span> LIVE</span>
            </div>
            <div class="kbody">
              <div class="cam-wrap">
                <div class="cam-top">
                  <span class="pill" style="background:rgba(0,0,0,.28);border:1px solid rgba(255,255,255,.10);color:#fff;">
                    <i class="fa-solid fa-face-smile"></i> Pastikan wajah terlihat
                  </span>
                  <span class="pill" style="background:rgba(225,29,72,.18);border:1px solid rgba(225,29,72,.26);color:#ffe4ea;">
                    <i class="fa-solid fa-bolt"></i> Otomatis
                  </span>
                </div>

                <!-- PENTING: ID tetap -->
                <video id="webcam" autoplay playsinline width="640" height="480"></video>
                <canvas id="canvas"></canvas>
                <div class="frame"></div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- SCAN BOX / READER -->
          <div class="kcard">
            <div class="khead">
              <strong><i class="fa-solid fa-qrcode"></i> Area Scan</strong>
              <span class="hint">Scan akan diproses otomatis</span>
            </div>
            <div class="kbody text-center">

              <?php if (($row_site['tipe_absen_layar'] ?? '') === 'qrcode'): ?>
                <div class="aniamed-scanner" style="margin-bottom:10px;">
                  <img src="data:image/png;base64,<?= base64_encode(file_get_contents('../template/img/qr-code.gif')) ?>" class="imaged-scanner mt-2 bm-2">
                </div>
                <input type="text" name="qrcode" class="form-control qrcode scan-input" placeholder="Scan QR di sini..." required>

              <?php elseif (($row_site['tipe_absen_layar'] ?? '') === 'rfid'): ?>
                <div style="display:flex;justify-content:center;gap:10px;align-items:center;margin-bottom:10px;">
                  <div style="width:44px;height:44px;border-radius:16px;background:rgba(225,29,72,.20);border:1px solid rgba(225,29,72,.30);display:flex;align-items:center;justify-content:center;">
                    <i class="fa-solid fa-id-card" style="color:#fff"></i>
                  </div>
                  <div style="text-align:left;">
                    <div style="font-weight:900;color:#fff">Tempelkan Kartu Pelajar</div>
                    <div class="hint">Tunggu bunyi/baca RFID lalu kamera mengambil foto</div>
                  </div>
                </div>
                <input type="text" name="qrcode" class="form-control qrcode scan-input" placeholder="Tempel kartu untuk absen..." required>

              <?php else: ?>
                <div class="webcame text-center">
                  <div id="reader"></div>
                </div>
              <?php endif; ?>

              <!-- SLIDESHOW DISIMPAN DI DOM (hidden) untuk dipakai screensaver -->
              <div class="screen-slider" aria-hidden="true">
                <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel" data-interval="5000">
                  <div class="carousel-inner">
                    <?= $sliderItemsHtml ?>
                  </div>

                  <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev" tabindex="-1" aria-hidden="true">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                  </a>
                  <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next" tabindex="-1" aria-hidden="true">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                  </a>
                </div>
              </div>
              <!-- /SLIDESHOW hidden -->
            </div>
          </div>
        </div>

        <!-- RIGHT PANEL -->
        <div>
          <!-- ABSENSI TERBARU -->
          <div class="kcard absensi-box">
            <div class="khead absensi-title">
              <strong><i class="fa-solid fa-list-check"></i> Absensi Terbaru</strong>
              <span class="mini">Live update</span>
            </div>
            <div class="kbody">
              <div class="marquee-container">
                <div class="data-absensi marquee"></div>
              </div>
            </div>
          </div>

          <!-- COUNTERS -->
          <div class="transactions mt-3">
            <div class="row data-counter-left kcounter-grid">

              <div class="col-md-4">
                <div class="card border-0 mb-2 bg-warning">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="data:image/png;base64,<?= base64_encode(file_get_contents('../template/img/icons/003-profile.png')) ?>" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">Total Siswa</strong>
                        <p class="text-white total-siswa">0</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card border-0 mb-2 bg-danger">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="data:image/png;base64,<?= base64_encode(file_get_contents('../template/img/icons/002-sand-clock.png')) ?>" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">Belum Absen</strong>
                        <p class="text-white belum-absen">0</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card border-0 mb-2 bg-primary">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="data:image/png;base64,<?= base64_encode(file_get_contents('../template/img/icons/007-insight.png')) ?>" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">Total Absen</strong>
                        <p class="text-white"><span class="total-absen">0</span>
                          <small class="text-white">
                            <span class="material-icons ml-3" style="font-size:15px">show_chart</span>
                            <span class="persentase ml-1">0</span>%
                          </small>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card border-0 mb-2 bg-secondary">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="data:image/png;base64,<?= base64_encode(file_get_contents('../template/img/icons/005-clipboard.png')) ?>" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">On Time</strong>
                        <p class="text-white ontime">0</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card border-0 mb-2 bg-danger">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="data:image/png;base64,<?= base64_encode(file_get_contents('../template/img/icons/004-time.png')) ?>" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">Terlambat</strong>
                        <p class="text-white terlambat">0</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-4">
                <div class="card border-0 mb-1 bg-info">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="data:image/png;base64,<?= base64_encode(file_get_contents('../template/img/icons/002-verified.png')) ?>" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">Izin</strong>
                        <p class="text-white izin">0</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>

        </div>

      </div><!-- /kiosk-grid -->
    </div>
  </div>
</main>

<footer class="footer">
  <div class="marquee-left">
    <p>Selamat Datang di <?= h($row_site['nama_sekolah'] ?? '') ?> • Gunakan Anjungan Absensi dengan tertib</p>
  </div>
</footer>

<div class="appBottomMenu d-none bg-primary">
  <span class="credits">
    <a class="credits_a" id="mycredit" href="https://s-widodo.com" target="_blank">S-widodo.com</a>
  </span>
</div>

<!-- SCREEN SAVER OVERLAY -->
<div id="ssOverlay" class="ss-overlay" aria-hidden="true">
  <div id="ssInner" class="ss-inner"></div>
</div>

<script src="../sw-library/bundle.min.php?get=s-widodo.com"></script>
<script src="./sw-script.js"></script>

<script>
/* ============================================================
   FULLSCREEN BUTTON (KIOSK)
============================================================ */
(function(){
  var btn = document.getElementById('btnFullscreen');
  if(!btn) return;

  function isFs(){
    return !!(document.fullscreenElement || document.webkitFullscreenElement);
  }
  function reqFs(){
    var el = document.documentElement;
    if (el.requestFullscreen) return el.requestFullscreen();
    if (el.webkitRequestFullscreen) return el.webkitRequestFullscreen();
  }
  function exitFs(){
    if (document.exitFullscreen) return document.exitFullscreen();
    if (document.webkitExitFullscreen) return document.webkitExitFullscreen();
  }

  btn.addEventListener('click', function(){
    try{
      if(isFs()) exitFs();
      else reqFs();
    }catch(e){}
  });

  function syncLabel(){
    btn.innerHTML = isFs()
      ? '<i class="fa-solid fa-compress"></i> Exit'
      : '<i class="fa-solid fa-expand"></i> Fullscreen';
  }
  document.addEventListener('fullscreenchange', syncLabel);
  document.addEventListener('webkitfullscreenchange', syncLabel);
  syncLabel();
})();
</script>

<script>
/* ============================================================
   SCREEN SAVER (idle + auto update)
============================================================ */
(function () {
  "use strict";

  var IDLE_MS  = 10000;
  var SLIDE_MS = 5000;
  var POLL_MS  = 15000;
  var FEED_URL = "./slider_feed.php";

  var overlay = document.getElementById("ssOverlay");
  var inner   = document.getElementById("ssInner");

  var idleTimer   = null;
  var pollTimer   = null;
  var slideTimer  = null;
  var lastVersion = "";
  var slides      = [];
  var slideIndex  = 0;
  var lastUserActivityAt = Date.now();

  function overlayOn() {
    return overlay && overlay.classList.contains("is-on");
  }

  function armTimer() {
    if (idleTimer) clearTimeout(idleTimer);
    idleTimer = setTimeout(showSaver, IDLE_MS);
  }

  function stopManualSlide() {
    if (slideTimer) clearInterval(slideTimer);
    slideTimer = null;
  }

  function hideSaver() {
    if (!overlay || !inner || !overlayOn()) return;

    stopManualSlide();
    overlay.classList.remove("is-on");
    overlay.setAttribute("aria-hidden", "true");
    inner.innerHTML = "";
  }

  function onUserActivity() {
    lastUserActivityAt = Date.now();

    if (overlayOn()) hideSaver();
    armTimer();
  }

  async function fetchFeed() {
    try {
      var res = await fetch(FEED_URL, { cache: "no-store" });
      return await res.json();
    } catch (e) {
      return null;
    }
  }

  function parseSlidesFromHtml(html) {
    var tmp = document.createElement("div");
    tmp.innerHTML = html || "";

    var imgs = tmp.querySelectorAll(".carousel-item img");
    var out = [];
    imgs.forEach(function (img) {
      var src = img.getAttribute("src") || "";
      var alt = img.getAttribute("alt") || "";
      if (src) out.push({ src: src, alt: alt });
    });
    return out;
  }

  function renderSaver(slidesArr) {
    if (!overlay || !inner) return;

    slides = slidesArr || [];
    slideIndex = 0;

    if (!slides.length) return;

    inner.innerHTML = '<div class="ss-manual"><img id="ssImg" alt=""></div>';

    var img = document.getElementById("ssImg");
    if (!img) return;

    img.src = slides[0].src;
    img.alt = slides[0].alt || "";

    overlay.classList.add("is-on");
    overlay.setAttribute("aria-hidden", "false");

    startManualSlide();
  }

  function startManualSlide() {
    stopManualSlide();

    slideTimer = setInterval(function () {
      if (!overlayOn()) return;
      if (!slides.length) return;

      slideIndex = (slideIndex + 1) % slides.length;

      var img = document.getElementById("ssImg");
      if (!img) return;

      img.src = slides[slideIndex].src;
      img.alt = slides[slideIndex].alt || "";
    }, SLIDE_MS);
  }

  async function showSaver() {
    if (!overlay || !inner || overlayOn()) return;
    if ((Date.now() - lastUserActivityAt) < IDLE_MS - 50) return;

    var feed = await fetchFeed();

    if (!feed || !feed.ok) {
      if (slides && slides.length) renderSaver(slides);
      return;
    }

    var freshSlides = parseSlidesFromHtml(feed.html || "");
    if (!freshSlides.length) return;

    slides = freshSlides;
    lastVersion = feed.version || lastVersion;
    renderSaver(freshSlides);
  }

  async function pollSlider() {
    var feed = await fetchFeed();
    if (!feed || !feed.ok) return;

    var ver = feed.version || "";
    var parsed = parseSlidesFromHtml(feed.html || "");

    if (!lastVersion) {
      lastVersion = ver;
      slides = parsed;
      return;
    }

    if (ver && ver !== lastVersion) {
      lastVersion = ver;
      slides = parsed;

      if (overlayOn()) {
        renderSaver(slides);
      }
    }
  }

  function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(pollSlider, POLL_MS);
    pollSlider();
  }

  var events = ["mousemove","mousedown","touchstart","touchmove","keydown","click"];
  events.forEach(function (ev) {
    document.addEventListener(ev, onUserActivity, { passive: true });
  });

  if (overlay) overlay.addEventListener("click", onUserActivity, { passive: true });

  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) onUserActivity();
  });

  startPolling();
  armTimer();
})();
</script>

</body>
</html>
