<?php
require_once __DIR__ . '/../config/runtime.php';
sds_session_start();
sds_session_destroy();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Logout</title>
  <script>
    // Kirim sinyal ke parent (anjungan.php) untuk menampilkan kembali tombol Fancybox
    window.parent.postMessage({ resetCloseButton: true }, "*");

    // Redirect kembali ke halaman scan_kartu.php di dalam iframe
    window.location.href = "scan_kartu.php";
  </script>
</head>
<body>
  <p>Logging out...</p>
</body>
</html>
