<?php
$id = $_GET['id'] ?? 0;
$ekskul_id = $_GET['ekskul_id'] ?? 0;

if ($id) {
    $stmt = $conn->prepare("DELETE FROM ekstrakurikuler_siswa WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
}
$_SESSION['success'] = "✅ Siswa berhasil dihapus dari ekstrakurikuler.";
header("Location: ekskul_lihat_siswa?id=$ekskul_id");
exit;
