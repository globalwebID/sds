<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{

require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

switch (@$_GET['action']){
case 'add':
$error = array();
  $id       = htmlentities(convert('decrypt', $_POST["id"]??'-'));

  $fields = [
    'nama_kategori'   => 'Nama Mata Pelajaran',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        $$key = anti_injection($_POST[$key]);
    }
  }
  
if (empty($error)) {

  $query="SELECT kategori_pelanggaran_id FROM kategori_pelanggaran WHERE kategori_pelanggaran_id='$id'";
  $result= $connection->query($query);
  if(!$result ->num_rows >0){

    $query="SELECT nama_kategori FROM kategori_pelanggaran WHERE nama_kategori='$nama_kategori' LIMIT 1";
    $result = $connection->query($query);
    if($result->num_rows > 0){
      die('Kategori sudah ada sebelumnya!');
    }

    $add ="INSERT INTO kategori_pelanggaran (nama_kategori) values('$nama_kategori')";
    if($connection->query($add) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        echo'success';
    }
        
  }else{
      $update="UPDATE kategori_pelanggaran SET nama_kategori='$nama_kategori' WHERE kategori_pelanggaran_id='$id'"; 
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
  $query  = "SELECT * FROM kategori_pelanggaran WHERE kategori_pelanggaran_id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $data= $result->fetch_assoc();
      $data['id']             = htmlentities(convert('encrypt', $data["kategori_pelanggaran_id"]??'-'));
      $data['nama_kategori']  = ($data["nama_kategori"]??'-');
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan!';
  }
}
  
break;
case 'delete':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $deleted = "DELETE FROM kategori_pelanggaran WHERE kategori_pelanggaran_id='$id'";
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