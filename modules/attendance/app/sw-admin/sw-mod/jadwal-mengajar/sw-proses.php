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
    'hari'           => 'Hari',
    'pegawai'        => 'Guru',
    'mata_pelajaran' => 'Mata Pelajaran',
    'kelas'          => 'Kelas',
    'dari_jam'       => 'Dari Jam',
    'sampai_jam'     => 'Sampai Jam',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        $$key = anti_injection($_POST[$key]);
    }
  }

if (empty($error)) {

  $query_kelas = "SELECT 
        anak.kelas_id,
        anak.nama_kelas AS nama_kelas_anak,
        induk.nama_kelas AS nama_kelas_induk
    FROM 
        kelas AS anak
    LEFT JOIN 
        kelas AS induk ON anak.parent_id = induk.kelas_id WHERE anak.nama_kelas='$kelas'";
  $result_kelas = $connection->query($query_kelas);
  $data_kelas = $result_kelas->fetch_assoc();

  $query="SELECT jadwal_id FROM jadwal_mengajar WHERE jadwal_id='$id'";
  $result= $connection->query($query);
  if(!$result ->num_rows >0){

      $query="SELECT jadwal_id FROM jadwal_mengajar WHERE mata_pelajaran='$mata_pelajaran' 
      AND pegawai='$pegawai' AND hari='$hari' AND kelas='$data_kelas[nama_kelas_anak]' LIMIT 1";
      $result = $connection->query($query);
      if($result->num_rows > 0){
        die('Data yang Anda tambah sudah ada!');
      }

     $add = "INSERT INTO jadwal_mengajar (hari, pegawai, mata_pelajaran, tingkat, kelas, dari_jam, sampai_jam) 
      VALUES ('$hari', '$pegawai', '$mata_pelajaran', '$data_kelas[nama_kelas_induk]', '$data_kelas[nama_kelas_anak]', '$dari_jam', '$sampai_jam')";

      if ($connection->query($add) === false) { 
          die($connection->error . __LINE__); 
          echo 'Data tidak berhasil disimpan!';
      } else {
          echo 'success';
      }
        
  }else{
      $update = "UPDATE jadwal_mengajar SET 
          hari = '$hari', 
          pegawai = '$pegawai', 
          mata_pelajaran = '$mata_pelajaran', 
          tingkat = '$data_kelas[nama_kelas_induk]', 
          kelas = '$data_kelas[nama_kelas_anak]', 
          dari_jam = '$dari_jam', 
          sampai_jam = '$sampai_jam' WHERE jadwal_id = '$id'"; 
      if ($connection->query($update) === false) { 
          die($connection->error . __LINE__); 
          echo 'Data tidak berhasil diupdate!';
      } else {
          echo 'success';
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
  $query  = "SELECT * FROM jadwal_mengajar WHERE jadwal_id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $data_jadwal = $result->fetch_assoc();
    $data['jadwal_id']  = htmlentities(convert('encrypt', $data_jadwal["jadwal_id"]??'-'));
    $data['hari']       = ($data_jadwal["hari"]??'-');
    $data['mata_pelajaran']    = ($data_jadwal["mata_pelajaran"]??'-');
    $data['pegawai']    = ($data_jadwal["pegawai"]??'-');
    $data['kelas']      = ($data_jadwal["kelas"]??'-');
    $data['dari_jam']    = ($data_jadwal["dari_jam"]??'-');
    $data['sampai_jam']  = ($data_jadwal["sampai_jam"]??'-');
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan!';
  }
}
  
break;
case 'delete':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $deleted = "DELETE FROM jadwal_mengajar WHERE jadwal_id='$id'";
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