<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

switch (@$_GET['action']){
case 'add':
  $error = array();
  $id       = htmlentities(convert('decrypt', $_POST['id']??''));

  if (empty($_POST['kode'])) {
    $error[] = 'Kode tidak boleh kosong';
  } else {
    $kode = trim($_POST['kode']);
  }

  if (empty($_POST['template'])) {
    $error[] = 'Template tidak boleh kosong';
  } else {
    $template = trim($_POST['template']);
  }

  if (empty($error)) {
    $query  = "SELECT id FROM template_surat WHERE id='$id'";
    $result = $connection->query($query) or die($connection->error.__LINE__);
    if(!$result ->num_rows >0){
  
      $query_check ="SELECT id FROM template_surat WHERE tipe='pelanggaran'";
      $result_check = $connection->query($query_check); 
      if($result_check->num_rows > 0){
          die('Template Surat sudah digunakan!');
          exit;
      }

      /* --  Insert data -- */
      $add ="INSERT INTO template_surat (kode,
              template,
              tipe,
              date) VALUES('$kode',
              '$template',
              'pelanggaran',
              '$date')";
        if($connection->query($add) === false) { 
            die($connection->error.__LINE__); 
            echo'Data tidak berhasil disimpan!';
        } else{
            echo'success';
        } 
  }else{
      /* --  Update data -- */
      $update="UPDATE template_surat SET 
              kode       ='$kode',
              template   ='$template',
              date       ='$date' WHERE id='$id'"; 
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
  $query  = "SELECT * FROM template_surat WHERE id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $data_temp = $result->fetch_assoc();
    $data = array(
      'id'          => convert('encrypt',$data_temp['id']),
      'kode'        => $data_temp['kode'],
      'template'    => $data_temp['template']
    );
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan!';
  }
}
  
/* --------------- Delete ------------*/
break;
case 'delete':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $deleted = "DELETE FROM template_surat WHERE id='$id'";
  if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    //tidak berhasil
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }
}

break;
}
}?>