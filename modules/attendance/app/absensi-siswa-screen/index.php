<?php include_once '../sw-library/sw-config.php';include_once '../sw-library/sw-function.php';ob_start("minify_html");function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');}$PAGE_REQUIRE_KEY=false;$KIOSK_DID_PARAM='did';$KIOSK_KEY_PARAM='k';$KIOSK_LABEL_PARAM='label';$did=strtoupper(trim((string)($_GET[$KIOSK_DID_PARAM]?? '')));$key=(string)($_GET[$KIOSK_KEY_PARAM]?? '');$label=trim((string)($_GET[$KIOSK_LABEL_PARAM]?? ''));if($PAGE_REQUIRE_KEY){if($did===''||$key===''){http_response_code(403);echo "Perangkat tidak valid (kiosk key diperlukan).";exit;}$stmt=$connection->prepare("\n    SELECT did, label, token, is_active\n    FROM app_device_allowlist\n    WHERE did=? LIMIT 1\n  ");if(!$stmt){http_response_code(500);echo "Query error (prepare device).";exit;}$stmt->bind_param('s',$did);$stmt->execute();$res=$stmt->get_result();$stmt->close();if(!$res||$res->num_rows<=0){http_response_code(403);echo "Perangkat tidak terdaftar.";exit;}$rowAllow=$res->fetch_assoc();if((int)($rowAllow['is_active']?? 0)!==1){http_response_code(403);echo "Perangkat non-aktif.";exit;}$dbToken=(string)($rowAllow['token']?? '');if(!hash_equals($dbToken,$key)){http_response_code(403);echo "Perangkat tidak terotorisasi.";exit;}if($label==='')$label=(string)($rowAllow['label']?? '');}$query_slide="SELECT * FROM slider WHERE active='Y' ORDER BY slider_id DESC";$result_slide=$connection->query($query_slide);$sliderItemsHtml='';$defaultSlide='../template/img/sw-big.jpg';if($result_slide&&$result_slide->num_rows>0){$active=0;while($data_slide=$result_slide->fetch_assoc()){$active++;$nama=strip_tags((string)($data_slide['slider_nama']?? ''));$foto=(string)($data_slide['foto']?? '');$src=$defaultSlide;if($foto!=='')$src='../sw-content/slider/'.rawurlencode($foto);$sliderItemsHtml.=($active===1)?'<div class="carousel-item active">':'<div class="carousel-item">';$sliderItemsHtml.='<img
      src="'.h($src).'"
      alt="'.h($nama).'"
      class="d-block w-100"
      loading="lazy"
      decoding="async"
      onerror="this.onerror=null;this.src=\''.h($defaultSlide).'\';"
    >';$sliderItemsHtml.='</div>';}}$tipeLayar=(string)($row_site['tipe_absen_layar']?? ''); ?><!doctypehtml><html lang="id"><head><meta charset="utf-8"><meta content="width=device-width,initial-scale=1,shrink-to-fit=no"name="viewport"><title><?=h(strip_tags($site_name))?></title><meta content="<?=h($site_name)?>"name="description"><meta content="s-widodo.com"name="author"><meta content="noindex,nofollow"name="robots"><link href="../sw-content/<?=h($site_favicon)?>"rel="icon"type="image/png"><link href="../template/css/style.css"rel="stylesheet"><link href="../template/css/sw-custom.css"rel="stylesheet"><link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic"rel="stylesheet"><link href="https://fonts.googleapis.com/icon?family=Material+Icons"rel="stylesheet"><link href="../template/vendor/fontawesome/css/all.min.css"rel="stylesheet"><link href="./main.css"rel="stylesheet"><link href="../template/vendor/webcame/webcam.css"rel="stylesheet"><style>.card .card-body{padding:12px}.screen-slider{display:none!important}.cam-preview-wrap{position:relative;width:100%;background:#0b1220;border-radius:14px;overflow:hidden;aspect-ratio:4/3;box-shadow:0 10px 25px rgba(0,0,0,.25)}.cam-preview-wrap canvas,.cam-preview-wrap video{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;border:0}.cam-preview-wrap canvas{display:none}.cam-badge{position:absolute;left:10px;top:10px;z-index:3;background:rgba(0,0,0,.45);color:#fff;padding:6px 10px;border-radius:999px;font-size:12px;letter-spacing:.3px;display:flex;gap:8px;align-items:center;backdrop-filter:blur(6px)}.cam-dot{width:8px;height:8px;border-radius:999px;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.15)}.status-panel{margin-top:10px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:10px 12px;color:#e5e7eb;font-size:12px;line-height:1.35;display:none}.status-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px 12px}.status-item{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:6px 8px;border-radius:10px;background:#343a40;border:1px solid rgba(255,255,255,.06)}.status-k{color:#e5e7eb;white-space:nowrap}.status-v{font-weight:700;color:#fff;text-align:right;min-width:70px}.pill{display:inline-flex;align-items:center;gap:6px;padding:2px 8px;border-radius:999px;font-weight:800;font-size:11px}.pill-dot{width:8px;height:8px;border-radius:999px;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.12)}.pill.off .pill-dot{background:#f59e0b;box-shadow:0 0 0 3px rgba(245,158,11,.12)}.pill.bad .pill-dot{background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.12)}.absen5-wrap{max-height:100%;overflow:hidden;position:relative}.absen5-list{display:flex;flex-direction:column}.absen5-item{background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:12px;color:#fff;transform:translateY(0);opacity:1;will-change:transform,opacity}.absen5-item.is-new{animation:absenSlideIn .22s ease-out}@keyframes absenSlideIn{from{transform:translateY(18px);opacity:.2}to{transform:translateY(0);opacity:1}}.absen5-item *{max-width:100%}.ss-overlay{position:fixed;inset:0;z-index:999999;background:#000;display:none}.ss-overlay.is-on{display:block}.ss-overlay .ss-inner{position:absolute;inset:0}#ssOverlay .ss-manual,#ssOverlay .ss-manual img{width:100vw;height:100vh}#ssOverlay .ss-manual img{object-fit:cover;display:block}.net-badge{display:inline-flex;align-items:center;gap:8px;padding:6px 10px;border-radius:999px;background:rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.1);color:#fff;font-size:12px;backdrop-filter:blur(8px);user-select:none;white-space:nowrap}.net-dot{width:10px;height:10px;border-radius:999px;background:#22c55e;box-shadow:0 0 0 3px rgba(34,197,94,.16)}.net-badge.offline .net-dot{background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.16)}.net-badge .net-text{opacity:.9;font-weight:800;letter-spacing:.2px}.net-badge .net-speed{padding-left:8px;border-left:1px solid rgba(255,255,255,.12);font-weight:900}.absen-loading{position:fixed;inset:0;z-index:1000000;display:none;align-items:center;justify-content:center;background:rgba(0,0,0,.55);backdrop-filter:blur(6px)}.absen-loading.is-on{display:flex}.absen-loading .box{width:min(520px,calc(100vw - 28px));background:rgba(15,23,42,.92);border:1px solid rgba(255,255,255,.12);border-radius:18px;padding:18px 18px;box-shadow:0 20px 60px rgba(0,0,0,.45);color:#fff;text-align:center}.absen-loading .ttl{font-weight:900;letter-spacing:.2px;margin:2px 0 8px;font-size:18px}.absen-loading .sub{opacity:.9;font-size:13px;line-height:1.35;margin:0 0 12px}.absen-loading .hint{opacity:.8;font-size:12px;margin-top:10px}.absen-loading .spin{width:46px;height:46px;border-radius:999px;border:4px solid rgba(255,255,255,.18);border-top-color:rgba(255,255,255,.95);margin:10px auto 10px;animation:absenSpin .9s linear infinite}@keyframes absenSpin{to{transform:rotate(360deg)}}.absen-locking input.qrcode{opacity:.65;pointer-events:none}</style></head><?php if($tipeLayar==='qrcode-webcame'): ?><body onload="qrcode_webcame()"><?php else: ?><body onload="webcame_selfie()"><?php endif; ?><span class="d-none latitude"></span><header class="header"><div class="conatiner-fluid"><div class="row"><div class="col-md-6"><div class="col align-self-left logo-header"><span><?=h(format_hari_tanggal($date))?></span><?php if($did!==''): ?><small class="ml-2"style="opacity:.75">(<?=h($did)?><?=$label?' - '.h($label):''?>)</small><?php endif; ?></div></div><div class="col-md-6"><div class="col align-self-left"><div class="align-items-center d-flex justify-content-end"style="gap:10px"><span class="clock"></span><div class="net-badge"id="netBadge"title="Status jaringan & perkiraan kecepatan"><span class="net-dot"id="netDot"></span> <span class="net-text"id="netText">Online</span> <span class="net-speed"id="netSpeed">-- Mbps</span></div></div></div></div></div></div></header><main class="mt-2 flex-shrink-0 has-footer main s-widodo.com"><div class="mt-2 section"><div class="container-fluid mb-2"><div class="row"><div class="col-md-4"><div class="card"><div class="card-body text-center"style="display:none"><div class="screen-slider"aria-hidden="true"><div class="carousel slide"id="carouselExampleIndicators"data-interval="5000"data-ride="carousel"><div class="carousel-inner"><?=$sliderItemsHtml?></div><a class="carousel-control-prev"href="#carouselExampleIndicators"data-slide="prev"role="button"><span class="carousel-control-prev-icon"aria-hidden="true"></span> <span class="sr-only">Previous</span> </a><a class="carousel-control-next"href="#carouselExampleIndicators"data-slide="next"role="button"><span class="carousel-control-next-icon"aria-hidden="true"></span> <span class="sr-only">Next</span></a></div></div></div></div><div class="card"><div class="card-body text-center pl-4 pr-4 pt-4"><?php if($tipeLayar==='qrcode'): ?><h3>Cukup scan QR Code dengan mesin scanner dan biarkan wajah Anda tertangkap secara otomatis</h3><div class="aniamed-scanner"><img alt="scanner"class="mt-2 bm-2 imaged-scanner"src="../template/img/qr-code.gif"></div><input class="bg-white form-control qrcode"name="qrcode"required><?php elseif($tipeLayar==='rfid'): ?><h1><b>Dekatkan KARTU PELAJAR Anda pada alat Pembaca, dan proses Absensi akan berjalan otomatis</b></h1><div class="aniamed-scanner"><img alt="logo"class="bm-2 imaged-scanner"src="../template/img/siprobo-melati.png"style="display:none"></div><input class="bg-white form-control qrcode"name="qrcode"required><?php else: ?><h3>Arahkan kamera Anda ke QR Code untuk memindai</h3><div class="text-center webcame"><div id="reader"></div></div><?php endif; ?><?php if($tipeLayar==='qrcode-webcame'): ?><div class="status-panel"id="statusPanelAlt"><div class="status-grid"><div class="status-item"><span class="status-k">Jaringan</span> <span class="status-v"><span class="pill js-stNet"><span class="pill-dot"></span><span class="js-stNetTxt">Online</span></span></span></div><div class="status-item"><span class="status-k">Realtime</span> <span class="status-v"><span class="pill off js-stSse"><span class="pill-dot"></span><span class="js-stSseTxt">Menghubungkan</span></span></span></div><div class="status-item"><span class="status-k">Internet</span> <span class="status-v js-stMbps">-- Mbps</span></div><div class="status-item"><span class="status-k">Ping</span> <span class="status-v js-stPing">-- ms</span></div><div class="status-item"><span class="status-k">Server</span> <span class="status-v"><span class="pill off js-stSrv"><span class="pill-dot"></span><span class="js-stSrvTxt">Cek</span></span></span></div><div class="status-item"><span class="status-k">Antrean</span> <span class="status-v js-stQueue">0</span></div><div class="status-item"><span class="status-k">Sync terakhir</span> <span class="status-v js-stSync">-</span></div><div class="status-item"><span class="status-k">Mode</span> <span class="status-v"><?=h($tipeLayar?:'-')?></span></div></div></div><?php endif; ?></div></div><?php if($tipeLayar!=='qrcode-webcame'): ?><div class="card mt-2"><div class="card-body p-3"><div class="align-items-center d-flex justify-content-between mb-2"><div><strong style="font-size:14px">Preview Kamera</strong><div class="text-muted"style="font-size:12px;line-height:1.2">Pastikan wajah terlihat jelas</div></div><span class="badge badge-success">LIVE</span></div><div class="cam-preview-wrap"><div class="cam-badge"><span class="cam-dot"></span> <span>Kamera aktif</span></div><video autoplay height="480"id="webcam"playsinline width="640"></video><canvas id="canvas"></canvas></div><div class="status-panel"id="statusPanel"><div class="status-grid"><div class="status-item"><span class="status-k">Jaringan</span> <span class="status-v"><span class="pill js-stNet"><span class="pill-dot"></span><span class="js-stNetTxt">Online</span></span></span></div><div class="status-item"><span class="status-k">Realtime</span> <span class="status-v"><span class="pill off js-stSse"><span class="pill-dot"></span><span class="js-stSseTxt">Menghubungkan</span></span></span></div><div class="status-item"><span class="status-k">Internet</span> <span class="status-v js-stMbps">-- Mbps</span></div><div class="status-item"><span class="status-k">Ping</span> <span class="status-v js-stPing">-- ms</span></div><div class="status-item"><span class="status-k">Server</span> <span class="status-v"><span class="pill off js-stSrv"><span class="pill-dot"></span><span class="js-stSrvTxt">Cek</span></span></span></div><div class="status-item"><span class="status-k">Antrean</span> <span class="status-v js-stQueue">0</span></div><div class="status-item"><span class="status-k">Sync terakhir</span> <span class="status-v js-stSync">-</span></div><div class="status-item"><span class="status-k">Mode</span> <span class="status-v"><?=h($tipeLayar?:'-')?></span></div></div></div></div></div><?php endif; ?></div><div class="col-md-8"><div class="card"><div class="card-body card-body-absensi"><div class="absen5-wrap"><div class="absen5-list"id="absen5List"></div></div></div></div><div class="mt-3 transactions"><div class="row data-counter-left"><div class="col-md-4"><div class="border-0 card mb-2 bg-warning"><div class="card-body"><div class="row align-items-center"><div class="col-auto pr-0"><div class="border-0 avatar avatar-50 text-default"><img alt="img"class="image-block imaged w36"src="../template/img/icons/003-profile.png"></div></div><div class="col align-self-center"><strong class="text-white">Total Siswa</strong><p class="text-white total-siswa">0</p></div></div></div></div></div><div class="col-md-4"><div class="border-0 card mb-2 bg-danger"><div class="card-body"><div class="row align-items-center"><div class="col-auto pr-0"><div class="border-0 avatar avatar-50 text-default"><img alt="img"class="image-block imaged w36"src="../template/img/icons/002-sand-clock.png"></div></div><div class="col align-self-center"><strong class="text-white">Belum Absen</strong><p class="text-white belum-absen">0</p></div></div></div></div></div><div class="col-md-4"><div class="border-0 card mb-2 bg-primary"><div class="card-body"><div class="row align-items-center"><div class="col-auto pr-0"><div class="border-0 avatar avatar-50 text-default"><img alt="img"class="image-block imaged w36"src="../template/img/icons/007-insight.png"></div></div><div class="col align-self-center"><strong class="text-white">Total Absen</strong><p class="text-white"><span class="total-absen">0</span> <small class="text-white"><span class="material-icons ml-3"style="font-size:15px">show_chart</span> <span class="ml-1 persentase">0</span>%</small></p></div></div></div></div></div><div class="col-md-4"><div class="border-0 card mb-2 bg-secondary"><div class="card-body"><div class="row align-items-center"><div class="col-auto pr-0"><div class="border-0 avatar avatar-50 text-default"><img alt="img"class="image-block imaged w36"src="../template/img/icons/005-clipboard.png"></div></div><div class="col align-self-center"><strong class="text-white">On Time</strong><p class="text-white ontime">0</p></div></div></div></div></div><div class="col-md-4"><div class="border-0 card mb-2 bg-danger"><div class="card-body"><div class="row align-items-center"><div class="col-auto pr-0"><div class="border-0 avatar avatar-50 text-default"><img alt="img"class="image-block imaged w36"src="../template/img/icons/004-time.png"></div></div><div class="col align-self-center"><strong class="text-white">Terlambat</strong><p class="text-white terlambat">0</p></div></div></div></div></div><div class="col-md-4"><div class="border-0 card bg-info mb-1"><div class="card-body"><div class="row align-items-center"><div class="col-auto pr-0"><div class="border-0 avatar avatar-50 text-default"><img alt="img"class="image-block imaged w36"src="../template/img/icons/002-verified.png"></div></div><div class="col align-self-center"><strong class="text-white">Izin</strong><p class="text-white izin">0</p></div></div></div></div></div></div></div></div></div></div></div></main><footer class="footer"style="display:none"><div class="marquee-left"><p>Selamat Datang di<?=h($row_site['nama_sekolah']?? '')?></p></div></footer><div class="d-none appBottomMenu bg-primary"><span class="credits"><a class="credits_a"href="https://s-widodo.com"id="mycredit"target="_blank">S-widodo.com</a></span></div><div class="ss-overlay"id="ssOverlay"aria-hidden="true"><div class="ss-inner"id="ssInner"></div></div><div class="absen-loading"id="absenLoading"aria-hidden="true"><div class="box"><div class="spin"></div><div class="ttl"id="absenLoadingTitle">Memproses absensi…</div><div class="sub"id="absenLoadingSub">Mohon tunggu. Siswa berikutnya silakan menunggu hingga proses selesai.</div><div class="hint"id="absenLoadingHint">Jangan scan ulang.</div></div></div><script src="../sw-library/bundle.min.php?get=s-widodo.com"></script><script src="./sw-script.js?v=<?=filemtime(__DIR__.'/sw-script.js')?>"></script><script>!function(){"use strict";var s=document.getElementById("absen5List");window.pushLatestAbsen=function(e){if(s){var t=function(e){return"string"==typeof e&&-1!==e.indexOf("absen5-item")?e:'<div class="absen5-item">'+(e||"")+"</div>"}(e),i=document.createElement("div");i.innerHTML=t.trim();var n=i.firstElementChild;if(n){for(n.classList.add("is-new"),s.insertBefore(n,s.firstChild);6<s.children.length;)s.removeChild(s.lastElementChild);setTimeout(function(){n.classList.remove("is-new")},400)}}}}()</script><script>!function(){"use strict";var n,t=document.getElementById("absenLoading"),i=document.getElementById("absenLoadingTitle"),o=document.getElementById("absenLoadingSub"),r=document.getElementById("absenLoadingHint"),u=!1,a=null;function c(){t&&(t.classList.remove("is-on"),t.setAttribute("aria-hidden","true"),document.documentElement.classList.remove("absen-locking"))}function s(e){u=!0,function(e){t&&(i&&(i.textContent=e||"Memproses absensi…"),o&&(o.textContent="Mohon tunggu. Siswa berikutnya silakan menunggu hingga proses selesai."),r&&(r.textContent="Jangan scan ulang."),t.classList.add("is-on"),t.setAttribute("aria-hidden","false"),document.documentElement.classList.add("absen-locking"))}(e),a&&clearTimeout(a),a=setTimeout(function(){u=!1,c()},15e3)}function l(){u=!1,a&&clearTimeout(a),a=null,c();try{var e=document.querySelector("input.qrcode");e&&(e.value="",e.focus())}catch(e){}}function d(){return u}function f(e){try{var n=String(e||"");return-1!==n.indexOf("sw-proses.php")&&-1!==n.indexOf("action=absen")}catch(e){return!1}}if(window.AbsenLock={lock:s,unlock:l,isLocked:d},(n=document.querySelector("input.qrcode"))&&(n.addEventListener("keydown",function(e){if(d()&&("Enter"===e.key||13===e.keyCode)){e.preventDefault(),e.stopPropagation();try{n.value=""}catch(e){}}},!0),n.addEventListener("input",function(){if(d())try{n.value=""}catch(e){}},!0)),window.fetch){var m=window.fetch;window.fetch=function(e,n){return f("string"==typeof e?e:e&&e.url?e.url:"")?d()?Promise.reject(new Error("ABSEN_LOCKED")):(s("Memproses absensi…"),m.apply(this,arguments).then(function(e){return e}).catch(function(e){throw e}).finally(function(){l()})):m.apply(this,arguments)}}if(window.jQuery&&jQuery.ajax){var y=jQuery.ajax;jQuery.ajax=function(e){try{if(f(e&&e.url?String(e.url):"")){if(d()){var n=jQuery.Deferred();return n.reject({status:0},"error","ABSEN_LOCKED"),n.promise()}var t=e.beforeSend,i=e.complete;e.beforeSend=function(e,n){if(s("Memproses absensi…"),"function"==typeof t)return t.call(this,e,n)},e.complete=function(e,n){if(l(),"function"==typeof i)return i.call(this,e,n)}}}catch(e){}return y.apply(this,arguments)}}}()</script><script>/**
 * HEADER BADGE: Online/Offline + Mbps
 * + juga update panel stMbps + stPing (kedua panel via class)
 */
(function(){
  "use strict";

  const elBadge = document.getElementById("netBadge");
  const elText  = document.getElementById("netText");
  const elSpeed = document.getElementById("netSpeed");

  const elMbpsList = document.querySelectorAll(".js-stMbps");
  const elPingList = document.querySelectorAll(".js-stPing");

  const TEST_URL = "./speed-test.bin";
  const INTERVAL_MS = 30000;

  function setTextList(nodeList, val){
    try{ nodeList.forEach(n => n.textContent = val); }catch(e){}
  }

  function setOffline(){
    if (elBadge) elBadge.classList.add("offline");
    if (elText)  elText.textContent = "Offline";
    if (elSpeed) elSpeed.textContent = "-- Mbps";
    setTextList(elMbpsList, "-- Mbps");
    setTextList(elPingList, "-- ms");
  }

  function setOnline(){
    if (elBadge) elBadge.classList.remove("offline");
    if (elText)  elText.textContent = "Online";
  }

  function fmtMbps(v){
    if (!v || !isFinite(v) || v <= 0) return "-- Mbps";
    return (v >= 10 ? v.toFixed(0) : v.toFixed(1)) + " Mbps";
  }
  function fmtMs(v){
    if (!v || !isFinite(v) || v <= 0) return "-- ms";
    return Math.round(v) + " ms";
  }

  async function runMiniSpeedTest(){
    if (!navigator.onLine) { setOffline(); return; }
    setOnline();

    const conn = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
    const hint = conn && typeof conn.downlink === "number" ? conn.downlink : null;

    try{
      const url = TEST_URL + "?t=" + Date.now();
      const t0 = performance.now();
      const res = await fetch(url, { cache: "no-store" });
      if (!res.ok) throw new Error("HTTP " + res.status);
      const buf = await res.arrayBuffer();
      const t1 = performance.now();

      const ms = Math.max(1, (t1 - t0));
      const bytes = buf.byteLength || 0;
      const sec = Math.max(0.001, ms / 1000);
      const mbps = (bytes * 8) / (sec * 1024 * 1024);

      const shown = (isFinite(mbps) && mbps > 0) ? mbps : hint;

      const sp = fmtMbps(shown);
      if (elSpeed) elSpeed.textContent = sp;
      setTextList(elMbpsList, sp);
      setTextList(elPingList, fmtMs(ms));

    }catch(e){
      const sp = fmtMbps(hint);
      if (elSpeed) elSpeed.textContent = sp;
      setTextList(elMbpsList, sp);
      setTextList(elPingList, "-- ms");
    }
  }

  window.addEventListener("online", runMiniSpeedTest);
  window.addEventListener("offline", setOffline);

  runMiniSpeedTest();
  setInterval(runMiniSpeedTest, INTERVAL_MS);
})();</script><script>/**
 * STATUS PANEL: Net / Realtime / Queue / Sync / Server
 * Sinkron dengan sw-script.js refactor
 */
(function(){
  "use strict";

  var elNet     = document.querySelectorAll(".js-stNet");
  var elNetTxt  = document.querySelectorAll(".js-stNetTxt");

  var elRt      = document.querySelectorAll(".js-stSse");
  var elRtTxt   = document.querySelectorAll(".js-stSseTxt");

  var elSrv     = document.querySelectorAll(".js-stSrv");
  var elSrvTxt  = document.querySelectorAll(".js-stSrvTxt");

  var elQueue   = document.querySelectorAll(".js-stQueue");
  var elSync    = document.querySelectorAll(".js-stSync");

  var lastQueue = null;
  var lastServerState = "off";

  function each(list, fn){ try{ list.forEach(fn); }catch(e){} }

  function setPill(listEl, listTxt, state, text){
    each(listEl, function(el){
      if (!el) return;
      el.classList.remove("off","bad");
      if (state === "off") el.classList.add("off");
      if (state === "bad") el.classList.add("bad");
    });
    each(listTxt, function(t){
      if (t) t.textContent = text;
    });
  }

  function fmtTime(ts){
    if (!ts) return "-";
    try{
      var d = new Date(Number(ts) || ts);
      return d.toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });
    }catch(e){
      return "-";
    }
  }

  function updateNet(){
    var on = !!navigator.onLine;
    setPill(elNet, elNetTxt, on ? "ok" : "off", on ? "Online" : "Offline");
  }

  function setRealtimeState(state, text){
    if (state === "ok") setPill(elRt, elRtTxt, "ok", text || "Aktif");
    else if (state === "bad") setPill(elRt, elRtTxt, "bad", text || "Error");
    else setPill(elRt, elRtTxt, "off", text || "Menghubungkan");
  }

  function setServerState(state, text){
    lastServerState = state || "off";
    if (state === "ok") setPill(elSrv, elSrvTxt, "ok", text || "Online");
    else if (state === "bad") setPill(elSrv, elSrvTxt, "bad", text || "Error");
    else setPill(elSrv, elSrvTxt, "off", text || "Offline");
  }

  function setLastSync(ts){
    try{
      localStorage.setItem("absensi_last_sync", String(ts));
    }catch(e){}
    each(elSync, function(n){
      if (n) n.textContent = fmtTime(ts);
    });
  }

  function loadLastSync(){
    var lastSync = "";
    try{
      lastSync = localStorage.getItem("absensi_last_sync") || "";
    }catch(e){}
    each(elSync, function(n){
      if (n) n.textContent = fmtTime(lastSync);
    });
  }

  var _qdb = null;
  var _qdbOpenAt = 0;

  function openQueueDB(){
    return new Promise(function(resolve){
      if (!window.indexedDB) return resolve(null);

      if (_qdb && (Date.now() - _qdbOpenAt) < 120000) return resolve(_qdb);

      var req = indexedDB.open("absensi_offline_db", 2); // wajib sama dengan sw-script.js
      req.onerror = function(){ resolve(null); };
      req.onsuccess = function(){
        try{
          _qdb = req.result;
          _qdbOpenAt = Date.now();
          _qdb.onversionchange = function(){
            try{ _qdb.close(); }catch(_){}
            _qdb = null;
          };
          resolve(_qdb);
        }catch(e){
          resolve(null);
        }
      };
    });
  }

  async function getQueueCount(){
    var db = await openQueueDB();
    return new Promise(function(resolve){
      try{
        if (!db || !db.objectStoreNames.contains("queue")) return resolve(0);
        var tx = db.transaction("queue", "readonly");
        var st = tx.objectStore("queue");
        var cReq = st.count();
        cReq.onsuccess = function(){ resolve(cReq.result || 0); };
        cReq.onerror = function(){ resolve(0); };
      }catch(e){
        resolve(0);
      }
    });
  }

  async function updateQueueAndSync(){
    var c = await getQueueCount();

    each(elQueue, function(n){
      if (n) n.textContent = String(c);
    });

    if (lastQueue !== null && c < lastQueue) {
      setLastSync(Date.now());
    }

    // kalau queue sudah 0 dan server normal, anggap sinkron sehat
    if (c === 0 && lastServerState === "ok" && lastQueue !== 0) {
      setLastSync(Date.now());
    }

    lastQueue = c;
  }

  // default awal
  updateNet();
  loadLastSync();
  setRealtimeState("off", "Menghubungkan");
  setServerState(navigator.onLine ? "off" : "off", navigator.onLine ? "Menyambung" : "Offline");

  // dari sw-script.js
  window.addEventListener("absensi:realtime", function(ev){
    try{
      var d = ev && ev.detail ? ev.detail : {};
      setRealtimeState(d.state || "off", d.text || "");

      // status server mengikuti status realtime utama
      if (d.state === "ok") {
        setServerState("ok", "Online");
      } else {
        setServerState(navigator.onLine ? "off" : "off", navigator.onLine ? "Menyambung" : "Offline");
      }
    }catch(e){}
  });

  if (typeof window.AbsensiRealtimeStatus === "function") {
    try{
      window.AbsensiRealtimeStatus(function(payload){
        setRealtimeState(payload.state || "off", payload.text || "");
        if ((payload.state || "") === "ok") {
          setServerState("ok", "Online");
        } else {
          setServerState(navigator.onLine ? "off" : "off", navigator.onLine ? "Menyambung" : "Offline");
        }
      });
    }catch(e){}
  }

  window.addEventListener("absensi:server-online", function(){
    setServerState("ok", "Online");
    updateQueueAndSync();
  });

  window.addEventListener("absensi:server-offline", function(){
    setServerState(navigator.onLine ? "off" : "off", navigator.onLine ? "Menyambung" : "Offline");
    updateQueueAndSync();
  });

  window.addEventListener("online", function(){
    updateNet();
    setServerState("off", "Menyambung");
    updateQueueAndSync();
  });

  window.addEventListener("offline", function(){
    updateNet();
    setRealtimeState("off", "Offline");
    setServerState("off", "Offline");
    updateQueueAndSync();
  });

  updateQueueAndSync();
  setInterval(updateQueueAndSync, 10000);
})();</script><script>/**
 * SCREEN SAVER (FULLSCREEN SLIDESHOW)
 */
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

  function overlayOn() { return overlay && overlay.classList.contains("is-on"); }
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
  function withKioskParams(url) {
    try {
      var u = new URL(url, location.href);
      var qs = new URLSearchParams(location.search || "");
      ["did","k","label"].forEach(function(p){
        var v = (qs.get(p) || "").trim();
        if (v) u.searchParams.set(p, v);
      });
      return u.pathname + "?" + u.searchParams.toString();
    } catch(e) { return url; }
  }
  async function fetchFeed() {
    try {
      var res = await fetch(withKioskParams(FEED_URL), { cache: "no-store" });
      return await res.json();
    } catch (e) { return null; }
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

    if (!lastVersion) { lastVersion = ver; slides = parsed; return; }
    if (ver && ver !== lastVersion) {
      lastVersion = ver;
      slides = parsed;
      if (overlayOn()) renderSaver(slides);
    }
  }
  function startPolling() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(pollSlider, POLL_MS);
    pollSlider();
  }

  ["mousemove","mousedown","touchstart","touchmove","keydown","click"].forEach(function (ev) {
    document.addEventListener(ev, onUserActivity, { passive: true });
  });
  if (overlay) overlay.addEventListener("click", onUserActivity, { passive: true });
  document.addEventListener("visibilitychange", function () {
    if (!document.hidden) onUserActivity();
  });

  startPolling();
  armTimer();
})();</script><script>/**
 * DEVICE PING (monitoring)
 */
(function(){
  "use strict";

  var PING_TOKEN = "c99a133ae06d17a6d4d0b80cb6ddb048";
  var PING_URL   = "./device_ping.php";

  function getDeviceId(){
    var p = new URLSearchParams(location.search);
    var did = (p.get("did") || "").trim();
    if (!did) return "";
    return did.slice(0, 64).toUpperCase();
  }
  function getLabel(){
    var p = new URLSearchParams(location.search);
    return (p.get("label") || "").slice(0, 100);
  }

  var deviceId = getDeviceId();
  var label    = getLabel();

  async function ping(){
    if (!deviceId) return;
    try{
      var fd = new FormData();
      fd.append("device_id", deviceId);
      fd.append("label", label);
      fd.append("page", location.pathname);

      await fetch(PING_URL, {
        method: "POST",
        body: fd,
        cache: "no-store",
        headers: { "X-Ping-Token": PING_TOKEN }
      });
    }catch(e){}
  }

  ping();
  setInterval(ping, 30 * 1000);

  document.addEventListener("visibilitychange", function(){
    if (!document.hidden) ping();
  });

  // jangan spam ping pada event user, cukup 1x per 60 detik
  var lastActivePing = 0;
  ["click","touchstart","keydown"].forEach(function(ev){
    document.addEventListener(ev, function(){
      var now = Date.now();
      if (now - lastActivePing > 60 * 1000) {
        lastActivePing = now;
        ping();
      }
    }, {passive:true});
  });
})();</script></body></html>