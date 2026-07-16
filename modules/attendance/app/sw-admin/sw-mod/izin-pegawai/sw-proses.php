<?php 
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:../login/');
  exit;
} else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

$validMimeTypes = [
    "image/jpeg", 
    "image/png", 
    "image/gif",
    "image/WEBP"
];

$allowedTypes = ["jpg", "jpeg", "png", "gif", "WEBP"];
$maxFileSize = 5 * 1024 * 1024; // 5MB

$uploadDir = '../../../sw-content/izin/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

function resizeImage($resourceType, int $image_width, int $image_height): GdImage {
  $resizeWidth = 700;
  $resizeHeight = (int)(($image_height / $image_width) * $resizeWidth);
  $imageLayer = imagecreatetruecolor($resizeWidth, $resizeHeight);
  if ($imageLayer === false) {
      throw new RuntimeException("Failed to create a true color image.");
  }
  if (!imagecopyresampled($imageLayer, $resourceType, 0, 0, 0, 0, $resizeWidth, $resizeHeight, $image_width, $image_height)) {
      throw new RuntimeException("Failed to resample the image.");
  }
  return $imageLayer;
}

function tambahAbsen($connection, $pegawai_id, $tanggal, $lokasi_id, $alasan, $keterangan) {
    // Cek apakah user sudah absen pada tanggal tersebut
    $cek_absen = "SELECT absen_id FROM absen_pegawai WHERE pegawai_id='$pegawai_id' AND tanggal='$tanggal'";
    $result = $connection->query($cek_absen);
    if ($result && $result->num_rows > 0) {
        // Jika sudah absen, hapus data izin (jika ada)
        $deleted = "DELETE FROM izin_pegawai WHERE pegawai_id='$pegawai_id' AND tanggal='$tanggal'";
        $connection->query($deleted);
        echo "Anda sudah melakukan absensi pada tanggal " . tanggal_ind($tanggal) . "!";
        return false;
    } else {
        // Tambahkan data absen dengan status Izin
        $add_absen = "INSERT INTO absen_pegawai (pegawai_id, tanggal, lokasi_id, status_masuk, status_pulang, kehadiran, keterangan)
        VALUES ('$pegawai_id', '$tanggal', '$lokasi_id', 'Izin', 'Izin', 'Izin', '$keterangan')";
        return $connection->query($add_absen);
    }
}

switch (@$_GET['action']){
case 'add':
$error = array();
  
$fields = [
    'pegawai_id'       => 'Pegawai ID',
    'tanggal'          => 'Tanggal Mulai',
    'tanggal_selesai'  => 'Tanggal Selesai',
    'alasan'           => 'Alasan',
    'keterangan'       => 'Keterangan'
];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        if ($key === 'tanggal') {
            $$key =  date('Y-m-d', strtotime($_POST[$key]));
        }
        elseif ($key === 'tanggal_selesai') {
            $$key =  date('Y-m-d', strtotime($_POST[$key]));
        }
        // default untuk field lainnya
        else {
            $$key = anti_injection($_POST[$key]);
        }
    }
  }


  if (empty($_FILES['foto']['name'])) {
      $newFileName = null;
    } else {
      $fileTmpPath = $_FILES['foto']['tmp_name'];
      $fileSize = $_FILES['foto']['size'];
      $fileName = basename($_FILES['foto']['name']);
      $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

      $sourceProperties = getimagesize($fileTmpPath);
      $uploadImageType  = $sourceProperties[2];
      $sourceImageWidth = $sourceProperties[0];
      $sourceImageHeight = $sourceProperties[1];

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_file($finfo, $fileTmpPath);
      finfo_close($finfo);

      // Beri nama unik dan pindahkan file
      $newFileName = uniqid("izin_pegawai_", true) . "." . $fileExt;
      $destPath = $uploadDir . $newFileName;
    }
    
  if (empty($error)) {

    $data_user = NULL;
    $query_user   = "SELECT nama_lengkap,lokasi FROM pegawai WHERE pegawai_id='$pegawai_id'";
    $result_user  = $connection->query($query_user);
    if($result_user->num_rows > 0){
      $data_user    = $result_user->fetch_assoc();
    }else{
      die("Data Pegawai tidak ditemukan!");
    }
    

    $cek_izin = "SELECT izin_id FROM izin_pegawai WHERE pegawai_id='$pegawai_id' AND ('$tanggal' BETWEEN tanggal AND tanggal_selesai)";
    if ($connection->query($cek_izin)->num_rows > 0) {
        die("Izin sudah ada pada tanggal tersebut!");
    }

      if (!empty($_FILES['foto']['name'])) {

        if (!in_array($fileExt, $allowedTypes)) {
            die("Hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diperbolehkan!");
        }

        // Validasi ukuran file
        if ($fileSize > $maxFileSize) {
            die("Ukuran file terlalu besar, Maksimal Size 5MB!");
        }

        if (!in_array($mimeType, $validMimeTypes)) {
            die("Tipe MIME file tidak valid.");
        }
      }

      $add ="INSERT INTO izin_pegawai(pegawai_id,
            nama_lengkap,
            tanggal,
            tanggal_selesai,
            files,
            alasan,
            keterangan,
            time,
            date,
            status) values('$pegawai_id',
            '$data_user[nama_lengkap]',
            '$tanggal',
            '$tanggal_selesai',
            '$newFileName',
            '$alasan',
            '$keterangan',
            '$time',
            '$date',
            'Y')";
    if($connection->query($add) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        $status = 'success';
        if (!empty($_FILES['foto']['name'])) {
          $imageProcess = processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
        }

        while (strtotime($tanggal) <= strtotime($tanggal_selesai)) {
          if (!tambahAbsen($connection, $pegawai_id, $tanggal, $data_user['lokasi'], $alasan, $keterangan))
          $status = '';
          break;
          $tanggal = date('Y-m-d',strtotime('+1 days',strtotime($tanggal)));
        }
        echo $status;
    }

  }else{           
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
}


break;
case 'update':
  
$fields = [ 
    'id'               => 'ID',
    'pegawai_id'       => 'Pegawai ID',
    'tanggal'          => 'Tanggal Mulai',
    'tanggal_selesai'  => 'Tanggal Selesai',
    'alasan'           => 'Alasan',
    'keterangan'       => 'Keterangan'
];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        if ($key === 'id') {
            $$key =  htmlentities(epm_decode($_POST[$key]));
        }
        elseif ($key === 'tanggal') {
            $$key =  date('Y-m-d', strtotime($_POST[$key]));
        }
        elseif ($key === 'tanggal_selesai') {
            $$key =  date('Y-m-d', strtotime($_POST[$key]));
        }
        // default untuk field lainnya
        else {
            $$key = anti_injection($_POST[$key]);
        }
    }
  }
    
  if (empty($_FILES['foto']['name'])) {
    $newFileName = '';
  } else {
    $fileTmpPath = $_FILES['foto']['tmp_name'];
    $fileSize = $_FILES['foto']['size'];
    $fileName = basename($_FILES['foto']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $sourceProperties = getimagesize($fileTmpPath);
    $uploadImageType  = $sourceProperties[2];
    $sourceImageWidth = $sourceProperties[0];
    $sourceImageHeight = $sourceProperties[1];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpPath);
    finfo_close($finfo);

    // Beri nama unik dan pindahkan file
    $newFileName = uniqid("izin_", true) . "." . $fileExt;
    $destPath = $uploadDir . $newFileName;
  }
  
if (empty($error)) {

    $data_user = NULL;
    $query_user   = "SELECT nama_lengkap,lokasi FROM pegawai WHERE pegawai_id='$pegawai_id'";
    $result_user  = $connection->query($query_user);
    if($result_user->num_rows > 0){
      $data_user    = $result_user->fetch_assoc();
    }else{
      die("Data Pegawai tidak ditemukan!");
    }

    if (!empty($_FILES['foto']['name'])) {

      if (!in_array($fileExt, $allowedTypes)) {
          die("Hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diperbolehkan!");
      }

      // Validasi ukuran file
      if ($fileSize > $maxFileSize) {
          die("Ukuran file terlalu besar, Maksimal Size 5MB!");
      }

      if (!in_array($mimeType, $validMimeTypes)) {
          die("Tipe MIME file tidak valid.");
      }
    }

    $update = "UPDATE izin_pegawai SET pegawai_id='$pegawai_id',
      nama_lengkap='$data_user[nama_lengkap]',
      alasan='$alasan',
      keterangan='$keterangan',
      time='$time',
      date='$date'";
      // Tambahkan file jika ada
      if (!empty($_FILES['foto']['name'])) {
          $update .= ", files='$newFileName'";
      }
      $update .= " WHERE izin_id='$id'";
      if($connection->query($update) === false) { 
          die($connection->error.__LINE__); 
          echo'Data tidak berhasil disimpan!';
      } else{
          echo'success';
          if (!empty($_FILES['foto']['name'])) {
            $imageProcess = processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
          }
      }
  }else{
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }

/** Data Update izin */
break;
case 'get-data-update':
if(isset($_POST['id'])){
  $id           = anti_injection(epm_decode($_POST['id']));
  $query_izin   = "SELECT * FROM izin_pegawai WHERE izin_id='$id'";
  $result_izin  = $connection->query($query_izin);
  if($result_izin->num_rows > 0){
    $data_izin = $result_izin->fetch_assoc();
      $data['izin_id']        = epm_encode($data_izin["izin_id"]);
      $data['pegawai_id']     = htmlentities($data_izin["pegawai_id"]??'-');
      $data['tanggal']        = tanggal_ind($data_izin["tanggal"]);
      $data['tanggal_selesai']= tanggal_ind($data_izin["tanggal_selesai"]??'-');
      $data['alasan']         = strip_tags($data_izin["alasan"]??'-');
      $data['keterangan']     = strip_tags($data_izin["keterangan"]??'-');
      $data['files']          = strip_tags($data_izin["files"]??'-');
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan!';
  }

}else{
  echo'ID tidak ditemukan!';
}
  
/** --------- Set Status ------ */
break;
case 'setujui':
if (isset($_POST['id'])) {
  $id     = anti_injection(epm_decode($_POST['id']));
  $status = htmlentities($_POST['status']);

  // Ambil berdasarkan izin_id
  $qIzin = $connection->query("SELECT * FROM izin_pegawai WHERE izin_id='$id'");
  if ($qIzin->num_rows === 0) {
      exit('Data tidak ditemukan!');
  }
  $dataIzin   = $qIzin->fetch_assoc();
  $pegawai_id = $dataIzin['pegawai_id'];
  $tanggal    = $dataIzin['tanggal'];
  $tglSelesai = $dataIzin['tanggal_selesai'];
  $alasan     = $dataIzin['alasan'];
  $keterangan = $dataIzin['keterangan'];

  // Cek status sebelumnya
  if (!$dataIzin['status']=='Y') {
      exit('Sebelumnya data ini sudah disetujui!');
  }

  // Ambil data user
  $qUser = $connection->query("SELECT nama_lengkap, lokasi FROM pegawai WHERE pegawai_id='$pegawai_id'");
  if ($qUser->num_rows === 0) {
      exit('Data pengguna tidak ditemukan!');
  }
  $dataUser = $qUser->fetch_assoc();

  // Update status 
  if (!$connection->query("UPDATE izin_pegawai SET status='$status' WHERE izin_id='$id'")) {
      exit('Data tidak dapat disimpan! ' . $connection->error);
  }

  // Tambah notifikasi
    $notifSql = "INSERT INTO notifikasi (pegawai_id, nama, keterangan, link, tanggal, datetime, tipe, tujuan, status)
    VALUES ('{$dataIzin['pegawai_id']}','{$dataUser['nama_lengkap']}',
    'Permohonan Izin Anda disetujui', 'izin', '$date', '$timeNow', 'admin', 'pegawai', 'N')";
    $connection->query($notifSql);

  // Tambah absen per hari
  while (strtotime($tanggal) <= strtotime($tglSelesai)) {
      if (!tambahAbsen($connection, $pegawai_id, $tanggal, $dataUser['lokasi'], $alasan, $keterangan)) break;
      $tanggal = date('Y-m-d', strtotime($tanggal . ' +1 day'));
  }
  echo 'success';
}


/** Tolak */ 
break;
case 'ditolak':
if (isset($_POST['id'])) {
  $id     = anti_injection(epm_decode($_POST['id']));
  $status = htmlentities($_POST['status']);
  if (empty($id) || empty($status)) {
      exit('ID atau status tidak boleh kosong.');
  }

  $qIzin = $connection->query("SELECT pegawai_id, tanggal, tanggal_selesai, status FROM izin_pegawai WHERE izin_id='$id'");
  if ($qIzin->num_rows === 0) {
      exit('Data tidak ditemukan!');
  }

  $dataIzin = $qIzin->fetch_assoc();
  // Cegah update jika status sudah final
  if ($dataIzin['status'] === 'N') {
      exit('Permohonan ini sudah ditolak sebelumnya.');
  }

  // Ambil data user
  $qUser = $connection->query("SELECT nama_lengkap,lokasi FROM pegawai WHERE pegawai_id='{$dataIzin['pegawai_id']}'");
  if ($qUser->num_rows === 0) {
      exit('Data pengguna tidak ditemukan!');
  }
  $dataUser = $qUser->fetch_assoc();

  // Update status cuti
  $update = $connection->query("UPDATE izin_pegawai SET status='$status' WHERE izin_id='$id'");
  if (!$update) {
      exit('Gagal mengupdate status  izin: ' . $connection->error);
  }

  $notifSql = "INSERT INTO notifikasi (pegawai_id, nama, keterangan, link, tanggal, datetime, tipe, tujuan, status)
    VALUES ('{$dataIzin['pegawai_id']}','{$dataUser['nama_lengkap']}',
    'Permohonan Izin Anda ditolak', 'izin', '$date', '$timeNow', 'pegawai', 'pegawai', 'N')";
    $connection->query($notifSql);

  $start  = new DateTime($dataIzin['tanggal']);
  $finish = new DateTime($dataIzin['tanggal_selesai']);
  $interval = new DateInterval('P1D');
  $daterange = new DatePeriod($start, $interval, $finish->modify('+1 day')); // Termasuk tanggal selesai

  foreach ($daterange as $dateObj) {
      $tanggal = $dateObj->format('Y-m-d');
      $connection->query("DELETE FROM absen_pegawai WHERE tanggal='$tanggal' AND pegawai_id='{$dataIzin['pegawai_id']}'");
  }
  echo 'success';
}

/* --------------- Delete ------------*/
break;
case 'delete':
if(isset($_POST['id'])){

  $id = anti_injection(epm_decode($_POST['id']));
  $q = $connection->query("SELECT * FROM izin_pegawai WHERE izin_id='$id'");
  if ($q->num_rows === 0) {
      exit('Data tidak ditemukan!');
  }

  $d = $q->fetch_assoc();
  if ($d['status'] !== 'N' && $d['status'] !== 'PENDING') {
      exit('Data izin telah disetujui dan tidak dapat dihapus!');
  }

  // Hapus data cuti
  if ($connection->query("DELETE FROM izin_pegawai WHERE izin_id='$id'")) {
      echo 'success';

    if (!empty($d['files']) && file_exists("../../../sw-content/izin/" .($d['files']??'-.jpg'))) {
      unlink("../../../sw-content/izin/" . $d['files']);
    }
    // Jika sebelumnya sudah disetujui (logika disesuaikan bila perlu)
    $start  = new DateTime($d['tanggal']);
    $end    = new DateTime($d['tanggal_selesai']);
    $end->modify('+1 day');
      foreach (new DatePeriod($start, new DateInterval('P1D'), $end) as $date) {
          $tgl = $date->format('Y-m-d');
          $connection->query("DELETE FROM absen_pegawai WHERE tanggal='$tgl' AND pegawai_id='{$d['pegawai_id']}'");
      }
  } else {
      exit('Data tidak berhasil dihapus: ' . $connection->error);
  }
}
   
   

break;
}}