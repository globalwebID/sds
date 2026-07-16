<?php
declare(strict_types=1);

ob_start();
require_once dirname(__DIR__) . '/config/runtime.php';
sds_session_start();
require dirname(__DIR__) . '/db.php';
require_once __DIR__ . '/fungsi.php';
require dirname(__DIR__) . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isset($_SESSION['admin_id'])) {
    header('Location: login');
    exit;
}

$classId = filter_input(INPUT_GET, 'kelas_id', FILTER_VALIDATE_INT);
if (!$classId || $classId < 1) {
    http_response_code(400);
    exit('Kelas tidak valid.');
}

$stmt = $conn->prepare('SELECT k.*,tk.nama_tingkat FROM kelas k JOIN tingkat_kelas tk ON tk.id=k.tingkat_id WHERE k.id=? LIMIT 1');
$stmt->bind_param('i', $classId);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$class) {
    http_response_code(404);
    exit('Data kelas tidak ditemukan.');
}

$activeYear = (string)($tahunAjaran ?? '');
$stmtStudents = $conn->prepare("SELECT ps.nipd,ps.nisn,ps.nama_lengkap,ps.jenis_kelamin
    FROM siswa_kelas sk
    JOIN pendaftaran_siswa ps ON ps.id=sk.siswa_id
    WHERE sk.kelas_id=? AND BINARY sk.tahun_ajaran=BINARY ? AND ps.status_aktif=1
    ORDER BY ps.nama_lengkap");
$stmtStudents->bind_param('is', $classId, $activeYear);
$stmtStudents->execute();
$students = $stmtStudents->get_result();

$settings = ['nama_sekolah' => 'Sekolah', 'kop_surat' => ''];
$result = $conn->query('SELECT nama_sekolah,kop_surat FROM pengaturan LIMIT 1');
if ($result && ($row = $result->fetch_assoc())) $settings = array_merge($settings, $row);

$e = static fn(mixed $value): string => htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
$letterhead = '';
$letterheadFile = basename((string)($settings['kop_surat'] ?? ''));
$letterheadPath = $letterheadFile !== '' ? dirname(__DIR__) . '/uploads/logo/' . $letterheadFile : '';
if ($letterheadPath !== '' && is_file($letterheadPath)) {
    $mime = function_exists('mime_content_type') ? (string)mime_content_type($letterheadPath) : 'image/jpeg';
    if (str_starts_with($mime, 'image/')) {
        $letterhead = '<img src="data:' . $e($mime) . ';base64,' . base64_encode((string)file_get_contents($letterheadPath)) . '" style="width:100%">';
    }
}

$html = '<style>
body{font-family:Arial,sans-serif;font-size:12px}table{border-collapse:collapse;width:100%}
th{border:1px solid #000;padding:6px;text-align:center;background:#eee}td{border:1px solid #000;padding:2px 5px;text-align:left}
</style>' . $letterhead .
'<h3 style="text-align:center">DAFTAR NAMA PESERTA DIDIK<br>' . $e($settings['nama_sekolah']) . '<br>TAHUN PELAJARAN: ' . $e($activeYear) . '</h3>
<table style="border:none;margin-bottom:10px"><tr><td style="width:130px;border:none">KELAS</td><td style="border:none">: ' . $e($class['nama_kelas']) . '</td></tr>
<tr><td style="border:none">WALI KELAS</td><td style="border:none">: ' . $e($class['wali_kelas'] ?? '-') . '</td></tr></table>
<table><thead><tr><th style="width:5px">NO.</th><th style="width:10px">NISN</th><th style="width:10px">NIS</th><th style="width:250px">NAMA PESERTA DIDIK</th><th style="width:10px">L/P</th><th colspan="7">KETERANGAN</th></tr></thead><tbody>';

$number = 1;
while ($student = $students->fetch_assoc()) {
    $gender = (string)$student['jenis_kelamin'] === 'Laki-laki' ? 'L' : 'P';
    $html .= '<tr><td style="text-align:center">' . $number++ . '</td><td>' . $e($student['nisn']) . '</td><td>' . $e($student['nipd']) . '</td><td>' . $e($student['nama_lengkap']) . '</td><td style="text-align:center">' . $gender . '</td>' . str_repeat('<td></td>', 7) . '</tr>';
}
$stmtStudents->close();

$html .= '</tbody></table><br><br><div style="float:right">Guru Mata Pelajaran _______________<br><br><br><br><br>________________________________<br>NIP.</div>';

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$pdf = new Dompdf($options);
$pdf->loadHtml($html, 'UTF-8');
$pdf->setPaper('A4', 'portrait');
$pdf->render();

while (ob_get_level() > 0) ob_end_clean();
$safeClass = preg_replace('/[^A-Za-z0-9_-]+/', '_', (string)$class['nama_kelas']) ?: 'Kelas';
$pdf->stream('Absensi_' . $safeClass . '.pdf', ['Attachment' => false]);
