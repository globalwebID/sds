<?php
include 'inc/fungsi.php';

$query = "
    SELECT DATE(tanggal) as tgl, SUM(nominal) as total
    FROM transaksi_kantin
    WHERE id_kantin = $id_kantin
    GROUP BY DATE(tanggal)
    ORDER BY tgl DESC
    LIMIT 7
";

$result = mysqli_query($conn, $query);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
        'tanggal' => date('d M', strtotime($row['tgl'])),
        'total' => (int)$row['total']
    ];
}

// Balikkan urutan agar tanggal terbaru di kanan
$data = array_reverse($data);

echo json_encode($data);
