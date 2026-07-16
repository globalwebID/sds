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
<title>Humas</title>

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
/* === Copy style Sarpras (biar konsisten) === */
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
.list{ display:grid; gap:10px; }
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

/* badge kecil opsional */
.badge-mini{
  display:inline-block;
  padding:4px 8px;
  border-radius:999px;
  font-size:11px;
  font-weight:900;
  background:#f3f4f6;
  color:#111827;
  margin-left:8px;
}
</style>
</head>

<body>

<header class="top">
  <h1>Humas</h1>
  <small>Halo, <?= htmlspecialchars($nama) ?> 👋</small>
</header>

<div class="hero">
  <h2><i class="fa-solid fa-bullhorn"></i> Fitur Humas Sedang Dikembangkan</h2>
  <p>
    Modul Humas akan menjadi pusat <b>informasi sekolah</b>, <b>pengaduan/aspirasi</b>,
    serta layanan <b>PKL/Prakerin</b> (data, monitoring, dan administrasi).
  </p>
  <div class="tag"><i class="fa-solid fa-circle-info"></i> Status: Coming Soon</div>
</div>

<div class="panel">
  <h3><i class="fa-solid fa-list-check"></i> Rencana Fitur Humas</h3>
  <div class="list">
    <div class="item">
      <i class="fa-solid fa-newspaper"></i>
      <div>
        <strong>Pengumuman & Berita Sekolah <span class="badge-mini">Soon</span></strong>
        <small>Daftar pengumuman, kategori, lampiran, dan arsip pencarian.</small>
      </div>
    </div>

    <div class="item">
      <i class="fa-solid fa-calendar-days"></i>
      <div>
        <strong>Agenda Kegiatan <span class="badge-mini">Soon</span></strong>
        <small>Kalender kegiatan sekolah, event, rapat, dan pengingat.</small>
      </div>
    </div>

    <div class="item">
      <i class="fa-solid fa-comments"></i>
      <div>
        <strong>Aspirasi & Pengaduan <span class="badge-mini">Soon</span></strong>
        <small>Kirim aspirasi/pengaduan dengan bukti foto, status tindak lanjut, dan notifikasi.</small>
      </div>
    </div>

    <div class="item">
      <i class="fa-solid fa-envelope-open-text"></i>
      <div>
        <strong>Surat Menyurat Digital <span class="badge-mini">Soon</span></strong>
        <small>Permohonan surat, tracking, dan pengambilan dokumen dengan QR.</small>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <h3><i class="fa-solid fa-briefcase"></i> Layanan PKL/Prakerin (Bagian Humas)</h3>
  <div class="list">
    <div class="item">
      <i class="fa-solid fa-building"></i>
      <div>
        <strong>Data DU/DI Mitra <span class="badge-mini">Soon</span></strong>
        <small>Daftar tempat PKL, kuota, alamat, kontak, dan kebutuhan kompetensi.</small>
      </div>
    </div>

    <div class="item">
      <i class="fa-solid fa-file-signature"></i>
      <div>
        <strong>Pengajuan & Penempatan PKL <span class="badge-mini">Soon</span></strong>
        <small>Pengajuan tempat, persetujuan, penempatan, dan dokumen administrasi.</small>
      </div>
    </div>

    <div class="item">
      <i class="fa-solid fa-location-dot"></i>
      <div>
        <strong>Monitoring PKL <span class="badge-mini">Soon</span></strong>
        <small>Log kegiatan harian, absensi PKL, kunjungan pembimbing, dan catatan evaluasi.</small>
      </div>
    </div>

    <div class="item">
      <i class="fa-solid fa-award"></i>
      <div>
        <strong>Nilai & Sertifikat PKL <span class="badge-mini">Soon</span></strong>
        <small>Input penilaian DU/DI, rekap nilai, unduh sertifikat / surat keterangan.</small>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <h3><i class="fa-solid fa-circle-question"></i> Butuh Bantuan Sekarang?</h3>
  <div class="list">
    <div class="item">
      <i class="fa-brands fa-whatsapp"></i>
      <div>
        <strong>Hubungi Admin Humas</strong>
        <small>Untuk pertanyaan/pengaduan/PKL saat ini, silakan hubungi admin Humas via WhatsApp.</small>
      </div>
    </div>
    <div class="item">
      <i class="fa-solid fa-file-lines"></i>
      <div>
        <strong>Siapkan Data</strong>
        <small>Nama, kelas, keperluan, detail masalah/permintaan, dan bukti pendukung (jika ada).</small>
      </div>
    </div>
  </div>
</div>

<div class="actions">
  <!--<button class="btnx btn-primary" onclick="location.href='index.php'">-->
  <!--  <i class="fa-solid fa-house"></i> Kembali ke Dashboard-->
  <!--</button>-->

  <!-- Ganti nomor WA admin humas sesuai kebutuhan -->
  <a class="btnx btn-primary" style="text-decoration:none; display:block; text-align:center;"
     href="https://wa.me/6285211868811?text=Halo%20Admin%20Humas,%20saya%20butuh%20bantuan.%0ANama:%20<?= urlencode($nama) ?>%0AKelas:%20...%0AKeperluan:%20(Humas/PKL/Pengaduan)%0ADetail:%20...">
    <i class="fa-brands fa-whatsapp"></i> Hubungi Admin Humas
  </a>
</div>

<p class="note">
  Catatan: Halaman ini adalah informasi sementara. Setelah modul Humas & PKL aktif, menu ini akan berisi layanan lengkap (pengumuman, pengaduan, agenda, dan PKL).
</p>

<!-- Bottom Nav (tetap 5 item seperti yang sekarang) -->
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
