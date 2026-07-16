<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
include('../../../sw-library/sw-function.php');
require_once'../../login/user.php';

// Master Tahun Ajaran kini hanya dikelola dari SDS agar seluruh aplikasi
// memakai periode yang sama. Endpoint lama dikunci untuk mencegah perubahan
// langsung dari modul Absensi.
if (in_array((string)($_GET['action'] ?? ''), ['add','delete','get-data-update'], true)) {
  http_response_code(409);
  echo 'Tahun ajaran hanya dapat dikelola melalui menu Tahun Ajaran di SDS.';
  exit;
}

switch (@$_GET['action']){
/* ---------- ADD  ---------- */
case 'add':
$error = array();
$id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
    
$fields = [
    'tahun_ajaran'   => 'Tahun Ajaran',
];

foreach ($fields as $key => $label) {
  if (empty($_POST[$key])) {
      $error[] = "$label tidak boleh kosong";
  } else {
      $$key = anti_injection($_POST[$key]);
  }
}
if (empty($error)) {

  $query="SELECT tahun_ajaran_id FROM tahun_ajaran WHERE tahun_ajaran_id='$id'";
  $result= $connection->query($query);
  if(!$result ->num_rows >0){
    
      $add ="INSERT INTO tahun_ajaran(tahun_ajaran) values('$tahun_ajaran')";
      if($connection->query($add) === false) { 
          die($connection->error.__LINE__); 
          echo'Data tidak berhasil disimpan!';
        } else{
            echo'success';
        }
  }else{
      $update="UPDATE tahun_ajaran SET tahun_ajaran='$tahun_ajaran' WHERE tahun_ajaran_id='$id'"; 
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
  $query  = "SELECT * FROM tahun_ajaran WHERE tahun_ajaran_id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $data_ajaran = $result->fetch_assoc();
      $data['id']         = htmlentities(convert('encrypt', $data_ajaran["tahun_ajaran_id"]??'-'));
      $data['tahun_ajaran'] = ($data_ajaran["tahun_ajaran"]??'-');
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan!';
  }
}
  

break;
case 'delete':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $deleted = "DELETE FROM tahun_ajaran WHERE tahun_ajaran_id='$id'";
  if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }
}
break;
}}