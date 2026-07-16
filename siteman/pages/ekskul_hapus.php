<?php
if (!isset($_GET['id'])) {
    $_SESSION['msg'] = [
        'type' => 'danger',
        'text' => "❌ ID tidak ditemukan."
    ];
    header("Location: ekskul");
    exit;
}

$ekskul_id = intval($_GET['id']);

// Cek jumlah siswa yang tergabung
$cek = $conn->prepare("SELECT COUNT(*) FROM ekstrakurikuler_siswa WHERE ekstrakurikuler_id = ?");
$cek->bind_param("i", $ekskul_id);
$cek->execute();
$cek->bind_result($jumlah_anggota);
$cek->fetch();
$cek->close();

if ($jumlah_anggota > 0) {
    $_SESSION['msg'] = [
        'type' => 'warning',
        'text' => "⚠️ Ekstrakurikuler masih memiliki <strong>$jumlah_anggota siswa</strong>. 
        Silakan hapus atau pindahkan siswa terlebih dahulu sebelum menghapus ekskul.
        <br><a href='ekskul_lihat_siswa&id=$ekskul_id' class='btn btn-sm btn-info mt-2'>Lihat Siswa</a>"
    ];
    header("Location: ekskul");
    exit;
}

// Hapus data ekskul
$hapus = $conn->prepare("DELETE FROM ekstrakurikuler WHERE id = ?");
$hapus->bind_param("i", $ekskul_id);
$hapus->execute();


if ($hapus->affected_rows > 0) {
    $_SESSION['msg'] = [
        'type' => 'success',
        'text' => "✅ Ekstrakurikuler berhasil dihapus."
    ];
    header("Location: ekskul");
} else {
    $_SESSION['msg'] = [
        'type' => 'danger',
        'text' => "❌ Gagal menghapus ekstrakurikuler."
    ];
    header("Location: ekskul");
}
