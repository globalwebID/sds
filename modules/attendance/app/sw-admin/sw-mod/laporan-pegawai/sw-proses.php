<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

switch (@$_GET['action']){
case 'dropdown':
  if (empty($_POST['lokasi'])) {
    $lokasi = '';
  } else {
    $lokasi = anti_injection($_POST['lokasi']);
  }

$query_pegawai = "SELECT pegawai_id,nama_lengkap FROM pegawai WHERE lokasi='$lokasi'";
$result_pegawai = $connection->query($query_pegawai);
if($result_pegawai->num_rows > 0) {
  while($data_pegawai = $result_pegawai->fetch_assoc()){
    echo'<option value="'.epm_encode($data_pegawai['pegawai_id']).'">'.strip_tags($data_pegawai['nama_lengkap']).'</option>';
  }
}else{
  echo'<option value="">Data tidak ditemukan</option>';
}


break;
case 'get-data-update':
if(empty($_GET['id'])){
  $id             = anti_injection(epm_decode($_POST['id']??''));

  $query_absen   = "SELECT absen_id,absen_in,absen_out,status_masuk,status_pulang,keterangan FROM absen_pegawai WHERE absen_id='$id'";
  $result_absen  = $connection->query($query_absen);
  if($result_absen->num_rows > 0){
    $data_absen = $result_absen->fetch_assoc();
      $data['absen_id']     = epm_encode($data_absen["absen_id"]);
      $data['absen_in']     = strip_tags($data_absen["absen_in"]??'');
      $data['absen_out']    = strip_tags($data_absen["absen_out"]??'');
      $data['status_masuk'] = strip_tags($data_absen["status_masuk"]??'');
      $data['status_pulang'] = strip_tags($data_absen["status_pulang"]??'');
      $data['keterangan']   = strip_tags($data_absen["keterangan"]??'');
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan';
  }
}

break;
case 'update':
$error = array();

  if (empty($_POST['id'])) {
    $error[] = 'ID tidak boleh kosong';
  } else {
    $id = anti_injection(epm_decode($_POST['id']));
  }

  if (empty($_POST['absen_in'])) {
    $error[] = 'Absen masuk tidak boleh kosong';
  } else {
    $absen_in = anti_injection($_POST['absen_in']);
  }

  if (empty($_POST['absen_out'])) {
    $error[] = 'Absen pulang tidak boleh kosong';
  } else {
    $absen_out = anti_injection($_POST['absen_out']);
  }

  if (empty($_POST['status_masuk'])) {
    $status_masuk = null;
  } else {
    $status_masuk = anti_injection($_POST['status_masuk']);
  }

  if (empty($_POST['status_pulang'])) {
    $status_pulang = null;
  } else {
    $status_pulang = anti_injection($_POST['status_pulang']);
  }

  if (empty($_POST['keterangan'])) {
    $keterangan = '-';
  } else {
    $keterangan = anti_injection($_POST['keterangan']);
  }
    
  if (empty($error)) {
    $update = "UPDATE absen_pegawai SET 
              absen_in='$absen_in',
              absen_out='$absen_out',
              status_masuk='$status_masuk',
              status_pulang='$status_pulang',
              keterangan='$keterangan' WHERE absen_id='$id'";
    if($connection->query($update) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        echo'success';
    }
  }else{
    foreach ($error as $key => $value) {
      echo"$value\n";
    }
  }
break;
}
}?>