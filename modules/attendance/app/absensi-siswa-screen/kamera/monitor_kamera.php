<?php
include_once '../../sw-library/sw-config.php';
include_once '../../sw-library/sw-function.php';
ob_start("minify_html");

if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:../../login'); // sesuaikan jika login Anda beda
  exit;
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>

<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Monitor Kamera • <?= h($site_name ?? 'Absensi') ?></title>
<meta name="robots" content="noindex,nofollow">
<style>
  :root{
    --bg:#0b1220; --card:rgba(255,255,255,.06); --line:rgba(255,255,255,.10);
    --text:#e5eef8; --muted:rgba(229,238,248,.70);
    --g:#22c55e; --y:#f59e0b; --r:#ef4444;
  }
  body{ margin:0; background:var(--bg); color:var(--text); font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial; overflow:hidden;}
  .wrap{ height:100vh; display:flex; flex-direction:column; gap:12px; padding:16px; box-sizing:border-box;}
  .top{
    background:var(--card); border:1px solid var(--line); border-radius:14px;
    padding:12px 14px; display:flex; align-items:center; justify-content:space-between; gap:12px;
  }
  .ttl{ font-weight:1000; letter-spacing:.3px; }
  .sub{ font-size:12px; color:var(--muted); margin-top:4px; }
  .pill{ display:inline-flex; align-items:center; gap:8px; padding:6px 10px; border-radius:999px;
    background:rgba(255,255,255,.06); border:1px solid var(--line); font-size:12px; font-weight:900; }
  .dot{ width:8px; height:8px; border-radius:999px; display:inline-block; }
  .dot.g{background:var(--g)} .dot.y{background:var(--y)} .dot.r{background:var(--r)}
  .btn{ border:1px solid rgba(255,255,255,.16); background:rgba(255,255,255,.08);
    color:var(--text); border-radius:10px; padding:8px 10px; font-weight:1000; cursor:pointer;}

  .grid{
    flex:1; overflow:hidden;
    display:grid; gap:12px;
    grid-template-columns: repeat(3, 1fr);
  }
  @media(max-width:1200px){ .grid{ grid-template-columns: repeat(2,1fr);} body{ overflow:auto;} }
  @media(max-width:760px){ .grid{ grid-template-columns: 1fr;} body{ overflow:auto;} }

  .cam{ background:var(--card); border:1px solid var(--line); border-radius:14px; overflow:hidden; display:flex; flex-direction:column;}
  .head{ padding:10px 12px; display:flex; align-items:flex-start; justify-content:space-between; gap:10px; border-bottom:1px solid rgba(255,255,255,.08);}
  .name{ font-weight:1000; }
  .loc{ font-size:12px; color:var(--muted); margin-top:4px; }
  .body{ position:relative; background:#050813; flex:1; min-height:240px;}
  .body img{ position:absolute; inset:0; width:100%; height:100%; object-fit:cover; display:block; }
  .empty{ position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-weight:1000; color:rgba(229,238,248,.55); }
  .foot{ padding:10px 12px; display:flex; justify-content:space-between; gap:10px; font-size:12px; color:var(--muted); border-top:1px solid rgba(255,255,255,.08);}

  .badge{
    min-width:92px; text-align:center;
    padding:6px 10px; border-radius:999px;
    font-size:12px; font-weight:1000; border:1px solid rgba(255,255,255,.14);
    background:rgba(255,255,255,.06);
  }
  .badge.g{ color:#bff7d7; border-color: rgba(34,197,94,.35); background: rgba(34,197,94,.12); }
  .badge.y{ color:#ffe5b2; border-color: rgba(245,158,11,.35); background: rgba(245,158,11,.12); }
  .badge.r{ color:#ffc1c1; border-color: rgba(239,68,68,.35); background: rgba(239,68,68,.12); }
</style>
</head>
<body>
<div class="wrap">
  <div class="top">
    <div>
      <div class="ttl">Monitor Kamera</div>
      <div class="sub">Snapshot realtime dari device (auto refresh).</div>
    </div>
    <div style="display:flex; gap:8px; flex-wrap:wrap; justify-content:flex-end; align-items:center;">
      <span class="pill"><span class="dot g"></span>Online &lt; 15s</span>
      <span class="pill"><span class="dot y"></span>Delay 15–60s</span>
      <span class="pill"><span class="dot r"></span>Offline &gt; 60s</span>
      <button class="btn" onclick="load()">Refresh</button>
    </div>
  </div>

  <div class="grid" id="grid"></div>
</div>

<script>
  const API = 'camera_list_api.php';
  const FAST = 15, SLOW = 60;

  function esc(s){ return String(s||'').replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'","&#039;"); }

  function statusFromSec(sec){
    if(sec <= FAST) return {c:'g', txt:'ONLINE'};
    if(sec <= SLOW) return {c:'y', txt:'DELAY'};
    return {c:'r', txt:'OFFLINE'};
  }

  function card(c, sec){
    const st = statusFromSec(sec);
    const img = c.last_image ? (c.last_image + '?t=' + Date.now()) : '';
    const seen = c.last_seen ? `Last: ${esc(c.last_seen)} (${sec}s)` : 'Belum ada data';

    return `
      <div class="cam">
        <div class="head">
          <div>
            <div class="name">${esc(c.cam_name || c.device_id)}</div>
            <div class="loc">${esc(c.lokasi || c.device_id)}</div>
          </div>
          <div class="badge ${st.c}">${st.txt}</div>
        </div>
        <div class="body">
          ${img ? `<img src="${img}" alt="">` : `<div class="empty">NO SNAPSHOT</div>`}
        </div>
        <div class="foot">
          <div>${seen}</div>
          <div>${esc(c.device_id)}</div>
        </div>
      </div>
    `;
  }

  async function load(){
    const grid = document.getElementById('grid');
    try{
      const res = await fetch(API, {cache:'no-store'});
      const js = await res.json();
      if(!js.ok) throw new Error(js.message || 'API error');

      const serverTs = Number(js.server_ts || Math.floor(Date.now()/1000));
      grid.innerHTML = (js.data || []).map(c=>{
        const lastTs = Number(c.last_seen_ts || 0);
        const sec = lastTs ? Math.max(0, serverTs - lastTs) : 999999;
        return card(c, sec);
      }).join('') || `<div style="opacity:.75">Belum ada kamera.</div>`;
    }catch(e){
      grid.innerHTML = `<div style="opacity:.75">Gagal load: ${esc(e.message||'')}</div>`;
    }
  }

  load();
  setInterval(load, 2000);
</script>
</body>
</html>
