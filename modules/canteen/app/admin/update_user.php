<?php
include 'inc/fungsi.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sesi formulir berakhir.'); }

$id = (int)($_POST['id'] ?? 0);
$username = trim((string)($_POST['username'] ?? ''));
$password = (string)($_POST['password'] ?? '');
$role = in_array(($_POST['role'] ?? ''), ['admin','kantin','operator'], true) ? (string)$_POST['role'] : 'operator';
$id_kantin = !empty($_POST['id_kantin']) ? (int)$_POST['id_kantin'] : null;

if ($password) {
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $query = 'UPDATE users SET username=?,password=?,role=?,id_kantin=? WHERE id=?';
    $stmt=$conn->prepare($query);$stmt->bind_param('sssii',$username,$hashed,$role,$id_kantin,$id);
} else {
    $query = 'UPDATE users SET username=?,role=?,id_kantin=? WHERE id=?';
    $stmt=$conn->prepare($query);$stmt->bind_param('ssii',$username,$role,$id_kantin,$id);
}

if ($stmt->execute()) {
    $_SESSION['success'] = "User berhasil diperbarui";
} else {
    $_SESSION['error'] = "Gagal memperbarui user";
}
$stmt->close();

header("Location: manajemen_user.php");
exit;
