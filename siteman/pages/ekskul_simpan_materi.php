<?php
$ekskul_id = $_POST['ekskul_id'];
$tanggal = $_POST['tanggal'];
$judul = $_POST['judul'];
$isi = $_POST['isi'];

$stmt = $conn->prepare("INSERT INTO ekskul_materi (ekskul_id, tanggal, judul, isi) VALUES (?, ?, ?, ?)");
$stmt->bind_param("isss", $ekskul_id, $tanggal, $judul, $isi);
$stmt->execute();

$_SESSION['success'] = "Materi berhasil ditambahkan.";
header("Location: ekskul_absen_siswa?ekskul_id=$ekskul_id");
exit;
