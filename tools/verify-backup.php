<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/config/runtime.php';
$cfg = sds_database_config('main');
$backupDir = $root . '/storage/backups';
$requested = isset($argv[1]) ? (string)$argv[1] : '';
if ($requested === '') {
    $files = glob($backupDir . '/*.sql') ?: [];
    usort($files, static fn(string $a, string $b): int => filemtime($b) <=> filemtime($a));
    $requested = $files[0] ?? '';
}
$backup = realpath($requested);
$backupRoot = realpath($backupDir);
if (!$backup || !$backupRoot || !str_starts_with($backup, $backupRoot . DIRECTORY_SEPARATOR) || !is_file($backup)) {
    throw new RuntimeException('File backup tidak valid atau berada di luar storage/backups.');
}
$expectedHashFile = $backup . '.sha256';
if (!is_file($expectedHashFile)) throw new RuntimeException('Checksum backup tidak ditemukan.');
$expectedHash = strtolower((string)preg_replace('/\s.*$/s', '', trim((string)file_get_contents($expectedHashFile))));
$actualHash = strtolower((string)hash_file('sha256', $backup));
if ($expectedHash === '' || !hash_equals($expectedHash, $actualHash)) {
    throw new RuntimeException('Checksum backup tidak cocok. File mungkin rusak atau berubah.');
}

$temporaryDb = 'sds_restore_test_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
$xamppRoot = dirname(dirname(PHP_BINARY));
$mysqlBinary = $xamppRoot . '/mysql/bin/mysql.exe';
if (!is_file($mysqlBinary)) $mysqlBinary = 'mysql';
$escapeIni = static fn(string $value): string => '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
$defaultsFile = tempnam(sys_get_temp_dir(), 'sds-db-');
if ($defaultsFile === false) throw new RuntimeException('File konfigurasi sementara tidak dapat dibuat.');
$defaults = "[client]\n"
    . 'host=' . $escapeIni((string)$cfg['host']) . "\n"
    . 'port=' . (int)$cfg['port'] . "\n"
    . 'user=' . $escapeIni((string)$cfg['username']) . "\n"
    . 'password=' . $escapeIni((string)$cfg['password']) . "\n"
    . "default-character-set=utf8mb4\n";

$server = null;
try {
    if (file_put_contents($defaultsFile, $defaults, LOCK_EX) === false) throw new RuntimeException('Konfigurasi sementara gagal ditulis.');
    @chmod($defaultsFile, 0600);
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $server = new mysqli((string)$cfg['host'], (string)$cfg['username'], (string)$cfg['password'], '', (int)$cfg['port']);
    $server->set_charset('utf8mb4');
    $server->query("CREATE DATABASE `{$temporaryDb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $pipes = [];
    $command = [$mysqlBinary, '--defaults-extra-file=' . $defaultsFile, '--database=' . $temporaryDb];
    $process = proc_open($command, [0 => ['file',$backup,'r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, $root);
    if (!is_resource($process)) throw new RuntimeException('Proses restore tidak dapat dijalankan.');
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0) throw new RuntimeException('Uji restore gagal: ' . trim((string)($stderr ?: $stdout)));

    $sourceCount = (int)$server->query("SELECT COUNT(*) total FROM information_schema.tables WHERE table_schema='" . $server->real_escape_string((string)$cfg['database']) . "'")->fetch_assoc()['total'];
    $restoredCount = (int)$server->query("SELECT COUNT(*) total FROM information_schema.tables WHERE table_schema='" . $server->real_escape_string($temporaryDb) . "'")->fetch_assoc()['total'];
    if ($sourceCount < 1 || $restoredCount !== $sourceCount) {
        throw new RuntimeException("Jumlah tabel hasil restore tidak cocok (sumber {$sourceCount}, hasil {$restoredCount}).");
    }
    echo "Verifikasi backup berhasil\n";
    echo 'File: ' . $backup . PHP_EOL;
    echo 'Checksum: cocok' . PHP_EOL;
    echo 'Tabel dipulihkan: ' . $restoredCount . PHP_EOL;
} finally {
    if ($server instanceof mysqli) {
        try { $server->query("DROP DATABASE IF EXISTS `{$temporaryDb}`"); } catch (Throwable $ignored) {}
        $server->close();
    }
    @unlink($defaultsFile);
}
