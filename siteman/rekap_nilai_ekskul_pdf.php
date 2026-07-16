<?php
// student_pdf.php
session_start();
require '../db.php';
require '../vendor/autoload.php';        // ← autoload Dompdf
use Dompdf\Dompdf;

// Tangkap parameter GET
$siswa_id = isset($_GET['siswa']) ? (int)$_GET['siswa'] : 0;
$ekskul_id = isset($_GET['ekskul']) ? (int)$_GET['ekskul'] : 0;

if ($siswa_id <= 0) {
    die('Parameter siswa tidak valid!');
}

// Ambil data siswa
$stmt = $conn->prepare("SELECT * FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $siswa_id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) {
    die('Data siswa tidak ditemukan!');
}

$nilaiList = [];
$groupedNilai = [];
$nama_ekskul_filter = null;

if ($ekskul_id > 0) {
    // Ambil data ekskul
    $stmt = $conn->prepare("SELECT * FROM ekstrakurikuler WHERE id = ?");
    $stmt->bind_param("i", $ekskul_id);
    $stmt->execute();
    $ekskul = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ekskul) {
        die('Data ekstrakurikuler tidak ditemukan!');
    }

    $nama_ekskul_filter = $ekskul['nama_ekskul'];

    // Ambil nilai berdasarkan ekskul tertentu
    $stmt = $conn->prepare("
        SELECT ne.nilai, ne.keterangan, ne.semester, ne.tanggal, ne.created_at,
               e.nama_ekskul, e.tahun_ajaran
        FROM nilai_ekskul ne
        JOIN ekstrakurikuler e ON ne.ekskul_id = e.id
        WHERE ne.siswa_id = ? AND ne.ekskul_id = ?
        ORDER BY e.tahun_ajaran DESC, ne.semester ASC, ne.tanggal DESC
    ");
    $stmt->bind_param("ii", $siswa_id, $ekskul_id);
} else {
    // Ambil semua nilai ekskul
    $stmt = $conn->prepare("
        SELECT ne.nilai, ne.keterangan, ne.semester, ne.tanggal, ne.created_at,
               e.id AS ekskul_id, e.nama_ekskul, e.tahun_ajaran
        FROM nilai_ekskul ne
        JOIN ekstrakurikuler e ON ne.ekskul_id = e.id
        WHERE ne.siswa_id = ?
        ORDER BY e.nama_ekskul ASC, e.tahun_ajaran DESC, ne.semester ASC, ne.tanggal DESC
    ");
    $stmt->bind_param("i", $siswa_id);
}

$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    $nilaiList = $result->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Jika tanpa filter, kelompokkan berdasarkan ekskul
if ($ekskul_id === 0) {
    foreach ($nilaiList as $row) {
        $groupedNilai[$row['ekskul_id']]['nama'] = $row['nama_ekskul'];
        $groupedNilai[$row['ekskul_id']]['data'][] = $row;
    }
}

// Ambil konten HTML sebagai string
ob_start();
include 'rekap_nilai_ekskul_template.php'; // file ini hanya berisi tampilan HTML
$html = ob_get_clean();

// Jika URL mengandung ?preview=1 maka tampilkan HTML-nya untuk dicek di browser
if (isset($_GET['preview'])) {
    echo $html;
    exit;
}

// Inisialisasi Dompdf
$dompdf = new Dompdf();
$dompdf->loadHtml($html);

// Atur ukuran kertas dan orientasi (opsional)
$dompdf->setPaper('A4', 'portrait');

// Render HTML ke PDF
$dompdf->render();

// Output ke browser
$dompdf->stream('rekap_nilai_ekskul.pdf', ['Attachment' => false]);
exit;
