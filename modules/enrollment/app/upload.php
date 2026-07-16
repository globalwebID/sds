<?php
// upload.php - versi aman untuk submit formulir daftar ulang.
// Fokus perbaikan:
// 1) Tidak generate NIPD otomatis dulu (NIPD disimpan NULL).
// 2) Aman jika form masih mengirim id jurusan tahun lama: dimapping ke jurusan tahun aktif.
// 3) Pilih kelas X berdasarkan isi aktual dari siswa_kelas, bukan hanya kelas.terisi.
// 4) Hindari bentrok function dengan siteman/fungsi.php.
// 5) WA/catat log tidak boleh membuat submit gagal.

mysqli_report(MYSQLI_REPORT_OFF);

require dirname(__DIR__, 3) . '/db.php';
require sds_root_path('siteman/fungsi.php');

if (!isset($baseUrl) || trim((string)$baseUrl) === '') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'smkn1probolinggo.sch.id';
    $baseUrl = $scheme . '://' . $host . '/sds/';
}

// Tahun ajaran aktif berasal dari master Tahun Ajaran SDS melalui db.php.
$tahunAjaran = (string)($tahunAjaran ?? '');
if ($tahunAjaran === '') {
    die('Tahun ajaran aktif belum ditetapkan. Hubungi operator SDS.');
}

function sds_upload_post($key, $default = null) {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

function sds_upload_format_nomor($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', (string)$nomor);
    if ($nomor === '') return '';
    return (substr($nomor, 0, 1) === '0') ? '62' . substr($nomor, 1) : $nomor;
}

function sds_upload_getStatusKirimPesan($conn) {
    $result = $conn->query("SELECT kirim_pesan FROM formulir WHERE id = 1 LIMIT 1");
    if ($result && $row = $result->fetch_assoc()) {
        return (int)$row['kirim_pesan'] === 1;
    }
    return false;
}

function sds_uploadFile($field, $allowed, $maxMB = 10, $subfolder = '') {
    if (!isset($_FILES[$field]) || (int)$_FILES[$field]['error'] === 4) {
        return null;
    }

    $f = $_FILES[$field];
    if ((int)$f['error'] !== 0) {
        die('Gagal unggah ' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . '. Kode error: ' . (int)$f['error']);
    }

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        die('Format file ' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . ' tidak diizinkan');
    }

    if (($f['size'] / (1024 * 1024)) > $maxMB) {
        die('Ukuran file ' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . ' > ' . (int)$maxMB . ' MB');
    }

    $mimeByExtension = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string)$finfo->file($f['tmp_name']);
    if (!isset($mimeByExtension[$ext]) || !in_array($mime, $mimeByExtension[$ext], true)) {
        die('Isi file ' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8') . ' tidak sesuai dengan format yang diizinkan');
    }

    $subfolder = trim((string)$subfolder, '/\\');
    $subfolder = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $subfolder);
    if ($subfolder === '') $subfolder = 'umum';

    $dir = sds_root_path('uploads/' . $subfolder) . '/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        die('Folder upload tidak bisa dibuat: ' . htmlspecialchars($dir, ENT_QUOTES, 'UTF-8'));
    }

    $cleanName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', basename($f['name']));
    $filename = uniqid('', true) . '_' . $cleanName;
    $path = $dir . $filename;

    if (!move_uploaded_file($f['tmp_name'], $path)) {
        die('Gagal unggah ' . htmlspecialchars($field, ENT_QUOTES, 'UTF-8'));
    }

    return $subfolder . '/' . $filename;
}

function sds_upload_map_jurusan_aktif($conn, $jurusanIdInput, $tahunAjaran) {
    $jurusanIdInput = (int)$jurusanIdInput;
    if ($jurusanIdInput <= 0) return null;

    $data = null;

    $stmt = $conn->prepare("SELECT id, kode_jurusan, nama_jurusan FROM jurusan WHERE id = ? AND BINARY tahun_ajaran = BINARY ? LIMIT 1");
    if (!$stmt) die('Gagal menyiapkan query jurusan aktif: ' . $conn->error);
    $stmt->bind_param('is', $jurusanIdInput, $tahunAjaran);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) $data = $row;
    $stmt->close();

    if ($data) return $data;

    // Jika ID yang dikirim adalah jurusan tahun lama, cari jurusan aktif dengan kode/nama yang sama.
    $stmt = $conn->prepare("SELECT kode_jurusan, nama_jurusan FROM jurusan WHERE id = ? LIMIT 1");
    if (!$stmt) die('Gagal menyiapkan query jurusan asal: ' . $conn->error);
    $stmt->bind_param('i', $jurusanIdInput);
    $stmt->execute();
    $res = $stmt->get_result();
    $old = ($res && $row = $res->fetch_assoc()) ? $row : null;
    $stmt->close();

    if (!$old) return null;

    $kode = trim((string)($old['kode_jurusan'] ?? ''));
    $nama = trim((string)($old['nama_jurusan'] ?? ''));

    $stmt = $conn->prepare("
        SELECT id, kode_jurusan, nama_jurusan
        FROM jurusan
        WHERE BINARY tahun_ajaran = BINARY ?
          AND (
              (kode_jurusan <> '' AND kode_jurusan = ?)
              OR nama_jurusan = ?
          )
        ORDER BY id ASC
        LIMIT 1
    ");
    if (!$stmt) die('Gagal menyiapkan query mapping jurusan: ' . $conn->error);
    $stmt->bind_param('sss', $tahunAjaran, $kode, $nama);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $row = $res->fetch_assoc()) $data = $row;
    $stmt->close();

    return $data;
}

function sds_upload_pilih_kelas_x($conn, $jurusanId, $tahunAjaran) {
    $stmt = $conn->prepare("
        SELECT
            k.id,
            COALESCE(k.kuota,0) AS kuota,
            COUNT(DISTINCT ps.id) AS terisi_aktual
        FROM kelas k
        JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
        LEFT JOIN siswa_kelas sk
            ON sk.kelas_id = k.id
           AND BINARY sk.tahun_ajaran = BINARY k.tahun_ajaran
        LEFT JOIN pendaftaran_siswa ps
            ON ps.id = sk.siswa_id
           AND ps.status_aktif = 1
        WHERE k.jurusan_id = ?
          AND BINARY k.tahun_ajaran = BINARY ?
          AND tk.urutan_tingkat = (
              SELECT MIN(tk_awal.urutan_tingkat) FROM tingkat_kelas tk_awal
          )
        GROUP BY k.id, k.kuota
        HAVING terisi_aktual < COALESCE(k.kuota,0)
        ORDER BY terisi_aktual ASC, k.id ASC
        LIMIT 1
    ");
    if (!$stmt) die('Gagal menyiapkan query kelas: ' . $conn->error);
    $stmt->bind_param('is', $jurusanId, $tahunAjaran);
    $stmt->execute();
    $res = $stmt->get_result();
    $kelas = ($res && $row = $res->fetch_assoc()) ? $row : null;
    $stmt->close();
    return $kelas;
}

function sds_upload_sync_terisi_kelas($conn, $kelasId) {
    $stmt = $conn->prepare("
        UPDATE kelas k
        SET k.terisi = (
            SELECT COUNT(DISTINCT ps.id)
            FROM siswa_kelas sk
            JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id
            WHERE sk.kelas_id = k.id
              AND BINARY sk.tahun_ajaran = BINARY k.tahun_ajaran
              AND ps.status_aktif = 1
        )
        WHERE k.id = ?
    ");
    if ($stmt) {
        $stmt->bind_param('i', $kelasId);
        $stmt->execute();
        $stmt->close();
    }
}

// Ambil data POST
$nama_lengkap = sds_upload_post('nama_lengkap');
$nisn = sds_upload_post('nisn');
$email = sds_upload_post('email');
$sekolah_asal = sds_upload_post('sekolah_asal');
$nomor_ijazah = sds_upload_post('nomor_ijazah');
$jenis_kelamin = sds_upload_post('jenis_kelamin');
$tempat_lahir = sds_upload_post('tempat_lahir');
$tanggal_lahir = sds_upload_post('tanggal_lahir');
$no_kk = sds_upload_post('no_kk');
$nik = sds_upload_post('nik');
$no_registrasi_akta = sds_upload_post('no_registrasi_akta');
$kebutuhan_khusus = sds_upload_post('kebutuhan_khusus');
$agama = sds_upload_post('agama');
$alamat = sds_upload_post('alamat');
$desa = sds_upload_post('desa');
$kecamatan = sds_upload_post('kecamatan');
$kota = sds_upload_post('kota');
$provinsi = sds_upload_post('provinsi');
$latitude = (string)floatval(sds_upload_post('latitude', 0));
$longitude = (string)floatval(sds_upload_post('longitude', 0));
$tempat_tinggal = sds_upload_post('tempat_tinggal');
$moda_transportasi = sds_upload_post('moda_transportasi');
$anak_ke = (string)intval(sds_upload_post('anak_ke'));
$jumlah_saudara_kandung = (string)intval(sds_upload_post('jumlah_saudara_kandung'));
$tinggi_badan = (string)intval(sds_upload_post('tinggi_badan'));
$berat_badan = (string)intval(sds_upload_post('berat_badan'));
$hobi = sds_upload_post('hobi');
$cita_cita = sds_upload_post('cita_cita');
$nomor_kip = sds_upload_post('nomor_kip');
$nomor_kps = sds_upload_post('nomor_kps');
$nomor_pkh = sds_upload_post('nomor_pkh');
$nomor_kks = sds_upload_post('nomor_kks');
$nomor_kis = sds_upload_post('nomor_kis');

$nama_ayah = sds_upload_post('nama_ayah');
$nik_ayah = sds_upload_post('nik_ayah');
$tahun_lahir_ayah = (string)intval(sds_upload_post('tahun_lahir_ayah'));
$pendidikan_ayah = sds_upload_post('pendidikan_ayah');
$pekerjaan_ayah = sds_upload_post('pekerjaan_ayah');
$penghasilan_ayah = sds_upload_post('penghasilan_ayah');

$nama_ibu = sds_upload_post('nama_ibu');
$nik_ibu = sds_upload_post('nik_ibu');
$tahun_lahir_ibu = (string)intval(sds_upload_post('tahun_lahir_ibu'));
$pendidikan_ibu = sds_upload_post('pendidikan_ibu');
$pekerjaan_ibu = sds_upload_post('pekerjaan_ibu');
$penghasilan_ibu = sds_upload_post('penghasilan_ibu');

$nama_wali = sds_upload_post('nama_wali');
$nik_wali = sds_upload_post('nik_wali');
$tahun_lahir_wali_raw = sds_upload_post('tahun_lahir_wali');
$tahun_lahir_wali = ($tahun_lahir_wali_raw !== null && trim((string)$tahun_lahir_wali_raw) !== '') ? (string)intval($tahun_lahir_wali_raw) : null;
$pendidikan_wali = sds_upload_post('pendidikan_wali');
$pekerjaan_wali = sds_upload_post('pekerjaan_wali');
$penghasilan_wali = sds_upload_post('penghasilan_wali');

foreach (['pendidikan_wali','pekerjaan_wali','penghasilan_wali','penghasilan_ayah','penghasilan_ibu'] as $varName) {
    if (isset($$varName) && in_array($$varName, ['-- Pilih Pendidikan --','-- Pilih Pekerjaan --','-- Pilih Penghasilan --'], true)) {
        $$varName = null;
    }
}

$nohp_ortu = sds_upload_post('nohp_ortu');
$nohp_siswa = sds_upload_post('nohp_siswa');
$pernyataan_setuju = isset($_POST['persetujuan']) ? '1' : '0';

$jurusan_id_input = (int)sds_upload_post('jurusan_id');
$jurusanAktif = sds_upload_map_jurusan_aktif($conn, $jurusan_id_input, $tahunAjaran);
if (!$jurusanAktif || empty($jurusanAktif['id'])) {
    header('Location: formulir?error=jurusan_tahun_tidak_valid');
    exit;
}
$jurusan_id = (int)$jurusanAktif['id'];
$kode_jurusan = (string)($jurusanAktif['kode_jurusan'] ?? '');

// NIPD dikosongkan/NULL dulu saat pendaftaran. NIPD bisa diisi/generate dari admin setelah validasi data.
$nipd = null;

$kelas = sds_upload_pilih_kelas_x($conn, $jurusan_id, $tahunAjaran);
if (!$kelas || empty($kelas['id'])) {
    header('Location: formulir?error=kelas_penuh');
    exit;
}
$kelas_id_siswa_baru = (int)$kelas['id'];

// Cek NISN duplikat
$cek_nisn = $conn->prepare('SELECT COUNT(*) FROM pendaftaran_siswa WHERE nisn = ?');
if (!$cek_nisn) die('Gagal menyiapkan query cek NISN: ' . $conn->error);
$cek_nisn->bind_param('s', $nisn);
$cek_nisn->execute();
$cek_nisn->bind_result($jumlah_nisn);
$cek_nisn->fetch();
$cek_nisn->close();

if ((int)$jumlah_nisn > 0) {
    header('Location: formulir?error=nisn_terdaftar');
    exit;
}

$subfolder = (string)$nisn;
$foto        = sds_uploadFile('foto',        ['pdf','jpg','jpeg','png'], 10, $subfolder) ?? '';
$file_kk     = sds_uploadFile('file_kk',     ['pdf','jpg','jpeg','png'], 10, $subfolder) ?? '';
$file_ijazah = sds_uploadFile('file_ijazah', ['pdf','jpg','jpeg','png'], 10, $subfolder) ?? '';
$file_akta   = sds_uploadFile('file_akta',   ['pdf','jpg','jpeg','png'], 10, $subfolder) ?? '';
$file_kip    = sds_uploadFile('file_kip',    ['pdf','jpg','jpeg','png'], 10, $subfolder) ?? '';
$file_kps    = sds_uploadFile('file_kps',    ['pdf','jpg','jpeg','png'], 10, $subfolder) ?? '';
$file_pkh    = sds_uploadFile('file_pkh',    ['pdf','jpg','jpeg','png'], 10, $subfolder) ?? '';
$file_kks    = sds_uploadFile('file_kks',    ['pdf','jpg','jpeg','png'], 10, $subfolder) ?? '';
$file_kis    = sds_uploadFile('file_kis',    ['pdf','jpg','jpeg','png'], 10, $subfolder) ?? '';

$kolom = [
    'nama_lengkap','email','nisn','nipd','sekolah_asal','nomor_ijazah','jenis_kelamin','tempat_lahir','tanggal_lahir',
    'no_kk','nik','no_registrasi_akta','kebutuhan_khusus','agama','alamat','desa','kecamatan','kota','provinsi',
    'latitude','longitude','tempat_tinggal','moda_transportasi','anak_ke','jumlah_saudara_kandung','tinggi_badan','berat_badan',
    'hobi','cita_cita','nomor_kip','nomor_kps','nomor_pkh','nomor_kks','nomor_kis','file_kip','file_kps','file_pkh','file_kks','file_kis',
    'nama_ayah','nik_ayah','tahun_lahir_ayah','pendidikan_ayah','pekerjaan_ayah','penghasilan_ayah',
    'nama_ibu','nik_ibu','tahun_lahir_ibu','pendidikan_ibu','pekerjaan_ibu','penghasilan_ibu',
    'nama_wali','nik_wali','tahun_lahir_wali','pendidikan_wali','pekerjaan_wali','penghasilan_wali',
    'nohp_ortu','nohp_siswa','foto','file_kk','file_ijazah','file_akta','pernyataan_setuju','tahun_ajaran','kelas_id','jurusan_id'
];

$values = [
    $nama_lengkap,$email,$nisn,$nipd,$sekolah_asal,$nomor_ijazah,$jenis_kelamin,$tempat_lahir,$tanggal_lahir,
    $no_kk,$nik,$no_registrasi_akta,$kebutuhan_khusus,$agama,$alamat,$desa,$kecamatan,$kota,$provinsi,
    $latitude,$longitude,$tempat_tinggal,$moda_transportasi,$anak_ke,$jumlah_saudara_kandung,$tinggi_badan,$berat_badan,
    $hobi,$cita_cita,$nomor_kip,$nomor_kps,$nomor_pkh,$nomor_kks,$nomor_kis,$file_kip,$file_kps,$file_pkh,$file_kks,$file_kis,
    $nama_ayah,$nik_ayah,$tahun_lahir_ayah,$pendidikan_ayah,$pekerjaan_ayah,$penghasilan_ayah,
    $nama_ibu,$nik_ibu,$tahun_lahir_ibu,$pendidikan_ibu,$pekerjaan_ibu,$penghasilan_ibu,
    $nama_wali,$nik_wali,$tahun_lahir_wali,$pendidikan_wali,$pekerjaan_wali,$penghasilan_wali,
    $nohp_ortu,$nohp_siswa,$foto,$file_kk,$file_ijazah,$file_akta,$pernyataan_setuju,$tahunAjaran,(string)$kelas_id_siswa_baru,(string)$jurusan_id
];

$placeholders = implode(',', array_fill(0, count($kolom), '?'));
$sql = 'INSERT INTO pendaftaran_siswa (' . implode(',', $kolom) . ') VALUES (' . $placeholders . ')';

// Mulai transaksi supaya pendaftaran_siswa dan siswa_kelas selalu sinkron.
$conn->begin_transaction();

$stmt = $conn->prepare($sql);
if (!$stmt) {
    $conn->rollback();
    die('Gagal menyiapkan query simpan pendaftaran: ' . $conn->error);
}

$types = str_repeat('s', count($values));
$stmt->bind_param($types, ...$values);

if (!$stmt->execute()) {
    $err = $stmt->error;
    $conn->rollback();
    die('Gagal menyimpan data pendaftaran: ' . $err);
}

$pendaftaran_siswa_id = (int)$conn->insert_id;
$stmt->close();

$stmt_siswa_kelas = $conn->prepare('INSERT INTO siswa_kelas (siswa_id, kelas_id, tahun_ajaran, naik_kelas) VALUES (?, ?, ?, 1)');
if (!$stmt_siswa_kelas) {
    $conn->rollback();
    die('Gagal menyiapkan query siswa_kelas: ' . $conn->error);
}
$stmt_siswa_kelas->bind_param('iis', $pendaftaran_siswa_id, $kelas_id_siswa_baru, $tahunAjaran);
if (!$stmt_siswa_kelas->execute()) {
    $err = $stmt_siswa_kelas->error;
    $conn->rollback();
    die('Gagal menambahkan ke siswa_kelas: ' . $err);
}
$stmt_siswa_kelas->close();

sds_upload_sync_terisi_kelas($conn, $kelas_id_siswa_baru);

// Sinkronisasi kelas sudah selesai, sekarang commit transaksi.
$conn->commit();

// WA dan catatLog sengaja tidak dijalankan sebelum redirect cetak.
// Tujuannya agar proses kirim formulir selalu langsung menuju halaman cetak
// setelah data utama dan relasi kelas berhasil tersimpan.

$printSecret = (string)sds_config('security.print_secret', '');
if ($printSecret === '') {
    error_log('[Formulir] Data tersimpan, tetapi security.print_secret belum dikonfigurasi.');
    header('Location: formulir?tab=progress&notice=print_secret_missing');
    exit;
}
$printToken = hash_hmac('sha256', 'print-v2|' . $pendaftaran_siswa_id, $printSecret);
header('Location: cetak_daftar_ulang.php?id=' . $pendaftaran_siswa_id . '&token=' . urlencode($printToken));
exit;
