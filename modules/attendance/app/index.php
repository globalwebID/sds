<?php ob_start();
  include_once 'sw-library/sw-config.php';
  include_once 'sw-library/sw-function.php';
  ob_start("minify_html");
  $website_url        = $row_site['site_url'];
  $website_name       = $row_site['site_name'];
  $website_phone      = $row_site['site_phone'];
  $website_addres     = $row_site['site_address'];
  $website_logo       = $row_site['site_logo'];
  $website_email      = $row_site['site_email'];
  
$mod = "home";
if(!empty($_GET['mod'])){$mod = mysqli_escape_string($connection,@$_GET['mod']);}else {$mod ='home';}
include_once 'web/sw-header.php';

if(file_exists("web/$mod/$mod.php")){
    require_once("web/$mod/$mod.php");
}else{
  echo'404';
}
include_once 'web/sw-footer.php';
?>