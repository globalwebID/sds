<?php
require_once dirname(__DIR__, 2) . '/config/runtime.php';
require_once __DIR__.'/_central_control.php';
sds_session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['login'])) {
  header('Location: ../login.php');
  exit;
}
if (empty($_SESSION['emoney_csrf'])) $_SESSION['emoney_csrf'] = bin2hex(random_bytes(32));
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Top-up Game</title>

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
:root{
  --blue:#60a5fa;
  --blue-strong:#2563eb;
  --red:#f43f5e;
  --pink:#ec4899;
  --violet:#8b5cf6;
  --cyan:#22d3ee;
  --emerald:#10b981;
  --yellow:#f59e0b;
  --bg:#060b16;
  --bg-2:#0a1020;
  --bg-3:#0d1427;
  --card:#0f172a;
  --card-2:#111827;
  --line:#213049;
  --line-soft:#182437;
  --muted:#94a3b8;
  --text:#eef2ff;
  --text-soft:#cbd5e1;
  --success:#22c55e;
  --warning:#f59e0b;
  --danger:#ef4444;
  --shadow:0 18px 40px rgba(0,0,0,.42);
  --shadow-soft:0 12px 28px rgba(0,0,0,.28);
  --radius:22px;
  --radius-sm:16px;
  --bottom-nav-h:84px;
}

*{box-sizing:border-box}
html,body{margin:0;padding:0}
html{scroll-behavior:smooth}
body{
  font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif;
  color:var(--text);
  background:
    radial-gradient(circle at top left, rgba(96,165,250,.18), transparent 24%),
    radial-gradient(circle at top right, rgba(244,63,94,.15), transparent 20%),
    radial-gradient(circle at 50% 20%, rgba(34,211,238,.10), transparent 26%),
    linear-gradient(180deg,#040915 0%, #07101d 22%, #0a1222 100%);
  min-height:100vh;
}
button,input,select{font-family:inherit}
img{max-width:100%}
.hidden{display:none !important}

.page{
  width:100%;
  max-width:520px;
  margin:0 auto;
  padding:14px 14px calc(var(--bottom-nav-h) + 22px + env(safe-area-inset-bottom));
  min-height:100vh;
}

/* generic card */
.card{
  margin:0;
  position:relative;
  background:
    linear-gradient(180deg, rgba(17,24,39,.96), rgba(9,16,31,.96));
  border:1px solid rgba(255,255,255,.06);
  border-radius:var(--radius);
  box-shadow:var(--shadow);
  padding:14px;
  margin-bottom:14px;
  overflow:hidden;
}
.card::before{
  content:"";
  position:absolute;
  inset:0;
  pointer-events:none;
  background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,0));
  opacity:.75;
}

/* hero */
.hero-card{
  padding:16px;
  overflow:hidden;
  background:
    radial-gradient(circle at top right, rgba(255,255,255,.16), transparent 30%),
    radial-gradient(circle at bottom left, rgba(34,211,238,.14), transparent 26%),
    linear-gradient(135deg,#111c3b 0%, #12224a 36%, #24153d 68%, #3b1025 100%);
  border:1px solid rgba(255,255,255,.08);
}
.hero-card::after{
  content:"";
  position:absolute;
  right:-18px;
  top:-18px;
  width:120px;
  height:120px;
  border-radius:50%;
  background:rgba(255,255,255,.06);
}
.hero-top{
  position:relative;
  z-index:1;
  display:flex;
  gap:12px;
  align-items:flex-start;
  justify-content:space-between;
}
.hero-copy{
  min-width:0;
  flex:1;
}
.hero-badge{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding:8px 12px;
  border-radius:999px;
  background:rgba(255,255,255,.10);
  border:1px solid rgba(255,255,255,.10);
  color:#fff;
  font-size:11px;
  font-weight:800;
  margin-bottom:12px;
  backdrop-filter:blur(4px);
}
.hero-copy h2{
  margin:0;
  font-size:24px;
  line-height:1.14;
  color:#fff;
}
.hero-copy p{
  margin:8px 0 0;
  color:rgba(255,255,255,.86);
  font-size:13px;
  line-height:1.55;
  max-width:290px;
}
.hero-icons{
  width:72px;
  height:72px;
  border-radius:22px;
  display:grid;
  place-items:center;
  flex-shrink:0;
  /*background:rgba(255,255,255,.10);*/
  /*border:1px solid rgba(255,255,255,.12);*/
  /*box-shadow:inset 0 1px 0 rgba(255,255,255,.08);*/
  font-size:50px;
  color:#fff;
}

.balance-card{
  position:relative;
  z-index:1;
  margin-top:14px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:14px;
  padding:14px;
  border-radius:20px;
  background:rgba(255,255,255,.08);
  border:1px solid rgba(255,255,255,.10);
  backdrop-filter:blur(8px);
}
.balance-card .left small{
  display:block;
  color:rgba(255,255,255,.80);
  margin-bottom:6px;
  font-size:12px;
}
.balance-card .left strong{
  display:block;
  font-size:30px;
  color:#00ff00;
  line-height:1.05;
  letter-spacing:.2px;
}
.balance-meta{
  margin-top:7px;
  font-size:12px;
  color:rgba(255,255,255,.78);
}
.balance-card .icon{
  width:58px;height:58px;
  border-radius:18px;
  display:grid;
  place-items:center;
  background:rgba(255,255,255,.10);
  border:1px solid rgba(255,255,255,.10);
  color:#fff;
  font-size:24px;
  flex-shrink:0;
}

/* tabs */
.tabs{
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:10px;
  margin-bottom:14px;
}
.tab-btn{
  position:relative;
  border:1px solid rgba(255,255,255,.08);
  border-radius:16px;
  background:linear-gradient(180deg,#111827,#0b1324);
  color:var(--text-soft);
  padding:13px 14px;
  font-size:13px;
  font-weight:900;
  cursor:pointer;
  box-shadow:var(--shadow-soft);
  transition:.18s ease;
}
.tab-btn:hover{border-color:#34465f}
.tab-btn.active{
  color:#fff;
  border-color:transparent;
  background:linear-gradient(135deg,#f43f5e,#be123c 55%, #7f1d1d 100%);
}
.tab-panel{display:none}
.tab-panel.active{display:block}

/* section */
.section-title{
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:10px;
  margin-bottom:12px;
}
.section-title h2{
  margin:0;
  font-size:16px;
  color:#fff;
  letter-spacing:.1px;
}
.section-title small{
  color:var(--muted);
  font-size:12px;
}

/* search */
.search-wrap{margin-bottom:12px}
.search-box{
  position:relative;
}
.search-box i{
  position:absolute;
  left:14px;
  top:50%;
  transform:translateY(-50%);
  color:#718096;
  font-size:13px;
  pointer-events:none;
}
.search-input{
  width:100%;
  padding:14px 14px 14px 42px;
  border:1px solid rgba(255,255,255,.07);
  border-radius:16px;
  background:linear-gradient(180deg,#0c162b,#0b1322);
  color:var(--text);
  font-size:14px;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.03);
}
.search-input::placeholder{color:#64748b}
.search-input:focus{
  outline:none;
  border-color:rgba(96,165,250,.55);
  box-shadow:0 0 0 3px rgba(59,130,246,.14);
}

/* popular slider */
.popular-wrap{
  position:relative;
  margin-bottom:14px;
}
.popular-slider{
  display:flex;
  gap:12px;
  overflow-x:auto;
  overflow-y:hidden;
  scroll-snap-type:x mandatory;
  -webkit-overflow-scrolling:touch;
  /*padding-bottom:10px;*/
  border-radius: 20px;
}
.popular-slider::-webkit-scrollbar{
  display:none;
}
.popular-item{
  position:relative;
  min-width:180px;
  width:180px;
  flex:0 0 180px;
  border:1px solid rgba(255,255,255,.07);
  border-radius:18px;
  padding:12px;
  text-align:center;
  background:
    linear-gradient(180deg, rgba(17,24,39,.98), rgba(10,18,34,.98));
  color:var(--text);
  box-shadow:var(--shadow-soft);
  cursor:pointer;
  transition:.18s ease;
  overflow:hidden;
  scroll-snap-align:start;
}
.popular-item::before{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,0));
  pointer-events:none;
}
.popular-item:hover{
  transform:translateY(-2px);
  border-color:#375172;
}
.popular-item .badge-top{
  display:inline-flex;
  align-items:center;
  gap:6px;
  margin-bottom:10px;
  padding:6px 10px;
  border-radius:999px;
  background:rgba(244,63,94,.12);
  color:#fda4af;
  font-size:10px;
  font-weight:900;
  border:1px solid rgba(244,63,94,.15);
}
.popular-item .game-icon{
  width:100%;
  height:auto;
  border-radius:16px;
  display:grid;
  place-items:center;
  font-size:22px;
  margin-bottom:10px;
  color:#fff;
  overflow:hidden;
  background:#0f172a;
}
.popular-item .game-icon img,
.brand-card .brand-icon img,
.modal-selected-game .icon img{
  width:100%;
  height:auto;
  object-fit:cover;
  display:block;
}
.popular-item strong{
  display:block;
  font-size:13px;
  line-height:1.35;
  color:#fff;
  position:relative;
  z-index:1;
}
.popular-item small{
  display:block;
  color:var(--muted);
  margin-top:5px;
  font-size:11px;
  position:relative;
  z-index:1;
}
.popular-hint{
  margin-bottom:8px;
  font-size:11px;
  color:var(--muted);
  text-align:center;
}

/* brand grid */
.brand-grid{
  display:flex;
  flex-direction:column;
  gap:10px;
  /*max-height:calc(100vh - 430px);*/
  /*overflow-y:auto;*/
  /*padding-right:2px;*/
}
.brand-grid::-webkit-scrollbar,
.history-list::-webkit-scrollbar,
.product-grid::-webkit-scrollbar{width:6px}
.brand-grid::-webkit-scrollbar-thumb,
.history-list::-webkit-scrollbar-thumb,
.product-grid::-webkit-scrollbar-thumb{
  background:#243247;
  border-radius:999px;
}
.brand-card{
  position:relative;
  border:1px solid rgba(255,255,255,.06);
  border-radius:20px;
  background:
    linear-gradient(180deg,#111827,#0b1324);
  color:var(--text);
  box-shadow:var(--shadow-soft);
  padding:12px;
  cursor:pointer;
  transition:.18s ease;
  text-align:left;
  overflow:hidden;
}
.brand-card::before{
  content:"";
  position:absolute;
  inset:0;
  background:linear-gradient(180deg, rgba(255,255,255,.04), rgba(255,255,255,0));
  pointer-events:none;
}
.brand-card:hover{
  transform:translateY(-1px);
  border-color:#375172;
}
.brand-card.active{
  border-color:rgba(96,165,250,.55);
  background:
    radial-gradient(circle at top right, rgba(59,130,246,.14), transparent 30%),
    linear-gradient(180deg, rgba(15,26,48,.98), rgba(8,16,31,.98));
  box-shadow:0 0 0 1px rgba(96,165,250,.12), var(--shadow);
}
.brand-top{
  display:flex;
  align-items:center;
  gap:12px;
}
.brand-card .brand-icon{
  width:58px;
  height:58px;
  border-radius:16px;
  display:grid;
  place-items:center;
  color:#fff;
  font-size:21px;
  flex-shrink:0;
  overflow:hidden;
  background:#0f172a;
  border:1px solid rgba(255,255,255,.06);
}
.brand-body{min-width:0;flex:1}
.brand-row{
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:8px;
}
.brand-name{
  font-weight:900;
  font-size:14px;
  line-height:1.35;
  color:#fff;
}
.brand-meta{
  color:var(--muted);
  margin-top:5px;
  font-size:12px;
}
.brand-chip{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:6px 9px;
  border-radius:999px;
  background:rgba(96,165,250,.10);
  color:#93c5fd;
  border:1px solid rgba(96,165,250,.12);
  font-size:10px;
  font-weight:900;
  white-space:nowrap;
}

/* history */
.history-list{
  display:flex;
  flex-direction:column;
  gap:10px;
  overflow-y:auto;
  max-height:calc(100vh - 320px);
}
.history-item{
  border:1px solid rgba(255,255,255,.06);
  border-radius:18px;
  background:linear-gradient(180deg,#111827,#0b1324);
  padding:14px;
  box-shadow:var(--shadow-soft);
}
.history-item.new{
  border:2px solid rgba(34,197,94,.65);
  animation:flash 1s ease;
}
@keyframes flash{
  0%{background:#173221}
  100%{background:#0f172a}
}
.history-head{
  display:flex;
  justify-content:space-between;
  gap:12px;
  align-items:flex-start;
}
.history-name{
  font-weight:900;
  font-size:14px;
  line-height:1.35;
  color:#fff;
}
.history-meta{
  font-size:12px;
  color:var(--muted);
  margin-top:5px;
  line-height:1.55;
}
.history-price{
  font-weight:900;
  color:#4ade80;
  text-align:right;
  font-size:14px;
}
.badge{
  display:inline-block;
  padding:5px 10px;
  border-radius:999px;
  font-size:11px;
  font-weight:900;
  white-space:nowrap;
}
.badge-success{background:rgba(34,197,94,.16);color:#86efac}
.badge-failed{background:rgba(239,68,68,.16);color:#fca5a5}
.badge-processing{background:rgba(245,158,11,.16);color:#fcd34d}
.badge-created{background:rgba(59,130,246,.16);color:#93c5fd}
.badge-refunded{background:rgba(148,163,184,.16);color:#cbd5e1}

.loading{color:var(--muted);font-size:14px}
.empty{
  text-align:center;
  color:var(--muted);
  padding:24px 12px;
  border:1px dashed rgba(255,255,255,.08);
  border-radius:18px;
  background:#0b1322;
}
.auto-refresh{
  font-size:12px;
  color:var(--muted);
  display:flex;
  align-items:center;
  gap:8px;
}
.auto-refresh .dot{
  width:8px;height:8px;border-radius:50%;
  background:#22c55e;
  display:inline-block;
  box-shadow:0 0 10px rgba(34,197,94,.55);
}

/* toast */
.toast{
  position:fixed;
  left:50%;
  bottom:calc(var(--bottom-nav-h) + 16px + env(safe-area-inset-bottom));
  transform:translateX(-50%);
  background:#020617;
  color:#fff;
  border:1px solid #1e293b;
  border-radius:14px;
  padding:12px 16px;
  font-size:13px;
  box-shadow:0 12px 30px rgba(0,0,0,.4);
  z-index:9999;
  opacity:0;
  pointer-events:none;
  transition:.2s ease;
  max-width:calc(100vw - 28px);
  text-align:center;
}
.toast.show{opacity:1}

/* modal */
.modal{
  position:fixed;
  inset:0;
  z-index:9998;
  display:none;
}
.modal.show{display:block}
.modal-backdrop{
  position:absolute;
  inset:0;
  background:rgba(2,6,23,.76);
  backdrop-filter:blur(6px);
  opacity:0;
  transition:opacity .22s ease;
}
.modal.show .modal-backdrop{opacity:1}
.modal-dialog{
  position:absolute;
  left:0;right:0;bottom:0;
  width:100%;
  max-width:520px;
  margin:0 auto;
  background:linear-gradient(180deg,#0f172a,#09101f);
  border:1px solid rgba(255,255,255,.08);
  border-bottom:none;
  border-radius:24px 24px 0 0;
  box-shadow:0 28px 60px rgba(0,0,0,.45);
  overflow:hidden;
  transform:translateY(24px);
  opacity:0;
  transition:all .22s ease;
}
.modal.show .modal-dialog{
  transform:translateY(0);
  opacity:1;
}
.modal-dialog::before{
  content:"";
  position:absolute;
  top:10px;left:50%;
  transform:translateX(-50%);
  width:44px;height:4px;
  border-radius:999px;
  background:rgba(255,255,255,.14);
}
.modal-header{
  padding:24px 16px 14px;
  border-bottom:1px solid rgba(255,255,255,.06);
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap:10px;
}
.modal-title h3{
  margin:0;
  font-size:18px;
  color:#fff;
}
.modal-title small{
  display:block;
  color:var(--muted);
  margin-top:5px;
  line-height:1.5;
  font-size:12px;
}
.modal-close{
  width:40px;height:40px;
  border:none;
  border-radius:14px;
  background:#0b1324;
  color:var(--text-soft);
  cursor:pointer;
  flex-shrink:0;
  border:1px solid rgba(255,255,255,.06);
}
.modal-close:hover{background:#182233}
.modal-body{
  padding:16px;
  max-height:68vh;
  overflow-y:auto;
}
.modal-footer{
  padding:14px 16px calc(18px + env(safe-area-inset-bottom));
  border-top:1px solid rgba(255,255,255,.06);
  display:grid;
  grid-template-columns:1fr 1fr;
  gap:10px;
  background:rgba(6,11,22,.92);
}
.btn-primary{
  border:none;
  border-radius:16px;
  padding:14px 16px;
  background:linear-gradient(135deg,var(--red),#be123c 62%, #881337 100%);
  color:#fff;
  font-size:14px;
  font-weight:900;
  cursor:pointer;
  box-shadow:0 10px 24px rgba(190,24,93,.26);
}
.btn-primary:disabled{
  opacity:.55;
  cursor:not-allowed;
  box-shadow:none;
}
.btn-secondary{
  border:1px solid rgba(255,255,255,.08);
  border-radius:16px;
  padding:14px 16px;
  background:#0b1324;
  color:#cbd5e1;
  font-size:14px;
  font-weight:900;
  cursor:pointer;
}
.btn-secondary:hover{background:#182233}

.input-group{margin-bottom:14px}
.input-group label{
  display:block;
  font-size:13px;
  font-weight:800;
  margin-bottom:7px;
  color:var(--text-soft);
}
.input-group input{
  width:100%;
  padding:13px 14px;
  border:1px solid rgba(255,255,255,.08);
  border-radius:16px;
  font-size:14px;
  background:#081120;
  color:var(--text);
}
.input-group input::placeholder{color:#64748b}
.input-group input:focus{
  outline:none;
  border-color:rgba(96,165,250,.55);
  box-shadow:0 0 0 3px rgba(59,130,246,.14);
}
.form-grid{
  display:grid;
  grid-template-columns:1fr;
  gap:12px;
}
.modal-selected-game{
  display:flex;
  align-items:center;
  gap:12px;
  padding:14px;
  background:
    radial-gradient(circle at top right, rgba(96,165,250,.10), transparent 30%),
    linear-gradient(180deg,#0b1324,#091120);
  border:1px solid rgba(255,255,255,.07);
  border-radius:18px;
  margin-bottom:14px;
}
.modal-selected-game .icon{
  width:54px;height:54px;
  border-radius:16px;
  display:grid;
  place-items:center;
  color:#fff;
  font-size:20px;
  overflow:hidden;
  background:#0f172a;
  border:1px solid rgba(255,255,255,.06);
  flex-shrink:0;
}
.modal-selected-game .name{
  font-weight:900;
  font-size:15px;
  color:#fff;
}
.modal-selected-game .meta{
  color:var(--muted);
  font-size:12px;
  margin-top:4px;
}

/* product grid */
.product-grid{
  display:grid;
  grid-template-columns:1fr;
  gap:10px;
}
.product-item{
  border:1px solid rgba(255,255,255,.07);
  border-radius:18px;
  padding:14px;
  background:
    linear-gradient(180deg,#111827,#0b1324);
  color:var(--text);
  cursor:pointer;
  transition:.16s ease;
  display:flex;
  align-items:center;
  justify-content:space-between;
  gap:12px;
  text-align:left;
  box-shadow:var(--shadow-soft);
}
.product-item:hover{
  transform:translateY(-1px);
  border-color:#334155;
}
.product-item.active{
  border-color:rgba(244,63,94,.38);
  background:
    radial-gradient(circle at top right, rgba(244,63,94,.12), transparent 26%),
    linear-gradient(180deg,rgba(56,17,29,.96),rgba(24,9,16,.98));
  box-shadow:0 0 0 1px rgba(244,63,94,.10), 0 16px 30px rgba(0,0,0,.30);
}
.product-left{min-width:0}
.product-item .name{
  font-weight:800;
  font-size:13px;
  line-height:1.4;
  color:#fff;
}
.product-item .sku{
  color:var(--muted);
  font-size:11px;
  margin-top:4px;
}
.product-item .price{
  font-size:15px;
  font-weight:900;
  color:#4ade80;
  white-space:nowrap;
}
.product-item .pick{
  display:inline-flex;
  align-items:center;
  gap:6px;
  font-size:11px;
  font-weight:900;
  color:#fda4af;
  margin-top:7px;
}

.summary{
  border:1px dashed rgba(255,255,255,.10);
  border-radius:18px;
  padding:14px;
  background:#081120;
}
.summary-row{
  display:flex;
  justify-content:space-between;
  gap:10px;
  padding:9px 0;
  font-size:14px;
  border-bottom:1px dashed rgba(255,255,255,.06);
}
.summary-row:last-child{border-bottom:none}
.summary-row .k{color:var(--muted)}
.summary-row .v{
  font-weight:800;
  text-align:right;
  max-width:62%;
  word-break:break-word;
  color:#fff;
}
.note{
  font-size:12px;
  color:var(--muted);
  line-height:1.6;
  margin-top:10px;
}

/* bottom fixed home */
.bottom-home{
  position:fixed;
  left:50%;
  bottom:12px;
  transform:translateX(-50%);
  width:calc(100% - 24px);
  max-width:492px;
  z-index:9990;
}
.bottom-home-inner{
  display:flex;
  justify-content:center;
  align-items:center;
  padding:10px;
  border-radius:24px;
  background:rgba(10,16,32,.88);
  backdrop-filter:blur(12px);
  -webkit-backdrop-filter:blur(12px);
  border:1px solid rgba(255,255,255,.08);
  box-shadow:0 18px 40px rgba(0,0,0,.38);
}
.home-fixed-btn{
  width:100%;
  border:none;
  border-radius:18px;
  background:linear-gradient(135deg,#1d4ed8,#1e40af 55%, #0f172a 100%);
  color:#fff;
  font-weight:900;
  font-size:14px;
  padding:15px 18px;
  display:flex;
  align-items:center;
  justify-content:center;
  gap:10px;
  box-shadow:0 12px 24px rgba(29,78,216,.28);
  cursor:pointer;
}
.home-fixed-btn i{
  font-size:15px;
}

@media(min-width:521px){
  .page{padding-top:18px}
  .modal-dialog{
    left:50%;
    right:auto;
    bottom:18px;
    transform:translate(-50%, 24px);
    border-radius:24px;
    border-bottom:1px solid rgba(255,255,255,.08);
  }
  .modal.show .modal-dialog{
    transform:translate(-50%, 0);
  }
}
</style>

</head>
<body>

<div class="page">
  <div class="card hero-card">
    <div class="hero-top">
      <div class="hero-copy">
        <div class="hero-badge">
          <i class="fa-solid fa-bolt"></i>
          <span>Cepat • Aman • Praktis</span>
        </div>
        <h2>Top-up game Favoritmu lebih cepat.</h2>
        <p>Mulai dari nominal kecil, cocok untuk siswa, langsung dipotong dari saldo e-Money.</p>
      </div>
      <div class="hero-icons">
        <i class="fa-solid fa-gamepad"></i>
      </div>
    </div>

    <div class="balance-card">
      <div class="left">
        <small>Saldo e-Money</small>
        <strong id="saldoText">Rp0</strong>
        <div class="balance-meta" id="saldoMeta">Memuat saldo...</div>
      </div>
      <div class="icon"><i class="fa-solid fa-wallet"></i></div>
    </div>
  </div>

  <div class="tabs">
    <button type="button" class="tab-btn active" data-tab="games">Daftar Game</button>
    <button type="button" class="tab-btn" data-tab="history">Riwayat Top-up</button>
  </div>

  <div class="tab-panel active" id="tab-games">
    <div class="card">
      <div class="search-wrap">
        <div class="search-box">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" class="search-input" id="searchGame" placeholder="Cari game favorit...">
        </div>
      </div>

      <div class="section-title">
        <h2>Game Populer</h2>
        <small id="popularInfo">Memuat game...</small>
      </div>

      <div class="popular-wrap">
          <div class="popular-hint">Geser ke samping untuk melihat game populer lainnya</div>
        <div id="popularGrid" class="popular-slider"></div>
        
      </div>

      <div class="section-title" style="margin-top:2px">
        <h2>Semua Game</h2>
        <small id="brandInfo">Memuat brand...</small>
      </div>
      <div id="brandGrid" class="brand-grid"></div>
    </div>
  </div>

  <div class="tab-panel" id="tab-history">
    <div class="card">
      <div class="section-title">
        <h2>Riwayat Top-up Game</h2>
        <div class="auto-refresh">
          <span class="dot"></span>
          <span id="refreshInfo">Auto refresh aktif</span>
        </div>
      </div>

      <div id="historyLoading" class="loading">Memuat riwayat...</div>
      <div id="historyList" class="history-list"></div>
      <div id="historyEmpty" class="empty hidden">Belum ada transaksi top-up game.</div>
    </div>
  </div>
</div>

<div class="bottom-home">
  <div class="bottom-home-inner">
    <button type="button" class="home-fixed-btn" onclick="window.location.href=<?=htmlspecialchars(json_encode(sds_base_url('emoney/dashboard/'), JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8')?>">
      <i class="fa-solid fa-bar"></i>
      <span>KEMBALI KE MENU</span>
    </button>
  </div>
</div>

<div class="modal" id="modalUser">
  <div class="modal-backdrop" onclick="closeAllModals()"></div>
  <div class="modal-dialog">
    <div class="modal-header">
      <div class="modal-title">
        <h3>Data Top-up</h3>
        <small>Masukkan data akun game yang akan di-top-up.</small>
      </div>
      <button type="button" class="modal-close" onclick="closeAllModals()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-selected-game">
        <div class="icon" id="userModalGameIcon"><i class="fa-solid fa-gamepad"></i></div>
        <div>
          <div class="name" id="userModalGameName">-</div>
          <div class="meta" id="userModalGameMeta">Pilih game untuk memulai top-up</div>
        </div>
      </div>

      <div class="form-grid">
        <div class="input-group">
          <label for="userIdGame">User ID Game</label>
          <input type="text" id="userIdGame" placeholder="Masukkan User ID">
        </div>

        <div class="input-group" id="zoneWrap">
          <label for="zoneIdGame">Zone ID</label>
          <input type="text" id="zoneIdGame" placeholder="Masukkan Zone ID">
        </div>
      </div>

      <div class="note" id="zoneNote">
        Beberapa game seperti Mobile Legends membutuhkan User ID dan Zone ID.
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="closeAllModals()">Batal</button>
      <button type="button" class="btn-primary" id="btnToNominal">Lanjutkan</button>
    </div>
  </div>
</div>

<div class="modal" id="modalNominal">
  <div class="modal-backdrop" onclick="closeAllModals()"></div>
  <div class="modal-dialog">
    <div class="modal-header">
      <div class="modal-title">
        <h3>Pilih Nominal</h3>
        <small>Pilih nominal yang ingin dibeli untuk game ini.</small>
      </div>
      <button type="button" class="modal-close" onclick="closeAllModals()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="modal-selected-game">
        <div class="icon" id="nominalModalGameIcon"><i class="fa-solid fa-gamepad"></i></div>
        <div>
          <div class="name" id="nominalModalGameName">-</div>
          <div class="meta" id="nominalModalGameMeta">Pilih nominal top-up</div>
        </div>
      </div>

      <div id="productLoading" class="loading">Memuat produk...</div>
      <div id="productGrid" class="product-grid"></div>
      <div id="productEmpty" class="empty hidden">Belum ada produk untuk game ini.</div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="backToUserModal()">Kembali</button>
      <button type="button" class="btn-primary" id="btnToSummary" disabled>Lanjut Bayar</button>
    </div>
  </div>
</div>

<div class="modal" id="modalSummary">
  <div class="modal-backdrop" onclick="closeAllModals()"></div>
  <div class="modal-dialog">
    <div class="modal-header">
      <div class="modal-title">
        <h3>Ringkasan Pembayaran</h3>
        <small>Periksa kembali data top-up sebelum dibayar.</small>
      </div>
      <button type="button" class="modal-close" onclick="closeAllModals()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <div class="summary">
        <div class="summary-row">
          <div class="k">Game</div>
          <div class="v" id="summaryBrand">-</div>
        </div>
        <div class="summary-row">
          <div class="k">Produk</div>
          <div class="v" id="summaryProduct">-</div>
        </div>
        <div class="summary-row">
          <div class="k">User ID</div>
          <div class="v" id="summaryTarget">-</div>
        </div>
        <div class="summary-row">
          <div class="k">Harga</div>
          <div class="v" id="summaryPrice">Rp0</div>
        </div>
        <div class="summary-row">
          <div class="k">Saldo Saat Ini</div>
          <div class="v" id="summarySaldo">Rp0</div>
        </div>
        <div class="summary-row" style="display:none">
          <div class="k">Sisa Saldo</div>
          <div class="v" id="summarySisaSaldo">Rp0</div>
        </div>
      </div>
      <div class="note">
        Setelah pembayaran dibuat, saldo dan riwayat transaksi akan diperbarui otomatis.
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn-secondary" onclick="backToNominalModal()">Kembali</button>
      <button type="button" class="btn-primary" id="btnBayar">Bayar Sekarang</button>
    </div>
  </div>
</div>

<div id="toast" class="toast"></div>

<script>
const state = {
  saldo: 0,
  brands: [],
  products: [],
  filteredProducts: [],
  brandLogoMap: {},
  selectedBrand: '',
  selectedBrandNeedZone: false,
  selectedProduct: null,
  checkoutBusy: false,
  lastHistoryJson: '',
  pollTimer: null
};

const popularPreferred = [
  'Mobile Legends',
  'Free Fire',
  'PUBG MOBILE',
  'Valorant',
  'Roblox',
  'Honor of Kings',
  'Steam Wallet'
];

const gameLogoMap = {
  'mobile legends': 'https://uniplay.id/cdn/dd3ccb84374a3f9225f0515c31ac6910-large.png',
  'free fire': 'https://uniplay.id/cdn/27f1a02395e94ada15b932beb3efbd62-large.jpeg',
  'pubg mobile': 'https://uniplay.id/cdn/d2a43deb7e94d645332bdf0415f5b36d-large.png',
  'valorant': 'https://images.seeklogo.com/logo-png/45/1/valorant-logo-png_seeklogo-456674.png',
  'roblox': 'https://uniplay.id/cdn/620ad99df2c704d765036ae40064ba77-large.jpeg',
  'steam wallet': 'https://images.seeklogo.com/logo-png/16/1/steam-logo-png_seeklogo-168108.png',
  'honor of kings': 'https://images.seeklogo.com/logo-png/61/2/honor-of-kings-logo-png_seeklogo-617362.png'
};

function rupiah(n){
  n = parseInt(n || 0, 10);
  return 'Rp ' + n.toLocaleString('id-ID');
}

function esc(s){
  return String(s ?? '').replace(/[&<>"']/g, m => ({
    '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'
  }[m]));
}

function getGameStyle(name){
  const key = String(name || '').toLowerCase();
  if (key.includes('mobile legends')) return { icon:'fa-solid fa-shield-halved', bg:'linear-gradient(135deg,#2563eb,#1d4ed8)' };
  if (key.includes('free fire')) return { icon:'fa-solid fa-fire-flame-curved', bg:'linear-gradient(135deg,#f97316,#ea580c)' };
  if (key.includes('pubg')) return { icon:'fa-solid fa-crosshairs', bg:'linear-gradient(135deg,#f59e0b,#d97706)' };
  if (key.includes('valorant')) return { icon:'fa-solid fa-bolt', bg:'linear-gradient(135deg,#ef4444,#e11d48)' };
  if (key.includes('roblox')) return { icon:'fa-solid fa-cube', bg:'linear-gradient(135deg,#111827,#374151)' };
  if (key.includes('steam')) return { icon:'fa-brands fa-steam', bg:'linear-gradient(135deg,#0f172a,#1e293b)' };
  if (key.includes('honor of kings')) return { icon:'fa-solid fa-crown', bg:'linear-gradient(135deg,#7c3aed,#5b21b6)' };
  return { icon:'fa-solid fa-gamepad', bg:'linear-gradient(135deg,#215dcc,#163a94)' };
}

function getGameLogo(name){
  const rawName = String(name || '').trim();
  const key = rawName.toLowerCase();

  if (state.brandLogoMap && state.brandLogoMap[rawName]) {
    return state.brandLogoMap[rawName];
  }

  for (const k in gameLogoMap) {
    if (key.includes(k)) return gameLogoMap[k];
  }
  return '';
}

function gameVisualHtml(name, cls, size = 52){
  const logo = getGameLogo(name);
  const style = getGameStyle(name);

  if (logo) {
    return `<div class="${cls}"><img src="${logo}" alt="${esc(name)}" loading="lazy" referrerpolicy="no-referrer"></div>`;
  }

  return `<div class="${cls}" style="background:${style.bg};width:${size}px;height:${size}px"><i class="${style.icon}"></i></div>`;
}

async function fetchJson(url){
  const res = await fetch(url, { credentials:'same-origin', cache:'no-store' });
  return await res.json();
}

function showToast(message, ms = 3000){
  const el = document.getElementById('toast');
  el.textContent = message || '';
  el.classList.add('show');
  clearTimeout(el._timer);
  el._timer = setTimeout(() => el.classList.remove('show'), ms);
}

function setRefreshInfo(text){
  const el = document.getElementById('refreshInfo');
  if (el) el.textContent = text;
}

function badgeStatus(status){
  status = String(status || '').toUpperCase();
  if(status === 'SUCCESS') return '<span class="badge badge-success">BERHASIL</span>';
  if(status === 'FAILED') return '<span class="badge badge-failed">GAGAL</span>';
  if(status === 'PROCESSING') return '<span class="badge badge-processing">DIPROSES</span>';
  if(status === 'REFUNDED') return '<span class="badge badge-refunded">REFUND</span>';
  return '<span class="badge badge-created">DIBUAT</span>';
}

function setActiveTab(tab){
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.tab === tab);
  });
  document.getElementById('tab-games').classList.toggle('active', tab === 'games');
  document.getElementById('tab-history').classList.toggle('active', tab === 'history');
}

function goToHistoryTab(){
  setActiveTab('history');
  setTimeout(() => {
    const el = document.getElementById('tab-history');
    if (el) el.scrollIntoView({behavior:'smooth', block:'start'});
  }, 180);
}

function closeAllModals(){
  document.querySelectorAll('.modal').forEach(m => m.classList.remove('show'));
}

function openModal(id){
  closeAllModals();
  const el = document.getElementById(id);
  if (!el) {
    showToast('Modal tidak ditemukan: ' + id);
    return;
  }
  el.classList.add('show');
}

function updateZoneVisibility(){
  const zoneWrap = document.getElementById('zoneWrap');
  const zoneNote = document.getElementById('zoneNote');

  if(state.selectedBrandNeedZone){
    zoneWrap.classList.remove('hidden');
    zoneNote.classList.remove('hidden');
  }else{
    zoneWrap.classList.add('hidden');
    zoneNote.classList.add('hidden');
    document.getElementById('zoneIdGame').value = '';
  }
}

function updateSelectedGameUI(){
  const brand = state.selectedBrand || '-';
  const totalProduk = state.filteredProducts.length || 0;

  document.getElementById('userModalGameIcon').outerHTML = gameVisualHtml(brand, 'icon', 54).replace('class="icon"', 'id="userModalGameIcon" class="icon"');
  document.getElementById('nominalModalGameIcon').outerHTML = gameVisualHtml(brand, 'icon', 54).replace('class="icon"', 'id="nominalModalGameIcon" class="icon"');

  document.getElementById('userModalGameName').textContent = brand;
  document.getElementById('userModalGameMeta').textContent = totalProduk + ' produk tersedia';
  document.getElementById('nominalModalGameName').textContent = brand;
  document.getElementById('nominalModalGameMeta').textContent = 'Pilih nominal top-up';
}

function updateSummary(){
  const userId = document.getElementById('userIdGame').value.trim();
  const zoneId = document.getElementById('zoneIdGame').value.trim();

  document.getElementById('summaryBrand').textContent = state.selectedBrand || '-';
  document.getElementById('summaryProduct').textContent = state.selectedProduct ? state.selectedProduct.product_name : '-';

  let target = '-';
  if(userId){
    target = userId;
    if(state.selectedBrandNeedZone && zoneId){
      target += ' (' + zoneId + ')';
    }
  }
  document.getElementById('summaryTarget').textContent = target;
  document.getElementById('summaryPrice').textContent = state.selectedProduct ? rupiah(state.selectedProduct.price_sell) : 'Rp0';
  document.getElementById('summarySaldo').textContent = rupiah(state.saldo);

  const sisa = state.selectedProduct ? (parseInt(state.saldo || 0, 10) - parseInt(state.selectedProduct.price_sell || 0, 10)) : state.saldo;
  document.getElementById('summarySisaSaldo').textContent = rupiah(sisa);
}

function updateNominalButtonState(){
  document.getElementById('btnToSummary').disabled = !state.selectedProduct;
}

function updateBayarButtonState(){
  const btn = document.getElementById('btnBayar');
  const userId = document.getElementById('userIdGame').value.trim();
  const zoneId = document.getElementById('zoneIdGame').value.trim();

  let ok = !!state.selectedProduct && userId !== '';
  if(state.selectedBrandNeedZone){
    ok = ok && zoneId !== '';
  }
  if(state.checkoutBusy){
    ok = false;
  }
  btn.disabled = !ok;
}

function getFilteredBrands(){
  const keyword = document.getElementById('searchGame').value.trim().toLowerCase();
  return state.brands.filter(item => String(item.brand).toLowerCase().includes(keyword));
}

function renderPopularGames(){
  const wrap = document.getElementById('popularGrid');
  if(!state.brands.length){
    wrap.innerHTML = '<div class="empty" style="min-width:100%">Belum ada game populer.</div>';
    document.getElementById('popularInfo').textContent = '0 game';
    return;
  }

  const filteredBrands = getFilteredBrands();
  const byPreferred = [];
  const others = [];

  filteredBrands.forEach(item => {
    if (popularPreferred.some(p => p.toLowerCase() === String(item.brand).toLowerCase())) byPreferred.push(item);
    else others.push(item);
  });

  byPreferred.sort((a,b) =>
    popularPreferred.findIndex(p => p.toLowerCase() === String(a.brand).toLowerCase()) -
    popularPreferred.findIndex(p => p.toLowerCase() === String(b.brand).toLowerCase())
  );

  const popular = [...byPreferred, ...others].slice(0, 8);
  document.getElementById('popularInfo').textContent = popular.length + ' game';

  if (!popular.length) {
    wrap.innerHTML = '<div class="empty" style="min-width:100%">Game tidak ditemukan.</div>';
    return;
  }

  wrap.innerHTML = popular.map(item => `
    <button class="popular-item" type="button" data-brand="${esc(item.brand)}">
      <div class="badge-top"><i class="fa-solid fa-fire"></i><span>Populer</span></div>
      ${gameVisualHtml(item.brand, 'game-icon')}
      <strong>${esc(item.brand)}</strong>
      <small>${item.total} produk tersedia</small>
    </button>
  `).join('');
}

function renderBrands(){
  const wrap = document.getElementById('brandGrid');
  if(!state.brands.length){
    wrap.innerHTML = '<div class="empty">Belum ada brand game aktif.</div>';
    return;
  }

  const filteredBrands = getFilteredBrands();
  if (!filteredBrands.length) {
    wrap.innerHTML = '<div class="empty">Game tidak ditemukan.</div>';
    return;
  }

  wrap.innerHTML = filteredBrands.map(item => `
    <button type="button" class="brand-card ${state.selectedBrand === item.brand ? 'active' : ''}" data-brand="${esc(item.brand)}">
      <div class="brand-top">
        ${gameVisualHtml(item.brand, 'brand-icon', 58)}
        <div class="brand-body">
          <div class="brand-row">
            <div class="brand-name">${esc(item.brand)}</div>
            <div class="brand-chip">${item.total} produk</div>
          </div>
          <div class="brand-meta">
            ${parseInt(item.need_zone_id || 0, 10) === 1 ? 'Butuh User ID + Zone ID' : 'Butuh User ID'}
          </div>
        </div>
      </div>
    </button>
  `).join('');
}

function renderProducts(){
  const productLoading = document.getElementById('productLoading');
  const productEmpty = document.getElementById('productEmpty');
  const productGrid = document.getElementById('productGrid');

  state.filteredProducts = state.products
    .filter(p => p.brand === state.selectedBrand)
    .sort((a,b) => parseInt(a.price_sell || 0, 10) - parseInt(b.price_sell || 0, 10));

  productLoading.classList.add('hidden');
  productGrid.innerHTML = '';

  if(!state.selectedBrand){
    productEmpty.classList.remove('hidden');
    productEmpty.textContent = 'Pilih game terlebih dahulu.';
    return;
  }

  if(!state.filteredProducts.length){
    productEmpty.classList.remove('hidden');
    productEmpty.textContent = 'Belum ada produk untuk game ini.';
    return;
  }

  productEmpty.classList.add('hidden');

  productGrid.innerHTML = state.filteredProducts.map(item => `
    <button type="button" class="product-item ${state.selectedProduct && Number(state.selectedProduct.id) === Number(item.id) ? 'active' : ''}" data-product-id="${item.id}">
      <div class="product-left">
        <div class="name">${esc(item.product_name)}</div>
        <div class="sku" style="display:none">${esc(item.sku_code)}</div>
        <div class="pick"><i class="fa-solid fa-bolt"></i><span>Pilih paket ini</span></div>
      </div>
      <div class="price">${rupiah(item.price_sell)}</div>
    </button>
  `).join('');

  updateSelectedGameUI();
}

function chooseGame(brand){
  state.selectedBrand = brand;
  const brandObj = state.brands.find(b => b.brand === brand) || null;
  state.selectedBrandNeedZone = !!(brandObj && parseInt(brandObj.need_zone_id || 0, 10) === 1);
  state.selectedProduct = null;

  renderBrands();
  renderProducts();
  updateZoneVisibility();
  updateSelectedGameUI();
  updateSummary();
  updateNominalButtonState();
  updateBayarButtonState();
  openModal('modalUser');
}

function selectProduct(id){
  const found = state.filteredProducts.find(p => parseInt(p.id, 10) === parseInt(id, 10));
  if(!found) return;
  state.selectedProduct = found;
  renderProducts();
  updateSummary();
  updateNominalButtonState();
  updateBayarButtonState();
}

function backToUserModal(){ openModal('modalUser'); }
function backToNominalModal(){ openModal('modalNominal'); }

function validateUserData(){
  const userId = document.getElementById('userIdGame').value.trim();
  const zoneId = document.getElementById('zoneIdGame').value.trim();

  if (!state.selectedBrand) {
    showToast('Pilih game terlebih dahulu');
    return false;
  }
  if (!userId) {
    showToast('User ID game wajib diisi');
    return false;
  }
  if (state.selectedBrandNeedZone && !zoneId) {
    showToast('Zone ID wajib diisi untuk game ini');
    return false;
  }
  return true;
}

async function loadSaldo(silent = false){
  try{
    const json = await fetchJson('../api/saldo.php');
    if(json.success && json.data){
      state.saldo = parseInt(json.data.saldo || 0, 10);
      document.getElementById('saldoText').textContent = rupiah(state.saldo);
      document.getElementById('saldoMeta').textContent = silent ? 'Saldo diperbarui otomatis' : 'Saldo berhasil dimuat';
      updateSummary();
    }else if(!silent){
      document.getElementById('saldoMeta').textContent = 'Gagal memuat saldo';
    }
  }catch(e){
    if(!silent) document.getElementById('saldoMeta').textContent = 'Gagal memuat saldo';
  }
}

async function loadProduk(){
  const brandInfo = document.getElementById('brandInfo');
  try{
    const json = await fetchJson('../api/game_produk.php');
    if(!json.success){
      brandInfo.textContent = 'Gagal memuat brand';
      return;
    }

    state.brands = Array.isArray(json.data?.brands) ? json.data.brands : [];
    state.products = Array.isArray(json.data?.products) ? json.data.products : [];
    state.brandLogoMap = {};

    state.brands.forEach(item => {
      const brandName = String(item.brand || '').trim();
      const logoUrl = String(item.logo_url || item.logo || '').trim();
      if (brandName && logoUrl) {
        state.brandLogoMap[brandName] = logoUrl;
      }
    });

    renderPopularGames();
    renderBrands();
    brandInfo.textContent = state.brands.length + ' brand tersedia';
  }catch(e){
    brandInfo.textContent = 'Terjadi kesalahan saat memuat brand';
  }
}

function renderHistoryItems(items){
  const list = document.getElementById('historyList');
  const empty = document.getElementById('historyEmpty');

  if(!Array.isArray(items) || items.length === 0){
    list.innerHTML = '';
    empty.classList.remove('hidden');
    return;
  }

  empty.classList.add('hidden');
  list.innerHTML = items.map((item, idx) => `
    <div class="history-item ${idx === 0 ? 'new' : ''}">
      <div class="history-head">
        <div>
          <div class="history-name">${esc(item.product_name)}</div>
          <div class="history-meta">
            Ref ID: ${esc(item.ref_id || '-')}<br>
            UID: ${esc(item.user_id_game || '-')}${item.zone_id ? ' (' + esc(item.zone_id) + ')' : ''}<br>
            ${esc(item.created_at || '')}
          </div>
        </div>
        <div>
          <div class="history-price">${rupiah(item.price_sell)}</div>
          <div style="margin-top:6px;text-align:right">${badgeStatus(item.status)}</div>
        </div>
      </div>
      ${item.sn ? `<div class="history-meta" style="margin-top:10px"><strong>SN:</strong> ${esc(item.sn)}</div>` : ''}
      ${item.message ? `<div class="history-meta" style="margin-top:6px">${esc(item.message)}</div>` : ''}
    </div>
  `).join('');
}

async function loadHistory(silent = false){
  const loading = document.getElementById('historyLoading');

  if(!silent) loading.classList.remove('hidden');

  try{
    const json = await fetchJson('../api/game_history.php');
    loading.classList.add('hidden');

    if(!json.success || !Array.isArray(json.data)){
      if(!silent) renderHistoryItems([]);
      return;
    }

    const currentJson = JSON.stringify(json.data);
    const changed = currentJson !== state.lastHistoryJson;
    state.lastHistoryJson = currentJson;

    renderHistoryItems(json.data);

    if(changed && silent){
      showToast('Riwayat transaksi diperbarui');
    }
    setRefreshInfo('Auto refresh aktif');
  }catch(e){
    loading.classList.add('hidden');
    if(!silent){
      loading.textContent = 'Gagal memuat riwayat';
      loading.classList.remove('hidden');
    }
    setRefreshInfo('Auto refresh gagal sesaat');
  }
}

async function refreshLiveData(){
  await Promise.all([ loadSaldo(true), loadHistory(true) ]);
}

function startPolling(){
  stopPolling();
  state.pollTimer = setInterval(async () => {
    if(document.hidden) return;
    if(state.checkoutBusy) return;
    await refreshLiveData();
  }, 8000);
}

function stopPolling(){
  if(state.pollTimer){
    clearInterval(state.pollTimer);
    state.pollTimer = null;
  }
}

async function doCheckout(){
  if (!state.selectedProduct || state.checkoutBusy) return;

  const userIdGame = document.getElementById('userIdGame').value.trim();
  const zoneIdGame = document.getElementById('zoneIdGame').value.trim();
  const btn = document.getElementById('btnBayar');

  if (!validateUserData()) return;

  const ok = confirm(
    'Konfirmasi top-up game?\n\n' +
    'Game: ' + (state.selectedBrand || '-') + '\n' +
    'Produk: ' + (state.selectedProduct?.product_name || '-') + '\n' +
    'Harga: ' + rupiah(state.selectedProduct?.price_sell || 0) + '\n' +
    'Target: ' + userIdGame + (zoneIdGame ? ' (' + zoneIdGame + ')' : '')
  );
  if (!ok) return;

  state.checkoutBusy = true;
  updateBayarButtonState();

  const oldText = btn.textContent;
  btn.textContent = 'Memproses...';
  setRefreshInfo('Checkout sedang diproses...');

  try {
    const body = new URLSearchParams();
    body.append('product_id', String(state.selectedProduct.id));
    body.append('user_id_game', userIdGame);
    body.append('zone_id', zoneIdGame);

    const res = await fetch('../api/game_checkout.php', {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-CSRF-Token': <?= json_encode($_SESSION['emoney_csrf']) ?> },
      body: body.toString()
    });

    const json = await res.json();
    await loadSaldo(true);
    await loadHistory(true);

    const status = String(json.data?.status || '').toUpperCase();

    if (!json.success) {
      if (status === 'FAILED' || status === 'REFUNDED') {
        showToast('Top-up gagal. Saldo dikembalikan.', 4500);
      } else {
        showToast(json.message || 'Checkout gagal', 4200);
      }
      return;
    }

    if (status === 'SUCCESS') showToast('Top-up berhasil.', 4200);
    else if (status === 'PROCESSING') showToast('Top-up sedang diproses.', 4200);
    else if (status === 'FAILED' || status === 'REFUNDED') showToast('Top-up gagal. Saldo dikembalikan.', 4500);
    else showToast('Top-up dibuat dengan status: ' + status, 4500);

    closeAllModals();
    state.selectedProduct = null;
    updateNominalButtonState();
    updateBayarButtonState();
    renderProducts();

    if (status === 'SUCCESS' || status === 'PROCESSING') {
      await loadHistory(true);
      goToHistoryTab();
    }
  } catch (e) {
    showToast('Terjadi kesalahan saat checkout', 4000);
  } finally {
    state.checkoutBusy = false;
    btn.textContent = oldText;
    updateBayarButtonState();
    setRefreshInfo('Auto refresh aktif');
  }
}

document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => setActiveTab(btn.dataset.tab));
});

document.getElementById('btnToNominal').addEventListener('click', () => {
  if (!validateUserData()) return;
  renderProducts();
  updateNominalButtonState();
  openModal('modalNominal');
});

document.getElementById('btnToSummary').addEventListener('click', () => {
  if (!state.selectedProduct) {
    showToast('Pilih nominal terlebih dahulu');
    return;
  }
  updateSummary();
  updateBayarButtonState();
  openModal('modalSummary');
});

document.getElementById('btnBayar').addEventListener('click', doCheckout);

document.getElementById('userIdGame').addEventListener('input', () => {
  updateSummary();
  updateBayarButtonState();
});

document.getElementById('zoneIdGame').addEventListener('input', () => {
  updateSummary();
  updateBayarButtonState();
});

document.getElementById('searchGame').addEventListener('input', () => {
  renderPopularGames();
  renderBrands();
});

document.getElementById('popularGrid').addEventListener('click', function(e){
  const btn = e.target.closest('[data-brand]');
  if (!btn) return;
  chooseGame(btn.getAttribute('data-brand'));
});

document.getElementById('brandGrid').addEventListener('click', function(e){
  const btn = e.target.closest('[data-brand]');
  if (!btn) return;
  chooseGame(btn.getAttribute('data-brand'));
});

document.getElementById('productGrid').addEventListener('click', function(e){
  const btn = e.target.closest('[data-product-id]');
  if (!btn) return;
  selectProduct(parseInt(btn.getAttribute('data-product-id'), 10));
});

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') closeAllModals();
});

document.addEventListener('visibilitychange', async () => {
  if (!document.hidden) await refreshLiveData();
});

window.addEventListener('beforeunload', stopPolling);

(async function init(){
  await loadSaldo();
  await loadProduk();
  await loadHistory();
  updateZoneVisibility();
  updateSummary();
  updateNominalButtonState();
  updateBayarButtonState();
  startPolling();
})();
</script>
</body>
</html>
