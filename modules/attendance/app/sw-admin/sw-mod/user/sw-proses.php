<?php use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

if(!isset($_COOKIE['ADMIN_KEY'])){
  header('location:./login/');
  exit;
} else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

$iB 			       = getBrowser();
$browser 		     = $iB['name'].' '.$iB['version'];

switch (@$_GET['action']){
case 'add':
$error = array();

 $fields = [
    'nisn' => 'NISN tidak boleh kosong',
    'nama_lengkap' => 'Nama Lengkap tidak boleh kosong',
    'email' => 'Email tidak boleh kosong',
    'password' => 'Password tidak boleh kosong',
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

  if (!empty($password)) {
    $password = password_hash($password, PASSWORD_DEFAULT);
  }

  
  if (empty($error)) {
      $add ="INSERT INTO user(
        nisn,
        rfid,
        email,
        password,
        nama_lengkap,
        tempat_lahir,
        tanggal_lahir,
        jenis_kelamin,
        kelas,
        tahun_ajaran,
        lokasi,
        alamat,
        telp,
        tanggal_registrasi,
        tanggal_login,
        ip,
        browser,
        status) values(
        '$nisn',
        '$rfid',
        '$email',
        '$password',
        '$nama_lengkap',
        '$tempat_lahir',
        '$tanggal_lahir',
        '$jenis_kelamin',
        '$kelas',
        '$tahun_ajaran',
        '$lokasi',
        '$alamat',
        '$telp',
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

  if (empty($_POST['id'])) {
    $error[] = 'ID tidak ditemukan';
  } else {
    $id = anti_injection(epm_decode($_POST['id'],ENT_QUOTES, 'UTF-8'));
  }

 $fields = [
    'nisn' => 'NISN tidak boleh kosong',
    'nama_lengkap' => 'Nama Lengkap tidak boleh kosong',
    'email' => 'Email tidak boleh kosong',
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

  if (empty($error)) {
    $update="UPDATE user SET rfid='$rfid',
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
        telp='$telp' WHERE user_id='$id'";
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


/* Forgot */
break;
case 'forgot':
require_once'../../../sw-library/PHPMailer/Exception.php';
require_once'../../../sw-library/PHPMailer/PHPMailer.php';
require_once'../../../sw-library/PHPMailer/SMTP.php';

$error = array();

if (empty($_POST['id'])) {
  $error[] = 'ID tidak ditemukan, Silahkan coba kembali';
} else {
  $id = anti_injection(epm_decode($_POST['id'],ENT_QUOTES, 'UTF-8'));
}

$password = '123456';
$password = password_hash($password,PASSWORD_DEFAULT);

  $query_user="SELECT nama_lengkap,email from user WHERE user_id='$id'";
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
        Email : '.$row_user['email'].'<br>
        <b>Password Baru Anda : 123456</b><br>
        IP : '.$ip.'<br>Browser : '.$browser.'<br><br><br><br>
        Harap simpan baik-baik akun Anda.<br><br>
        Hormat Kami,<br>'.$site_name.'<br>Email otomatis, Mohon tidak membalas email ini</p>';
    $mail->Body = $mailContent;
  }

  if(empty($error)){
      $update="UPDATE user SET password='$password' WHERE user_id='$id'";
        if($connection->query($update) === false) { 
          die($connection->error.__LINE__); 
          echo'Sepertinya Sistem Kami sedang error!';
        } else{
          echo'success';
          if($gmail_active =='Y'){
            if(!$mail->send()){
              echo 'Mailer Error: Send email hanya bekerja saat online';
            }
          }
        }
      }else{
        echo'Akun Anda tidak ditemukan, silahkan cek kembali.!';
      }
  }else{           
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }


/** Setactive user */
break;
case 'active':
if(empty($_POST['id'])) {
  echo 'ID tidak ditemukan, Silahkan coba kembali';
  exit;
}
$id = htmlentities($_POST['id'], ENT_QUOTES, 'UTF-8');
$active = htmlentities($_POST['active'], ENT_QUOTES, 'UTF-8');
$update="UPDATE user SET active='$active' WHERE user_id='$id'";
if($connection->query($update) === false) { 
  echo 'error';
  die($connection->error.__LINE__); 
}else{
  echo'success';
}


/** Kenaikan Update */
break;
case'kenaikan':
$error = array();

  if (empty($_POST['kelas'])) {
    $error[] = 'Kelas harus dipilih';
  } else {
    $kelas = anti_injection($_POST['kelas']);
  }

  if (empty($_POST['status'])) {
    $error[] = 'Kelas harus dipilih';
  } else {
    $status = anti_injection($_POST['status']);
  }

  $tahun_ajaran = anti_injection($_POST['tahun_ajaran']);
 
  if (empty($_POST['id'])) {
    $error[] = 'Siswa harus dipilih';
  } else {
    $idArr = $_POST['id'];
  }
  
if (empty($error)) {
  foreach($idArr as $id){ 
    $update="UPDATE user SET kelas='$kelas', tahun_ajaran='$tahun_ajaran', active='$status' WHERE user_id='$id'";
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
  $query ="SELECT nisn FROM user where user_id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    $nisn = $row['nisn'];
    if(empty($nisn)){
      die('NISN Anda belum terisi. Mohon lengkapi data Anda untuk melanjutkan!');
    }
    $filepath = "../../../sw-content/qrcode/$nisn.png";
    if ($nisn && file_exists($filepath??'-.jpg')) {
        unlink ('../../../sw-content/qrcode/'.$row['nisn'].'.png'); 
    }
    echo'success';
  }else{
    echo'Data tidak ditemukan!';
  }
}

/* --------------- Delete ------------*/
break;
case 'delete':
  $id       = anti_injection(epm_decode($_POST['id']));

  $query ="SELECT avatar,nisn FROM user where user_id='$id' LIMIT 1";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    if(file_exists('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').'') && $row['avatar'] !== 'avatar.jpg'){
      unlink ('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').''); 
    }

    if(file_exists('../../../sw-content/qrcode/'.strip_tags($row['nisn']??'-').'.png')){
      unlink ('../../../sw-content/qrcode/'.strip_tags($row['nisn']??'-').'.png'); 
    }
  }

  $deleted  = "DELETE FROM user WHERE user_id='$id'";
  if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }



/* ----- Import -------*/
break;
case 'import':
    // Error impor dicatat ke log tanpa membocorkan detail internal ke browser.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);

// tulis juga ke file log biar pasti kebaca
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/import_error.log');

// kalau fatal, tulis penyebabnya
register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
    file_put_contents(__DIR__ . '/import_error.log',
      "\n[".date('c')."] FATAL: {$e['message']} in {$e['file']}:{$e['line']}\n",
      FILE_APPEND
    );
  }
});

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
    if ($i == 4) continue; 

        $nisn = $connection->real_escape_string((string)($row['A'] ?? ''));
        $rfid = $connection->real_escape_string((string)($row['B'] ?? ''));
        $email = $connection->real_escape_string((string)($row['C'] ?? ''));
        $nama_lengkap = $connection->real_escape_string((string)($row['D'] ?? ''));
        $tempat_lahir = $connection->real_escape_string((string)($row['E'] ?? ''));

        $tanggal_raw = $row['F'] ?? '';
        $tanggal_lahir = $tanggal_raw ? $connection->real_escape_string(date('Y-m-d', strtotime($tanggal_raw))) : null;

        $jenis_kelamin = $connection->real_escape_string((string)($row['G'] ?? ''));
        $kelas = $connection->real_escape_string((string)($row['H'] ?? ''));
        $tahun_ajaran = $connection->real_escape_string((string)($row['I'] ?? ''));

        $lokasi_raw = $row['J'] ?? '';
        $lokasi = is_numeric($lokasi_raw) ? (int)$lokasi_raw : null;

        $alamat = $connection->real_escape_string((string)($row['K'] ?? ''));
        $telp = $connection->real_escape_string((string)($row['L'] ?? ''));

        if (empty($nisn) || empty($email) || empty($nama_lengkap)) {
            continue; 
        }

        // Cek jika data sudah ada berdasarkan NISN dan Email
        $sql_check = "SELECT * FROM user WHERE nisn = '$nisn' AND email = '$email'";
        $result = $connection->query($sql_check);
        if ($result->num_rows > 0) {
            // Jika data sudah ada, update
            $sql_update = "UPDATE user SET rfid='$rfid',
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
                telp='$telp' WHERE nisn = '$nisn' AND email = '$email'";
            if ($connection->query($sql_update) === TRUE) {
                //echo "Data berhasil diupdate untuk $nisn";
            } else {
                echo "Error updating record: " . $connection->error . "<br>";
            }
        } else {
            if($nisn == ""){

            }else{
            // Jika data belum ada, insert
            $sql_insert = "INSERT INTO user (nisn,
                  rfid,
                  email,
                  password,
                  nama_lengkap,
                  tempat_lahir,
                  tanggal_lahir,
                  jenis_kelamin,
                  kelas,
                  tahun_ajaran,
                  lokasi,
                  alamat,
                  telp,
                  tanggal_registrasi,
                  tanggal_login,
                  ip,
                  browser,
                  status) values(
                  '$nisn',
                  '$rfid',
                  '$email',
                  '$password',
                  '$nama_lengkap',
                  '$tempat_lahir',
                  '$tanggal_lahir',
                  '$jenis_kelamin',
                  '$kelas',
                  '$tahun_ajaran',
                  '$lokasi',
                  '$alamat',
                  '$telp',
                  '$date $time',
                  '$date $time',
                  '$ip',
                  '$browser',
                  'Offline')";
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
