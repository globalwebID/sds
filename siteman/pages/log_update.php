<?php
declare(strict_types=1);
$updates = [
 ['date'=>'15 Juli 2026','version'=>'Build 2026.07.15','title'=>'Pengaturan Sistem & Kesiapan Produksi','type'=>'Sistem','items'=>[
  'Pusat pengaturan identitas sekolah, branding dokumen, kartu pelajar, integrasi, profil administrator, keamanan, audit, dan kesehatan sistem.',
  'Identitas dan logo sekolah dipusatkan agar dapat digunakan konsisten oleh SDS, Absensi, dan modul terkait.',
  'Pemeriksaan integrasi database, format impor-ekspor Excel, dokumen PDF, foto siswa, dan pembersihan source yang tidak diperlukan.',
  'Penyempurnaan tampilan Pengaturan Sistem, sidebar tetap, navigasi sticky, serta perbaikan struktur footer.'
 ]],
 ['date'=>'14 Juli 2026','version'=>'Perpustakaan v2.6','title'=>'Laporan, Audit & Layanan Anggota','type'=>'Perpustakaan','items'=>[
  'Laporan operasional perpustakaan, audit aktivitas, saran koleksi, reservasi, notifikasi, dan kiosk layanan mandiri.',
  'Login mandiri perpustakaan dengan pengelolaan admin dan staf, tetap terintegrasi dalam satu database SDS.',
  'Superadmin SDS dapat masuk langsung ke dashboard perpustakaan melalui menu aplikasi tanpa mekanisme SSO eksternal.',
  'Penyempurnaan antarmuka AdminLTE perpustakaan, login, dashboard, OPAC, dan layanan anggota berbasis RFID.'
 ]],
 ['date'=>'14 Juli 2026','version'=>'Perpustakaan v2.4','title'=>'Data Massal, Excel & OPAC Publik','type'=>'Perpustakaan','items'=>[
  'Impor massal bibliografi dan eksemplar dengan validasi, riwayat hasil, serta pencegahan duplikasi barcode.',
  'Ekspor Excel anggota, koleksi, eksemplar, pinjaman, keterlambatan, denda, kunjungan, dan data RFID.',
  'OPAC publik dengan pencarian, filter, detail koleksi, status ketersediaan, dan koleksi populer.',
  'Perbaikan pencarian anggota untuk mencegah konflik collation pada database.'
 ]],
 ['date'=>'14 Juli 2026','version'=>'Build 2026.07.14','title'=>'Kantin & E-Money Mandiri','type'=>'Aplikasi','items'=>[
  'Penguatan keamanan PIN E-Money dengan penyimpanan hash dan kompatibilitas migrasi data.',
  'Idempotensi transaksi kantin untuk mencegah transaksi ganda saat permintaan dikirim ulang.',
  'Penyelarasan akses aplikasi Absensi, Kantin, dan Perpustakaan dari dashboard SDS.'
 ]],
 ['date'=>'13 Juli 2026','version'=>'Build 2026.07.13','title'=>'Integrasi Database & Akses Aplikasi','type'=>'Platform','items'=>[
  'Penyatuan database SDS, Absensi, Kantin, E-Money, dan Perpustakaan pada database utama.',
  'Pusat akun dan hak akses aplikasi untuk administrator SDS.',
  'Proyeksi data induk siswa, pegawai, RFID, rombel, dan tahun ajaran agar konsisten antar modul.',
  'Migrasi Absensi ke struktur database terpadu dan penghapusan ketergantungan database terpisah.'
 ]],
 ['date'=>'26 Mei 2025','version'=>'Pembaruan Mei 2025','title'=>'Formulir, Status & Administrasi','type'=>'SDS','items'=>[
  'Toggle status formulir aktif/nonaktif pada dashboard dan penanda input Dapodik siswa.',
  'Status siswa aktif/nonaktif beserta modul penonaktifan siswa.',
  'Tautan Google Maps untuk koordinat rumah serta WhatsApp untuk nomor siswa dan orang tua.',
  'Pengaturan logo, kop lembaga, profil administrator, dan Log Aktivitas.',
  'Perbaikan ekspor PDF dan Excel.'
 ]],
 ['date'=>'23 Mei 2025','version'=>'Pembaruan Mei 2025','title'=>'Biodata & Dokumen Siswa','type'=>'SDS','items'=>[
  'Fitur edit data siswa, status Dapodik, status siswa, dan salin data pada detail siswa.',
  'Perbaikan tampilan edit siswa serta nama dan lokasi berkas unggahan.',
  'Penyelarasan unggahan formulir siswa dan penambahan kop lembaga pada PDF data siswa.'
 ]]
];
?>
<style>
.sds-update-page{color:#334151}.sds-update-page .page-head{background:#fff;border:1px solid #dee2e6;padding:1rem;display:flex;justify-content:space-between;align-items:flex-start;gap:1rem}.sds-update-page .page-head h2{font-size:1.25rem;margin:0 0 .25rem;font-weight:600}.sds-update-page .page-head p{margin:0;color:#6c757d;font-size:.875rem}.sds-update-page .summary{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))}.sds-update-page .summary>div{background:#fff;border:1px solid #dee2e6;border-top:0;padding:1rem}.sds-update-page .summary small{display:block;color:#6c757d;text-transform:uppercase;font-size:.7rem;font-weight:700;letter-spacing:.04em}.sds-update-page .summary strong{display:block;margin-top:.2rem;font-size:1rem}.sds-update-page .timeline{position:relative;margin:1rem 0 0 13px;padding-left:27px;border-left:2px solid #ced4da}.sds-update-page .release{position:relative;background:#fff;border:1px solid #dee2e6;margin-bottom:1rem}.sds-update-page .release:before{content:'';position:absolute;width:12px;height:12px;border-radius:50%;background:#0d6efd;border:3px solid #fff;box-shadow:0 0 0 2px #0d6efd;left:-34px;top:20px}.sds-update-page .release-head{padding:.85rem 1rem;background:#f8f9fa;border-bottom:1px solid #dee2e6;display:flex;justify-content:space-between;align-items:center;gap:1rem}.sds-update-page .release-head h3{font-size:1rem;margin:0;font-weight:600}.sds-update-page .release-meta{font-size:.78rem;color:#6c757d;margin-top:.25rem}.sds-update-page .release-type{font-size:.72rem;font-weight:700;color:#0b5ed7;background:#e7f1ff;border:1px solid #cfe2ff;padding:.25rem .45rem;border-radius:.2rem;white-space:nowrap}.sds-update-page .release-body{padding:.9rem 1rem}.sds-update-page .release-body ul{margin:0;padding-left:1.2rem}.sds-update-page .release-body li{margin:.35rem 0;line-height:1.5}.sds-update-page .release-body li::marker{color:#0d6efd}
@media(max-width:767.98px){.sds-update-page .summary{grid-template-columns:1fr}.sds-update-page .release-head{align-items:flex-start}.sds-update-page .page-head{display:block}.sds-update-page .page-head .btn{margin-top:.75rem}}
</style>

<div class="sds-update-page">
 <div class="page-head"><div><h2>Pembaruan Sistem</h2><p>Riwayat pengembangan, integrasi, keamanan, dan penyempurnaan aplikasi SDS.</p></div><a href="log_aktivitas" class="btn btn-outline-primary btn-sm"><i data-feather="activity" class="me-1"></i> Log Aktivitas</a></div>
 <div class="summary"><div><small>Build Terbaru</small><strong>15 Juli 2026</strong></div><div><small>Database</small><strong>Terintegrasi</strong></div><div><small>Modul Utama</small><strong>SDS · Absensi · Kantin · Perpustakaan</strong></div></div>
 <div class="timeline">
 <?php foreach($updates as $update):?><article class="release"><div class="release-head"><div><h3><?=htmlspecialchars($update['title'],ENT_QUOTES,'UTF-8')?></h3><div class="release-meta"><?=htmlspecialchars($update['date'].' · '.$update['version'],ENT_QUOTES,'UTF-8')?></div></div><span class="release-type"><?=htmlspecialchars($update['type'],ENT_QUOTES,'UTF-8')?></span></div><div class="release-body"><ul><?php foreach($update['items'] as $item):?><li><?=htmlspecialchars($item,ENT_QUOTES,'UTF-8')?></li><?php endforeach;?></ul></div></article><?php endforeach;?>
 </div>
</div>
