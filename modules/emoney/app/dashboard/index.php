<?php
session_start();
require_once dirname(__DIR__,2).'/config/runtime.php';
$centralControlConnection=sds_mysqli('main');
sds_apply_central_controls($centralControlConnection,'E-Money');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['login'])) {
  header('Location: ../login.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Dashboard</title>

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
:root{
  --red:#e11d48;
  --olive:#9ca34c;
  --bg:#f3f4f6;
  --card:#ffffff;
  --text:#111827;
  --muted:#6b7280;
  --shadow: 0 10px 28px rgba(0,0,0,.10);
  --shadow-soft: 0 8px 20px rgba(0,0,0,.08);
  --radius: 10px;
  --radius-lg: 15px;
}

/* ===== WebView Safe Area ===== */
.safe-top{ height: env(safe-area-inset-top); }
.safe-bottom{ height: env(safe-area-inset-bottom); }

*{box-sizing:border-box}
body{
  margin:0;
  font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  background: var(--bg);
  color: var(--text);
  padding-bottom: calc(86px + env(safe-area-inset-bottom)); /* tombol aman */
}

/* ===== Header Splash-style ===== */
.top{
    position: relative;
    padding: calc(15px + env(safe-area-inset-top)) 18px 130px;
    color: #fff;
    overflow: hidden;
    border-bottom-left-radius: 28px;
    border-bottom-right-radius: 28px;
    box-shadow: var(--shadow-soft);
    background: linear-gradient(180deg, rgba(0, 0, 0, .22), rgba(0, 0, 0, .40)), url(../assets/img/dashboard-header.webp);
    background-size: cover;
    background-position: center;
}

/* kalau gambar belum ada, gradient fallback */
.top.noimg{
  background: linear-gradient(135deg, var(--red), var(--olive));
}

.top .row{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:14px;
}

.top .left h1{
  margin:0;
  font-size: 34px;
  letter-spacing:.3px;
  text-shadow: 0 6px 18px rgba(0,0,0,.35);
}
.top .left small{
  display:inline-block;
  margin-top:6px;
  font-size: 14px;
  opacity:.95;
  text-shadow: 0 6px 18px rgba(0,0,0,.35);
}

/* maskot di header */
.top .mascot{
  width: 190px;
  height: 230px;
  flex: 0 0 auto;
  background: url("../assets/img/mascot.webp"); /* ⬅️ maskot png */
  background-size: cover;
  background-repeat:no-repeat;
  background-position: center;
  /*filter: drop-shadow(0 14px 24px rgba(0,0,0,.35));*/
  position: absolute;
    right: 0;
}

/* ===== Saldo card modern ===== */
.saldo{
    margin: -60px 14px 14px;
    background: var(--card);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    padding: 18px 18px;
    display: flex;
    align-items: center;
    justify-content: space-around;
    gap: 14px;
    position: relative;
    text-align: center;
}

.saldo .info span{
  display:block;
  font-size: 15px;
  color: var(--muted);
  margin-bottom:6px;
}
.saldo .info strong{
  display:block;
  font-size: 34px;
  color: #16a34a;
  letter-spacing:.3px;
}

.saldo .mini-mascot{
  width:64px;height:64px;
  background: url("../assets/img/mini-mascot.png");
  background-size: contain;
  background-repeat:no-repeat;
  background-position:center;
  opacity:.95;
}

/* ===== Menu grid ===== */
.menu{
  display:grid;
  grid-template-columns: repeat(3, minmax(0,1fr));
  gap: 10px;
  padding: 8px 14px 6px;
}

.menu a{
  text-decoration:none;
  color: var(--text);
  background: var(--card);
  border-radius: var(--radius);
  box-shadow: var(--shadow-soft);
  padding: 16px 10px;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  min-height: 108px;
  border:1px solid rgba(17,24,39,.06);
  transition: transform .08s ease;
}
.menu a:active{ transform: scale(.98); }

.menu .ico{
  width:44px;height:44px;
  border-radius: 14px;
  display:grid;
  place-items:center;
  font-size: 20px;
  margin-bottom:10px;
}

.menu a:nth-child(1) .ico{ background: rgba(156,163,76,.18); color: #3f6212; }
.menu a:nth-child(2) .ico{ background: rgba(59,130,246,.16); color: #1d4ed8; }
.menu a:nth-child(3) .ico{ background: rgba(239,68,68,.14); color: #b91c1c; }
.menu a:nth-child(4) .ico{ background: rgba(234,179,8,.16); color: #a16207; }
.menu a:nth-child(5) .ico{ background: rgba(236,72,153,.14); color: #be185d; }
.menu a:nth-child(6) .ico{ background: rgba(17,24,39,.10); color: #111827; }

.menu span{
  font-size: 13px;
  overflow-wrap: break-word;
  text-align:center;
}

/* ===== Card Pengumuman ===== */
.card{
  margin: 12px 14px 18px;
  background: var(--card);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow);
  padding: 16px;
  border:1px solid rgba(17,24,39,.06);
}
.card h2{
  margin:0 0 12px;
  font-size: 22px;
}

.announce-item{
  background:#fff;
  border:1px solid #f3f4f6;
  border-radius: 16px;
  padding: 14px;
  margin: 12px 0;
  box-shadow: 0 6px 16px rgba(0,0,0,.04);
  position: relative;
}
.announce-item:before{
  content:"";
  position:absolute;
  left:0; top:12px; bottom:12px;
  width:4px;
  border-radius: 999px;
  background: linear-gradient(180deg, var(--red), rgba(225,29,72,.35));
}
.announce-item h3{
  margin:0 0 6px;
  font-size: 15px;
  padding-left: 10px;
}
.announce-item small{
  display:block;
  color: var(--muted);
  font-size: 12px;
  margin-bottom:10px;
  padding-left: 10px;
}
.announce-item .content{
  color:#111827;
  font-size: 13.5px;
  line-height: 1.65;
  white-space: pre-line;
  word-break: break-word;
  padding-left: 10px;
}

.badge-new{
  display:inline-block;
  margin-left:8px;
  font-size:11px;
  font-weight:900;
  padding:4px 10px;
  border-radius:999px;
  background:#dbeafe;
  color:#1d4ed8;
}

/* ===== Tombol keluar fixed aman gesture bar ===== */
.fixed{
  position: fixed;
  left: 0; right:0; bottom:0;
  /*padding: 12px 14px calc(16px + env(safe-area-inset-bottom));*/
  padding:12px;
  background: rgba(243,244,246,.86);
  backdrop-filter: blur(10px);
  border-top: 1px solid rgba(17,24,39,.08);
}

.btn.btn-logout{
  width:100%;
  border:none;
  border-radius: var(--radius);
  padding: 16px 14px;
  font-size: 16px;
  font-weight: 700;
  letter-spacing: .6px;
  color:#fff;
  background: linear-gradient(135deg, var(--red), #be123c);
  box-shadow: 0 14px 30px rgba(225,29,72,.28);
}
.btn.btn-logout:active{ transform: translateY(1px); }

@media (max-width:360px){
  .top .left h1{ font-size: 30px; }
  .saldo .info strong{ font-size: 30px; }
  /*.top .mascot{ width:100px; height:100px; }*/
}
</style>
</head>

<body>

<header class="top">
  <div class="row">
    <div class="left">
      <h1>Hallo,</h1>
      <small id="nama">Memuat...</small><br>
      <small>Salam Kenal, Namaku <b>PROBO</b></small>
    </div>
    <div class="mascot" aria-label="Mascot"></div>
  </div>
</header>

<section class="saldo">
  <div class="info">
    <span>Saldo e-Money</span>
    <strong id="saldo">Memuat...</strong>
  </div>
  <!--<div class="mini-mascot" aria-hidden="true"></div>-->
</section>

<section class="menu">
  <a href="emoney.php"><div class="ico"><i class="fa-solid fa-credit-card"></i></div><span>e-Money</span></a>
  <a href="perpustakaan.php"><div class="ico"><i class="fa-solid fa-book"></i></div><span>Perpustakaan</span></a>
  <a href="absensi.php"><div class="ico"><i class="fa-solid fa-clock"></i></div><span>Absensi</span></a>
  <a href="topup_game.php"><div class="ico"><i class="fa-solid fa-gamepad"></i></div><span>Top-up Game</span></a>
  <a href="sarpras.php"><div class="ico"><i class="fa-solid fa-school"></i></div><span>Sarpras</span></a>
  <a href="humas.php"><div class="ico"><i class="fa-solid fa-bullhorn"></i></div><span>Humas</span></a>
  <a href="profil.php"><div class="ico"><i class="fa-solid fa-user"></i></div><span>Profil</span></a>
</section>

<div class="card" id="pengumuman">
  <h2>Pengumuman</h2>
  <div id="listPengumuman">Memuat...</div>
</div>

<div class="fixed">
  <form method="POST" action="../logout.php">
    <button class="btn btn-logout">KELUAR</button>
  </form>
</div>

<script>
const API = <?=json_encode(rtrim(sds_base_url('emoney/api'), '/'), JSON_UNESCAPED_SLASHES)?>;

function rupiah(n){
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(n||0));
}

function formatTanggalWaktu(dateStr){
  if(!dateStr) return '-';
  const d = new Date(dateStr.replace(' ', 'T'));
  if (isNaN(d.getTime())) return dateStr;
  const tgl = d.toLocaleDateString('id-ID',{day:'numeric',month:'long',year:'numeric'});
  const jam = d.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit'});
  return `${tgl} • ${jam} WIB`;
}

function escapeHtml(str){
  return String(str ?? '')
    .replaceAll('&','&amp;')
    .replaceAll('<','&lt;')
    .replaceAll('>','&gt;')
    .replaceAll('"','&quot;')
    .replaceAll("'","&#039;");
}

function loadSaldo(){
  fetch(API + '/saldo.php',{credentials:'include'})
    .then(r=>r.json())
    .then(d=>{
      if(d.success){
        document.getElementById('saldo').innerText = rupiah(d.data.saldo);
        if(d.data.nama) document.getElementById('nama').innerText = d.data.nama;
      }
    })
    .catch(()=>{});
}

function loadPengumuman(){
  fetch(API + '/informasi.php', {credentials:'include'})
    .then(r=>r.json())
    .then(d=>{
      const box = document.getElementById('listPengumuman');
      if(!d.success || !d.data || d.data.length===0){
        box.innerHTML = '<small style="color:#6b7280;">Belum ada pengumuman.</small>';
        return;
      }

      let html = '';
      d.data.forEach(x=>{
        const judul = escapeHtml(x.judul || 'Pengumuman');
        const waktu = formatTanggalWaktu(x.tanggal);
        const baru  = (x.dibaca == 0) ? '<span class="badge-new">Baru</span>' : '';
        const isi = escapeHtml(x.isi || '-');

        html += `
          <div class="announce-item">
            <h3>${judul} ${baru}</h3>
            <small>${waktu}</small>
            <div class="content">${isi}</div>
          </div>
        `;
      });

      box.innerHTML = html;
    })
    .catch(()=>{
      document.getElementById('listPengumuman').innerHTML = '<small style="color:#6b7280;">Gagal memuat pengumuman.</small>';
    });
}

loadSaldo();
loadPengumuman();
setInterval(loadSaldo, 3000);
setInterval(loadPengumuman, 15000);
</script>
<script>
(function(){
  function hardRefreshIfNeeded(e){
    // Jika halaman datang dari BFCache / snapshot restore
    if (e && e.persisted) {
      location.reload();
      return;
    }
  }

  // BFCache: saat balik dari background / back-forward
  window.addEventListener('pageshow', hardRefreshIfNeeded);

  // Saat user balik lagi ke app
  document.addEventListener('visibilitychange', function(){
    if (!document.hidden) {
      // refresh data realtime tanpa reload penuh (lebih halus)
      if (typeof loadSaldo === 'function') loadSaldo();
      if (typeof loadRiwayat === 'function') loadRiwayat();
      if (typeof loadPengumuman === 'function') loadPengumuman();
      if (typeof loadPerpus === 'function') loadPerpus();
      if (typeof loadAbsensi === 'function') loadAbsensi(
        (document.getElementById('tglAwal')?.value || ''),
        (document.getElementById('tglAkhir')?.value || '')
      );
    }
  });

  // fallback: ketika app kembali fokus
  window.addEventListener('focus', function(){
    if (typeof loadSaldo === 'function') loadSaldo();
  });
})();
</script>

</body>
</html>
