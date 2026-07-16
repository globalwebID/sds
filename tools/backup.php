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
if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Folder backup tidak dapat dibuat.');
}

$stamp = date('Ymd_His');
$database = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$cfg['database']);
$output = $backupDir . '/sds_' . $database . '_' . $stamp . '.sql';
$xamppRoot = dirname(dirname(PHP_BINARY));
$dumpBinary = $xamppRoot . '/mysql/bin/mysqldump.exe';
if (!is_file($dumpBinary)) {
    $dumpBinary = 'mysqldump';
}

$escapeIni = static fn(string $value): string => '"' . str_replace(['\\', '"'], ['\\\\', '\\"'], $value) . '"';
$defaultsFile = tempnam(sys_get_temp_dir(), 'sds-db-');
if ($defaultsFile === false) throw new RuntimeException('File konfigurasi sementara tidak dapat dibuat.');

$defaults = "[client]\n"
    . 'host=' . $escapeIni((string)$cfg['host']) . "\n"
    . 'port=' . (int)$cfg['port'] . "\n"
    . 'user=' . $escapeIni((string)$cfg['username']) . "\n"
    . 'password=' . $escapeIni((string)$cfg['password']) . "\n"
    . "default-character-set=utf8mb4\n";

try {
    if (file_put_contents($defaultsFile, $defaults, LOCK_EX) === false) {
        throw new RuntimeException('Konfigurasi backup sementara tidak dapat ditulis.');
    }
    @chmod($defaultsFile, 0600);

    $command = [
        $dumpBinary,
        '--defaults-extra-file=' . $defaultsFile,
        '--single-transaction',
        '--quick',
        '--routines',
        '--triggers',
        '--events',
        '--hex-blob',
        '--default-character-set=utf8mb4',
        (string)$cfg['database'],
        '--result-file=' . $output,
    ];
    $pipes = [];
    $process = proc_open($command, [0 => ['pipe','r'], 1 => ['pipe','w'], 2 => ['pipe','w']], $pipes, $root);
    if (!is_resource($process)) throw new RuntimeException('mysqldump tidak dapat dijalankan.');
    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        @unlink($output);
        throw new RuntimeException('Backup database gagal: ' . trim((string)($stderr ?: $stdout)));
    }
} finally {
    @unlink($defaultsFile);
}

if (!is_file($output) || filesize($output) < 1024) {
    @unlink($output);
    throw new RuntimeException('Hasil backup kosong atau tidak lengkap.');
}

$hash = hash_file('sha256', $output);
if (!is_string($hash) || file_put_contents($output . '.sha256', $hash . '  ' . basename($output) . PHP_EOL, LOCK_EX) === false) {
    throw new RuntimeException('Checksum backup tidak dapat dibuat.');
}

echo "Backup berhasil\n";
echo 'File: ' . $output . PHP_EOL;
echo 'Ukuran: ' . number_format((int)filesize($output), 0, ',', '.') . " byte\n";
echo 'SHA-256: ' . $hash . PHP_EOL;
