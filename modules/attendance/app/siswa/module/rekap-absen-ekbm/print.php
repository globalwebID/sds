<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:./404');
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';
require_once'../../../sw-library/fpdf/fpdf.php';


$filter = empty($_GET['mulai']) || empty($_GET['selesai']) 
? "AND MONTH(absen_ekbm.tanggal) ='$month' AND YEAR(absen_ekbm.tanggal) ='$year'" 
: "AND absen_ekbm.tanggal BETWEEN '" . htmlentities(date('Y-m-d', strtotime($_GET['mulai']))) . "' AND '" . htmlentities(date('Y-m-d', strtotime($_GET['selesai']))). "'";


$query_absen ="SELECT absen_ekbm.*,user.nama_lengkap,user.nisn,user.kelas FROM absen_ekbm 
INNER JOIN user ON absen_ekbm.user_id=user.user_id AND user.user_id='$data_user[user_id]' $filter ORDER BY absen_ekbm.absen_id DESC";
$result_absen = $connection->query($query_absen);
if($result_absen->num_rows > 0){

$data_wali = getWaliKelas($data_user['kelas'], $connection);

  // PDF Setup
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
  $imageWidth = 160;
  $centerX = ($pageWidth - $imageWidth) / 2;
  $pdf->Image($kopPath, $centerX, 10, $imageWidth);
  $pdf->Ln(20);

  $pdf->Line(10, $pdf->GetY(), 285, $pdf->GetY());
  $pdf->Ln(5);
  
  // Header Laporan
  $pdf->SetFont('Arial', 'B', 12);
  $pdf->Cell(0, 7, 'LAPORAN KEHADIRAN EKBM', 0, 1, 'C');
  $pdf->SetFont('Arial', '', 10);
  $pdf->Cell(0, 6, ''.($_GET['mulai']??'-').' s.d '.($_GET['selesai']??'-'), 0, 1, 'C');
  $pdf->Ln(5);
  // Hitung lebar kolom otomatis
  $pdf->SetFont('Arial', 'B', 9);


  $pdf->SetFont('Arial', 'B', 9);
  $pdf->SetFillColor(200, 200, 200); // Warna abu-abu (light grey)
  $header = [
      'No', 'Nama Siswa', 'NISN', 'Pelajaran', 'Pengajar', 'Kelas', 'Keterangan'];

  $widths = [20, 50, 35, 50, 50, 25, 25];

  foreach ($header as $i => $col) {
      $pdf->Cell($widths[$i], 8, $col, 1, 0, 'C', true);
  }
  $pdf->Ln();



// Table Data
$pdf->SetFont('Arial', '', 9);
$no =0;
$data_pelajaran = NULL;
while ($data_absen = $result_absen->fetch_assoc()) {$no++;

    $query_pelajaran="SELECT nama_mapel FROM mata_pelajaran WHERE id='$data_absen[pelajaran]'";
    $result_pelajaran = $connection->query($query_pelajaran);
    $data_pelajaran = $result_pelajaran->fetch_assoc();

    $query_pegawai ="SELECT nama_lengkap FROM pegawai WHERE pegawai_id='$data_absen[pegawai]' AND jabatan='guru' LIMIT 1";
    $result_pegawai = $connection->query($query_pegawai);
    $data_pegawai  = $result_pegawai->fetch_assoc();

    $keterangan = strip_tags($data_absen['keterangan']??'-');
    $pdf->Cell($widths[0], 7, $no, 1, 0, 'C', false);
    $pdf->Cell($widths[1], 7, ($data_absen['nama_lengkap']??'-'), 1, 0, 'L', false);
    $pdf->Cell($widths[2], 7, strip_tags($data_absen['nisn']??'-'), 1, 0, 'L', false);
    $pdf->Cell($widths[3], 7, strip_tags($data_pelajaran['nama_mapel']??'-'), 1, 0, 'L', false);
    $pdf->Cell($widths[4], 7, ($data_pegawai['nama_lengkap']??'-'), 1, 0, 'L', false);
    $pdf->Cell($widths[5], 7, strip_tags($data_absen['kelas']??'-'), 1, 0, 'C', false);
    $pdf->Cell($widths[6], 7, strip_tags($keterangan??'-'), 1, 0, 'C', false);
    $pdf->Ln();
}

    $pdf->SetFont('Arial', '', 10);
    $pdf->Ln(20);
    $pdf->Cell(15); // Margin kiri (offset 20)
    $pdf->Cell(170, 10, "Siswa", 0, 0, 'L');
    $pdf->Cell(90, 5, ''.$row_site['kabupaten'].', '.tgl_indo($date).'', 0, 1, 'L');
    $pdf->Cell(185, 5, '', 0, 0, 'L');
    $pdf->Cell(90, 10, 'Wali Kelas', 0, 1, 'L');
    $pdf->Ln(15);

    $pdf->Cell(15); // Margin kiri (offset 20)
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Cell(170, 10, strip_tags($data_user['nama_lengkap']??''), 0, 0, 'L');
    $pdf->Cell(90, 5,  strip_tags($data_wali['nama_lengkap']??'-'), 0, 1, 'L');
    $pdf->Cell(185, 5, '', 0, 0, 'L');
    $pdf->Cell(90, 5, 'NIP. '.($data_wali['nip']??'-'), 0, 1, 'L');

    $pdf->Output('I', 'Laporan_Absen_'.$data_user['nama_lengkap'].'_'.($_GET['mulai']??'-').'_'.($_GET['selesai']??'-').'.pdf');


}else{
  echo'<title>Data Kehadiran EKBM</title>
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
        <h1>Data Kehadiran Tidak Ditemukan</h1>
        <p>Mohon periksa kembali data yang Anda masukkan atau coba lagi nanti.</p>
        <button onclick="window.close();">Tutup</button>
    </div>';
  }
}
?>