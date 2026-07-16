<?php
include 'inc/fungsi.php';
// date_default_timezone_set('Asia/Jakarta');

header('Content-Type: application/json');

$id_kantin = $_SESSION['id_kantin'] ?? 0;

// Ambil 5 transaksi terakhir untuk kantin ini
$result = mysqli_query($conn, "
SELECT t.id, t.tanggal, s.nama_lengkap AS nama_siswa, t.nominal, t.status_dilayani
FROM transaksi_kantin t
JOIN pendaftaran_siswa s ON t.id_siswa = s.id
WHERE t.id_kantin = $id_kantin
  AND DATE(t.tanggal) = CURDATE()
ORDER BY t.tanggal DESC
LIMIT 10
");

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
  $data[] = $row;
}

echo json_encode($data);
