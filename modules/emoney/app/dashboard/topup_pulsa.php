<?php
require_once dirname(__DIR__, 2) . '/config/runtime.php';
require_once __DIR__.'/_central_control.php';
sds_session_start();
if (!isset($_SESSION['login'])) {
  header('Location: ../login.php'); exit;
}
if (empty($_SESSION['emoney_csrf'])) $_SESSION['emoney_csrf'] = bin2hex(random_bytes(32));
if (empty($_SESSION['pulsa_request_key'])) $_SESSION['pulsa_request_key'] = bin2hex(random_bytes(24));
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Topup</title>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
body{
  margin:0;
  font-family:'Inter',sans-serif;
  background:#f5f7fb;
}

/* HEADER */
.header{
  background:linear-gradient(135deg,#6366f1,#4f46e5);
  color:#fff;
  padding:20px;
  border-bottom-left-radius:20px;
  border-bottom-right-radius:20px;
}

.header h2{
  margin:0;
  font-size:18px;
}

/* CONTAINER */
.container{
  margin-top:-20px;
  padding:16px;
}

/* CARD */
.card{
  background:#fff;
  border-radius:16px;
  padding:16px;
  margin-bottom:16px;
  box-shadow:0 8px 20px rgba(0,0,0,.05);
}

/* INPUT */
input{
  width:100%;
  padding:14px;
  border-radius:12px;
  border:1px solid #eee;
  font-size:15px;
  outline:none;
}

/* TAB */
.tabs{
  display:flex;
  background:#eef2ff;
  border-radius:12px;
  padding:4px;
}

.tab{
  flex:1;
  padding:10px;
  text-align:center;
  border-radius:10px;
  font-weight:600;
  cursor:pointer;
  color:#555;
}

.tab.active{
  background:#fff;
  color:#4f46e5;
}

/* OPERATOR */
.operator{
  display:flex;
  gap:8px;
  overflow:auto;
  margin-top:12px;
}

.operator div{
  padding:6px 12px;
  background:#f1f5f9;
  border-radius:999px;
  font-size:12px;
  cursor:pointer;
}

.operator .active{
  background:#4f46e5;
  color:#fff;
}

/* PRODUK */
.menu{
  display:grid;
  grid-template-columns: repeat(2,1fr);
  gap:12px;
  margin-top:10px;
}

.item{
  background:#f9fafb;
  padding:14px;
  border-radius:14px;
  text-align:center;
  cursor:pointer;
  transition:.2s;
}

.item.active{
  border:2px solid #4f46e5;
  background:#eef2ff;
}

.item b{
  display:block;
  margin-top:5px;
}

/* BOTTOM BAR */
.bottom{
  position:fixed;
  bottom:0;
  left:0;
  right:0;
  background:#fff;
  padding:15px;
  box-shadow:0 -5px 15px rgba(0,0,0,.05);
}

button{
  width:100%;
  padding:14px;
  border:none;
  border-radius:12px;
  background:#4f46e5;
  color:#fff;
  font-size:16px;
  font-weight:600;
}

button:disabled{
  background:#ccc;
}
</style>
</head>

<body>

<div class="header">
  <h2>Topup Pulsa & Data</h2>
</div>

<div class="container">

  <!-- INPUT -->
  <div class="card">
    <input type="text" id="nomor" placeholder="Masukkan nomor HP" onkeyup="detectOperator()">
  </div>

  <!-- TAB -->
  <div class="card">
    <div class="tabs">
      <div class="tab active" onclick="setType('pulsa',this)">Pulsa</div>
      <div class="tab" onclick="setType('data',this)">Paket Data</div>
    </div>

    <div class="operator" id="operatorList"></div>
  </div>

  <!-- PRODUK -->
  <div class="card">
    <div class="menu" id="produkList"></div>
  </div>

</div>

<!-- BOTTOM -->
<div class="bottom">
  <button id="btnBeli" onclick="beli()" disabled>Lanjutkan</button>
</div>

<script>
let selectedType = "pulsa";
let selectedOperator = "";
let selectedKode = null;

const operators = ["Telkomsel","Indosat","XL","Tri","Smartfren"];

function renderOperator(){
  let html="";
  operators.forEach(op=>{
    html += `<div onclick="pilihOperator('${op}',this)">${op}</div>`;
  });
  document.getElementById("operatorList").innerHTML = html;
}
renderOperator();

function pilihOperator(op,el){
  document.querySelectorAll('.operator div').forEach(i=>i.classList.remove('active'));
  el.classList.add('active');
  selectedOperator = op;
  loadProduk();
}

function setType(type,el){
  selectedType = type;
  document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
  el.classList.add('active');
  loadProduk();
}

function loadProduk(){
  fetch(`../api/pulsa_produk.php?type=${selectedType}&operator=${selectedOperator}`)
  .then(res=>res.json())
  .then(data=>{
    let html="";
    data.forEach(p=>{
      html += `<div class="item" onclick="pilihProduk(this,'${p.kode}')">
                ${p.nama}
                <b>Rp ${p.harga}</b>
              </div>`;
    });
    document.getElementById("produkList").innerHTML = html;
  });
}

function pilihProduk(el,kode){
  document.querySelectorAll('.item').forEach(i=>i.classList.remove('active'));
  el.classList.add('active');
  selectedKode = kode;
  document.getElementById("btnBeli").disabled = false;
}

function detectOperator(){
  let n = document.getElementById("nomor").value;

  if(n.startsWith("081") || n.startsWith("082")) selectedOperator="Telkomsel";
  else if(n.startsWith("085")) selectedOperator="Indosat";
  else if(n.startsWith("087")) selectedOperator="XL";
  else if(n.startsWith("089")) selectedOperator="Tri";

  loadProduk();
}

function beli(){
  let nomor = document.getElementById("nomor").value;

  fetch("../api/pulsa_trx.php",{
    method:"POST",
    headers:{
      "Content-Type":"application/x-www-form-urlencoded"
    },
    body:`nomor=${encodeURIComponent(nomor)}&kode=${encodeURIComponent(selectedKode)}&csrf=${encodeURIComponent(<?= json_encode($_SESSION['emoney_csrf']) ?>)}&request_key=${encodeURIComponent(<?= json_encode($_SESSION['pulsa_request_key']) ?>)}`
  })
  .then(res=>res.json())
  .then(res=>{
    alert(res.msg);
    window.location.href="riwayat.php";
  });
}

loadProduk();
</script>

</body>
</html>
