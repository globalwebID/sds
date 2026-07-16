<?php
require '../vendor/autoload.php';
require '../db.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

$id = $_GET['id'];
$stmt = $conn->prepare("SELECT * FROM pendaftaran_siswa WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$siswa = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$siswa) die("Siswa tidak ditemukan.");

// Generate QR dari RFID
$rfid = $siswa['rfid'] ?: $siswa['nisn'];
$qr = QrCode::create($rfid)->setSize(100);
$writer = new PngWriter();
$qrResult = $writer->write($qr);
$qrImage = base64_encode($qrResult->getString());

// Ambil data penting
$nama = strtoupper($siswa['nama_lengkap']);
$nisn = $siswa['nisn'];
$agama = strtoupper($siswa['agama']);
$ttl = strtoupper($siswa['tempat_lahir']) . ', ' . date('d F Y', strtotime($siswa['tanggal_lahir']));
$alamat = strtoupper($siswa['alamat'] . ', ' . $siswa['kecamatan'] . ', ' . $siswa['kota']);

// Path foto siswa
$fotoPath = "../uploads/" . $siswa['foto'];
$fotoBase64 = file_exists($fotoPath) ? base64_encode(file_get_contents($fotoPath)) : '';

$pengaturan = [];

$result = $conn->query("SELECT * FROM pengaturan LIMIT 1");

if ($result && $result->num_rows > 0) {
    $pengaturan = $result->fetch_assoc();
} else {
    // Default jika belum ada data
    $pengaturan = [
        'nama_sekolah' => '',
        'logo' => ''
    ];
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Kartu Pelajar</title>
    <style>
        body {
            margin: 0;
            padding: 0;
        }

        .kartu {
            width: 340px;
            height: 540px;
            font-family: Arial, sans-serif;
            position: relative;
            background-image: url('../assets/img/kartu_pelajar_bg.jpg');
            background-size: cover;
            color: white;
            overflow: hidden;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.4);
        }

        .kartu .header {
            text-align: center;
            padding: 10px 15px 5px;
            display: flex;
        }

        .kartu .header img {
            width: 45px;
            vertical-align: middle;
        }

        .kartu .header h4 {
            font-size: 14px;
            margin: 5px 0 0;
        }

        .kartu .header h5 {
            font-size: 13px;
            color: #ffd700;
            margin: 0;
        }

        .foto {
            text-align: center;
            margin-top: 8px;
        }

        .foto img {
            width: 100%;
            /* height: 130px; */
            object-fit: cover;
            border: 2px solid white;
        }

        .info {
            padding: 10px 15px;
            font-size: 14px;
            line-height: 1.4em;
            z-index: 9;
            position: fixed;
            bottom: 133px;
            width: 200px;
        }

        .info .nama {
            font-weight: bold;
            font-size: 15px;
            margin-bottom: 3px;
        }

        .qr {
            position: absolute;
            bottom: 15px;
            right: 15px;
        }

        .qr img {
            width: 100px;
        }
    </style>
</head>

<body>
    <div class="kartu">
        <!-- HEADER -->
        <div class="header">
            <div>
                <?php if (!empty($pengaturan['logo'])): ?>
                    <img src="../uploads/logo/<?= htmlspecialchars($pengaturan['logo']) ?>" alt="Logo Sekolah" width="40" style="height: auto;">
                <?php endif; ?>
            </div>
            <div>
                <h4>KARTU PELAJAR</h4>
                <h5>SMKN 3 PROBOLINGGO</h5>
            </div>
        </div>

        <!-- FOTO -->
        <div class="foto">
            <?php if ($fotoBase64): ?>
                <img src="data:image/jpeg;base64,<?= $fotoBase64 ?>" alt="Foto Siswa">
            <?php else: ?>
                <div style="width:100px; height:130px; background:#ccc; line-height:130px; color:#333;">No Foto</div>
            <?php endif; ?>
        </div>

        <!-- INFORMASI -->
        <div class="info">
            <div class="nama"><?= $nama ?></div>
            <div><?= $nisn ?></div>
            <div><?= $agama ?></div>
            <div><?= $ttl ?></div>
            <div><?= $alamat ?></div>
        </div>

        <!-- QR CODE -->
        <div class="qr">
            <img src="data:image/png;base64,<?= $qrImage ?>" alt="QR">
        </div>
    </div>
</body>

</html>