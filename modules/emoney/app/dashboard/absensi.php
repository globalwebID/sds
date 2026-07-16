<?php
session_start();
require_once dirname(__DIR__, 2) . '/config/runtime.php';
require_once __DIR__.'/_central_control.php';
$absensiRootUrl = rtrim(sds_base_url('absensi'), '/');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['login'])) {
  header('Location: ../login.php');
  exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Absensi Siswa</title>

<link rel="stylesheet" href="../assets/css/app.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-J18CE0BVMY"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-J18CE0BVMY');
</script>
<style>
/* ===============================
   Tabs (Harian / Ekstra)
================================ */
.tabs{
  margin: 12px 12px 0;
  background:#fff;
  border-radius:14px;
  padding:6px;
  box-shadow: 0 6px 18px rgba(0,0,0,.08);
  display:flex;
  gap:6px;
}
.tab-btn{
  flex:1;
  border:0;
  border-radius:12px;
  padding:10px 12px;
  font-weight:900;
  cursor:pointer;
  background:transparent;
  color:#111827;
}
.tab-btn.active{
  background:#e11d48;
  color:#fff;
}
.tab-pane{ display:none; }
.tab-pane.active{ display:block; }

/* ===============================
   Card transaksi absensi
================================ */
.card-transaksi{
  background:#fff;
  margin:10px 0;
  padding:14px;
  border-radius:12px;
  box-shadow:0 4px 12px rgba(0,0,0,0.1);
  display:flex;
  justify-content:space-between;
  align-items:center;

  cursor:pointer;
  user-select:none;
  transition: transform .08s ease, box-shadow .12s ease;
}
.card-transaksi:active{ transform: scale(.99); }

.card-transaksi.is-hadir{
  background:#ecfdf5;
  border:1px solid #86efac;
  box-shadow:0 6px 16px rgba(16,185,129,.18);
}
.card-transaksi.is-terlambat{
  background:#fff7ed;
  border:1px solid #fdba74;
  box-shadow:0 6px 16px rgba(251,146,60,.25);
}
.card-transaksi.is-belum-pulang{
  background:#fef2f2;
  border:1px solid #fca5a5;
  box-shadow:0 6px 16px rgba(239,68,68,.18);
}
.card-transaksi.is-absen{
  background:#fff7ed;
  border:1px solid #fdba74;
}

.item .left{ flex:1; min-width:0; }
.item .left strong{
  display:block;
  font-size:14px;
  white-space:nowrap;
  overflow:hidden;
  text-overflow:ellipsis;
}
.item .left small{
  display:block;
  color:#6b7280;
  margin-top:3px;
  font-size:12px;
}

.badge{
  padding:7px 10px;
  border-radius:999px;
  font-size:12px;
  font-weight:900;
  white-space:nowrap;
  height: fit-content;
}

/* warna teks badge normal */
.status-hadir { color: #166534; }
.status-absen { color: #991b1b; }
.status-terlambat { color: #b45309; }

/* badge khusus belum pulang */
.badge-belum-pulang{
  background:#fee2e2;
  color:#991b1b;
  font-weight:900;
}

/* highlight baris pulang */
.small-warn{
  color:#991b1b !important;
  font-weight:800;
}
.small-warn .ico-warn{ margin-right:6px; }

/* FILTER */
.filter-form{
  display:flex;
  gap:10px;
  margin:0;
}
.filter-form input{
  flex:1;
  padding:8px 12px;
  border-radius:8px;
  border:1px solid #ddd;
}
.filter-form button{
  width:100%;
  padding:11px 12px;
  border:0;
  border-radius:12px;
  background:#e11d48;
  color:#fff;
  font-weight:800;
  cursor:pointer;
}

/* box info "dalam pengembangan" */
.dev-box{
  margin: 12px 15px;
  padding: 14px;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 6px 18px rgba(0,0,0,.06);
  color:#6b7280;
  font-size: 13px;
  display:flex;
  gap:10px;
  align-items:flex-start;
}
.dev-box i{ color:#e11d48; margin-top:2px; }

/* list */
#listAbsensi small{
  display:block;
  color:#888;
  font-size:12px;
}

/* ===============================
   MODAL + TAB DETAIL (Datang / Pulang)
================================ */
.modal{
  position:fixed;
  inset:0;
  background: rgba(0,0,0,.55);
  display:none;
  align-items:center;
  justify-content:center;
  padding:18px;
  z-index:9999;
}
.modal.show{ display:flex; }

.modal-card{
  width:min(760px, 100%);
  background:#fff;
  border-radius:16px;
  box-shadow:0 18px 50px rgba(0,0,0,.25);
  overflow:hidden;
}
.modal-head{
  padding:14px 16px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  border-bottom:1px solid #eee;
}
.modal-head b{ font-size:14px; }
.modal-close{
  border:0;
  background:transparent;
  font-size:18px;
  cursor:pointer;
  padding:6px 10px;
  border-radius:10px;
}

.modal-body{ padding:14px 16px 16px; }

/* Tab bar di dalam modal */
.md-tabs{
  display:flex;
  gap:8px;
  background:#f3f4f6;
  padding:6px;
  border-radius:14px;
  margin-bottom:12px;
}
.md-tab-btn{
  flex:1;
  border:0;
  border-radius:12px;
  padding:10px 12px;
  cursor:pointer;
  font-weight:900;
  background:transparent;
  color:#111827;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:8px;
}
.md-tab-btn.active{
  background:#e11d48;
  color:#fff;
}

.md-pane{ display:none; }
.md-pane.active{ display:block; }

.photo-box{
  border:1px solid #eee;
  border-radius:14px;
  padding:10px;
  overflow:hidden;
}
.photo-box img{
  width:100%;
  height:auto;
  object-fit:cover;
  border-radius:12px;
  background:#f3f4f6;
  border:1px solid #f1f1f1;
}

.detail-line{
  margin-top:10px;
  background:#f9fafb;
  border:1px solid #eee;
  border-radius:12px;
  padding:10px 12px;
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
  font-size:13px;
}
.detail-line b{ white-space:nowrap; }
.detail-line span{ font-weight:900; color:#111827; }

@media (max-width:520px){
  .photo-box img{ height:auto; }
}
</style>
</head>

<body>

<header class="top">
  <h1>Absensi</h1>
  <small id="nama">Memuat...</small>
</header>

<!-- Tabs -->
<div class="tabs" role="tablist" aria-label="Tab Absensi">
  <button type="button" class="tab-btn active" data-tab="tabHarian">
    <i class="fa-solid fa-calendar-day"></i> Harian
  </button>
  <button type="button" class="tab-btn" data-tab="tabEkstra">
    <i class="fa-solid fa-star"></i> Ekstra
  </button>
</div>

<!-- TAB: HARIAN -->
<div id="tabHarian" class="tab-pane active">

  <!-- FILTER -->
  <div class="card">
    <form id="filterForm" class="filter-form">
      <input type="date" id="tglAwal" required>
      <input type="date" id="tglAkhir" required>
      <button type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>
  </div>

  <!-- RIWAYAT ABSENSI -->
  <div class="card" id="riwayat">
    <h2>Riwayat Absensi Harian</h2>
    <div id="listAbsensi">Memuat...</div>
  </div>

</div>

<!-- TAB: EKSTRA -->
<div id="tabEkstra" class="tab-pane">
  <div class="dev-box">
    <i class="fa-solid fa-screwdriver-wrench"></i>
    <div>
      <b>Riwayat Absensi Ekstra</b><br>
      Fitur ini masih dalam pengembangan.
    </div>
  </div>
</div>

<!-- MODAL DETAIL ABSENSI (TAB) -->
<div id="modalDetail" class="modal" aria-hidden="true">
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="mdTitle">
    <div class="modal-head">
      <b id="mdTitle">Detail Absensi</b>
      <button type="button" class="modal-close" id="mdClose" aria-label="Tutup">✕</button>
    </div>

    <div class="modal-body">

      <div class="md-tabs" role="tablist" aria-label="Tab Foto Absensi">
        <button type="button" class="md-tab-btn active" data-mdtab="mdDatang">
          Foto Absen Datang
        </button>
        <button type="button" class="md-tab-btn" data-mdtab="mdPulang">
          Foto Absen Pulang
        </button>
      </div>

      <!-- TAB: DATANG -->
      <div id="mdDatang" class="md-pane active">
        <div class="photo-box">
          <img id="imgIn" src="" alt="Foto absen datang">
        </div>
        <div class="detail-line">
          <b>Jam Absen Masuk</b>
          <span id="dMasuk">-</span>
        </div>
      </div>

      <!-- TAB: PULANG -->
      <div id="mdPulang" class="md-pane">
        <div class="photo-box">
          <img id="imgOut" src="" alt="Foto absen pulang">
        </div>
        <div class="detail-line">
          <b>Jam Absen Pulang</b>
          <span id="dPulang">-</span>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
  <a href="index.php"><i class="fa-solid fa-house"></i>Home</a>
  <a href="emoney.php"><i class="fa-solid fa-wallet"></i>e-Money</a>
  <a href="perpustakaan.php"><i class="fa-solid fa-book"></i>Buku</a>
  <a href="absensi.php" class="active"><i class="fa-solid fa-calendar-check"></i>Absen</a>
  <a href="profil.php"><i class="fa-solid fa-user"></i>Profil</a>
</nav>

<script>
const API        = <?=json_encode(rtrim(sds_base_url('emoney/api'), '/'), JSON_UNESCAPED_SLASHES)?>;
const EP_ABSENSI = API + '/absensi.php';
const EP_POLL    = API + '/absensi_realtime.php';   // ✅ polling JSON (pengganti SSE)
const EP_DETAIL  = API + '/absensi_detail.php';

/**
 * ✅ FOTO ada di domain berbeda (absensi.smkn1probolinggo.sch.id)
 */
const ABSENSI_ROOT = <?= json_encode($absensiRootUrl, JSON_UNESCAPED_SLASHES) ?>;
const PHOTO_BASE = ABSENSI_ROOT + '/sw-content/absen/';

let activeTab = 'tabHarian';

/* ===============================
   TAB SWITCHER (Harian / Ekstra)
================================ */
function setTab(tabId){
  activeTab = tabId;

  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));

  document.getElementById(tabId)?.classList.add('active');
  document.querySelector(`.tab-btn[data-tab="${tabId}"]`)?.classList.add('active');

  if(tabId === 'tabHarian'){
    loadAbsensi(tglAwalInput.value, tglAkhirInput.value);
  }
}
document.querySelectorAll('.tab-btn').forEach(btn=>{
  btn.addEventListener('click', ()=> setTab(btn.getAttribute('data-tab')));
});

/* ===============================
   UTIL
================================ */
function isPastDay(dateStr){
  if(!dateStr) return false;
  const rec = new Date(dateStr);
  const now = new Date();
  rec.setHours(0,0,0,0);
  now.setHours(0,0,0,0);
  return rec < now;
}
function isEmptyTime(t){
  return !t || String(t).trim()==='' || String(t).trim()==='00:00:00';
}

function getStatusClass(status, absenOut, tanggal){
  if(isEmptyTime(absenOut) && isPastDay(tanggal)) return 'badge-belum-pulang';

  const s = (status || '').toLowerCase();
  if(s === 'hadir') return 'status-hadir';
  if(s === 'absen') return 'status-absen';
  if(s === 'terlambat') return 'status-terlambat';
  return '';
}
function getCardClass(status, absenOut, tanggal){
  if(isEmptyTime(absenOut) && isPastDay(tanggal)) return 'is-belum-pulang';

  const s = (status || '').toLowerCase();
  if (s === 'hadir' || s === 'tepat waktu' || s === 'ontime') return 'is-hadir';
  if (s === 'terlambat' || s === 'late') return 'is-terlambat';
  if (s === 'absen') return 'is-absen';
  return '';
}
function getBadgeLabel(status, absenOut, tanggal){
  if(isEmptyTime(absenOut) && isPastDay(tanggal)) return 'Belum Absen Pulang';
  return status || '-';
}

/* ===============================
   DEFAULT FILTER 7 HARI
================================ */
const tglAwalInput  = document.getElementById('tglAwal');
const tglAkhirInput = document.getElementById('tglAkhir');

const today = new Date();
const priorDate = new Date();
priorDate.setDate(today.getDate() - 6);

tglAkhirInput.value = today.toISOString().slice(0,10);
tglAwalInput.value  = priorDate.toISOString().slice(0,10);

/* ===============================
   FORMAT
================================ */
function formatTanggalIndo(dateStr){
  const d = new Date(dateStr);
  return d.toLocaleDateString('id-ID', { day:'numeric', month:'long', year:'numeric' });
}
function formatJam(jamStr){
  if(isEmptyTime(jamStr)) return '-';
  const d = new Date('1970-01-01T' + jamStr + 'Z');
  const hours = d.getUTCHours().toString().padStart(2,'0');
  const minutes = d.getUTCMinutes().toString().padStart(2,'0');
  return `${hours}:${minutes} WIB`;
}

/* ===============================
   MODAL + TAB DETAIL
================================ */
const modal   = document.getElementById('modalDetail');
const mdClose = document.getElementById('mdClose');

const imgIn   = document.getElementById('imgIn');
const imgOut  = document.getElementById('imgOut');

const dMasuk  = document.getElementById('dMasuk');
const dPulang = document.getElementById('dPulang');

function setMdTab(tabId){
  document.querySelectorAll('.md-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.md-tab-btn').forEach(b=>b.classList.remove('active'));

  document.getElementById(tabId)?.classList.add('active');
  document.querySelector(`.md-tab-btn[data-mdtab="${tabId}"]`)?.classList.add('active');
}
document.querySelectorAll('.md-tab-btn').forEach(btn=>{
  btn.addEventListener('click', ()=> setMdTab(btn.getAttribute('data-mdtab')));
});

function openModal(){
  modal.classList.add('show');
  modal.setAttribute('aria-hidden','false');
}
function closeModal(){
  modal.classList.remove('show');
  modal.setAttribute('aria-hidden','true');
}
mdClose.addEventListener('click', closeModal);
modal.addEventListener('click', (e)=>{ if(e.target === modal) closeModal(); });
document.addEventListener('keydown', (e)=>{ if(e.key === 'Escape' && modal.classList.contains('show')) closeModal(); });

function resolvePhotoUrl(v){
  const val = (v || '').trim();
  if(!val) return '';
  if(/^https?:\/\//i.test(val)) return val;
  if(val.startsWith('/')) return ABSENSI_ROOT + val;
  return PHOTO_BASE + val;
}
function setImgOrPlaceholder(imgEl, url){
  if(url){
    imgEl.src = url;
    return;
  }
  imgEl.src = 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(`
    <svg xmlns="http://www.w3.org/2000/svg" width="800" height="500">
      <rect width="100%" height="100%" fill="#f3f4f6"/>
      <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle"
            font-family="Arial" font-size="28" fill="#9ca3af">
        Tidak ada foto
      </text>
    </svg>
  `);
}

function showDetail(absenId){
  if(!absenId) return;

  setMdTab('mdDatang');

  setImgOrPlaceholder(imgIn, '');
  setImgOrPlaceholder(imgOut, '');
  dMasuk.textContent  = 'Memuat...';
  dPulang.textContent = 'Memuat...';

  openModal();

  fetch(`${EP_DETAIL}?id=${encodeURIComponent(absenId)}`, {credentials:'include'})
    .then(r => r.json())
    .then(d => {
      if(!d.success || !d.data){
        dMasuk.textContent = '-';
        dPulang.textContent = '-';
        return;
      }
      const row = d.data;

      dMasuk.textContent  = formatJam(row.absen_in);
      dPulang.textContent = formatJam(row.absen_out);

      setImgOrPlaceholder(imgIn, resolvePhotoUrl(row.foto_in));
      setImgOrPlaceholder(imgOut, resolvePhotoUrl(row.foto_out));
    })
    .catch(err => {
      console.error(err);
      dMasuk.textContent  = '-';
      dPulang.textContent = '-';
    });
}

/* ===============================
   LOAD ABSENSI (HARIAN)
================================ */
function loadAbsensi(tglAwal='', tglAkhir=''){
  const url = `${EP_ABSENSI}?tglAwal=${encodeURIComponent(tglAwal)}&tglAkhir=${encodeURIComponent(tglAkhir)}`;

  fetch(url, {credentials:'include'})
    .then(r => r.json())
    .then(d => {
      const list = document.getElementById('listAbsensi');

      if(!d.success || !d.data || d.data.length === 0){
        list.innerHTML = '<small>Belum ada absensi</small>';
        return;
      }

      let html = '';
      d.data.forEach(row => {
        const tanggalIndo = formatTanggalIndo(row.tanggal);
        const masuk  = formatJam(row.absen_in);
        const pulang = formatJam(row.absen_out);

        const warnBelumPulang = isPastDay(row.tanggal) && isEmptyTime(row.absen_out);

        const cardCls  = getCardClass(row.status, row.absen_out, row.tanggal);
        const badgeCls = getStatusClass(row.status, row.absen_out, row.tanggal);
        const badgeLbl = getBadgeLabel(row.status, row.absen_out, row.tanggal);

        const pulangLineClass = warnBelumPulang ? 'small-warn' : '';
        const pulangIcon = warnBelumPulang ? '<i class="fa-solid fa-triangle-exclamation ico-warn"></i>' : '';

        const absenId = row.absen_id ?? '';

        html += `
          <div class="card-transaksi ${cardCls}" data-absen-id="${String(absenId)}" title="Klik untuk lihat detail">
            <div class="left">
              <strong>${tanggalIndo}</strong>
              <small>Absen Masuk : ${masuk}</small>
              <small class="${pulangLineClass}">${pulangIcon}Absen Pulang: ${pulang}</small>
            </div>
            <div class="badge ${badgeCls}">${badgeLbl}</div>
          </div>
        `;
      });

      list.innerHTML = html;
    })
    .catch(err => {
      console.error(err);
      document.getElementById('listAbsensi').innerHTML = '<small>Gagal memuat absensi</small>';
    });
}

/* Klik card => modal (delegation) */
document.getElementById('listAbsensi').addEventListener('click', (e)=>{
  const card = e.target.closest('.card-transaksi');
  if(!card) return;
  const id = card.getAttribute('data-absen-id');
  showDetail(id);
});

/* ===============================
   FILTER FORM
================================ */
document.getElementById('filterForm').addEventListener('submit', function(e){
  e.preventDefault();
  loadAbsensi(tglAwalInput.value, tglAkhirInput.value);
});

/* ===============================
   REALTIME POLLING (pengganti SSE)
================================ */
function startPolling(){
  let last = 0;
  let tDebounce = null;

  const INTERVAL_MS = 3000; // 3 detik
  const DEBOUNCE_MS = 400;

  async function tick(){
    if (!navigator.onLine) return;

    try{
      const url = `${EP_POLL}?last=${encodeURIComponent(last)}&t=${Date.now()}`;
      const res = await fetch(url, { cache:"no-store", credentials:"include" });
      if(!res.ok) return;

      const j = await res.json();
      if(!j || !j.ok) return;

      if (j.last) last = j.last;

      if (j.changed) {
        clearTimeout(tDebounce);
        tDebounce = setTimeout(()=>{
          if(activeTab === 'tabHarian'){
            loadAbsensi(tglAwalInput.value, tglAkhirInput.value);
          }
        }, DEBOUNCE_MS);
      }
    }catch(e){
      // diam saja
    }
  }

  tick();
  setInterval(tick, INTERVAL_MS);

  document.addEventListener('visibilitychange', function(){
    if (!document.hidden) tick();
  });
}

/* ===============================
   INIT
================================ */
loadAbsensi(tglAwalInput.value, tglAkhirInput.value);
startPolling();

document.getElementById('nama').innerText = "<?= htmlspecialchars($_SESSION['nama'] ?? 'Siswa'); ?>";

(function(){
  function hardRefreshIfNeeded(e){
    if (e && e.persisted) { location.reload(); return; }
  }
  window.addEventListener('pageshow', hardRefreshIfNeeded);

  document.addEventListener('visibilitychange', function(){
    if (!document.hidden && activeTab === 'tabHarian') {
      loadAbsensi(tglAwalInput.value, tglAkhirInput.value);
    }
  });

  window.addEventListener('focus', function(){
    if(activeTab === 'tabHarian'){
      loadAbsensi(tglAwalInput.value, tglAkhirInput.value);
    }
  });
})();
</script>

</body>
</html>
