<?php
if (empty($_GET['get'])) {
    echo 'Mod parameter is not set.';
    exit;
}

$allowed = [
    's-widodo.com' => [
        '../template/js/bundle.min.js',
        '../template/vendor/emojionearea/emojionearea.min.js',
    ],
];

$key = $_GET['get'];

// Validasi key dan file ada
if (!array_key_exists($key, $allowed)) {
    header("Content-Type: application/javascript");
    echo '// Invalid get parameter.';
    exit;
}

// Ambil array file
$files = $allowed[$key];

// Cek semua file ada
foreach ($files as $f) {
    if (!file_exists($f)) {
        header("Content-Type: application/javascript");
        echo "// File $f not found.\n";
        exit;
    }
}

// Cari Last Modified terbaru dari semua file
$lastModifiedTimes = array_map('filemtime', $files);
$lastModified = max($lastModifiedTimes);

// Buat ETag gabungan dari isi semua file
$combinedContents = '';
foreach ($files as $f) {
    $combinedContents .= file_get_contents($f) . "\n";
}
$etag = md5($combinedContents);

header("Content-Type: application/javascript");
header("Cache-Control: public, max-age=604800");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
header("ETag: \"$etag\"");

// Cek cache browser
if (
    (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $lastModified) ||
    (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH'], '"') === $etag)
) {
    header("HTTP/1.1 304 Not Modified");
    exit;
}

echo $combinedContents;
exit;
?>