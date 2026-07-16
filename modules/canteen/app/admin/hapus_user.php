<?php
include 'inc/fungsi.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sesi formulir berakhir.'); }
$id = (int)($_POST['id'] ?? 0);

// Ambil info user
$stmt=$conn->prepare('SELECT * FROM users WHERE id=? LIMIT 1');$stmt->bind_param('i',$id);$stmt->execute();$user=$stmt->get_result()->fetch_assoc();$stmt->close();

if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan.";
    header('Location: manajemen_user.php');
    exit;
}

// Jika role kantin, cek apakah id_kantin-nya masih aktif
if ($user['role'] === 'kantin' && !empty($user['id_kantin'])) {
    // Cek apakah kantin masih ada di tabel kantin
    $kantinId=(int)$user['id_kantin'];$stmt=$conn->prepare('SELECT id FROM kantin WHERE id=? LIMIT 1');$stmt->bind_param('i',$kantinId);$stmt->execute();$activeKantin=$stmt->get_result()->fetch_assoc();$stmt->close();
    if ($activeKantin) {
        $_SESSION['error'] = "User tidak bisa dihapus karena masih memiliki kantin aktif.";
        header('Location: manajemen_user.php');
        exit;
    }
}

// Lanjutkan hapus user
$stmt=$conn->prepare('DELETE FROM users WHERE id=?');$stmt->bind_param('i',$id);$stmt->execute();$stmt->close();
$_SESSION['success'] = "User berhasil dihapus.";
header('Location: manajemen_user.php');
exit;
