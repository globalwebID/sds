<?php
include_once '../sw-library/sw-config.php';
include_once '../sw-library/sw-function.php';
ob_start("minify_html");

/**
 * UPDATE FULL (PERFORMA + TANPA MARQUEE):
 * 1) HAPUS base64 gambar slider (lebih ringan, input scan lebih responsif)
 * 2) HAPUS base64 ikon/gif (browser bisa cache)
 * 3) MATIKAN marquee berjalan: ganti jadi LIST 5 ABSEN TERAKHIR
 *    - saat ada absen baru: item baru masuk paling atas (naik ke atas), maksimal 5 item
 *
 * NOTE:
 * - Agar list 5 terakhir benar-benar update saat ada absen baru,
 *   panggil: window.pushLatestAbsen("<html item>") dari sw-script.js (bagian sukses absen / update SSE).
 *
 * WAJIB: file endpoint "slider_feed.php" diletakkan di folder yang sama dengan file ini.
 */

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// =======================
// SLIDER (TANPA BASE64)
// =======================
$query_slide  = "SELECT * FROM slider WHERE active='Y' ORDER BY slider_id DESC";
$result_slide = $connection->query($query_slide);

// ===== render item slider (dipakai 1x di awal halaman) =====
$sliderItemsHtml = '';
$defaultSlide = '../template/img/sw-big.jpg';

if ($result_slide && $result_slide->num_rows > 0) {
  $active = 0;
  while ($data_slide = $result_slide->fetch_assoc()) {
    $active++;

    $nama = strip_tags((string)($data_slide['slider_nama'] ?? ''));
    $foto = (string)($data_slide['foto'] ?? '');

    // Pakai URL file langsung (browser bisa cache) + fallback onerror
    $src = $defaultSlide;
    if ($foto !== '') {
      $src = '../sw-content/slider/' . rawurlencode($foto);
    }

    $sliderItemsHtml .= ($active === 1)
      ? '<div class="carousel-item active">'
      : '<div class="carousel-item">';

    $sliderItemsHtml .= '<img
      src="'.h($src).'"
      alt="'.h($nama).'"
      class="d-block w-100"
      loading="lazy"
      decoding="async"
      onerror="this.onerror=null;this.src=\''.h($defaultSlide).'\';"
    >';

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
  body {
    height: 100vh;
    overflow: hidden;
}
    /* ==========================
       HIDE SLIDESHOW ON MAIN VIEW
       ========================== */
    .screen-slider{ display:none !important; }

    /* ==========================
       PREVIEW CAMERA CARD
       ========================== */
    .cam-preview-wrap{
      position: relative;
      width: 100%;
      background: #0b1220;
      border-radius: 14px;
      overflow: hidden;
      aspect-ratio: 4/3;
      box-shadow: 0 10px 25px rgba(0,0,0,.25);
    }
    .cam-preview-wrap video,
    .cam-preview-wrap canvas{
      position:absolute;
      inset:0;
      width:100%;
      height:100%;
      object-fit: cover;
      border:0;
    }
    .cam-preview-wrap canvas{ display:none; }

    .cam-badge{
      position:absolute;
      left:10px;
      top:10px;
      z-index:3;
      background: rgba(0,0,0,.45);
      color:#fff;
      padding:6px 10px;
      border-radius: 999px;
      font-size: 12px;
      letter-spacing: .3px;
      display:flex;
      gap:8px;
      align-items:center;
      backdrop-filter: blur(6px);
    }
    .cam-dot{
      width:8px; height:8px;
      border-radius:999px;
      background:#22c55e;
      box-shadow: 0 0 0 3px rgba(34,197,94,.15);
    }

    /* ==========================
       5 ABSEN TERAKHIR (TANPA MARQUEE)
       ========================== */
    .absen5-wrap{
      max-height: 100%;           /* silakan sesuaikan tinggi card */
      overflow: hidden;
      position: relative;
    }
    .absen5-list{
      display:flex;
      flex-direction:column;
      /*gap:10px;*/
    }
    .absen5-item{
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 12px;
      /*padding: 10px 12px;*/
      color: #fff;
      transform: translateY(0);
      opacity: 1;
      will-change: transform, opacity;
    }
    .absen5-item.is-new{
      animation: absenSlideIn .22s ease-out;
    }
    @keyframes absenSlideIn{
      from{ transform: translateY(18px); opacity: .2; }
      to  { transform: translateY(0);   opacity: 1; }
    }
    .absen5-item *{ max-width:100%; }

    /* ==========================
       SCREEN SAVER (FULLSCREEN SLIDESHOW)
       ========================== */
    .ss-overlay{
      position:fixed; inset:0;
      z-index:999999;
      background:#000;
      display:none;
    }
    .ss-overlay.is-on{ display:block; }
    .ss-overlay .ss-inner{ position:absolute; inset:0; }

    .ss-overlay .carousel,
    .ss-overlay .carousel-inner,
    .ss-overlay .carousel-item{ height:100vh !important; }

    .ss-overlay .carousel-item img{
      width:100vw !important;
      height:100vh !important;
      object-fit:cover;
      display:block;
    }

    .ss-overlay .carousel-control-prev,
    .ss-overlay .carousel-control-next{ opacity:.12; }
  </style>
</head>

<?php if (($row_site['tipe_absen_layar'] ?? '') === 'qrcode-webcame'): ?>
  <body onload="qrcode_webcame()">
<?php else: ?>
  <body onload="webcame_selfie()">
<?php endif; ?>

<span class="latitude d-none"></span>

<header class="header">
  <div class="conatiner-fluid">
    <div class="row">
      <div class="col-md-6">
        <div class="col align-self-left logo-header">
          <span><?= h(format_hari_tanggal($date)) ?></span>
        </div>
      </div>
      <div class="col-md-6">
        <div class="col align-self-left">
          <span class="clock"></span>
        </div>
      </div>
    </div>
  </div>
</header>

<main class="flex-shrink-0 main has-footer s-widodo.com mt-2">
  <div class="section mt-2">
    <div class="container-fluid mb-2">
      <div class="row">

        <div class="col-md-3">

          <div class="card">
            <div class="card-body text-center" style="display:none">

              <!-- SLIDESHOW DISIMPAN DI DOM (hidden) untuk dipakai screensaver -->
              <div class="screen-slider" aria-hidden="true">
                <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel" data-interval="5000">
                  <div class="carousel-inner">
                    <?= $sliderItemsHtml ?>
                  </div>

                  <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                  </a>
                  <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                  </a>
                </div>
              </div>
              <!-- /SLIDESHOW hidden -->

            </div>
          </div>

          <div class="card">
            <div class="card-body pt-4 pl-4 pr-4 text-center">

              <?php if (($row_site['tipe_absen_layar'] ?? '') === 'qrcode'): ?>
                <h3>Cukup scan QR Code dengan mesin scanner dan biarkan wajah Anda tertangkap secara otomatis</h3>

                <div class="aniamed-scanner">
                  <img src="../template/img/qr-code.gif" class="imaged-scanner mt-2 bm-2" alt="scanner">
                </div>

                <input type="text" name="qrcode" class="form-control qrcode bg-white" required>

              <?php elseif (($row_site['tipe_absen_layar'] ?? '') === 'rfid'): ?>
                <h1><b>Dekatkan KARTU PELAJAR Anda pada alat Pembaca, dan proses Absensi akan berjalan otomatis</b></h1>

                <div class="aniamed-scanner">
                  <img src="../template/img/siprobo-melati.png" class="imaged-scanner bm-2" style="display:none;" alt="logo">
                </div>

                <input type="text" name="qrcode" class="form-control qrcode bg-white" required>

              <?php else: ?>
                <h3>Arahkan kamera Anda ke QR Code untuk memindai</h3>
                <div class="webcame text-center">
                  <div id="reader"></div>
                </div>
              <?php endif; ?>

            </div>
          </div>

<?php if (($row_site['tipe_absen_layar'] ?? '') !== 'qrcode-webcame'): ?>
          <div class="card mt-2">
            <div class="card-body p-3">
              <div class="d-flex align-items-center justify-content-between mb-2">
                <div>
                  <strong style="font-size:14px">Preview Kamera</strong>
                  <div class="text-muted" style="font-size:12px; line-height:1.2">
                    Pastikan wajah terlihat jelas
                  </div>
                </div>
                <span class="badge badge-success">LIVE</span>
              </div>

              <div class="cam-preview-wrap">
                <div class="cam-badge">
                  <span class="cam-dot"></span>
                  <span>Kamera aktif</span>
                </div>

                <video id="webcam" autoplay playsinline width="640" height="480"></video>
                <canvas id="canvas"></canvas>
              </div>
            </div>
          </div>
<?php endif; ?>

        </div>

        <div class="col-md-6">
          <div class="card">
            <div class="card-body card-body-absensi">
              <h3>Absensi terbaru</h3>
              <hr>

              <!-- DULU MARQUEE BERJALAN -> SEKARANG LIST 5 TERAKHIR -->
              <div class="absen5-wrap">
                <div class="absen5-list" id="absen5List">
                  <!-- item diisi oleh sw-script.js melalui window.pushLatestAbsen(...) -->
                </div>
              </div>

            </div>
          </div>

          

        </div>
        
        <div class="col-md-3">
            <div class="transactions">
                <div class="card border-0 mb-2 bg-warning">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="../template/img/icons/003-profile.png" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">Total Siswa</strong>
                        <p class="text-white total-siswa">0</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card border-0 mb-2 bg-danger">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="../template/img/icons/002-sand-clock.png" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">Belum Absen</strong>
                        <p class="text-white belum-absen">0</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card border-0 mb-2 bg-primary">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="../template/img/icons/007-insight.png" alt="img" class="image-block imaged w36">
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
                <div class="card border-0 mb-2 bg-secondary">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="../template/img/icons/005-clipboard.png" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">On Time</strong>
                        <p class="text-white ontime">0</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card border-0 mb-2 bg-danger">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="../template/img/icons/004-time.png" alt="img" class="image-block imaged w36">
                        </div>
                      </div>
                      <div class="col align-self-center">
                        <strong class="text-white">Terlambat</strong>
                        <p class="text-white terlambat">0</p>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card border-0 mb-1 bg-info">
                  <div class="card-body">
                    <div class="row align-items-center">
                      <div class="col-auto pr-0">
                        <div class="avatar avatar-50 border-0 text-default">
                          <img src="../template/img/icons/002-verified.png" alt="img" class="image-block imaged w36">
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
  </div>
</main>

<footer class="footer">
  <div class="marquee-left">
    <p>Selamat Datang di <?= h($row_site['nama_sekolah'] ?? '') ?> </p>
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
/**
 * LIST 5 ABSEN TERAKHIR
 * - item baru masuk posisi paling atas
 * - list max 5
 *
 * Cara pakai dari sw-script.js:
 *   window.pushLatestAbsen("<div>...html item...</div>");
 *
 * Atau kalau Anda punya data JSON:
 *   window.pushLatestAbsen(window.renderAbsenItem(data));
 */
(function(){
  "use strict";

  var MAX_ITEMS = 8;
  var listEl = document.getElementById("absen5List");

  function normalizeToItem(html) {
    if (typeof html === "string" && html.indexOf("absen5-item") !== -1) return html;
    return '<div class="absen5-item">' + (html || "") + '</div>';
  }

  window.pushLatestAbsen = function(htmlItem){
    if (!listEl) return;

    var html = normalizeToItem(htmlItem);

    var tmp = document.createElement("div");
    tmp.innerHTML = html.trim();
    var node = tmp.firstElementChild;
    if (!node) return;

    node.classList.add("is-new");
    listEl.insertBefore(node, listEl.firstChild);

    while (listEl.children.length > MAX_ITEMS) {
      listEl.removeChild(listEl.lastElementChild);
    }

    setTimeout(function(){ node.classList.remove("is-new"); }, 400);
  };

  // Optional helper kalau Anda ingin push berdasarkan JSON
  window.renderAbsenItem = function(d){
    var nama = (d && (d.nama || d.nama_lengkap)) ? (d.nama || d.nama_lengkap) : "";
    var kelas = (d && d.kelas) ? d.kelas : "";
    var jam  = (d && (d.jam || d.waktu)) ? (d.jam || d.waktu) : "";
    var status = (d && d.status) ? d.status : "";

    return (
      '<div class="absen5-item">' +
        '<div style="display:flex;justify-content:space-between;gap:10px;">' +
          '<div>' +
            '<div style="font-weight:700;font-size:14px;line-height:1.2;">'+ esc(nama) +'</div>' +
            '<div style="opacity:.75;font-size:12px;">'+ esc(kelas) +' • '+ esc(status) +'</div>' +
          '</div>' +
          '<div style="font-weight:700;font-size:13px;white-space:nowrap;">'+ esc(jam) +'</div>' +
        '</div>' +
      '</div>'
    );
  };

  function esc(s){
    return String(s || "")
      .replaceAll("&","&amp;")
      .replaceAll("<","&lt;")
      .replaceAll(">","&gt;")
      .replaceAll('"',"&quot;")
      .replaceAll("'","&#039;");
  }
})();
</script>

<script>
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

<style>
#ssOverlay .ss-manual, #ssOverlay .ss-manual img{
  width:100vw; height:100vh;
}
#ssOverlay .ss-manual img{
  object-fit:cover;
  display:block;
}
</style>

</body>
</html>
