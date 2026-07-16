<?php
session_start();
require_once __DIR__.'/../config/runtime.php';
$centralControlConnection=sds_mysqli('main');
sds_apply_central_controls($centralControlConnection,'E-Money');

/* ===============================
   JIKA SUDAH LOGIN → DASHBOARD
================================ */
if (isset($_SESSION['login']) && $_SESSION['login'] === true) {
    header("Location: dashboard/");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>STUDENT CARD MONITOR</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-J18CE0BVMY"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-J18CE0BVMY');
</script>
<style>
* {
  box-sizing: border-box;
  font-family: 'Segoe UI', Arial, sans-serif;
}

body {
  margin: 0;
  min-height: 100vh;
  /*background: linear-gradient(135deg, #e11d48, #9ca34c);*/
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  background-image: url("assets/img/splashscreen.webp");
  background-size: cover;
  background-position: center;
  background-repeat: no-repeat;

}

.card {
  background: rgba(0,0,0,.25);
  backdrop-filter: blur(10px);
  padding: 15px;
  border-radius: 15px;
  width: 100%;
  max-width: 95%;
  text-align: center;
  box-shadow: 0 15px 30px rgba(0,0,0,.4);
}

h1 {
  margin-top: 0;
  font-size: 22px;
}

p {
  opacity: .9;
}

.btn {
  display: block;
  width: 100%;
  padding: 14px;
  border-radius: 12px;
  background: #111827;
  color: #fff;
  border: none;
  font-size: 16px;
  margin-top: 20px;
  cursor: pointer;
}

.footer {
  margin-top: 25px;
  font-size: 13px;
  opacity: .8;
}
</style>
</head>

<body>

<div class="card">
  <!--<h1>STUDENT CARD MONITOR</h1>-->
  <!--<p>SMK Negeri 1 Probolinggo</p>-->
  <h1>TENTANG APLIKASI</h1>
<hr>
 <p>“Aplikasi ini terhubung dengan <b>Kartu Pelajar</b> sebagai Sistem Informasi bagi Pengguna untuk memantau dan mengelola aktivitas di lingkungan sekolah secara terintegrasi.”</p>
  <button class="btn" onclick="location.href='login.php'">
    Login Pengguna
  </button>

  <div class="footer">
    © <?= date('Y'); ?> SMKN 1 Probolinggo
  </div>
</div>

</body>
</html>
