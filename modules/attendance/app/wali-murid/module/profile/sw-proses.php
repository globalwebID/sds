<?php if(empty($connection) AND !isset($_COOKIE['wali_murid'])){
    header('location:../404');
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';


switch (@$_GET['action']){
case 'cek-nisn':
header('Content-Type: application/json');
if (isset($_POST['nisnVal'])) {
    $nisnVal = anti_injection($_POST['nisnVal']);
    $query_siswa = "SELECT nisn, nama_lengkap FROM user WHERE nisn='$nisnVal' LIMIT 1";
    $result_siswa = $connection->query($query_siswa);
    if ($result_siswa && $result_siswa->num_rows > 0) {
        $data_siswa = $result_siswa->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'data' => $data_siswa
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Data tidak ditemukan'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter tidak ditemukan'
    ]);
}

break;
case 'update':
$error = array();
$fields = [
    'nama_lengkap'   => 'Nama Lengkap',
    'email'          => 'Email',
    'tempat_lahir'   => 'Tempat Lahir',
    'jenis_kelamin'  => 'Jenis Kelamin',
    'telp'           => 'No. Telepon',
    'nisn'           => 'NISN',
    'nama_siswa'     => 'Nama Siswa',
    'alamat'         => 'Alamat',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {

        if ($key === 'nama_lengkap') {
            $$key = mysqli_real_escape_string($connection, $_POST[$key]);
        }
        // khusus email
        elseif ($key === 'email') {
            $email = anti_injection($_POST[$key]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error[] = "Format email tidak valid";
            }
            $$key = $email;
        }
        // khusus telp
        elseif ($key === 'telp') {
            $telp = anti_injection($_POST[$key]);
            if (substr($telp, 0, 2) !== '62') {
                $error[] = "Nomor telepon harus diawali dengan 62";
            }
            $$key = $telp;
        }
        else {
            $$key = anti_injection($_POST[$key]);
        }
    }
  }

      
  if (empty($error)) {

    $query_siswa = "SELECT nisn, nama_lengkap FROM user WHERE nisn='$nisn' LIMIT 1";
    $result_siswa = $connection->query($query_siswa);
    if ($result_siswa && $result_siswa->num_rows > 0) {
        $data_siswa = $result_siswa->fetch_assoc();
        $nama_siswa =  anti_injection($data_siswa['nama_lengkap']);
    }else{
      die("Data siswa tidak ditemukan, Cek kembali NISN siswa!");
    }

    $update="UPDATE wali_murid SET nama_lengkap='$nama_lengkap',
        email='$email',
        tempat_lahir = '$tempat_lahir',
        telp='$telp',
        nisn='$nisn',
        nama_siswa='$nama_siswa',
        alamat='$alamat' WHERE wali_murid_id='{$data_user['wali_murid_id']}'"; 
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