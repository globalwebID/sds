<?php 
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
} else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';
require_once'../../../sw-library/fpdf/fpdf.php';


$query_tema ="SELECT foto FROM kartu_nama WHERE active='Y'";
$result_tema = $connection->query($query_tema);
$data_tema = $result_tema->fetch_assoc();


if(file_exists('../../../sw-content/tema/'.$data_tema['foto'].'')){
    $background = '../../../sw-content/tema/'.$data_tema['foto'].'';
}else{
    $background = '';
}

if(isset($_GET['jabatan']) && !empty($_GET['jabatan'])){
    $jabatan = strip_tags($_GET['jabatan']??'-');
    $filter = "WHERE jabatan='$jabatan'";
}else{
    $filter = "";
}   


$query_user ="SELECT * FROM pegawai $filter ORDER BY nama_lengkap ASC";
$result_user = $connection->query($query_user);
if($result_user->num_rows > 0){

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
$pdf->AddPage('P', 'A4');
$pageWidth = $pdf->GetPageWidth();


$cardHeight = 98; 
$marginBottom = 0;
$currentY = 0; 

// Loop untuk mencetak setiap ID card
while ($data_user = $result_user->fetch_assoc()) {
    if ($currentY + $cardHeight > 297 - $marginBottom) { 
        $pdf->AddPage(); 
        $currentY = 10; 
    }

    // Tentukan avatar path
    if (file_exists('../../../sw-content/avatar/'.strip_tags($data_user['avatar']??'-').'')) {
        $avatarPath = '../../../sw-content/avatar/'.strip_tags($data_user['avatar']??'-').'';
    } else {
        $avatarPath = '../../../sw-content/avatar/avatar.jpg';
    }

  // Tambahkan background jika tersedia
    if (!empty($background) && file_exists($background)) {
        $pdf->Image($background, 20, $currentY + 28, 55, 90);
    }
    // Menambahkan avatar pengguna
    if (file_exists($avatarPath)) {
        $areaX = 20;
        $areaWidth = 55;
        $avatarWidth = 31;
        $avatarHeight = 32;
        $centerX = $areaX + ($areaWidth - $avatarWidth) / 2;

        // Gambar avatar
        $pdf->Image($avatarPath, $centerX, $currentY + 47, $avatarWidth, $avatarHeight);
        
        // Tambahkan border biru
        $pdf->SetDrawColor(225, 225, 255); // Biru
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($centerX, $currentY + 47, $avatarWidth, $avatarHeight);
    }

   
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(255, 255, 255);  // Set text color to white
    $pdf->SetXY(20, $currentY + 79);  // Posisikan teks untuk nama
    $pdf->Cell(55, 10, strtoupper(strip_tags($data_user['nama_lengkap'] ?? '')), 0, 0, 'C');
    $pdf->SetXY(20, $currentY + 83);  // Posisikan teks untuk kelas
    $pdf->Cell(55, 10, ucfirst($data_user['jabatan'] ?? ''), 0, 0, 'C');
    $pdf->Ln(10); // Spasi setelah teks nama dan kelas
    
    // Menambah NISN dan Email
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);  // Set text color to black
    $pdf->SetXY(23, $currentY + 90);  // Tentukan posisi awal untuk NISN
    $pdf->Cell(8, 10, 'NIP', 0, 0, 'L');  // Label NISN
    $pdf->Cell(3, 10, ':', 0, 0, 'C');  // Titik dua sejajar
    $pdf->Cell(98, 10, $data_user['nip'], 0, 0, 'L');  // Nilai NISN
    $pdf->SetXY(23, $currentY + 94);
    $pdf->Cell(8, 10, 'Email', 0, 0, 'L');  // Label Email
    $pdf->Cell(3, 10, ':', 0, 0, 'C');  // Titik dua sejajar
    $pdf->Cell(98, 10, $data_user['email'], 0, 0, 'L');  // Nilai Email

    $pdf->Ln(10);  // Line break setelah menambahkan data

    // Right Section
    if (!empty($background) && file_exists($background)) {
        $pdf->Image($background, 77, $currentY + 28, 55, 90); 
    }

    // Gambar QR code
    if (file_exists('../../../sw-content/qrcode/pegawai_'.($data_user['qrcode']??'avatar').'.png')) {
        $imageData = '../../../sw-content/qrcode/pegawai_'.($data_user['qrcode']??'avatar').'.png';
        $areaX = 77;
        $areaWidth = 55;
        $avatarWidth = 32;
        $avatarHeight = 32;
        $centerX = $areaX + ($areaWidth - $avatarWidth) / 2;
        
        // Gambar QR code
        $pdf->Image($imageData, $centerX, $currentY + 47, $avatarWidth, $avatarHeight);
        
        // Tambahkan border putih
        $pdf->SetDrawColor(255, 255, 255); // Putih
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($centerX, $currentY + 47, $avatarWidth, $avatarHeight);
    }

    // Menambahkan teks nama lengkap dan kelas di bagian kanan
    $pdf->SetTextColor(255, 255, 255);  // Set text color to white
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetXY(77, $currentY + 77);  // Posisikan nama lengkap bagian kanan
    $pdf->Cell(55, 13, strtoupper(strip_tags($data_user['nama_lengkap'] ?? '')), 0, 0, 'C');
    $pdf->SetXY(77, $currentY + 83);  // Posisikan kelas bagian kanan
    $pdf->Cell(55, 10, ucfirst($data_user['jabatan'] ?? ''), 0, 0, 'C');
    $pdf->Ln(10);  // Spasi setelah teks kelas

    // Menambah NISN dan Email bagian kanan
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);  // Set text color to black
    $pdf->SetXY(80, $currentY + 90);  // Tentukan posisi untuk NISN bagian kanan
    $pdf->Cell(8, 10, 'NIP', 0, 0, 'L');  // Label NISN
    $pdf->Cell(3, 10, ':', 0, 0, 'C');  // Titik dua sejajar
    $pdf->Cell(98, 10, $data_user['nip'], 0, 0, 'L');  // Nilai NISN
    $pdf->SetXY(80, $currentY + 94);  // Tentukan posisi untuk Email bagian kanan
    $pdf->Cell(8, 10, 'Email', 0, 0, 'L');  // Label Email
    $pdf->Cell(3, 10, ':', 0, 0, 'C');  // Titik dua sejajar
    $pdf->Cell(98, 10, $data_user['email'], 0, 0, 'L');  // Nilai Email

    $pdf->SetFont('Arial', 'I', 8); 
    $pdf->SetXY(75, $currentY + 103); 
    $pdf->Cell(55, 10, strip_tags($row_site['nama_sekolah'] ?? 'Sekolah Anda'), 0, 0, 'C');
    $pdf->SetXY(75, $currentY + 107); 
    $pdf->Cell(55, 10, 'Telp.'.strip_tags($row_site['site_phone'] ?? ''), 0, 0, 'C');
    
    $currentY += $cardHeight;  
}
    // Output PDF
    $pdf->Output('I', 'Kartu_nama'.$date.'.pdf');
    
    }else{
      echo'Data yang Anda cari tidak ditemukan';
    }
}?>
