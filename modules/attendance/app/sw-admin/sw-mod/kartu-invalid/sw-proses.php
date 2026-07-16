<?php session_start();
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
case 'add-siswa':
  $error = array();

  if (empty($_POST['nisn'])) {
    $nisn = '';
  } else {
    $nisn = anti_injection($_POST['nisn']);
  }

  if (empty($_POST['rfid'])) {
    $error[] = 'RFID tidak boleh kosong';
  } else {
    $rfid = anti_injection($_POST['rfid']);
  }

  if (empty($_POST['nama_lengkap'])) {
      $error[] = 'Nama Lengkap tidak boleh kosong';
    } else {
      $nama_lengkap = mysqli_real_escape_string($connection,$_POST['nama_lengkap']);
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

  if (empty($_POST['password'])) {
    $error[] = 'Password tidak boleh kosong';
  } else {
    $password = anti_injection($_POST['password']);
    $password = password_hash($password,PASSWORD_DEFAULT);
  }

  if (empty($_POST['lokasi'])) {
    $error[] = 'Lokasi Kerja tidak boleh kosong';
  } else {
    $lokasi = anti_injection($_POST['lokasi']);
  }


  if (empty($_POST['tempat_lahir'])) {
    $tempat_lahir = '-';
  } else {
    $tempat_lahir = anti_injection($_POST['tempat_lahir']);
  }

  if (empty($_POST['tanggal_lahir'])) {
      $error[] = 'Tanggal Lahir tidak boleh kosong';
    } else {
      $tanggal_lahir = date('Y-m-d', strtotime($_POST['tanggal_lahir']));
  }

  if (empty($_POST['jenis_kelamin'])) {
    $error[] = 'Jenis Kelamin tidak boleh kosong';
  } else {
    $jenis_kelamin = anti_injection($_POST['jenis_kelamin']);
  }

  if (empty($_POST['kelas'])) {
    $error[] = 'Kelas tidak boleh kosong';
  } else {
    $kelas = anti_injection($_POST['kelas']);
  }


  if (empty($_POST['alamat'])) {
    $alamat ='-';
  } else {
    $alamat = anti_injection($_POST['alamat']);
  }

  if (empty($_POST['telp'])) {
    $telp = '-';
  } else {
    $telp = anti_injection($_POST['telp']);
  }
  if (empty($error)) {
    $query_siswa ="SELECT rfid FROM siswa WHERE rfid='$rfid'";
    $result_siswa = $connection->query($query_siswa);
    if($result_siswa->num_rows > 0){
      echo'RFID sudah terdaftar!';
      exit;
    }else{
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
            lokasi,
            alamat,
            telp,
            avatar,
            tanggal_registrasi,
            tanggal_login,
            time,
            ip,
            browser,
            status,
            active) values(
            '$nisn',
            '$rfid',
            '$email',
            '$password',
            '$nama_lengkap',
            '$tempat_lahir',
            '$tanggal_lahir',
            '$jenis_kelamin',
            '$kelas',
            '$lokasi',
            '$alamat',
            '$telp',
            'avatar.jpg',
            '$date $time',
            '$date $time',
            '$timein',
            '$ip',
            '$browser',
            'Offline',
            'Y')";
      $deleted = "DELETE FROM kartu_invalid WHERE kartu='$rfid'";

    if($connection->query($add) === false) { 
    die($connection->error.__LINE__); 
      echo'Data tidak berhasil disimpan!';
    } else{
      echo'success';
      /** Hapus Kaartu jika success */
      $connection->query($deleted);
    }
  }
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}

break;
case'add-guru':
  $error = array();

  if (empty($_POST['nip'])) {
    $nip ='-';
  } else {
    $nip = anti_injection($_POST['nip']);
  }

  if (empty($_POST['rfid'])) {
    $error[] = 'RFID tidak boleh kosong';
  } else {
    $rfid = anti_injection($_POST['rfid']);
  }

  if (empty($_POST['nama_lengkap'])) {
      $error[] = 'Nama Lengkap tidak boleh kosong';
    } else {
      $nama_lengkap = anti_injection($_POST['nama_lengkap']);
  }

  if (empty($_POST['jabatan'])) {
    $jabatan = '-';
  } else {
    $jabatan = anti_injection($_POST['jabatan']);
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

  if (empty($_POST['password'])) {
    $error[] = 'Password tidak boleh kosong';
  } else {
    $password = anti_injection($_POST['password']);
    $password = password_hash($password,PASSWORD_DEFAULT);
  }

  if (empty($_POST['lokasi'])) {
    $error[] = 'Lokasi Kerja tidak boleh kosong';
  } else {
    $lokasi = anti_injection($_POST['lokasi']);
  }


  if (empty($_POST['tempat_lahir'])) {
    $tempat_lahir ='-';
  } else {
    $tempat_lahir = anti_injection($_POST['tempat_lahir']);
  }

  if (empty($_POST['tanggal_lahir'])) {
      $error[] = 'Tanggal Lahir tidak boleh kosong';
    } else {
      $tanggal_lahir = date('Y-m-d', strtotime($_POST['tanggal_lahir']));
  }

  if (empty($_POST['jenis_kelamin'])) {
    $error[] = 'Jenis Kelamin tidak boleh kosong';
  } else {
    $jenis_kelamin = anti_injection($_POST['jenis_kelamin']);
  }


  if (empty($_POST['tipe'])) {
    $error[] = 'Tipe tidak boleh kosong';
  } else {
    $tipe = anti_injection($_POST['tipe']);
  }


  if (empty($_POST['alamat'])) {
    $alamat = '';
  } else {
    $alamat = anti_injection($_POST['alamat']);
  }

  if (empty($_POST['telp'])) {
    $telp ='-';
  } else {
    $telp = anti_injection($_POST['telp']);
  }

  if (empty($error)) {
    $query_guru ="SELECT rfid FROM guru WHERE rfid='$rfid'";
    $result_guru = $connection->query($query_guru);
    if($result_guru->num_rows > 0){
      echo'RFID sudah terdaftar!';
      exit;
    }else{
      $add ="INSERT INTO guru(nip,
          rfid,
          email,
          password,
          nama_lengkap,
          tempat_lahir,
          tanggal_lahir,
          jenis_kelamin,
          telp,
          tipe,
          jabatan,
          lokasi,
          alamat,
          avatar,
          tanggal_registrasi,
          tanggal_login,
          time,
          ip,
          browser,
          status) values('$nip',
          '$rfid',
          '$email',
          '$password',
          '$nama_lengkap',
          '$tempat_lahir',
          '$tanggal_lahir',
          '$jenis_kelamin',
          '$telp',
          '$tipe',
          '$jabatan',
          '$lokasi',
          '$alamat',
          'avatar.jpg',
          '$date $time',
          '$date $time',
          '$timein',
          '$ip',
          '$browser',
          'Offline')";
      $deleted = "DELETE FROM kartu_invalid WHERE kartu='$rfid'";

      if($connection->query($add) === false) { 
          die($connection->error.__LINE__); 
          echo'Data tidak berhasil disimpan!';
      } else{
          echo'success';
          /** Hapus Kaartu jika success */
          $connection->query($deleted);
      }
    }
  }else{           
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
}
  

/* --------------- Delete ------------*/
break;
case 'delete':
  $id       = anti_injection(epm_decode($_POST['id']));
  $deleted = "DELETE FROM kartu_invalid WHERE kartu_invalid_id='$id'";
  if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }
break;
}}