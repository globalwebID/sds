<?php use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}
else{
require_once '../../../sw-library/sw-config.php';
require_once '../../../sw-library/sw-function.php';
require_once '../../login/user.php';

$no = 0;
switch (@$_GET['action']){
case 'print':

$tanggal = !empty($_GET['tanggal']) ? date('Y-m-d', strtotime($_GET['tanggal'])) : $date;
$kelas  = !empty($_GET['kelas']) ? anti_injection($_GET['kelas']) : null;
$filter = "WHERE absen.tanggal='$tanggal'";
if ($kelas) $filter .= " AND user.kelas='$kelas'";

$data_wali = getWaliKelas($kelas, $connection);

$query_absen = "SELECT 
    absen.tanggal,
    absen.absen_in,
    absen.absen_out,
    absen.status_masuk,
    absen.status_pulang,
    absen.map_in,
    absen.map_out,
    user.nisn,
    user.nama_lengkap,
    user.kelas FROM absen
LEFT JOIN user ON absen.user_id = user.user_id $filter
ORDER BY absen.tanggal ASC";
$result_absen = $connection->query($query_absen);
if($result_absen->num_rows > 0){

echo'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="s-widodo.com">
    <meta name="author" content="s-widodo.com">
    <title>Laporan Absensi hari ini</title>
    <style>

    body{font-family:Arial,Helvetica,sans-serif}
    .text-center{
      text-align: center;
    }
    
    .kop {
      position:relative;
      display:contents;
      margin:0px 0px 20px 0px;
    }

    .kop img{
      width:100%;
      height:auto;
    }

    table.datatable{
      width:100%;
      background-color:#fff;
      border-collapse:collapse;
      border-width:1px;
      border-color:#b3b3b3;
      border-style:solid;
      color:#000;
      margin:10px 0px 0px 0px;
  }
    table.datatable td,table.datatable th{
      border-width:1px;
      border-color:#b3b3b3;
      border-style:solid;
      padding:5px;text-align:left;
      
    }
    table.datatable th{
      background-color:#666666;
      color:#ffffff;
    }
    table.datatable td.text-center,
    table.datatable th.text-center{text-align:center}

    .badge {
      font-size: 66%;
      font-weight: 600;
      line-height: 1;
      display: inline-block;
      padding: 0.35rem 0.375rem;
      transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out, box-shadow .15s ease-in-out;
      text-align: center;
      vertical-align: baseline;
      white-space: nowrap;
      border-radius: 0.375rem;
    }
    .badge-success {
      color: #1aae6f;
      background-color: #b0eed3;
    }
    
    .badge-danger {
      color: #f80031;
      background-color: #fdd1da;
    }

    .rounded {
      border-radius: 0.375rem !important;
    }

    </style>
      <script>
        window.onafterprint = window.close;
        window.print();
      </script>
</head>
<body>

  <div class="container">
    <div class="row">
      <div class="col-md-12">
        <div class="kop text-center">';
        if(file_exists("../../../sw-content/$site_kop")){
          echo'
          <img src="../../../sw-content/'.$site_kop.'" class="img-responsive imaged w100">';
        }
        echo'
        </div>

        <h3 class="text-center" style="margin:0px">LAPORAN ABSENSI SISWA</h3>
        <div class="mt-3 text-center">Tanggal : '.format_hari_tanggal($tanggal).'</div>
      </div>

      <div class="col-md-12">
          <table class="datatable mt-3">
              <thead>
                <tr>
                  <th style="width:20px" class="text-center">No</th>
                  <th>NISN</th>
                  <th>Nama</th>
                  <th>Kelas</th>
                  <th>Absen Masuk</th>
                  <th>Absen Pulang</th>
                  <th>Lokasi Masuk</th>
                  <th>Lokasi Pulang</th>
                </tr>
              </thead>
              <tbody>';
                while ($data_absen = $result_absen->fetch_assoc()){$no++;
            
                  $status_masuk_val  = $data_absen['status_masuk'] ?? '';
                  $status_pulang_val = $data_absen['status_pulang'] ?? '';

                  if (strtolower($status_masuk_val) === 'tepat waktu') {
                      $cls_masuk = 'success';
                  } elseif (strtolower($status_masuk_val) === 'izin') {
                      $cls_masuk = 'warning';
                  } else {
                      $cls_masuk = 'danger';
                  }

                  if (strtolower($status_pulang_val) === 'tepat waktu') {
                      $cls_pulang = 'success';
                  } elseif (strtolower($status_pulang_val) === 'izin') {
                      $cls_pulang = 'warning';
                  } else {
                      $cls_pulang = 'danger';
                  }

                  $status_masuk  = '<span class="badge badge-'.$cls_masuk.'">'.htmlspecialchars($status_masuk_val).'</span>';
                  $status_pulang = '<span class="badge badge-'.$cls_pulang.'">'.htmlspecialchars($status_pulang_val).'</span>';

                $map_in = ($data_absen['map_in'] && $data_absen['map_in'] != '-') 
                ? '<a href="https://www.google.com/maps/place/'.$data_absen['map_in'].'" class="btn btn-outline-primary btn-sm" target="_blank">IN</a>' 
                : '-';

                $map_out = ($data_absen['map_out'] && $data_absen['map_out'] != '-') 
                ? '<a href="https://www.google.com/maps/place/'.$data_absen['map_out'].'" class="btn btn-outline-primary btn-sm" target="_blank">OUT</a>' 
                : '-';
                  

                echo'
                <tr>
                  <td class="text-center">'.$no.'</td>
                  <td>'.strip_tags($data_absen['nisn']??'-').'</td>
                  <td>'.strip_tags($data_absen['nama_lengkap']??'-').'</td>
                  <td>'.strip_tags($data_absen['kelas']??'-').'</td>
                  <td>'.$data_absen['absen_in'].''.$status_masuk.'</td>
                  <td>'.$data_absen['absen_out'].''.$status_pulang.'</td>
                  <td>'.$map_in.'</td>
                  <td>'.$map_out.'</td>
                </tr>';
                }
              echo'
              </tbody>
          </table>

          <table style="width:100%;margin-top:30px">
            <tr>
              <td style="width:60%;text-align:left;vertical-align:top;">
              <strong>Wali Kelas</strong>
              <br><br><br><br><br><br><br><br>
              <br>
              <b>'.strip_tags($data_wali['nama_lengkap']??'-').'</b>
              <br>
              NIP. '.($data_wali['nip']??'-').'
              </td>

              <td style="width:40%;text-align:left;vertical-align:top;">
              '.$row_site['kabupaten'].', '.tgl_indo($date).'<br>
                <strong>Kepala Sekolah</strong><br>
              '.(file_exists("../../../sw-content/".strip_tags($row_site['stempel']??'stempel.png')) 
                ? '<img src="../../../sw-content/'.strip_tags($row_site['stempel']??'stempel.png').'" style="width:160px;height:auto;margin-left: -40px;"><br>' 
                : '').'
              <b>'.strip_tags($row_site['kepala_sekolah']??'').'</b><br>
              NIP. '.($row_site['nip_kepala_sekolah']??'-').'
              </td>
            </tr>
          </table>
      </div>
    </div>
  </div>
</body>
</html>';

}else{
  echo'<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
  <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
}


break;
case'pdf':
require_once '../../../sw-library/fpdf/fpdf.php';

$tanggal = !empty($_GET['tanggal']) ? date('Y-m-d', strtotime($_GET['tanggal'])) : $date;
$kelas  = !empty($_GET['kelas']) ? anti_injection($_GET['kelas']) : null;
$filter = "WHERE absen.tanggal='$tanggal'";
if ($kelas) $filter .= " AND user.kelas='$kelas'";

$data_wali = getWaliKelas($kelas, $connection);

$query_absen = "SELECT 
    absen.tanggal,
    absen.absen_in,
    absen.absen_out,
    absen.status_masuk,
    absen.status_pulang,
    absen.map_in,
    absen.map_out,
    user.nisn,
    user.nama_lengkap,
    user.kelas FROM absen
LEFT JOIN user ON absen.user_id = user.user_id $filter
ORDER BY absen.tanggal ASC";
$result_absen = $connection->query($query_absen);
if($result_absen->num_rows > 0){

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


if(file_exists("../../../sw-content/".strip_tags($site_kop??'logo.jpg')."")){
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

$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 3, 'LAPORAN KEHADIRAN SISWA', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Tanggal: ' . format_hari_tanggal($tanggal), 0, 1, 'C');
$pdf->Ln(5);


// Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'NISN', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Nama', 1, 0, 'C', true);
$pdf->Cell(35, 8, 'Kelas', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Absen Masuk', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'Absen Pulang', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Lokasi Masuk', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Lokasi Pulang', 1, 1, 'C', true);

// Table Data
$pdf->SetFont('Arial', '', 10);
$no = 1;

while ($data = $result_absen->fetch_assoc()) {
    $statusMasuk = $data['status_masuk'] == 'Tepat Waktu' ? 'Tepat Waktu' : 'Terlambat';
    $statusPulang = !empty($data['status_pulang']) ? ($data['status_pulang'] == 'Tepat Waktu' ? '-' : 'Pulang Cepat') : '-';

    $lokasiIn = ($data['map_in'] && $data['map_in'] != '-') 
    ? 'https://www.google.com/maps/place/'.$data['map_in'].'' : '-';

    $lokasiOut = ($data['map_out'] && $data['map_out'] != '-') 
      ? 'https://www.google.com/maps/place/'.$data['map_out'].'' : '-';

    $pdf->Cell(10, 8, $no++, 1, 0, 'C');
    $pdf->Cell(25, 8, $data['nisn'], 1);
    $pdf->Cell(60, 8, $data['nama_lengkap'], 1);
    $pdf->Cell(35, 8, $data['kelas'] ?? '-', 1);
    $pdf->Cell(40, 8, $data['absen_in'] . " ($statusMasuk)", 1);
    $pdf->Cell(40, 8, $data['absen_out'] . " ($statusPulang)", 1);
    if ($lokasiIn !== '-') {
        $pdf->SetTextColor(0, 0, 255);
        $pdf->SetFont('Arial', 'U');
        $pdf->Cell(30, 8, 'IN', 1, 0, 'C', false, $lokasiIn);
    } else {
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(30, 8, '-', 1, 0, 'C');
    }

    if ($lokasiOut !== '-') {
        $pdf->SetTextColor(0, 0, 255);
        $pdf->SetFont('Arial', 'U');
        $pdf->Cell(30, 8, 'OUT', 1, 0, 'C', false, $lokasiOut);
    } else {
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '');
        $pdf->Cell(30, 8, '-', 1, 0, 'C');
    }
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Ln();
}


  $pdf->Ln(20);

  // Tanda tangan kiri (Pegawai) dan kanan (Kepala Sekolah)
  $pdf->SetFont('Arial', '', 10);
  $pdf->Ln(20);

  // Tanggal dan tempat di kanan
  $pdf->Cell(170, 6, '', 0, 0); // Kosongkan kiri
  $pdf->Cell(20); // Margin kiri (offset 20)
  $pdf->Cell(90, 6, $row_site['kabupaten'] . ', ' . tgl_indo($date), 0, 1, 'L');

  $pdf->Cell(20); // Margin kiri (offset 20)
  // Hormat Saya di kiri, Kepala Sekolah di kanan
  $pdf->SetFont('Arial', 'B', 10);
  $pdf->Cell(20, 6, 'Wali Kelas,', 0, 0, 'L');
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
  $pdf->Cell(20, 6, strip_tags($data_wali['nama_lengkap'] ?? ''), 0, 0, 'L');
  $pdf->Cell(150, 6, '', 0, 0); // Spacer
  $pdf->Cell(90, 6, strip_tags($row_site['kepala_sekolah'] ?? '-'), 0, 1, 'L');
  
  // NIP pegawai di kiri, NIP kepala sekolah di kanan
  $pdf->SetFont('Arial', '', 10);
  $pdf->Cell(20); // Margin kiri (offset 20)
  $pdf->Cell(20, 6, 'NIP. ' . ($data_wali['nip'] ?? '-'), 0, 0, 'L');
  $pdf->Cell(150, 6, '', 0, 0); // Spacer
  $pdf->Cell(90, 6, 'NIP. ' . ($row_site['nip_kepala_sekolah'] ?? '-'), 0, 1, 'L');
  $pdf->Output('I', 'Laporan_Absensi_'.$kelas.'_'. $tanggal . '.pdf');

}else{
  echo'<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
  <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
}


/** Download Excel */
break;
case'excel';
require '../../../sw-library/PhpSpreadsheet/autoload.php';

$tanggal = !empty($_GET['tanggal']) ? date('Y-m-d', strtotime($_GET['tanggal'])) : $date;
$kelas  = !empty($_GET['kelas']) ? anti_injection($_GET['kelas']) : null;
$filter = "WHERE absen.tanggal='$tanggal'";
if ($kelas) $filter .= " AND user.kelas='$kelas'";

$data_wali = getWaliKelas($kelas, $connection);

$query_absen = "SELECT 
    absen.tanggal,
    absen.absen_in,
    absen.absen_out,
    absen.status_masuk,
    absen.status_pulang,
    absen.map_in,
    absen.map_out,
    user.nisn,
    user.nama_lengkap,
    user.kelas FROM absen
LEFT JOIN user ON absen.user_id = user.user_id $filter
ORDER BY absen.tanggal ASC";
$result_absen = $connection->query($query_absen);
if($result_absen->num_rows > 0){
    

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
// Set header style
$style_col = [
  'font' => ['bold' => true],
  'alignment' => [
    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
  ],
  'borders' => [
    'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
    'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
    'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
    'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]
  ],
  'fill' => [
    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
    'startColor' => ['argb' => 'FFC0C0C0']
  ]
];

// Set row style
$style_row = [
  'alignment' => [
    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
  ],
  'borders' => [
    'top' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE],
    'right' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE],
    'bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE],
    'left' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE]
  ]
];


  // Set judul
  $sheet->setCellValue('A1', 'LAPORAN ABSENSI SISWA');
  $sheet->mergeCells('A1:K1');
  $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);

  // Tanggal
  $sheet->setCellValue('A2', 'Tanggal: ' . format_hari_tanggal($tanggal));
  $sheet->mergeCells('A2:K2');
  

  // Mengatur alignment (ratakan ke tengah)
  $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

  // Tambahkan style untuk font dan ukuran teks
  $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
  $sheet->getStyle('A2')->getFont()->setSize(12);
  
  // Header tabel
  $headers = ['No', 'NISN', 'Nama', 'Kelas', 'Absen Masuk', 'Status Masuk', 'Absen Pulang', 'Status Pulang', 'Lokasi Masuk', 'Lokasi Pulang', 'Keterangan'];
  $sheet->fromArray($headers, NULL, 'A4');

  $col = 'A';
  foreach ($headers as $header) {
    $sheet->setCellValue($col . '3', $header);
    $sheet->getStyle($col . '3')->applyFromArray($style_col);
    $sheet->getColumnDimension($col)->setAutoSize(14); // Set auto width for each column
    $col++;
  }

    $row = 4;
    $no = 1;
  while ($data = $result_absen->fetch_assoc()) {
      $statusMasuk = $data['status_masuk'] == 'Tepat Waktu' ? 'Tepat Waktu' : 'Terlambat';
      $statusPulang = !empty($data['status_pulang']) ? ($data['status_pulang'] == 'Tepat Waktu' ? '-' : 'Pulang Cepat') : '-';

      $lokasiIn = (!empty($data['map_in']) && $data['map_in'] != '-') 
          ? 'https://www.google.com/maps/place/' . $data['map_in'] 
          : '-';

      $lokasiOut = (!empty($data['map_out']) && $data['map_out'] != '-') 
          ? 'https://www.google.com/maps/place/' . $data['map_out'] 
          : '-';

      $sheet->setCellValue('A' . $row, $no++);
      $sheet->setCellValue('B' . $row, $data['nisn'] ?? '');
      $sheet->setCellValue('C' . $row, $data['nama_lengkap'] ?? '');
      $sheet->setCellValue('D' . $row, $data['kelas'] ?? '-');
      $sheet->setCellValue('E' . $row, $data['absen_in'] ?? '');
      $sheet->setCellValue('F' . $row, $statusMasuk);
      $sheet->setCellValue('G' . $row, $data['absen_out'] ?? '');
      $sheet->setCellValue('H' . $row, $statusPulang);

      if ($lokasiIn !== '-') {
          $sheet->setCellValue('I' . $row, 'Lihat Lokasi Masuk');
          $sheet->getCell('I' . $row)->getHyperlink()->setUrl($lokasiIn);
          $sheet->getStyle('I' . $row)->applyFromArray([
              'font' => ['color' => ['rgb' => '0000FF'], 'underline' => 'single']
          ]);
      } else {
          $sheet->setCellValue('I' . $row, '-');
      }

      if ($lokasiOut !== '-') {
          $sheet->setCellValue('J' . $row, 'Lihat Lokasi Pulang');
          $sheet->getCell('J' . $row)->getHyperlink()->setUrl($lokasiOut);
          $sheet->getStyle('J' . $row)->applyFromArray([
              'font' => ['color' => ['rgb' => '0000FF'], 'underline' => 'single']
          ]);
      } else {
          $sheet->setCellValue('J' . $row, '-');
      }
      $sheet->setCellValue('K' . $row, strip_tags($data['keterangan'] ?? '-'));

      $row++;
    }

    // Atur lebar kolom
    foreach (range('A', 'K') as $col) {
      $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    $lastColIndex = count($headers);
    $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastColIndex);
    $lastRow = $row - 1; // baris terakhir yang berisi data
    $range = 'A3:' . $lastColumn . $lastRow;
    $borderStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
                'color' => ['argb' => 'FF000000']
            ]
        ]
    ];

    $sheet->getStyle($range)->applyFromArray($borderStyle);

    // Baris untuk tanggal (sekitar di atas tanda tangan, kanan)
    $row = $row + 3;
    $sheet->setCellValue('G' . $row, ''.$row_site['kabupaten'].', ' . tgl_indo(date('Y-m-d')) . '');
    $sheet->mergeCells('G' . $row . ':K' . $row);
    $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // Label kolom tanda tangan: kiri Wali Murid, kanan Kepala Sekolah
    $row++;
    $sheet->setCellValue('A' . $row, 'Wali Murid');
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('G' . $row, 'Kepala Sekolah');
    $sheet->mergeCells('G' . $row . ':K' . $row);
    $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('G' . $row)->getFont()->setItalic(true);

    // Ruang kosong untuk tanda tangan (beberapa baris)
    $row++;
    $sheet->mergeCells('A' . $row . ':F' . $row); // kosong
    $sheet->mergeCells('G' . $row . ':K' . $row);
    $row++;
    $sheet->mergeCells('A' . $row . ':F' . $row); // kosong
    $sheet->mergeCells('G' . $row . ':K' . $row);
    $row++;
    $sheet->mergeCells('A' . $row . ':F' . $row); // kosong
    $sheet->mergeCells('G' . $row . ':K' . $row);

    // Nama Wali Murid (kiri) dan Nama Kepala Sekolah (kanan)
    $row++;
    $sheet->setCellValue('A' . $row, ''.strip_tags($data_wali['nama_lengkap']??'').'' );
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('G' . $row, ''.strip_tags($row_site['kepala_sekolah']??'').'' );
    $sheet->mergeCells('G' . $row . ':K' . $row);
    $sheet->getStyle('G' . $row)->getFont()->setBold(true);
    $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // NIP Wali Murid dan NIP Kepala Sekolah
    $row++;
    $sheet->setCellValue('A' . $row, 'NIP. ' . ($data_wali['nip'] ?? '-'));
    $sheet->mergeCells('A' . $row . ':F' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('G' . $row, 'NIP. ' . ($row_site['nip_kepala_sekolah'] ?? '-'));
    $sheet->mergeCells('G' . $row . ':K' . $row);
    $sheet->getStyle('G' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

    // Simpan sebagai file Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Laporan_Absensi_' . $tanggal . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;

} else {
    echo '<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
    <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
}

break;
}}