<?php use PHPMailer\PHPMailer\PHPMailer;
      use PHPMailer\PHPMailer\Exception;
require_once'../../sw-library/sw-config.php';
require_once'../../sw-library/sw-function.php';
require_once '../../sw-library/PHPMailer/Exception.php';
require_once '../../sw-library/PHPMailer/PHPMailer.php';
require_once '../../sw-library/PHPMailer/SMTP.php';

  $iB 			       = getBrowser();
  $browser 		     = $iB['name'].' '.$iB['version'];
  $ip 		         = $_SERVER['REMOTE_ADDR'];
  $time_online     = time();
  
  switch (@$_GET['action']){
    case 'forgot':
      $error = array();

      if (empty($_POST['email'])) {
          $error[] = 'Email tidak boleh kosong!';
        } else {
          $email       = htmlentities($_POST['email']);
      }

      if (empty($_POST['tipe'])) {
        $error[] = 'Silahkan pilih Level!';
      } else {
        $tipe = htmlentities($_POST['tipe']);
    }
    
      $password_baru = randomPassword();
      $password = password_hash($password_baru,PASSWORD_DEFAULT);
      if(empty($error)){

      if($tipe=='siswa'){
          $query_user="SELECT user_id,nama_lengkap,email from user WHERE email='$email'";
          $result_user= $connection->query($query_user);
          if($result_user ->num_rows >0){
            $row_user = $result_user->fetch_assoc();

            if($gmail_active =='Y'){
              $mail = new PHPMailer;
              $mail->isSMTP();
              $mail->Host = $gmail_host;
              $mail->Username = $gmail_username; // Email Pengirim
              $mail->Password = $gmail_password; // Isikan dengan Password email pengirim
              $mail->Port = $gmail_port;
              $mail->SMTPAuth = true;
              $mail->SMTPSecure = 'ssl';
              $mail->Port = 587; 
              //$mail->SMTPDebug = 2; // Aktifkan untuk melakukan debugging
    
              $mail->setFrom($gmail_username, $site_name);  //Email Pengirim
              $mail->addAddress($row_user['email'], $row_user['nama_lengkap']); // Email Penerima
    
              $mail->isHTML(true); // Aktifkan jika isi emailnya berupa html
              // Subjek email
              $mail->Subject = 'Resset password Baru | '.$site_name.'';
    
              $mailContent = '<h1>'.$site_name.'</h1><br>
                  <h3>Halo, '.$row_user['nama_lengkap'].'</h3><br>
                  <p>Selamat akun anda berahsil kami reset ulang, silahkan login dengan password baru dibawah ini<br>
                  Email : '.$row_user['email'].'
                  <b>Password Baru Anda : '.$password_baru.'</b><br>
                  IP : '.$ip.'<br>Browser : '.$browser.'<br><br><br><br>
                  Harap simpan baik-baik akun Anda.<br><br>
                  Hormat Kami,<br>'.$site_name.'<br>Email otomatis, Mohon tidak membalas email ini</p>';
              $mail->Body = $mailContent;
            }
              
              $update="UPDATE user SET password='$password' WHERE user_id='$row_user[user_id]'";
              
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

        }else if($tipe=='wali'){
          /** Wali Murid */
        $query_user="SELECT orangtua_id,nama_lengkap,email from orangtua WHERE email='$email'";
        $result_user= $connection->query($query_user);
        if($result_user ->num_rows >0){
          $row_user = $result_user->fetch_assoc();
            
          if($gmail_active =='Y'){
            $mail = new PHPMailer;
            $mail->isSMTP();
            $mail->Host = $gmail_host;
            $mail->Username = $gmail_username; // Email Pengirim
            $mail->Password = $gmail_password; // Isikan dengan Password email pengirim
            $mail->Port = $gmail_port;
            $mail->SMTPAuth = true;
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 587; 
            //$mail->SMTPDebug = 2; // Aktifkan untuk melakukan debugging
  
            $mail->setFrom($gmail_username, $site_name);  //Email Pengirim
            $mail->addAddress($row_user['email'], $row_user['nama_lengkap']); // Email Penerima
  
            $mail->isHTML(true); // Aktifkan jika isi emailnya berupa html
            // Subjek email
            $mail->Subject = 'Resset password Baru | '.$site_name.'';
  
            $mailContent = '<h1>'.$site_name.'</h1><br>
                <h3>Halo, '.$row_user['nama_lengkap'].'</h3><br>
                <p>Selamat akun anda berahsil kami reset ulang, silahkan login dengan password baru dibawah ini<br>
                Email : '.$row_user['email'].'
                <b>Password Baru Anda : '.$password_baru.'</b><br>
                IP : '.$ip.'<br>Browser : '.$browser.'<br><br><br><br>
                Harap simpan baik-baik akun Anda.<br><br>
                Hormat Kami,<br>'.$site_name.'<br>Email otomatis, Mohon tidak membalas email ini</p>';
            $mail->Body = $mailContent;
          }
        
        
            $update="UPDATE orangtua SET password='$password' WHERE orangtua_id='$row_user[orangtua_id]'";
            
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
          echo'Silahkan Pilih Level!';
        }

    }else{           
        foreach ($error as $key => $values) {            
          echo"$values\n";
        }
        }
     
break;
}