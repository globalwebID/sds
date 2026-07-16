<?php

declare(strict_types=1);
$settings = $conn->query('SELECT * FROM pengaturan ORDER BY id LIMIT 1')->fetch_assoc() ?: [];
$adminId = (int)($_SESSION['admin_id'] ?? 0);
$stmt = $conn->prepare('SELECT id,username,full_name,email,role FROM admins WHERE id=? LIMIT 1');
$stmt->bind_param('i', $adminId);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();
$legacy = $conn->query('SELECT gmail_active,google_client_active,whatsapp_active FROM setting ORDER BY site_id LIMIT 1')->fetch_assoc() ?: [];
$auditRows = [];
if ($conn->query("SHOW TABLES LIKE 'sds_pengaturan_audit'")->num_rows) {
   $r = $conn->query("SELECT a.*,COALESCE(ad.full_name,ad.username,'-') admin_nama FROM sds_pengaturan_audit a LEFT JOIN admins ad ON ad.id=a.admin_id ORDER BY a.id DESC LIMIT 10");
   while ($r && ($x = $r->fetch_assoc())) $auditRows[] = $x;
}
$backupFiles = glob(dirname(__DIR__, 2) . '/storage/backups/*.{sql,zip}', GLOB_BRACE) ?: [];
usort($backupFiles, static fn($a, $b) => filemtime($b) <=> filemtime($a));
$projectRoot = dirname(__DIR__, 2);
$storageWritable = is_writable($projectRoot . '/storage');
$uploadsWritable = is_writable($projectRoot . '/uploads');
$freeDisk = @disk_free_space($projectRoot);
$migrationCount = count(glob($projectRoot . '/install/migrations/*.sql') ?: []);
$activeSessions = (int)($conn->query("SELECT COUNT(*) total FROM sds_admin_sessions WHERE last_activity>=DATE_SUB(NOW(),INTERVAL 30 MINUTE)")?->fetch_assoc()['total'] ?? 0);
$versionFile = is_file($projectRoot . '/VERSION.txt') ? trim((string)file_get_contents($projectRoot . '/VERSION.txt')) : 'Build lokal';
$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$asset = static function (array $s, string $field): string {
   $n = basename((string)($s[$field] ?? ''));
   return $n !== '' ? '../uploads/logo/' . rawurlencode($n) : '';
};
$csrf = $h(sds_csrf_token());
?>
<script>
   document.documentElement.classList.add('sds-settings-page-open');
</script>
<?php include __DIR__ . '/partials/shared/master_page_style.php'; ?>
<style>
   /* Template induk memakai overflow:hidden pada .main. Khusus halaman ini overflow
   harus visible agar sticky mengikuti scroll dokumen seperti navigasi Data Induk. */
   html.sds-settings-page-open .main,
   html.sds-settings-page-open main.content {
      overflow: visible !important
   }

   .settings-page {
      --set-primary: #0d6efd;
      --set-border: #dee2e6;
      --set-muted: #6c757d;
      color: #334151
   }

   .settings-head-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
      margin-top: 9px
   }

   .settings-chip {
      font-size: .75rem;
      border: 1px solid #dee2e6;
      background: #f8f9fa;
      padding: 3px 7px;
      border-radius: .2rem;
      color: #495057
   }

   .settings-page>.row {
      align-items: stretch
   }

   .settings-page>.row>.col-lg-3 {
      align-self: stretch
   }

   .settings-nav {
      position: -webkit-sticky !important;
      position: sticky !important;
      top: 72px;
      z-index: 20;
      height: max-content
   }

   .settings-page .nav-link {
      display: flex;
      align-items: center;
      gap: 10px;
      border-radius: 3px;
      color: #374151;
      padding: 11px 12px;
      font-size: .9rem;
      border-left: 3px solid transparent
   }

   .settings-page .nav-link svg {
      width: 17px;
      height: 17px;
      color: #64748b
   }

   .settings-page .nav-link:hover {
      background: #f1f5f9
   }

   .settings-page .nav-link.active {
      background: #eaf2ff;
      color: #1d4ed8;
      border-left-color: var(--set-primary);
      font-weight: 600
   }

   .settings-page .nav-link.active svg {
      color: #1d4ed8
   }

   .settings-page .card {
      border-radius: 0;
      border: 1px solid var(--set-border);
      box-shadow: none;
      margin-bottom: 0
   }

   .settings-page .card-header {
      background: #f8f9fa;
      border-bottom: 1px solid var(--set-border);
      padding: .9rem 1rem;
      min-height: 52px;
      align-items: center
   }

   .settings-page .card-header strong {
      font-size: 1rem;
      font-weight: 600
   }

   .settings-page .card-body {
      padding: 1rem
   }

   .settings-page .form-label {
      font-size: .82rem;
      font-weight: 600;
      color: #495057;
      margin-bottom: 5px
   }

   .settings-page .form-control,
   .settings-page .form-select {
      border-radius: .2rem;
      border-color: #ced4da;
      min-height: 36px
   }

   .settings-page .form-control:focus,
   .settings-page .form-select:focus {
      border-color: #86b7fe;
      box-shadow: 0 0 0 .15rem rgba(13, 110, 253, .12)
   }

   .settings-page .preview {
      border: 1px dashed #ced4da;
      background: #f8f9fa;
      min-height: 128px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 12px;
      border-radius: 0;
      overflow: hidden
   }

   .settings-page .preview img {
      max-width: 100%;
      max-height: 112px;
      object-fit: contain
   }

   .settings-page .dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      display: inline-block;
      margin-right: 6px
   }

   .settings-page .on {
      background: #198754
   }

   .settings-page .off {
      background: #adb5bd
   }

   .settings-page .system-metric {
      height: 100%;
      border: 1px solid var(--set-border);
      border-top: 3px solid #6c757d;
      background: #fff;
      padding: 1rem
   }

   .settings-page .system-metric small {
      display: block;
      color: var(--set-muted);
      text-transform: uppercase;
      font-size: .7rem;
      font-weight: 700;
      letter-spacing: .04em
   }

   .settings-page .table {
      margin-bottom: 0
   }

   .settings-page .table thead th {
      font-size: .72rem;
      text-transform: uppercase;
      letter-spacing: .04em;
      color: #6c757d;
      background: #f8f9fa;
      border-bottom-width: 1px
   }

   .settings-page .table td {
      vertical-align: middle
   }

   .settings-page .btn {
      border-radius: .2rem
   }

   .settings-page .text-muted {
      color: var(--set-muted) !important
   }

   .settings-page>.alert {
      margin: 1rem 0 0
   }

   .settings-nav>.alert {
      margin: 1rem 0 0
   }

   .settings-page .tab-content>.tab-pane>.card>.card-body>.alert {
      margin: 0 0 1rem
   }

   @media(max-width:991.98px) {
      .settings-nav {
         top: 64px;
         background: #f5f7fb;
         padding: 4px 0;
         z-index: 1025
      }

      .settings-nav>.card {
         margin-bottom: 0
      }

      .settings-nav>.alert {
         display: none
      }

      .settings-page .nav-pills {
         display: flex !important;
         flex-flow: row nowrap;
         overflow-x: auto;
         gap: 4px;
         scrollbar-width: thin
      }

      .settings-page .nav-link {
         flex: 0 0 auto;
         border-left: 0;
         border-bottom: 2px solid transparent;
         padding: 9px 12px;
         white-space: nowrap
      }

      .settings-page .nav-link.active {
         border-left: 0;
         border-bottom-color: var(--set-primary)
      }
   }

   @media(max-width:575.98px) {
      .settings-page .nav-link {
         padding: 8px 10px;
         font-size: .82rem
      }

      .settings-page .card-header {
         align-items: flex-start !important;
         gap: 10px
      }

      .settings-page .card-header .btn {
         white-space: nowrap
      }

      .settings-page .card-body {
         padding: 14px
      }
   }
</style>
<div class="sds-master-page settings-page">
   <div class="sds-hero">
      <div>
         <h2>Pengaturan Sistem</h2>
         <p>Kelola identitas sekolah, dokumen, keamanan, dan status sistem dari satu tempat.</p>
         <div class="settings-head-meta"><span class="settings-chip"><i data-feather="home" class="me-1"></i><?= $h($settings['nama_sekolah'] ?? 'Sekolah') ?></span><span class="settings-chip">NPSN: <?= $h($settings['npsn'] ?: 'Belum diisi') ?></span><span class="settings-chip">Tahun Ajaran: <?= $h($tahunAjaran ?? '-') ?></span></div>
      </div>
      <div class="sds-hero-actions"><a href="tahun_ajaran" class="btn btn-outline-primary btn-sm"><i data-feather="calendar" class="me-1"></i> Tahun Ajaran</a></div>
   </div>
   <?php if (!empty($_SESSION['error'])): ?><div class="alert alert-danger alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?= $h($_SESSION['error']) ?></div><?php unset($_SESSION['error']);
                                                                                                                                                                                       endif; ?>
   <?php if (!empty($_SESSION['success'])): ?><div class="alert alert-success alert-dismissible"><button class="btn-close" data-bs-dismiss="alert"></button><?= $h($_SESSION['success']) ?></div><?php unset($_SESSION['success']);
                                                                                                                                                                                          endif; ?>
   <div class="row g-0">
      <div class="col-lg-3">
         <div class="settings-nav">
            <div class="card">
               <div class="card-body p-2">
                  <div class="nav flex-column nav-pills">
                     <?php $tabs = ['identitas' => ['home', 'Identitas Sekolah'], 'branding' => ['image', 'Branding & Dokumen'], 'kartu' => ['credit-card', 'Kartu Pelajar'], 'integrasi' => ['link', 'Integrasi & Notifikasi'], 'operasional' => ['sliders', 'Operasional & Keamanan'], 'regional' => ['globe', 'Regional'], 'admin' => ['shield', 'Profil Administrator'], 'kesehatan' => ['activity', 'Kesehatan Sistem']];
                     foreach ($tabs as $id => $tab): ?>
                        <a class="nav-link <?= $id === 'identitas' ? 'active' : '' ?>" data-bs-toggle="pill" href="#<?= $id ?>"><i data-feather="<?= $tab[0] ?>"></i><span><?= $tab[1] ?></span></a>
                     <?php endforeach; ?>
                  </div>
               </div>
            </div>
            <div class="alert alert-light border small mt-3 mb-0"><strong>Master Tingkat Kelas</strong><br><span class="text-muted">Dikelola melalui Data Induk &rarr; Rombel.</span><br><a href="kuota_kelas" class="alert-link">Buka Rombel</a></div>
         </div>
      </div>
      <div class="col-lg-9">
         <div class="tab-content">

            <div class="tab-pane fade show active" id="identitas">
               <form action="pengaturan_simpan" method="post" class="card"><input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <div class="card-header d-flex justify-content-between"><strong>Identitas Sekolah</strong><button class="btn btn-success btn-sm" name="submit_identitas">Simpan</button></div>
                  <div class="card-body">
                     <div class="row g-3">
                        <?php $fields = ['nama_sekolah' => ['Nama Sekolah', 'text', true], 'npsn' => ['NPSN', 'text', false], 'kementerian' => ['Kementerian / Dinas', 'text', false], 'telepon' => ['Telepon', 'text', false], 'email' => ['Email Sekolah', 'email', false], 'website' => ['Website', 'url', false], 'kepala_sekolah' => ['Kepala Sekolah', 'text', false], 'nip_kepala_sekolah' => ['NIP / NIY Kepala Sekolah', 'text', false], 'desa' => ['Desa / Kelurahan', 'text', false], 'kecamatan' => ['Kecamatan', 'text', false], 'kabupaten' => ['Kabupaten / Kota', 'text', false], 'provinsi' => ['Provinsi', 'text', false]];
                        foreach ($fields as $name => $meta): ?>
                           <div class="col-md-6"><label class="form-label"><?= $meta[0] ?></label><input type="<?= $meta[1] ?>" class="form-control" name="<?= $name ?>" value="<?= $h($settings[$name] ?? '') ?>" <?= $meta[2] ? 'required' : '' ?>></div>
                        <?php endforeach; ?><div class="col-12"><label class="form-label">Alamat Lengkap</label><textarea class="form-control" rows="3" name="alamat"><?= $h($settings['alamat'] ?? '') ?></textarea></div>
                     </div>
                  </div>
               </form>
            </div>

            <div class="tab-pane fade" id="branding">
               <form action="pengaturan_simpan" method="post" enctype="multipart/form-data" class="card"><input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <div class="card-header d-flex justify-content-between"><strong>Branding & Dokumen</strong><button class="btn btn-success btn-sm" name="submit_branding">Simpan Berkas</button></div>
                  <div class="card-body">
                     <div class="row g-3">
                        <?php $brands = ['logo' => 'Logo Sekolah', 'favicon' => 'Favicon', 'kop_surat' => 'Kop Surat', 'ttd_kepala_sekolah' => 'Tanda Tangan Kepala Sekolah', 'stempel' => 'Stempel Sekolah'];
                        foreach ($brands as $field => $label): $src = $asset($settings, $field); ?>
                           <div class="col-md-6"><label class="form-label"><?= $label ?></label>
                              <div class="preview mb-2"><?php if ($src): ?><img src="<?= $h($src) ?>" alt="<?= $label ?>"><?php else: ?><span class="text-muted">Belum tersedia</span><?php endif; ?></div><input type="file" class="form-control" name="<?= $field ?>" accept=".png,.jpg,.jpeg,.webp"><small class="text-muted">Maksimal 5 MB.</small>
                           </div>
                        <?php endforeach; ?>
                     </div>
                  </div>
               </form>
            </div>

            <div class="tab-pane fade" id="kartu">
               <form action="pengaturan_simpan" method="post" enctype="multipart/form-data" class="card"><input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <div class="card-header d-flex justify-content-between"><strong>Kartu Pelajar</strong><button class="btn btn-success btn-sm" name="submit_kartu">Simpan</button></div>
                  <div class="card-body">
                     <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Orientasi Default</label><select class="form-select" name="kartu_orientasi">
                              <option value="potrait" <?= ($settings['kartu_orientasi'] ?? 'potrait') === 'potrait' ? 'selected' : '' ?>>Potrait</option>
                              <option value="landscape" <?= ($settings['kartu_orientasi'] ?? '') === 'landscape' ? 'selected' : '' ?>>Landscape</option>
                           </select></div>
                        <div class="col-md-4"><label class="form-label">Lebar (mm)</label><input type="number" step="0.01" min="40" max="120" class="form-control" name="kartu_lebar_mm" value="<?= $h($settings['kartu_lebar_mm'] ?? '53.98') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Tinggi (mm)</label><input type="number" step="0.01" min="40" max="150" class="form-control" name="kartu_tinggi_mm" value="<?= $h($settings['kartu_tinggi_mm'] ?? '85.60') ?>"></div>
                        <div class="col-12"><label class="form-label">Background Sesuai Orientasi</label><input type="file" class="form-control" name="bg" accept=".jpg,.jpeg"><small class="text-muted">Potrait: <?= is_file(dirname(__DIR__, 2) . '/uploads/bg/bg_potrait.jpg') ? 'tersedia' : 'belum ada' ?> &middot; Landscape: <?= is_file(dirname(__DIR__, 2) . '/uploads/bg/bg_landscape.jpg') ? 'tersedia' : 'belum ada' ?>.</small></div>
                     </div>
                  </div>
               </form>
            </div>

            <div class="tab-pane fade" id="integrasi">
               <div class="card">
                  <div class="card-header"><strong>Integrasi & Notifikasi</strong></div>
                  <div class="card-body">
                     <p class="text-muted">Kredensial tidak ditampilkan demi keamanan.</p>
                     <div class="list-group mb-3">
                        <?php foreach (['gmail_active' => 'Email SMTP', 'google_client_active' => 'Google Login', 'whatsapp_active' => 'WhatsApp'] as $key => $label): $active = ($legacy[$key] ?? 'N') === 'Y'; ?><div class="list-group-item d-flex justify-content-between"><span><?= $label ?></span><span><i class="dot <?= $active ? 'on' : 'off' ?>"></i><?= $active ? 'Aktif' : 'Nonaktif' ?></span></div><?php endforeach; ?>
                     </div>
                     <div class="d-flex flex-wrap gap-2"><a href="application_accounts" class="btn btn-primary">Akun & Akses Aplikasi</a>
                        <form action="pengaturan_simpan" method="post"><input type="hidden" name="csrf" value="<?= $csrf ?>"><button class="btn btn-outline-primary" name="submit_test_integrasi"><i data-feather="check-circle" class="me-1"></i> Periksa Konfigurasi</button></form>
                     </div><small class="text-muted d-block mt-2">Pemeriksaan tidak mengirim pesan atau membuka kredensial.</small>
                  </div>
               </div>
            </div>

            <div class="tab-pane fade" id="operasional">
               <form action="pengaturan_simpan" method="post" class="card"><input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <div class="card-header d-flex justify-content-between"><strong>Operasional & Keamanan</strong><button class="btn btn-success btn-sm" name="submit_operasional">Simpan</button></div>
                  <div class="card-body">
                     <div class="row g-3">
                        <div class="col-12">
                           <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="maintenance_mode">Aktifkan mode pemeliharaan</label></div><small class="text-muted">Superadmin tetap dapat masuk untuk melakukan perbaikan.</small>
                        </div>
                        <div class="col-12"><label class="form-label">Pesan Pemeliharaan</label><textarea class="form-control" rows="2" name="maintenance_message" maxlength="500"><?= $h($settings['maintenance_message'] ?? '') ?></textarea></div>
                        <div class="col-md-4"><label class="form-label">Jadwal Backup</label><select class="form-select" name="backup_schedule"><?php foreach (['disabled' => 'Nonaktif', 'daily' => 'Setiap Hari', 'weekly' => 'Setiap Minggu'] as $value => $label): ?><option value="<?= $value ?>" <?= ($settings['backup_schedule'] ?? 'disabled') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label">Retensi Backup (hari)</label><input type="number" min="7" max="365" class="form-control" name="backup_retention_days" value="<?= (int)($settings['backup_retention_days'] ?? 30) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Batas Percobaan Login</label><input type="number" min="3" max="20" class="form-control" name="login_max_attempts" value="<?= (int)($settings['login_max_attempts'] ?? 5) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Jendela Login (menit)</label><input type="number" min="1" max="60" class="form-control" name="login_window_minutes" value="<?= (int)($settings['login_window_minutes'] ?? 5) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Batas Sesi Admin (menit)</label><input type="number" min="10" max="1440" class="form-control" name="admin_session_minutes" value="<?= (int)($settings['admin_session_minutes'] ?? 30) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Masa Password (hari)</label><input type="number" min="0" max="365" class="form-control" name="password_expiry_days" value="<?= (int)($settings['password_expiry_days'] ?? 0) ?>"><small class="text-muted">Isi 0 untuk menonaktifkan kedaluwarsa.</small></div>
                        <div class="col-12">
                           <div class="alert alert-light border small mb-0">Runner backup aman tersedia di <code>tools/scheduled-maintenance.php</code> dan dapat dijadwalkan melalui Windows Task Scheduler.</div>
                        </div>
                     </div>
                  </div>
               </form>
            </div>

            <div class="tab-pane fade" id="regional">
               <form action="pengaturan_simpan" method="post" class="card"><input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <div class="card-header d-flex justify-content-between"><strong>Preferensi Regional</strong><button class="btn btn-success btn-sm" name="submit_regional">Simpan</button></div>
                  <div class="card-body">
                     <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Zona Waktu</label><select class="form-select" name="system_timezone"><?php foreach (['Asia/Jakarta' => 'WIB · Asia/Jakarta', 'Asia/Makassar' => 'WITA · Asia/Makassar', 'Asia/Jayapura' => 'WIT · Asia/Jayapura'] as $value => $label): ?><option value="<?= $value ?>" <?= ($settings['system_timezone'] ?? 'Asia/Jakarta') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label">Format Tanggal</label><select class="form-select" name="date_format"><?php foreach (['d/m/Y' => '31/12/2026', 'd-m-Y' => '31-12-2026', 'Y-m-d' => '2026-12-31'] as $value => $label): ?><option value="<?= $value ?>" <?= ($settings['date_format'] ?? 'd/m/Y') === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?></select></div>
                        <div class="col-md-4"><label class="form-label">Format Angka</label><select class="form-select" name="number_locale">
                              <option value="id_ID" <?= ($settings['number_locale'] ?? 'id_ID') === 'id_ID' ? 'selected' : '' ?>>Indonesia (1.234,56)</option>
                              <option value="en_US" <?= ($settings['number_locale'] ?? '') === 'en_US' ? 'selected' : '' ?>>Internasional (1,234.56)</option>
                           </select></div>
                     </div>
                  </div>
               </form>
            </div>

            <div class="tab-pane fade" id="admin">
               <form action="pengaturan_simpan" method="post" class="card" autocomplete="off"><input type="hidden" name="csrf" value="<?= $csrf ?>">
                  <div class="card-header d-flex justify-content-between"><strong>Profil & Keamanan</strong><button class="btn btn-success btn-sm" name="submit_admin">Simpan Profil</button></div>
                  <div class="card-body">
                     <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Username</label><input class="form-control" value="<?= $h($admin['username'] ?? '') ?>" disabled></div>
                        <div class="col-md-6"><label class="form-label">Role</label><input class="form-control" value="<?= $h($admin['role'] ?? '') ?>" disabled></div>
                        <div class="col-md-6"><label class="form-label">Nama Lengkap</label><input class="form-control" name="admin_nama" value="<?= $h($admin['full_name'] ?? '') ?>" required></div>
                        <div class="col-md-6"><label class="form-label">Email</label><input type="email" class="form-control" name="admin_email" value="<?= $h($admin['email'] ?? '') ?>" required></div>
                        <div class="col-12">
                           <hr><strong>Ganti Password (opsional)</strong>
                        </div>
                        <div class="col-md-4"><label class="form-label">Password Saat Ini</label><input type="password" class="form-control" name="current_password"></div>
                        <div class="col-md-4"><label class="form-label">Password Baru</label><input type="password" class="form-control" name="admin_password" minlength="10"><small class="text-muted">Minimal 10 karakter, huruf dan angka.</small></div>
                        <div class="col-md-4"><label class="form-label">Konfirmasi Password</label><input type="password" class="form-control" name="admin_password_confirmation"></div>
                     </div>
                  </div>
               </form>
            </div>

            <div class="tab-pane fade" id="kesehatan">
               <div class="card">
                  <div class="card-header"><strong>Kesehatan & Backup</strong><span class="sds-mini"><?= $h(strtok($versionFile, "\n")) ?></span></div>
                  <div class="card-body">
                     <div class="row g-3 mb-3">
                        <div class="col-md-4">
                           <div class="system-metric"><small>PHP</small>
                              <div class="h5 mb-0"><?= PHP_VERSION ?></div>
                           </div>
                        </div>
                        <div class="col-md-4">
                           <div class="system-metric"><small>Database</small>
                              <div class="h5 mb-0"><?= $h($conn->server_info) ?></div>
                           </div>
                        </div>
                        <div class="col-md-4">
                           <div class="system-metric"><small>Backup Lokal</small>
                              <div class="h5 mb-0"><?= count($backupFiles) ?> berkas</div>
                           </div>
                        </div>
                        <div class="col-md-4">
                           <div class="system-metric"><small>Storage</small>
                              <div class="h5 mb-0"><?= $storageWritable ? 'Siap' : 'Bermasalah' ?></div>
                           </div>
                        </div>
                        <div class="col-md-4">
                           <div class="system-metric"><small>Uploads</small>
                              <div class="h5 mb-0"><?= $uploadsWritable ? 'Siap' : 'Bermasalah' ?></div>
                           </div>
                        </div>
                        <div class="col-md-4">
                           <div class="system-metric"><small>Disk Tersedia</small>
                              <div class="h5 mb-0"><?= $freeDisk !== false ? number_format($freeDisk / 1073741824, 1, ',', '.') : '-' ?> GB</div>
                           </div>
                        </div>
                        <div class="col-md-4">
                           <div class="system-metric"><small>Migrasi Tersedia</small>
                              <div class="h5 mb-0"><?= $migrationCount ?> berkas</div>
                           </div>
                        </div>
                        <div class="col-md-4">
                           <div class="system-metric"><small>Sesi Admin Aktif</small>
                              <div class="h5 mb-0"><?= $activeSessions ?></div>
                           </div>
                        </div>
                        <div class="col-md-4">
                           <div class="system-metric"><small>Backup Terakhir</small>
                              <div class="h5 mb-0" style="font-size:1rem"><?= !empty($settings['last_backup_at']) ? $h(date('d/m/Y H:i', strtotime($settings['last_backup_at']))) : 'Belum ada' ?></div>
                           </div>
                        </div>
                     </div>
                     <div class="alert alert-info small">Backup dan health check dijalankan melalui folder <code>tools</code> pada server; eksekusi browser sengaja dinonaktifkan.</div>
                     <?php if ($backupFiles): ?><div class="table-responsive">
                           <table class="table table-sm">
                              <thead>
                                 <tr>
                                    <th>Backup Terbaru</th>
                                    <th>Waktu</th>
                                    <th class="text-end">Ukuran</th>
                                 </tr>
                              </thead>
                              <tbody><?php foreach (array_slice($backupFiles, 0, 5) as $file): ?><tr>
                                       <td><?= $h(basename($file)) ?></td>
                                       <td><?= date('d/m/Y H:i', filemtime($file)) ?></td>
                                       <td class="text-end"><?= number_format(filesize($file) / 1024, 1, ',', '.') ?> KB</td>
                                    </tr><?php endforeach; ?></tbody>
                           </table>
                        </div><?php endif; ?>
                     <h6 class="mt-4">Audit Pengaturan</h6>
                     <div class="table-responsive">
                        <table class="table table-sm">
                           <thead>
                              <tr>
                                 <th>Waktu</th>
                                 <th>Admin</th>
                                 <th>Bagian</th>
                              </tr>
                           </thead>
                           <tbody><?php if (!$auditRows): ?><tr>
                                    <td colspan="3" class="text-muted text-center">Belum ada perubahan.</td>
                                 </tr><?php endif; ?><?php foreach ($auditRows as $row): ?><tr>
                                    <td><?= $h(date('d/m/Y H:i', strtotime($row['created_at']))) ?></td>
                                    <td><?= $h($row['admin_nama']) ?></td>
                                    <td><?= $h(str_replace('_', ' ', $row['bagian'])) ?></td>
                                 </tr><?php endforeach; ?></tbody>
                        </table>
                     </div>
                  </div>
               </div>
            </div>

         </div>
      </div>
   </div>
</div>
<script>
   document.addEventListener('DOMContentLoaded', function() {
      var hash = location.hash || '#identitas',
         el = document.querySelector('a[data-bs-toggle="pill"][href="' + hash + '"]');
      if (el) bootstrap.Tab.getOrCreateInstance(el).show();
      document.querySelectorAll('a[data-bs-toggle="pill"]').forEach(function(a) {
         a.addEventListener('shown.bs.tab', function(e) {
            history.replaceState(null, '', e.target.getAttribute('href'));
         });
      });
   });
</script>