<?php
require '../../db.php';
require '../fungsi.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tahun_asal = $_POST['tahun_asal'];
    $tahun_tujuan = $_POST['tahun_tujuan'];

    // Ambil semua data ekskul dari tahun asal
    $query = "SELECT * FROM ekstrakurikuler WHERE tahun_ajaran = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $tahun_asal);
    $stmt->execute();
    $result = $stmt->get_result();

    $jumlah_berhasil = 0;
    $jumlah_duplikat = 0;

    while ($row = $result->fetch_assoc()) {
        $nama_ekskul = $row['nama_ekskul'];

        // Cek apakah data dengan nama_ekskul dan tahun_tujuan sudah ada
        $cek = $conn->prepare("SELECT * FROM ekstrakurikuler WHERE nama_ekskul = ? AND tahun_ajaran = ?");
        $cek->bind_param("ss", $nama_ekskul, $tahun_tujuan);
        $cek->execute();
        $cek_result = $cek->get_result();

        if ($cek_result->num_rows == 0) {
            // Jika tidak ada, maka insert
            $insert = $conn->prepare("INSERT INTO ekstrakurikuler (nama_ekskul, tahun_ajaran) VALUES (?, ?)");
            $insert->bind_param("ss", $nama_ekskul, $tahun_tujuan);
            if ($insert->execute()) {
                $jumlah_berhasil++;
            }
        } else {
            $jumlah_duplikat++;
        }
    }

    $_SESSION['msg'] = [
        'type' => 'success',
        'text' => "Salin data selesai: {$jumlah_berhasil} berhasil ditambahkan, {$jumlah_duplikat} duplikat.",
    ];
    header('Location: ../ekskul?tahun=' . rawurlencode((string)$tahun_tujuan));
    exit;
} else {
    echo "Akses tidak sah.";
}
