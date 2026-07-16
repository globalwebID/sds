<?php use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';
require_once '../../../sw-library/PHPMailer/Exception.php';
require_once '../../../sw-library/PHPMailer/PHPMailer.php';
require_once '../../../sw-library/PHPMailer/SMTP.php';


$max_size = 3000000; //2MB
$allowed_ext = array('jpg','jpeg','gif', 'png');
$iB 			       = getBrowser();
$browser 		     = $iB['name'].' '.$iB['version'];
$ip 		         = $_SERVER['REMOTE_ADDR'];

switch (@$_GET['action']){
case 'load-data':
$query_user ="SELECT * FROM admin WHERE admin.admin_id='$current_user[admin_id]'";
$result_user = $connection->query($query_user);
if($result_user->num_rows > 0){
  $data_user  = $result_user->fetch_assoc();

if(htmlentities($_GET['id']== 1)){
echo'
<div class="form-group">
  <h4>PROFIL '.strip_tags(strtoupper($data_user['fullname'])).'</h4>
</div>

<form class="form-update" role="form" method="post" action="javascript:void(0);" autocomplete="off">
    <div class="form-group">
    <label>Nama lengkap</label>
        <input type="text" class="form-control" name="fullname" value="'.strip_tags($data_user['fullname']).'" required>
    </div>

<div class="form-group">
    <label>No. Telp</label>
        <input type="number" class="form-control" name="phone" value="'.strip_tags($data_user['phone']).'" required>
</div>

<div class="form-group">
    <label>Email</label>
        <input type="email" class="form-control" name="email" value="'.strip_tags($data_user['email']).'" required>
</div>

<div class="form-group">
    <label>Username</label>
        <input type="text" class="form-control password" name="username" value="'.strip_tags($data_user['username']).'" required>
</div>
<hr>
  <div class="form-group">
    <button class="btn btn-primary btn-save submitBtn"><i class="far fa-save"></i> Simpan</button>
  </div>
</form>';

}elseif(htmlentities($_GET['id']== 2)){
  echo'
  <div class="form-group">
    <h4>RESET PASSWORD</h4>
  </div>

      <form class="form-password" role="form" action="javascript:void(0);" autocomplete="off">
        <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control email" name="email" value="'.strip_tags($data_user['email']).'" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <div class="input-group input-group-merge">
              <input type="password" class="form-control password" id="password-field"  name="password" required>
              <div class="input-group-append">
                <span class="input-group-text"><span toggle="#password-field" class="fas fa-eye toggle-password"></span></span>
              </div>
            </div>
        </div>

      <hr>
        <div class="form-group">
          <button class="btn btn-primary btn-save submitForgot"><i class="far fa-save"></i> Simpan</button>
        </div>
      </form>';
  }

}

/* -------------- Update ----------*/
break;
case 'update':
  $error = array();
    if (empty($_POST['fullname'])) {
      $error[] = 'Nama Lengkap tidak boleh kosong';
    } else {
      $fullname = anti_injection($_POST['fullname']);
  }

  if (empty($_POST['username'])) {
    $error[] = 'Username tidak boleh kosong';
  } else {
    $username = anti_injection($_POST['username']);
  }

  if (empty($_POST['phone'])) {
    $error[] = 'No. email tidak boleh kosong';
  } else {
    $phone = anti_injection($_POST['phone']);
  }

    if (empty($_POST['email'])) {
      $error[] = 'Email tidak boleh kosong';
    } else {
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
          $error[] = "Email yang Anda masukan tidak valid"; 
        }else{
          $email = htmlentities(strip_tags($_POST['email']));
        }
    }


  if (empty($error)) {
    $update="UPDATE admin SET fullname='$fullname',
            username='$username',
            phone='$phone',
            email='$email' WHERE admin_id='$current_user[admin_id]'"; 
    if($connection->query($update) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        echo'success';
    }}
  else{
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }


/* ----------------- Forgot/Resset Password -----------*/
break;
case 'forgot':
$error = array();

  if (empty($_POST['password'])) {
    $error[] = 'Password tidak boleh kosong';
  } else {
    $password = htmlentities(strip_tags($_POST['password']));
    $password = password_hash($password,PASSWORD_DEFAULT);
  }

  if(empty($error)){
    $query_user="SELECT fullname,email,username from admin WHERE admin_id='$current_user[admin_id]'";
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

    $update="UPDATE admin SET password='$password' WHERE admin_id='$current_user[admin_id]'";
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


/** Avatar */
break;
case 'avatar':
  $error = array();
    function resizeImage($resourceType,$image_width,$image_height){
      $resizeWidth = 500;
      $resizeHeight = ($image_height/$image_width)*$resizeWidth;
      $imageLayer = imagecreatetruecolor($resizeWidth,$resizeHeight);
      imagecopyresampled($imageLayer,$resourceType,0,0,0,0,$resizeWidth,$resizeHeight, $image_width,$image_height);
      return $imageLayer;
    }

    if (empty($_FILES['avatar']['name'])){
      $error[]    = 'Foto belum di unggah.!';
    } else {
      $file_name        = $_FILES['avatar']['name'];
      $fileExt          = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
      $file_size        = $_FILES['avatar']['size'];
      $file_tmp         = $_FILES['avatar']['tmp_name'];
    }
  if (empty($error)) { 
    if(in_array($fileExt, $allowed_ext) === true){
      if ($file_size <= $max_size) {

    $sourceProperties = getimagesize($file_tmp);
    $uploadImageType  = $sourceProperties[2];
    $sourceImageWidth = $sourceProperties[0];
    $sourceImageHeight = $sourceProperties[1];

    $resizeFileName   = 'avatar-'.$current_user['username'].'-'.time().'';
    $uploadPath       = '../../sw-assets/avatar/';
    $foto            = ''.$resizeFileName.'.'.$fileExt.'';

      $query = "SELECT avatar FROM admin WHERE admin_id='$current_user[admin_id]'"; 
      $result = $connection->query($query);
      $rows= $result->fetch_assoc();
      $avatar = $rows['avatar'];
          if(file_exists("../../sw-assets/avatar/$avatar")){
            if($avatar == 'avatar.jpg'){
              //Jika avatar.kpg makan tidak hapus file
            }else{
              unlink( "../../sw-assets/avatar/$avatar");
            }
          }
          $update ="UPDATE admin SET avatar='$foto' WHERE admin_id='$current_user[admin_id]'";
          if($connection->query($update) === false) { 
              die($connection->error.__LINE__); 
              echo'Sepertinya Sistem Kami sedang error!';
          } else{
              echo'success';
              
              switch ($uploadImageType) {
              case IMAGETYPE_JPEG:
                  $resourceType = imagecreatefromjpeg($file_tmp); 
                  $imageLayer = resizeImage($resourceType,$sourceImageWidth,$sourceImageHeight);
                  imagejpeg($imageLayer,$uploadPath."".$resizeFileName.'.'. $fileExt);
                  break;
      
              case IMAGETYPE_GIF:
                  $resourceType = imagecreatefromgif($file_tmp); 
                  $imageLayer = resizeImage($resourceType,$sourceImageWidth,$sourceImageHeight);
                  imagegif($imageLayer,$uploadPath."".$resizeFileName.'.'. $fileExt);
                  break;
      
              case IMAGETYPE_PNG:
                  $resourceType = imagecreatefrompng($file_tmp); 
                  $imageLayer = resizeImage($resourceType,$sourceImageWidth,$sourceImageHeight);
                  imagepng($imageLayer,$uploadPath."".$resizeFileName.'.'. $fileExt);
                  break;
      
              default:
                  $imageProcess = 0;
              break;
            }
          }

         }else{
          echo 'Foto terlalu besar Maksimal Size 5MB.!';
        }
        }
        else{
          echo'Gambar/Foto yang di unggah tidak sesuai dengan format, Berkas harus berformat JPG,JPEG,GIF..!';
        }
      }else{
        foreach ($error as $key => $values) {            
          echo $values;
        }
      }
}}