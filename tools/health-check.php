<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/config/runtime.php';
require_once $root . '/config/academic_year.php';

$failures = [];
$warnings = [];
$passes = [];
$pass = static function (string $message) use (&$passes): void { $passes[] = $message; };
$fail = static function (string $message) use (&$failures): void { $failures[] = $message; };
$warn = static function (string $message) use (&$warnings): void { $warnings[] = $message; };

foreach (['mysqli','mbstring','fileinfo','openssl','gd','zip'] as $extension) {
    extension_loaded($extension) ? $pass("Ekstensi {$extension} tersedia") : $fail("Ekstensi PHP {$extension} belum aktif");
}

if (!sds_is_installed()) $fail('Status instalasi belum terkunci');
else $pass('Installer terkunci');

foreach ([$root . '/uploads', $root . '/storage', $root . '/tmp_dompdf'] as $directory) {
    if (!is_dir($directory)) {
        if (!mkdir($directory, 0750, true) && !is_dir($directory)) $fail('Folder tidak tersedia: ' . $directory);
    }
    is_writable($directory) ? $pass('Folder writable: ' . basename($directory)) : $fail('Folder tidak writable: ' . $directory);
}

$baseUrl = (string)sds_config('app.base_url', '');
if ($baseUrl === '') $fail('app.base_url belum dikonfigurasi');
elseif (!str_starts_with(strtolower($baseUrl), 'https://')) $warn('app.base_url belum menggunakan HTTPS: ' . $baseUrl);
else $pass('Base URL menggunakan HTTPS');

try {
    $db = sds_mysqli('main');
    $pass('Koneksi database berhasil');
    $requiredTables = ['admins','pendaftaran_siswa','pengaturan','formulir','jurusan','kelas','siswa_kelas','user','absen','absensi_kelas','pegawai','jam_sekolah','setting','kartu_rfid','perpus_anggota','perpus_buku','perpus_buku_eksemplar','perpus_pengaturan','perpus_users'];
    $existing = [];
    $result = $db->query('SHOW TABLES');
    while ($row = $result->fetch_row()) $existing[(string)$row[0]] = true;
    $missing = array_values(array_filter($requiredTables, static fn(string $table): bool => !isset($existing[$table])));
    $missing ? $fail('Tabel wajib belum tersedia: ' . implode(', ', $missing)) : $pass('Seluruh tabel wajib tersedia');

    $adminCount = (int)$db->query("SELECT COUNT(*) total FROM admins")->fetch_assoc()['total'];
    $adminCount > 0 ? $pass("Akun admin tersedia ({$adminCount})") : $fail('Tidak ada akun admin');
    $academic = sds_academic_year_get_active($db);
    if (trim((string)($academic['tahun_ajaran'] ?? '')) === '') $fail('Tahun ajaran aktif belum tersedia');
    else $pass('Tahun ajaran aktif: ' . $academic['tahun_ajaran'] . ' ' . ($academic['semester_aktif'] ?? ''));

    $fkSql = "SELECT kcu.CONSTRAINT_NAME,kcu.TABLE_NAME,kcu.COLUMN_NAME,kcu.REFERENCED_TABLE_NAME,kcu.REFERENCED_COLUMN_NAME
              FROM information_schema.KEY_COLUMN_USAGE kcu
              WHERE kcu.TABLE_SCHEMA=DATABASE() AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
              ORDER BY kcu.TABLE_NAME,kcu.CONSTRAINT_NAME,kcu.ORDINAL_POSITION";
    $foreignKeys = $db->query($fkSql);
    $checked = 0;
    $orphans = [];
    while ($fk = $foreignKeys->fetch_assoc()) {
        $table = str_replace('`', '``', (string)$fk['TABLE_NAME']);
        $column = str_replace('`', '``', (string)$fk['COLUMN_NAME']);
        $refTable = str_replace('`', '``', (string)$fk['REFERENCED_TABLE_NAME']);
        $refColumn = str_replace('`', '``', (string)$fk['REFERENCED_COLUMN_NAME']);
        $sql = "SELECT COUNT(*) total FROM `{$table}` c LEFT JOIN `{$refTable}` p ON p.`{$refColumn}`=c.`{$column}` WHERE c.`{$column}` IS NOT NULL AND p.`{$refColumn}` IS NULL";
        $count = (int)$db->query($sql)->fetch_assoc()['total'];
        $checked++;
        if ($count > 0) $orphans[] = "{$table}.{$column} -> {$refTable}.{$refColumn}: {$count}";
    }
    $orphans ? $fail('Relasi database yatim: ' . implode('; ', $orphans)) : $pass("Relasi foreign key bersih ({$checked} pemeriksaan)");
    $db->close();
} catch (Throwable $e) {
    $fail('Pemeriksaan database gagal: ' . $e->getMessage());
}

$backups = glob($root . '/storage/backups/*.sql') ?: [];
usort($backups, static fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));
if (!$backups) $warn('Backup database belum tersedia');
else {
    $latest = $backups[0];
    $hashFile = $latest . '.sha256';
    if (!is_file($hashFile)) $fail('Checksum backup terbaru tidak tersedia');
    else {
        $expected = strtolower((string)preg_replace('/\s.*$/s', '', trim((string)file_get_contents($hashFile))));
        $actual = strtolower((string)hash_file('sha256', $latest));
        hash_equals($expected, $actual) ? $pass('Checksum backup terbaru cocok') : $fail('Checksum backup terbaru tidak cocok');
    }
}

foreach ($passes as $message) echo "[OK] {$message}\n";
foreach ($warnings as $message) echo "[WARN] {$message}\n";
foreach ($failures as $message) echo "[FAIL] {$message}\n";
echo "\nRingkasan: " . count($passes) . ' OK, ' . count($warnings) . ' peringatan, ' . count($failures) . " gagal\n";
exit($failures ? 1 : 0);
