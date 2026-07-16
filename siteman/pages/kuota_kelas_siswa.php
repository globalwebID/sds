<?php
// ➜ proteksi admin login
if (!isset($_SESSION['admin_id'])) {
  header('Location: login.php');
  exit;
}

$id_kelas = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$jurusan_id = isset($_GET['jurusan_id']) ? (int)$_GET['jurusan_id'] : 0;
$tahunParam = trim((string)($_GET['tahun'] ?? ($_GET['tahun_ajaran'] ?? '')));
$keyword = trim((string)($_GET['q'] ?? ''));
$keywordLike = '%' . $keyword . '%';

// Tahun ajaran aktif berasal dari master Tahun Ajaran SDS.
$tahunAjaranAktif = (string)($tahunAjaran ?? '');

$modeJurusan = ($jurusan_id > 0 && $id_kelas <= 0);
$modeKelas = ($id_kelas > 0);

if (!$modeJurusan && !$modeKelas) {
  die('Parameter kelas atau jurusan tidak valid.');
}

function h($value) {
  return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Tahun ajaran sebelumnya mengikuti master aktif, bukan kalender server.
$tahunAjaranLama = sds_academic_year_previous_label($tahunAjaranAktif);

$kelas = null;
$jurusan = null;
$tahunAjaranKelas = $tahunParam !== '' ? $tahunParam : $tahunAjaranAktif;
$totalTerisiReal = 0;
$kuota = 0;
$sisaKuota = 0;
$qSiswa = null;
$jumlahKelasJurusan = 0;
$jumlahLaki = 0;
$jumlahPerempuan = 0;
$jumlahGenderKosong = 0;

if ($modeKelas) {
  /**
   * Mode lama: tampilkan daftar siswa per rombel/kelas.
   */
  $sqlKelas = "
    SELECT k.*, tk.nama_tingkat, tk.urutan_tingkat, j.nama_jurusan
    FROM kelas k
    JOIN tingkat_kelas tk ON k.tingkat_id = tk.id
    LEFT JOIN jurusan j ON j.id = k.jurusan_id
    WHERE k.id = ?
    LIMIT 1
  ";
  $stmtKelas = $conn->prepare($sqlKelas);
  $stmtKelas->bind_param("i", $id_kelas);
  $stmtKelas->execute();
  $resKelas = $stmtKelas->get_result();
  $kelas = $resKelas ? $resKelas->fetch_assoc() : null;
  $stmtKelas->close();

  if (!$kelas) die('Data kelas tidak ditemukan.');

  $tahunAjaranKelas = (string)$kelas['tahun_ajaran'];
  $kuota = isset($kelas['kuota']) ? (int)$kelas['kuota'] : 0;

  $sqlSiswa = "
    SELECT
      ps.id,
      ps.nisn,
      ps.tahun_ajaran,
      ps.nipd,
      ps.nik,
      ps.nama_lengkap,
      ps.jenis_kelamin,
      ps.foto,
      sk.kelas_id,
      sk.naik_kelas,
      tk.urutan_tingkat,
      k.nama_kelas,
      tk.nama_tingkat
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    WHERE sk.kelas_id = ?
      AND sk.tahun_ajaran = ?
      AND sk.naik_kelas = 1
      AND ps.status_aktif = 1
  ";
  $siswaTypes = "is";
  $siswaParams = [$id_kelas, $tahunAjaranKelas];
  if ($keyword !== '') {
    $sqlSiswa .= "
      AND (
        ps.nama_lengkap LIKE ?
        OR ps.nisn LIKE ?
        OR ps.nipd LIKE ?
        OR ps.nik LIKE ?
        OR ps.jenis_kelamin LIKE ?
        OR k.nama_kelas LIKE ?
      )
    ";
    $siswaTypes .= "ssssss";
    array_push($siswaParams, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike);
  }
  $sqlSiswa .= " ORDER BY ps.nama_lengkap ASC";
  $stmtSiswa = $conn->prepare($sqlSiswa);
  $stmtSiswa->bind_param($siswaTypes, ...$siswaParams);
  $stmtSiswa->execute();
  $qSiswa = $stmtSiswa->get_result();

  $sqlCount = "
    SELECT COUNT(DISTINCT ps.id) AS total
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    WHERE sk.kelas_id = ?
      AND sk.tahun_ajaran = ?
      AND sk.naik_kelas = 1
      AND ps.status_aktif = 1
  ";
  $countTypes = "is";
  $countParams = [$id_kelas, $tahunAjaranKelas];
  if ($keyword !== '') {
    $sqlCount .= "
      AND (
        ps.nama_lengkap LIKE ?
        OR ps.nisn LIKE ?
        OR ps.nipd LIKE ?
        OR ps.nik LIKE ?
        OR ps.jenis_kelamin LIKE ?
        OR k.nama_kelas LIKE ?
      )
    ";
    $countTypes .= "ssssss";
    array_push($countParams, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike);
  }
  $stmtCount = $conn->prepare($sqlCount);
  $stmtCount->bind_param($countTypes, ...$countParams);
  $stmtCount->execute();
  $totalTerisiReal = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
  $stmtCount->close();

  $sqlGenderCount = "
    SELECT
      COUNT(DISTINCT CASE WHEN UPPER(TRIM(ps.jenis_kelamin)) IN ('L','LAKI-LAKI','LAKI LAKI') THEN ps.id END) AS total_laki,
      COUNT(DISTINCT CASE WHEN UPPER(TRIM(ps.jenis_kelamin)) IN ('P','PEREMPUAN') THEN ps.id END) AS total_perempuan,
      COUNT(DISTINCT CASE WHEN COALESCE(TRIM(ps.jenis_kelamin),'') = '' THEN ps.id END) AS total_kosong
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    WHERE sk.kelas_id = ?
      AND sk.tahun_ajaran = ?
      AND sk.naik_kelas = 1
      AND ps.status_aktif = 1
  ";
  $genderTypes = "is";
  $genderParams = [$id_kelas, $tahunAjaranKelas];
  if ($keyword !== '') {
    $sqlGenderCount .= "
      AND (
        ps.nama_lengkap LIKE ?
        OR ps.nisn LIKE ?
        OR ps.nipd LIKE ?
        OR ps.nik LIKE ?
        OR ps.jenis_kelamin LIKE ?
        OR k.nama_kelas LIKE ?
      )
    ";
    $genderTypes .= "ssssss";
    array_push($genderParams, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike);
  }
  $stmtGender = $conn->prepare($sqlGenderCount);
  $stmtGender->bind_param($genderTypes, ...$genderParams);
  $stmtGender->execute();
  $rowGender = $stmtGender->get_result()->fetch_assoc();
  $jumlahLaki = (int)($rowGender['total_laki'] ?? 0);
  $jumlahPerempuan = (int)($rowGender['total_perempuan'] ?? 0);
  $jumlahGenderKosong = (int)($rowGender['total_kosong'] ?? 0);
  $stmtGender->close();

  $sisaKuota = max(0, $kuota - $totalTerisiReal);

  $stmtKelasSebelum = $conn->prepare("
    SELECT kelas_id
    FROM siswa_kelas
    WHERE siswa_id = ?
      AND tahun_ajaran = ?
    LIMIT 1
  ");
} else {
  /**
   * Mode baru: tampilkan daftar siswa dalam satu jurusan/kompetensi keahlian.
   * Dipakai dari link JML. PD pada halaman jurusan.
   */
  $sqlJurusan = "
    SELECT id, nama_jurusan, kode_jurusan, tahun_ajaran
    FROM jurusan
    WHERE id = ?
    LIMIT 1
  ";
  $stmtJurusan = $conn->prepare($sqlJurusan);
  $stmtJurusan->bind_param("i", $jurusan_id);
  $stmtJurusan->execute();
  $resJurusan = $stmtJurusan->get_result();
  $jurusan = $resJurusan ? $resJurusan->fetch_assoc() : null;
  $stmtJurusan->close();

  if (!$jurusan) die('Data jurusan tidak ditemukan.');

  // Prioritaskan tahun dari link, jika kosong pakai tahun jurusan.
  $tahunAjaranKelas = $tahunParam !== '' ? $tahunParam : (string)$jurusan['tahun_ajaran'];

  $sqlCountKelas = "
    SELECT COUNT(*) AS total_kelas, COALESCE(SUM(kuota),0) AS total_kuota
    FROM kelas
    WHERE jurusan_id = ?
      AND tahun_ajaran = ?
  ";
  $stmtCountKelas = $conn->prepare($sqlCountKelas);
  $stmtCountKelas->bind_param("is", $jurusan_id, $tahunAjaranKelas);
  $stmtCountKelas->execute();
  $rowCountKelas = $stmtCountKelas->get_result()->fetch_assoc();
  $jumlahKelasJurusan = (int)($rowCountKelas['total_kelas'] ?? 0);
  $kuota = (int)($rowCountKelas['total_kuota'] ?? 0);
  $stmtCountKelas->close();

  $sqlSiswa = "
    SELECT
      ps.id,
      ps.nisn,
      ps.tahun_ajaran,
      ps.nipd,
      ps.nik,
      ps.nama_lengkap,
      ps.jenis_kelamin,
      ps.foto,
      sk.kelas_id,
      sk.naik_kelas,
      tk.urutan_tingkat,
      tk.nama_tingkat,
      k.nama_kelas
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    WHERE k.jurusan_id = ?
      AND k.tahun_ajaran = ?
      AND sk.tahun_ajaran = ?
      AND sk.naik_kelas = 1
      AND ps.status_aktif = 1
  ";
  $siswaTypes = "iss";
  $siswaParams = [$jurusan_id, $tahunAjaranKelas, $tahunAjaranKelas];
  if ($keyword !== '') {
    $sqlSiswa .= "
      AND (
        ps.nama_lengkap LIKE ?
        OR ps.nisn LIKE ?
        OR ps.nipd LIKE ?
        OR ps.nik LIKE ?
        OR ps.jenis_kelamin LIKE ?
        OR k.nama_kelas LIKE ?
        OR tk.nama_tingkat LIKE ?
      )
    ";
    $siswaTypes .= "sssssss";
    array_push($siswaParams, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike);
  }
  $sqlSiswa .= " ORDER BY k.nama_kelas ASC, ps.nama_lengkap ASC";
  $stmtSiswa = $conn->prepare($sqlSiswa);
  $stmtSiswa->bind_param($siswaTypes, ...$siswaParams);
  $stmtSiswa->execute();
  $qSiswa = $stmtSiswa->get_result();

  $sqlCount = "
    SELECT COUNT(DISTINCT ps.id) AS total
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    WHERE k.jurusan_id = ?
      AND k.tahun_ajaran = ?
      AND sk.tahun_ajaran = ?
      AND sk.naik_kelas = 1
      AND ps.status_aktif = 1
  ";
  $countTypes = "iss";
  $countParams = [$jurusan_id, $tahunAjaranKelas, $tahunAjaranKelas];
  if ($keyword !== '') {
    $sqlCount .= "
      AND (
        ps.nama_lengkap LIKE ?
        OR ps.nisn LIKE ?
        OR ps.nipd LIKE ?
        OR ps.nik LIKE ?
        OR ps.jenis_kelamin LIKE ?
        OR k.nama_kelas LIKE ?
      )
    ";
    $countTypes .= "ssssss";
    array_push($countParams, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike);
  }
  $stmtCount = $conn->prepare($sqlCount);
  $stmtCount->bind_param($countTypes, ...$countParams);
  $stmtCount->execute();
  $totalTerisiReal = (int)($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
  $stmtCount->close();

  $sqlGenderCount = "
    SELECT
      COUNT(DISTINCT CASE WHEN UPPER(TRIM(ps.jenis_kelamin)) IN ('L','LAKI-LAKI','LAKI LAKI') THEN ps.id END) AS total_laki,
      COUNT(DISTINCT CASE WHEN UPPER(TRIM(ps.jenis_kelamin)) IN ('P','PEREMPUAN') THEN ps.id END) AS total_perempuan,
      COUNT(DISTINCT CASE WHEN COALESCE(TRIM(ps.jenis_kelamin),'') = '' THEN ps.id END) AS total_kosong
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
    JOIN kelas k ON k.id = sk.kelas_id
    LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    WHERE k.jurusan_id = ?
      AND k.tahun_ajaran = ?
      AND sk.tahun_ajaran = ?
      AND sk.naik_kelas = 1
      AND ps.status_aktif = 1
  ";
  $genderTypes = "iss";
  $genderParams = [$jurusan_id, $tahunAjaranKelas, $tahunAjaranKelas];
  if ($keyword !== '') {
    $sqlGenderCount .= "
      AND (
        ps.nama_lengkap LIKE ?
        OR ps.nisn LIKE ?
        OR ps.nipd LIKE ?
        OR ps.nik LIKE ?
        OR ps.jenis_kelamin LIKE ?
        OR k.nama_kelas LIKE ?
        OR tk.nama_tingkat LIKE ?
      )
    ";
    $genderTypes .= "sssssss";
    array_push($genderParams, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike);
  }
  $stmtGender = $conn->prepare($sqlGenderCount);
  $stmtGender->bind_param($genderTypes, ...$genderParams);
  $stmtGender->execute();
  $rowGender = $stmtGender->get_result()->fetch_assoc();
  $jumlahLaki = (int)($rowGender['total_laki'] ?? 0);
  $jumlahPerempuan = (int)($rowGender['total_perempuan'] ?? 0);
  $jumlahGenderKosong = (int)($rowGender['total_kosong'] ?? 0);
  $stmtGender->close();

  $sisaKuota = max(0, $kuota - $totalTerisiReal);

  $stmtKelasSebelum = null;
}

$exportParams = [];
if ($modeJurusan) {
  $exportParams['jurusan_id'] = (int)$jurusan_id;
  $exportParams['tahun'] = $tahunAjaranKelas;
} else {
  $exportParams['kelas_id'] = (int)$id_kelas;
}
if ($keyword !== '') {
  $exportParams['q'] = $keyword;
}
$exportExcelUrl = 'kuota_kelas_siswa_export.php?' . http_build_query($exportParams);
$cetakAbsenUrl = $modeKelas ? ('cetak_absensi_pdf.php?kelas_id=' . (int)$id_kelas) : '';
?>
<style>
  .sds-dashboard{padding:0}
  .sds-hero{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem}
  .sds-hero h2{font-size:1.25rem;font-weight:700;margin:0;color:#212529}
  .sds-hero p{margin:.25rem 0 0;color:#6c757d;font-size:.86rem}
  .sds-hero-actions{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;justify-content:flex-end}
  .sds-card{background:#fff;border:1px solid #dee2e6;border-radius:0rem;box-shadow:unset;margin-bottom:0rem}
  .sds-card-header{display:flex;align-items:center;justify-content:space-between;gap:.75rem;padding:.9rem 1rem;border-bottom:1px solid #dee2e6;background:#f8f9fa}
  .sds-card-header h5{margin:0;font-weight:600;color:#334151;font-size:1rem}
  .sds-card-body{padding:1rem}
  .sds-info-row{display:flex;flex-wrap:wrap;gap:.5rem .65rem;align-items:center}
  .sds-badge{display:inline-flex;align-items:center;gap:.35rem;border:1px solid #dee2e6;background:#fff;color:#334151;border-radius:.25rem;padding:.32rem .55rem;font-size:.78rem;line-height:1.15}
  .sds-badge.info{background:#f8f9fa;color:#334151}
  .sds-badge.ok{background:#eaf7ef;color:#146c43;border-color:#cfe9d8}
  .sds-badge.warn{background:#fff8e5;color:#946200;border-color:#f5df9a}
  .sds-mini{color:#6c757d;font-size:.78rem}
  .sds-filter{display:grid;grid-template-columns:minmax(280px,1fr) auto auto;gap:.65rem;align-items:end}
  .sds-filter .form-label{font-size:.78rem;color:#6c757d;text-transform:uppercase;letter-spacing:.04em}
  .sds-table-wrap{overflow:auto}
  .sds-table{width:100%;border-collapse:collapse;background:#fff;margin:0}
  .sds-table th{font-size:.72rem;text-transform:uppercase;letter-spacing:.04em;color:#6c757d;background:#f8f9fa;padding:.48rem .55rem;border-bottom:1px solid #dee2e6;white-space:nowrap}
  .sds-table td{padding:5px .55rem;border-bottom:1px solid #edf1f5;vertical-align:middle;color:#334151}
  .sds-table tr:last-child td{border-bottom:0}
  .sds-table a{font-weight:600;text-decoration:none}
  .sds-photo{width:34px;height:34px;object-fit:cover;border:1px solid #dee2e6;border-radius:.25rem;margin-right:.5rem;background:#f8f9fa}
  .sds-name-cell{display:flex;align-items:center;min-width:260px}
  .sds-action{white-space:nowrap;text-align:right}
  .sds-empty{padding:1.25rem;text-align:center;color:#6c757d}
  @media(max-width:700px){.sds-dashboard{padding:0 6px}.sds-hero{display:block}.sds-hero-actions{justify-content:flex-start;margin-top:.75rem}.sds-filter{grid-template-columns:1fr}.sds-table{min-width:760px}}
</style>

<div class="sds-dashboard">
  <div class="sds-hero">
    <div>
      <?php if ($modeJurusan): ?>
        <h2>Daftar Siswa Jurusan <?= h($jurusan['nama_jurusan']) ?></h2>
        <p>Tahun ajaran <?= h($tahunAjaranKelas) ?> · Data peserta didik aktif pada kompetensi keahlian.</p>
      <?php else: ?>
        <h2>Rombel Siswa Kelas <?= h($kelas['nama_kelas']) ?></h2>
        <p>Tahun ajaran <?= h($tahunAjaranKelas) ?> · Data peserta didik aktif pada rombel.</p>
      <?php endif; ?>
    </div>
    <div class="sds-hero-actions">
      <?php if ($modeKelas): ?>
        <a href="<?= h($cetakAbsenUrl) ?>" target="_blank" class="btn btn-outline-primary">Cetak Absen</a>
      <?php endif; ?>
      <a href="<?= h($exportExcelUrl) ?>" class="btn btn-success">Ekspor Excel</a>
      <a href="#" onclick="goBackOrRedirect(); return false;" class="btn btn-outline-secondary">Kembali</a>
    </div>
  </div>

  <?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible" role="alert">
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      <div class="alert-message"><?= $_SESSION['error'] ?></div>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible" role="alert">
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      <div class="alert-message"><?= $_SESSION['success'] ?></div>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <div class="sds-card">
    <div class="sds-card-header">
      <h5><?= $modeJurusan ? 'Ringkasan Jurusan' : 'Ringkasan Kelas' ?></h5>
      <span class="sds-badge info"><?= $keyword !== '' ? 'Hasil filter' : 'Semua siswa aktif' ?></span>
    </div>
    <div class="sds-card-body">
      <div class="sds-info-row">
        <?php if ($modeJurusan): ?>
          <span class="sds-badge info">Kode: <strong><?= h($jurusan['kode_jurusan']) ?></strong></span>
          <span class="sds-badge info">Tahun Ajaran: <strong><?= h($tahunAjaranKelas) ?></strong></span>
          <span class="sds-badge info">JML. Kelas: <strong><?= number_format((int)$jumlahKelasJurusan, 0, ',', '.') ?></strong></span>
          <span class="sds-badge info">Kuota: <strong><?= number_format((int)$kuota, 0, ',', '.') ?></strong></span>
          <span class="sds-badge ok">JML. PD: <strong><?= number_format((int)$totalTerisiReal, 0, ',', '.') ?></strong></span>
          <span class="sds-badge info">Laki-laki: <strong><?= number_format((int)$jumlahLaki, 0, ',', '.') ?></strong></span>
          <span class="sds-badge info">Perempuan: <strong><?= number_format((int)$jumlahPerempuan, 0, ',', '.') ?></strong></span>
          <?php if ($jumlahGenderKosong > 0): ?><span class="sds-badge warn">Gender kosong: <strong><?= number_format((int)$jumlahGenderKosong, 0, ',', '.') ?></strong></span><?php endif; ?>
        <?php else: ?>
          <span class="sds-badge info">Wali Kelas: <strong><?= h($kelas['wali_kelas'] ?: '-') ?></strong></span>
          <span class="sds-badge info">Tahun Ajaran: <strong><?= h($tahunAjaranKelas) ?></strong></span>
          <span class="sds-badge info">Kuota: <strong><?= number_format((int)$kuota, 0, ',', '.') ?></strong></span>
          <span class="sds-badge ok">Terisi: <strong><?= number_format((int)$totalTerisiReal, 0, ',', '.') ?></strong></span>
          <span class="sds-badge info">Laki-laki: <strong><?= number_format((int)$jumlahLaki, 0, ',', '.') ?></strong></span>
          <span class="sds-badge info">Perempuan: <strong><?= number_format((int)$jumlahPerempuan, 0, ',', '.') ?></strong></span>
          <?php if ($jumlahGenderKosong > 0): ?><span class="sds-badge warn">Gender kosong: <strong><?= number_format((int)$jumlahGenderKosong, 0, ',', '.') ?></strong></span><?php endif; ?>
          <span class="sds-badge warn">Sisa: <strong><?= number_format((int)$sisaKuota, 0, ',', '.') ?></strong></span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="sds-card">
    <div class="sds-card-header">
      <h5>Pencarian Siswa</h5>
      <?php if ($keyword !== ''): ?><span class="sds-mini">Kata kunci: <strong><?= h($keyword) ?></strong></span><?php endif; ?>
    </div>
    <div class="sds-card-body">
      <form method="get" class="sds-filter align-items-center">
        <?php if ($modeJurusan): ?>
          <input type="hidden" name="jurusan_id" value="<?= (int)$jurusan_id ?>">
          <input type="hidden" name="tahun" value="<?= h($tahunAjaranKelas) ?>">
        <?php else: ?>
          <input type="hidden" name="kelas_id" value="<?= (int)$id_kelas ?>">
        <?php endif; ?>

        <div>
          <label class="form-label mb-1 d-none"><strong>Pencarian Siswa</strong></label>
          <input
            type="text"
            name="q"
            class="form-control"
            value="<?= h($keyword) ?>"
            placeholder="Cari nama, NISN, NIPD, NIK, kelas, atau jenis kelamin...">
        </div>
        <button type="submit" class="btn btn-primary">Cari</button>
        <?php if ($keyword !== ''): ?>
          <?php if ($modeJurusan): ?>
            <a class="btn btn-outline-secondary" href="kuota_kelas_siswa?jurusan_id=<?= (int)$jurusan_id ?>&tahun=<?= urlencode($tahunAjaranKelas) ?>">Reset</a>
          <?php else: ?>
            <a class="btn btn-outline-secondary" href="kuota_kelas_siswa?kelas_id=<?= (int)$id_kelas ?>">Reset</a>
          <?php endif; ?>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <div class="sds-card">
    <div class="sds-card-header">
      <h5>Daftar Peserta Didik</h5>
      <span class="sds-badge info"><?= number_format((int)$totalTerisiReal, 0, ',', '.') ?> siswa</span>
    </div>
    <div class="sds-table-wrap">
      <table class="sds-table">
        <thead>
          <tr>
            <th>NO.</th>
            <th>NAMA PESERTA DIDIK</th>
            <th>JK</th>
            <th>NISN</th>
            <th>NIPD</th>
            <?php if ($modeJurusan): ?><th>KELAS</th><?php endif; ?>
            <th class="text-center">TINGKAT</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php
          $no = 1;
          if ($qSiswa && $qSiswa->num_rows > 0):
            while ($row = $qSiswa->fetch_assoc()):
              $kelasSebelumNaik = null;
              if ($modeKelas && $stmtKelasSebelum) {
                $sid = (int)$row['id'];
                $stmtKelasSebelum->bind_param("is", $sid, $tahunAjaranLama);
                $stmtKelasSebelum->execute();
                $resPrev = $stmtKelasSebelum->get_result();
                $dataPrev = $resPrev ? $resPrev->fetch_assoc() : null;
                $kelasSebelumNaik = $dataPrev['kelas_id'] ?? null;
              }

              $jkRaw = (string)($row['jenis_kelamin'] ?? '');
              $jk = ($jkRaw === 'Laki-laki' || strtoupper($jkRaw) === 'L') ? 'L' : (($jkRaw === 'Perempuan' || strtoupper($jkRaw) === 'P') ? 'P' : '-');
              $nisn = (string)($row['nisn'] ?? '');
              $foto = (string)($row['foto'] ?? '');
          ?>
            <tr>
              <td><?= $no++ ?></td>
              <td>
                <div class="sds-name-cell">
                  <?php if (!empty($foto)): ?>
                    <a href="../uploads/<?= h($foto) ?>" data-lightbox="foto-<?= h($nisn) ?>">
                      <img src="../uploads/<?= h($foto) ?>" class="sds-photo" alt="Foto">
                    </a>
                  <?php else: ?>
                    <span class="sds-photo d-inline-flex align-items-center justify-content-center text-muted">-</span>
                  <?php endif; ?>

                  <a href="student_view&id=<?= (int)$row['id'] ?>" title="Lihat Profil Siswa">
                    <?= h($row['nama_lengkap']) ?>
                  </a>
                </div>
              </td>
              <td><?= h($jk) ?></td>
              <td><?= h($nisn) ?></td>
              <td><?= h($row['nipd']) ?></td>
              <?php if ($modeJurusan): ?><td><?= h($row['nama_kelas'] ?? '-') ?></td><?php endif; ?>
              <td class="text-center"><?= h($row['nama_tingkat'] ?? '-') ?></td>
              <td class="sds-action">
                <?php if ($modeKelas): ?>
                  <?php if ((int)$row['naik_kelas'] === 1 && (int)$row['urutan_tingkat'] > 1): ?>
                    <form action="tidak_naik_kelas" method="post" style="display:inline;" onsubmit="return confirm('Yakin siswa ini tidak naik kelas?');">
                      <input type="hidden" name="siswa_id" value="<?= (int)$row['id'] ?>">
                      <input type="hidden" name="kelas_id" value="<?= (int)$row['kelas_id'] ?>">
                      <input type="hidden" name="tahun_ajaran" value="<?= h($tahunAjaranKelas) ?>">
                      <button type="submit" class="btn btn-sm btn-danger" title="Tandai Tidak Naik Kelas">Turun Kelas</button>
                    </form>
                  <?php elseif ((int)$row['naik_kelas'] !== 1): ?>
                    <span class="badge bg-danger">Tidak Naik</span>
                  <?php endif; ?>
                <?php endif; ?>

                <a href="student_view&id=<?= (int)$row['id'] ?>" title="Lihat Profil Siswa" class="btn btn-sm btn-primary">Profil Peserta Didik</a>
              </td>
            </tr>
          <?php
            endwhile;
          else:
          ?>
            <tr>
              <td colspan="<?= $modeJurusan ? 8 : 7 ?>" class="sds-empty">Tidak ada data siswa.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
  function goBackOrRedirect() {
    if (document.referrer) {
      history.back();
    } else {
      window.location.href = 'jurusan';
    }
  }
</script>
