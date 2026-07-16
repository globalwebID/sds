<?php 
require_once'../../../sw-library/sw-config.php'; 
require_once'../../../sw-library/sw-function.php';
require_once'../../../sw-library/fpdf/fpdf.php';
require_once'../../oauth/user.php';
    
$filter = empty($_GET['mulai']) || empty($_GET['selesai']) 
    ? "AND MONTH(tanggal) ='$month' AND YEAR(tanggal) ='$year'" 
    : "AND tanggal BETWEEN '" . htmlentities(date('Y-m-d', strtotime($_GET['mulai']))) . "' AND '" . htmlentities(date('Y-m-d', strtotime($_GET['selesai']))). "'";

$query_izin ="SELECT * FROM izin WHERE user_id='".htmlentities($data_user['user_id'])."' $filter";
$result_izin = $connection->query($query_izin);
if($result_izin->num_rows > 0){

$data_wali =NULL;
$data_wali = getWaliKelas($data_user['kelas'], $connection);

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
$pdf->Ln(25);

$pdf->Line(10, $pdf->GetY(), 285, $pdf->GetY());
$pdf->Ln(5);

// Header Laporan
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 7, 'LAPORAN IZIN '.strtoupper($data_user['nama_lengkap']??'-').'', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, ''.($_GET['mulai']??'-').' s.d '.($_GET['selesai']??'-'), 0, 1, 'C');
$pdf->Ln(5);
// Hitung lebar kolom otomatis
$pdf->SetFont('Arial', 'B', 9);


// Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Nama', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Tanggal', 1, 0, 'C', true);
$pdf->Cell(20, 8, 'Alasan', 1, 0, 'C', true);
$pdf->Cell(145, 8, 'Keterangan', 1, 0, 'C', true);
$pdf->Cell(21, 8, 'Status', 1, 1, 'C', true);

// Table Data
$pdf->SetFont('Arial', '', 8);
$no = 1;
while ($data = $result_izin->fetch_assoc()) {

    if($data['status'] == 'PENDING' OR $data['status'] == '-'){
        $status = 'Pending';
    }elseif($data['status'] == 'Y'){
        $status = 'Disetujui';
    }else{
        $status = 'Ditolak';
    }

    $pdf->Cell(10, 8, $no++, 1, 0, 'C');
    $pdf->Cell(60, 8, strip_tags($data_user['nama_lengkap']??'-'), 1);
    $pdf->Cell(40, 8, ''.tanggal_ind($data['tanggal']). ' s.d '.tanggal_ind($data['tanggal_selesai']), 1, 0, 'C');
    $pdf->Cell(20, 8, strip_tags($data['alasan']??'-'), 1);
    $pdf->Cell(125, 8, strip_tags($data['keterangan']??'-'), 1);
    $pdf->Cell(21, 8, $status, 1, 0, 'C');
    $pdf->Ln();
}

    $pdf->SetFont('Arial', '', 10);
    $pdf->Ln(20);
    $pdf->Cell(20); // Margin kiri (offset 20)
    $pdf->Cell(170, 10, "Siswa", 0, 0, 'L');
    $pdf->Cell(90, 5, ''.$row_site['kabupaten'].', '.tgl_indo($date).'', 0, 1, 'L');
    $pdf->Cell(190, 5, '', 0, 0, 'L');
    $pdf->Cell(90, 10, 'Wali Kelas', 0, 1, 'L');
    $pdf->Ln(15);
    $pdf->Cell(20); // Margin kiri (offset 20)
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(170, 10, strip_tags($data_user['nama_lengkap']??''), 0, 0, 'L');
    $pdf->Cell(90, 5,  strip_tags($data_wali['nama_lengkap']??'-'), 0, 1, 'L');
    $pdf->Cell(190, 5, '', 0, 0, 'L');
    $pdf->Cell(90, 5, 'NIP. '.($data_wali['nip']??'-'), 0, 1, 'L');

    $pdf->Output('I', 'Laporan_Izin_'.$date.'.pdf');

}else{
  echo'<title>Data Izin</title>
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
        <h1>Data Izin Tidak Ditemukan</h1>
        <p>Mohon periksa kembali data yang Anda masukkan atau coba lagi nanti.</p>
        <button onclick="window.close();">Tutup</button>
    </div>';
}

?>