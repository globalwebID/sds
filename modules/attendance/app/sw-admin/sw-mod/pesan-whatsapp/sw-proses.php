<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
include('../../../sw-library/sw-function.php');
require_once'../../login/user.php';

switch (@$_GET['action']){
/* ---------- ADD  ---------- */
case 'add':
  $error = array();

  if (empty($_POST['pembukaan'])) {
    $error[] = 'Salam tidak boleh kosong';
  } else {
    $pembukaan = mysqli_real_escape_string($connection,$_POST['pembukaan']);
  }

  if (empty($_POST['pesan_masuk'])) {
    $error[] = 'Status tidak boleh kosong';
  } else {
    $pesan_masuk = anti_injection(htmlentities($_POST['pesan_masuk']));
  }

  if (empty($_POST['penutupan'])) {
    $error[] = 'Salam Penutupan tidak boleh kosong';
  } else {
    $penutupan = mysqli_real_escape_string($connection,$_POST['penutupan']);
  }

  if (empty($_POST['pesan_pulang'])) {
    $error[] = 'Status Pulang tidak boleh kosong';
  } else {
    $pesan_pulang = anti_injection(htmlentities($_POST['pesan_pulang']));
  }

 
  if (empty($error)) {
    $update="UPDATE whatsapp_pesan SET pembukaan='$pembukaan',
            pesan_masuk='$pesan_masuk',
            penutupan='$penutupan',
            pesan_pulang='$pesan_pulang' WHERE whatsapp_pesan_id='1'"; 
    if($connection->query($update) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        echo'success';
    }
  }else{           
      foreach ($error as $key => $values) {            
        echo"$values\n";
      }
  }

break;
}}