<?php use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
require_once '../../../sw-library/sw-config.php';
require_once '../../../sw-library/sw-function.php';
require_once '../../login/user.php';
$no = 0;
$no = 0;
$total_bobot = 0; 

switch (@$_GET['action']){
case 'print':

$filterParts = [];
$from   = isset($_GET['from']) ? date('Y-m-d', strtotime(strip_tags($_GET['from']))) : date('Y-m-d', strtotime($date));
$to     = isset($_GET['to']) ? date('Y-m-d', strtotime(strip_tags($_GET['to']))) : date('Y-m-d', strtotime($date));
$filterParts[] = "sanksi_pelanggaran.tanggal  BETWEEN '$from' AND '$to'";

if (!empty($_GET['siswa'])) {
    $siswa = htmlentities($_GET['siswa']);
    $filterParts[] = "sanksi_pelanggaran.user_id='$siswa'";
}

if (!empty($_GET['kelas'])) {
    $kelas = htmlentities($_GET['kelas']);
    $filterParts[] = "user.kelas='$kelas'";
    $data_wali = getWaliKelas($kelas, $connection);
}
$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query ="SELECT sanksi_pelanggaran.*,user.user_id,user.nama_lengkap,user.kelas FROM sanksi_pelanggaran
LEFT JOIN user ON user.user_id = sanksi_pelanggaran.user_id $filter";
$result = $connection->query($query);
if($result->num_rows > 0){

echo'
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="s-widodo.com">
    <meta name="author" content="s-widodo.com">
    <title>Pelanggaran Siswa</title>
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

        <h3 class="text-center" style="margin:0px">SANKSI PELANGGARAN</h3>
        <div class="mt-3 text-center">Tanggal : '.tanggal_ind($from).' s.d '.tanggal_ind($to).'</div>
      </div>

      <div class="col-md-12">
          <table class="datatable mt-3">
              <thead>
                <tr>
                  <th style="width:20px" class="text-center">No</th>
                  <th>Nama</th>
                  <th>Kelas</th>
                  <th>Surat</th>
                  <th>Diinput oleh</th>
                  <th>Tanggal</th>
                </tr>
              </thead>
              <tbody>';
                while ($data = $result->fetch_assoc()){$no++;
          
                  /** Data Guru */
                  $query_guru ="SELECT nama_lengkap FROM pegawai WHERE pegawai_id='$data[pegawai_id]'";
                  $result_guru = $connection->query($query_guru);
                  $data_guru = $result_guru->fetch_assoc();

                echo'
                <tr>
                  <td class="text-center">'.$no.'</td>
                  <td>'.strip_tags($data['nama_lengkap']??'-').'</td>
                  <td>'.strip_tags($data['kelas']??'-').'</td>
                  <td>'.strip_tags($data['kode_surat']??'-').'<br>'.strip_tags($data['perihal']??'-').'</td>
                  <td>'.strip_tags($data_guru['nama_lengkap']??'-').'</td>
                  <td>'.tanggal_ind($data['tanggal']??'-').'</td>
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

$filterParts = [];
$from   = isset($_GET['from']) ? date('Y-m-d', strtotime(strip_tags($_GET['from']))) : date('Y-m-d', strtotime($date));
$to     = isset($_GET['to']) ? date('Y-m-d', strtotime(strip_tags($_GET['to']))) : date('Y-m-d', strtotime($date));
$filterParts[] = "sanksi_pelanggaran.tanggal  BETWEEN '$from' AND '$to'";

if (!empty($_GET['siswa'])) {
    $siswa = htmlentities($_GET['siswa']);
    $filterParts[] = "sanksi_pelanggaran.user_id='$siswa'";
}

if (!empty($_GET['kelas'])) {
    $kelas = htmlentities($_GET['kelas']);
    $filterParts[] = "user.kelas='$kelas'";
    $data_wali = getWaliKelas($kelas, $connection);
}
$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query ="SELECT sanksi_pelanggaran.*,user.user_id,user.nama_lengkap,user.kelas FROM sanksi_pelanggaran
LEFT JOIN user ON user.user_id = sanksi_pelanggaran.user_id $filter";
$result = $connection->query($query);
if($result->num_rows > 0){

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
$pdf->Cell(0, 3, 'SANKSI PELANGGARAN', 0, 1, 'C');
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Tanggal: ' . tanggal_ind($from).' s.d '.tanggal_ind($to).'', 0, 1, 'C');
$pdf->Ln(5);


// Table Header
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetFillColor(200, 200, 200);
$pdf->Cell(10, 8, 'No', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Nama', 1, 0, 'C', true);
$pdf->Cell(25, 8, 'Kelas', 1, 0, 'C', true);
$pdf->Cell(40, 8, 'No. Surat', 1, 0, 'C', true);
$pdf->Cell(50, 8, 'Perihal', 1, 0, 'C', true);
$pdf->Cell(60, 8, 'Diinput Oleh', 1, 0, 'C', true);
$pdf->Cell(30, 8, 'Tanggal', 1, 1, 'C', true);

// Table Data
$pdf->SetFont('Arial', '', 10);
while ($data = $result->fetch_assoc()){$no++;
          
  /** Data Guru */
  $query_guru ="SELECT nama_lengkap FROM pegawai WHERE pegawai_id='$data[pegawai_id]'";
  $result_guru = $connection->query($query_guru);
  $data_guru = $result_guru->fetch_assoc();

    $pdf->Cell(10, 8, $no++, 1, 0, 'C');
    $pdf->Cell(60, 8, $data['nama_lengkap'], 1);
    $pdf->Cell(25, 8, $data['kelas'] ?? '-', 1);
    $pdf->Cell(40, 8, ($data['kode_surat']??'-'), 1);
    $pdf->Cell(50, 8, ($data['perihal']??'-'), 1);
    $pdf->Cell(60, 8, strip_tags($data_guru['nama_lengkap']??'-'), 1);
    $pdf->Cell(30, 8, tanggal_ind($data['tanggal']??'-'), 1);
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
  $pdf->Output('I', 'Sanksi_pelanggaran_'. $tanggal . '.pdf');

}else{
  echo'<div style="font-size:30px;text-align:center;margin-top:30px;">Data tidak ditemukan</div>
  <center><button onclick="window.close();" style="background:#111111;padding:8px 20px;color:#ffffff;border-radius:10px;">KEMBALI</button></center>';
}


/** Download Excel */
break;
case'excel';
require '../../../sw-library/PhpSpreadsheet/autoload.php';


$filterParts = [];
$from   = isset($_GET['from']) ? date('Y-m-d', strtotime(strip_tags($_GET['from']))) : date('Y-m-d', strtotime($date));
$to     = isset($_GET['to']) ? date('Y-m-d', strtotime(strip_tags($_GET['to']))) : date('Y-m-d', strtotime($date));
$filterParts[] = "sanksi_pelanggaran.tanggal  BETWEEN '$from' AND '$to'";

if (!empty($_GET['siswa'])) {
    $siswa = htmlentities($_GET['siswa']);
    $filterParts[] = "sanksi_pelanggaran.user_id='$siswa'";
}

if (!empty($_GET['kelas'])) {
    $kelas = htmlentities($_GET['kelas']);
    $filterParts[] = "user.kelas='$kelas'";
    $data_wali = getWaliKelas($kelas, $connection);
}
$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query ="SELECT sanksi_pelanggaran.*,user.user_id,user.nama_lengkap,user.kelas FROM sanksi_pelanggaran
LEFT JOIN user ON user.user_id = sanksi_pelanggaran.user_id $filter";
$result = $connection->query($query);
if($result->num_rows > 0){
    

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
  $sheet->setCellValue('A1', 'SANKSI PELANGGARAN');
  $sheet->mergeCells('A1:G1');
  $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);

  // Tanggal
  $sheet->setCellValue('A2', 'Tanggal: ' .tanggal_ind($from).' s.d '.tanggal_ind($to).'');
  $sheet->mergeCells('A2:G2');
  

  // Mengatur alignment (ratakan ke tengah)
  $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
  $sheet->getStyle('A2')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

  // Tambahkan style untuk font dan ukuran teks
  $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(15);
  $sheet->getStyle('A2')->getFont()->setSize(12);
  
  // Header tabel
  $headers = ['No', 'Nama', 'Kelas', 'No. Surat', 'Perihal', 'Di input Oleh', 'Tanggal'];
  $sheet->fromArray($headers, NULL, 'A4');

  $col = 'A';
  foreach ($headers as $header) {
    $sheet->setCellValue($col . '3', $header);
    $sheet->getStyle($col . '3')->applyFromArray($style_col);
    $sheet->getColumnDimension($col)->setAutoSize(14); // Set auto width for each column
    $col++;
  }

  $row = 4;
  $no = 0;
  while ($data = $result->fetch_assoc()){$no++;
          
    /** Data Guru */
    $query_guru ="SELECT nama_lengkap FROM pegawai WHERE pegawai_id='$data[pegawai_id]'";
    $result_guru = $connection->query($query_guru);
    $data_guru = $result_guru->fetch_assoc();
   
      $sheet->setCellValue('A' . $row, $no++);
      $sheet->setCellValue('B' . $row, $data['nama_lengkap'] ?? '');
      $sheet->setCellValue('C' . $row, $data['kelas'] ?? '-');
      $sheet->setCellValue('D' . $row, ($data['kode_surat']??'-'));
      $sheet->setCellValue('E' . $row, ($data['perihal']??'-'));
      $sheet->setCellValue('F' . $row, strip_tags($data_guru['nama_lengkap']??'-'));
      $sheet->setCellValue('G' . $row, tanggal_ind($data['tanggal']??'-'));
      $row++;
    }

    // Atur lebar kolom
    foreach (range('A', 'H') as $col) {
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
    // Tanggal + kabupaten
    $sheet->setCellValue('E' . $row, ''.$row_site['kabupaten'].', ' . tgl_indo(date('Y-m-d')) . '');
    $sheet->mergeCells('E' . $row . ':G' . $row);
    $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // Label tanda tangan
    $row++;
    $sheet->setCellValue('A' . $row, 'Wali Murid');
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('E' . $row, 'Kepala Sekolah');
    $sheet->mergeCells('E' . $row . ':G' . $row);
    $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('E' . $row)->getFont()->setItalic(true);

    // Ruang kosong tanda tangan (3 baris)
    $row++;
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->mergeCells('E' . $row . ':G' . $row);
    $row++;
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->mergeCells('E' . $row . ':G' . $row);
    $row++;
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->mergeCells('E' . $row . ':G' . $row);

    // Nama Wali Murid & Kepala Sekolah
    $row++;
    $sheet->setCellValue('A' . $row, ''.strip_tags($data_wali['nama_lengkap']??'').'' );
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('E' . $row, ''.strip_tags($row_site['kepala_sekolah']??'').'' );
    $sheet->mergeCells('E' . $row . ':G' . $row);
    $sheet->getStyle('E' . $row)->getFont()->setBold(true);
    $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    // NIP Wali Murid & Kepala Sekolah
    $row++;
    $sheet->setCellValue('A' . $row, 'NIP. ' . ($data_wali['nip'] ?? '-'));
    $sheet->mergeCells('A' . $row . ':D' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

    $sheet->setCellValue('E' . $row, 'NIP. ' . ($row_site['nip_kepala_sekolah'] ?? '-'));
    $sheet->mergeCells('E' . $row . ':G' . $row);
    $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

    $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

    // Simpan sebagai file Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="Sanksi_pelanggaran_' . $tanggal . '.xlsx"');
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