<?php
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';

$expired_cookie = time() + 60 * 60 * 24 * 30;
$iB 			       = getBrowser();
$browser 		     = $iB['name'].' '.$iB['version'];

switch (@$_GET['action']){
case'notifikasi':
  $error = array();
  if (empty($_POST['id'])) {
    $error[] = 'Nama tidak boleh kosong';
  } else {
    $id = anti_injection($_POST['id']);
  }
  if (empty($error)) {
  $update="UPDATE notifikasi SET status='Y' WHERE notifikasi_id='$id' AND user_id='$data_user[user_id]'"; 
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
}