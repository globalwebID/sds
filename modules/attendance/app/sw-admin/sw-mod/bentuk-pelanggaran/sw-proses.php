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
    'kategori'   => 'Jenis Pelanggaran',
    'bentuk_pelanggaran' => 'Bentuk Pelanggaran',
    'bobot' => 'Bobot',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        $$key = anti_injection($_POST[$key]);
    }
  }
  
if (empty($error)) {

  $query  = "SELECT bentuk_pelanggaran_id FROM bentuk_pelanggaran WHERE bentuk_pelanggaran_id='$id'";
  $result = $connection->query($query);
  if(!$result ->num_rows >0){

    $q = "SELECT 1 FROM bentuk_pelanggaran WHERE kategori_pelanggaran_id='$kategori' AND bentuk_pelanggaran_id='$id' LIMIT 1";
    if ($connection->query($q)->fetch_row()) {
        die('Pelanggaran sudah ada sebelumnya!');
    }

    $add ="INSERT INTO bentuk_pelanggaran (kategori_pelanggaran_id,
              bentuk_pelanggaran,
              bobot) values('$kategori',
              '$bentuk_pelanggaran',
              '$bobot')";
    if($connection->query($add) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        echo'success';
    }
        
  }else{
        $update="UPDATE bentuk_pelanggaran SET kategori_pelanggaran_id='$kategori',
        bentuk_pelanggaran='$bentuk_pelanggaran',
        bobot='$bobot' WHERE bentuk_pelanggaran_id='$id'"; 
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
  $query_pelanggaran = "SELECT * FROM bentuk_pelanggaran WHERE bentuk_pelanggaran_id='$id'";
  $result_pelanggaran  = $connection->query($query_pelanggaran);
  if($result_pelanggaran->num_rows > 0){
      $data_pelanggaran = $result_pelanggaran->fetch_assoc();
      $data['bentuk_pelanggaran_id']    = convert('encrypt',$data_pelanggaran["bentuk_pelanggaran_id"]);
      $data['kategori_pelanggaran_id']  = $data_pelanggaran["kategori_pelanggaran_id"];
      $data['bentuk_pelanggaran']       = $data_pelanggaran["bentuk_pelanggaran"];
      $data['bobot']                    = $data_pelanggaran["bobot"];
    echo json_encode($data);
  }else{
      echo'Data tidak ditemukan!';
  }
}
  
break;
case 'delete':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $deleted = "DELETE FROM bentuk_pelanggaran WHERE bentuk_pelanggaran_id='$id'";
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