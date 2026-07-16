<?php
session_start();
require dirname(__DIR__, 3) . '/db.php'; // koneksi database

// Validasi input
if (
    !isset($_POST['nisn']) ||
    !isset($_POST['latitude']) ||
    !isset($_POST['longitude'])
) {
    $_SESSION['error'] = 'Data tidak lengkap!';
    header('Location: students');
    exit;
}

// Ambil dan bersihkan data input
$nisn = trim($_POST['nisn']);
$latitude = trim($_POST['latitude']);
$longitude = trim($_POST['longitude']);

// Validasi isi data
if ($nisn === '' || $latitude === '' || $longitude === '') {
    $_SESSION['error'] = 'Semua data harus diisi!';
    header("Location: form.php?nisn=" . urlencode($nisn));
    exit;
}

// Pastikan nilai latitude dan longitude berupa angka
if (!is_numeric($latitude) || !is_numeric($longitude)) {
    $_SESSION['error'] = 'Koordinat tidak valid!';
    header("Location: form.php?nisn=" . urlencode($nisn));
    exit;
}

// Update data koordinat di database berdasarkan NISN
$stmt = $conn->prepare("
    UPDATE pendaftaran_siswa 
    SET latitude = ?, longitude = ?
    WHERE nisn = ?
");

if ($stmt === false) {
    $_SESSION['error'] = 'Query gagal dipersiapkan.';
    header("Location: form.php?nisn=" . urlencode($nisn));
    exit;
}

$stmt->bind_param('dds', $latitude, $longitude, $nisn);

if ($stmt->execute()) {
    $_SESSION['success'] = 'Koordinat berhasil disimpan!';
} else {
    $_SESSION['error'] = 'Gagal menyimpan koordinat.';
}

$stmt->close();
$conn->close();

// Kembali ke halaman form
header("Location: form.php?nisn=" . urlencode($nisn));
exit;
