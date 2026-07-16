<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../../db.php';

$id_siswa = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id_siswa <= 0) {
    echo '<tr><td colspan="5" class="text-danger text-center">ID siswa tidak valid.</td></tr>';
    exit;
}

$filter = '';
if (!empty($_GET['bulan']) && !empty($_GET['tahun'])) {
    $bulan = intval($_GET['bulan']);
    $tahun = intval($_GET['tahun']);
    $filter = "AND MONTH(tanggal) = $bulan AND YEAR(tanggal) = $tahun";
}

$query = "
    SELECT t.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, k.nama AS nama_kantin, t.nominal, 'Pembelian' AS jenis
    FROM transaksi_kantin t
    JOIN pendaftaran_siswa s ON t.id_siswa = s.id
    JOIN kantin k ON t.id_kantin = k.id
    WHERE s.id = $id_siswa $filter

    UNION ALL

    SELECT tp.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, '-' AS nama_kantin, tp.nominal, 'Topup' AS jenis
    FROM topup tp
    JOIN pendaftaran_siswa s ON tp.id_siswa = s.id
    WHERE s.id = $id_siswa $filter

    ORDER BY waktu DESC
";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo '<tr><td colspan="5" class="text-danger text-center">Query error: ' . htmlspecialchars(mysqli_error($conn)) . '</td></tr>';
    exit;
}

$jumlah_total = 0;
$output = '';

while ($log = mysqli_fetch_assoc($result)) {
    $jumlah_total += $log['nominal'];

    $badgeClass = 'badge ';
    switch ($log['jenis']) {
        case 'Pembelian':
            $badgeClass .= 'bg-danger';
            break;
        case 'Topup':
            $badgeClass .= 'bg-success';
            break;
        case 'Penarikan':
            $badgeClass .= 'bg-warning text-dark';
            break;
        default:
            $badgeClass .= 'bg-secondary';
            break;
    }

    $output .= '<tr>
        <td>' . date('d M Y H:i', strtotime($log['waktu'])) . '</td>
        <td><span class="' . $badgeClass . '">' . htmlspecialchars($log['jenis']) . '</span></td>
        <td>' . htmlspecialchars($log['nama_kantin']) . '</td>
        <td class="text-end">Rp ' . number_format($log['nominal'], 0, ',', '.') . '</td>
    </tr>';
}

// Tambahkan total nominal jika ada data
if ($output) {
    $output .= '<tr class="table-secondary fw-bold">
        <td colspan="3" class="text-end">Jumlah Total</td>
        <td class="text-end">Rp ' . number_format($jumlah_total, 0, ',', '.') . '</td>
    </tr>';
}

echo $output ?: '<tr><td colspan="5" class="text-center">Belum ada transaksi.</td></tr>';
