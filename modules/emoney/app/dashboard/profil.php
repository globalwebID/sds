<?php
require_once dirname(__DIR__, 2) . '/config/runtime.php';
require_once __DIR__.'/_central_control.php';
sds_require_installed();
session_start();
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
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Profil Siswa</title>

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
  --primary:#e11d48;
  --primary-2:#9ca34c;
  --dark:#111827;
  --muted:#6b7280;
  --line:#e5e7eb;
  --soft:#f3f4f6;
  --white:#ffffff;
}

*{ box-sizing:border-box; }

body{
  background:#f8fafc;
}

.section{
  background:#fff;
  margin:12px;
  padding:14px;
  border-radius:16px;
  box-shadow:0 6px 18px rgba(0,0,0,.08);
}

.section h2{
  margin:0 0 10px;
  font-size:16px;
  display:flex;
  gap:8px;
  align-items:center;
  color:var(--dark);
}

.row-top{
  display:flex;
  gap:12px;
  align-items:center;
}

.avatar{
  width:60px;
  height:80px;
  border-radius:10px;
  background:linear-gradient(135deg,var(--primary),var(--primary-2));
  display:flex;
  align-items:center;
  justify-content:center;
  color:#fff;
  font-weight:900;
  flex:0 0 auto;
  overflow:hidden;
  box-shadow:0 6px 16px rgba(225,29,72,.18);
}

.avatar-img{
  width:100%;
  height:100%;
  object-fit:cover;
  display:block;
}

.avatar-text{
  font-weight:900;
  font-size:20px;
}

.meta strong{
  display:block;
  font-size:16px;
  color:var(--dark);
}

.meta small{
  display:block;
  color:#6b7280;
  margin-top:2px;
}

.badges{
  display:flex;
  flex-wrap:wrap;
  gap:10px;
  margin-top:10px;
}

.badge{
  display:inline-flex;
  gap:8px;
  align-items:center;
  background:rgba(17,24,39,.06);
  border:1px solid rgba(17,24,39,.08);
  padding:8px 10px;
  border-radius:999px;
  font-size:12px;
  color:#111827;
}

.kv{ display:grid; gap:10px; }
.grid{ display:grid; gap:10px; }

@media(min-width:768px){
  .kv.two{ grid-template-columns:1fr 1fr; }
  .grid.two{ grid-template-columns:1fr 1fr; }
}

.item{
  border:1px solid #f3f4f6;
  border-radius:14px;
  padding:10px 12px;
  background:#fff;
}

.item .k{
  font-size:12px;
  color:#6b7280;
}

.item .v{
  margin-top:4px;
  font-weight:500;
  color:#111827;
  word-break:break-word;
  font-size:12px;
}

.muted{
  color:#9ca3af;
  font-weight:600;
}

.field label{
  display:block;
  font-size:12px;
  color:#6b7280;
  margin-bottom:6px;
}

.field input,
.field select,
.field textarea{
  width:100%;
  padding:11px 12px;
  border:1px solid #e5e7eb;
  border-radius:12px;
  outline:none;
  background:#fff;
  color:#111827;
}

.field input:focus,
.field select:focus,
.field textarea:focus{
  border-color:#e11d48;
  box-shadow:0 0 0 3px rgba(225,29,72,.14);
}

.field textarea{
  min-height:90px;
  resize:vertical;
}

.actions{
  padding:12px;
  display:grid;
  gap:10px;
  position:fixed;
  bottom:60px;
  left:0;
  width:100%;
  background:#ffffff;
  border-radius:15px 15px 0 0;
  box-shadow:0 -4px 15px rgba(0,0,0,.1);
  z-index:999;
}

@media(min-width:768px){
  .actions{
    left:50%;
    transform:translateX(-50%);
    max-width:720px;
    border-radius:18px 18px 0 0;
  }
}

.btnx{
  border:0;
  border-radius:14px;
  padding:12px 14px;
  font-weight:900;
  cursor:pointer;
  transition:.18s ease;
}

.btnx:active{
  transform:scale(.99);
}

.btn-primary{
  background:#e11d48;
  color:#fff;
}

.btn-outline{
  background:#111827;
  color:#fff;
  border:1px solid #e5e7eb;
}

.btn-dark{
  background:#111827;
  color:#fff;
}

.note{
  font-size:12px;
  color:#9ca3af;
  text-align:center;
  margin:0;
}

.hidden{
  display:none !important;
}

/* ==== MODAL UBAH PIN ==== */
.modal-backdrop{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.45);
  display:flex;
  align-items:center;
  justify-content:center;
  padding:18px;
  z-index:9999;
}

.modal-card{
  width:100%;
  max-width:420px;
  background:#fff;
  border-radius:18px;
  box-shadow:0 18px 60px rgba(0,0,0,.25);
  overflow:hidden;
}

.modal-head{
  padding:14px 16px;
  background:linear-gradient(135deg,#e11d48,#9ca34c);
  color:#fff;
}

.modal-head strong{
  display:block;
}

.modal-head small{
  display:block;
  opacity:.9;
  margin-top:4px;
  font-size:12px;
}

.modal-body{
  padding:14px 16px;
}

/* ==== MODAL ZOOM FOTO ==== */
.modal-photo{
  position:fixed;
  inset:0;
  background:rgba(0,0,0,.65);
  display:none;
  align-items:center;
  justify-content:center;
  padding:18px;
  z-index:10000;
}

.modal-photo.show{
  display:flex;
}

.modal-photo .box{
  width:100%;
  max-width:520px;
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 18px 60px rgba(0,0,0,.35);
  background:#111;
}

.modal-photo img{
  width:100%;
  height:auto;
  display:block;
}

.modal-photo .bar{
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:10px 12px;
  background:rgba(0,0,0,.55);
  color:#fff;
  font-size:12px;
}

.modal-photo .close-btn{
  border:0;
  background:rgba(255,255,255,.12);
  color:#fff;
  padding:8px 10px;
  border-radius:12px;
  font-weight:800;
  cursor:pointer;
}
</style>
</head>

<body>
<header class="top">
  <h1>Profil</h1>
  <small id="sub">Memuat...</small>
</header>

<!-- =========================
     VIEW MODE
========================= -->
<div id="viewMode" style="margin-bottom:250px;">

  <div class="section">
    <div class="row-top">
      <div class="avatar" id="avatar">
        <img id="avatarImg" class="avatar-img hidden" alt="Foto Siswa">
        <span id="avatarText" class="avatar-text">S</span>
      </div>

      <div class="meta">
        <strong id="namaView">Memuat...</strong>
        <small id="nisnView">NISN: -</small>

        <div class="badges">
          <div class="badge">
            <i class="fa-solid fa-wallet"></i>
            Saldo: <b id="saldoView">Memuat...</b>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="section">
    <h2><i class="fa-solid fa-user"></i> Data Pribadi</h2>
    <div class="kv two">
      <div class="item"><div class="k">Nama Lengkap</div><div class="v" id="v_nama_lengkap">-</div></div>
      <div class="item"><div class="k">Email</div><div class="v" id="v_email">-</div></div>
      <div class="item"><div class="k">Jenis Kelamin</div><div class="v" id="v_jenis_kelamin">-</div></div>
      <div class="item"><div class="k">Agama</div><div class="v" id="v_agama">-</div></div>
      <div class="item"><div class="k">Tempat Lahir</div><div class="v" id="v_tempat_lahir">-</div></div>
      <div class="item"><div class="k">Tanggal Lahir</div><div class="v" id="v_tanggal_lahir">-</div></div>
      <div class="item"><div class="k">Hobi</div><div class="v" id="v_hobi">-</div></div>
      <div class="item"><div class="k">Cita-cita</div><div class="v" id="v_cita_cita">-</div></div>
    </div>
  </div>

  <div class="section">
    <h2><i class="fa-solid fa-location-dot"></i> Alamat</h2>
    <div class="kv">
      <div class="item"><div class="k">Alamat</div><div class="v" id="v_alamat">-</div></div>
      <div class="kv two">
        <div class="item"><div class="k">Desa</div><div class="v" id="v_desa">-</div></div>
        <div class="item"><div class="k">Kecamatan</div><div class="v" id="v_kecamatan">-</div></div>
        <div class="item"><div class="k">Kota</div><div class="v" id="v_kota">-</div></div>
        <div class="item"><div class="k">Provinsi</div><div class="v" id="v_provinsi">-</div></div>
      </div>
    </div>
  </div>

  <div class="section">
    <h2><i class="fa-solid fa-phone"></i> Kontak</h2>
    <div class="kv two">
      <div class="item"><div class="k">No. HP Siswa</div><div class="v" id="v_nohp_siswa">-</div></div>
      <div class="item"><div class="k">No. HP Ortu</div><div class="v" id="v_nohp_ortu">-</div></div>
    </div>
  </div>

  <div class="section">
    <h2><i class="fa-solid fa-people-roof"></i> Orang Tua / Wali</h2>
    <div class="kv two">
      <div class="item"><div class="k">Nama Ayah</div><div class="v" id="v_nama_ayah">-</div></div>
      <div class="item"><div class="k">NIK Ayah</div><div class="v" id="v_nik_ayah">-</div></div>
      <div class="item"><div class="k">Nama Ibu</div><div class="v" id="v_nama_ibu">-</div></div>
      <div class="item"><div class="k">NIK Ibu</div><div class="v" id="v_nik_ibu">-</div></div>
      <div class="item"><div class="k">Nama Wali</div><div class="v" id="v_nama_wali">-</div></div>
      <div class="item"><div class="k">NIK Wali</div><div class="v" id="v_nik_wali">-</div></div>
    </div>

    <div class="note muted" style="margin:10px 2px 0;">
      Data sensitif (SALDO, NISN & NIS) tidak bisa diubah di sini.
    </div>
  </div>

</div>

<!-- =========================
     EDIT MODE
========================= -->
<form id="editMode" class="hidden" style="margin-bottom:300px;">
  <div class="section">
    <h2><i class="fa-solid fa-user-pen"></i> Edit Data Pribadi</h2>
    <div class="grid two">
      <div class="field">
        <label>Nama Lengkap</label>
        <input name="nama_lengkap" id="nama_lengkap" required>
      </div>

      <div class="field">
        <label>Email</label>
        <input name="email" id="email" type="email" placeholder="contoh@email.com">
      </div>

      <div class="field">
        <label>Jenis Kelamin</label>
        <select name="jenis_kelamin" id="jenis_kelamin">
          <option value="">-- Pilih --</option>
          <option value="Laki-laki">Laki-laki</option>
          <option value="Perempuan">Perempuan</option>
        </select>
      </div>

      <div class="field">
        <label>Agama</label>
        <select name="agama" id="agama">
          <option value="">-- Pilih Agama --</option>
          <option value="Islam">Islam</option>
          <option value="Kristen">Kristen</option>
          <option value="Katolik">Katolik</option>
          <option value="Hindu">Hindu</option>
          <option value="Buddha">Buddha</option>
          <option value="Khonghucu">Khonghucu</option>
          <option value="Lainnya">Lainnya</option>
        </select>
      </div>

      <div class="field">
        <label>Tempat Lahir</label>
        <input name="tempat_lahir" id="tempat_lahir">
      </div>

      <div class="field">
        <label>Tanggal Lahir</label>
        <input name="tanggal_lahir" id="tanggal_lahir" type="date">
      </div>

      <div class="field">
        <label>Hobi</label>
        <input name="hobi" id="hobi">
      </div>

      <div class="field">
        <label>Cita-cita</label>
        <input name="cita_cita" id="cita_cita">
      </div>
    </div>
  </div>

  <div class="section">
    <h2><i class="fa-solid fa-location-dot"></i> Edit Alamat</h2>
    <div class="grid">
      <div class="field">
        <label>Alamat</label>
        <textarea name="alamat" id="alamat"></textarea>
      </div>

      <div class="grid two">
        <div class="field"><label>Desa</label><input name="desa" id="desa"></div>
        <div class="field"><label>Kecamatan</label><input name="kecamatan" id="kecamatan"></div>
        <div class="field"><label>Kota</label><input name="kota" id="kota"></div>
        <div class="field"><label>Provinsi</label><input name="provinsi" id="provinsi"></div>
      </div>
    </div>
  </div>

  <div class="section">
    <h2><i class="fa-solid fa-phone"></i> Edit Kontak</h2>
    <div class="grid two">
      <div class="field"><label>No. HP Siswa</label><input name="nohp_siswa" id="nohp_siswa" inputmode="numeric"></div>
      <div class="field"><label>No. HP Ortu</label><input name="nohp_ortu" id="nohp_ortu" inputmode="numeric"></div>
    </div>
  </div>

  <div class="section">
    <h2><i class="fa-solid fa-people-roof"></i> Edit Orang Tua / Wali</h2>
    <div class="grid two">
      <div class="field"><label>Nama Ayah</label><input name="nama_ayah" id="nama_ayah"></div>
      <div class="field"><label>NIK Ayah</label><input name="nik_ayah" id="nik_ayah" inputmode="numeric"></div>

      <div class="field"><label>Nama Ibu</label><input name="nama_ibu" id="nama_ibu"></div>
      <div class="field"><label>NIK Ibu</label><input name="nik_ibu" id="nik_ibu" inputmode="numeric"></div>

      <div class="field"><label>Nama Wali</label><input name="nama_wali" id="nama_wali"></div>
      <div class="field"><label>NIK Wali</label><input name="nik_wali" id="nik_wali" inputmode="numeric"></div>
    </div>
  </div>
</form>

<div class="actions">
  <p class="note" id="statusMsg">Update: -</p>

  <button id="btnEdit" class="btnx btn-primary" type="button">
    <i class="fa-solid fa-pen-to-square"></i> Edit Data
  </button>

  <button id="btnSave" class="btnx btn-primary hidden" type="button">
    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
  </button>

  <button id="btnCancel" class="btnx btn-outline hidden" type="button">
    <i class="fa-solid fa-xmark"></i> Batal
  </button>

  <button id="btnPin" class="btnx btn-outline" type="button">
    <i class="fa-solid fa-key"></i> Ubah PIN
  </button>
</div>

<!-- MODAL UBAH PIN -->
<div id="pinModal" class="modal-backdrop hidden">
  <div class="modal-card">
    <div class="modal-head">
      <strong><i class="fa-solid fa-key"></i> Ubah PIN</strong>
      <small>PIN harus 6 digit angka</small>
    </div>

    <div class="modal-body">
      <form id="pinForm" autocomplete="off">
        <div class="field">
          <label>PIN Lama</label>
          <input type="password" name="pin_lama" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
        </div>

        <div class="field">
          <label>PIN Baru</label>
          <input type="password" name="pin_baru" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
        </div>

        <div class="field">
          <label>Konfirmasi PIN Baru</label>
          <input type="password" name="pin_konfirmasi" inputmode="numeric" maxlength="6" pattern="[0-9]{6}" required>
        </div>

        <p class="note" id="pinMsg" style="margin:10px 0 0;">-</p>

        <div style="display:grid; gap:10px; margin-top:12px;">
          <button type="submit" class="btnx btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Simpan PIN
          </button>

          <button type="button" id="pinClose" class="btnx btn-outline">
            <i class="fa-solid fa-xmark"></i> Tutup
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL ZOOM FOTO -->
<div id="photoModal" class="modal-photo" aria-hidden="true">
  <div class="box">
    <div class="bar">
      <span id="photoCaption">Foto Siswa</span>
      <button type="button" id="photoClose" class="close-btn">
        <i class="fa-solid fa-xmark"></i> Tutup
      </button>
    </div>
    <img id="photoPreview" alt="Foto Siswa">
  </div>
</div>

<!-- Bottom Nav -->
<nav class="bottom-nav">
  <a href="index.php"><i class="fa-solid fa-house"></i>Home</a>
  <a href="emoney.php"><i class="fa-solid fa-wallet"></i>e-Money</a>
  <a href="perpustakaan.php"><i class="fa-solid fa-book"></i>Buku</a>
  <a href="absensi.php"><i class="fa-solid fa-calendar-check"></i>Absen</a>
  <a href="profil.php" class="active"><i class="fa-solid fa-user"></i>Profil</a>
</nav>

<script>
const API = <?=json_encode(rtrim(sds_base_url('emoney/api'), '/'), JSON_UNESCAPED_SLASHES)?>;

function rupiah(n){
  return 'Rp ' + new Intl.NumberFormat('id-ID').format(Number(n || 0));
}

function initials(name){
  const s = (name || 'Siswa').trim().split(/\s+/);
  return (s[0]?.[0] || 'S') + (s[1]?.[0] || '');
}

function safe(v){
  return (v === null || v === undefined || String(v).trim() === '') ? '-' : String(v);
}

function formatTanggalIndo(dateStr){
  if(!dateStr) return '-';
  const d = new Date(dateStr);
  if(isNaN(d.getTime())) return dateStr;
  return d.toLocaleDateString('id-ID', {
    day:'numeric',
    month:'long',
    year:'numeric'
  });
}

async function fetchJson(url, opt = {}){
  const res = await fetch(url, { credentials:'include', ...opt });
  const txt = await res.text();

  let j;
  try{
    j = JSON.parse(txt);
  }catch(e){
    throw new Error('Respon bukan JSON: ' + txt.slice(0, 120));
  }

  if(!res.ok){
    throw new Error('HTTP ' + res.status + ': ' + (j.message || 'Gagal'));
  }

  return j;
}

function setStatus(msg){
  const el = document.getElementById('statusMsg');
  const now = new Date().toLocaleTimeString('id-ID', {
    hour:'2-digit',
    minute:'2-digit'
  });
  el.textContent = `${msg} • ${now}`;
}

let currentProfile = null;
let editOpen = false;

function setMode(isEdit){
  editOpen = isEdit;

  document.getElementById('viewMode').classList.toggle('hidden', isEdit);
  document.getElementById('editMode').classList.toggle('hidden', !isEdit);

  document.getElementById('btnEdit').classList.toggle('hidden', isEdit);
  document.getElementById('btnSave').classList.toggle('hidden', !isEdit);
  document.getElementById('btnCancel').classList.toggle('hidden', !isEdit);
  document.getElementById('btnPin').classList.toggle('hidden', isEdit);
}

function resolveFotoUrl(foto, nisn){
  let f = String(foto || '').trim();
  const n = String(nisn || '').trim();

  if(!f) return '';
  if(!n) return '';

  if(/^https?:\/\//i.test(f)){
    return f;
  }

  f = f.replace(/^\/+/, '');
  const appBase = <?= json_encode(rtrim(sds_base_url(), '/'), JSON_UNESCAPED_SLASHES) ?>;

  if(f.startsWith(n + '/')){
    return appBase + '/uploads/' + f;
  }

  if(f.startsWith('uploads/')){
    return appBase + '/' + f;
  }

  return appBase + '/uploads/' + encodeURIComponent(n) + '/' + encodeURIComponent(f);
}
function fillView(p){
  document.getElementById('namaView').innerText = p.nama_lengkap || 'Siswa';
  document.getElementById('nisnView').innerText = 'NISN: ' + safe(p.nisn);
  document.getElementById('saldoView').innerText = rupiah(p.saldo);
  document.getElementById('sub').innerText = 'Tahun Ajaran: ' + (p.tahun_ajaran ? p.tahun_ajaran : '-');

  const img  = document.getElementById('avatarImg');
  const text = document.getElementById('avatarText');
  const fotoUrl = resolveFotoUrl(p.foto, p.nisn);

  img.onerror = null;
  img.onclick = null;

  if(fotoUrl){
    img.src = fotoUrl;
    img.classList.remove('hidden');
    text.classList.add('hidden');
    img.style.cursor = 'zoom-in';

    img.onclick = () => openPhotoModal(fotoUrl, p.nama_lengkap || 'Foto Siswa');

    img.onerror = () => {
      console.warn('Foto gagal dimuat:', fotoUrl);
      img.classList.add('hidden');
      text.classList.remove('hidden');
      text.textContent = initials(p.nama_lengkap);
      img.style.cursor = 'default';
      img.onclick = null;
    };
  } else {
    img.classList.add('hidden');
    text.classList.remove('hidden');
    text.textContent = initials(p.nama_lengkap);
    img.style.cursor = 'default';
    img.onclick = null;
  }

  const map = [
    'nama_lengkap','email','jenis_kelamin','agama','tempat_lahir','tanggal_lahir','hobi','cita_cita',
    'alamat','desa','kecamatan','kota','provinsi',
    'nohp_siswa','nohp_ortu',
    'nama_ayah','nik_ayah','nama_ibu','nik_ibu','nama_wali','nik_wali'
  ];

  map.forEach(k => {
    const el = document.getElementById('v_' + k);
    if(!el) return;

    if(k === 'tanggal_lahir'){
      el.innerText = safe(formatTanggalIndo(p[k]));
    }else{
      el.innerText = safe(p[k]);
    }
  });
}

function fillForm(p){
  const ids = [
    'nama_lengkap','email','jenis_kelamin','agama','tempat_lahir','tanggal_lahir','hobi','cita_cita',
    'alamat','desa','kecamatan','kota','provinsi',
    'nohp_siswa','nohp_ortu',
    'nama_ayah','nik_ayah','nama_ibu','nik_ibu','nama_wali','nik_wali'
  ];

  ids.forEach(id => {
    const el = document.getElementById(id);
    if(!el) return;
    el.value = p[id] ?? '';
  });
}

async function loadProfil(){
  try{
    const d = await fetchJson(API + '/profil.php');

    if(!d.success){
      throw new Error(d.message || 'Gagal memuat profil');
    }

    currentProfile = d.data;
    fillView(currentProfile);

    if(!editOpen){
      fillForm(currentProfile);
    }

    setStatus('Profil dimuat');
  }catch(e){
    console.error(e);
    setStatus('Gagal memuat: ' + e.message);
  }
}

/* =========================
   EDIT DATA
========================= */
document.getElementById('btnEdit').addEventListener('click', () => {
  if(currentProfile) fillForm(currentProfile);
  setMode(true);
  setStatus('Mode edit aktif');
});

document.getElementById('btnCancel').addEventListener('click', () => {
  setMode(false);
  setStatus('Batal edit');
});

document.getElementById('btnSave').addEventListener('click', async () => {
  const form = document.getElementById('editMode');
  const fd = new FormData(form);

  try{
    setStatus('Menyimpan...');

    const d = await fetchJson(API + '/profil_update.php', {
      method:'POST',
      headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
      body: new URLSearchParams(fd)
    });

    if(!d.success){
      throw new Error(d.message || 'Gagal menyimpan');
    }

    setStatus('Tersimpan ✅');
    setMode(false);
    await loadProfil();
  }catch(err){
    console.error(err);
    alert(err.message);
    setStatus('Gagal menyimpan');
  }
});

/* =========================
   UBAH PIN
========================= */
const pinModal = document.getElementById('pinModal');
const btnPin = document.getElementById('btnPin');
const pinClose = document.getElementById('pinClose');
const pinForm = document.getElementById('pinForm');
const emoneyCsrf = <?= json_encode($_SESSION['emoney_csrf'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const pinMsg = document.getElementById('pinMsg');

function openPin(){
  pinMsg.textContent = '-';
  pinForm.reset();
  pinModal.classList.remove('hidden');
}

function closePin(){
  pinModal.classList.add('hidden');
}

btnPin?.addEventListener('click', openPin);
pinClose?.addEventListener('click', closePin);

pinModal?.addEventListener('click', (e) => {
  if(e.target === pinModal) closePin();
});

pinForm?.addEventListener('submit', async (e) => {
  e.preventDefault();
  pinMsg.textContent = 'Menyimpan...';

  const fd = new FormData(pinForm);
  fd.set('csrf', emoneyCsrf);

  try{
    const res = await fetch(API + '/pin_update.php', {
      method:'POST',
      credentials:'include',
      headers:{ 'Content-Type':'application/x-www-form-urlencoded' },
      body: new URLSearchParams(fd)
    });

    const txt = await res.text();
    let j;

    try{
      j = JSON.parse(txt);
    }catch(_){
      throw new Error('Respon bukan JSON: ' + txt.slice(0,120));
    }

    if(!res.ok || !j.success){
      throw new Error(j.message || ('HTTP ' + res.status));
    }

    pinMsg.textContent = 'PIN berhasil diubah ✅';
    setTimeout(() => { closePin(); }, 900);

  }catch(err){
    pinMsg.textContent = 'Gagal: ' + err.message;
  }
});

/* =========================
   ZOOM FOTO
========================= */
const photoModal   = document.getElementById('photoModal');
const photoPreview = document.getElementById('photoPreview');
const photoClose   = document.getElementById('photoClose');
const photoCaption = document.getElementById('photoCaption');

function openPhotoModal(url, caption){
  if(!url) return;
  photoPreview.src = url;
  photoCaption.textContent = caption || 'Foto Siswa';
  photoModal.classList.add('show');
  photoModal.setAttribute('aria-hidden','false');
}

function closePhotoModal(){
  photoModal.classList.remove('show');
  photoModal.setAttribute('aria-hidden','true');
  photoPreview.src = '';
}

photoClose?.addEventListener('click', closePhotoModal);
photoModal?.addEventListener('click', (e) => {
  if(e.target === photoModal) closePhotoModal();
});

document.addEventListener('keydown', (e) => {
  if(e.key === 'Escape') closePhotoModal();
});

/* =========================
   LOAD AWAL
========================= */
loadProfil();
setInterval(() => {
  if(!editOpen) loadProfil();
}, 15000);
</script>

<script>
(function(){
  function hardRefreshIfNeeded(e){
    if (e && e.persisted) {
      location.reload();
      return;
    }
  }

  window.addEventListener('pageshow', hardRefreshIfNeeded);

  document.addEventListener('visibilitychange', function(){
    if (!document.hidden) {
      if (typeof loadSaldo === 'function') loadSaldo();
      if (typeof loadRiwayat === 'function') loadRiwayat();
      if (typeof loadPengumuman === 'function') loadPengumuman();
      if (typeof loadPerpus === 'function') loadPerpus();
      if (typeof loadAbsensi === 'function') {
        loadAbsensi(
          (document.getElementById('tglAwal')?.value || ''),
          (document.getElementById('tglAkhir')?.value || '')
        );
      }

      if (typeof loadProfil === 'function') loadProfil();
    }
  });

  window.addEventListener('focus', function(){
    if (typeof loadProfil === 'function') loadProfil();
    if (typeof loadSaldo === 'function') loadSaldo();
  });
})();
</script>

</body>
</html>
