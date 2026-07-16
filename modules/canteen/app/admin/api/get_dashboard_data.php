<?php
include '../middleware/auth.php';
include '../middleware/role_check.php';
include '../../../config/db.php';
header('Content-Type: application/json');

function q1($conn, string $sql): int {
    $r = mysqli_query($conn, $sql);
    if (!$r) return 0;
    $row = mysqli_fetch_assoc($r);
    return (int)($row['total'] ?? 0);
}
function qsum($conn, string $sql): int {
    $r = mysqli_query($conn, $sql);
    if (!$r) return 0;
    $row = mysqli_fetch_assoc($r);
    return (int)($row['total'] ?? 0);
}

/**
 * RULE:
 * - Topup manual: merchant_order_id & duitku_reference NULL => tetap dihitung walau status PENDING
 * - Topup Duitku: merchant_order_id atau duitku_reference NOT NULL => hanya dihitung jika status = PAID
 */
$filter_topup_valid = "
(
  (merchant_order_id IS NULL AND duitku_reference IS NULL)
  OR status = 'PAID'
)
";

// ====== TOTAL TRANSAKSI (COUNT) ======
$total_transaksi_kantin = q1($conn, "SELECT COUNT(*) AS total FROM transaksi_kantin");
$total_topup_valid      = q1($conn, "SELECT COUNT(*) AS total FROM topup WHERE $filter_topup_valid");
$total_transaksi        = $total_transaksi_kantin + $total_topup_valid;

// ====== TRANSAKSI HARI INI (COUNT) ======
$total_transaksi_kantin_hari_ini = q1($conn, "SELECT COUNT(*) AS total FROM transaksi_kantin WHERE DATE(tanggal)=CURDATE()");
$total_topup_hari_ini_valid      = q1($conn, "SELECT COUNT(*) AS total FROM topup WHERE DATE(tanggal)=CURDATE() AND $filter_topup_valid");
$total_transaksi_hari_ini        = $total_transaksi_kantin_hari_ini + $total_topup_hari_ini_valid;

// ====== SALDO SISWA ======
$total_saldo = qsum($conn, "SELECT COALESCE(SUM(saldo),0) AS total FROM pendaftaran_siswa");

// ====== NOMINAL KANTIN & TOPUP (SUM) ======
$total_nominal_kantin = qsum($conn, "SELECT COALESCE(SUM(saldo),0) AS total FROM kantin");
$total_nominal_topup  = qsum($conn, "SELECT COALESCE(SUM(nominal),0) AS total FROM topup WHERE $filter_topup_valid");
$total_nominal_transaksi = $total_nominal_kantin + $total_nominal_topup;

// ====== NOMINAL HARI INI (SUM) ======
$total_nominal_kantin_hari_ini = qsum($conn, "SELECT COALESCE(SUM(nominal),0) AS total FROM transaksi_kantin WHERE DATE(tanggal)=CURDATE()");
$total_nominal_topup_hari_ini  = qsum($conn, "SELECT COALESCE(SUM(nominal),0) AS total FROM topup WHERE DATE(tanggal)=CURDATE() AND $filter_topup_valid");
$total_nominal_transaksi_hari_ini = $total_nominal_kantin_hari_ini + $total_nominal_topup_hari_ini;

// ====== SALDO SAAT INI (tetap mengikuti logika kamu) ======
$total_saldo_sekarang = $total_saldo + $total_nominal_kantin;

// ====== LOG TERBARU (TOPUP DI FILTER) ======
$log_result = mysqli_query($conn, "
    SELECT t.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, k.nama AS nama_kantin, t.nominal, 'Pembelian' AS jenis
    FROM transaksi_kantin t
    JOIN pendaftaran_siswa s ON t.id_siswa = s.id
    JOIN kantin k ON t.id_kantin = k.id

    UNION ALL

    SELECT tp.tanggal AS waktu, s.nama_lengkap AS nama_siswa, s.rfid_uid, '-' AS nama_kantin, tp.nominal, 'Topup' AS jenis
    FROM topup tp
    JOIN pendaftaran_siswa s ON tp.id_siswa = s.id
    WHERE $filter_topup_valid

    ORDER BY waktu DESC
    LIMIT 10
");

$log = [];
if ($log_result) {
    while ($row = mysqli_fetch_assoc($log_result)) {
        $log[] = [
            'waktu' => date('d M Y H:i', strtotime($row['waktu'])),
            'nama_siswa' => $row['nama_siswa'],
            'jenis' => $row['jenis'],
            'nama_kantin' => $row['nama_kantin'],
            'nominal' => (int)$row['nominal'],
        ];
    }
}

// ====== CEK PENARIKAN ======
$penarikan_total = q1($conn, "SELECT COUNT(*) AS total FROM penarikan WHERE status='diproses'");
$ada_penarikan = $penarikan_total > 0;

echo json_encode([
    'total_transaksi_hari_ini' => $total_transaksi_hari_ini,
    'total_nominal_transaksi_hari_ini' => $total_nominal_transaksi_hari_ini,
    'total_transaksi' => $total_transaksi,
    'total_nominal_transaksi' => $total_nominal_transaksi,
    'total_saldo' => $total_saldo,
    'total_nominal_kantin' => $total_nominal_kantin,
    'total_nominal_topup' => $total_nominal_topup,
    'total_saldo_sekarang' => $total_saldo_sekarang,
    'log' => $log,
    'ada_penarikan' => $ada_penarikan
]);
