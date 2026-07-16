<?php ob_start(); 
  include_once '../sw-library/sw-config.php';
  include_once '../sw-library/sw-function.php';
  ob_start("minify_html");
  $website_url        = $row_site['site_url'];
  $website_name       = $row_site['site_name'];
  $website_phone      = $row_site['site_phone'];
  $website_addres     = $row_site['site_address'];
  $website_logo       = $row_site['site_logo'];
  $website_email      = $row_site['site_email'];
  if(empty($_COOKIE['siswa'])){
    header('location:../');
  }else{
    require_once 'oauth/user.php';
    $data_absen = getAbsenSiswa($connection, $data_user['user_id'], $date);
  }
  
$mod ='home';
if(!empty($_GET['mod'])){$mod = mysqli_escape_string($connection,@$_GET['mod']);}else {$mod ='home';}
if(file_exists("module/$mod/$mod.php")){
  require_once 'sw-header.php';
  require_once("module/$mod/$mod.php");
  require_once 'sw-footer.php';
}else{
  echo'SISWA 404';
}



?>