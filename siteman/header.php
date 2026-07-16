<?php
session_start();
require '../db.php';
require '../vendor/autoload.php'; // autoload Dompdf
include 'fungsi.php'; // fungsi umum

// ➜ Hanya admin yang sudah login
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php'); // paksa login
    exit;
}
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? '-';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Dashboard – SMKN 1 Probolinggo</title>

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <!-- Tambahkan di <head> -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <!-- Tambahkan sebelum </body> -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script> -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- Leaflet CSS & JS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

</head>

<body>

    <!-- Sidebar -->
    <?php
    // Simpel: ambil nama file yang sedang di‑load, mis. "dashboard.php"
    $current = basename($_SERVER['PHP_SELF']);
    ?>

    <aside class="sidebar">
        <h2>Admin Panel</h2>
        <nav class="nav">
            <a href="dashboard"
                class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
                Dashboard
            </a>
            <a href="kuota_kelas_index"
                class="<?= $current === 'kuota_kelas_index.php' ? 'active' : '' ?>">
                Data Kelas
            </a>
            <a href="students"
                class="<?= $current === 'students.php' ? 'active' : '' ?>">
                Data Siswa
            </a>
            <a href="log_update"
                class="<?= $current === 'log_update.php' ? 'active' : '' ?>">
                Update Log
            </a>
        </nav>
    </aside>

    <!-- Content -->
    <section class="content">
        <!-- Top bar -->
        <div class="topbar">
            <div class="user">Hai, <?= htmlspecialchars($adminName) ?>! 👋 (<?= htmlspecialchars($adminRole) ?>)</div>
            <form action="logout" method="post" style="margin:0">
                <button type="submit">Keluar</button>
            </form>
        </div>
        <main>