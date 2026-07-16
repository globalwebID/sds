<?php use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

$iB 			       = getBrowser();
$browser 		     = $iB['name'].' '.$iB['version'];

switch (@$_GET['action']){
/* ---------- ADD  ---------- */
case 'add':
$error = array();

$fields = [
  'nama_lengkap'  => 'Nama Lengkap tidak boleh kosong',
  'email'         => 'Email tidak boleh kosong',
  'password'      => 'Password tidak boleh kosong',
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
  $rfid = empty($_POST['rfid']) ? '' : anti_injection($_POST['rfid']);

  if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error[] = "Email yang Anda masukan tidak valid";
  }

  if (empty($_POST['telp'])) {
    $telp = NULL;
  }else{
    if (substr($_POST['telp'], 0, 2) !== '62') {
      die('No. Telp harus diawali dengan angka 62');
    }else{
      $telp = anti_injection($_POST['telp']);
    }
  }

  if (!empty($password)) {
    $password = password_hash($password, PASSWORD_DEFAULT);
  }

if (empty($error)) {

  $qrcode   = strtoupper(substr(hash('sha1', $nama_lengkap), 0, 10)); 
  $tempdir  = '../../../sw-content/qrcode/';
  $namafile = 'pegawai_'.seo_title($qrcode).'.jpg';

  $add = "INSERT INTO pegawai(
          nip,
          rfid,
          qrcode,
          nama_lengkap,
          email,
          password,
          tempat_lahir,
          tanggal_lahir,
          jenis_kelamin,
          jabatan,
          wali_kelas,
          lokasi,
          telp,
          alamat,
          avatar,
          tanggal_registrasi,
          tanggal_login,
          ip,
          browser) VALUES (
          '$nip',
          '$rfid',
          '$qrcode',
          '$nama_lengkap',
          '$email',
          '$password',
          '$tempat_lahir',
          '$tanggal_lahir',
          '$jenis_kelamin',
          '$jabatan',
          '$wali_kelas',
          '$lokasi',
          '$telp',
          '$alamat',
          'avatar.jpg',
          '$date $time',
          '$date $time',
          '$ip',
          '$browser')";
    if($connection->query($add) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        echo'success';
        /*if ($qrcode && !file_exists($tempdir . $namafile)) { 
            QRCode::png($qrcode, $tempdir . $namafile, 'QR_ECLEVEL_Q', 10, 1);
        }*/
    }
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}


break;
case 'update':
$error = array();
    
$fields = [
  'id'            => 'ID',
  'nama_lengkap'  => 'Nama',
  'email'         => 'Email',
  'tempat_lahir'  => 'Tempat Lahir',
  'tanggal_lahir' => 'Tanggal Lahir',
  'jenis_kelamin' => 'Jenis Kelamin',
  'jabatan'       => 'Jabatan',
  'wali_kelas'    => 'Wali Kelas',
  'lokasi'        => 'Lokasi Kerja',
  'alamat'        => 'Alamat',
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
        else {
            $$key = anti_injection($_POST[$key]);
        }
    }
  }

  $nip = empty($_POST['nip']) ? '' : anti_injection($_POST['nip']);
  $rfid = empty($_POST['rfid']) ? '' : anti_injection($_POST['rfid']);

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
          rfid = '$rfid',
          email = '$email',
          nama_lengkap = '$nama_lengkap',
          tempat_lahir = '$tempat_lahir',
          tanggal_lahir = '$tanggal_lahir',
          jenis_kelamin = '$jenis_kelamin',
          telp = '$telp',
          jabatan = '$jabatan',
          wali_kelas = '$wali_kelas',
          lokasi = '$lokasi',
          alamat = '$alamat' WHERE pegawai_id = '$id'";
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
require_once'../../../sw-library/PHPMailer/Exception.php';
require_once'../../../sw-library/PHPMailer/PHPMailer.php';
require_once'../../../sw-library/PHPMailer/SMTP.php';

$error = array();
  if (empty($_POST['id'])) {
      $error[] = 'ID tidak ditemukan, Silahkan coba kembali';
    } else {
      $id       = anti_injection(epm_decode($_POST['id']));
  }

  $password = '123456';
  $password = password_hash($password,PASSWORD_DEFAULT);
  
    $query_user="SELECT nama_lengkap,email FROM pegawai WHERE pegawai_id='$id'";
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
	            <b>Password Baru Anda : 123456</b><br>
	            IP : '.$ip.'<br>Browser : '.$browser.'<br><br><br><br>
	            Harap simpan baik-baik akun Anda.<br><br>
	            Hormat Kami,<br>'.$site_name.'<br>Email otomatis, Mohon tidak membalas email ini</p>';
	        $mail->Body = $mailContent;
	      }

  if(empty($error)){
      $update="UPDATE pegawai SET password='$password' WHERE pegawai_id='$id'";
        if($connection->query($update) === false) { 
          die($connection->error.__LINE__); 
          echo'Sepertinya Sistem Kami sedang error!';
        } else{
          echo'success';
          if($gmail_active =='Y'){
            if (!$mail->send()) {
              echo 'Gagal kirim email (hanya bisa online)';
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

break;
case'status':
$error = array();

  if (empty($_POST['status'])) {
    $error[] = 'Kelas harus dipilih';
  } else {
    $status = anti_injection($_POST['status']);
  }

  if (empty($_POST['id'])) {
    $error[] = 'Pegawai harus dipilih';
  } else {
    $idArr = $_POST['id'];
  }
  
if (empty($error)) {
  foreach($idArr as $id){ 
    $update="UPDATE pegawai SET active='$status' WHERE pegawai_id='$id'";
    $connection->query($update);
  }
  echo 'success';
}else{
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}


/** Reset qrcode */
break;
case 'reset-qrcode':
if(isset($_POST['id'])){
  $id       = anti_injection(epm_decode($_POST['id']));
  $query ="SELECT qrcode FROM pegawai where pegawai_id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    $qrcode = $row['qrcode'];
    if(empty($qrcode)){
      die('QRCODE Anda belum terisi. Mohon lengkapi data Anda untuk melanjutkan!');
    }
    $filepath = "../../../sw-content/qrcode/pegawai_$qrcode.png";
    if ($qrcode && file_exists($filepath??'-.jpg')) {
        unlink ('../../../sw-content/qrcode/pegawai_'.$row['qrcode'].'.png'); 
    }
    echo'success';
  }else{
    echo'Data tidak ditemukan!';
  }
}


break;
case 'delete':
if(isset($_POST['id'])){
  $id       = anti_injection(epm_decode($_POST['id']));
  $query ="SELECT qrcode,avatar FROM pegawai where pegawai_id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    if(file_exists('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').'') && $row['avatar'] !== 'avatar.jpg'){
      unlink ('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').''); 
    }

    if(file_exists('../../../sw-content/qrcode/'.strip_tags($row['qrcode']??'.avatar.jpg').'.png')){
      unlink ('../../../sw-content/qrcode/'.strip_tags($row['qrcode']??'-').''); 
    }
  }


    $deleted  = "DELETE FROM pegawai WHERE pegawai_id='$id'";
    if($connection->query($deleted) === true) {
      echo'success';

    } else { 
      echo'Data tidak berhasil dihapus.!';
      die($connection->error.__LINE__);
    }
}


/* ----- Import -------*/
break;
case 'import':
require '../../../sw-library/PhpSpreadsheet/autoload.php';

// Jika ada file yang di-upload
if (isset($_FILES['file_excel']['tmp_name'])) {
  $file = $_FILES['file_excel']['tmp_name'];

  $spreadsheet = IOFactory::load($file);
  $sheet = $spreadsheet->getActiveSheet();
  $data = $sheet->toArray(null, true, true, true);

  $password = '123456';
  $password = password_hash(trim($password ?? ''), PASSWORD_DEFAULT);

  foreach ($data as $i => $row) {
    if ($i == 5) continue; 


        $nip = $connection->real_escape_string((string)($row['A'] ?? ''));
        $rfid = $connection->real_escape_string((string)($row['B'] ?? ''));
        $nama_lengkap = $connection->real_escape_string((string)($row['C'] ?? ''));
        $qrcode = strtoupper(substr(hash('sha1', $nama_lengkap), 0, 10)); 

        $email = $connection->real_escape_string((string)($row['D'] ?? ''));
        $tempat_lahir = $connection->real_escape_string((string)($row['E'] ?? ''));

        $tanggal_raw = $row['F'] ?? '';
        $tanggal_lahir = $tanggal_raw ? $connection->real_escape_string(date('Y-m-d', strtotime($tanggal_raw))) : null;

        $jenis_kelamin = $connection->real_escape_string((string)($row['G'] ?? ''));
        $jabatan = $connection->real_escape_string((string)($row['H'] ?? ''));
        $wali_kelas = $connection->real_escape_string((string)($row['I'] ?? ''));

        $lokasi_raw = $row['J'] ?? '';
        $lokasi = is_numeric($lokasi_raw) ? (int)$lokasi_raw : 0;

        $alamat = $connection->real_escape_string((string)($row['K'] ?? ''));
        $telp = $connection->real_escape_string((string)($row['L'] ?? ''));
       

        if (empty($email) || empty($nama_lengkap)) {
            continue; 
        }

        // Cek jika data sudah ada berdasarkan NISN dan Email
        $sql_check = "SELECT * FROM pegawai WHERE email='$email'";
        $result = $connection->query($sql_check);
        if ($result->num_rows > 0) {
            // Jika data sudah ada, update
            $sql_update = "UPDATE pegawai SET nip = '$nip',
                  rfid = '$rfid',
                  email = '$email',
                  nama_lengkap = '$nama_lengkap',
                  tempat_lahir = '$tempat_lahir',
                  tanggal_lahir = '$tanggal_lahir',
                  jenis_kelamin = '$jenis_kelamin',
                  jabatan = '$jabatan',
                  wali_kelas = '$wali_kelas',
                  lokasi = '$lokasi',
                  telp = '$telp',
                  alamat = '$alamat' WHERE email = '$email'";
            if ($connection->query($sql_update) === TRUE) {
                //echo "Data berhasil diupdate untuk $nisn";
            } else {
                echo "Error updating record: " . $connection->error . "<br>";
            }
        } else {
            if($nama_lengkap == ""){

            }else{
            // Jika data belum ada, insert
            $sql_insert = "INSERT INTO pegawai ( nip,
                  rfid,
                  qrcode,
                  nama_lengkap,
                  email,
                  password,
                  tempat_lahir,
                  tanggal_lahir,
                  jenis_kelamin,
                  jabatan,
                  wali_kelas,
                  lokasi,
                  telp,
                  alamat,
                  avatar,
                  tanggal_registrasi,
                  tanggal_login,
                  ip,
                  browser) VALUES (
                  '$nip',
                  '$rfid',
                  '$qrcode',
                  '$nama_lengkap',
                  '$email',
                  '$password',
                  '$tempat_lahir',
                  '$tanggal_lahir',
                  '$jenis_kelamin',
                  '$jabatan',
                  '$wali_kelas',
                  '$lokasi',
                  '$telp',
                  '$alamat',
                  'avatar.jpg',
                  '$date $time',
                  '$date $time',
                  '$ip',
                  '$browser')";
            if ($connection->query($sql_insert) === TRUE) {
                //echo "Data berhasil diimport";
            } else {
                echo "Error inserting record: " . $connection->error . "<br>";
            }
          }
        }
    }
    echo 'success';
} else {
    echo 'No file uploaded';
}
$connection->close();

break;
}}