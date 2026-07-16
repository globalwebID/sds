<?php
session_start();
require_once __DIR__.'/_central_control.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['login'])) {
  header('Location: ../login.php');
  exit;
}

// Default filter: 30 hari terakhir
$tglAkhir = date('Y-m-d');
$tglAwal  = date('Y-m-d', strtotime('-30 days'));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Perpustakaan</title>

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
   Ringkasan (2 kotak)
================================ */
.summary{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap:12px;
  margin: 12px;
}
.summary .box{
  background:#fff;
  border-radius:14px;
  padding:14px;
  box-shadow: 0 6px 18px rgba(0,0,0,.08);
}
.summary .box span{
  display:block;
  font-size:12px;
  color:#6b7280;
}
.summary .box strong{
  display:block;
  margin-top:4px;
  font-size:18px;
}

/* ===============================
   Tabs
================================ */
.tabs{
  margin: 0 12px 12px;
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
   Filter Modern
================================ */
.filter-group{
  display:flex;
  gap:10px;
  flex-wrap:wrap;
  margin-top:10px;
}
.filter-group input[type="date"]{
  flex:1;
  padding:10px 12px;
  border-radius:12px;
  border:1px solid #e5e7eb;
  background:#fff;
}
.filter-group input[type="date"]:focus{
  outline:none;
  border-color:#e11d48;
  box-shadow: 0 0 0 3px rgba(225,29,72,.15);
}
.filter-group button{
  width:100%;
  padding:11px 12px;
  border:0;
  border-radius:12px;
  background:#e11d48;
  color:#fff;
  font-weight:800;
  cursor:pointer;
}

/* ===============================
   List Card Item
================================ */
.item{
  background:#fff;
  margin: 10px 0;
  padding: 14px;
  border-radius: 14px;
  box-shadow: 0 6px 18px rgba(0,0,0,.08);
  display:flex;
  justify-content:space-between;
  gap:12px;

  cursor:pointer;
  transition: transform .08s ease, box-shadow .08s ease;
}
.item:active{
  transform: scale(.99);
  box-shadow: 0 4px 12px rgba(0,0,0,.10);
}

.item .left{
  flex:1;
  min-width:0;
}
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
  font-weight:800;
  white-space:nowrap;
  height: fit-content;
}
.b-green{ background:#dcfce7; color:#166534; }
.b-yellow{ background:#fef9c3; color:#854d0e; }
.b-red{ background:#fee2e2; color:#991b1b; }

.loading, .empty, .error, .note-lite{
  margin: 10px 0;
  padding: 12px 14px;
  border-radius: 14px;
  background: #fff;
  box-shadow: 0 6px 18px rgba(0,0,0,.06);
  color:#6b7280;
  font-size: 13px;
}
.note-lite{
  margin: 0 12px 12px;
  box-shadow:none;
  background:transparent;
  padding: 0 2px;
  color:#9ca3af;
  text-align: center;
}

/* ===============================
   MODAL DETAIL BUKU (BOTTOM SHEET)
================================ */
.modal{
  position:fixed;
  inset:0;
  background: rgba(17,24,39,.45);
  display:none;
  align-items:flex-end;
  justify-content:center;
  z-index:9999;
}
.modal.show{ display:flex; }

.modal .sheet{
  width:min(560px, 100%);
  background:#fff;
  border-radius: 18px 18px 0 0;
  box-shadow: 0 -14px 40px rgba(0,0,0,.18);
  padding: 14px 14px 18px;
  max-height: 80vh;
  overflow:auto;
}
.modal .sheet .head{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-bottom:10px;
}
.modal .sheet .head h3{
  margin:0;
  font-size:16px;
  line-height:1.2;
}
.modal .close{
  border:0;
  background:#f3f4f6;
  border-radius:12px;
  padding:10px 12px;
  cursor:pointer;
  font-weight:900;
}
.kv{
  display:grid;
  grid-template-columns: 140px 1fr;
  gap:8px 10px;
  font-size:13px;
}
.kv .k{ color:#6b7280; }
.kv .v{ color:#111827; font-weight:700; word-break:break-word; }

@media (max-width: 420px){
  .kv{ grid-template-columns: 120px 1fr; }
}
</style>
</head>

<body>

<header class="top">
  <h1>Perpustakaan</h1>
  <small id="nama">Memuat...</small>
</header>

<!-- Ringkasan -->
<div class="summary">
  <div class="box">
    <span><i class="fa-solid fa-book-open"></i> Pinjaman Aktif</span>
    <strong id="pinjamanAktif">-</strong>
  </div>
  <div class="box">
    <span><i class="fa-solid fa-money-bill-wave"></i> Denda Aktif</span>
    <strong id="dendaAktif">-</strong>
  </div>
</div>

<!-- Last update -->
<div class="note-lite" id="lastUpdate">Terakhir update: -</div>

<!-- Tabs -->
<div class="tabs" role="tablist" aria-label="Tab Perpustakaan">
  <button type="button" class="tab-btn active" data-tab="tabAktif">
    <i class="fa-solid fa-book-open"></i> Pinjaman Aktif
  </button>
  <button type="button" class="tab-btn" data-tab="tabRiwayat">
    <i class="fa-solid fa-clock-rotate-left"></i> Riwayat Peminjaman
  </button>
</div>

<!-- PANEL: Pinjaman Aktif -->
<div id="tabAktif" class="tab-pane active">
  <div class="card">
    <h2>Pinjaman Aktif</h2>
    <div id="listAktif" class="loading">Memuat...</div>
  </div>
</div>

<!-- PANEL: Riwayat -->
<div id="tabRiwayat" class="tab-pane">
  <div class="card">
    <h2>Filter Riwayat</h2>
    <form id="filterForm" class="filter-group">
      <input type="date" id="tglAwal" required value="<?= $tglAwal ?>">
      <input type="date" id="tglAkhir" required value="<?= $tglAkhir ?>">
      <button type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
    </form>
  </div>

  <div class="card">
    <h2>Riwayat Peminjaman</h2>
    <div id="listRiwayat" class="empty"><small>Memuat otomatis saat tab dibuka...</small></div>
  </div>
</div>

<!-- Bottom Nav -->
<nav class="bottom-nav">
  <a href="index.php"><i class="fa-solid fa-house"></i>Home</a>
  <a href="emoney.php"><i class="fa-solid fa-wallet"></i>e-Money</a>
  <a href="perpustakaan.php" class="active"><i class="fa-solid fa-book"></i>Buku</a>
  <a href="absensi.php"><i class="fa-solid fa-calendar-check"></i>Absen</a>
  <a href="profil.php"><i class="fa-solid fa-user"></i>Profil</a>
</nav>

<!-- MODAL DETAIL -->
<div id="detailModal" class="modal" aria-hidden="true">
  <div class="sheet" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
    <div class="head">
      <h3 id="detailTitle">Detail Buku</h3>
      <button type="button" class="close" id="btnCloseDetail" aria-label="Tutup">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div id="detailBody" class="loading">Memuat...</div>
  </div>
</div>

<script>
/* ============================================================
   CONFIG
============================================================ */
const API = <?=json_encode(rtrim(sds_base_url('emoney/api'), '/'), JSON_UNESCAPED_SLASHES)?>;
const EP_AKTIF   = API + '/perpustakaan_aktif.php';
const EP_RIWAYAT = API + '/perpustakaan_riwayat.php';
const EP_DETAIL  = API + '/perpustakaan_detail.php';
const REFRESH_MS = 15000;

/* ============================================================
   STATE
============================================================ */
let activeTab = 'tabAktif';
let riwayatLoadedOnce = false;
let refreshTimer = null;

/* ============================================================
   TAB
============================================================ */
function setTab(tabId){
  activeTab = tabId;

  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));

  document.getElementById(tabId)?.classList.add('active');
  document.querySelector(`.tab-btn[data-tab="${tabId}"]`)?.classList.add('active');

  if(tabId === 'tabAktif'){
    loadPerpusAktif();
    startAutoRefreshAktif();
  } else {
    stopAutoRefresh();
    if(!riwayatLoadedOnce){
      loadPerpusRiwayat();
    }
  }
}

document.querySelectorAll('.tab-btn').forEach(btn=>{
  btn.addEventListener('click', ()=> setTab(btn.getAttribute('data-tab')));
});

/* ============================================================
   AUTO REFRESH (HANYA TAB AKTIF)
============================================================ */
function startAutoRefreshAktif(){
  stopAutoRefresh();
  refreshTimer = setInterval(() => {
    if(activeTab === 'tabAktif') loadPerpusAktif();
  }, REFRESH_MS);
}
function stopAutoRefresh(){
  if(refreshTimer){
    clearInterval(refreshTimer);
    refreshTimer = null;
  }
}

/* ============================================================
   HELPERS
============================================================ */
function rupiah(n){
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(n||0));
}
function formatTanggalIndo(dateStr){
  if(!dateStr) return '-';
  const d = new Date(String(dateStr).replace(' ', 'T'));
  if (isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('id-ID', {day:'numeric', month:'long', year:'numeric'});
}
function badgeStatus(status=''){
  const s = (status||'').toLowerCase();
  if(s.includes('terlambat')) return ['b-red', status];
  if(s.includes('belum')) return ['b-yellow', status];
  if(s.includes('sudah')) return ['b-green', status];
  return ['b-red', status || '-'];
}

function escapeHtml(str){
  return String(str ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

function showError(container, msg){
  container.className = 'error';
  container.innerHTML = `<small>${escapeHtml(msg)}</small>`;
}
function setLastUpdate(label){
  const el = document.getElementById('lastUpdate');
  if(!el) return;
  const now = new Date().toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
  el.textContent = `Terakhir update: ${now}${label ? ' • ' + label : ''}`;
}

/* ============================================================
   RENDER LIST (KLIKABLE)
============================================================ */
function renderList(container, items, emptyText){
  if(!items || items.length === 0){
    container.className = 'empty';
    container.innerHTML = `<small>${escapeHtml(emptyText)}</small>`;
    return;
  }

  let html = '';
  items.forEach(it=>{
    const [cls, label] = badgeStatus(it.status);
    const judul = it.judul || '(Judul tidak ditemukan)';
    const judulSafe = escapeHtml(judul);

    // wajib: id_pinjam & id_buku dari API
    const idPinjam = it.id_pinjam ?? it.ID_PINJAM ?? '';
    const idBuku   = it.id_buku   ?? it.ID_BUKU   ?? '';

    html += `
      <div class="item" role="button" tabindex="0"
           title="Klik untuk lihat detail"
           data-id="${escapeHtml(idPinjam)}"
           data-idbuku="${escapeHtml(idBuku)}">
        <div class="left">
          <strong title="${judulSafe}">${judulSafe}</strong>
          <small>Pinjam: ${escapeHtml(formatTanggalIndo(it.tgl_pinjam))}</small>
          <small>Kembali: ${escapeHtml(formatTanggalIndo(it.tgl_kembali))}</small>
          <small>Denda: ${escapeHtml(rupiah(it.denda))}</small>
        </div>
        <div class="badge ${cls}">${escapeHtml(label || '-')}</div>
      </div>
    `;
  });

  container.className = '';
  container.innerHTML = html;
}

/* ============================================================
   FETCH JSON SAFE
============================================================ */
async function fetchJsonSafe(url){
  const res  = await fetch(url, { credentials:'include' });
  const text = await res.text();

  let data;
  try { data = JSON.parse(text); }
  catch { throw new Error(`HTTP ${res.status} bukan JSON. Respon: ${text.slice(0, 200)}`); }

  if (!res.ok) throw new Error(`HTTP ${res.status}: ${data.message || 'Request gagal'}`);
  return data;
}

function applyHeaderAndSummaryFromAktif(d){
  const nama = (d.data?.anggota?.nama) || "<?= htmlspecialchars($_SESSION['nama'] ?? 'Siswa') ?>";
  document.getElementById('nama').innerText = nama;

  document.getElementById('pinjamanAktif').innerText = d.data?.ringkasan?.pinjaman_aktif ?? 0;
  document.getElementById('dendaAktif').innerText = rupiah(d.data?.ringkasan?.total_denda_aktif ?? 0);
}

function getRiwayatUrl(){
  const tglAwal  = document.getElementById('tglAwal')?.value || '';
  const tglAkhir = document.getElementById('tglAkhir')?.value || '';
  return `${EP_RIWAYAT}?tglAwal=${encodeURIComponent(tglAwal)}&tglAkhir=${encodeURIComponent(tglAkhir)}`;
}

/* ============================================================
   LOAD DATA
============================================================ */
function loadPerpusAktif(){
  const listAktif = document.getElementById('listAktif');
  listAktif.className = 'loading';
  listAktif.innerText = 'Memuat...';

  fetchJsonSafe(EP_AKTIF)
    .then(d=>{
      if(!d.success){
        showError(listAktif, d.message || 'Gagal memuat pinjaman aktif');
        document.getElementById('nama').innerText = d.message || 'Gagal memuat';
        return;
      }

      applyHeaderAndSummaryFromAktif(d);
      renderList(listAktif, d.data?.pinjaman_aktif, 'Tidak ada pinjaman aktif');
      setLastUpdate('Pinjaman Aktif');
    })
    .catch(err=>{
      console.error(err);
      showError(listAktif, err.message);
    });
}

function loadPerpusRiwayat(){
  const listRiwayat = document.getElementById('listRiwayat');
  listRiwayat.className = 'loading';
  listRiwayat.innerText = 'Memuat...';

  fetchJsonSafe(getRiwayatUrl())
    .then(d=>{
      if(!d.success){
        showError(listRiwayat, d.message || 'Gagal memuat riwayat');
        return;
      }

      riwayatLoadedOnce = true;
      renderList(listRiwayat, d.data?.riwayat, 'Belum ada riwayat peminjaman pada rentang ini');
      setLastUpdate('Riwayat');
    })
    .catch(err=>{
      console.error(err);
      showError(listRiwayat, err.message);
    });
}

/* ============================================================
   FILTER FORM (RIWAYAT)
============================================================ */
document.getElementById('filterForm')?.addEventListener('submit', function(e){
  e.preventDefault();
  if(activeTab !== 'tabRiwayat') setTab('tabRiwayat');
  riwayatLoadedOnce = true;
  loadPerpusRiwayat();
});

/* ============================================================
   MODAL DETAIL
============================================================ */
const modal = document.getElementById('detailModal');
const detailBody = document.getElementById('detailBody');
const btnClose = document.getElementById('btnCloseDetail');

function openModal(){
  modal?.classList.add('show');
  modal?.setAttribute('aria-hidden','false');
}
function closeModal(){
  modal?.classList.remove('show');
  modal?.setAttribute('aria-hidden','true');
  detailBody.className = 'loading';
  detailBody.innerText = 'Memuat...';
}

btnClose?.addEventListener('click', closeModal);
modal?.addEventListener('click', (e)=>{
  if(e.target === modal) closeModal();
});
document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape' && modal?.classList.contains('show')) closeModal();
});

function renderDetailContent(obj){
  const safe = (v)=> (v === null || v === undefined || v === '') ? '-' : String(v);

  // ✅ edisi: kalau NULL/kosong tampilkan "Tidak ada"
  const safeEdisi = (v)=>{
    if (v === null || v === undefined) return 'Tidak ada';
    const s = String(v).trim();
    if (!s || s === '-' ) return 'Tidak ada';
    return s;
  };

  const rows = [
    ['Judul', safe(obj.judul)],
    ['Pengarang', safe(obj.pengarang)],
    ['Penerbit', safe(obj.penerbit)], // pastikan API detail sudah kirim publisher_name
    ['Tahun', safe(obj.tahun)],
    ['ISBN', safe(obj.isbn)],
    ['Edisi', safeEdisi(obj.edisi)],

    ['Tanggal Pinjam', formatTanggalIndo(obj.tgl_pinjam)],
    ['Batas Kembali', formatTanggalIndo(obj.tgl_kembali)],
    ['Resi', safe(obj.resi)],
    ['Status', safe(obj.status)],
    ['Denda', rupiah(obj.denda)],
  ].filter(r => r[1] !== '-' && r[1] !== 'Rp 0');

  let kvHtml = `<div class="kv">`;
  rows.forEach(([k,v])=>{
    kvHtml += `<div class="k">${escapeHtml(k)}</div><div class="v">${escapeHtml(v)}</div>`;
  });
  kvHtml += `</div>`;

  // cover + detail
  if (obj.foto) {
    const fotoUrl = escapeHtml(obj.foto);
    return `
      <div style="display:grid;gap:12px;align-items:flex-start;margin-bottom:12px">
        <img src="${fotoUrl}" alt="Cover"
          onerror="this.style.display='none'"
          style="width:40%;height:auto;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;place-self: center;">
        <div style="flex:1">${kvHtml}</div>
      </div>
    `;
  }

  return kvHtml;
}

async function showDetailFetch(idPinjam, idBuku){
  openModal();

  if(!idPinjam || !idBuku){
    detailBody.className = 'error';
    detailBody.innerHTML = `<small>id_pinjam / id_buku tidak ditemukan. Pastikan API mengirim field itu.</small>`;
    return;
  }

  try{
    detailBody.className = 'loading';
    detailBody.innerText = 'Memuat detail...';

    const url = `${EP_DETAIL}?id_pinjam=${encodeURIComponent(idPinjam)}&id_buku=${encodeURIComponent(idBuku)}`;
    const d = await fetchJsonSafe(url);

    if(!d.success){
      detailBody.className = 'error';
      detailBody.innerHTML = `<small>${escapeHtml(d.message || 'Gagal memuat detail')}</small>`;
      return;
    }

    const detail = d.data?.detail ?? d.data ?? {};
    detailBody.className = '';
    detailBody.innerHTML = renderDetailContent(detail);
  }catch(err){
    console.error(err);
    detailBody.className = 'error';
    detailBody.innerHTML = `<small>${escapeHtml(err.message || 'Gagal memuat detail')}</small>`;
  }
}

/* ============================================================
   EVENT DELEGATION: klik item aktif & riwayat
============================================================ */
function bindListClick(containerId){
  const container = document.getElementById(containerId);
  if(!container) return;

  function handler(card){
    const idPinjam = card.getAttribute('data-id') || '';
    const idBuku   = card.getAttribute('data-idbuku') || '';
    showDetailFetch(idPinjam, idBuku);
  }

  container.addEventListener('click', (e)=>{
    const card = e.target.closest('.item');
    if(!card) return;
    handler(card);
  });

  container.addEventListener('keydown', (e)=>{
    if(e.key !== 'Enter') return;
    const card = e.target.closest('.item');
    if(!card) return;
    handler(card);
  });
}
bindListClick('listAktif');
bindListClick('listRiwayat');

/* ============================================================
   LOAD AWAL
============================================================ */
loadPerpusAktif();
startAutoRefreshAktif();

/* ============================================================
   HARD REFRESH + VISIBILITY (ANDROID/WV)
============================================================ */
(function(){
  function hardRefreshIfNeeded(e){
    if (e && e.persisted) { location.reload(); return; }
  }
  window.addEventListener('pageshow', hardRefreshIfNeeded);

  document.addEventListener('visibilitychange', function(){
    if (!document.hidden) {
      if(activeTab === 'tabAktif') loadPerpusAktif();
      else if(activeTab === 'tabRiwayat' && riwayatLoadedOnce) loadPerpusRiwayat();
    }
  });

  window.addEventListener('focus', function(){
    if(activeTab === 'tabAktif') loadPerpusAktif();
  });
})();
</script>

</body>
</html>
