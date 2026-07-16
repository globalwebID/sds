<?php
// student_pdf.php - Unduh PDF Peserta Didik lengkap dan aman dari error tabel/kolom opsional.
// Revisi v20: perbaiki render Dompdf agar tidak fallback ke preview HTML akibat HTML table tidak valid / warning internal Dompdf.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
@ini_set('memory_limit', '256M');
@set_time_limit(120);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function pdfFailSafeMessage($message, $detail = '') {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><html lang="id"><head><meta charset="UTF-8"><title>PDF Peserta Didik</title>' .
         '<style>body{font-family:Arial,sans-serif;background:#f5f6f8;margin:0;padding:24px;color:#222}.box{max-width:820px;margin:auto;background:#fff;border:1px solid #ddd;padding:18px}.title{font-weight:700;font-size:18px;margin-bottom:8px}.muted{color:#666}.code{background:#f8f9fa;border:1px solid #ddd;padding:10px;white-space:pre-wrap;font-family:Consolas,monospace;font-size:12px}</style>' .
         '</head><body><div class="box"><div class="title">PDF belum bisa dibuat</div><div class="muted">' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</div>';
    if ($detail !== '' && isset($_GET['debug'])) {
        echo '<div class="code">' . htmlspecialchars($detail, ENT_QUOTES, 'UTF-8') . '</div>';
    }
    echo '</div></body></html>';
    exit;
}

set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        pdfFailSafeMessage('Terjadi error fatal saat membuat PDF. Tambahkan parameter &debug=1 untuk melihat detail teknis.', $err['message'] . ' di ' . $err['file'] . ':' . $err['line']);
    }
});

require '../db.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
$dompdfReady = false;
if (is_file($autoload)) {
    require_once $autoload;
    $dompdfReady = class_exists('\Dompdf\Dompdf') && class_exists('\Dompdf\Options');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    pdfFailSafeMessage('ID siswa tidak valid.');
}

function h($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function val($row, $key, $default = '-') {
    if (!is_array($row) || !array_key_exists($key, $row)) return $default;
    $value = trim((string)$row[$key]);
    return $value !== '' ? $value : $default;
}

function raw($row, $key) {
    if (!is_array($row) || !array_key_exists($key, $row)) return '';
    return trim((string)$row[$key]);
}

function formatNomor($nomor) {
    $nomor = preg_replace('/[^0-9]/', '', (string)$nomor);
    if ($nomor === '') return '-';
    return (substr($nomor, 0, 2) === '62') ? '0' . substr($nomor, 2) : $nomor;
}

function tanggalIndo($date = null, $withTime = false) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    if ($date === null || trim((string)$date) === '') {
        $ts = time();
    } else {
        $date = trim((string)$date);
        if ($date === '0000-00-00' || $date === '0000-00-00 00:00:00') return '-';
        $ts = strtotime($date);
        if ($ts === false) return $date;
    }
    $hasil = date('d', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
    if ($withTime) $hasil .= ' ' . date('H:i:s', $ts);
    return $hasil;
}

function tableExists($conn, $table) {
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    if ($table === '') return false;
    $res = $conn->query("SHOW TABLES LIKE '" . $conn->real_escape_string($table) . "'");
    return ($res && $res->num_rows > 0);
}

function tableColumns($conn, $table) {
    static $cache = [];
    $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$table);
    if ($table === '') return [];
    if (isset($cache[$table])) return $cache[$table];
    $cache[$table] = [];
    if (!tableExists($conn, $table)) return $cache[$table];
    $res = $conn->query("SHOW COLUMNS FROM `$table`");
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $cache[$table][] = $row['Field'];
        }
    }
    return $cache[$table];
}

function colExists($conn, $table, $col) {
    return in_array($col, tableColumns($conn, $table), true);
}

function firstCol($conn, $table, $candidates) {
    $cols = tableColumns($conn, $table);
    foreach ($candidates as $c) {
        if (in_array($c, $cols, true)) return $c;
    }
    return '';
}

function safeRows($conn, $sql, $types = '', $params = []) {
    $stmt = null;
    try {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        if ($types !== '' && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        if (!$stmt->execute()) {
            $stmt->close();
            return [];
        }
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    } catch (Throwable $e) {
        if ($stmt instanceof mysqli_stmt) {
            try { $stmt->close(); } catch (Throwable $ignore) {}
        }
        error_log('[student_pdf safeRows] ' . $e->getMessage());
        return [];
    }
}

function dataUriFromFile($path) {
    $path = (string)$path;
    if ($path === '' || !is_file($path)) return '';
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    // Dompdf paling stabil untuk jpg/png/gif. WebP sering memicu error render di hosting tertentu.
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'];
    if (!isset($allowed[$ext])) return '';

    // Batas ini mengikuti upload foto siswa (maksimal 5 MB). Batas lama 2,5 MB
    // membuat foto yang berhasil diunggah diam-diam hilang dari PDF.
    $size = @filesize($path);
    if ($size !== false && $size > 5 * 1024 * 1024) return '';

    $data = @file_get_contents($path);
    if ($data === false) return '';
    return 'data:' . $allowed[$ext] . ';base64,' . base64_encode($data);
}

function uploadPath($file) {
    $file = trim((string)$file);
    if ($file === '') return '';
    $file = str_replace('\\', '/', $file);
    $file = ltrim($file, '/');
    if (strpos($file, '..') !== false) return '';
    $candidates = [
        __DIR__ . '/../uploads/' . $file,
        __DIR__ . '/../uploads/foto/' . $file,
        __DIR__ . '/../uploads/logo/' . $file,
        __DIR__ . '/../' . $file,
        dirname(__DIR__) . '/' . $file,
    ];
    foreach ($candidates as $p) {
        if (is_file($p)) return $p;
    }
    return '';
}

function fileStatus($student, $field) {
    return raw($student, $field) !== '' ? 'Ada' : 'Tidak Ada';
}

function row($label, $value) {
    echo '<tr><td class="label">' . h($label) . '</td><td class="colon">:</td><td>' . h($value) . '</td></tr>';
}

function section($title) {
    echo '<h2>' . h($title) . '</h2>';
}

function tableStart() {
    echo '<table class="info">';
}

function tableEnd() {
    echo '</table>';
}

function renderSimpleTable($title, $rows) {
    section($title);
    tableStart();
    foreach ($rows as $r) {
        row($r[0], $r[1]);
    }
    tableEnd();
}

function renderRowsTable($title, $headers, $rows, $emptyText = 'Tidak ada data.') {
    section($title);
    if (empty($rows)) {
        echo '<div class="empty">' . h($emptyText) . '</div>';
        return;
    }
    echo '<table class="list"><thead><tr>';
    foreach ($headers as $h) echo '<th>' . h($h) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r) {
        echo '<tr>';
        foreach ($r as $v) echo '<td>' . h($v) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
}

function renderGenericRows($title, $rows, $preferredLabels = []) {
    section($title);
    if (empty($rows)) {
        echo '<div class="empty">Tidak ada data.</div>';
        return;
    }

    $allKeys = [];
    foreach ($rows as $row) {
        foreach (array_keys($row) as $key) {
            if (!in_array($key, $allKeys, true)) $allKeys[] = $key;
        }
    }

    $skip = ['id', 'created_at', 'updated_at', 'deleted_at'];
    $keys = [];
    foreach ($preferredLabels as $key => $label) {
        if (in_array($key, $allKeys, true) && !in_array($key, $keys, true)) $keys[] = $key;
    }
    foreach ($allKeys as $key) {
        if (count($keys) >= 7) break;
        if (in_array($key, $skip, true)) continue;
        if (strpos($key, 'id') !== false && !in_array($key, ['siswa_id','pendaftaran_id'], true)) continue;
        if (!in_array($key, $keys, true)) $keys[] = $key;
    }

    echo '<table class="list"><thead><tr>';
    foreach ($keys as $key) {
        $label = $preferredLabels[$key] ?? ucwords(str_replace('_', ' ', $key));
        echo '<th>' . h($label) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($rows as $row) {
        echo '<tr>';
        foreach ($keys as $key) {
            echo '<td>' . h(val($row, $key)) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table>';
}


function makeWritableDompdfDir() {
    $candidates = [
        __DIR__ . '/../tmp_dompdf',
        __DIR__ . '/tmp_dompdf',
        rtrim(sys_get_temp_dir(), '/\\') . '/sds_dompdf'
    ];
    foreach ($candidates as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (is_dir($dir) && is_writable($dir)) {
            return $dir;
        }
    }
    return sys_get_temp_dir();
}

function dompdfSafeHtml($html) {
    // Bersihkan hanya CSS yang tidak didukung konsisten oleh Dompdf. Layout table/table-cell
    // harus dipertahankan agar foto siswa tetap berada di kanan atas biodata.
    $html = preg_replace('/object-fit\s*:\s*[^;]+;?/i', '', $html);
    $html = str_replace('page-break-before: always;', 'page-break-before:auto;', $html);

    // Foto, logo, dan kop sudah dikonversi menjadi data URI dari berkas lokal yang
    // tervalidasi. Jangan menghapusnya: data URI justru menghindari masalah URL/chroot
    // saat dokumen dirender oleh Dompdf.

    // Kurangi tabel opsional panjang bila HTML terlalu besar. Data utama tetap lengkap.
    if (strlen($html) > 350000) {
        $html = preg_replace('/<h2>Riwayat Transaksi \/ E-Money<\/h2>.*?(?=<h2>|<div class="footer">)/is', '', $html);
        $html = preg_replace('/<h2>Pembayaran<\/h2>.*?(?=<h2>|<div class="footer">)/is', '', $html);
        $html = preg_replace('/<h2>Kehadiran<\/h2>.*?(?=<h2>|<div class="footer">)/is', '', $html);
    }

    return $html;
}

$tahunAjaranAktif = (string)$tahunAjaran;

// Data siswa memakai kelas aktif tahun ajaran berjalan bila ada.
function fetchStudentPdfByPendaftaranId($conn, $pendaftaranId, $tahunAjaranAktif) {
    $sqlStudent = "
        SELECT
            p.*,
            COALESCE(k_aktif.nama_kelas, k_form.nama_kelas, '-') AS nama_kelas,
            COALESCE(j_aktif.nama_jurusan, j_form.nama_jurusan, '-') AS nama_jurusan,
            COALESCE(tk_aktif.nama_tingkat, '-') AS nama_tingkat
        FROM pendaftaran_siswa p
        LEFT JOIN siswa_kelas sk_aktif
            ON sk_aktif.siswa_id = p.id AND BINARY sk_aktif.tahun_ajaran = BINARY ?
        LEFT JOIN kelas k_aktif
            ON k_aktif.id = sk_aktif.kelas_id
        LEFT JOIN jurusan j_aktif
            ON j_aktif.id = k_aktif.jurusan_id
        LEFT JOIN tingkat_kelas tk_aktif
            ON tk_aktif.id = k_aktif.tingkat_id
        LEFT JOIN kelas k_form
            ON k_form.id = p.kelas_id
        LEFT JOIN jurusan j_form
            ON j_form.id = COALESCE(k_form.jurusan_id, p.jurusan_id)
        WHERE p.id = ?
        LIMIT 1
    ";
    $stmt = $conn->prepare($sqlStudent);
    if (!$stmt) {
        throw new RuntimeException('Query siswa gagal disiapkan: ' . $conn->error);
    }
    $stmt->bind_param('si', $tahunAjaranAktif, $pendaftaranId);
    $stmt->execute();
    $res = $stmt->get_result();
    $student = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $student ?: null;
}

function fetchStudentPdfByNisn($conn, $nisn, $tahunAjaranAktif) {
    $nisn = trim((string)$nisn);
    if ($nisn === '') return null;
    $rows = safeRows($conn, "SELECT id FROM pendaftaran_siswa WHERE nisn = ? ORDER BY tahun_ajaran DESC, id DESC LIMIT 1", 's', [$nisn]);
    if (empty($rows)) return null;
    return fetchStudentPdfByPendaftaranId($conn, (int)$rows[0]['id'], $tahunAjaranAktif);
}

function fetchStudentPdfByNameAndYear($conn, $nama, $tahun, $tahunAjaranAktif) {
    $nama = trim((string)$nama);
    $tahun = trim((string)$tahun);
    if ($nama === '') return null;

    if ($tahun !== '') {
        $rows = safeRows($conn, "SELECT id FROM pendaftaran_siswa WHERE nama_lengkap = ? AND tahun_ajaran = ? ORDER BY id DESC LIMIT 1", 'ss', [$nama, $tahun]);
        if (!empty($rows)) return fetchStudentPdfByPendaftaranId($conn, (int)$rows[0]['id'], $tahunAjaranAktif);
    }

    $rows = safeRows($conn, "SELECT id FROM pendaftaran_siswa WHERE nama_lengkap = ? ORDER BY tahun_ajaran DESC, id DESC LIMIT 1", 's', [$nama]);
    if (empty($rows)) return null;
    return fetchStudentPdfByPendaftaranId($conn, (int)$rows[0]['id'], $tahunAjaranAktif);
}

function fetchStudentPdfFromLegacyAbsensiUser($conn, $legacyUserId, $tahunAjaranAktif, &$debugInfo) {
    if (!tableExists($conn, 'user')) return null;
    $cols = tableColumns($conn, 'user');
    if (!in_array('user_id', $cols, true)) return null;

    $select = ['user_id'];
    foreach (['nisn','nama_lengkap','tahun_ajaran','kelas'] as $c) {
        if (in_array($c, $cols, true)) $select[] = $c;
    }
    $rows = safeRows($conn, "SELECT " . implode(',', array_map(function($c){ return "`$c`"; }, $select)) . " FROM user WHERE user_id = ? LIMIT 1", 'i', [$legacyUserId]);
    if (empty($rows)) return null;

    $u = $rows[0];
    $debugInfo[] = 'ID ditemukan di tabel user. Sistem mencoba mencocokkan ke pendaftaran_siswa melalui NISN/nama.';

    if (!empty($u['nisn'])) {
        $student = fetchStudentPdfByNisn($conn, $u['nisn'], $tahunAjaranAktif);
        if ($student) return $student;
    }
    if (!empty($u['nama_lengkap'])) {
        $student = fetchStudentPdfByNameAndYear($conn, $u['nama_lengkap'], $u['tahun_ajaran'] ?? '', $tahunAjaranAktif);
        if ($student) return $student;
    }
    return null;
}

function fetchStudentPdfFromLegacyUsers($conn, $legacyUserId, $tahunAjaranAktif, &$debugInfo) {
    if (!tableExists($conn, 'users')) return null;
    $cols = tableColumns($conn, 'users');
    if (!in_array('id', $cols, true)) return null;

    $select = ['id'];
    foreach (['username','nama_lengkap','full_name','nisn','email'] as $c) {
        if (in_array($c, $cols, true)) $select[] = $c;
    }
    $rows = safeRows($conn, "SELECT " . implode(',', array_map(function($c){ return "`$c`"; }, $select)) . " FROM users WHERE id = ? LIMIT 1", 'i', [$legacyUserId]);
    if (empty($rows)) return null;

    $u = $rows[0];
    $debugInfo[] = 'ID ditemukan di tabel users. Sistem mencoba mencocokkan ke pendaftaran_siswa melalui username/NISN/nama.';

    foreach (['nisn','username'] as $key) {
        if (!empty($u[$key])) {
            $student = fetchStudentPdfByNisn($conn, $u[$key], $tahunAjaranAktif);
            if ($student) return $student;
        }
    }
    foreach (['nama_lengkap','full_name'] as $key) {
        if (!empty($u[$key])) {
            $student = fetchStudentPdfByNameAndYear($conn, $u[$key], '', $tahunAjaranAktif);
            if ($student) return $student;
        }
    }
    return null;
}

$debugInfo = [];
$student = null;
try {
    // Normal: ID dari halaman Data Peserta Didik adalah pendaftaran_siswa.id.
    $student = fetchStudentPdfByPendaftaranId($conn, $id, $tahunAjaranAktif);

    // Fallback: beberapa data lama memakai ID user/users, bukan pendaftaran_siswa.id.
    if (!$student) {
        $student = fetchStudentPdfFromLegacyAbsensiUser($conn, $id, $tahunAjaranAktif, $debugInfo);
    }
    if (!$student) {
        $student = fetchStudentPdfFromLegacyUsers($conn, $id, $tahunAjaranAktif, $debugInfo);
    }
} catch (Throwable $e) {
    pdfFailSafeMessage('Query data siswa gagal dijalankan.', $e->getMessage());
}

if (!$student) {
    $detail = "Parameter id: " . $id . "\n";
    $detail .= "Tahun ajaran aktif terdeteksi: " . $tahunAjaranAktif . "\n";
    $detail .= "Cek pendaftaran_siswa.id, user.user_id, dan users.id tidak menemukan pasangan data yang bisa dibuat PDF.\n";
    if (!empty($debugInfo)) $detail .= "\n" . implode("\n", $debugInfo);
    pdfFailSafeMessage('Data siswa tidak ditemukan. Kemungkinan ID pada link bukan ID tabel pendaftaran_siswa, atau data siswa belum tersinkron dari data absensi lama.', $detail);
}

$student['nohp_siswa'] = formatNomor(raw($student, 'nohp_siswa'));
$student['nohp_ortu'] = formatNomor(raw($student, 'nohp_ortu'));

$adminNama = '';
if (isset($_SESSION['admin_id'])) {
    $adminId = (int)$_SESSION['admin_id'];
    $rowsAdmin = safeRows($conn, "SELECT full_name FROM admins WHERE id = ? LIMIT 1", 'i', [$adminId]);
    if (!empty($rowsAdmin)) $adminNama = raw($rowsAdmin[0], 'full_name');
}

$pengaturan = [];
$resPengaturan = $conn->query("SELECT * FROM pengaturan LIMIT 1");
if ($resPengaturan && $resPengaturan->num_rows > 0) {
    $pengaturan = $resPengaturan->fetch_assoc();
}

$kopDataUri = '';
if (!empty($pengaturan['kop_surat'])) {
    $kopDataUri = dataUriFromFile(__DIR__ . '/../uploads/logo/' . $pengaturan['kop_surat']);
}
$logoDataUri = '';
if (!$kopDataUri && !empty($pengaturan['logo'])) {
    $logoDataUri = dataUriFromFile(__DIR__ . '/../uploads/logo/' . $pengaturan['logo']);
}
$fotoDataUri = '';
if (!empty($student['foto'])) {
    $fotoPath = uploadPath($student['foto']);
    if ($fotoPath !== '' && !preg_match('/\.pdf$/i', $fotoPath)) {
        $fotoDataUri = dataUriFromFile($fotoPath);
    }
}

$alamatLengkap = trim(
    val($student, 'alamat', '') .
    (raw($student, 'desa') !== '' ? ', Desa/Kelurahan ' . raw($student, 'desa') : '') .
    (raw($student, 'kecamatan') !== '' ? ', Kecamatan ' . raw($student, 'kecamatan') : '') .
    (raw($student, 'kota') !== '' ? ', ' . raw($student, 'kota') : '') .
    (raw($student, 'provinsi') !== '' ? ', ' . raw($student, 'provinsi') : '')
);
if ($alamatLengkap === '') $alamatLengkap = '-';

// Riwayat Rombel.
$riwayatRombel = safeRows($conn, "
    SELECT
        sk.tahun_ajaran,
        COALESCE(k.nama_kelas,'-') AS nama_kelas,
        COALESCE(j.nama_jurusan,'-') AS nama_jurusan,
        COALESCE(tk.nama_tingkat,'-') AS tingkat,
        sk.naik_kelas
    FROM siswa_kelas sk
    LEFT JOIN kelas k ON k.id = sk.kelas_id
    LEFT JOIN jurusan j ON j.id = k.jurusan_id
    LEFT JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    WHERE sk.siswa_id = ?
    ORDER BY sk.tahun_ajaran ASC, sk.id ASC
", 'i', [$id]);

$riwayatRombelRows = [];
foreach ($riwayatRombel as $r) {
    $status = ((int)($r['naik_kelas'] ?? 1) === 0) ? 'Tidak Naik' : 'Naik/Aktif';
    $riwayatRombelRows[] = [val($r, 'tahun_ajaran'), val($r, 'tingkat'), val($r, 'nama_kelas'), val($r, 'nama_jurusan'), $status];
}

// Ekstrakurikuler.
$ekskulRows = [];
if (tableExists($conn, 'ekstrakurikuler_siswa') && tableExists($conn, 'ekstrakurikuler')) {
    $ekskulCols = tableColumns($conn, 'ekstrakurikuler');
    $namaEkskulExpr = in_array('nama_ekskul', $ekskulCols, true)
        ? 'e.`nama_ekskul`'
        : (in_array('nama', $ekskulCols, true) ? 'e.`nama`' : "CONCAT('Ekskul #', es.ekstrakurikuler_id)");
    $pembinaExpr = in_array('nama_pembina', $ekskulCols, true)
        ? 'e.`nama_pembina`'
        : (in_array('pembina', $ekskulCols, true) ? 'e.`pembina`' : "''");

    $ekskulRows = safeRows($conn, "
        SELECT es.id, {$namaEkskulExpr} AS nama_ekskul, {$pembinaExpr} AS pembina, es.ekstrakurikuler_id
        FROM ekstrakurikuler_siswa es
        LEFT JOIN ekstrakurikuler e ON e.id = es.ekstrakurikuler_id
        WHERE es.siswa_id = ?
        ORDER BY nama_ekskul ASC, es.id DESC
        LIMIT 50
    ", 'i', [$id]);
} elseif (tableExists($conn, 'ekskul_siswa')) {
    $cols = tableColumns($conn, 'ekskul_siswa');
    $idCol = in_array('siswa_id', $cols, true) ? 'siswa_id' : (in_array('pendaftaran_id', $cols, true) ? 'pendaftaran_id' : '');
    if ($idCol !== '') {
        $ekskulRows = safeRows($conn, "SELECT * FROM ekskul_siswa WHERE `$idCol` = ? ORDER BY 1 DESC LIMIT 50", 'i', [$id]);
    }
} elseif (tableExists($conn, 'siswa_ekskul')) {
    $cols = tableColumns($conn, 'siswa_ekskul');
    $idCol = in_array('siswa_id', $cols, true) ? 'siswa_id' : (in_array('pendaftaran_id', $cols, true) ? 'pendaftaran_id' : '');
    if ($idCol !== '') {
        $ekskulRows = safeRows($conn, "SELECT * FROM siswa_ekskul WHERE `$idCol` = ? ORDER BY 1 DESC LIMIT 50", 'i', [$id]);
    }
}

// Data generic aman untuk tabel opsional.
function findRowsByStudent($conn, $tableNames, $studentId, $limit = 50) {
    foreach ($tableNames as $table) {
        if (!tableExists($conn, $table)) continue;
        $cols = tableColumns($conn, $table);
        $idCol = '';
        foreach (['siswa_id','pendaftaran_id','student_id','id_siswa'] as $candidate) {
            if (in_array($candidate, $cols, true)) {
                $idCol = $candidate;
                break;
            }
        }
        if ($idCol === '') continue;

        $orderCol = firstCol($conn, $table, ['tanggal','tanggal_input','tgl','created_at','id']);
        $orderSql = $orderCol !== '' ? " ORDER BY `$orderCol` DESC" : '';
        return [
            'table' => $table,
            'rows' => safeRows($conn, "SELECT * FROM `$table` WHERE `$idCol` = ?" . $orderSql . " LIMIT " . (int)$limit, 'i', [$studentId])
        ];
    }
    return ['table' => '', 'rows' => []];
}

$kehadiranData = findRowsByStudent($conn, ['kehadiran_siswa','absensi_siswa','absensi','kehadiran'], $id, 60);
$transaksiData = findRowsByStudent($conn, ['riwayat_transaksi','transaksi_siswa','transaksi','emoney_transaksi','e_money_transaksi','tabungan_transaksi'], $id, 60);
$pembayaranData = findRowsByStudent($conn, ['pembayaran_siswa','pembayaran','tagihan_siswa','siswa_pembayaran'], $id, 60);
$pelanggaranData = findRowsByStudent($conn, ['pelanggaran_siswa','siswa_pelanggaran','pelanggaran'], $id, 60);
$prestasiData = findRowsByStudent($conn, ['prestasi_siswa','siswa_prestasi','prestasi'], $id, 60);
$nilaiEkskulData = findRowsByStudent($conn, ['nilai_ekskul','ekskul_nilai','nilai_ekstrakurikuler'], $id, 60);
$berkasTambahanData = findRowsByStudent($conn, ['berkas_tambahan_siswa','siswa_berkas_tambahan','berkas_siswa'], $id, 60);

ob_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
    @page { size: A4; margin: 12mm 14mm 14mm 14mm; }
    body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #111; line-height: 1.35; }
    .kop { width: 100%; margin-bottom: 8px; text-align: center; }
    .kop img { max-width: 100%; max-height: 115px;  }
    .kop-manual { border-bottom: 3px double #111; padding-bottom: 7px; margin-bottom: 8px; text-align:center; }
    .kop-manual .school { font-size: 18px; font-weight: bold; }
    h1 { text-align:center; font-size: 16px; margin: 6px 0 12px; letter-spacing: .5px; }
    h2 { font-size: 12px; background:#e9ecef; padding: 5px 7px; margin: 10px 0 5px; border-left: 4px solid #333; }
    table { width: 100%; border-collapse: collapse; }
    table.info td { padding: 3px 5px; vertical-align: top; border-bottom: 1px solid #eee; }
    table.info td.label { width: 31%; font-weight: bold; }
    table.info td.colon { width: 10px; text-align:center; }
    table.list th, table.list td { border: 1px solid #ddd; padding: 4px 5px; vertical-align: top; }
    table.list th { background: #f3f4f6; font-weight: bold; text-align: left; }
    .grid { display: table; width: 100%; }
    .grid .left { display: table-cell; width: 75%; vertical-align: top; padding-right: 10px; }
    .grid .right { display: table-cell; width: 25%; vertical-align: top; text-align: center; }
    .photo { border: 1px solid #bbb; width: 95px; height: 125px;  display:block; margin: 0 auto 4px; }
    .photo-empty { border: 1px solid #bbb; width: 95px; height: 80px; display:block; padding-top:45px; text-align: center; color:#777; }
    .badge { display:inline-block; padding:2px 6px; border-radius: 3px; font-weight:bold; font-size:9px; }
    .ada { background:#d1e7dd; color:#0f5132; }
    .tidak { background:#f8d7da; color:#842029; }
    .empty { color:#666; font-style: italic; border: 1px dashed #ccc; padding: 7px; margin-bottom: 8px; }
    .footer { margin-top: 16px; text-align:right; font-size: 10px; }
    .page-break { page-break-before: always; }
</style>
</head>
<body>
<div class="kop">
    <?php if ($kopDataUri !== ''): ?>
        <img src="<?= $kopDataUri ?>" alt="Kop Surat">
    <?php else: ?>
        <div class="kop-manual">
            <?php if ($logoDataUri !== ''): ?><img src="<?= $logoDataUri ?>" alt="Logo" style="width:55px;max-height:55px;"><?php endif; ?>
            <div class="school"><?= h(val($pengaturan, 'nama_sekolah', 'SMK NEGERI 1 PROBOLINGGO')) ?></div>
            <div>Data Peserta Didik Tahun Ajaran <?= h($tahunAjaranAktif) ?></div>
        </div>
    <?php endif; ?>
</div>

<h1>BIODATA PESERTA DIDIK LENGKAP</h1>

<div class="grid">
    <div class="left">
        <?php
        renderSimpleTable('Data Pribadi', [
            ['Tahun Ajaran Masuk', val($student, 'tahun_ajaran')],
            ['Nama Lengkap', val($student, 'nama_lengkap')],
            ['Email', val($student, 'email')],
            ['NISN', val($student, 'nisn')],
            ['Kelas Aktif', val($student, 'nama_kelas')],
            ['Konsentrasi Keahlian', val($student, 'nama_jurusan')],
            ['Sekolah Asal', val($student, 'sekolah_asal')],
            ['Nomor Ijazah SMP', val($student, 'nomor_ijazah')],
            ['Jenis Kelamin', val($student, 'jenis_kelamin')],
            ['Tempat/Tanggal Lahir', val($student, 'tempat_lahir') . ', ' . tanggalIndo(raw($student, 'tanggal_lahir'))],
            ['No. KK', val($student, 'no_kk')],
            ['NIK', val($student, 'nik')],
            ['No. Registrasi Akta', val($student, 'no_registrasi_akta')],
            ['Kebutuhan Khusus', val($student, 'kebutuhan_khusus', 'Tidak')],
            ['Agama', val($student, 'agama')],
        ]);
        ?>
    </div>
    <div class="right">
        <?php if ($fotoDataUri !== ''): ?>
            <img class="photo" src="<?= $fotoDataUri ?>" alt="Foto Siswa">
        <?php else: ?>
            <div class="photo-empty">Foto<br>Tidak Ada</div>
        <?php endif; ?>
    </div>
</div>

<?php
renderSimpleTable('Alamat, Fisik, dan Minat', [
    ['Alamat Lengkap', $alamatLengkap],
    ['Alamat', val($student, 'alamat')],
    ['Desa/Kelurahan', val($student, 'desa')],
    ['Kecamatan', val($student, 'kecamatan')],
    ['Kabupaten/Kota', val($student, 'kota')],
    ['Provinsi', val($student, 'provinsi')],
    ['Koordinat Rumah', val($student, 'latitude') . ', ' . val($student, 'longitude')],
    ['Tempat Tinggal', val($student, 'tempat_tinggal')],
    ['Moda Transportasi', val($student, 'moda_transportasi')],
    ['Anak ke', val($student, 'anak_ke')],
    ['Jumlah Saudara Kandung', val($student, 'jumlah_saudara_kandung')],
    ['Tinggi Badan', val($student, 'tinggi_badan') . ' cm'],
    ['Berat Badan', val($student, 'berat_badan') . ' kg'],
    ['Hobi', val($student, 'hobi')],
    ['Cita-cita', val($student, 'cita_cita')],
]);

renderSimpleTable('Data Ayah', [
    ['Nama Ayah', val($student, 'nama_ayah')],
    ['NIK Ayah', val($student, 'nik_ayah')],
    ['Tahun Lahir Ayah', val($student, 'tahun_lahir_ayah')],
    ['Pendidikan Ayah', val($student, 'pendidikan_ayah')],
    ['Pekerjaan Ayah', val($student, 'pekerjaan_ayah')],
    ['Penghasilan Ayah', val($student, 'penghasilan_ayah')],
]);

renderSimpleTable('Data Ibu', [
    ['Nama Ibu', val($student, 'nama_ibu')],
    ['NIK Ibu', val($student, 'nik_ibu')],
    ['Tahun Lahir Ibu', val($student, 'tahun_lahir_ibu')],
    ['Pendidikan Ibu', val($student, 'pendidikan_ibu')],
    ['Pekerjaan Ibu', val($student, 'pekerjaan_ibu')],
    ['Penghasilan Ibu', val($student, 'penghasilan_ibu')],
]);

if (raw($student, 'nama_wali') !== '' || raw($student, 'nik_wali') !== '' || raw($student, 'pekerjaan_wali') !== '') {
    renderSimpleTable('Data Wali', [
        ['Nama Wali', val($student, 'nama_wali')],
        ['NIK Wali', val($student, 'nik_wali')],
        ['Tahun Lahir Wali', val($student, 'tahun_lahir_wali')],
        ['Pendidikan Wali', val($student, 'pendidikan_wali')],
        ['Pekerjaan Wali', val($student, 'pekerjaan_wali')],
        ['Penghasilan Wali', val($student, 'penghasilan_wali')],
    ]);
}

renderSimpleTable('Kontak', [
    ['No. HP Orang Tua/Wali', val($student, 'nohp_ortu')],
    ['No. HP Siswa', val($student, 'nohp_siswa')],
]);

renderSimpleTable('Kesejahteraan / Program Bantuan', [
    ['Nomor KIP', val($student, 'nomor_kip')],
    ['Nomor KPS', val($student, 'nomor_kps')],
    ['Nomor PKH', val($student, 'nomor_pkh')],
    ['Nomor KKS', val($student, 'nomor_kks')],
    ['Nomor KIS', val($student, 'nomor_kis')],
]);

section('Berkas / File Utama');
echo '<table class="list"><thead><tr><th>Nama Berkas</th><th>Status</th><th>File</th></tr></thead><tbody>';
$fileUtama = [
    ['Foto Siswa', 'foto'],
    ['Kartu Keluarga', 'file_kk'],
    ['Ijazah SMP', 'file_ijazah'],
    ['Akta Kelahiran', 'file_akta'],
];
foreach ($fileUtama as $f) {
    $status = raw($student, $f[1]) !== '' ? 'Ada' : 'Tidak Ada';
    $cls = $status === 'Ada' ? 'ada' : 'tidak';
    echo '<tr><td>' . h($f[0]) . '</td><td><span class="badge ' . $cls . '">' . h($status) . '</span></td><td>' . h(val($student, $f[1])) . '</td></tr>';
}
echo '</tbody></table>';

section('Berkas / File Pendukung');
echo '<table class="list"><thead><tr><th>Nama Berkas</th><th>Nomor</th><th>Status File</th><th>File</th></tr></thead><tbody>';
$filePendukung = [
    ['KIP', 'nomor_kip', 'file_kip'],
    ['KPS', 'nomor_kps', 'file_kps'],
    ['PKH', 'nomor_pkh', 'file_pkh'],
    ['KKS', 'nomor_kks', 'file_kks'],
    ['KIS', 'nomor_kis', 'file_kis'],
];
foreach ($filePendukung as $f) {
    $nomor = raw($student, $f[1]);
    $file = raw($student, $f[2]);
    if ($nomor === '' && $file === '') continue;
    $status = $file !== '' ? 'Ada' : 'Tidak Ada';
    $cls = $status === 'Ada' ? 'ada' : 'tidak';
    echo '<tr><td>' . h($f[0]) . '</td><td>' . h(val($student, $f[1])) . '</td><td><span class="badge ' . $cls . '">' . h($status) . '</span></td><td>' . h(val($student, $f[2])) . '</td></tr>';
}
echo '</tbody></table>';
if (empty(array_filter(array_map(function($f) use ($student) { return raw($student, $f[1]) . raw($student, $f[2]); }, $filePendukung)))) {
    echo '<div class="empty">Tidak ada file pendukung yang tercatat.</div>';
}

renderGenericRows('Berkas Tambahan', $berkasTambahanData['rows'], [
    'nama_berkas' => 'Nama Berkas',
    'jenis_berkas' => 'Jenis',
    'file' => 'File',
    'tanggal_upload' => 'Tanggal Upload',
    'keterangan' => 'Keterangan'
]);

renderRowsTable('Riwayat Rombel', ['Tahun Ajaran','Tingkat','Kelas','Konsentrasi Keahlian','Status'], $riwayatRombelRows);

renderGenericRows('Ekstrakurikuler', $ekskulRows, [
    'nama_ekskul' => 'Ekstrakurikuler',
    'ekskul' => 'Ekstrakurikuler',
    'pembina' => 'Pembina',
    'tahun_ajaran' => 'Tahun Ajaran',
    'status' => 'Status',
    'nilai' => 'Nilai'
]);

echo '<div class="page-break"></div>';
renderGenericRows('Kehadiran', $kehadiranData['rows'], [
    'tanggal' => 'Tanggal',
    'tgl' => 'Tanggal',
    'status' => 'Status',
    'keterangan' => 'Keterangan',
    'jam_masuk' => 'Jam Masuk',
    'jam_pulang' => 'Jam Pulang'
]);

renderGenericRows('Riwayat Transaksi / E-Money', $transaksiData['rows'], [
    'tanggal' => 'Tanggal',
    'jenis' => 'Jenis',
    'tipe' => 'Tipe',
    'nominal' => 'Nominal',
    'jumlah' => 'Jumlah',
    'saldo' => 'Saldo',
    'keterangan' => 'Keterangan'
]);

renderGenericRows('Pembayaran', $pembayaranData['rows'], [
    'tanggal' => 'Tanggal',
    'jenis_pembayaran' => 'Jenis Pembayaran',
    'nama_pembayaran' => 'Nama Pembayaran',
    'nominal' => 'Nominal',
    'jumlah' => 'Jumlah',
    'status' => 'Status',
    'keterangan' => 'Keterangan'
]);

renderGenericRows('Pelanggaran', $pelanggaranData['rows'], [
    'tanggal' => 'Tanggal',
    'jenis_pelanggaran' => 'Jenis Pelanggaran',
    'pelanggaran' => 'Pelanggaran',
    'poin' => 'Poin',
    'tindak_lanjut' => 'Tindak Lanjut',
    'keterangan' => 'Keterangan'
]);

renderGenericRows('Prestasi', $prestasiData['rows'], [
    'tanggal' => 'Tanggal',
    'nama_prestasi' => 'Nama Prestasi',
    'prestasi' => 'Prestasi',
    'tingkat' => 'Tingkat',
    'peringkat' => 'Peringkat',
    'keterangan' => 'Keterangan'
]);

renderGenericRows('Nilai Ekstrakurikuler', $nilaiEkskulData['rows'], [
    'nama_ekskul' => 'Ekstrakurikuler',
    'nilai' => 'Nilai',
    'predikat' => 'Predikat',
    'semester' => 'Semester',
    'tahun_ajaran' => 'Tahun Ajaran',
    'keterangan' => 'Keterangan'
]);
?>

<div class="footer">
    Probolinggo, <?= h(tanggalIndo()) ?><br>
    <?= h($adminNama !== '' ? $adminNama : 'Petugas') ?><br>
    <?= h(val($pengaturan, 'nama_sekolah', 'SMK NEGERI 1 PROBOLINGGO')) ?><br>
    <small>Dicetak pada <?= h(tanggalIndo(null, true)) ?></small>
</div>
</body>
</html>
<?php
$html = ob_get_clean();

if (isset($_GET['preview'])) {
    echo $html;
    exit;
}

if (!$dompdfReady) {
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<div style="font-family:Arial,sans-serif;margin:12px;padding:12px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;">';
    echo '<strong>PDF belum bisa dibuat.</strong><br>File vendor/autoload.php atau library Dompdf belum tersedia. Tampilan di bawah ini adalah preview HTML. Jalankan composer install / upload folder vendor untuk mengaktifkan download PDF.';
    echo '</div>';
    echo $html;
    exit;
}

try {
    // Jangan biarkan handler utama mengubah warning/deprecated internal Dompdf menjadi exception.
    // Pada hosting tertentu ini membuat PDF gagal walaupun HTML sebenarnya bisa dirender.
    restore_error_handler();

    $dompdfTempDir = makeWritableDompdfDir();
    $pdfHtml = dompdfSafeHtml($html);

    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'Arial');
    $options->set('isFontSubsettingEnabled', false);
    $options->set('tempDir', $dompdfTempDir);
    $options->set('fontDir', $dompdfTempDir);
    $options->set('fontCache', $dompdfTempDir);
    if (method_exists($options, 'setIsHtml5ParserEnabled')) {
        $options->setIsHtml5ParserEnabled(true);
    }
    if (method_exists($options, 'setChroot')) {
        $options->setChroot(dirname(__DIR__));
    }

    // Render dengan level error yang lebih tenang agar warning/deprecated library tidak menghentikan PDF.
    $oldErrorReporting = error_reporting(E_ERROR | E_PARSE);
    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($pdfHtml, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    error_reporting($oldErrorReporting);

    $namaFile = preg_replace('/[^A-Za-z0-9_\-]/', '_', val($student, 'nama_lengkap', 'Siswa'));
    $filename = 'Data_Lengkap_Siswa_' . $namaFile . '.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
} catch (Throwable $e) {
    if (isset($oldErrorReporting)) {
        error_reporting($oldErrorReporting);
    }
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=UTF-8');
    }
    $debugDetail = $e->getMessage() . ' di ' . $e->getFile() . ':' . $e->getLine();
    $debugDetail .= "\nTempDir: " . (isset($dompdfTempDir) ? $dompdfTempDir : '-');
    $debugDetail .= "\nTempDir writable: " . ((isset($dompdfTempDir) && is_writable($dompdfTempDir)) ? 'ya' : 'tidak');
    $debugDetail .= "\nHTML length: " . strlen($html);
    $debugDetail .= "\nPDF HTML length: " . (isset($pdfHtml) ? strlen($pdfHtml) : 0);
    error_log('[student_pdf] ' . $debugDetail);

    echo '<div style="font-family:Arial,sans-serif;margin:12px;padding:12px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;">';
    echo '<strong>PDF belum bisa dibuat otomatis.</strong><br>Sistem menampilkan preview HTML agar data tetap bisa dicek/cetak. ';
    echo 'Tambahkan parameter <code>&debug=1</code> pada URL untuk melihat detail teknis.';
    if (isset($_GET['debug'])) {
        echo '<pre style="white-space:pre-wrap;margin-top:8px;">' . h($debugDetail) . '</pre>';
    }
    echo '<div style="margin-top:8px;"><button onclick="window.print()" style="padding:6px 10px;border:1px solid #842029;background:#fff;color:#842029;cursor:pointer;">Cetak / Simpan PDF dari Browser</button></div>';
    echo '</div>';
    echo $html;
    exit;
}
