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
    'kode'         => 'Kode',
    'nama_mapel'   => 'Nama Mata Pelajaran',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        $$key = anti_injection($_POST[$key]);
    }
  }

if (empty($error)) {

  $query="SELECT id FROM mata_pelajaran WHERE id='$id'";
  $result= $connection->query($query);
  if(!$result ->num_rows >0){

      $query="SELECT id FROM mata_pelajaran WHERE nama_mapel='$nama_mapel' LIMIT 1";
      $result = $connection->query($query);
      if(!$result->num_rows > 0){

        $add ="INSERT INTO mata_pelajaran(kode, nama_mapel) values('$kode', '$nama_mapel')";
          if($connection->query($add) === false) { 
              die($connection->error.__LINE__); 
              echo'Data tidak berhasil disimpan!';
          } else{
              echo'success';
          }
        } else{
          echo 'Kelas '.$nama_mapel.' sudah ada!';
        }
  }else{
      $update="UPDATE mata_pelajaran SET kode='$kode', nama_mapel='$nama_mapel' WHERE id='$id'"; 
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
  $query  = "SELECT * FROM mata_pelajaran WHERE id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $data_mata_pelajaran = $result->fetch_assoc();
      $data['id']         = htmlentities(convert('encrypt', $data_mata_pelajaran["id"]??'-'));
       $data['kode']      = ($data_mata_pelajaran["kode"]??'-');
      $data['nama_mapel'] = ($data_mata_pelajaran["nama_mapel"]??'-');
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan!';
  }
}
  
break;
case 'delete':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
    $deleted = "DELETE FROM mata_pelajaran WHERE id='$id'";
    if($connection->query($deleted) === true) {
      echo'success';
    } else { 
      echo'Data tidak berhasil dihapus.!';
      die($connection->error.__LINE__);
    }
}


break;
}
}?>