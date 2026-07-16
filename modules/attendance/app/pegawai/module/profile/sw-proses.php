<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';


switch (@$_GET['action']){
case 'update':
$error = array();
$fields = [
  'nama_lengkap'  => 'Nama Lengkap tidak boleh kosong',
  'tempat_lahir'  => 'Tempat Lahir tidak boleh kosong',
  'tanggal_lahir' => 'Tanggal Lahir tidak boleh kosong',
  'jenis_kelamin' => 'Jenis Kelamin tidak boleh kosong',
  'jabatan'       => 'Jabatan tidak boleh kosong',
  'wali_kelas'    => 'Wali Kelas tidak boleh kosong',
  'lokasi'        => 'Lokasi Kerja tidak boleh kosong',
  'alamat'        => 'Alamat Lengkap tidak boleh kosong',
];

  // Validasi kosong
  foreach ($fields as $key => $errorMessage) {
      if (empty($_POST[$key])) {
          die($errorMessage);
      }
  }

  foreach ($fields as $key => $msg) {
    if (empty($_POST[$key])) {
      $error[] = $msg;
    } else {
      $$key = $key === 'nama_lengkap'
        ? mysqli_real_escape_string($connection, $_POST[$key])
        : ($key === 'tanggal_lahir'
          ? date('Y-m-d', strtotime($_POST[$key]))
          : anti_injection($_POST[$key]));
    }
  }

  $nip = empty($_POST['nip']) ? '' : anti_injection($_POST['nip']);

  if (empty($_POST['telp'])) {
    $telp = NULL;
  }else{
    if (substr($_POST['telp'], 0, 2) !== '62') {
      die('No. Telp harus diawali dengan angka 62');
    }else{
      $telp = anti_injection($_POST['telp']);
    }
  }

      
  if (empty($error)) {
    $update = "UPDATE pegawai SET 
          nip = '$nip',
          nama_lengkap = '$nama_lengkap',
          tempat_lahir = '$tempat_lahir',
          tanggal_lahir = '$tanggal_lahir',
          jenis_kelamin = '$jenis_kelamin',
          telp = '$telp',
          jabatan = '$jabatan',
          wali_kelas = '$wali_kelas',
          lokasi = '$lokasi',
          alamat = '$alamat' WHERE pegawai_id = '".htmlspecialchars($data_user['pegawai_id'], ENT_QUOTES, 'UTF-8')."'";

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
}}?>