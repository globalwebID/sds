<?php
session_start();
require_once __DIR__.'/_central_control.php';
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (!isset($_SESSION['login'])) { header('Location: ../login.php'); exit; }
$nama = $_SESSION['nama'] ?? 'Siswa';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sarpras</title>

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
.hero{
  margin: 12px;
  padding: 16px;
  border-radius: 18px;
  color: #fff;
  background: linear-gradient(135deg,#111827,#e11d48,#9ca34c);
  box-shadow: 0 14px 45px rgba(0,0,0,.18);
  position: relative;
  overflow: hidden;
}
.hero h2{ margin:0 0 6px; font-size:18px; }
.hero p{ margin:0; opacity:.92; font-size:13px; line-height:1.55; }
.hero .tag{
  display:inline-flex; gap:8px; align-items:center;
  margin-top:10px;
  background: rgba(255,255,255,.18);
  border: 1px solid rgba(255,255,255,.22);
  padding: 8px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 800;
}

.panel{
  background:#fff;
  margin: 12px;
  padding: 14px;
  border-radius: 16px;
  box-shadow: 0 6px 18px rgba(0,0,0,.08);
}
.panel h3{
  margin:0 0 10px;
  font-size: 15px;
  display:flex; gap:8px; align-items:center;
}
.list{
  display:grid;
  gap:10px;
}
.item{
  border:1px solid #f3f4f6;
  border-radius: 14px;
  padding: 12px;
  display:flex;
  gap:12px;
  align-items:flex-start;
}
.item i{
  font-size: 18px;
  margin-top:2px;
  color:#e11d48;
}
.item strong{ display:block; font-size: 13px; }
.item small{ display:block; color:#6b7280; margin-top:4px; line-height:1.45; }

.actions{
  display:grid;
  gap:10px;
  margin: 12px;
}
.btnx{
  border:0;
  border-radius: 14px;
  padding: 12px 14px;
  font-weight: 900;
  cursor: pointer;
}
.btn-primary{ background:#e11d48; color:#fff; }
.btn-outline{ background:#fff; color:#111827; border:1px solid #e5e7eb; }
.note{
  margin: 0 12px 80px;
  font-size: 12px;
  color:#9ca3af;
}
</style>
</head>

<body>

<header class="top">
  <h1>Sarpras</h1>
  <small>Halo, <?= htmlspecialchars($nama) ?> 👋</small>
</header>

<div class="hero">
  <h2><i class="fa-solid fa-screwdriver-wrench"></i> Fitur Sarpras Sedang Dikembangkan</h2>
  <p>
    Modul Sarpras akan digunakan untuk <b>peminjaman & pengembalian</b> sarana/prasarana sekolah
    secara tertib, transparan, dan tercatat.
  </p>
  <div class="tag"><i class="fa-solid fa-circle-info"></i> Status: Coming Soon</div>
</div>

<div class="panel">
  <h3><i class="fa-solid fa-list-check"></i> Rencana Fitur yang Akan Hadir</h3>
  <div class="list">
    <div class="item">
      <i class="fa-solid fa-clipboard-list"></i>
      <div>
        <strong>Ajukan Peminjaman</strong>
        <small>Pilih barang, tanggal, durasi, tujuan penggunaan, lalu ajukan.</small>
      </div>
    </div>
    <div class="item">
      <i class="fa-solid fa-qrcode"></i>
      <div>
        <strong>QR / Kode Bukti Peminjaman</strong>
        <small>Validasi cepat saat pengambilan & pengembalian barang.</small>
      </div>
    </div>
    <div class="item">
      <i class="fa-solid fa-clock-rotate-left"></i>
      <div>
        <strong>Riwayat Peminjaman</strong>
        <small>Lihat daftar peminjaman aktif & riwayat lengkap termasuk statusnya.</small>
      </div>
    </div>
    <div class="item">
      <i class="fa-solid fa-bell"></i>
      <div>
        <strong>Notifikasi & Pengingat</strong>
        <small>Pengingat batas waktu, denda/konsekuensi, dan update persetujuan.</small>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <h3><i class="fa-solid fa-circle-question"></i> Butuh Peminjaman Sekarang?</h3>
  <div class="list">
    <div class="item">
      <i class="fa-solid fa-person-chalkboard"></i>
      <div>
        <strong>Hubungi Petugas Sarpras</strong>
        <small>Silakan ajukan peminjaman melalui prosedur sekolah (manual) sementara modul ini disiapkan.</small>
      </div>
    </div>
    <div class="item">
      <i class="fa-solid fa-file-signature"></i>
      <div>
        <strong>Siapkan Data</strong>
        <small>Nama peminjam, kelas, barang, tanggal pinjam/kembali, dan tujuan penggunaan.</small>
      </div>
    </div>
  </div>
</div>

<div class="actions">
  <!--<button class="btnx btn-primary" onclick="location.href='index.php'">-->
  <!--  <i class="fa-solid fa-house"></i> Kembali ke Dashboard-->
  <!--</button>-->

  <!-- Ubah nomor WA/admin sesuai kebutuhan -->
  <a class="btnx btn-primary" style="text-decoration:none; display:block; text-align:center;"
     href="https://wa.me/628970560041?text=Halo%20Admin%20Sarpras,%20saya%20ingin%20meminjam%20sarpras.%20Nama:%20<?= urlencode($nama) ?>%20Kelas:%20...%20Barang:%20...%20Tanggal:%20...">
    <i class="fa-brands fa-whatsapp"></i> Hubungi Admin Sarpras
  </a>
</div>

<p class="note">
  Catatan: Halaman ini hanya informasi sementara. Setelah sistem admin Sarpras aktif, menu ini akan berubah menjadi layanan peminjaman sarpras lengkap.
</p>

<!-- Bottom Nav -->
<nav class="bottom-nav">
  <a href="index.php"><i class="fa-solid fa-house"></i>Home</a>
  <a href="emoney.php"><i class="fa-solid fa-wallet"></i>e-Money</a>
  <a href="perpustakaan.php"><i class="fa-solid fa-book"></i>Buku</a>
  <a href="absensi.php"><i class="fa-solid fa-calendar-check"></i>Absen</a>
  <a href="profil.php"><i class="fa-solid fa-user"></i>Profil</a>
</nav>
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
