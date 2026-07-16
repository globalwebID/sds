<?php 
function uploadFile($field, $allowed, $maxMB = 10, $subfolder = '', $existing = '')
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] == 4) {
        return $existing;
    }

    $f = $_FILES[$field];
    if ((int)$f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        die("Upload $field tidak valid");
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        die("Format file $field tidak diizinkan");
    }

    if ($f['size'] / (1024 * 1024) > $maxMB) {
        die("Ukuran file $field > {$maxMB} MB");
    }

    $mimeMap = [
        'pdf' => ['application/pdf'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
    ];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    if (!isset($mimeMap[$ext]) || !in_array($mime, $mimeMap[$ext], true)) {
        die("Isi file $field tidak sesuai format");
    }

    $subfolder = preg_replace('/[^A-Za-z0-9_.-]/', '_', trim((string)$subfolder, '/\\'));
    $dir = dirname(__DIR__, 2) . '/uploads/' . ($subfolder !== '' ? $subfolder . '/' : 'umum/');

    // Buat folder jika belum ada
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    // Buat nama file yang aman dan unik
    $cleanName = preg_replace('/[^A-Za-z0-9_.-]/', '_', basename($f['name']));
    $filename = uniqid() . "_" . $cleanName;

    // Path lengkap untuk menyimpan file
    $path = $dir . $filename;

    // Proses upload file
    if (!move_uploaded_file($f['tmp_name'], $path)) {
        die("Gagal unggah $field");
    }

    // Kembalikan path relatif dari folder publik (untuk disimpan di database)
    return ($subfolder !== '' ? $subfolder : 'umum') . '/' . $filename;
}
