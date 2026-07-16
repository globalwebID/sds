<?php if(!empty($connection)){
  echo'<title>Access Denied</title>';
  exit;
}else{
require_once'../../../sw-library/sw-config.php'; 
require_once'../../../sw-library/sw-function.php';
require_once'../../../sw-library/fpdf/fpdf.php';


if(!empty($_GET['id'])){
$id = convert("decrypt", $_GET['id']);

$query_sanksi = "SELECT sanksi_pelanggaran.*, user.nama_lengkap,user.kelas 
                 FROM sanksi_pelanggaran 
                 LEFT JOIN user ON user.user_id = sanksi_pelanggaran.user_id
                 WHERE sanksi_pelanggaran.id='$id'
                 ORDER BY sanksi_pelanggaran.id DESC LIMIT 10";
$result_sanksi = $connection->query($query_sanksi);
if ($result_sanksi->num_rows > 0) {
 $data_sanksi = $result_sanksi->fetch_assoc();

    $data_wali =NULL;
    $data_wali = getWaliKelas($data_sanksi['kelas'], $connection);

    $pesan = str_replace(
        ['{{nama_siswa}}', '{{kelas}}', '{{daftar_pelanggaran}}', '{{peringatan}}'],
        [
            $data_sanksi['nama_lengkap'],
            $data_sanksi['kelas'],
            $data_sanksi['keterangan'],
            $data_sanksi['perihal']
        ],
        $data_sanksi['template']
    );

class PDFWithFooter extends FPDF {
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Halaman ' . $this->PageNo() . ' dari {nb} | Dicetak pada ' . date('d-m-Y H:i'), 0, 0, 'C');
    }
}

/** Buat Footer dan halaman */
    $pdf = new PDFWithFooter();
    $pdf->AliasNbPages();
    $pdf->AddPage('P', 'A4');

    // Menambahkan logo atau kop surat
    if (file_exists('../../../sw-content/'.($site_kop ?? '-logo.png'))) {
        $kopPath = '../../../sw-content/'.strip_tags($site_kop);
    } else {
        $kopPath = '../../../sw-content/'.strip_tags($site_logo);
    }

    $pageWidth = $pdf->GetPageWidth();
    $imageWidth = 170;
    $centerX = ($pageWidth - $imageWidth) / 2;
    $pdf->Image($kopPath, $centerX, 10, $imageWidth);
    $pdf->Ln(22);

    // Garis pemisah
    $pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
    $pdf->Ln(5);

    // Menambahkan Judul Surat
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 6, 'SURAT PERINGATAN', 0, 1, 'C');
    
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 6, 'No. Surat: ' . $data_sanksi['kode_surat'], 0, 1, 'C');  // Nomor Surat
    $pdf->Ln(10);

    // Kepada Yth (Wali Murid)
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 6, 'Kepada Yth:', 0, 1, 'L');
    $pdf->Cell(0, 6, strip_tags($data_sanksi['ditujukan']??'-'), 0, 1, 'L'); 
    $pdf->Cell(0, 6, 'Di Tempat', 0, 1, 'L');
    $pdf->Ln(5);

    // Isi Surat Peringatan
    $pdf->SetFont('Arial', '', 12);
    $pdf->MultiCell(0, 6, $pesan, 0, 'L');
    $pdf->Ln(15);


    /** TANDA TANGAN 3 KOLOM */
    $pdf->SetFont('Arial', '', 12);
    // HEADER TT
    $pdf->Cell(60, 6, 'Wali Murid', 0, 0, 'C');
    $pdf->Cell(60, 6, 'Wali Kelas', 0, 0, 'C');
    $pdf->Cell(60, 6, 'Kepala Sekolah', 0, 1, 'C');

    // JARAK UNTUK TANDA TANGAN
    $pdf->Ln(25);

    /** STAMPEL KEPALA SEKOLAH (OPSIONAL) */
    $stempelPath = '../../../sw-content/'.($row_site['stempel'] ?? 'stempel.png');
    if (file_exists($stempelPath)) {
        $pdf->Image($stempelPath, 135, $pdf->GetY() - 27, 45);
    }

    // NAMA TT
    $pdf->SetFont('Arial','B',12);
    $pdf->Cell(60, 6, strip_tags($data_sanksi['ditujukan'] ?? '-'), 0, 0, 'C');
    $pdf->Cell(60, 6, strip_tags($data_wali['nama_lengkap'] ?? '-'), 0, 0, 'C');
    $pdf->Cell(60, 6, strip_tags($row_site['kepala_sekolah'] ?? '-'), 0, 1, 'C');

    // NIP (Wali Murid biasanya tidak punya)
    $pdf->SetFont('Arial','',12);
    $pdf->Cell(60, 6, '', 0, 0, 'C');
    $pdf->Cell(60, 6, 'NIP. '.($data_wali['nip'] ?? '-'), 0, 0, 'C');
    $pdf->Cell(60, 6, 'NIP. '.($row_site['nip_kepala_sekolah'] ?? '-'), 0, 1, 'C');

    // Output PDF
    $pdf->Output('I', 'Surat_Peringatan_'.$date.'.pdf');

}else{
  echo'<title>Access Denied</title>
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
    }}

}

?>