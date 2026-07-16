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

$iB 			       = getBrowser();
$browser 		     = $iB['name'].' '.$iB['version'];
$ip 		         = $_SERVER['REMOTE_ADDR'];
$time_online     = time();


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
case 'add':
$error = array();

  $fields = [
    'nama_lengkap'   => 'Nama Lengkap',
    'email'          => 'Email',
    'password'       => 'Password',
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
        // khusus nama_lengkap -> real_escape_string
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
        // default untuk field lainnya
        else {
            $$key = anti_injection($_POST[$key]);
        }
    }
  }

  if (empty($error)){
      $query_siswa = "SELECT nisn, nama_lengkap FROM user WHERE nisn='$nisn' LIMIT 1";
      $result_siswa = $connection->query($query_siswa);
      if ($result_siswa && $result_siswa->num_rows > 0) {
          $data_siswa = $result_siswa->fetch_assoc();
          $nama_siswa =  anti_injection($data_siswa['nama_lengkap']);
      }else{
        die("Data siswa tidak ditemukan, Cek kembali NISN siswa!");
      }

      $add ="INSERT INTO wali_murid(nama_lengkap,
            email,
            password,
            tempat_lahir,
            jenis_kelamin,
            telp,
            nisn,
            nama_siswa,
            alamat,
            tanggal_registrasi,
            tanggal_login,
            ip,
            browser,
            status) values('$nama_lengkap',
            '$email',
            '$password',
            '$tempat_lahir',
            '$jenis_kelamin',
            '$telp',
            '$nisn',
            '$nama_siswa',
            '$alamat',
            '$date $time',
            '$date $time',
            '$ip',
            '$browser',
            'Offline')";
      if($connection->query($add) === false) { 
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


/* -------------- Update ----------*/
break;
case 'update':
$error = array();

  $fields = [
    'id'   => 'ID',
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

        if ($key === 'id') {
            $$key = anti_injection(epm_decode($_POST[$key]));
        }
        elseif ($key === 'nama_lengkap') {
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
        alamat='$alamat' WHERE wali_murid_id='$id'"; 
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
case 'forgot':
require_once '../../../sw-library/PHPMailer/Exception.php';
require_once '../../../sw-library/PHPMailer/PHPMailer.php';
require_once '../../../sw-library/PHPMailer/SMTP.php';
$error = array();

  if (empty($_POST['id'])) {
      $error[] = 'ID tidak ditemukan, Silahkan coba kembali';
    } else {
      $id       = anti_injection(epm_decode($_POST['id']));
  }

  $password = '123456';
  $password = password_hash($password,PASSWORD_DEFAULT);
  
  $query_user="SELECT nama_lengkap,email  FROM wali_murid WHERE wali_murid_id='$id'";
  $result_user= $connection->query($query_user);
  if($result_user ->num_rows >0){
		$row_user = $result_user->fetch_assoc();
        
      // Konfigurasi SMTP
	    if($gmail_active =='Y'){
	        $mail = new PHPMailer;
	        $mail->isSMTP();
	        $mail->Host = $gmail_host;
	        $mail->Username = $gmail_username;
	        $mail->Password = $gmail_password;
	        $mail->Port = $gmail_port;
	        $mail->SMTPAuth = true;
	        $mail->SMTPSecure = 'ssl';
	        $mail->setFrom($gmail_username, $site_name);  //Email Pengirim
	        $mail->addAddress($row_user['email'], $row_user['nama_lengkap']);
	        $mail->isHTML(true);

	        // Subjek email
	        $mail->Subject = 'Resset password Baru | '.$site_name.'';
	        $mailContent = '<h1>'.$site_name.'</h1><br>
	            <h3>Halo, '.$row_user['nama_lengkap'].'</h3><br>
	            <p>Selamat akun anda berahsil kami reset ulang, silahkan login dengan password baru dibawah ini<br>
	            Email : '.$row_user['email'].'
	            <b>Password Baru : 123456</b><br>
	            IP : '.$ip.'<br>Browser : '.$browser.'<br><br><br><br>
	            Harap simpan baik-baik akun Anda.<br><br>
	            Hormat Kami,<br>'.$site_name.'<br>Email otomatis, Mohon tidak membalas email ini</p>';
	        $mail->Body = $mailContent;
	    }

  if(empty($error)){
      $update="UPDATE wali_murid SET password='$password' WHERE wali_murid_id='$id'";
        if($connection->query($update) === false) { 
          die($connection->error.__LINE__); 
          echo'Sepertinya Sistem Kami sedang error!';
        } else{
          echo'success';
          if($gmail_active =='Y'){
            if($mail->send()){
              //echo 'Pesan telah terkirim';
            }else{
              echo 'Mailer Error: Send email hanya bekerja saat online';
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



/* --------------- Delete ------------*/
break;
case 'delete':
$id       = anti_injection(epm_decode($_POST['id']));
$query ="SELECT avatar FROM wali_murid WHERE wali_murid_id='$id'";
$result = $connection->query($query);
if($result->num_rows > 0){
  $row = $result->fetch_assoc();
  if(file_exists('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').'') && $row['avatar'] !== 'avatar.jpg'){
    unlink ('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').''); 
  }
}
$deleted  = "DELETE FROM wali_murid WHERE wali_murid_id='$id'";
if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    //tidak berhasil
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
}

/* ----- Import -------*/
break;
case 'import':
// Allowed mime types
$csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 'text/plain');

if(!empty($_FILES['files']['name']) && in_array($_FILES['files']['type'], $csvMimes)){
        // If the file is uploaded
        if(is_uploaded_file($_FILES['files']['tmp_name'])){
            // Open uploaded CSV file with read-only mode
            $csvFile = fopen($_FILES['files']['tmp_name'], 'r');
    
            // Skip the first line
            fgetcsv($csvFile);
            
            // Parse data from CSV file line by line
            while(($line = fgetcsv($csvFile)) !== FALSE){
                // Get row data -------- //
                $nama_lengkap     = mysqli_real_escape_string($connection,$line[0]);
                $email            = anti_injection($line[2]);
                $username         = anti_injection($line[3]);
                $password         = anti_injection($line[4]);
                $password         = password_hash($password,PASSWORD_DEFAULT);
                $tempat_lahir     = anti_injection($line[5]);
                $tanggal_lahir    = date('Y-m-d', strtotime($line[6]));
                $jenis_kelamin    = anti_injection($line[7]);
                $telp             = anti_injection($line[8]);
                $alamat           = anti_injection($line[9]);

              // Check berdasa  ID
              $query  = "SELECT wali_murid_id FROM wali_murid WHERE email='$email' AND username='$username'";
              $result = $connection->query($query);
              if($result->num_rows > 0){
                  $row = $result->fetch_assoc();
                // Update member data in the database
                $update="UPDATE wali_murid SET 
                        nama_lengkap='$nama_lengkap',
                        email='$email',
                        tempat_lahir='$tempat_lahir',
                        tanggal_lahir='$tanggal_lahir',
                        jenis_kelamin='$jenis_kelamin',
                        telp='$telp',
                        alamat='$alamat', WHERE email='$email'"; 
                }else{
                  
                // Insert  data in the database
                  $add ="INSERT INTO wali_murid(
                          nama_lengkap,
                          email,
                          username,
                          password,
                          tempat_lahir,
                          tanggal_lahir,
                          jenis_kelamin,
                          telp,
                          kelas,
                          user_id,
                          alamat,
                          avatar,
                          tanggal_registrasi,
                          tanggal_login,
                          ip,
                          browser,
                          status) values('$nama_lengkap',
                            '$email',
                            '$username',
                            '$password',
                            '$tempat_lahir',
                            '$tanggal_lahir',
                            '$jenis_kelamin',
                            '$telp',
                            '0', /** Kelas */
                            '0', /** Siswa */
                            '$alamat',
                            '$date $time',
                            '$date $time',
                            '$ip',
                            '$browser',
                            'Offline')";
                    if($connection->query($add) === false) {
                        echo'Data Wali Tidak dapat di Import!';
                        die($connection->error.__LINE__); 
                    }else{
                        //echo'success';
                    }
                }
            }
            
            // Close opened CSV file
            fclose($csvFile);
           echo'success';
        }else{
            echo'Data Wali tidak berhasil di import!';
        }
    }else{
          echo'File tidak sesuai format, Upload file CSV.!';

    }

break;
}}