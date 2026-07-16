<?php
include '../../../config/db.php';

$filter_topup_valid = "
(
  (merchant_order_id IS NULL AND duitku_reference IS NULL)
  OR status = 'PAID'
)
";

$result = mysqli_query($conn, "
    SELECT t.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, k.nama AS nama_kantin,
           t.nominal, 'Pembelian' AS jenis, '-' AS sumber_topup
    FROM transaksi_kantin t
    JOIN pendaftaran_siswa s ON t.id_siswa = s.id
    JOIN kantin k ON t.id_kantin = k.id

    UNION ALL

    SELECT tp.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, '-' AS nama_kantin,
           tp.nominal, 'Topup' AS jenis,
           CASE
             WHEN (tp.merchant_order_id IS NULL AND tp.duitku_reference IS NULL) THEN 'MANUAL'
             ELSE 'DUITKU'
           END AS sumber_topup
    FROM topup tp
    JOIN pendaftaran_siswa s ON tp.id_siswa = s.id
    WHERE $filter_topup_valid

    UNION ALL

    SELECT p.tanggal AS waktu, '-' AS nama_siswa, '-' AS rfid_uid, k.nama AS nama_kantin,
           p.jumlah AS nominal, 'Penarikan' AS jenis, '-' AS sumber_topup
    FROM penarikan p
    JOIN kantin k ON p.id_kantin = k.id

    UNION ALL

    SELECT l.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, '-' AS nama_kantin,
           l.jumlah AS nominal, 'Transfer Keluar' AS jenis, '-' AS sumber_topup
    FROM log_transfer l
    JOIN pendaftaran_siswa s ON l.id_pengirim = s.id

    UNION ALL

    SELECT l.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, '-' AS nama_kantin,
           l.jumlah AS nominal, 'Transfer Masuk' AS jenis, '-' AS sumber_topup
    FROM log_transfer l
    JOIN pendaftaran_siswa s ON l.id_penerima = s.id

    ORDER BY waktu DESC
    LIMIT 5
");

$data = '';
while ($log = mysqli_fetch_assoc($result)) {
    $jenis = $log['jenis'];

    // Badge Jenis
    switch ($jenis) {
        case 'Pembelian':       $badgeClass = 'badge bg-danger'; break;
        case 'Topup':           $badgeClass = 'badge bg-success'; break;
        case 'Penarikan':       $badgeClass = 'badge bg-warning text-dark'; break;
        case 'Transfer Keluar': $badgeClass = 'badge bg-secondary'; break;
        case 'Transfer Masuk':  $badgeClass = 'badge bg-primary'; break;
        default:                $badgeClass = 'badge bg-light text-dark';
    }

    // Badge Sumber Topup (hanya tampil kalau jenis=Topup)
    $sumberBadge = '';
    if ($jenis === 'Topup') {
        if (($log['sumber_topup'] ?? '') === 'DUITKU') {
            $sumberBadge = ' <span class="badge bg-info text-dark ms-1">Merchant</span>';
        } else {
            $sumberBadge = ' <span class="badge bg-dark ms-1">Sekolah</span>';
        }
    }

    $data .= '<tr>
        <td>' . date('d M Y H:i', strtotime($log['waktu'])) . '</td>
        <td>' . htmlspecialchars($log['nama_siswa']) . '</td>
        <td style="display:flex"><span class="w-100 ' . $badgeClass . '">' . htmlspecialchars($jenis) . '</span>' . $sumberBadge . '</td>
        <td>' . htmlspecialchars($log['nama_kantin']) . '</td>
        <td>Rp ' . number_format((int)$log['nominal'], 0, ',', '.') . '</td>
    </tr>';
}

echo $data;
?>
