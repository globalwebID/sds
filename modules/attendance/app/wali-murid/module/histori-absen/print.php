<?php if(empty($connection) AND !isset($_COOKIE['wali_murid'])){
    header('location:./404');
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';
require_once'../../../sw-library/fpdf/fpdf.php';

$data_siswa     = NULL;
$query_siswa    = "SELECT kelas FROM user WHERE nisn='$data_user[nisn]'";
$result_siswa   = $connection->query($query_siswa);
$data_siswa     = $result_siswa->fetch_assoc();


$filter = empty($_GET['mulai']) || empty($_GET['selesai']) 
? "AND MONTH(absen.tanggal) ='$month' AND YEAR(absen.tanggal) ='$year'" 
: "AND absen.tanggal BETWEEN '" . htmlentities(date('Y-m-d', strtotime($_GET['mulai']))) . "' AND '" . htmlentities(date('Y-m-d', strtotime($_GET['selesai']))). "'";

$query_absen ="SELECT absen.*, user.nisn,user.nama_lengkap,user.kelas 
    FROM absen 
    INNER JOIN user ON absen.user_id = user.user_id 
    WHERE user.nisn = '$data_user[nisn]' $filter";
$result_absen = $connection->query($query_absen);
if($result_absen->num_rows > 0){

$data_wali = getWaliKelas($data_siswa['kelas'], $connection);

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
  $imageWidth = 190;
  $centerX = ($pageWidth - $imageWidth) / 2;
  $pdf->Image($kopPath, $centerX, 10, $imageWidth);
  $pdf->Ln(25);

  $pdf->Line(10, $pdf->GetY(), 285, $pdf->GetY());
  $pdf->Ln(5);
  // Header Laporan
  $pdf->SetFont('Arial', 'B', 12);
  $pdf->Cell(0, 7, 'LAPORAN KEHADIRAN '.strip_tags(strtoupper($data_user['nama_lengkap'])??'-').'', 0, 1, 'C');
  $pdf->SetFont('Arial', '', 10);
  $pdf->Cell(0, 6, 'Periode: '.htmlentities($_GET['mulai']??'').' s.d '.htmlentities($_GET['selesai']??'').'', 0, 1, 'C');
  $pdf->Ln(5);
  // Hitung lebar kolom otomatis
  $pdf->SetFont('Arial', 'B', 9);


  $pdf->SetFont('Arial', 'B', 9);
  $pdf->SetFillColor(200, 200, 200); // Warna abu-abu (light grey)
  $header = [
      'No', 'Tanggal', 'Jam Kerja', 'Toleransi',
      'Absen Masuk', 'Absen Pulang', 'Kehadiran', 'Lokasi IN', 'Lokasi OUT' ,'Keterangan'];

  $widths = [10, 20, 30, 20, 36, 36, 30, 20, 20, 55];

  foreach ($header as $i => $col) {
      $pdf->Cell($widths[$i], 8, $col, 1, 0, 'C', true);
  }
  $pdf->Ln();



// Table Data
$pdf->SetFont('Arial', '', 8);
$no =0;
while ($data_absen = $result_absen->fetch_assoc()) {$no++;

   // Status Masuk
  $status_masuk = ($data_absen['status_masuk'] === 'Tepat Waktu') 
  ? ''.$data_absen['status_masuk'].'' 
  : (($data_absen['status_masuk'] === 'Telat') 
      ? ''.$data_absen['status_masuk'].'' 
      : ($data_absen['status_masuk'] !== '' 
          ? ''.$data_absen['status_masuk'].'' 
          : 'Belum Absen'));  // Default jika kosong

  // Status Pulang
  $status_pulang = $data_absen['absen_out'] === '00:00:00' 
      ? '' 
      : ($data_absen['status_pulang'] === 'Tepat Waktu' 
          ? ''.$data_absen['status_pulang'].'' 
          : ''.$data_absen['status_pulang'].'');

  // Status Kehadiran
  $kehadiran =($data_absen['kehadiran'] !== '' 
          ? ''.strip_tags($data_absen['kehadiran']??'').'' 
          : 'Tidak Hadir');

  // Map IN
  if (!empty($data_absen['map_in']) && $data_absen['map_in'] !== '-') {
      $map_in_url = 'https://www.google.com/maps/place/' . $data_absen['map_in'].'';
  } else {
      $map_in_url = '-';
  }

  // Map OUT
  if (!empty($data_absen['map_out']) && $data_absen['map_out'] !== '-') {
      $map_out_url = 'https://www.google.com/maps/place/' . $data_absen['map_out'].'';
  } else {
      $map_out_url = '-';
  }


    $keterangan = strip_tags($data_absen['keterangan']??'-');
    $pdf->Cell($widths[0], 7, $no, 1, 0, 'C', false);
    $pdf->Cell($widths[1], 7, ''.tanggal_ind($data_absen['tanggal']), 1, 0, 'C', false);
    $pdf->Cell($widths[2], 7, ''.strip_tags($data_absen['jam_masuk']??'-').' - '.strip_tags($data_absen['jam_pulang']??'-') , 1, 0, 'C', false);
    $pdf->Cell($widths[3], 7, strip_tags($data_absen['jam_toleransi']??'-'), 1, 0, 'C', false);
    $pdf->Cell($widths[4], 7, ''.strip_tags($data_absen['absen_in']??'-').' ('.strip_tags($status_masuk??'').')', 1, 0, 'C', false);
    $pdf->Cell($widths[5], 7, ''.strip_tags($data_absen['absen_out']??'-').' ('.strip_tags($status_pulang??'').')', 1, 0, 'C', false);
    $pdf->Cell($widths[6], 7, strip_tags($kehadiran??'-'), 1, 0, 'C', false);
    if ($map_in_url !=='-') {
        $pdf->SetTextColor(0, 0, 255); // Biru seperti hyperlink
        $pdf->SetFont('', 'U');        // Underline
        $pdf->Cell($widths[7], 7, 'Lokasi in', 1, 0, 'C', false, $map_in_url);
    } else {
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        $pdf->Cell($widths[7], 7, '-', 1, 0, 'C', false);
    }

// Link OUT
     if ($map_out_url !=='-') {
        $pdf->SetTextColor(0, 0, 255);
        $pdf->SetFont('', 'U');
        $pdf->Cell($widths[8], 7, 'Lokasi Out', 1, 0, 'C', false, $map_out_url);
    } else {
        $pdf->SetTextColor(0);
        $pdf->SetFont('');
        $pdf->Cell($widths[8], 7, '-', 1, 0, 'C', false);
    }
    $pdf->SetTextColor(0); // Hitam
    $pdf->SetFont('');
     $pdf->Cell($widths[9], 7, strip_tags($keterangan??''), 1, 0, 'L', false);
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

  $pdf->Output('I', 'Laporan_Absen_'.($_GET['mulai']??'-').'_'.($_GET['selesai']??'-').'.pdf');


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
        <h1>Data Kehadiran Tidak Ditemukan</h1>
        <p>Mohon periksa kembali data yang Anda masukkan atau coba lagi nanti.</p>
        <button onclick="window.close();">Tutup</button>
    </div>';
  }
}
?>