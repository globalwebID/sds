<?php if(empty($connection) AND !isset($_COOKIE['wali_murid'])){
    header('location:./404');
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';
require_once'../../../sw-library/fpdf/fpdf.php';

$query_tema ="SELECT foto FROM kartu_nama WHERE active='Y'";
$result_tema = $connection->query($query_tema);
$data_tema = $result_tema->fetch_assoc();

$data_siswa = getSiswa($connection, $data_user['nisn']);

if(file_exists('../../../sw-content/tema/'.$data_tema['foto'].'')){
    $background = '../../../sw-content/tema/'.$data_tema['foto'].'';
}else{
    $background = '';
}


if(file_exists('../../../sw-content/avatar/'.strip_tags($data_siswa['avatar']??'-').'')){
    $avatarPath = '../../../sw-content/avatar/'.strip_tags($data_siswa['avatar']??'-').'';
}else{
    $avatarPath = '../../../sw-content/avatar/avatar.jpg';
}

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

    // Tambahkan background jika tersedia
    if (!empty($background) && file_exists($background)) {
        $pdf->Image($background, 20, 28, 55, 90);
    }

    // Tambahkan avatar pengguna
    if (file_exists($avatarPath)) {
        // Area avatar: x=20, width=55 (sama seperti area background/logo)
        $areaX = 20;
        $areaWidth = 55;
        $avatarWidth = 31;
        $avatarHeight = 32;
        $centerX = $areaX + ($areaWidth - $avatarWidth) / 2;
        // Gambar avatar
        $pdf->Image($avatarPath, $centerX, 47, $avatarWidth, $avatarHeight);
        // Tambahkan border biru
        $pdf->SetDrawColor(225, 225, 255); // Biru
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($centerX, 47, $avatarWidth, $avatarHeight);
    }

    
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetTextColor(255, 255, 255);  // Set text color to white
    $pdf->SetXY(20, 38);
    $pdf->SetXY(20, 79);
    $pdf->Cell(55, 10, strip_tags(strtoupper($data_siswa['nama_lengkap']) ?? ''), 0, 0, 'C');
    $pdf->SetXY(20, 83);
    $pdf->Cell(55, 10, 'KELAS '.strip_tags($data_siswa['kelas'] ?? ''), 0, 0, 'C');
    $pdf->Ln(10);
    
    // Menambah NISN dan Email
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);  // Set text color to white
    $pdf->SetXY(23, 90);  // Tentukan posisi awal untuk NISN
    $pdf->Cell(8, 10, 'NISN', 0, 0, 'L');  // Label NISN
    $pdf->Cell(5, 10, ':', 0, 0, 'C');  // Titik dua sejajar
    $pdf->Cell(100, 10, '24325353535', 0, 0, 'L');  // Nilai NISN
    $pdf->SetXY(23, 94);
    $pdf->Cell(8, 10, 'Email', 0, 0, 'L');  // Label Email
    $pdf->Cell(5, 10, ':', 0, 0, 'C');  // Titik dua sejajar
    $pdf->Cell(100, 10, 'sample@gmail.com', 0, 0, 'L');  // Nilai Email

    $pdf->Ln(10);  // Line break setelah menambahkan data


    /** Right */
    if (!empty($background) && file_exists($background)) {
        $pdf->Image($background, 77, 28, 55, 90);
    }


    if (file_exists('../../../sw-content/qrcode/'.($data_siswa['nisn']??'avatar').'.png')) {
        $imageData = '../../../sw-content/qrcode/'.($data_siswa['nisn']??'avatar').'.png';
        $areaX = 77;
        $areaWidth = 55;
        $avatarWidth = 32;
        $avatarHeight = 32;
        $centerX = $areaX + ($areaWidth - $avatarWidth) / 2;
        // Gambar avatar
        $pdf->Image($imageData, $centerX, 47, $avatarWidth, $avatarHeight);
        //$pdf->Image('data:image/jpeg;base64,' . $imageData, $centerX, 47, $avatarWidth, $avatarHeight);
        // Tambahkan border biru
        $pdf->SetDrawColor(255, 255, 255); // Biru
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($centerX, 47, $avatarWidth, $avatarHeight);
    }


    $pdf->SetTextColor(255, 255, 255);  // Set text color to white
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(77, 38);
    $pdf->SetXY(77, 77);
    $pdf->Cell(55, 13, strip_tags(strtoupper($data_siswa['nama_lengkap']) ?? ''), 0, 0, 'C');
    $pdf->SetXY(77, 83);
    $pdf->Cell(55, 10, 'KELAS '.strip_tags($data_siswa['kelas'] ?? ''), 0, 0, 'C');
    $pdf->Ln(10);

    // Menambah NISN dan Email
    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(0, 0, 0);  // Set text color to white
    $pdf->SetXY(80, 90);  // Tentukan posisi awal untuk NISN
    $pdf->Cell(8, 10, 'NISN', 0, 0, 'L');  // Label NISN
    $pdf->Cell(5, 10, ':', 0, 0, 'C');  // Titik dua sejajar
    $pdf->Cell(100, 10, '24325353535', 0, 0, 'L');  // Nilai NISN
    $pdf->SetXY(80, 94);
    $pdf->Cell(8, 10, 'Email', 0, 0, 'L');  // Label Email
    $pdf->Cell(5, 10, ':', 0, 0, 'C');  // Titik dua sejajar
    $pdf->Cell(100, 10, 'sample@gmail.com', 0, 0, 'L');  // Nilai Email

    $pdf->Ln(10);  // Line break setelah menambahkan data


    // Output PDF
    $pdf->Output('I', 'Kartu_nama'.$date.'.pdf');

}
?>