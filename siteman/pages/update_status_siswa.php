<?php
if (empty($_SESSION['admin_id']) || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Permintaan tidak valid.'); }
$id = (int)($_POST['siswa_id'] ?? 0);
$status = (int)($_POST['status'] ?? 1);
$alasan = $_POST['alasan'] ?? null;
if ($id <= 0 || !in_array($status, [0,1], true)) { http_response_code(400); exit('Data tidak valid.'); }

if ($status == 0 && !$alasan) {
    http_response_code(400);
    echo "Alasan wajib diisi saat menonaktifkan.";
    exit;
}

$stmt = $conn->prepare("UPDATE pendaftaran_siswa SET status_aktif = ?, alasan_nonaktif = ? WHERE id = ?");
$stmt->bind_param('isi', $status, $alasan, $id);
$stmt->execute();

catatLog($conn, (int)$_SESSION['admin_id'], 'Status Siswa', "Mengubah status siswa ID {$id} menjadi {$status}");

echo 'OK';
