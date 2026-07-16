<?PHP use PHPMailer\PHPMailer\PHPMailer;
      use PHPMailer\PHPMailer\Exception;
require_once'../../sw-library/sw-config.php'; 
include_once'../../sw-library/sw-function.php';
$ip_login 		    = $_SERVER['REMOTE_ADDR'];
$created_login	 = date('Y-m-d H:i:s');
$iB 			       = getBrowser();
$browser 		     = $iB['name'].' '.$iB['version'];
$expired_cookie = time()+60*60*24*7;

switch (@$_GET['action']){
case 'login':
  $error = array();
  if (empty($_POST['username'])) { 
      $error[] = 'Username / Email tidak boleh kosong';
    } else { 
      $username = mysqli_real_escape_string($connection,$_POST['username']);
  }

  if (empty($_POST['password'])) { 
      $error[] = 'Password tidak boleh kosong';
    } else {
      $password_hash = mysqli_real_escape_string($connection,$_POST['password']);
  }

if (empty($error)){
  $time_online = time();
  if(filter_var($username, FILTER_VALIDATE_EMAIL)){
    $query_login ="SELECT admin_id,username,password,fullname,email,tanggal_login,active FROM admin WHERE email='$username'";
  }else{
    $query_login ="SELECT admin_id,username,password,fullname,email,tanggal_login,active FROM admin WHERE username='$username'";
  }
  $result_login       = $connection->query($query_login);

  if($result_login->num_rows > 0){
  	$row_user    = $result_login->fetch_assoc();
    if($row_user['active'] == 'Y'){
      $ADMIN_KEY   = htmlentities(epm_encode($row_user['admin_id']));
      $KEY                = hash('sha256',$row_user['username']);
      /* ---------- Update Status Online --------- */
      $update_admin = "UPDATE admin SET tanggal_login='$date $time', time='$date $time', status='Online' WHERE admin_id='$row_user[admin_id]'";
      $connection->query($update_admin);
    /* ---------- Update Status Online --------- */
		//verify password 
      if(password_verify($password_hash,$row_user['password'])) {
          setcookie('ADMIN_KEY', $ADMIN_KEY, $expired_cookie, '/');
          setcookie('KEY', $KEY, $expired_cookie, '/');
          echo'success';
      }else{
          echo "Username dan password yang Anda masukkan salah!";
      }
    } else{
      echo'Saat ini akun Anda belum aktif, silahkan hubungi Admin!';
    }
  }else {
    echo'Akun Anda Tidak ditemukan!';
  }

  }else{       
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }



/* ------------  FORGOT -------------*/
break;
case 'forgot':

require_once '../../sw-library/PHPMailer/Exception.php';
require_once '../../sw-library/PHPMailer/PHPMailer.php';
require_once '../../sw-library/PHPMailer/SMTP.php';

$error = array();
  if (empty($_POST['email'])) {
      $error[] = 'Email tidak boleh kosong';
    } else {
      $email= mysqli_real_escape_string($connection, $_POST['email']);
  }

  $password_baru = randomPassword();
  $password      = password_hash($password_baru,PASSWORD_DEFAULT);

  if (empty($error)) {
  if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $query="SELECT fullname,email FROM admin WHERE email='$email'";
  $result= $connection->query($query);
  if($result ->num_rows >0){
    $row = $result->fetch_assoc();
    
    // Konfigurasi SMTP
    $mail = new PHPMailer;
    $mail->isSMTP();
    $mail->Host = $gmail_host;
    $mail->Username = $gmail_username; // Email Pengirim
    $mail->Password = $gmail_password; // Isikan dengan Password email pengirim
    $mail->Port = $gmail_port;
    $mail->SMTPAuth = true;
    $mail->SMTPSecure = 'ssl';
    //$mail->SMTPDebug = 2; // Aktifkan untuk melakukan debugging

    $mail->setFrom($gmail_username, $site_name);  //Email Pengirim
    $mail->addAddress($email, strip_tags($row['fullname'])); // Email Penerima

    $mail->isHTML(true); // Aktifkan jika isi emailnya berupa html
   // Subjek email
    $mail->Subject = 'Resset password Baru | '.$site_name.'';

    $mailContent = '<h1>'.$site_name.'</h1><br>
        <h3>Halo, '.strip_tags($row['fullname']).'</h3><br>
        <p>Kamu baru saja mengirim permintaan reset password akun '.$site_name.'.<br>
        Password Baru Anda : <b>'.$password_baru.'</b><br><br><br>Harap simpan baik-baik akun Anda.<br><br>
        Hormat Kami,<br>'.$site_name.'<br>Email otomatis, Mohon tidak membalas email ini</p>';
    $mail->Body = $mailContent;
    $mail->AddEmbeddedImage('image/logo.png', ''.$site_name.'', '../sw-content/'.$site_logo.''); //Logo 

    $update="UPDATE admin SET password='$password' WHERE email='$email'"; 
    if($connection->query($update) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan.!';
    } else{
        echo'success';
        if($mail->send()){
          //echo 'Pesan telah terkirim';
        }else{
          echo 'Mailer Error: ' . $mail->ErrorInfo;
        }
    }}
    else   {
       echo'Untuk Email "'.$email.'" belum terdaftar, silahkan cek kembali.!';
    }}

    else {
     echo'Email yang Anda masukkan salah.!';
    }
  
  }else{           
      foreach ($error as $key => $values) {            
        echo"$values\n";
      }
  }
  break;
}