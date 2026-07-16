<?php
include 'inc/fungsi.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

function setExcelText($sheet, string $cell, mixed $value): void
{
    $sheet->setCellValueExplicit($cell, (string)$value, DataType::TYPE_STRING);
}

function formatTanggalIndo($tanggal)
{
    $bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $tgl = date('j', strtotime($tanggal));
    $bln = intval(date('m', strtotime($tanggal))) - 1;
    $thn = date('Y', strtotime($tanggal));
    return "$tgl " . $bulanIndo[$bln] . " $thn";
}

function getNamaBulanIndo($bulan, $tahun)
{
    $bulanIndo = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    return $bulanIndo[(int)$bulan - 1] . ' ' . $tahun;
}

// Parameter
$jenis = $_GET['jenis'] ?? 'semua';
$bulan = $_GET['bulan'] ?? '';
$tahun = $_GET['tahun'] ?? date('Y');
$bulanNama = $bulan ? getNamaBulanIndo($bulan, $tahun) : 'Semua Bulan';

// ===== RULE TOPUP VALID =====
$filterTopupValidSQL = "
(
  (tp.merchant_order_id IS NULL AND tp.duitku_reference IS NULL)
  OR tp.status = 'PAID'
)
";

// Filter tanggal
$whereDate = ($bulan !== '')
    ? "MONTH(waktu) = " . intval($bulan) . " AND YEAR(waktu) = " . intval($tahun)
    : "YEAR(waktu) = " . intval($tahun);

// Judul & Query
switch ($jenis) {
    case 'semua':
        $judulLaporan = "LAPORAN SEMUA TRANSAKSI";
        // pakai derived table supaya filter tanggal gampang & konsisten
        $query = "
            SELECT *
            FROM (
                SELECT 
                    t.tanggal AS waktu,
                    s.nama_lengkap AS nama_siswa,
                    'Pembelian' AS jenis,
                    k.nama AS detail,
                    '-' AS sumber_topup,
                    '-' AS petugas_topup,
                    t.nominal AS nominal
                FROM transaksi_kantin t
                JOIN pendaftaran_siswa s ON t.id_siswa = s.id
                JOIN kantin k ON t.id_kantin = k.id

                UNION ALL

                SELECT 
                    tp.tanggal AS waktu,
                    s.nama_lengkap AS nama_siswa,
                    'Topup' AS jenis,
                    '-' AS detail,
                    CASE
                        WHEN (tp.merchant_order_id IS NULL AND tp.duitku_reference IS NULL) THEN 'Sekolah'
                        ELSE 'Merchant'
                    END AS sumber_topup,
                    COALESCE(u.username, '-') AS petugas_topup,
                    tp.nominal AS nominal
                FROM topup tp
                JOIN pendaftaran_siswa s ON tp.id_siswa = s.id
                LEFT JOIN users u ON tp.petugas_id = u.id
                WHERE $filterTopupValidSQL

                UNION ALL

                SELECT 
                    p.tanggal AS waktu,
                    '-' AS nama_siswa,
                    'Penarikan' AS jenis,
                    k.nama AS detail,
                    '-' AS sumber_topup,
                    '-' AS petugas_topup,
                    p.jumlah AS nominal
                FROM penarikan p
                JOIN kantin k ON p.id_kantin = k.id

                UNION ALL

                SELECT 
                    l.tanggal AS waktu,
                    pengirim.nama_lengkap AS nama_siswa,
                    'Transfer Keluar' AS jenis,
                    penerima.nama_lengkap AS detail,
                    '-' AS sumber_topup,
                    '-' AS petugas_topup,
                    l.jumlah AS nominal
                FROM log_transfer l
                JOIN pendaftaran_siswa pengirim ON l.id_pengirim = pengirim.id
                JOIN pendaftaran_siswa penerima ON l.id_penerima = penerima.id

                UNION ALL

                SELECT 
                    l.tanggal AS waktu,
                    penerima.nama_lengkap AS nama_siswa,
                    'Transfer Masuk' AS jenis,
                    pengirim.nama_lengkap AS detail,
                    '-' AS sumber_topup,
                    '-' AS petugas_topup,
                    l.jumlah AS nominal
                FROM log_transfer l
                JOIN pendaftaran_siswa pengirim ON l.id_pengirim = pengirim.id
                JOIN pendaftaran_siswa penerima ON l.id_penerima = penerima.id
            ) x
            WHERE $whereDate
            ORDER BY waktu DESC
        ";
        break;

    case 'kantin':
        $judulLaporan = "LAPORAN PEMBELIAN KANTIN";
        $query = "SELECT 
                    t.tanggal AS waktu,
                    s.nama_lengkap AS nama_siswa,
                    'Pembelian' AS jenis,
                    k.nama AS nama_kantin,
                    t.nominal
                  FROM transaksi_kantin t
                  JOIN pendaftaran_siswa s ON t.id_siswa = s.id
                  JOIN kantin k ON t.id_kantin = k.id
                  WHERE " . str_replace('waktu', 't.tanggal', $whereDate) . "
                  ORDER BY t.tanggal DESC";
        break;

    case 'topup':
        $judulLaporan = "LAPORAN TOP UP SALDO";
        $query = "SELECT 
                    tp.tanggal AS waktu,
                    s.nama_lengkap AS nama_siswa,
                    'Topup' AS jenis,
                    CASE
                      WHEN (tp.merchant_order_id IS NULL AND tp.duitku_reference IS NULL) THEN 'Sekolah'
                      ELSE 'Merchant'
                    END AS sumber_topup,
                    COALESCE(u.username, '-') AS petugas_topup,
                    tp.nominal
                  FROM topup tp
                  JOIN pendaftaran_siswa s ON tp.id_siswa = s.id
                  LEFT JOIN users u ON tp.petugas_id = u.id
                  WHERE $filterTopupValidSQL
                    AND " . str_replace('waktu', 'tp.tanggal', $whereDate) . "
                  ORDER BY tp.tanggal DESC";
        break;

    case 'penarikan':
        $judulLaporan = "LAPORAN PENARIKAN DANA";
        $query = "SELECT 
                    p.tanggal AS waktu,
                    'Penarikan' AS jenis,
                    k.nama AS nama_kantin,
                    p.jumlah AS nominal
                  FROM penarikan p
                  JOIN kantin k ON p.id_kantin = k.id
                  WHERE " . str_replace('waktu', 'p.tanggal', $whereDate) . "
                  ORDER BY p.tanggal DESC";
        break;

    case 'transfer-masuk':
        $judulLaporan = "LAPORAN TRANSFER MASUK";
        $query = "SELECT 
                    t.tanggal AS waktu,
                    peng.nama_lengkap AS nama_pengirim,
                    penerima.nama_lengkap AS nama_penerima,
                    t.jumlah
                  FROM log_transfer t
                  JOIN pendaftaran_siswa peng ON t.id_pengirim = peng.id
                  JOIN pendaftaran_siswa penerima ON t.id_penerima = penerima.id
                  WHERE " . str_replace('waktu', 't.tanggal', $whereDate) . "
                  ORDER BY t.tanggal DESC";
        break;

    case 'transfer-keluar':
        $judulLaporan = "LAPORAN TRANSFER KELUAR";
        $query = "SELECT 
                    t.tanggal AS waktu,
                    peng.nama_lengkap AS nama_pengirim,
                    penerima.nama_lengkap AS nama_penerima,
                    t.jumlah
                  FROM log_transfer t
                  JOIN pendaftaran_siswa peng ON t.id_pengirim = peng.id
                  JOIN pendaftaran_siswa penerima ON t.id_penerima = penerima.id
                  WHERE " . str_replace('waktu', 't.tanggal', $whereDate) . "
                  ORDER BY t.tanggal DESC";
        break;

    default:
        die('Jenis tidak valid');
}

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query error: " . mysqli_error($conn));
}

// Buat Spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$rowHeader = 4;

// ===== Header kolom dinamis =====
if ($jenis === 'semua') {
    $headers = ['No.', 'Tanggal', 'Jam', 'Nama', 'Jenis', 'Detail', 'Sumber', 'Petugas', 'Nominal'];
    $lastColumn = 'I';
    $totalLabelCol = 'H';
    $totalValueCol = 'I';
} elseif (strpos($jenis, 'transfer') === 0) {
    $headers = ['No.', 'Tanggal', 'Jam', 'Nama Pengirim', 'Nama Penerima', 'Jumlah'];
    $lastColumn = 'F';
    $totalLabelCol = 'E';
    $totalValueCol = 'F';
} elseif ($jenis === 'penarikan') {
    $headers = ['No.', 'Tanggal', 'Jam', 'Jenis', 'Kantin', 'Nominal'];
    $lastColumn = 'F';
    $totalLabelCol = 'E';
    $totalValueCol = 'F';
} elseif ($jenis === 'topup') {
    // ✅ tambah Petugas
    $headers = ['No.', 'Tanggal', 'Jam', 'Nama Siswa', 'Jenis', 'Sumber', 'Petugas', 'Nominal'];
    $lastColumn = 'H';
    $totalLabelCol = 'G';
    $totalValueCol = 'H';
} else {
    $headers = ['No.', 'Tanggal', 'Jam', 'Nama Siswa', 'Jenis', 'Kantin', 'Nominal'];
    $lastColumn = 'G';
    $totalLabelCol = 'F';
    $totalValueCol = 'G';
}

// Judul (merge mengikuti lastColumn)
$sheet->setCellValue('A1', $judulLaporan)->mergeCells("A1:{$lastColumn}1");
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

$sheet->setCellValue('A2', 'Bulan: ' . $bulanNama)->mergeCells("A2:{$lastColumn}2");
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

// Tulis header
$col = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($col++ . $rowHeader, $header);
}

// Isi data
$row = $rowHeader + 1;
$no = 1;

while ($log = mysqli_fetch_assoc($result)) {
    $tgl = formatTanggalIndo($log['waktu']);
    $jam = date('H:i', strtotime($log['waktu']));
    $col = 'A';

    $sheet->setCellValue($col++ . $row, $no);
    $sheet->setCellValue($col++ . $row, $tgl);
    $sheet->setCellValue($col++ . $row, $jam);

    if ($jenis === 'semua') {
        setExcelText($sheet, $col++ . $row, $log['nama_siswa'] ?? '-');
        setExcelText($sheet, $col++ . $row, $log['jenis'] ?? '-');
        setExcelText($sheet, $col++ . $row, $log['detail'] ?? '-');
        setExcelText($sheet, $col++ . $row, $log['sumber_topup'] ?? '-');
        setExcelText($sheet, $col++ . $row, $log['petugas_topup'] ?? '-');
        $cell = $col++ . $row;
        $sheet->setCellValue($cell, (int)($log['nominal'] ?? 0));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');

    } elseif (strpos($jenis, 'transfer') === 0) {
        setExcelText($sheet, $col++ . $row, $log['nama_pengirim'] ?? '');
        setExcelText($sheet, $col++ . $row, $log['nama_penerima'] ?? '');
        $cell = $col++ . $row;
        $sheet->setCellValue($cell, (int)($log['jumlah'] ?? 0));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');

    } elseif ($jenis === 'penarikan') {
        setExcelText($sheet, $col++ . $row, $log['jenis'] ?? 'Penarikan');
        setExcelText($sheet, $col++ . $row, $log['nama_kantin'] ?? '');
        $cell = $col++ . $row;
        $sheet->setCellValue($cell, (int)($log['nominal'] ?? 0));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');

    } elseif ($jenis === 'topup') {
        setExcelText($sheet, $col++ . $row, $log['nama_siswa'] ?? '');
        setExcelText($sheet, $col++ . $row, $log['jenis'] ?? 'Topup');
        setExcelText($sheet, $col++ . $row, $log['sumber_topup'] ?? '');
        setExcelText($sheet, $col++ . $row, $log['petugas_topup'] ?? '-');
        $cell = $col++ . $row;
        $sheet->setCellValue($cell, (int)($log['nominal'] ?? 0));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');

    } else {
        setExcelText($sheet, $col++ . $row, $log['nama_siswa'] ?? '');
        setExcelText($sheet, $col++ . $row, $log['jenis'] ?? '');
        setExcelText($sheet, $col++ . $row, $log['nama_kantin'] ?? '-');
        $cell = $col++ . $row;
        $sheet->setCellValue($cell, (int)($log['nominal'] ?? 0));
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('#,##0');
    }

    $no++;
    $row++;
}

// Total
$totalRow = $row;
$sheet->setCellValue("{$totalLabelCol}{$totalRow}", 'Total');
$sheet->setCellValue("{$totalValueCol}{$totalRow}", "=SUM({$totalValueCol}" . ($rowHeader + 1) . ":{$totalValueCol}" . ($totalRow - 1) . ")");
$sheet->getStyle("{$totalValueCol}{$totalRow}")->getNumberFormat()->setFormatCode('#,##0');

$sheet->getStyle("{$totalLabelCol}{$totalRow}:{$totalValueCol}{$totalRow}")->applyFromArray([
    'font' => ['bold' => true],
    'borders' => ['top' => ['borderStyle' => Border::BORDER_THIN]],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
]);

// Header styling
$sheet->getStyle("A{$rowHeader}:{$lastColumn}{$rowHeader}")->applyFromArray([
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D9D9D9']],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
]);

// Isi tabel styling
if ($totalRow > ($rowHeader + 1)) {
    $sheet->getStyle("A" . ($rowHeader + 1) . ":{$lastColumn}" . ($totalRow - 1))->applyFromArray([
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
    ]);
}

// Auto-size
foreach (range('A', $lastColumn) as $c) {
    $sheet->getColumnDimension($c)->setAutoSize(true);
}

// Output file
$filename = 'laporan_' . $jenis . '_' . date('Ymd_His') . '.xlsx';
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
