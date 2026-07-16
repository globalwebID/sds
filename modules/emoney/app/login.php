<?php
require_once dirname(__DIR__) . '/config/runtime.php';
sds_require_installed();
$emoneyAuthUrl = sds_base_url('emoney/api/auth.php');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Login Siswa</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* (CSS kamu TIDAK DIUBAH) */
*{box-sizing:border-box;font-family:Arial,Helvetica,sans-serif}
body{
  margin:0;min-height:100vh;display:flex;
  align-items:center;justify-content:center;
  background:linear-gradient(135deg,#e11d48,#9ca34c)
}
.login-box{
  background:#fff;width:100%;max-width:95%;
  padding:25px;border-radius:12px;
  box-shadow:0 10px 30px rgba(0,0,0,.2)
}
.login-box h2{text-align:center;margin-bottom:20px;color:#e11d48}
.login-box input{
  width:100%;padding:14px;margin-bottom:14px;
  border-radius:8px;border:1px solid #ddd;font-size:16px
}
.login-box input:focus{outline:none;border-color:#e11d48}
.login-box button{
  width:100%;padding:14px;background:#e11d48;
  color:#fff;font-size:16px;border:none;
  border-radius:8px;cursor:pointer
}
.login-box button:active{transform:scale(.98)}
.info{margin-top:10px;font-size:13px;text-align:center;color:#666}
</style>
</head>

<body>

<div class="login-box">
  <h2>Login Pengguna Kartu</h2>

  <form id="loginForm" autocomplete="off">
    <input name="nisn" placeholder="NISN" required inputmode="numeric" pattern="[0-9]+">
    <input name="pin" type="password" placeholder="PIN 6 Digit" maxlength="6" pattern="[0-9]{6}" required inputmode="numeric">
    <button type="submit">Masuk</button>
  </form>

  <!--<div class="info">PIN = 6 digit terakhir NISN</div>-->
</div>

<script>
document.getElementById('loginForm').onsubmit = async e => {
  e.preventDefault();

  const res = await fetch(<?=json_encode($emoneyAuthUrl, JSON_UNESCAPED_SLASHES)?>, {
    method: 'POST',
    body: new FormData(e.target),
    credentials: 'include' // WAJIB
  }).then(r => r.json()).catch(()=>null);

  if (!res) {
    alert('Gagal terhubung ke server');
    return;
  }

  if (res.success) {
    location.href = 'dashboard/';
  } else {
    alert(res.message);
  }
};
</script>

</body>
</html>
