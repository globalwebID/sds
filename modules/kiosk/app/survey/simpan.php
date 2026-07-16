<?php
require "../../db.php";

// Terima data POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = isset($_POST["nama"]) ? trim($_POST["nama"]) : "-";
    $penilaian = intval($_POST["penilaian"]);
    $saran = trim($_POST["saran"]);

    // Validasi dasar
    // if (empty($nama) || $penilaian < 1 || $penilaian > 5) {
    //     http_response_code(400);
    //     echo "Data tidak valid.";
    //     exit;
    // }

    // Gunakan prepared statement untuk keamanan
    $stmt = $conn->prepare("INSERT INTO survey_kepuasan (nama, penilaian, saran) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $nama, $penilaian, $saran);

    if ($stmt->execute()) {
        http_response_code(200);
        echo "Berhasil disimpan.";
    } else {
        http_response_code(500);
        echo "Gagal menyimpan data.";
    }

    $stmt->close();
} else {
    http_response_code(405);
    echo "Metode tidak diizinkan.";
}

$conn->close();
