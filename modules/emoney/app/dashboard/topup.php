<?php
session_start();
require_once __DIR__.'/_central_control.php';
if (!isset($_SESSION['login'])) {
  header('Location: ../login.php'); exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Topup</title>

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
  --bg:#0f172a;
  --card:#1e293b;
}

body{
  margin:0;
  font-family:system-ui;
  background:var(--bg);
  color:#fff;
}

/* HEADER */
.header{
  background:linear-gradient(135deg,var(--red),var(--olive));
  padding:25px 20px 80px;
  border-radius:0 0 30px 30px;
}

.saldo strong{
  font-size:26px;
}

/* QUICK */
.quick{
  display:flex;
  justify-content:space-around;
  margin-top:-40px;
}

.quick .btn{
  background:var(--card);
  width:70px;
  height:70px;
  border-radius:20px;
  display:flex;
  flex-direction:column;
  align-items:center;
  justify-content:center;
  font-size:12px;
  text-decoration:none;
  color:#fff;
}

/* MENU */
.container{padding:20px;}

.grid{
  display:grid;
  grid-template-columns:repeat(4,1fr);
  gap:12px;
}

.menu{
  text-align:center;
  color:#fff;
  text-decoration:none;
  font-size:12px;
}

.icon{
  background:var(--card);
  width:60px;
  height:60px;
  margin:auto;
  border-radius:18px;
  display:flex;
  align-items:center;
  justify-content:center;
  margin-bottom:6px;
}

/* SLIDER */
.slider{
  display:flex;
  gap:12px;
  overflow-x:auto;
  margin-top:15px;
}

.game{
  min-width:120px;
  background:var(--card);
  border-radius:15px;
  padding:10px;
  text-align:center;
}

.game img{
  width:100%;
  height:80px;
  border-radius:10px;
  object-fit:cover;
}
</style>
</head>
<body>

<div class="header">
  <h2>Topup</h2>
  <div class="saldo">
    Saldo
    <strong id="saldo">Memuat...</strong>
  </div>
</div>

<div class="quick">
  <div class="btn"><i class="fa fa-plus"></i>Topup</div>
  <div class="btn"><i class="fa fa-clock"></i>History</div>
  <div class="btn"><i class="fa fa-bell"></i>Info</div>
</div>

<div class="container">

  <!-- MENU -->
  <div class="grid">
    <a href="topup_game.php" class="menu">
      <div class="icon"><i class="fa fa-gamepad"></i></div>
      Game
    </a>

    <a href="topup_pulsa.php" class="menu">
      <div class="icon"><i class="fa fa-signal"></i></div>
      Pulsa
    </a>
  </div>

  <!-- GAME POPULER -->
  <h3>Game Populer</h3>

  <div class="slider" id="gameList">
    <div>Memuat...</div>
  </div>

</div>

<script>
const API = <?=json_encode(rtrim(sds_base_url('emoney/api'), '/'), JSON_UNESCAPED_SLASHES)?>;

/* FORMAT */
function rupiah(n){
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(n||0));
}

/* LOAD SALDO (SAMA SEPERTI DASHBOARD) */
function loadSaldo(){
  fetch(API + '/saldo.php',{credentials:'include'})
    .then(r=>r.json())
    .then(d=>{
      if(d.success){
        document.getElementById('saldo').innerText = rupiah(d.data.saldo);
      }
    });
}

/* LOAD GAME DARI API */
function loadGame(){
  fetch(API + '/game_produk.php',{credentials:'include'})
    .then(r=>r.json())
    .then(d=>{
      if(!d.success) return;

      let html = '';

      d.data.slice(0,10).forEach(g=>{
        const nama = g.product_name || 'Game';
        const brand = g.brand || '';
        const logo = g.logo 
          ? '../uploads/game-logo/' + g.logo
          : 'https://via.placeholder.com/120x80';

        html += `
          <a href="topup_game.php?brand=${encodeURIComponent(brand)}" class="game">
            <img src="${logo}">
            <small>${nama}</small>
          </a>
        `;
      });

      document.getElementById('gameList').innerHTML = html;
    });
}

/* INIT */
loadSaldo();
loadGame();
</script>

</body>
</html>
