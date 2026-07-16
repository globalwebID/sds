<?php
// pages/students_import_template.php
// Menyajikan template Excel (.xlsx) yang rapi dan melengkapi referensi dari database SDS.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    exit('Forbidden');
}

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$templatePath = __DIR__ . '/templates/template_import_peserta_didik.xlsx';
if (!is_file($templatePath)) {
    http_response_code(500);
    exit('Template Excel tidak ditemukan. Upload ulang patch secara lengkap.');
}

try {
    $spreadsheet = IOFactory::load($templatePath);
    $sheet = $spreadsheet->getSheetByName('IMPORT_SISWA') ?: $spreadsheet->getActiveSheet();
    $refSheet = $spreadsheet->getSheetByName('REFERENSI');

    if (!$refSheet) {
        $refSheet = $spreadsheet->createSheet();
        $refSheet->setTitle('REFERENSI');
        $refSheet->fromArray(['tahun_ajaran', 'nama_kelas', 'nama_jurusan'], null, 'A1');
    }

    // Bersihkan contoh referensi bawaan, kemudian isi data master aktual dari SDS.
    $refSheet->getStyle('A2:C500')->getNumberFormat()->setFormatCode('@');
    $refSheet->setCellValue('A1', 'tahun_ajaran');
    $refSheet->setCellValue('B1', 'nama_kelas');
    $refSheet->setCellValue('C1', 'nama_jurusan');
    for ($clearRow = 2; $clearRow <= 500; $clearRow++) {
        $refSheet->setCellValue('A' . $clearRow, null);
        $refSheet->setCellValue('B' . $clearRow, null);
        $refSheet->setCellValue('C' . $clearRow, null);
    }

    $tahunRows = [];
    try {
        $q = $conn->query("SELECT tahun_ajaran FROM tahun_ajaran ORDER BY is_active DESC, tahun_ajaran DESC");
    } catch (Throwable $e) {
        $q = $conn->query("SELECT tahun_ajaran FROM tahun_ajaran ORDER BY tahun_ajaran DESC");
    }
    while ($q && ($row = $q->fetch_assoc())) {
        $value = trim((string)($row['tahun_ajaran'] ?? ''));
        if ($value !== '') {
            $tahunRows[$value] = $value;
        }
    }
    if (!$tahunRows && !empty($tahunAjaran)) {
        $tahunRows[(string)$tahunAjaran] = (string)$tahunAjaran;
    }

    $kelasRows = [];
    $q = $conn->query("SELECT DISTINCT nama_kelas FROM kelas WHERE nama_kelas IS NOT NULL AND nama_kelas<>'' ORDER BY nama_kelas ASC");
    while ($q && ($row = $q->fetch_assoc())) {
        $value = trim((string)($row['nama_kelas'] ?? ''));
        if ($value !== '') {
            $kelasRows[$value] = $value;
        }
    }

    $jurusanRows = [];
    $q = $conn->query("SELECT DISTINCT nama_jurusan FROM jurusan WHERE nama_jurusan IS NOT NULL AND nama_jurusan<>'' ORDER BY nama_jurusan ASC");
    while ($q && ($row = $q->fetch_assoc())) {
        $value = trim((string)($row['nama_jurusan'] ?? ''));
        if ($value !== '') {
            $jurusanRows[$value] = $value;
        }
    }

    $maxRows = max(count($tahunRows), count($kelasRows), count($jurusanRows), 1);
    $tahunRows = array_values($tahunRows);
    $kelasRows = array_values($kelasRows);
    $jurusanRows = array_values($jurusanRows);

    for ($i = 0; $i < $maxRows; $i++) {
        $excelRow = $i + 2;
        $refSheet->setCellValueExplicit('A' . $excelRow, (string)($tahunRows[$i] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $refSheet->setCellValueExplicit('B' . $excelRow, (string)($kelasRows[$i] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $refSheet->setCellValueExplicit('C' . $excelRow, (string)($jurusanRows[$i] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    }

    // Dropdown master aktual. Formula tetap dibatasi sampai baris 500 agar ringan.
    $validationMap = [
        'G' => "'REFERENSI'!\$A\$2:\$A\$500", // tahun_ajaran
        'H' => "'REFERENSI'!\$B\$2:\$B\$500", // nama_kelas
        'I' => "'REFERENSI'!\$C\$2:\$C\$500", // nama_jurusan
    ];
    foreach ($validationMap as $column => $formula) {
        for ($row = 7; $row <= 1000; $row++) {
            $validation = $sheet->getCell($column . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);
            $validation->setShowErrorMessage(true);
            $validation->setErrorTitle('Pilihan tidak tersedia');
            $validation->setError('Pilih nilai yang tersedia pada sheet REFERENSI.');
            $validation->setFormula1($formula);
        }
    }

    $sheet->setAutoFilter('A5:BL1000');
    $sheet->freezePane('C6');
    $sheet->setCellValueExplicit('G6', (string)($tahunRows[0] ?? ($tahunAjaran ?? '')), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('H6', (string)($kelasRows[0] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $sheet->setCellValueExplicit('I6', (string)($jurusanRows[0] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);

    $spreadsheet->setActiveSheetIndex($spreadsheet->getIndex($sheet));

    while (ob_get_level() > 0) {
        @ob_end_clean();
    }

    $filename = 'template_import_peserta_didik_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0, no-store, no-cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
} catch (Throwable $e) {
    error_log('[SDS template import peserta didik] ' . $e->getMessage());
    http_response_code(500);
    exit('Template Excel gagal dibuat: ' . $e->getMessage());
}
