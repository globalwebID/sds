<?php
// pages/students_import.php
// Import Excel (.xlsx) -> UPSERT pendaftaran_siswa (SDS) + sinkron siswa_kelas
// Setelah commit SDS:
//   - sinkronkan data operasional ke aplikasi Absensi bila koneksinya tersedia;
//   - hubungkan siswa ke keanggotaan Perpustakaan internal SDS;
//   - simpan RFID pada kartu_rfid sebagai sumber kartu terpusat.
// Tidak ada lagi koneksi, kodeapp, atau sinkronisasi ke database Perpustakaan lama.

require_once __DIR__ . '/../../config/runtime.php';
sds_session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login'); exit; }
require_once __DIR__ . '/../../config/perpus.php';

function back_students(): void { header("Location: index?page=students"); exit; }
function fail(string $msg): void { $_SESSION['error'] = $msg; back_students(); }
function ok(string $msg): void { $_SESSION['success'] = $msg; back_students(); }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// =====================================================
// KONFIG DB (EDIT INI)
// =====================================================
$mainCfg = sds_database_config('main');
$absenCfg = sds_database_config('attendance');
$DBH = (string)$mainCfg['host'];

// SDS (source utama) -> pakai koneksi EXISTING dari aplikasi Anda: $conn
$DB_NAME_SDS = (string)$mainCfg['database'];

// Absensi
$ABSEN_DB   = (string)($absenCfg['database'] ?? '');
$ABSEN_USER = (string)($absenCfg['username'] ?? '');
$ABSEN_PASS = (string)($absenCfg['password'] ?? '');


// =====================================================
// VALIDASI DAN BACA FILE EXCEL
// =====================================================
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') fail('Metode tidak valid.');
if (!sds_csrf_verify((string)($_POST['csrf'] ?? ''))) fail('Sesi formulir tidak valid. Muat ulang halaman.');

$upload = $_FILES['excel'] ?? null;
if (!is_array($upload)) fail('Pilih file Excel yang akan diimpor.');
if (($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
  fail('Upload Excel gagal. Pastikan file sudah dipilih dan ukurannya tidak melebihi batas server.');
}

$origName = (string)($upload['name'] ?? 'data.xlsx');
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if ($ext !== 'xlsx') fail('File import harus berformat Excel .xlsx.');

$tmpPath = (string)($upload['tmp_name'] ?? '');
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) fail('File upload tidak valid.');
if ((int)($upload['size'] ?? 0) > 15 * 1024 * 1024) fail('Ukuran file Excel maksimal 15 MB.');
$mime = (string)(new finfo(FILEINFO_MIME_TYPE))->file($tmpPath);
if (!in_array($mime, ['application/zip','application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'], true)) {
  fail('Isi file bukan dokumen Excel .xlsx yang valid.');
}

try {
  $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
  $reader->setReadDataOnly(false);
  $spreadsheet = $reader->load($tmpPath);
  $worksheet = $spreadsheet->getSheetByName('IMPORT_SISWA') ?: $spreadsheet->getActiveSheet();

  $highestRow = (int)$worksheet->getHighestDataRow();
  $highestColumn = (string)$worksheet->getHighestDataColumn();
  if ($highestRow < 1) fail('Sheet import kosong.');
  if ($highestRow > 10001) fail('Maksimal 10.000 baris data dalam satu kali import.');

  $excelRows = $worksheet->rangeToArray(
    'A1:' . $highestColumn . $highestRow,
    null,
    false,
    true,
    false
  );
  foreach ($excelRows as $excelRowNumber => $excelRow) {
    foreach ((array)$excelRow as $excelValue) {
      if (is_string($excelValue) && str_starts_with(ltrim($excelValue), '=')) {
        fail('Formula Excel tidak diizinkan pada file import (baris ' . ($excelRowNumber + 1) . '). Ubah hasil formula menjadi nilai biasa.');
      }
    }
  }
} catch (Throwable $e) {
  fail('File Excel tidak dapat dibaca: ' . $e->getMessage());
}

// =====================================================
// HELPER
// =====================================================
$nullify = function($v) {
  if ($v === null) return null;
  $v = trim((string)$v);
  if ($v === '' || strtolower($v) === 'null') return null;
  return $v;
};

$isPlaceholder = function(?string $v): bool {
  if ($v === null) return false;
  $t = trim($v);
  return $t === '' || stripos($t, '-- pilih') === 0;
};

$asInt = function($v): ?int {
  $v = trim((string)$v);
  if ($v === '' || strtolower($v) === 'null') return null;
  if (!is_numeric($v)) return null;
  return (int)$v;
};

$asTinyInt = function($v) use ($asInt): ?int {
  $i = $asInt($v);
  if ($i === null) return null;
  return $i ? 1 : 0;
};

$asDecimalString = function($v): ?string {
  $v = trim((string)$v);
  if ($v === '' || strtolower($v) === 'null') return null;
  $v = str_replace(',', '.', $v);
  if (!is_numeric($v)) return null;
  return $v;
};

$asDate = function($v): ?string {
  if ($v instanceof DateTimeInterface) return $v->format('Y-m-d');

  $raw = trim((string)$v);
  if ($raw === '' || strtolower($raw) === 'null' || $raw === '0000-00-00') return null;

  if (is_numeric($raw) && (float)$raw > 1000 && (float)$raw < 100000) {
    try {
      return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float)$raw)->format('Y-m-d');
    } catch (Throwable $e) {
      return null;
    }
  }

  foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $format) {
    $date = DateTimeImmutable::createFromFormat('!' . $format, $raw);
    if ($date && $date->format($format) === $raw) return $date->format('Y-m-d');
  }
  return null;
};

$asSafeDateForAbsen = function($v) use ($asDate): string {
  return $asDate($v) ?? '2000-01-01';
};

$norm = function(?string $s): string {
  $s = trim((string)$s);
  $s = preg_replace('/\s+/', ' ', $s);
  return mb_strtolower($s, 'UTF-8');
};

/**
 * Koneksi DB dengan collation per database (penting untuk stabil).
 */
function db_connect(string $host, string $user, string $pass, string $db, string $collation): mysqli {
  $m = new mysqli($host, $user, $pass, $db);
  if ($m->connect_errno) throw new Exception("Koneksi DB gagal ({$db}): ".$m->connect_error);
  $m->set_charset('utf8mb4');
  $m->query("SET NAMES utf8mb4 COLLATE {$collation}");
  $m->query("SET collation_connection = '{$collation}'");
  return $m;
}

$mapGenderPerpus = function(?string $jk) use ($norm): ?string {
  if ($jk === null) return null;
  $t = $norm($jk);
  if ($t === '') return null;
  if (str_contains($t, 'laki') || str_contains($t, 'pria') || $t === 'l') return 'l';
  if (str_contains($t, 'perem') || $t === 'p') return 'p';
  return null;
};

$mapActiveAbsensi = function(?int $statusAktif, ?int $blokir): string {
  $aktif = ($statusAktif === null) ? 1 : (int)$statusAktif;
  $blk   = ($blokir === null) ? 0 : (int)$blokir;
  return ($aktif === 1 && $blk === 0) ? 'Y' : 'N';
};

function fmt_result(
  int $rows, int $inserted, int $updated, int $skipped,
  int $conflictNipd, int $conflictRfid,
  int $mapKelasOk, int $mapKelasFail,
  int $mapSkOk, int $mapSkSkip,
  int $syncAbsenOk, int $syncPerpusOk
): string {
  $lines = [
    "Impor selesai: total {$rows} baris.",
    "Insert: {$inserted}. Update: {$updated}.",
    "Skip: {$skipped}.",
    "Konflik NIPD: {$conflictNipd}.",
    "Konflik RFID: {$conflictRfid}.",
    "Map kelas OK: {$mapKelasOk}, gagal: {$mapKelasFail}.",
    "siswa_kelas OK: {$mapSkOk}, skip: {$mapSkSkip}.",
    "SYNC Absensi OK: {$syncAbsenOk}. Anggota Perpustakaan terhubung: {$syncPerpusOk}."
  ];
  return implode("\n", $lines);
}

// =====================================================
// TEMUKAN HEADER EXCEL
// Template resmi memakai header teknis pada baris 5, tetapi importer tetap
// mencari otomatis pada 15 baris pertama agar file lebih toleran.
// =====================================================
$normalizeHeader = static function($value): string {
  $value = preg_replace('/^\xEF\xBB\xBF/', '', trim((string)$value));
  $value = mb_strtolower($value, 'UTF-8');
  $value = preg_replace('/[^a-z0-9]+/u', '_', $value);
  return trim((string)$value, '_');
};

$headerRowIndex = null;
$header = [];
$scanLimit = min(15, count($excelRows));
for ($i = 0; $i < $scanLimit; $i++) {
  $candidate = array_map($normalizeHeader, (array)$excelRows[$i]);
  if (in_array('nisn', $candidate, true) && in_array('nama_lengkap', $candidate, true)) {
    $headerRowIndex = $i;
    $header = $candidate;
    break;
  }
}
if ($headerRowIndex === null) {
  fail('Header Excel tidak ditemukan. Gunakan template resmi dan jangan mengubah baris header.');
}

$cols = $header;
$idx = [];
foreach ($cols as $position => $column) {
  if ($column !== '' && !isset($idx[$column])) $idx[$column] = $position;
}
if (!isset($idx['nisn']) || !isset($idx['nama_lengkap'])) {
  fail('Excel harus memiliki kolom nisn dan nama_lengkap.');
}

$dataRows = array_slice($excelRows, $headerRowIndex + 1);
$get = function(array $row, string $col) use ($idx) {
  if (!isset($idx[$col])) return null;
  $i = $idx[$col];
  return array_key_exists($i, $row) ? trim((string)$row[$i]) : null;
};

// =====================================================
// CACHE MAPPING SDS: kelas/jurusan
// =====================================================
$kelasMapByYear = [];
$kelasMapNoYear = [];
$qk = $conn->query("SELECT id, nama_kelas, tahun_ajaran, jurusan_id FROM `{$DB_NAME_SDS}`.`kelas`");
while ($r = $qk->fetch_assoc()) {
  $nk = $norm($r['nama_kelas'] ?? '');
  $th = trim((string)($r['tahun_ajaran'] ?? ''));
  $rowx = ['id'=>(int)$r['id'], 'jurusan_id'=>(int)$r['jurusan_id'], 'tahun_ajaran'=>$th];
  $kelasMapNoYear[$nk] = $rowx;
  $kelasMapByYear[$th.'|'.$nk] = $rowx;
}

$jurusanMap = [];
$qj = $conn->query("SELECT id, nama_jurusan FROM `{$DB_NAME_SDS}`.`jurusan`");
while ($r = $qj->fetch_assoc()) {
  $nj = $norm($r['nama_jurusan'] ?? '');
  if ($nj !== '') $jurusanMap[$nj] = (int)$r['id'];
}

$hasNamaKelas   = isset($idx['nama_kelas']);
$hasNamaJurusan = isset($idx['nama_jurusan']);

// =====================================================
// PREPARED STATEMENTS SDS
// =====================================================
// Pastikan tabel identitas/RFID terpusat tersedia sebelum validasi import.
sds_perpus_ensure_schema($conn);

$stmtFind = $conn->prepare("SELECT id, tahun_ajaran FROM `{$DB_NAME_SDS}`.`pendaftaran_siswa` WHERE nisn=? LIMIT 1");
$stmtNipdConflict = $conn->prepare("SELECT 1 FROM `{$DB_NAME_SDS}`.`pendaftaran_siswa` WHERE nipd=? AND id<>? LIMIT 1");
$stmtRfidConflict = $conn->prepare("SELECT 1 FROM `{$DB_NAME_SDS}`.`pendaftaran_siswa` WHERE rfid_uid=? AND id<>? LIMIT 1");
$stmtRfidCentralConflict = $conn->prepare("SELECT pemilik_tipe,pemilik_id FROM `{$DB_NAME_SDS}`.`kartu_rfid` WHERE uid=? AND NOT (pemilik_tipe='siswa' AND pemilik_id=?) LIMIT 1");

$stmtSkFind = $conn->prepare("SELECT id FROM `{$DB_NAME_SDS}`.`siswa_kelas` WHERE siswa_id=? AND tahun_ajaran=? LIMIT 1");
$stmtSkUpd  = $conn->prepare("UPDATE `{$DB_NAME_SDS}`.`siswa_kelas` SET kelas_id=? WHERE id=?");
$stmtSkIns  = $conn->prepare("INSERT INTO `{$DB_NAME_SDS}`.`siswa_kelas` (siswa_id, kelas_id, tahun_ajaran, naik_kelas) VALUES (?,?,?,1)");

// whitelist kolom SDS
$allowedCols = [
  'nama_lengkap','email','sekolah_asal','nomor_ijazah','jenis_kelamin','tempat_lahir',
  'no_kk','nik','no_registrasi_akta','agama','alamat','desa','kecamatan','kota','provinsi',
  'tempat_tinggal','moda_transportasi','hobi','cita_cita',
  'nama_ayah','nik_ayah','pendidikan_ayah','pekerjaan_ayah',
  'nama_ibu','nik_ibu','pendidikan_ibu','pekerjaan_ibu',
  'nohp_siswa','file_ijazah','file_akta',
  'pin','nipd','kebutuhan_khusus','nomor_kip','nomor_kps','nomor_pkh','nomor_kks','nomor_kis',
  'file_kip','file_kps','file_pkh','file_kks','file_kis',
  'penghasilan_ayah','penghasilan_ibu',
  'nama_wali','nik_wali','pendidikan_wali','pekerjaan_wali','penghasilan_wali',
  'nohp_ortu','file_kk','foto','tahun_ajaran','alasan_nonaktif','rfid_uid'
];
$intCols  = ['anak_ke','jumlah_saudara_kandung','tinggi_badan','berat_badan','tahun_lahir_ayah','tahun_lahir_ibu','tahun_lahir_wali','kelas_id','jurusan_id','saldo'];
$tinyCols = ['pernyataan_setuju','sudah_dapodik','status_aktif','blokir'];
$decCols  = ['latitude','longitude'];
$dateCols = ['tanggal_lahir'];


// Nilai aman untuk kolom database lama yang masih NOT NULL tanpa default.
// Data wajib utama tetap NISN dan nama_lengkap; data lain dapat dilengkapi kemudian.
$insertStringDefaults = array_fill_keys([
  'sekolah_asal','nomor_ijazah','jenis_kelamin','tempat_lahir','no_kk','nik',
  'no_registrasi_akta','agama','alamat','desa','kecamatan','kota','provinsi',
  'tempat_tinggal','moda_transportasi','hobi','cita_cita','nama_ayah','nik_ayah',
  'pendidikan_ayah','pekerjaan_ayah','nama_ibu','nik_ibu','pendidikan_ibu',
  'pekerjaan_ibu','nohp_siswa','file_ijazah'
], '');
$insertIntDefaults = array_fill_keys([
  'anak_ke','jumlah_saudara_kandung','tinggi_badan','berat_badan',
  'tahun_lahir_ayah','tahun_lahir_ibu','saldo'
], 0);
$insertDecimalDefaults = ['latitude' => '0', 'longitude' => '0'];
$insertDateDefaults = ['tanggal_lahir' => '2000-01-01'];
$insertTinyDefaults = [
  'status_aktif' => 1, 'blokir' => 0, 'sudah_dapodik' => 0, 'pernyataan_setuju' => 0
];

// build UPDATE dinamis SDS
$setParts = [];
$bindTypesUpd = '';
foreach ($allowedCols as $c) { if (isset($idx[$c])) { $setParts[] = "{$c}=COALESCE(?,{$c})"; $bindTypesUpd.='s'; } }
foreach ($dateCols as $c)    { if (isset($idx[$c])) { $setParts[] = "{$c}=COALESCE(?,{$c})"; $bindTypesUpd.='s'; } }
foreach ($decCols as $c)     { if (isset($idx[$c])) { $setParts[] = "{$c}=COALESCE(?,{$c})"; $bindTypesUpd.='s'; } }
foreach ($intCols as $c)     { if (isset($idx[$c])) { $setParts[] = "{$c}=COALESCE(?,{$c})"; $bindTypesUpd.='i'; } }
foreach ($tinyCols as $c)    { if (isset($idx[$c])) { $setParts[] = "{$c}=COALESCE(?,{$c})"; $bindTypesUpd.='i'; } }

if (!isset($idx['kelas_id']) && $hasNamaKelas) { $setParts[] = "kelas_id=COALESCE(?,kelas_id)"; $bindTypesUpd.='i'; }
if (!isset($idx['jurusan_id']) && ($hasNamaJurusan || $hasNamaKelas)) { $setParts[] = "jurusan_id=COALESCE(?,jurusan_id)"; $bindTypesUpd.='i'; }

if (!count($setParts)) fail('Excel tidak memiliki kolom yang dapat diproses.');

$stmtUpd = $conn->prepare("UPDATE `{$DB_NAME_SDS}`.`pendaftaran_siswa` SET ".implode(',', $setParts)." WHERE id=?");
$bindTypesUpdFinal = $bindTypesUpd.'i';

// build INSERT dinamis SDS
$insertCols = ['nisn'];
$insertTypes = 's';
$canInsertCols = array_merge($allowedCols, $dateCols, $decCols, $intCols, $tinyCols);
$canInsertCols = array_values(array_filter($canInsertCols, fn($c) => $c !== 'nisn'));

foreach ($canInsertCols as $c) {
  if (!isset($idx[$c])) continue;
  $insertCols[] = $c;
  $insertTypes .= (in_array($c, $intCols, true) || in_array($c, $tinyCols, true)) ? 'i' : 's';
}

$needVirtualKelas = (!isset($idx['kelas_id']) && $hasNamaKelas);
$needVirtualJur   = (!isset($idx['jurusan_id']) && ($hasNamaJurusan || $hasNamaKelas));
if ($needVirtualKelas) { $insertCols[] = 'kelas_id'; $insertTypes.='i'; }
if ($needVirtualJur)   { $insertCols[] = 'jurusan_id'; $insertTypes.='i'; }

$hasStatusAktif = isset($idx['status_aktif']);
if (!$hasStatusAktif) { $insertCols[]='status_aktif'; $insertTypes.='i'; }

$ph = implode(',', array_fill(0, count($insertCols), '?'));
$stmtIns = $conn->prepare("INSERT INTO `{$DB_NAME_SDS}`.`pendaftaran_siswa` (".implode(',', $insertCols).") VALUES ($ph)");

// =====================================================
// PROSES IMPORT SDS
// =====================================================
$rows=0; $inserted=0; $updated=0; $skipped=0; $conflictNipd=0; $conflictRfid=0;
$mapKelasOk=0; $mapKelasFail=0; $mapSkOk=0; $mapSkSkip=0;

$affectedIds = [];

$conn->begin_transaction();

try {
  foreach ($dataRows as $row) {
    $hasValue = false;
    foreach ((array)$row as $cellValue) {
      if (trim((string)$cellValue) !== '') { $hasValue = true; break; }
    }
    if (!$hasValue) continue;

    $nisn = $nullify($get((array)$row,'nisn'));
    if ($nisn && stripos($nisn, 'CONTOH-') === 0) continue;

    if ($rows >= 10000) throw new RuntimeException('Maksimal 10.000 baris data dalam satu kali import.');
    $rows++;
    if (!$nisn) { $skipped++; continue; }

    $stmtFind->bind_param("s", $nisn);
    $stmtFind->execute();
    $res = $stmtFind->get_result();
    $found = $res ? $res->fetch_assoc() : null;

    $id = $found ? (int)$found['id'] : 0;
    $tahunSiswaDb = $found ? $nullify($found['tahun_ajaran'] ?? null) : null;

    // mapping kelas/jurusan dari nama
    $kelasIdFromName = null; $jurusanIdFromName = null; $tahunForKelas = null;
    $namaKelasExcel = $nullify($get($row,'nama_kelas'));
    $tahunExcelInput = $nullify($get($row,'tahun_ajaran'));
    $tahunExcel     = $tahunExcelInput ?? (string)($tahunAjaran ?? '');
    $namaJurExcel   = $nullify($get($row,'nama_jurusan'));

    if ($namaKelasExcel) {
      $nk = $norm($namaKelasExcel);
      if ($tahunExcel) {
        $key = $tahunExcel.'|'.$nk;
        if (isset($kelasMapByYear[$key])) {
          $kelasIdFromName = $kelasMapByYear[$key]['id'];
          $jurusanIdFromName = $kelasMapByYear[$key]['jurusan_id'] ?: null;
          $tahunForKelas = $kelasMapByYear[$key]['tahun_ajaran'] ?: $tahunExcel;
          $mapKelasOk++;
        } elseif (isset($kelasMapNoYear[$nk])) {
          $kelasIdFromName = $kelasMapNoYear[$nk]['id'];
          $jurusanIdFromName = $kelasMapNoYear[$nk]['jurusan_id'] ?: null;
          $tahunForKelas = $kelasMapNoYear[$nk]['tahun_ajaran'] ?: $tahunExcel;
          $mapKelasOk++;
        } else $mapKelasFail++;
      } else {
        if (isset($kelasMapNoYear[$nk])) {
          $kelasIdFromName = $kelasMapNoYear[$nk]['id'];
          $jurusanIdFromName = $kelasMapNoYear[$nk]['jurusan_id'] ?: null;
          $tahunForKelas = $kelasMapNoYear[$nk]['tahun_ajaran'] ?: null;
          $mapKelasOk++;
        } else $mapKelasFail++;
      }
    }
    if ($namaJurExcel) {
      $nj = $norm($namaJurExcel);
      if (isset($jurusanMap[$nj])) $jurusanIdFromName = (int)$jurusanMap[$nj];
    }

    $tahunSk = $tahunExcel ?? $tahunForKelas ?? $tahunSiswaDb;

    $valOf = function(string $c) use ($get,$row,$nullify,$isPlaceholder,$asDate,$asDecimalString,$asInt,$asTinyInt,$dateCols,$decCols,$intCols,$tinyCols) {
      $raw = $nullify($get($row,$c));
      if ($isPlaceholder($raw)) $raw = null;
      if (in_array($c,$dateCols,true)) return $asDate($raw);
      if (in_array($c,$decCols,true))  return $asDecimalString($raw);
      if (in_array($c,$intCols,true))  return $asInt($raw);
      if (in_array($c,$tinyCols,true)) return $asTinyInt($raw);
      return $raw;
    };

    if ($found) {
      // UPDATE SDS
      $vals = [];

      foreach ($allowedCols as $c) {
        if (!isset($idx[$c])) continue;
        $v = $valOf($c);

        if ($c === 'nipd' && $v !== null) {
          $stmtNipdConflict->bind_param("si", $v, $id);
          $stmtNipdConflict->execute();
          $r = $stmtNipdConflict->get_result();
          if ($r && $r->fetch_row()) { $v = null; $conflictNipd++; }
        }
        if ($c === 'rfid_uid' && $v !== null) {
          $rfidBentrok = false;
          $stmtRfidConflict->bind_param("si", $v, $id);
          $stmtRfidConflict->execute();
          $r = $stmtRfidConflict->get_result();
          if ($r && $r->fetch_row()) $rfidBentrok = true;

          // Cek lintas pemilik: UID pegawai dan UID siswa lain juga tidak boleh sama.
          if (!$rfidBentrok) {
            $stmtRfidCentralConflict->bind_param("si", $v, $id);
            $stmtRfidCentralConflict->execute();
            $rCentral = $stmtRfidCentralConflict->get_result();
            if ($rCentral && $rCentral->fetch_row()) $rfidBentrok = true;
          }
          if ($rfidBentrok) { $v = null; $conflictRfid++; }
        }

        $vals[] = $v;
      }

      foreach ($dateCols as $c) { if (isset($idx[$c])) $vals[] = $valOf($c); }
      foreach ($decCols as $c)  { if (isset($idx[$c])) $vals[] = $valOf($c); }
      foreach ($intCols as $c)  { if (isset($idx[$c])) $vals[] = $valOf($c); }
      foreach ($tinyCols as $c) { if (isset($idx[$c])) $vals[] = $valOf($c); }

      if ($needVirtualKelas) $vals[] = $kelasIdFromName;
      if ($needVirtualJur)   $vals[] = $jurusanIdFromName;

      $anyNotNull = false;
      foreach ($vals as $v) { if ($v !== null) { $anyNotNull = true; break; } }

      if (!$anyNotNull) {
        // tetap ikut SYNC (biar kelas Absensi bisa dibetulkan)
        $skipped++;
        $affectedIds[$id] = true;
      } else {
        $vals[] = $id;

        $refs = [];
        foreach ($vals as $k => $v) { $refs[$k] = &$vals[$k]; }

        $stmtUpd->bind_param($bindTypesUpdFinal, ...$refs);
        $stmtUpd->execute();

        $updated++;
        $affectedIds[$id] = true;
      }

      // sinkron siswa_kelas
      $kelasIdNumeric = isset($idx['kelas_id']) ? $asInt($get($row,'kelas_id')) : null;
      $kelasTarget = $kelasIdNumeric ?? $kelasIdFromName;

      if ($kelasTarget && $tahunSk) {
        $stmtSkFind->bind_param("is", $id, $tahunSk);
        $stmtSkFind->execute();
        $rsk = $stmtSkFind->get_result();
        $skFound = $rsk ? $rsk->fetch_assoc() : null;

        if ($skFound) {
          $skId = (int)$skFound['id'];
          $stmtSkUpd->bind_param("ii", $kelasTarget, $skId);
          $stmtSkUpd->execute();
          $mapSkOk++;
        } else {
          $stmtSkIns->bind_param("iis", $id, $kelasTarget, $tahunSk);
          $stmtSkIns->execute();
          $mapSkOk++;
        }
      } else $mapSkSkip++;

    } else {
      // INSERT SDS
      $vals = [];
      $vals[] = $nisn;

      foreach (array_slice($insertCols, 1) as $c) {
        if ($c === 'kelas_id' && $needVirtualKelas) { $vals[] = $kelasIdFromName; continue; }
        if ($c === 'jurusan_id' && $needVirtualJur) { $vals[] = $jurusanIdFromName; continue; }
        if ($c === 'status_aktif' && !$hasStatusAktif) { $vals[] = 1; continue; }

        $v = $valOf($c);

        if ($c === 'nipd' && $v !== null) {
          $zero = 0;
          $stmtNipdConflict->bind_param("si", $v, $zero);
          $stmtNipdConflict->execute();
          $r = $stmtNipdConflict->get_result();
          if ($r && $r->fetch_row()) { $v = null; $conflictNipd++; }
        }
        if ($c === 'rfid_uid' && $v !== null) {
          $zero = 0;
          $rfidBentrok = false;
          $stmtRfidConflict->bind_param("si", $v, $zero);
          $stmtRfidConflict->execute();
          $r = $stmtRfidConflict->get_result();
          if ($r && $r->fetch_row()) $rfidBentrok = true;

          if (!$rfidBentrok) {
            $stmtRfidCentralConflict->bind_param("si", $v, $zero);
            $stmtRfidCentralConflict->execute();
            $rCentral = $stmtRfidCentralConflict->get_result();
            if ($rCentral && $rCentral->fetch_row()) $rfidBentrok = true;
          }
          if ($rfidBentrok) { $v = null; $conflictRfid++; }
        }

        if ($c === 'email' && ($v === null || $v === '')) $v = $nisn.'@smkn1.local';
        if ($c === 'tahun_ajaran' && ($v === null || $v === '')) $v = (string)($tahunAjaran ?? '');
        if ($v === null && array_key_exists($c, $insertStringDefaults)) $v = $insertStringDefaults[$c];
        if ($v === null && array_key_exists($c, $insertIntDefaults)) $v = $insertIntDefaults[$c];
        if ($v === null && array_key_exists($c, $insertDecimalDefaults)) $v = $insertDecimalDefaults[$c];
        if ($v === null && array_key_exists($c, $insertDateDefaults)) $v = $insertDateDefaults[$c];
        if ($v === null && array_key_exists($c, $insertTinyDefaults)) $v = $insertTinyDefaults[$c];
        $vals[] = $v;
      }

      $nama = isset($idx['nama_lengkap']) ? $nullify($get($row,'nama_lengkap')) : null;
      if (!$nama) { $skipped++; continue; }

      $refs = [];
      foreach ($vals as $k => $v) { $refs[$k] = &$vals[$k]; }

      $stmtIns->bind_param($insertTypes, ...$refs);
      $stmtIns->execute();

      $newId = (int)$conn->insert_id;
      $inserted++;
      $affectedIds[$newId] = true;

      // siswa_kelas insert
      $kelasIdNumeric = isset($idx['kelas_id']) ? $asInt($get($row,'kelas_id')) : null;
      $kelasTarget = $kelasIdNumeric ?? $kelasIdFromName;
      $tahunSkIns = $tahunSk ?? $tahunExcel ?? null;

      if ($kelasTarget && $tahunSkIns) {
        $stmtSkIns->bind_param("iis", $newId, $kelasTarget, $tahunSkIns);
        $stmtSkIns->execute();
        $mapSkOk++;
      } else $mapSkSkip++;
    }
  }

  if ($rows < 1) throw new RuntimeException('Tidak ada baris data yang dapat diproses.');
  $conn->commit();

} catch (Throwable $e) {
  $conn->rollback();
  fail("Impor SDS gagal: ".$e->getMessage());
}

// =====================================================
// SINKRON ABSENSI + IDENTITAS PERPUSTAKAAN INTERNAL SDS
// =====================================================
$syncAbsenOk = 0;
$syncPerpusOk = 0;
$conn_absen = null;

try {
  sds_perpus_ensure_schema($conn);

  $syncAbsensiEnabled = $ABSEN_DB !== '' && $ABSEN_USER !== '';
  if ($syncAbsensiEnabled) {
    $conn_absen = db_connect($DBH, $ABSEN_USER, $ABSEN_PASS, $ABSEN_DB, 'utf8mb4_unicode_ci');
    $conn_absen->query("SET @SYNC_LOCK='IMPORT_SDS'");

    $stmtAbsenFind = $conn_absen->prepare("SELECT `user_id` FROM `user` WHERE BINARY `nisn` = BINARY ? LIMIT 1");
    $stmtAbsenIns = $conn_absen->prepare("
      INSERT INTO `user`
        (`nisn`,`rfid`,`email`,`password`,`nama_lengkap`,`tempat_lahir`,`tanggal_lahir`,`jenis_kelamin`,`kelas`,`tahun_ajaran`,`alamat`,`telp`,`avatar`,`tanggal_registrasi`,`status`,`active`)
      VALUES
        (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),'Offline',?)
    ");
    $stmtAbsenUpd = $conn_absen->prepare("
      UPDATE `user` SET
        `rfid`          = COALESCE(?, `rfid`),
        `email`         = COALESCE(?, `email`),
        `nama_lengkap`  = COALESCE(?, `nama_lengkap`),
        `tempat_lahir`  = COALESCE(?, `tempat_lahir`),
        `tanggal_lahir` = COALESCE(?, `tanggal_lahir`),
        `jenis_kelamin` = COALESCE(?, `jenis_kelamin`),
        `kelas`         = ?,
        `tahun_ajaran`  = COALESCE(?, `tahun_ajaran`),
        `alamat`        = COALESCE(?, `alamat`),
        `telp`          = COALESCE(?, `telp`),
        `active`        = COALESCE(?, `active`)
      WHERE `user_id` = ?
    ");
  }

  if (!empty($affectedIds)) {
    $ids = array_map('intval', array_keys($affectedIds));
    foreach (array_chunk($ids, 400) as $chunk) {
      $in = implode(',', $chunk);
      $q = $conn->query("
        SELECT
          p.id,p.nisn,p.rfid_uid,p.pin,p.email,p.nama_lengkap,p.tempat_lahir,
          p.tanggal_lahir,p.jenis_kelamin,p.alamat,p.nohp_siswa,p.tahun_ajaran,
          p.status_aktif,p.blokir,
          COALESCE(kp.nama_kelas,ksk.nama_kelas,'-') AS nama_kelas
        FROM pendaftaran_siswa p
        LEFT JOIN siswa_kelas sk
               ON sk.id=(SELECT sk2.id FROM siswa_kelas sk2 WHERE sk2.siswa_id=p.id ORDER BY sk2.tahun_ajaran DESC,sk2.id DESC LIMIT 1)
        LEFT JOIN kelas kp ON kp.id=p.kelas_id
        LEFT JOIN kelas ksk ON ksk.id=sk.kelas_id
        WHERE p.id IN ($in)
      ");

      while ($s = $q->fetch_assoc()) {
        $studentId = (int)$s['id'];
        $nisn = trim((string)($s['nisn'] ?? ''));
        if ($studentId <= 0 || $nisn === '') continue;

        $rfid = $nullify($s['rfid_uid'] ?? null);

        // RFID dan keanggotaan Perpustakaan tetap berada pada database SDS.
        if ($rfid !== null && $rfid !== '') {
          sds_rfid_assign(
            $conn,
            'siswa',
            $studentId,
            $rfid,
            (int)($_SESSION['admin_id'] ?? 0),
            'Disinkronkan dari Import Peserta Didik'
          );
        }
        sds_perpus_ensure_member($conn, 'siswa', $studentId, true);
        $syncPerpusOk++;

        if (!$syncAbsensiEnabled) continue;

        $email = $nullify($s['email'] ?? null);
        if ($email === null || $email === '') $email = $nisn . '@smkn1.local';
        $pin = $nullify($s['pin'] ?? null);
        $plain = ($pin && preg_match('/^\d{4,10}$/', $pin)) ? $pin : $nisn;
        $passHash = password_hash($plain, PASSWORD_BCRYPT);
        $nama = $nullify($s['nama_lengkap'] ?? null);
        $tmpl = $nullify($s['tempat_lahir'] ?? null);
        $tgll = $asSafeDateForAbsen($s['tanggal_lahir'] ?? '');
        $jk = $nullify($s['jenis_kelamin'] ?? null);
        $alamat = $nullify($s['alamat'] ?? null);
        $telp = $nullify($s['nohp_siswa'] ?? null);
        $thAj = $nullify($s['tahun_ajaran'] ?? null);
        $kelasName = trim((string)($s['nama_kelas'] ?? '-'));
        if ($kelasName === '' || $kelasName === '0') $kelasName = '-';
        $active = $mapActiveAbsensi(
          isset($s['status_aktif']) ? (int)$s['status_aktif'] : null,
          isset($s['blokir']) ? (int)$s['blokir'] : null
        );

        $stmtAbsenFind->bind_param('s', $nisn);
        $stmtAbsenFind->execute();
        $absenUserId = (int)($stmtAbsenFind->get_result()->fetch_assoc()['user_id'] ?? 0);
        if ($absenUserId > 0) {
          $stmtAbsenUpd->bind_param(
            'sssssssssssi',
            $rfid,$email,$nama,$tmpl,$tgll,$jk,$kelasName,$thAj,$alamat,$telp,$active,$absenUserId
          );
          $stmtAbsenUpd->execute();
        } else {
          $avatar = null;
          $stmtAbsenIns->bind_param(
            'ssssssssssssss',
            $nisn,$rfid,$email,$passHash,$nama,$tmpl,$tgll,$jk,$kelasName,$thAj,$alamat,$telp,$avatar,$active
          );
          $stmtAbsenIns->execute();
        }
        $syncAbsenOk++;
      }
    }
  }

  if ($conn_absen instanceof mysqli) {
    $conn_absen->query("SET @SYNC_LOCK=''");
    $conn_absen->close();
  }
} catch (Throwable $e) {
  try {
    if ($conn_absen instanceof mysqli) {
      $conn_absen->query("SET @SYNC_LOCK=''");
      $conn_absen->close();
    }
  } catch (Throwable $ignored) {}
  fail('Impor SDS sukses, tetapi sinkronisasi lanjutan gagal: ' . $e->getMessage());
}

$msg = fmt_result(
  $rows, $inserted, $updated, $skipped,
  $conflictNipd, $conflictRfid,
  $mapKelasOk, $mapKelasFail,
  $mapSkOk, $mapSkSkip,
  $syncAbsenOk, $syncPerpusOk
);

ok(nl2br($msg));
