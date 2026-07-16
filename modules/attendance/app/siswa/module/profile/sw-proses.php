<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:../404');
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';


switch (@$_GET['action']){
case 'update':
$error = array();
$fields = [
    'nisn' => 'NISN tidak boleh kosong',
    'nama_lengkap' => 'Nama Lengkap tidak boleh kosong',
    'lokasi' => 'Lokasi Kerja tidak boleh kosong',
    'tempat_lahir' => 'Tempat Lahir tidak boleh kosong',
    'tanggal_lahir' => 'Tanggal Lahir tidak boleh kosong',
    'jenis_kelamin' => 'Jenis Kelamin tidak boleh kosong',
    'kelas' => 'Kelas tidak boleh kosong',
    'tahun_ajaran' => 'Tahun ajaran tidak boleh kosong',
    'alamat' => 'Alamat Lengkap tidak boleh kosong',
    'telp' => 'No. Telp tidak boleh kosong'
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

  $rfid = empty($_POST['rfid']) ? '' : anti_injection($_POST['rfid']);

  if (!validasiNama($_POST['nama_lengkap'])) {
    $error[] = 'Nama tidak valid! Hanya huruf dan angka yang diperbolehkan';
  }

  if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error[] = "Email yang Anda masukan tidak valid";
  }

  if (substr($telp, 0, 2) !== '62') {
      die('No. Telp harus diawali dengan angka 62');
  }
  if (empty($_POST['tanggal_lahir'])) {
      $error[] = 'Tanggal Lahir tidak boleh kosong';
  } else {
      $tanggal_lahir = date('Y-m-d', strtotime($_POST['tanggal_lahir']));
  }
      
  if (empty($error)) {
    $update="UPDATE user SET 
        nisn='$nisn',
        email='$email',
        nama_lengkap='$nama_lengkap',
        tempat_lahir='$tempat_lahir',
        tanggal_lahir='$tanggal_lahir',
        jenis_kelamin='$jenis_kelamin',
        kelas='$kelas',
        tahun_ajaran='$tahun_ajaran',
        lokasi='$lokasi',
        alamat='$alamat',
        telp='$telp' WHERE user_id='".htmlspecialchars($data_user['user_id'], ENT_QUOTES, 'UTF-8')."'"; 
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