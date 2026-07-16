<?php use PHPMailer\PHPMailer\PHPMailer;
      use PHPMailer\PHPMailer\Exception;
require_once'../../sw-library/sw-config.php';
require_once'../../sw-library/sw-function.php';

$expired_cookie = time() + 60 * 60 * 24 * 30;
$iB 			       = getBrowser();
$browser 		     = $iB['name'].' '.$iB['version'];

switch (@$_GET['action']){
case 'login':
  $error = array();
  if (empty($_POST['username'])) { 
      $error[] = 'Username / Email tidak boleh kosong';
    } else { 
      if(filter_var($_POST['username'], FILTER_VALIDATE_EMAIL)){
        $username = anti_injection($_POST['username']);
      }else{
        $error[] = 'Email yang Anda masukkan tidak benar';
      }
      
  }

  if (empty($_POST['password'])) { 
      $error[] = 'Password tidak boleh kosong';
    } else {
      $password_hash = htmlentities(htmlspecialchars($_POST['password']));
  }

if (empty($error)){
  $time_online = time();
  function getUserByUsername($username, $connection) {
    $query  = "SELECT user_id,email,nama_lengkap,password,tanggal_login,active FROM user WHERE email='$username'";
    $result = $connection->query($query);
    return $result->num_rows > 0 ? $result->fetch_assoc() : null;    
  }

  function countPerangkat($userId, $connection) {
    $query  = "SELECT COUNT(user_id) as total FROM perangkat WHERE user_id =$userId";
    $result = $connection->query($query);
    return $result->fetch_assoc()['total'];
  }

  function addPerangkat($userId, $device_name, $ip, $browser, $date, $connection) {
      $query = "INSERT INTO perangkat (user_id, device_name, ip_address, browser, tanggal) VALUES ('$userId',
      '$device_name', '$ip', '$browser', '$date')";
      return $connection->query($query);
  }

  $user = getUserByUsername($username, $connection);
  if (!$user) {
    echo'Akun Anda Tidak ditemukan!';
    exit;
  }

  if ($user['active'] === 'N') {
    echo'Saat ini akun Anda belum aktif, silahkan hubungi Admin!';
    exit;
  }

  if (password_verify($password_hash, $user['password'])) {
    $sessionCount = countPerangkat($user['user_id'], $connection);
    $USER_KEY   = convert("encrypt", htmlspecialchars($user['user_id']));
    $TOKEN_KEY  = convert("encrypt", htmlspecialchars($user['email']));

    if ($sessionCount >= 2) {
        echo "Login ditolak! Hanya 2 perangkat yang diizinkan.";
      } else {
          addPerangkat($user['user_id'], $device_name, $ip, $browser, $date, $connection);
          setcookie('USER_KEY', $USER_KEY, $expired_cookie, '/');
          setcookie('TOKEN_KEY', $USER_KEY, $expired_cookie, '/');
          echo'success';
      }
  } else {
    echo'Email/Password yang Anda masukkan salah!';
  }

}else{       
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}
 


/* ----------------- Forgot/Resset Password -----------*/
break;
case 'forgot':
require_once '../../sw-library/PHPMailer/Exception.php';
require_once '../../sw-library/PHPMailer/PHPMailer.php';
require_once '../../sw-library/PHPMailer/SMTP.php';

$error = array();
  if (empty($_POST['id'])) {
      $error[] = 'ID tidak ditemukan, Silahkan coba kembali';
    } else {
      $id       = anti_injection(epm_decode($_POST['id']));
  }

  $password = '123456';
  $password = password_hash($password,PASSWORD_DEFAULT);
  
  if(empty($error)){
    $query_user="SELECT fullname,email,username from admin WHERE admin_id='$id'";
		  $result_user= $connection->query($query_user);
		  if($result_user ->num_rows >0){
		    $row_user = $result_user->fetch_assoc();
        // Konfigurasi SMTP
      if($gmail_active =='Y'){
        $mail = new PHPMailer;
        $mail->isSMTP();
        $mail->Host = $gmail_host;
        $mail->Username = $gmail_username; // Email Pengirim
        $mail->Password = $gmail_password; // Isikan dengan Password email pengirim
        $mail->Port = $gmail_port;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = 'ssl';
        $mail->SMTPDebug = 2; // Aktifkan untuk melakukan debugging

        $mail->setFrom($gmail_username, $site_name);  //Email Pengirim
        $mail->addAddress($row_user['email'], $row_user['fullname']); // Email Penerima

        $mail->isHTML(true); // Aktifkan jika isi emailnya berupa html
        // Subjek email
        $mail->Subject = 'Resset password Baru | '.$site_name.'';

        $mailContent = '<h1>'.$site_name.'</h1><br>
            <h3>Halo, '.$row_user['fullname'].'</h3><br>
            <p>Selamat akun anda berahsil kami reset ulang, silahkan login dengan password baru<br>
            Username : '.$row_user['username'].'<br>
            Email : '.$row_user['email'].'<br><b>Password Baru Anda : 123456</b><br>IP : '.$ip.'<br>Browser : '.$browser.'<br><br><br><br>
            Harap simpan baik-baik akun Anda.<br><br>
            Hormat Kami,<br>'.$site_name.'<br>Email otomatis, Mohon tidak membalas email ini</p>';
        $mail->Body = $mailContent;
        $mail->AddEmbeddedImage('image/logo.png', ''.$site_name.'', '../../../sw-content/'.$site_logo.''); //Logo 
      }

        $update="UPDATE admin SET password='$password' WHERE admin_id='$id'";
        if($connection->query($update) === false) { 
          die($connection->error.__LINE__); 
          echo'Sepertinya Sistem Kami sedang error!';
        } else{
          echo'success';
          if($gmail_active =='Y'){
            if($mail->send()){
              //echo 'Pesan telah terkirim';
            }else{
              echo 'Mailer Error: Send email hanya bekerja saat online' . $mail->ErrorInfo;
            }
          }
        }
      }else{
        echo'Akun Anda tidak ditemukan, silahkan cek kembali.!';
      }
    }else{           
        foreach ($error as $key => $values) {            
          echo $values;
        }
    }


/** Baca notifikasi */
break; 
case'notifikasi':
  $error = array();
  if (empty($_POST['id'])) {
    $error[] = 'Nama tidak boleh kosong';
  } else {
    $id = anti_injection($_POST['id']);
  }
  if (empty($error)) {
  $update="UPDATE notifikasi SET status='Y' WHERE notifikasi_id='$id' AND user_id='$data_user[user_id]'"; 
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
}