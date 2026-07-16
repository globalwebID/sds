<?php
// students_export.php – export data siswa mengikuti filter halaman students
session_start();
require '../db.php';
require '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

if (!isset($_SESSION['admin_id'])) {
    header('Location: login');
    exit;
}

$filterTahun = $_GET['tahun'] ?? '';
$filterTingkat = $_GET['tingkat'] ?? '';
$filterKelas = $_GET['kelas'] ?? '';
$filterAsalSekolah = trim($_GET['asal_sekolah'] ?? '');
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$statusFilter = in_array($statusFilter, ['aktif', 'nonaktif'], true) ? $statusFilter : '';
$statusValue = $statusFilter === 'aktif' ? 1 : ($statusFilter === 'nonaktif' ? 0 : null);

$tahunActive = $filterTahun !== '';
$tingkatActive = $filterTingkat !== '' && ctype_digit((string)$filterTingkat);
$kelasActive = $filterKelas !== '';
$asalSekolahActive = $filterAsalSekolah !== '';
$searchActive = $search !== '';
$statusActive = $statusFilter !== '';

$filterConditions = [];
$params = [];
$types = '';

if ($tahunActive) {
    $filterConditions[] = 'k.tahun_ajaran = ?';
    $params[] = $filterTahun;
    $types .= 's';
}
if ($tingkatActive) {
    $filterConditions[] = 'k.tingkat_id = ?';
    $params[] = (int)$filterTingkat;
    $types .= 'i';
}
if ($kelasActive) {
    $filterConditions[] = 'k.nama_kelas = ?';
    $params[] = $filterKelas;
    $types .= 's';
}
if ($asalSekolahActive) {
    $filterConditions[] = 'p.sekolah_asal = ?';
    $params[] = $filterAsalSekolah;
    $types .= 's';
}
if ($searchActive) {
    $filterConditions[] = '(p.nama_lengkap LIKE ? OR p.nisn LIKE ? OR p.nipd LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= 'sss';
}
if ($statusActive) {
    $filterConditions[] = 'p.status_aktif = ?';
    $params[] = $statusValue;
    $types .= 'i';
}

$where = count($filterConditions) ? ' AND ' . implode(' AND ', $filterConditions) : '';

$sql = "
    SELECT
        p.*,
        k.nama_kelas,
        k.tahun_ajaran AS tahun_ajaran_kelas,
        tk.nama_tingkat
    FROM pendaftaran_siswa p
    JOIN siswa_kelas sk ON sk.siswa_id = p.id
    JOIN kelas k ON k.id = sk.kelas_id
    JOIN tingkat_kelas tk ON tk.id = k.tingkat_id
    WHERE sk.tahun_ajaran = (
        SELECT MAX(sk2.tahun_ajaran)
        FROM siswa_kelas sk2
        WHERE sk2.siswa_id = p.id
    )
    $where
    ORDER BY p.tanggal_input DESC, p.nama_lengkap ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die('Query export gagal: ' . $conn->error);
}
if (count($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$res = $stmt->get_result();

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? '';
$scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? ''); // /sds/siteman
$appBase = rtrim(dirname($scriptDir), '/');          // /sds
$uploadBaseUrl = $host ? $scheme . '://' . $host . $appBase . '/uploads/' : '../uploads/';

$cols = [
    'No.', 'Tahun Ajaran', 'Tingkat', 'Kelas', 'Nama Lengkap', 'Email', 'NISN', 'NIPD', 'Status Aktif', 'Alasan Nonaktif',
    'Sekolah Asal', 'Nomor Ijazah', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'No KK', 'NIK', 'No Reg Akta',
    'Kebutuhan Khusus', 'Agama', 'Alamat', 'Desa', 'Kecamatan', 'Kota', 'Latitude', 'Longitude', 'Tempat Tinggal', 'Moda Transportasi',
    'Anak Ke-', 'Jumlah Saudara', 'Tinggi Badan', 'Berat Badan', 'Hobi', 'Cita-cita', 'Nomor KIP',
    'Nama Ayah', 'NIK Ayah', 'Thn Lahir Ayah', 'Pendidikan Ayah', 'Pekerjaan Ayah', 'Penghasilan Ayah',
    'Nama Ibu', 'NIK Ibu', 'Thn Lahir Ibu', 'Pendidikan Ibu', 'Pekerjaan Ibu', 'Penghasilan Ibu',
    'Nama Wali', 'NIK Wali', 'Thn Lahir Wali', 'Pendidikan Wali', 'Pekerjaan Wali', 'Penghasilan Wali',
    'HP Ortu', 'HP Siswa', 'File KIP', 'File KK', 'File Ijazah', 'Tanggal Input'
];

$ss = new Spreadsheet();
$sheet = $ss->getActiveSheet();
$sheet->setTitle('Data Siswa');
$sheet->fromArray($cols, null, 'A1');

$lastCol = Coordinate::stringFromColumnIndex(count($cols));
$sheet->getStyle('A1:' . $lastCol . '1')->getFont()->setBold(true);
$sheet->getStyle('A1:' . $lastCol . '1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getStyle('A1:' . $lastCol . '1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE9ECEF');
$sheet->freezePane('A2');
$sheet->setAutoFilter('A1:' . $lastCol . '1');

// Kolom identitas harus diperlakukan sebagai TEKS agar Excel tidak mengubah
// NIK/No KK/No Ijazah menjadi format scientific notation seperti 3,57403E+15.
$textColumns = [
    'NISN',
    'NIPD',
    'Nomor Ijazah',
    'No KK',
    'NIK',
    'No Reg Akta',
    'Nomor KIP',
    'NIK Ayah',
    'NIK Ibu',
    'NIK Wali',
    'HP Ortu',
    'HP Siswa',
];
$textColIndexes = [];
foreach ($textColumns as $textColumnName) {
    $idx = array_search($textColumnName, $cols, true);
    if ($idx !== false) {
        $textColIndexes[$textColumnName] = $idx + 1;
        $letter = Coordinate::stringFromColumnIndex($idx + 1);
        $sheet->getStyle($letter)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
    }
}

$r = 2;
$no = 1;
while ($d = $res->fetch_assoc()) {
    $fileKip    = !empty($d['file_kip'])    ? $uploadBaseUrl . $d['file_kip']    : '';
    $fileKk     = !empty($d['file_kk'])     ? $uploadBaseUrl . $d['file_kk']     : '';
    $fileIjazah = !empty($d['file_ijazah']) ? $uploadBaseUrl . $d['file_ijazah'] : '';

    $tanggalLahir = !empty($d['tanggal_lahir']) && $d['tanggal_lahir'] !== '0000-00-00'
        ? date('d/m/Y', strtotime($d['tanggal_lahir']))
        : '';
    $tanggalInput = !empty($d['tanggal_input'])
        ? date('d/m/Y H:i', strtotime($d['tanggal_input']))
        : '';

    $row = [
        $no++,
        $d['tahun_ajaran_kelas'] ?: ($d['tahun_ajaran'] ?? ''),
        $d['nama_tingkat'] ?? '',
        $d['nama_kelas'] ?? '',
        $d['nama_lengkap'] ?? '',
        $d['email'] ?? '',
        $d['nisn'] ?? '',
        $d['nipd'] ?? '',
        !empty($d['status_aktif']) ? 'Aktif' : 'Non Aktif',
        $d['alasan_nonaktif'] ?? '',
        $d['sekolah_asal'] ?? '',
        $d['nomor_ijazah'] ?? '',
        $d['jenis_kelamin'] ?? '',
        $d['tempat_lahir'] ?? '',
        $tanggalLahir,
        $d['no_kk'] ?? '',
        $d['nik'] ?? '',
        $d['no_registrasi_akta'] ?? '',
        $d['kebutuhan_khusus'] ?? '',
        $d['agama'] ?? '',
        $d['alamat'] ?? '',
        $d['desa'] ?? '',
        $d['kecamatan'] ?? '',
        $d['kota'] ?? '',
        $d['latitude'] ?? '',
        $d['longitude'] ?? '',
        $d['tempat_tinggal'] ?? '',
        $d['moda_transportasi'] ?? '',
        $d['anak_ke'] ?? '',
        $d['jumlah_saudara_kandung'] ?? '',
        $d['tinggi_badan'] ?? '',
        $d['berat_badan'] ?? '',
        $d['hobi'] ?? '',
        $d['cita_cita'] ?? '',
        $d['nomor_kip'] ?? '',
        $d['nama_ayah'] ?? '',
        $d['nik_ayah'] ?? '',
        $d['tahun_lahir_ayah'] ?? '',
        $d['pendidikan_ayah'] ?? '',
        $d['pekerjaan_ayah'] ?? '',
        $d['penghasilan_ayah'] ?? '',
        $d['nama_ibu'] ?? '',
        $d['nik_ibu'] ?? '',
        $d['tahun_lahir_ibu'] ?? '',
        $d['pendidikan_ibu'] ?? '',
        $d['pekerjaan_ibu'] ?? '',
        $d['penghasilan_ibu'] ?? '',
        $d['nama_wali'] ?? '',
        $d['nik_wali'] ?? '',
        $d['tahun_lahir_wali'] ?? '',
        $d['pendidikan_wali'] ?? '',
        $d['pekerjaan_wali'] ?? '',
        $d['penghasilan_wali'] ?? '',
        $d['nohp_ortu'] ?? '',
        $d['nohp_siswa'] ?? '',
        $fileKip,
        $fileKk,
        $fileIjazah,
        $tanggalInput,
    ];

    $sheet->fromArray($row, null, 'A' . $r);

    // Jangan biarkan data pengguna yang diawali =, +, - atau @ ditafsirkan
    // Excel sebagai formula. Nilai string selalu ditulis sebagai teks eksplisit.
    foreach ($row as $columnOffset => $value) {
        if (is_string($value)) {
            $cellRef = Coordinate::stringFromColumnIndex($columnOffset + 1) . $r;
            $sheet->setCellValueExplicit($cellRef, $value, DataType::TYPE_STRING);
        }
    }

    // Paksa ulang kolom identitas sebagai string setelah fromArray(),
    // karena fromArray dapat menebak string digit panjang sebagai angka.
    foreach ($textColIndexes as $textColumnName => $colIndex) {
        $value = $row[$colIndex - 1] ?? '';
        $cellRef = Coordinate::stringFromColumnIndex($colIndex) . $r;
        $sheet->setCellValueExplicit($cellRef, (string)$value, DataType::TYPE_STRING);
    }

    foreach (['File KIP' => $fileKip, 'File KK' => $fileKk, 'File Ijazah' => $fileIjazah] as $colName => $url) {
        if ($url) {
            $colIndex = array_search($colName, $cols, true) + 1;
            $cell = $sheet->getCellByColumnAndRow($colIndex, $r);
            $cell->getHyperlink()->setUrl($url);
            $cell->getStyle()->getFont()->setUnderline(Font::UNDERLINE_SINGLE)
                ->setColor(new Color(Color::COLOR_BLUE));
        }
    }

    $r++;
}

for ($i = 1; $i <= count($cols); $i++) {
    $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($i))->setAutoSize(true);
}

$filterLabel = [];
if ($tahunActive) $filterLabel[] = preg_replace('/[^0-9A-Za-z_-]+/', '-', $filterTahun);
if ($tingkatActive) $filterLabel[] = 'tingkat-' . (int)$filterTingkat;
if ($kelasActive) $filterLabel[] = preg_replace('/[^0-9A-Za-z_-]+/', '-', $filterKelas);
if ($asalSekolahActive) $filterLabel[] = 'asal-' . preg_replace('/[^0-9A-Za-z_-]+/', '-', $filterAsalSekolah);
if ($statusActive) $filterLabel[] = $statusFilter;
$fname = 'data_siswa' . (count($filterLabel) ? '_' . implode('_', $filterLabel) : '') . '_' . date('Ymd_His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"{$fname}\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($ss);
$writer->save('php://output');
exit;
