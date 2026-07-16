<?php
/**
 * Kehadiran PD - TANPA lintas DB
 * Sumber: tabel lokal di DB SDS:
 *  - user  (copy dari absensi.user)
 *  - absen (copy dari absensi.absen)
 */

if (!isset($conn) || !($conn instanceof mysqli)) {
  echo '<div class="alert alert-danger">Koneksi database ($conn) tidak tersedia.</div>';
  return;
}

$id_siswa = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bulan    = isset($_GET['bulan']) ? (int)$_GET['bulan'] : 0;
$tahun    = isset($_GET['tahun']) ? (int)$_GET['tahun'] : 0;

if ($id_siswa <= 0) {
  echo '<div class="alert alert-warning">ID siswa tidak valid.</div>';
  return;
}

// Folder foto (sesuaikan)
$FOTO_BASE_URL = '../../../../../absensi.smkn1probolinggo.sch.id/sw-content/absen/';

function e($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function badgeKehadiran($kh) {
  $kh_raw = trim((string)$kh);
  $kh_l = strtolower($kh_raw);
  if ($kh_l === 'hadir' || $kh_l === 'h')  return '<span class="badge bg-success">Hadir</span>';
  if ($kh_l === 'izin'  || $kh_l === 'i')  return '<span class="badge bg-warning text-dark">Izin</span>';
  if ($kh_l === 'sakit' || $kh_l === 's')  return '<span class="badge bg-info text-dark">Sakit</span>';
  if ($kh_l === 'alfa'  || $kh_l === 'a' || $kh_l === 'tanpa keterangan') return '<span class="badge bg-danger">Alfa</span>';
  return $kh_raw !== '' ? '<span class="badge bg-secondary">'.e($kh_raw).'</span>' : '<span class="badge bg-light text-dark">-</span>';
}

function badgeStatus($st) {
  $st_raw = trim((string)$st);
  $st_l = strtolower($st_raw);
  if ($st_l === '') return '<span class="badge bg-light text-dark">-</span>';
  if (str_contains($st_l,'telat') || str_contains($st_l,'terlambat') || $st_l === 't') return '<span class="badge bg-danger">Terlambat</span>';
  if (str_contains($st_l,'tepat') || $st_l === 'ontime' || $st_l === 'tepat waktu') return '<span class="badge bg-success">Tepat Waktu</span>';
  if (str_contains($st_l,'pulang cepat')) return '<span class="badge bg-warning text-dark">Pulang Cepat</span>';
  return '<span class="badge bg-secondary">'.e($st_raw).'</span>';
}

// 1) Ambil NISN/RFID dari pendaftaran_siswa (DB SDS)
$stmtPd = $conn->prepare("SELECT nisn, rfid_uid, nama_lengkap FROM pendaftaran_siswa WHERE id=? LIMIT 1");
$stmtPd->bind_param('i', $id_siswa);
$stmtPd->execute();
$resPd = $stmtPd->get_result();
$pd = $resPd ? $resPd->fetch_assoc() : null;
$stmtPd->close();

if (!$pd) {
  echo '<div class="alert alert-warning">Data siswa tidak ditemukan di pendaftaran_siswa.</div>';
  return;
}

$nisn_pd = trim((string)($pd['nisn'] ?? ''));
$rfid_pd = trim((string)($pd['rfid_uid'] ?? ''));
$nama_pd = (string)($pd['nama_lengkap'] ?? '-');

// 2) Cari user_id absensi dari tabel lokal user
$user_id_absen = 0;
$userInfo = ['user_id'=>0,'nisn'=>$nisn_pd ?: '-', 'rfid'=>$rfid_pd ?: '-', 'nama'=>$nama_pd ?: '-'];

if ($nisn_pd !== '') {
  $stmtU = $conn->prepare("SELECT user_id, nisn, rfid, nama_lengkap FROM user WHERE nisn=? LIMIT 1");
  if ($stmtU) {
    $stmtU->bind_param('s', $nisn_pd);
    $stmtU->execute();
    $resU = $stmtU->get_result();
    if ($u = ($resU ? $resU->fetch_assoc() : null)) {
      $user_id_absen = (int)$u['user_id'];
      $userInfo['user_id'] = $user_id_absen;
      $userInfo['nisn'] = $u['nisn'] ?: $userInfo['nisn'];
      $userInfo['rfid'] = $u['rfid'] ?: $userInfo['rfid'];
      $userInfo['nama'] = $u['nama_lengkap'] ?: $userInfo['nama'];
    }
    $stmtU->close();
  }
}

if ($user_id_absen <= 0 && $rfid_pd !== '') {
  $stmtU2 = $conn->prepare("SELECT user_id, nisn, rfid, nama_lengkap FROM user WHERE rfid=? LIMIT 1");
  if ($stmtU2) {
    $stmtU2->bind_param('s', $rfid_pd);
    $stmtU2->execute();
    $resU2 = $stmtU2->get_result();
    if ($u2 = ($resU2 ? $resU2->fetch_assoc() : null)) {
      $user_id_absen = (int)$u2['user_id'];
      $userInfo['user_id'] = $user_id_absen;
      $userInfo['nisn'] = $u2['nisn'] ?: $userInfo['nisn'];
      $userInfo['rfid'] = $u2['rfid'] ?: $userInfo['rfid'];
      $userInfo['nama'] = $u2['nama_lengkap'] ?: $userInfo['nama'];
    }
    $stmtU2->close();
  }
}

if ($user_id_absen <= 0) {
  echo '<div class="card-body">
          <div class="alert alert-warning mb-0">
            <b>Data absensi belum tersedia di DB SDS</b><br>
            Pastikan tabel <code>user</code> sudah ada & berisi data (hasil import dari DB absensi).<br>
            NISN: <b>'.e($nisn_pd ?: '-').'</b> | RFID: <b>'.e($rfid_pd ?: '-').'</b>
          </div>
        </div>';
  return;
}

// 3) Filter
$where = "";
$types = "i";
$vals  = [$user_id_absen];

if ($bulan && $tahun) {
  $where = " AND MONTH(tanggal)=? AND YEAR(tanggal)=? ";
  $types .= "ii";
  $vals[] = $bulan;
  $vals[] = $tahun;
}

// 4) Ambil absen dari tabel lokal absen
$sqlA = "
  SELECT absen_id, tanggal, jam_masuk, jam_pulang, absen_in, absen_out,
         foto_in, foto_out, status_masuk, status_pulang, kehadiran, keterangan
  FROM absen
  WHERE user_id=?
  {$where}
  ORDER BY tanggal DESC, absen_id DESC
";

$stmtA = $conn->prepare($sqlA);
if (!$stmtA) {
  echo '<div class="alert alert-danger">Query absen gagal: '.e($conn->error).'</div>';
  return;
}

// bind dinamis
$tmp = [];
$tmp[] = $types;
foreach ($vals as $k => $v) { $tmp[] = &$vals[$k]; }
call_user_func_array([$stmtA, 'bind_param'], $tmp);

$stmtA->execute();
$resA = $stmtA->get_result();
$rows = [];
if ($resA) while ($r = $resA->fetch_assoc()) $rows[] = $r;
$stmtA->close();

// summary
$summary = ['total'=>0,'hadir'=>0,'izin'=>0,'sakit'=>0,'alfa'=>0,'terlambat'=>0];
foreach ($rows as $r) {
  $summary['total']++;
  $kh = strtolower(trim((string)($r['kehadiran'] ?? '')));
  if ($kh === 'hadir' || $kh === 'h') $summary['hadir']++;
  elseif ($kh === 'izin' || $kh === 'i') $summary['izin']++;
  elseif ($kh === 'sakit' || $kh === 's') $summary['sakit']++;
  elseif ($kh === 'alfa' || $kh === 'a' || $kh === 'tanpa keterangan') $summary['alfa']++;

  $st = strtolower(trim((string)($r['status_masuk'] ?? '')));
  if ($st !== '' && (str_contains($st,'telat') || str_contains($st,'terlambat') || $st === 't')) $summary['terlambat']++;
}
?>

<div class="card-body">
  <div class="row" style="margin:-20px;">
    <div class="top-tab mt-0">
      <div class="row align-items-center justify-content-between g-2">
        <div class="col-auto">
          <h5 class="card-title mb-0">
            Kehadiran (UserID: <?= (int)$userInfo['user_id'] ?> | NISN: <?= e($userInfo['nisn']) ?> | RFID: <?= e($userInfo['rfid']) ?>)
          </h5>
          <small class="text-muted">Sumber: tabel lokal DB SDS (user & absen)</small>
        </div>

        <div class="col-auto">
          <form method="GET" action="" class="d-flex align-items-center gap-2 flex-wrap" onsubmit="return tambahHashKehadiran();">
            <input type="hidden" name="id" value="<?= (int)$id_siswa ?>">

            <select name="bulan" class="form-select" style="width:auto;">
              <option value="">Bulan</option>
              <?php for ($b=1; $b<=12; $b++): ?>
                <option value="<?= $b ?>" <?= ($bulan===$b?'selected':'') ?>><?= date('F', mktime(0,0,0,$b,10)) ?></option>
              <?php endfor; ?>
            </select>

            <select name="tahun" class="form-select" style="width:auto;">
              <option value="">Tahun</option>
              <?php $cy=(int)date('Y'); for($t=$cy;$t>=$cy-5;$t--): ?>
                <option value="<?= $t ?>" <?= ($tahun===$t?'selected':'') ?>><?= $t ?></option>
              <?php endfor; ?>
            </select>

            <button type="submit" class="btn btn-primary">Tampilkan</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="d-flex">
  <div class="col-md-2"><div class="text-white bg-primary shadow-sm"><div class="card-body text-center"><h6 class="card-title mb-1 text-white">Total</h6><h4 class="fw-bold text-white"><?= (int)$summary['total'] ?></h4></div></div></div>
  <div class="col-md-2"><div class="text-white bg-success shadow-sm"><div class="card-body text-center"><h6 class="card-title mb-1 text-white">Hadir</h6><h4 class="fw-bold text-white"><?= (int)$summary['hadir'] ?></h4></div></div></div>
  <div class="col-md-2"><div class="text-white bg-warning shadow-sm"><div class="card-body text-center"><h6 class="card-title mb-1 text-white">Izin</h6><h4 class="fw-bold text-white"><?= (int)$summary['izin'] ?></h4></div></div></div>
  <div class="col-md-2"><div class="text-white bg-info shadow-sm"><div class="card-body text-center"><h6 class="card-title mb-1 text-white">Sakit</h6><h4 class="fw-bold text-white"><?= (int)$summary['sakit'] ?></h4></div></div></div>
  <div class="col-md-2"><div class="text-white bg-danger shadow-sm"><div class="card-body text-center"><h6 class="card-title mb-1 text-white">Alfa</h6><h4 class="fw-bold text-white"><?= (int)$summary['alfa'] ?></h4></div></div></div>
  <div class="col-md-2"><div class="text-white bg-dark shadow-sm"><div class="card-body text-center"><h6 class="card-title mb-1 text-white">Terlambat</h6><h4 class="fw-bold text-white"><?= (int)$summary['terlambat'] ?></h4></div></div></div>
</div>

<div class="">
  <table class="table table-sm table-striped table-bordered table-hover shadow-sm bg-white rounded">
    <thead class="table-success">
      <tr>
        <th style="width:120px;">Tanggal</th>
        <th style="width:95px;">Masuk</th>
        <th style="width:95px;">Pulang</th>
        <th style="width:120px;">Status Masuk</th>
        <th style="width:120px;">Status Pulang</th>
        <th style="width:110px;">Kehadiran</th>
        <th>Keterangan</th>
        <th style="width:120px;">Foto</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($rows)): ?>
        <tr><td colspan="8" class="text-center text-muted">Tidak ada data kehadiran untuk filter ini.</td></tr>
      <?php else: ?>
        <?php foreach ($rows as $r):
          $jamMasuk  = $r['absen_in']  ?: ($r['jam_masuk']  ?: '-');
          $jamPulang = $r['absen_out'] ?: ($r['jam_pulang'] ?: '-');

          $fotoIn  = trim((string)($r['foto_in'] ?? ''));
          $fotoOut = trim((string)($r['foto_out'] ?? ''));

          $urlIn  = $fotoIn  !== '' ? $FOTO_BASE_URL . $fotoIn  : '';
          $urlOut = $fotoOut !== '' ? $FOTO_BASE_URL . $fotoOut : '';
        ?>
          <tr>
            <td><?= e($r['tanggal'] ?? '-') ?></td>
            <td><?= e($jamMasuk) ?></td>
            <td><?= e($jamPulang) ?></td>
            <td><?= badgeStatus($r['status_masuk'] ?? '') ?></td>
            <td><?= badgeStatus($r['status_pulang'] ?? '') ?></td>
            <td><?= badgeKehadiran($r['kehadiran'] ?? '') ?></td>
            <td><?= ($r['keterangan'] ?? '') !== '' ? nl2br(e($r['keterangan'])) : '-' ?></td>
            <td class="text-center">
              <div class="d-flex justify-content-center gap-1 flex-wrap">
                <?php if ($urlIn !== ''): ?>
                  <a href="javascript:void(0)" onclick="openFoto('<?= e($urlIn) ?>','Foto Masuk')"><span class="badge bg-primary">IN</span></a>
                <?php else: ?><span class="badge bg-light text-dark">IN -</span><?php endif; ?>

                <?php if ($urlOut !== ''): ?>
                  <a href="javascript:void(0)" onclick="openFoto('<?= e($urlOut) ?>','Foto Pulang')"><span class="badge bg-success">OUT</span></a>
                <?php else: ?><span class="badge bg-light text-dark">OUT -</span><?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<div class="modal fade" id="modalFotoKehadiran" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalFotoTitle">Foto</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <img id="modalFotoImg" src="" alt="Foto" style="max-width:100%;height:auto;border-radius:10px;">
      </div>
    </div>
  </div>
</div>

<script>
function tambahHashKehadiran() {
  const form = event.target;
  const action = form.getAttribute('action') || window.location.pathname;
  const params = new URLSearchParams(new FormData(form));
  window.location.href = `${action}?${params.toString()}#kehadiran`;
  return false;
}
function openFoto(src, title) {
  document.getElementById('modalFotoTitle').innerText = title || 'Foto';
  document.getElementById('modalFotoImg').src = src;
  const m = new bootstrap.Modal(document.getElementById('modalFotoKehadiran'));
  m.show();
}
</script>
