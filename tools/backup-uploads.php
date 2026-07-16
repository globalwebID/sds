<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
if (!class_exists(ZipArchive::class)) throw new RuntimeException('Ekstensi ZipArchive belum tersedia.');

$root = dirname(__DIR__);
$source = realpath($root . '/uploads');
if (!$source || !is_dir($source)) throw new RuntimeException('Folder uploads tidak ditemukan.');
$backupDir = $root . '/storage/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0750, true) && !is_dir($backupDir)) {
    throw new RuntimeException('Folder backup tidak dapat dibuat.');
}
$output = $backupDir . '/sds_uploads_' . date('Ymd_His') . '.zip';
$zip = new ZipArchive();
if ($zip->open($output, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
    throw new RuntimeException('Arsip upload tidak dapat dibuat.');
}
$count = 0;
try {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    foreach ($iterator as $file) {
        if (!$file->isFile() || $file->isLink()) continue;
        $path = $file->getRealPath();
        if (!$path || !str_starts_with($path, $source . DIRECTORY_SEPARATOR)) continue;
        $relative = 'uploads/' . str_replace('\\', '/', substr($path, strlen($source) + 1));
        if (!$zip->addFile($path, $relative)) throw new RuntimeException('Gagal menambahkan file: ' . $relative);
        $count++;
    }
} finally {
    $zip->close();
}
if (!is_file($output) || filesize($output) < 1) {
    @unlink($output);
    throw new RuntimeException('Arsip upload kosong atau gagal dibuat.');
}
$hash = hash_file('sha256', $output);
if (!is_string($hash) || file_put_contents($output . '.sha256', $hash . '  ' . basename($output) . PHP_EOL, LOCK_EX) === false) {
    throw new RuntimeException('Checksum arsip upload tidak dapat dibuat.');
}
echo "Backup uploads berhasil\n";
echo 'File: ' . $output . PHP_EOL;
echo 'Jumlah file: ' . $count . PHP_EOL;
echo 'Ukuran: ' . number_format((int)filesize($output), 0, ',', '.') . " byte\n";
echo 'SHA-256: ' . $hash . PHP_EOL;
