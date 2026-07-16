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
if (empty($_SESSION['emoney_csrf'])) {
  $_SESSION['emoney_csrf'] = bin2hex(random_bytes(32));
}

// Default untuk input filter (bukan untuk load awal)
$tglAkhir = date('Y-m-d');
$tglAwal  = date('Y-m-d', strtotime('-6 days')); // 7 hari
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>e-Money Detail</title>

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
   UTIL
================================ */
.muted{ color:#6b7280; display:block; margin-top:10px; }
.row{ display:flex; gap:10px; flex-wrap:wrap; }
.w-100{ width:100%; }
.center{ text-align:center; }

/* ===============================
   BUTTONS
================================ */
.btn-kunci{
  border:none;
  border-radius:12px;
  padding:12px 14px;
  font-weight:900;
  cursor:pointer;
  background:#e11d48;
  color:#fff;
  white-space:nowrap;
}

.blue{
  background:#215dcc;
}
.btn-kunci:hover{ filter:brightness(.95); }

.btn-buka{
  border:none;
  border-radius:12px;
  padding:12px 14px;
  font-weight:900;
  cursor:pointer;
  background:#9ca34c;
  color:#111827;
  white-space:nowrap;
}
.btn-buka:hover{ filter:brightness(.97); }

.btn-soft{
  padding:12px 14px;
  border:1px solid #e5e7eb;
  border-radius:12px;
  background:#fff;
  font-weight:900;
  cursor:pointer;
}
.btn-soft:hover{ background:#fafafa; }

.btn-outline{
  padding: 12px 14px;
    border: 1px solid rgb(71 29 225 / 35%);
    border-radius: 12px;
    background: rgb(71 29 225 / 4%);
    font-weight: 900;
    cursor: pointer;
    color: #215dcc;
}
.btn-outline:hover{ background:rgba(225,29,72,.07); }

/* ===============================
   TRANSACTION LIST
================================ */
.transaksi {
  display:flex;
  justify-content:space-between;
  align-items:center;
  background:#fff;
  padding:15px;
  margin:10px 0;
  border-radius:12px;
  box-shadow:0 4px 12px rgba(0,0,0,.08);
  transition:transform .08s ease, box-shadow .08s ease;
}
.transaksi:hover{
  transform:translateY(-1px);
  box-shadow:0 8px 18px rgba(0,0,0,.10);
}
.transaksi .info strong{ display:block; font-size:14px; }
.transaksi .info small{ color:#555; font-size:12px; }
.debit { color:#dc2626; font-weight:900; }   /* KELUAR */
.kredit{ color:#16a34a; font-weight:900; }   /* MASUK */

/* ===============================
   STATUS KARTU
================================ */
.kartu-row{
  display:flex;
  justify-content:space-between;
  align-items:center;
  gap:12px;
}
.kartu-status{ font-weight:900; margin-bottom:4px; }

.badge-ok{
  display:inline-block;
  padding:6px 10px;
  border-radius:999px;
  background:rgba(16,185,129,.12);
  color:#065f46;
  font-weight:900;
  font-size:12px;
}
.badge-lock{
  display:inline-block;
  padding:6px 10px;
  border-radius:999px;
  background:rgba(225,29,72,.12);
  color:#9f1239;
  font-weight:900;
  font-size:12px;
}
.card-status{ transition: background .3s ease, border .3s ease; }
.status-aktif{
  background: linear-gradient(135deg, #ecfdf5, #d1fae5);
  border-left:6px solid #9ca34c;
}
.status-blokir{
  background: linear-gradient(135deg, #fff1f2, #ffe4e6);
  border-left:6px solid #e11d48;
}

/* ===============================
   MODAL (shared)
================================ */
.modal{ position:fixed; inset:0; z-index:9999; }
.modal[hidden]{ display:none; }
.modal-backdrop{
  position:absolute; inset:0;
  background:rgba(0,0,0,.55);
}
.modal-card{
  position:relative;
  width:min(560px, calc(100% - 24px));
  margin:10vh auto 0;
  background:#fff;
  border-radius:18px;
  box-shadow:0 20px 60px rgba(0,0,0,.25);
  overflow:hidden;
}
.modal-head{
  display:flex;
  justify-content:space-between;
  align-items:flex-start;
  gap:12px;
  padding:14px 14px 10px;
  border-bottom:1px solid #eee;
}
.modal-title{ font-weight:900; font-size:16px; }
.modal-sub{ color:#6b7280; font-size:12px; margin-top:4px; }
.modal-x{
  border:none; background:transparent; cursor:pointer;
  font-size:18px; padding:8px; border-radius:10px;
}
.modal-body{ padding:14px; }
.modal-foot{
  padding:12px 14px;
  border-top:1px solid #eee;
  display:grid;
  gap:10px;
}

.detail-row{
  display:flex; justify-content:space-between; gap:12px;
  padding:10px 0; border-bottom:1px dashed #eee;
}
.detail-row:last-child{ border-bottom:none; }
.detail-row .k{ color:#6b7280; font-size:12px; font-weight:900; }
.detail-row .v{ font-size:13px; font-weight:900; text-align:right; word-break:break-word; }
.badge-pill{
  display:inline-block;
  padding:6px 10px;
  border-radius:999px;
  background:#f3f4f6;
  font-weight:900;
  font-size:12px;
}

/* ===============================
   TOPUP MODAL (nice)
================================ */
.topup-hero{
  border-radius:16px;
  padding:14px;
  /*background: linear-gradient(135deg, rgba(225,29,72,.10), rgba(156,163,76,.10));*/
  background: #215dcc;
  color: white;
  border:1px solid rgba(0,0,0,.06);
}
.preset-grid{
  display:grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap:10px;
  margin-top:10px;
}
.preset{
  border:1px solid #e5e7eb;
  background:#fff;
  border-radius:14px;
  padding:12px 10px;
  font-weight:900;
  cursor:pointer;
}
.preset:hover{ background:#fafafa; }
.preset.active{
  border-color: rgb(71 29 225 / 55%);
    background: rgb(71 29 225 / 6%);
    color: #215dcc;
}
.amount-input{
  margin-top:12px;
  display:flex;
  gap:10px;
  align-items:center;
}
.amount-input input{
  flex:1;
  padding:12px 12px;
  border-radius:12px;
  border:1px solid #ddd;
  font-size:14px;
}
.amount-input input:focus{
  outline:none;
  border-color:#215dcc;
  box-shadow: 0 0 6px rgba(30,29,225,.25);
}
.small-note{
  font-size:12px;
  color:#6b7280;
  margin-top:10px;
  line-height:1.35;
  /*padding:0 10px;*/
}

/* ===============================
   BANKING FILTER UI
================================ */
.filter-toggle{
  width:100%;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  border:none;
  background:transparent;
  padding:6px 2px;
  font-weight:900;
  cursor:pointer;
}
.filter-toggle .left{ display:flex; align-items:center; gap:10px; }

.rotate-180{ transform: rotate(180deg); }

.filter-chip{
  margin-left:auto;
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:7px 12px;
  border-radius:999px;
  background:#fff;
  border:1px solid #e5e7eb;
  color:#111827;
  font-weight:900;
  font-size:12px;
  white-space:nowrap;
}
.filter-chip .dot{ width:8px; height:8px; border-radius:999px; background:#9ca34c; }
.filter-chip.inactive .dot{ background:#e5e7eb; }
.filter-chip.inactive{ color:#6b7280; }

.filter-panel{
  margin-top:10px;
  border-top:1px solid #eee;
  padding-top:12px;
}

.preset-chips{
  display:flex;
  flex-wrap:wrap;
  gap:8px;
  margin-top:10px;
}
.preset-chip{
  border:1px solid #e5e7eb;
  background:#fff;
  border-radius:999px;
  padding:9px 12px;
  font-weight:900;
  cursor:pointer;
  font-size:12px;
}
.preset-chip:hover{ background:#fafafa; }
.preset-chip.active{
  border-color:rgba(225,29,72,.55);
  background:rgba(225,29,72,.06);
  color:#9f1239;
}

.filter-grid{
  display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 10px;
}
.filter-grid .field{
    flex: 1;
    padding: 10px 12px;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    background: #fff;
}
.filter-grid label{
  display:block;
  font-size:12px;
  color:#6b7280;
  margin-bottom:6px;
}
.filter-grid input[type="date"]{
    width: 100%;
    border: none;
    outline: none;
    background: transparent;
    padding: 0;
}

.filter-actions{
  position:sticky;
  bottom:0px;
  margin-top:0px;
  padding-top:10px;
  background:linear-gradient(to top, #fff 75%, rgba(255,255,255,0));
}
.filter-actions .bar{
  display:flex;
  gap:10px;
}
.filter-actions button{
  flex:1;
  border-radius:14px;
  padding:12px 14px;
  font-weight:900;
  cursor:pointer;
  border:1px solid #e11d48;
}
.btn-apply{
  background:#e11d48;
  color:#fff;
  border-color:#e11d48;
}
.btn-apply:hover{ filter:brightness(.96); }
.btn-reset{
  background:#fff;
  border-color:#e5e7eb;
  color:#111827;
}
.btn-reset:hover{ background:#fafafa; }

.filter-help{
  margin-top:10px;
  color:#6b7280;
  font-size:12px;
  line-height:1.35;
}

/* Mobile: grid tanggal jadi 1 kolom */
@media (max-width:520px){
  /*.filter-grid{ grid-template-columns:1fr; }*/
}
</style>
</head>

<body>

<header class="top">
  <h1>e-Money</h1>
  <small id="nama">Memuat...</small>
</header>

<section class="saldo">
  <span>Saldo Saat Ini</span>
  <strong id="saldo">Memuat...</strong>
</section>

<div class="card">
  <h2>Isi Saldo</h2>
  <button class="btn-kunci w-100 blue" type="button" onclick="openTopupModal()">
    <i class="fa-solid fa-plus"></i> Isi Saldo Via Transfer
  </button>
  <small class="muted">Top up diproses melalui pembayaran online.</small>
</div>

<!-- BLOKIR KARTU -->
<div class="card card-status">
  <h2>Status Kartu</h2>

  <div class="kartu-row">
    <div>
      <div class="kartu-status" id="kartuStatus">Memuat status...</div>
      <small id="kartuUid" style="display:none">UID: -</small>
    </div>

    <button id="btnKunci" class="btn-kunci" type="button" onclick="toggleKartu()">
      <i class="fa-solid fa-lock"></i> Kunci Kartu
    </button>
  </div>

  <small class="muted">
    Saat kartu dikunci, transaksi menggunakan kartu akan ditolak sampai dibuka kembali.
  </small>
</div>

<!-- FILTER (BANKING STYLE) -->
<div class="card">
  <button class="filter-toggle" type="button" onclick="toggleFilterPanel()">
    <span class="left">
      <i class="fa-solid fa-filter"></i>
      <span>Filter Riwayat</span>
    </span>

    <span id="filterChip" class="filter-chip inactive">
      <span class="dot"></span>
      <span id="filterSummary">Semua</span>
    </span>

    <i id="filterChevron" class="fa-solid fa-chevron-down"></i>
  </button>

  <div id="filterPanel" class="filter-panel" hidden>

    <div class="preset-chips" id="presetChips">
      <button type="button" class="preset-chip" data-preset="today">Hari ini</button>
      <button type="button" class="preset-chip" data-preset="7d">7 hari</button>
      <button type="button" class="preset-chip" data-preset="30d">30 hari</button>
      <button type="button" class="preset-chip" data-preset="month">Bulan ini</button>
      <!--<button type="button" class="preset-chip" data-preset="all">Semua</button>-->
    </div>

    <form id="filterForm">
      <div class="filter-grid">
        <div class="field">
          <label>Dari tanggal</label>
          <input type="date" id="tglAwal" name="tglAwal" required value="<?= $tglAwal ?>">
        </div>
        <div class="field">
          <label>Sampai tanggal</label>
          <input type="date" id="tglAkhir" name="tglAkhir" required value="<?= $tglAkhir ?>">
        </div>
      </div>

      <div class="filter-actions">
        <div class="bar">
          <button type="submit" class="btn-apply">
            <i class="fa-solid fa-check"></i> Terapkan
          </button>
          <button type="button" class="btn-reset" onclick="resetFilter()">
            <i class="fa-solid fa-rotate-left"></i> Reset
          </button>
        </div>
      </div>

      <div class="filter-help">
        Filter hanya aktif setelah klik <b>Terapkan</b>.
      </div>
    </form>

  </div>
</div>

<!-- RIWAYAT TRANSAKSI -->
<div class="card">
  <h2>Riwayat Transaksi</h2>
  <div id="listRiwayat">Memuat...</div>
</div>

<!-- BOTTOM NAV -->
<nav class="bottom-nav">
  <a href="index.php"><i class="fa-solid fa-house"></i>Home</a>
  <a href="emoney.php" class="active"><i class="fa-solid fa-wallet"></i>e-Money</a>
  <a href="perpustakaan.php"><i class="fa-solid fa-book"></i>Buku</a>
  <a href="absensi.php"><i class="fa-solid fa-calendar-check"></i>Absen</a>
  <a href="profil.php"><i class="fa-solid fa-user"></i>Profil</a>
</nav>

<!-- MODAL DETAIL TRANSAKSI -->
<div id="trxModal" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeTrxModal()"></div>

  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="trxTitle">
    <div class="modal-head">
      <div>
        <div id="trxTitle" class="modal-title">Detail Transaksi</div>
        <div id="trxSub" class="modal-sub">-</div>
      </div>
      <button class="modal-x" type="button" onclick="closeTrxModal()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="modal-body" id="trxBody"></div>

    <div class="modal-foot">
      <button class="btn-soft w-100" type="button" onclick="closeTrxModal()">Tutup</button>
    </div>
  </div>
</div>

<!-- MODAL TOPUP (MENARIK) -->
<div id="topupModal" class="modal" hidden>
  <div class="modal-backdrop" onclick="closeTopupModal()"></div>

  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="topupTitle">
    <div class="modal-head">
      <div>
        <div id="topupTitle" class="modal-title">Isi Saldo</div>
        <div class="modal-sub">Pilih nominal cepat atau masukkan nominal sendiri.</div>
      </div>
      <button class="modal-x" type="button" onclick="closeTopupModal()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <div class="modal-body">
      <div class="topup-hero">
        <div style="font-weight:900;margin-bottom:6px">
          <i class="fa-solid fa-wallet"></i> Top Up via Transfer
        </div>
        <div class="small-note" style="color:white">
          Setelah pembayaran berhasil, saldo akan bertambah secara otomatis.
        </div>
      </div>

      <div class="preset-grid" id="presetGrid">
        <button class="preset" type="button" data-amt="10000">Rp 10.000</button>
        <button class="preset" type="button" data-amt="20000">Rp 20.000</button>
        <button class="preset" type="button" data-amt="50000">Rp 50.000</button>
        <button class="preset" type="button" data-amt="100000">Rp 100.000</button>
        <button class="preset" type="button" data-amt="150000">Rp 150.000</button>
        <button class="preset" type="button" data-amt="200000">Rp 200.000</button>
      </div>

      <div class="amount-input">
        <input id="topupAmount" type="text" inputmode="numeric" placeholder="Nominal lain (contoh: 75000)">
        <button class="btn-outline" type="button" onclick="clearTopupAmount()">
          <i class="fa-solid fa-eraser"></i>
        </button>
      </div>

      <div class="small-note">
        <ul style="margin:8px 0 0 18px;padding:0">
          <li>Minimal top up Rp 1.000. Pastikan nominal benar sebelum lanjut</li>
          <li>Biaya admin berlaku sesuai ketentuan setiap bank</li>
        </ul>
      </div>
    </div>

    <div class="modal-foot">
      <button class="btn-kunci w-100 blue" type="button" onclick="submitTopupFromModal()">
        <i class="fa-solid fa-arrow-right-to-bracket"></i> Lanjut Pembayaran
      </button>
      <!--<button class="btn-soft w-100" type="button" onclick="closeTopupModal()">Batal</button>-->
    </div>
  </div>
</div>

<script>
const API = <?=json_encode(rtrim(sds_base_url('emoney/api'), '/'), JSON_UNESCAPED_SLASHES)?>;

function rupiah(n){
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(n||0));
}

function formatTanggalWaktu(dateStr){
  if(!dateStr) return '-';
  const d = new Date(String(dateStr).replace(' ', 'T'));
  if (isNaN(d.getTime())) return dateStr;
  const tgl = d.toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'});
  const jam = d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
  return `${tgl} • ${jam} WIB`;
}

function badgeKategori(kat='', ket=''){
  const k = (kat||'').toUpperCase();
  const s = (ket||'').toLowerCase();

  if(k==='TOPUP'){
    if(s.includes('duitku') || s.includes('transfer')) return '🟣 Topup (Transfer)';
    return '🟢 Topup (Sekolah)';
  }
  if(k==='PEMBELIAN') return '🔴 Pembelian';
  if(k==='TRANSFER') return '🔵 Transfer';
  return '⚪ Transaksi';
}

/* ===============================
   FILTER MODE (BANKING)
   - Filter hanya aktif setelah Terapkan
================================ */
let FILTER_ACTIVE = false;
let ACTIVE_TGL_AWAL = '';
let ACTIVE_TGL_AKHIR = '';

function updateFilterSummary(){
  const sum = document.getElementById('filterSummary');
  const chip = document.getElementById('filterChip');

  if(FILTER_ACTIVE){
    sum.textContent = `${ACTIVE_TGL_AWAL} s/d ${ACTIVE_TGL_AKHIR}`;
    chip.classList.remove('inactive');
  }else{
    sum.textContent = 'Semua';
    chip.classList.add('inactive');
  }
}

function toggleFilterPanel(){
  const p = document.getElementById('filterPanel');
  const chev = document.getElementById('filterChevron');
  const isOpen = !p.hasAttribute('hidden');

  if(isOpen){
    p.setAttribute('hidden','');
    chev.classList.remove('rotate-180');
  }else{
    p.removeAttribute('hidden');
    chev.classList.add('rotate-180');
  }
}

function applyFilter(tAw, tAk){
  FILTER_ACTIVE = true;
  ACTIVE_TGL_AWAL = tAw;
  ACTIVE_TGL_AKHIR = tAk;
  updateFilterSummary();
  loadRiwayat(ACTIVE_TGL_AWAL, ACTIVE_TGL_AKHIR);

  const p = document.getElementById('filterPanel');
  if(p && !p.hasAttribute('hidden')) toggleFilterPanel();
}

function resetFilter(){
  FILTER_ACTIVE = false;
  ACTIVE_TGL_AWAL = '';
  ACTIVE_TGL_AKHIR = '';
  updateFilterSummary();

  loadRiwayat('', '');

  // reset preset UI
  document.querySelectorAll('#presetChips .preset-chip').forEach(b=>b.classList.remove('active'));

  const p = document.getElementById('filterPanel');
  if(p && !p.hasAttribute('hidden')) toggleFilterPanel();
}

document.getElementById('filterForm').addEventListener('submit', function(e){
  e.preventDefault();
  applyFilter(
    document.getElementById('tglAwal').value,
    document.getElementById('tglAkhir').value
  );
});

/* Preset chips (tidak auto apply, hanya mengisi tanggal) */
function pad2(n){ return String(n).padStart(2,'0'); }
function fmtDate(d){ return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`; }
function firstDayOfMonth(d){ return new Date(d.getFullYear(), d.getMonth(), 1); }

document.getElementById('presetChips').addEventListener('click', function(e){
  const btn = e.target.closest('.preset-chip');
  if(!btn) return;

  document.querySelectorAll('#presetChips .preset-chip').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');

  const preset = btn.getAttribute('data-preset');
  const now = new Date();
  let start = null;
  let end = null;

  if(preset === 'today'){
    start = new Date(now); end = new Date(now);
  }else if(preset === '7d'){
    end = new Date(now);
    start = new Date(now); start.setDate(start.getDate()-6);
  }else if(preset === '30d'){
    end = new Date(now);
    start = new Date(now); start.setDate(start.getDate()-29);
  }else if(preset === 'month'){
    end = new Date(now);
    start = firstDayOfMonth(now);
  }else if(preset === 'all'){
    // langsung reset (Semua)
    resetFilter();
    return;
  }

  document.getElementById('tglAwal').value = fmtDate(start);
  document.getElementById('tglAkhir').value = fmtDate(end);
});

/* ===============================
   LOAD RIWAYAT + CLICK TO MODAL
================================ */
function loadRiwayat(tglAwal='', tglAkhir=''){
  let url = API + '/riwayat.php';
  if(tglAwal && tglAkhir){
    url += `?tglAwal=${encodeURIComponent(tglAwal)}&tglAkhir=${encodeURIComponent(tglAkhir)}`;
  }

  fetch(url,{credentials:'include'})
    .then(r=>r.json())
    .then(d=>{
      const list = document.getElementById('listRiwayat');

      if(!d.success || !Array.isArray(d.data) || d.data.length===0){
        list.innerHTML = '<small>Belum ada transaksi</small>';
        return;
      }

      let html='';
      d.data.forEach(t=>{
        const waktu = formatTanggalWaktu(t.tanggal);
        const ket   = t.keterangan || 'Transaksi';
        const jenis = (t.jenis || 'DEBIT').toUpperCase();
        const nominal = Number(t.nominal||0);
        const trxJson = encodeURIComponent(JSON.stringify(t));

        html += `
          <div class="transaksi" style="cursor:pointer" data-trx="${trxJson}" onclick="openTrxModal(this)">
            <div class="info" style="width:70%">
              <strong>${badgeKategori(t.kategori, t.keterangan)}</strong>
              <small style="font-weight:400">${ket}</small><br>
              <small>${waktu}</small>
            </div>
            <div class="${jenis==='DEBIT'?'debit':'kredit'}">
              ${jenis==='DEBIT' ? '-' : '+'}${rupiah(nominal)}
            </div>
          </div>
        `;
      });

      list.innerHTML = html;
    })
    .catch(()=>{
      document.getElementById('listRiwayat').innerHTML = '<small>Gagal memuat riwayat</small>';
    });
}

/* ===============================
   MODAL DETAIL TRANSAKSI
================================ */
function openTrxModal(el){
  try{
    const raw = el.getAttribute('data-trx') || '';
    const t = JSON.parse(decodeURIComponent(raw));

    const title = document.getElementById('trxTitle');
    const sub   = document.getElementById('trxSub');
    const body  = document.getElementById('trxBody');

    const waktu = formatTanggalWaktu(t.tanggal);
    const jenis = (t.jenis || '').toUpperCase();
    const jenisLabel = (jenis === 'KREDIT') ? 'MASUK' : (jenis === 'DEBIT') ? 'KELUAR' : (jenis || '-');

    const nominal = Number(t.nominal||0);
    const tanda = (jenis === 'DEBIT') ? '-' : '+';

    title.textContent = (t.kategori || 'Transaksi');
    sub.textContent = waktu;

    let rows = `
      <div class="detail-row"><div class="k">Kategori</div><div class="v"><span class="badge-pill">${t.kategori||'-'}</span></div></div>
      <div class="detail-row"><div class="k">Jenis</div><div class="v">${jenisLabel}</div></div>
      <div class="detail-row"><div class="k">Nominal</div><div class="v">${tanda}${rupiah(nominal)}</div></div>
      <div class="detail-row"><div class="k">Keterangan</div><div class="v">${(t.keterangan||'-')}</div></div>
    `;

    // Topup Duitku detail (jika ada field ini dari riwayat.php)
    const isTopup = String(t.kategori||'').toUpperCase() === 'TOPUP';
    const isDuitku = (t.merchant_order_id || t.duitku_reference);

    if(isTopup && isDuitku){
      rows += `
        <div class="detail-row"><div class="k">Status</div><div class="v">${t.topup_status || '-'}</div></div>
        <div class="detail-row"><div class="k">Paid At</div><div class="v">${t.paid_at || '-'}</div></div>
        <div class="detail-row"><div class="k">Merchant Order ID</div><div class="v">${t.merchant_order_id || '-'}</div></div>
        <div class="detail-row"><div class="k">Duitku Reference</div><div class="v">${t.duitku_reference || '-'}</div></div>
      `;
    }

    body.innerHTML = rows;

    const modal = document.getElementById('trxModal');
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
  }catch(e){
    alert('Gagal membuka detail transaksi');
  }
}

function closeTrxModal(){
  const modal = document.getElementById('trxModal');
  modal.hidden = true;
  document.body.style.overflow = '';
}

/* ===============================
   SALDO + STATUS KARTU
================================ */
let KARTU_BLOKIR = 0;

function renderKartu(blokir){
  KARTU_BLOKIR = Number(blokir||0);

  const statusEl = document.getElementById('kartuStatus');
  const btn      = document.getElementById('btnKunci');
  const card     = document.querySelector('.card-status');

  card.classList.remove('status-aktif','status-blokir');
  card.classList.add(KARTU_BLOKIR ? 'status-blokir' : 'status-aktif');

  if(KARTU_BLOKIR){
    statusEl.innerHTML = `<span class="badge-lock"><i class="fa-solid fa-circle-xmark"></i> DIBLOKIR</span>`;
    btn.className = 'btn-buka';
    btn.innerHTML = `<i class="fa-solid fa-lock-open"></i> Buka Kunci`;
  }else{
    statusEl.innerHTML = `<span class="badge-ok"><i class="fa-solid fa-circle-check"></i> AKTIF</span>`;
    btn.className = 'btn-kunci';
    btn.innerHTML = `<i class="fa-solid fa-lock"></i> Kunci Kartu`;
  }
}

function loadSaldo(){
  fetch(API + '/saldo.php',{credentials:'include'})
    .then(r=>r.json())
    .then(d=>{
      if(d.success){
        document.getElementById('saldo').innerText = rupiah(d.data.saldo);
        document.getElementById('nama').innerText = d.data.nama;
        renderKartu(d.data.blokir);
      }
    });
}

function toggleKartu(){
  const action = KARTU_BLOKIR ? 'unlock' : 'lock';
  const msg = KARTU_BLOKIR
    ? 'Buka kunci kartu? Kartu bisa dipakai transaksi lagi.'
    : 'Kunci kartu? Transaksi dengan kartu akan ditolak.';

  if(!confirm(msg)) return;

  const fd = new FormData();
  fd.append('action', action);

  fetch(API + '/kartu.php', {
    method: 'POST',
    body: fd,
    credentials: 'include'
  })
  .then(r=>r.json())
  .then(d=>{
    if(!d.success){
      alert(d.message || 'Gagal memperbarui status kartu');
      return;
    }
    renderKartu(d.data.blokir);
  })
  .catch(()=>alert('Koneksi gagal'));
}

/* ===============================
   TOPUP MODAL (UI)
================================ */
let TOPUP_AMOUNT = 0;

function openTopupModal(){
  TOPUP_AMOUNT = 0;
  document.getElementById('topupAmount').value = '';
  document.querySelectorAll('#presetGrid .preset').forEach(b=>b.classList.remove('active'));

  const modal = document.getElementById('topupModal');
  modal.hidden = false;
  document.body.style.overflow = 'hidden';
}
function closeTopupModal(){
  const modal = document.getElementById('topupModal');
  modal.hidden = true;
  document.body.style.overflow = '';
}
function clearTopupAmount(){
  TOPUP_AMOUNT = 0;
  document.getElementById('topupAmount').value = '';
  document.querySelectorAll('#presetGrid .preset').forEach(b=>b.classList.remove('active'));
}

document.getElementById('presetGrid').addEventListener('click', function(e){
  const btn = e.target.closest('.preset');
  if(!btn) return;

  document.querySelectorAll('#presetGrid .preset').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');

  TOPUP_AMOUNT = Number(btn.getAttribute('data-amt') || 0);
  document.getElementById('topupAmount').value = String(TOPUP_AMOUNT);
});

document.getElementById('topupAmount').addEventListener('input', function(){
  const val = String(this.value || '').replace(/[^\d]/g,'');
  this.value = val;
  TOPUP_AMOUNT = Number(val || 0);
  document.querySelectorAll('#presetGrid .preset').forEach(b=>b.classList.remove('active'));
});

function submitTopupFromModal(){
  const amount = Number(TOPUP_AMOUNT || 0);
  if(!Number.isFinite(amount) || amount < 1000){
    alert('Nominal tidak valid (minimal 1.000).');
    return;
  }

  const fd = new FormData();
  fd.append('amount', String(amount));
  fd.append('csrf', <?= json_encode($_SESSION['emoney_csrf']) ?>);

  fetch(API + '/topup_create.php', {
    method: 'POST',
    body: fd,
    credentials: 'include'
  })
  .then(r => r.json())
  .then(d => {
    if(!d.success){
      alert(d.message || 'Gagal membuat transaksi top up');
      return;
    }
    window.location.href = d.data.paymentUrl;
  })
  .catch(() => alert('Koneksi gagal'));
}

/* ===============================
   GLOBAL SHORTCUTS
================================ */
document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape'){
    const m1 = document.getElementById('trxModal');
    const m2 = document.getElementById('topupModal');
    if(m1 && !m1.hidden) closeTrxModal();
    if(m2 && !m2.hidden) closeTopupModal();
  }
});

/* ===============================
   LOAD AWAL + AUTO REFRESH
   - Riwayat default: semua
================================ */
updateFilterSummary();
loadSaldo();
loadRiwayat('', '');

setInterval(loadSaldo, 3000);
setInterval(()=>{
  if(FILTER_ACTIVE) loadRiwayat(ACTIVE_TGL_AWAL, ACTIVE_TGL_AKHIR);
  else loadRiwayat('', '');
}, 6000);
</script>

<script>
(function(){
  function hardRefreshIfNeeded(e){
    if (e && e.persisted) location.reload();
  }
  window.addEventListener('pageshow', hardRefreshIfNeeded);

  document.addEventListener('visibilitychange', function(){
    if (!document.hidden) {
      if (typeof loadSaldo === 'function') loadSaldo();
      if (typeof loadRiwayat === 'function') {
        if(FILTER_ACTIVE) loadRiwayat(ACTIVE_TGL_AWAL, ACTIVE_TGL_AKHIR);
        else loadRiwayat('', '');
      }
    }
  });

  window.addEventListener('focus', function(){
    if (typeof loadSaldo === 'function') loadSaldo();
  });
})();

// Notifikasi balik dari Duitku
if (new URLSearchParams(location.search).get('topup') === 'return') {
  alert('Kembali dari pembayaran. Saldo akan diperbarui otomatis setelah pembayaran terkonfirmasi.');
}
</script>

</body>
</html>
