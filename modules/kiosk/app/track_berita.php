<?php
require '../db.php';
require_once '../config/anjungan_runtime.php';
sdsAnjunganEnsureSchema($conn);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$id = max(0, (int)($_POST['id'] ?? 0));
if ($id < 1) {
    http_response_code(422);
    echo json_encode(['ok' => false]);
    exit;
}

$condition = sdsAnjunganPublishedCondition($conn);
$stmt = $conn->prepare("UPDATE `anjungan_berita` SET `dilihat` = COALESCE(`dilihat`, 0) + 1 WHERE `id` = ? AND {$condition}");
$stmt->bind_param('i', $id);
$stmt->execute();

echo json_encode(['ok' => true]);
