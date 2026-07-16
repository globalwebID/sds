<?php
$nama_ekskul = $_POST['nama_ekskul'] ?? '';
$nama_pembina = $_POST['nama_pembina'] ?? '';
$tahun_ajaran = $_POST['tahun_ajaran'] ?? '';

if ($nama_ekskul && $tahun_ajaran) {
    $stmt = $conn->prepare("INSERT INTO ekstrakurikuler (nama_ekskul, nama_pembina, tahun_ajaran) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nama_ekskul, $nama_pembina, $tahun_ajaran);
    $stmt->execute();
    $stmt->close();
}
$_SESSION['success'] = "Ekstrakurikuler <strong>$nama_ekskul</strong> berhasil ditambahkan.";
header("Location: ekskul");
exit;
