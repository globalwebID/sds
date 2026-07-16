<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
  echo'<title>Access Denied</title>';
  exit;
}else{
require_once'../../../sw-library/sw-config.php'; 
require_once'../../../sw-library/sw-function.php';
require_once'../../../sw-library/fpdf/fpdf.php';
require_once'../../oauth/user.php';
$totalBobot = 0;

$filterParts = [];
$bulan      = isset($_GET['bulan']) ? strip_tags($_GET['bulan']) : $month;
$filterParts[] = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$year'";

if (!empty($_GET['siswa'])) {
    $siswa = convert("decrypt", $_GET['siswa']??'0');
    $filterParts[] = "pelanggaran.user_id='$siswa'";
}

$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query_pelanggaran ="SELECT pelanggaran.*,user.nama_lengkap,user.kelas FROM pelanggaran
LEFT JOIN user ON user.user_id= pelanggaran.user_id $filter AND pelanggaran.kelas='{$data_user['wali_kelas']}' ORDER BY pelanggaran.pelanggaran_id DESC LIMIT 10";
$result_pelanggaran = $connection->query($query_pelanggaran);


class PDFWithFooter extends FPDF {
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . ' dari {nb} | Dicetak pada ' . date('d-m-Y H:i'), 0, 0, 'C');
    }
}

/** Buat Footer Dan halaman */
$pdf = new PDFWithFooter();
$pdf->AliasNbPages(); 
$pdf->AddPage('L', 'A4');


if(file_exists('../../../sw-content/'.($site_kop??'-logo.png').'')){
  $kopPath = '../../../sw-content/'.strip_tags($site_kop).'';
}else{
  $kopPath = '../../../sw-content/'.strip_tags($site_logo).'';
}


$pageWidth = $pdf->GetPageWidth();
$imageWidth = 190;
$centerX = ($pageWidth - $imageWidth) / 2;
$pdf->Image($kopPath, $centerX, 10, $imageWidth);
$pdf->Ln(28);

$pdf->Line(10, $pdf->GetY(), 285, $pdf->GetY());
$pdf->Ln(5);

$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 3, 'Laporan Pelanggaran Bulan '.ambilbulan($_GET['bulan']??'').'-'.$year.'', 0, 1, 'L');
$pdf->Ln(5);


// Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Nama', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'Kelas', 1, 0, 'C', true);
$pdf->Cell(70, 8, 'Bentuk Pelanggaran', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'Bobot', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Diinput Oleh', 1, 0, 'C', true);
$pdf->Cell(22, 8, 'Tanggal', 1, 1, 'C', true);

// Table Data
$pdf->SetFont('Arial', '', 8);
$no = 1;
if($result_pelanggaran->num_rows > 0){
  while ($data= $result_pelanggaran->fetch_assoc()){

    $uqery_pegawai ="SELECT nama_lengkap FROM pegawai WHERE pegawai_id='{$data['pegawai_id']}'";
    $result_pegawai = $connection->query($uqery_pegawai); 
    $data_pegawai = $result_pegawai->fetch_assoc();

    $totalBobot += (int)$data['bobot'];

    $pdf->Cell(10, 8, $no++, 1, 0, 'C');
    $pdf->Cell(60, 8, strip_tags($data['nama_lengkap']??'-'), 1);
    $pdf->Cell(20, 8, strip_tags($data['kelas']??'-'), 1);
    $pdf->Cell(70, 8, strip_tags($data['bentuk_pelanggaran']??'-'), 1);
    $pdf->Cell(20, 8, strip_tags($data['bobot']??'-'), 1, 0, 'C', false);
    $pdf->Cell(60, 8, strip_tags($data_pegawai['nama_lengkap']??'-'), 1);
    $pdf->Cell(22, 8, tanggal_ind($data['tanggal']??'-'), 1);
    $pdf->Ln();
}
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(160, 8, 'TOTAL BOBOT', 1, 0, 'R'); // gabungan kolom sebelum bobot
    $pdf->Cell(20, 8, $totalBobot, 1, 0, 'C');    // kolom bobot
    $pdf->Cell(82, 8, '', 1, 1);                 // kolom setelah bobot (60 + 22)
    
    $pdf->SetFont('Arial', '', 10);
    $pdf->Ln(20);

    // Tanggal dan tempat di kanan
    $pdf->Cell(170, 6, '', 0, 0); // Kosongkan kiri
    $pdf->Cell(20); // Margin kiri (offset 20)
    $pdf->Cell(90, 6, $row_site['kabupaten'] . ', ' . tgl_indo($date), 0, 1, 'L');

    $pdf->Cell(20); // Margin kiri (offset 20)
    // Hormat Saya di kiri, Kepala Sekolah di kanan
    $pdf->Cell(20, 6, 'Hormat Saya,', 0, 0, 'L');
    $pdf->Cell(150, 6, '', 0, 0); // Spacer
    $pdf->Cell(90, 6, 'Kepala Sekolah', 0, 1, 'L');

    // Tambahkan stempel di atas nama kepala sekolah
   $stempelPath = '../../../sw-content/'.($row_site['stempel']??'stempel.png').'';
    if (file_exists($stempelPath)) {
        // Ambil ukuran gambar
        list($width, $height) = getimagesize($stempelPath);

        $desiredWidth = 45; // mm
        $desiredHeight = ($height / $width) * $desiredWidth;

        $xStempel = 20 + 20 + 95 + 60;
        $yStempel = $pdf->GetY() - 5; 
        
        // Gambar dengan ukuran yang dihitung
        $pdf->Image($stempelPath, $xStempel, $yStempel, $desiredWidth, $desiredHeight);
    }

    $pdf->Ln(25);

    // Nama pegawai di kiri, nama kepala sekolah di kanan
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(20); // Margin kiri (offset 20)
    $pdf->Cell(20, 6, strip_tags($data_user['nama_lengkap'] ?? ''), 0, 0, 'L');
    $pdf->Cell(150, 6, '', 0, 0); // Spacer
    $pdf->Cell(90, 6, strip_tags($row_site['kepala_sekolah'] ?? '-'), 0, 1, 'L');
    
    // NIP pegawai di kiri, NIP kepala sekolah di kanan
    $pdf->SetFont('Arial', '', 10);
    $pdf->Cell(20); // Margin kiri (offset 20)
    $pdf->Cell(20, 6, 'NIP. ' . ($data_user['nip'] ?? '-'), 0, 0, 'L');
    $pdf->Cell(150, 6, '', 0, 0); // Spacer
    $pdf->Cell(90, 6, 'NIP. ' . ($row_site['nip_kepala_sekolah'] ?? '-'), 0, 1, 'L');

    $pdf->Output('I', 'Laporan_Pelanggaran_'.$date.'.pdf');

}else{
  echo'<title>Data Pelanggaran</title>
  <style>
       body {
            font-family: Arial, Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fc;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        /* Box Style */
        .box {
            background-color: #ffffff;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 400px;
            text-align: center;
        }

        /* Heading Text Style */
        .box h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }

        /* Message Text Style */
        .box p {
            font-size: 16px;
            color: #555;
            margin-bottom: 30px;
        }

        /* Button Style */
        .box button {
            background-color: #ff6f61;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .box button:hover {
            background-color: #ff4a3b;
        }

        /* Animasi Fade-in */
        .box {
            opacity: 0;
            animation: fadeIn 1s forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>
     <div class="box">
        <h1>Access Denied</h1>
        <p>Mohon periksa kembali data yang Anda masukkan atau coba lagi nanti.</p>
        <button onclick="window.close();">Tutup</button>
    </div>';
}}

?>