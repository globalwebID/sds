<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}
else{
require_once '../../../sw-library/sw-config.php';
require_once '../../../sw-library/sw-function.php';
require_once '../../login/user.php';

switch (@$_GET['action']){
case 'dropdown':
if(isset($_POST['kelas'])){
  $kelas = anti_injection($_POST['kelas']??'-');
  $query_siwa = "SELECT user_id,nama_lengkap FROM user WHERE kelas='$kelas'";
  $result_siwa = $connection->query($query_siwa);
  if($result_siwa->num_rows > 0) {
    echo'<option value="">Semua Siswa</option>';
    while($data_siwa = $result_siwa->fetch_assoc()){
      echo'<option value="'.epm_encode($data_siwa['user_id']??'-').'">'.strip_tags($data_siwa['nama_lengkap']??'-').'</option>';
    }
  }else{
    echo'<option value="">Data tidak ditemukan</option>';
  }
}

break;
case 'delete':
if(isset($_POST['id'])){
  $id       = anti_injection(convert('decrypt',$_POST['id']??'-'));
  $deleted = "DELETE FROM pelanggaran WHERE pelanggaran_id='$id'";
  if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }
}


break;
}}