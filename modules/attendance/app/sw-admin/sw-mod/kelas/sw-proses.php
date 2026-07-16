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
case 'add':
$error = array();

  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $fields = [
    'nama_kelas'   => 'Nama Kelas',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        $$key = anti_injection($_POST[$key]);
    }
  }

if (empty($error)) {

  $query="SELECT kelas_id FROM absensi_kelas where kelas_id='$id'";
  $result= $connection->query($query);
  if(!$result ->num_rows >0){

      $query_kelas="SELECT nama_kelas FROM absensi_kelas WHERE nama_kelas='$nama_kelas'";
      $result_kelas = $connection->query($query_kelas);
      if(!$result_kelas->num_rows > 0){

        $add ="INSERT INTO absensi_kelas(nama_kelas) values('$nama_kelas')";
          if($connection->query($add) === false) { 
              die($connection->error.__LINE__); 
              echo'Data tidak berhasil disimpan!';
          } else{
              echo'success';
          }
        } else{
          echo 'Kelas '.$nama_kelas.' sudah ada!';
        }
  }else{
      $update="UPDATE absensi_kelas SET nama_kelas='$nama_kelas' WHERE kelas_id='$id'"; 
      if($connection->query($update) === false) { 
          die($connection->error.__LINE__); 
          echo'Data tidak berhasil disimpan!';
      } else{
          echo'success';
      }
  }
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}


break;
case 'get-data-update':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $query  = "SELECT * FROM absensi_kelas WHERE kelas_id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $data_kelas = $result->fetch_assoc();
      $data['id']         = htmlentities(convert('encrypt', $data_kelas["kelas_id"]??'-'));
      $data['nama_kelas'] = ($data_kelas["nama_kelas"]??'-');
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan!';
  }
}
  

break;
case 'delete':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $query_kelas ="SELECT kelas.nama_kelas,user.kelas FROM absensi_kelas
      JOIN user ON kelas.nama_kelas =user.kelas WHERE kelas.parent_id='$id'";
  $result_kelas = $connection->query($query_kelas);
  if(!$result_kelas->num_rows > 0){
    $row = $result_kelas->fetch_assoc();

    $deleted = "DELETE FROM absensi_kelas WHERE kelas_id='$id'";
    if($connection->query($deleted) === true) {
      echo'success';
    } else { 
      echo'Data tidak berhasil dihapus.!';
      die($connection->error.__LINE__);
    }

  }else{
    echo 'Data Kelas ini aktif atau digunakan!';
  }
}


/** Tingkatan Kelas */
break;
case 'add-kelas':
$error = array();
  $id       = convert('decrypt', $_POST['id']??'-');

  $fields = [
    'kelas_id'   => 'ID Kelas',
    'nama_kelas'   => 'Nama Kelas',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        if ($key === 'kelas_id') {
            $$key =  htmlentities(convert('decrypt', $_POST[$key]));
        }
        // default untuk field lainnya
        else {
            $$key = anti_injection($_POST[$key]);
        }
    }
  }

if (empty($error)) {

  $query="SELECT kelas_id FROM absensi_kelas WHERE kelas_id='$id'";
  $result= $connection->query($query);
  if(!$result ->num_rows >0){

      $query_kelas="SELECT nama_kelas FROM absensi_kelas WHERE nama_kelas='$nama_kelas'";
      $result_kelas = $connection->query($query_kelas);
      if(!$result_kelas->num_rows > 0){

        $add ="INSERT INTO absensi_kelas(parent_id,nama_kelas) values('$kelas_id', '$nama_kelas')";
          if($connection->query($add) === false) { 
              die($connection->error.__LINE__); 
              echo'Data tidak berhasil disimpan!';
          } else{
              echo'success';
          }
        } else{
          echo 'Kelas '.$nama_kelas.' sudah ada!';
        }
  }else{
      $update="UPDATE absensi_kelas SET nama_kelas='$nama_kelas' WHERE kelas_id='$id'"; 
      if($connection->query($update) === false) { 
          die($connection->error.__LINE__); 
          echo'Data tidak berhasil disimpan!';
      } else{
          echo'success';
      }
  }
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}


break;
case'delete-kelas':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $query_kelas ="SELECT kelas.nama_kelas,user.kelas FROM absensi_kelas
      JOIN user ON kelas.nama_kelas =user.kelas WHERE kelas.kelas_id='$id'";
  $result_kelas = $connection->query($query_kelas);
  if(!$result_kelas->num_rows > 0){
    $row = $result_kelas->fetch_assoc();

    $deleted = "DELETE FROM absensi_kelas WHERE kelas_id='$id'";
    if($connection->query($deleted) === true) {
      echo'success';
    } else { 
      echo'Data tidak berhasil dihapus.!';
      die($connection->error.__LINE__);
    }

  }else{
    echo 'Data Kelas ini aktif atau digunakan!';
  }
}

break;
}}