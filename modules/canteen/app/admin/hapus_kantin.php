<?php
include 'inc/fungsi.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sesi formulir berakhir.'); }
$id = (int)($_POST['id'] ?? 0);

if (!$id) {
    header("Location: kantin.php");
    exit;
}

// Cek apakah ada transaksi terkait dengan kantin ini
$stmt=$conn->prepare('SELECT COUNT(*) total FROM transaksi_kantin WHERE id_kantin=?');$stmt->bind_param('i',$id);$stmt->execute();$data=$stmt->get_result()->fetch_assoc();$stmt->close();

if ($data['total'] > 0) {
    // Ada transaksi, tidak boleh dihapus
    header("Location: kantin.php?error=Kantin tidak bisa dihapus karena masih memiliki transaksi");
    exit;
}

// Ambil data gambar kantin
$stmt=$conn->prepare('SELECT gambar FROM kantin WHERE id=? LIMIT 1');$stmt->bind_param('i',$id);$stmt->execute();$kantin=$stmt->get_result()->fetch_assoc();$stmt->close();

// Hapus user yang terkait
$stmt_user = $conn->prepare("DELETE FROM users WHERE id_kantin = ? AND role = 'kantin'");
$stmt_user->bind_param("i", $id);
$stmt_user->execute();

// Hapus kantin
$stmt = $conn->prepare("DELETE FROM kantin WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// Hapus gambar jika ada
if ($kantin) {
    $image=dirname(__DIR__).'/images/kantin/'.basename((string)$kantin['gambar']);
    if(is_file($image))unlink($image);
}

header("Location: kantin.php?success=Data kantin berhasil dihapus");
exit;
