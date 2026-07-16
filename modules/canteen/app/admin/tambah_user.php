<?php
include 'inc/fungsi.php';
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !sds_csrf_verify((string)($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Sesi formulir berakhir.'); }

$username = trim((string)($_POST['username'] ?? ''));
$password = password_hash((string)($_POST['password'] ?? ''), PASSWORD_DEFAULT);
$role = in_array(($_POST['role'] ?? ''), ['admin','kantin','operator'], true) ? (string)$_POST['role'] : 'operator';
$id_kantin = !empty($_POST['id_kantin']) ? (int)$_POST['id_kantin'] : null;

$query = "INSERT INTO users (username, password, role, id_kantin) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "sssi", $username, $password, $role, $id_kantin);
mysqli_stmt_execute($stmt);

$_SESSION['success'] = "User berhasil ditambahkan.";
header('Location: manajemen_user.php');
