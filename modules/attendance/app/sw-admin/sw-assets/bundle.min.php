<?php
if (empty($_GET['get'])) {
    echo 'Mod parameter is not set.';
    exit;
}

$allowed = [
    's-widodo.com' => [
        '../sw-assets/js/bundle.min.js',
        '../../template/vendor/html5-qrcode/minified/html5-qrcode.min.js',
        '../sw-assets/js/template-admin.js',
        '../sw-assets/js/demo.min.js',
    ],
];

$key = $_GET['get'];

if (!isset($allowed[$key])) {
    header("Content-Type: application/javascript");
    echo '// Invalid get parameter.';
    exit;
}

$files = $allowed[$key];

// Check if all files exist
foreach ($files as $f) {
    if (!file_exists($f)) {
        header("Content-Type: application/javascript");
        echo "// File $f not found.\n";
        exit;
    }
}

// Determine last modified time and ETag
$lastModifiedTimes = array_map('filemtime', $files);
$lastModified = max($lastModifiedTimes);

$combinedContents = '';
foreach ($files as $f) {
    $combinedContents .= file_get_contents($f) . "\n";
}

$etag = md5($combinedContents);
// Set headers
header("Content-Type: application/javascript");
header("Cache-Control: public, max-age=604800");
header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
header("ETag: \"$etag\"");

// Handle cache validation
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