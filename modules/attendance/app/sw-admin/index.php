<?php
require_once'../sw-library/sw-config.php';
include_once'../sw-library/sw-function.php';
ob_start("minify_html");
$sdsAdminId = (int)($_SESSION['admin_id'] ?? 0);
if ($sdsAdminId > 0) {
  $stmtSdsAdmin = $connection->prepare("SELECT a.username,x.app_role FROM admins a JOIN app_admin_access x ON x.admin_id=a.id AND x.application='absensi' AND x.active='Y' WHERE a.id = ? LIMIT 1");
  $stmtSdsAdmin->bind_param('i', $sdsAdminId);
  $stmtSdsAdmin->execute();
  $sdsAdmin = $stmtSdsAdmin->get_result()->fetch_assoc();
  $stmtSdsAdmin->close();
  if (!$sdsAdmin) {
    header('location:../../siteman/dashboard');
    exit;
  }

  // Marker kompatibilitas untuk endpoint lama; autentikasi tetap dari sesi SDS.
  $adminMarker = htmlentities(epm_encode($sdsAdminId));
  $keyMarker = hash('sha256', (string)$sdsAdmin['username']);
  $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  setcookie('ADMIN_KEY', $adminMarker, ['expires'=>time()+86400, 'path'=>'/', 'secure'=>$secureCookie, 'httponly'=>true, 'samesite'=>'Lax']);
  setcookie('KEY', $keyMarker, ['expires'=>time()+86400, 'path'=>'/', 'secure'=>$secureCookie, 'httponly'=>true, 'samesite'=>'Lax']);
  $_COOKIE['ADMIN_KEY'] = $adminMarker;
  $_COOKIE['KEY'] = $keyMarker;
}

if($sdsAdminId <= 0 && !isset($_COOKIE['ADMIN_KEY'])){
  header('location:./login/');
  exit;
}else{ 
    require_once'./login/user.php';
}
    
if(!empty($_GET['mod'])){$mod = mysqli_escape_string($connection,@$_GET['mod']);}else {$mod ='home';}
  include_once 'sw-mod/header.php';
  if(file_exists('./sw-mod/'.$mod.'/'.$mod.'.php')){
    include('./sw-mod/'.$mod.'/'.$mod.'.php');
    include_once 'sw-mod/footer.php';
  }else{
    include('./sw-mod/home/home.php');
    include_once './sw-mod/footer.php';
  }
  function theme_404(){
    echo'
    <div class="text-center">
    <h1 class="display-1 mb-20 text-info"><i class="ni ni-spaceship"></i></h1>
    <h1 class="display-1 mb-10 mt-10">404</h1>
     <h4 class="mb-10">Sepertinya Halaman yang anda tidak ditemukan</h4>
     <button type="button" class="btn btn-primary mt-4" onclick="history.back()">Kembali</button>
    </div>';
  }

  function hak_akses(){
    echo'
    <div class="text-center">
    <h1 class="display-1 mb-20 text-info"><i class="ni ni-spaceship"></i></h1>
    <h1 class="display-1 mb-10 mt-10">Oop</h1>
     <h4 class="mb-10">Anda tidak memiliki hak Akses halaman ini</h4>
     <button type="button" class="btn btn-primary mt-4" onclick="history.back()">Kembali</button>
    </div>';
  }
  ob_end_flush(); // minify_html
?>
