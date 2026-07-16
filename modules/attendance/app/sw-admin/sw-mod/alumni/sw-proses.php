<?php use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

if(!isset($_COOKIE['ADMIN_KEY'])){
  header('location:./login/');
  exit;
} else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

switch (@$_GET['action']){
case'kenaikan':
$error = array();

  if (empty($_POST['status'])) {
    $error[] = 'Kelas harus dipilih';
  } else {
    $status = anti_injection($_POST['status']);
  }

  if (empty($_POST['id'])) {
    $error[] = 'Siswa harus dipilih';
  } else {
    $idArr = $_POST['id'];
  }
  
if (empty($error)) {
  foreach($idArr as $id){ 
    $update="UPDATE user SET active='$status' WHERE user_id='$id'";
    $connection->query($update);
  }
  echo 'success';
}else{
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
}


/* --------------- Delete ------------*/
break;
case 'delete':
if(empty($_POST['id'])){
  $id       = anti_injection(epm_decode($_POST['id']));
  $query ="SELECT avatar,nisn FROM user where user_id='$id' LIMIT 1";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    if(file_exists('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').'') && $row['avatar'] !== 'avatar.jpg'){
      unlink ('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').''); 
    }

    if(file_exists('../../../sw-content/qrcode/'.strip_tags($row['nisn']??'-').'.png')){
      unlink ('../../../sw-content/qrcode/'.strip_tags($row['nisn']??'-').'.png'); 
    }
  }

  $deleted  = "DELETE FROM user WHERE user_id='$id'";
  if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }
}

$connection->close();

break;
}}