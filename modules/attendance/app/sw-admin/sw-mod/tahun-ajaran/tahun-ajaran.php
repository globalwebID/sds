<?php
if (!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])) {
    header('location:./login');
    exit;
}

$sdsYearUrl = sds_base_url('siteman/tahun_ajaran');
$rows = [];
$result = $connection->query("SELECT tahun_ajaran,status,semester_aktif,tanggal_mulai,tanggal_selesai,is_active FROM tahun_ajaran ORDER BY tahun_ajaran DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) $rows[] = $row;
}
$statusLabels = ['draft'=>'Draft','active'=>'Aktif','completed'=>'Selesai','archived'=>'Arsip'];
?>
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-8 col-8">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Tahun Ajaran</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>
<div class="container-fluid mt--6">
  <div class="row">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
          <div>
            <h3 class="mb-1">Tahun Ajaran</h3>
            <small class="text-muted">Data ini bersumber dari master SDS dan hanya ditampilkan sebagai referensi di Absensi.</small>
          </div>
          <a href="<?= htmlspecialchars($sdsYearUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary" target="_blank" rel="noopener">
            <i class="fas fa-external-link-alt"></i> Kelola di SDS
          </a>
        </div>
        <div class="card-body">
          <div class="alert alert-info">
            Penambahan, aktivasi, perubahan semester, arsip, dan penghapusan tahun ajaran hanya dilakukan melalui <strong>SDS → Tahun Ajaran</strong>. Hal ini mencegah periode Absensi berbeda dengan data siswa, rombel, dan E-KBM.
          </div>
          <div class="table-responsive">
            <table class="table align-items-center table-flush table-striped">
              <thead class="thead-light"><tr><th>No.</th><th>Tahun Ajaran</th><th>Periode</th><th>Semester</th><th>Status</th></tr></thead>
              <tbody>
              <?php if (!$rows): ?>
                <tr><td colspan="5" class="text-center text-muted">Belum ada data tahun ajaran.</td></tr>
              <?php else: foreach ($rows as $i => $row):
                $status = (string)($row['status'] ?? 'draft');
                $badge = $status === 'active' ? 'success' : ($status === 'draft' ? 'warning' : ($status === 'completed' ? 'info' : 'secondary'));
              ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><strong><?= htmlspecialchars((string)$row['tahun_ajaran']) ?></strong></td>
                  <td><?= htmlspecialchars((string)($row['tanggal_mulai'] ?: '-')) ?> s.d. <?= htmlspecialchars((string)($row['tanggal_selesai'] ?: '-')) ?></td>
                  <td><?= htmlspecialchars(ucfirst((string)($row['semester_aktif'] ?? '-'))) ?></td>
                  <td><span class="badge badge-<?= $badge ?>"><?= htmlspecialchars($statusLabels[$status] ?? ucfirst($status)) ?></span></td>
                </tr>
              <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
