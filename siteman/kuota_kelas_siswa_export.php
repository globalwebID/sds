<?php
session_start();
require '../db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

function sds_export_clean_filename($text) {
    $text = preg_replace('/[^A-Za-z0-9_\-]+/', '_', (string)$text);
    $text = trim($text, '_');
    return $text !== '' ? $text : 'data';
}

function sds_export_text_cell($sheet, $col, $row, $value) {
    $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit((string)($value ?? ''), DataType::TYPE_STRING);
}

function sds_export_jk($value) {
    $v = strtoupper(trim((string)$value));
    if ($v === 'L' || $v === 'LAKI-LAKI' || $v === 'LAKI LAKI') return 'L';
    if ($v === 'P' || $v === 'PEREMPUAN') return 'P';
    return '';
}

$kelasId = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$jurusanId = isset($_GET['jurusan_id']) ? (int)$_GET['jurusan_id'] : 0;
$tahunParam = trim((string)($_GET['tahun'] ?? ($_GET['tahun_ajaran'] ?? '')));
$keyword = trim((string)($_GET['q'] ?? ''));
$keywordLike = '%' . $keyword . '%';

$modeKelas = $kelasId > 0;
$modeJurusan = (!$modeKelas && $jurusanId > 0);
if (!$modeKelas && !$modeJurusan) {
    die('Parameter kelas atau jurusan tidak valid.');
}

$title = 'Daftar Peserta Didik';
$subtitle = '';
$filenameBase = 'daftar_peserta_didik';
$tahunAjaran = $tahunParam;
$kelas = null;
$jurusan = null;

if ($modeKelas) {
    $stmtInfo = $conn->prepare("\n        SELECT k.*, tk.nama_tingkat, j.nama_jurusan, j.kode_jurusan\n        FROM kelas k\n        JOIN tingkat_kelas tk ON tk.id = k.tingkat_id\n        LEFT JOIN jurusan j ON j.id = k.jurusan_id\n        WHERE k.id = ?\n        LIMIT 1\n    ");
    $stmtInfo->bind_param('i', $kelasId);
    $stmtInfo->execute();
    $kelas = $stmtInfo->get_result()->fetch_assoc();
    $stmtInfo->close();
    if (!$kelas) die('Data kelas tidak ditemukan.');

    $tahunAjaran = (string)$kelas['tahun_ajaran'];
    $title = 'Daftar Peserta Didik Kelas ' . $kelas['nama_kelas'];
    $subtitle = 'Jurusan: ' . ($kelas['nama_jurusan'] ?? '-') . ' | Tahun Ajaran: ' . $tahunAjaran;
    $filenameBase = 'daftar_siswa_' . sds_export_clean_filename($kelas['nama_kelas']) . '_' . sds_export_clean_filename($tahunAjaran);

    $sql = "\n        SELECT\n            ps.id, ps.nama_lengkap, ps.nisn, ps.nipd, ps.nik, ps.jenis_kelamin,\n            ps.tempat_lahir, ps.tanggal_lahir, ps.no_kk, ps.nomor_ijazah,\n            ps.nohp_siswa, ps.nohp_ortu,\n            k.nama_kelas, tk.nama_tingkat, j.nama_jurusan, j.kode_jurusan\n        FROM siswa_kelas sk\n        JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id\n        JOIN kelas k ON k.id = sk.kelas_id\n        JOIN tingkat_kelas tk ON tk.id = k.tingkat_id\n        LEFT JOIN jurusan j ON j.id = k.jurusan_id\n        WHERE sk.kelas_id = ?\n          AND sk.tahun_ajaran = ?\n          AND sk.naik_kelas = 1\n          AND ps.status_aktif = 1\n    ";
    $types = 'is';
    $params = [$kelasId, $tahunAjaran];
    if ($keyword !== '') {
        $sql .= "\n          AND (\n            ps.nama_lengkap LIKE ?\n            OR ps.nisn LIKE ?\n            OR ps.nipd LIKE ?\n            OR ps.nik LIKE ?\n            OR ps.jenis_kelamin LIKE ?\n            OR k.nama_kelas LIKE ?\n          )\n        ";
        $types .= 'ssssss';
        array_push($params, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike);
    }
    $sql .= ' ORDER BY ps.nama_lengkap ASC, ps.id ASC';
} else {
    $stmtInfo = $conn->prepare("\n        SELECT id, nama_jurusan, kode_jurusan, tahun_ajaran\n        FROM jurusan\n        WHERE id = ?\n        LIMIT 1\n    ");
    $stmtInfo->bind_param('i', $jurusanId);
    $stmtInfo->execute();
    $jurusan = $stmtInfo->get_result()->fetch_assoc();
    $stmtInfo->close();
    if (!$jurusan) die('Data jurusan tidak ditemukan.');

    $tahunAjaran = $tahunParam !== '' ? $tahunParam : (string)$jurusan['tahun_ajaran'];
    $title = 'Daftar Peserta Didik Jurusan ' . $jurusan['nama_jurusan'];
    $subtitle = 'Kode: ' . ($jurusan['kode_jurusan'] ?? '-') . ' | Tahun Ajaran: ' . $tahunAjaran;
    $filenameBase = 'daftar_siswa_jurusan_' . sds_export_clean_filename($jurusan['nama_jurusan']) . '_' . sds_export_clean_filename($tahunAjaran);

    $sql = "\n        SELECT\n            ps.id, ps.nama_lengkap, ps.nisn, ps.nipd, ps.nik, ps.jenis_kelamin,\n            ps.tempat_lahir, ps.tanggal_lahir, ps.no_kk, ps.nomor_ijazah,\n            ps.nohp_siswa, ps.nohp_ortu,\n            k.nama_kelas, tk.nama_tingkat, j.nama_jurusan, j.kode_jurusan\n        FROM siswa_kelas sk\n        JOIN pendaftaran_siswa ps ON ps.id = sk.siswa_id\n        JOIN kelas k ON k.id = sk.kelas_id\n        JOIN tingkat_kelas tk ON tk.id = k.tingkat_id\n        LEFT JOIN jurusan j ON j.id = k.jurusan_id\n        WHERE k.jurusan_id = ?\n          AND k.tahun_ajaran = ?\n          AND sk.tahun_ajaran = ?\n          AND sk.naik_kelas = 1\n          AND ps.status_aktif = 1\n    ";
    $types = 'iss';
    $params = [$jurusanId, $tahunAjaran, $tahunAjaran];
    if ($keyword !== '') {
        $sql .= "\n          AND (\n            ps.nama_lengkap LIKE ?\n            OR ps.nisn LIKE ?\n            OR ps.nipd LIKE ?\n            OR ps.nik LIKE ?\n            OR ps.jenis_kelamin LIKE ?\n            OR k.nama_kelas LIKE ?\n            OR tk.nama_tingkat LIKE ?\n          )\n        ";
        $types .= 'sssssss';
        array_push($params, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike, $keywordLike);
    }
    $sql .= ' ORDER BY k.nama_kelas ASC, ps.nama_lengkap ASC, ps.id ASC';
}

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Daftar Siswa');

$sheet->mergeCells('A1:O1');
$sheet->mergeCells('A2:O2');
$sheet->setCellValue('A1', $title);
$sheet->setCellValue('A2', $subtitle . ($keyword !== '' ? ' | Filter: ' . $keyword : ''));
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A2')->getFont()->setSize(10);
$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$headers = [
    'No Absen', 'Nama Peserta Didik', 'JK', 'NISN', 'NIS/NIPD', 'NIK', 'No KK', 'Nomor Ijazah',
    'Tempat Lahir', 'Tanggal Lahir', 'Kelas', 'Tingkat', 'Jurusan', 'Kode Jurusan', 'HP Siswa'
];
$sheet->fromArray($headers, null, 'A4');
$sheet->getStyle('A4:O4')->getFont()->setBold(true);
$sheet->getStyle('A4:O4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFEFEF');
$sheet->getStyle('A4:O4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$rowNum = 5;
$no = 1;
while ($row = $res->fetch_assoc()) {
    $sheet->setCellValueByColumnAndRow(1, $rowNum, $no++);
    sds_export_text_cell($sheet, 2, $rowNum, $row['nama_lengkap'] ?? '');
    sds_export_text_cell($sheet, 3, $rowNum, sds_export_jk($row['jenis_kelamin'] ?? ''));
    sds_export_text_cell($sheet, 4, $rowNum, $row['nisn'] ?? '');
    sds_export_text_cell($sheet, 5, $rowNum, $row['nipd'] ?? '');
    sds_export_text_cell($sheet, 6, $rowNum, $row['nik'] ?? '');
    sds_export_text_cell($sheet, 7, $rowNum, $row['no_kk'] ?? '');
    sds_export_text_cell($sheet, 8, $rowNum, $row['nomor_ijazah'] ?? '');
    sds_export_text_cell($sheet, 9, $rowNum, $row['tempat_lahir'] ?? '');
    $tgl = '';
    if (!empty($row['tanggal_lahir']) && $row['tanggal_lahir'] !== '0000-00-00') {
        $tgl = date('d/m/Y', strtotime($row['tanggal_lahir']));
    }
    sds_export_text_cell($sheet, 10, $rowNum, $tgl);
    sds_export_text_cell($sheet, 11, $rowNum, $row['nama_kelas'] ?? '');
    sds_export_text_cell($sheet, 12, $rowNum, $row['nama_tingkat'] ?? '');
    sds_export_text_cell($sheet, 13, $rowNum, $row['nama_jurusan'] ?? '');
    sds_export_text_cell($sheet, 14, $rowNum, $row['kode_jurusan'] ?? '');
    sds_export_text_cell($sheet, 15, $rowNum, $row['nohp_siswa'] ?? '');
    $rowNum++;
}
$stmt->close();

$lastRow = max(4, $rowNum - 1);
$sheet->getStyle('A4:O' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
$sheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('C5:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

foreach (range('A', 'O') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}
$sheet->freezePane('A5');
$sheet->setAutoFilter('A4:O' . $lastRow);

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
$fname = $filenameBase . '_' . date('Ymd_His') . '.xlsx';
header('Content-Disposition: attachment; filename="' . $fname . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
