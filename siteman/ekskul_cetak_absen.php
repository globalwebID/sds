<?php
session_start();
ob_start(); // ✅ WAJIB untuk menangkap HTML-nya

require '../db.php';
require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$id = $_GET['id'] ?? 0;

$nama_ekskul = '';
$nama_pembina = '';
$get = $conn->prepare("SELECT nama_ekskul, nama_pembina FROM ekstrakurikuler WHERE id = ?");
$get->bind_param("i", $id);
$get->execute();
$get->bind_result($nama_ekskul, $nama_pembina);
$get->fetch();
$get->close();

$result = $conn->prepare("
    SELECT ps.nama_lengkap, ps.nipd 
    FROM ekstrakurikuler_siswa es
    JOIN pendaftaran_siswa ps ON es.siswa_id = ps.id
    WHERE es.ekstrakurikuler_id = ?
    ORDER BY ps.nama_lengkap ASC
");
$result->bind_param("i", $id);
$result->execute();
$data = $result->get_result();


// Ambil pengaturan sekolah (misalnya hanya ada 1 baris data)
$pengaturan = [];

$result = $conn->query("SELECT * FROM pengaturan LIMIT 1");

if ($result && $result->num_rows > 0) {
    $pengaturan = $result->fetch_assoc();
} else {
    $pengaturan = [
        'nama_sekolah' => '',
        'logo' => '',
        'kop_surat' => ''
    ];
}

$kopSuratImg = '';
$path = '../uploads/logo/' . $pengaturan['kop_surat']; // pastikan path benar

if (!empty($pengaturan['kop_surat']) && file_exists($path)) {
    $imgData = base64_encode(file_get_contents($path));
    $kopSuratImg = '<img src="data:image/jpg;base64,' . $imgData . '" style="width:100%;">';
}


?>

<!DOCTYPE html>
<html>

<head>
    <title>Absensi <?= htmlspecialchars($nama_ekskul) ?></title>
    <style>
        body {
            font-family: Arial;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
        }
    </style>
</head>

<body>
    <?php
    echo $kopSuratImg ?: '<p style="color:red;">Gagal menampilkan kop surat</p>';
    ?>
    <h3>Daftar Hadir Ekstrakurikuler: <?= htmlspecialchars($nama_ekskul) ?></h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>NIPD</th>
                <th>Nama</th>
                <th>Tanda Tangan</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            while ($siswa = $data->fetch_assoc()): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td><?= htmlspecialchars($siswa['nipd']) ?></td>
                    <td><?= htmlspecialchars($siswa['nama_lengkap']) ?></td>
                    <td></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table><br><br>
    <div style="float:right;">
        Pembina <?= htmlspecialchars($nama_ekskul) ?>
        <br><br><br><br>
        <?= htmlspecialchars($nama_pembina) ?><br>
        NIP.
    </div>
</body>

</html>
<?php
$html = ob_get_clean(); // Ambil output HTML

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("Absensi_" . $nama_ekskul . ".pdf", ["Attachment" => false]);
?>