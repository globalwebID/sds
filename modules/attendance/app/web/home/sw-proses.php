<?php
require_once'../../sw-library/sw-config.php';
require_once'../../sw-library/sw-function.php';
$expired_cookie = time() + 60 * 60 * 24 * 60;

switch (@$_GET['action']){
case 'login':
  $error = array();

  $fields = [
    'email'     => 'Email',
    'password'  => 'Password',
    'tipe'      => 'Level Login'
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {

        if ($key === 'password') {
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
        else {
            $$key = anti_injection($_POST[$key]);
        }
    }
  }

if (empty($error)){

  if($tipe=='siswa'){
      $query  ="SELECT user_id,email,nama_lengkap,password FROM user WHERE email='$email' LIMIT 1";
      $result = $connection->query($query);
      if($result->num_rows > 0){
        $data_user  = $result->fetch_assoc();
          $user_id_encrypted = convert("encrypt", strip_tags($data_user['user_id']));

          $update= "UPDATE user SET tanggal_login='$date $time', status='Online' WHERE user_id='$data_user[user_id]'";
          $connection->query($update);
          if(password_verify($password,$data_user['password'])) {
            setcookie('siswa', $user_id_encrypted, $expired_cookie, '/');
            echo'siswa';
          }else{
            echo'Login Gagal karena Password Salah!';
          }
      }else {
        echo'Akun dengan email yang Anda masukkan tidak ditemukan. Pastikan email yang Anda gunakan benar!';
      }

    $connection->close();

  }elseif($tipe =='pegawai'){
      $query  = "SELECT pegawai_id,email,nama_lengkap,password FROM pegawai WHERE email='$email'";
      $result = $connection->query($query);
      if($result->num_rows > 0){
        $data_user  = $result->fetch_assoc();
        $user_id_encrypted = convert("encrypt", strip_tags($data_user['pegawai_id']));

        $update_user = "UPDATE pegawai SET tanggal_login='$date $time', status='Online' WHERE pegawai_id='$data_user[pegawai_id]'";
        $connection->query($update_user);

        if(password_verify($password,$data_user['password'])) {
            setcookie('pegawai', $user_id_encrypted, $expired_cookie, '/');
            echo'pegawai';
        }else{
          echo'Login Gagal karena Password Salah!!';
        }

      }else {
       echo'Akun dengan email yang Anda masukkan tidak ditemukan. Pastikan email yang Anda gunakan benar!';
      }


    }elseif($tipe =='wali-murid'){
        $query  = "SELECT wali_murid_id,email,nama_lengkap,password FROM wali_murid WHERE email='$email'";
        $result = $connection->query($query);
        if($result->num_rows > 0){
          $data_user    = $result->fetch_assoc();
          $user_id_encrypted = convert("encrypt", strip_tags($data_user['wali_murid_id']));
        
          $update_user = "UPDATE wali_murid SET tanggal_login='$date $time', status='Online' WHERE wali_murid_id='$data_user[wali_murid_id]'";
          $connection->query($update_user);

          if(password_verify($password,$data_user['password'])) {
            setcookie('wali_murid', $user_id_encrypted, $expired_cookie, '/');
            echo'wali-murid';
          }else{
            echo "Ppassword yang Anda masukkan salah!";
          }

        }else {
          echo'Akun Anda Tidak ditemukan!';
        }
    }else{
      echo'Silahkan pilih level Login!';
    }

  }else{       
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
 }
 
break;
}?>