<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
  echo'Not Found';
}else{
  require_once'../../../sw-library/sw-config.php';
  require_once'../../../sw-library/sw-function.php';
  require_once'../../oauth/user.php';

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

function isHariLibur($connection, $tanggal, $tanggal_selesai) {
    // Ubah tanggal dan tanggal_selesai jadi timestamp
    $start = strtotime($tanggal);
    $end = strtotime($tanggal_selesai);

    for ($i = $start; $i <= $end; $i += 86400) {
        $tanggal = date('Y-m-d', $i);
        $namaHari = getNamaHariIndonesia($tanggal);
  
        $queryHari = "SELECT jam_sekolah_id FROM jam_sekolah WHERE hari = '$namaHari' AND tipe='Siswa' AND active = 'N' LIMIT 1";
        $resultHari = mysqli_query($connection, $queryHari);
        if ($resultHari && mysqli_num_rows($resultHari) > 0) {
            return true; // Ada hari libur mingguan dalam rentang
        }
    }

    // Cek libur nasional dalam rentang tanggal
    $queryTanggal = "SELECT libur_nasional_id FROM libur_nasional WHERE libur_tanggal BETWEEN '$tanggal' AND '$tanggal_selesai' LIMIT 1";
    $resultTanggal = mysqli_query($connection, $queryTanggal);
    if ($resultTanggal && mysqli_num_rows($resultTanggal) > 0) {
        return true;
    }
    return false;
}


switch (@$_GET['action']){
case 'data-izin':

if (empty($_GET['mulai']) OR empty($_GET['selesai'])){ 
  $filter = "AND MONTH(tanggal) ='$month' AND YEAR(tanggal) ='$year'";
} else { 
  $mulai = date('Y-m-d', strtotime($_GET['mulai']));
  $selesai = date('Y-m-d', strtotime($_GET['selesai']));
  $filter = "AND tanggal BETWEEN '$mulai' AND '$selesai'";
}

$query_izin ="SELECT * FROM izin WHERE user_id='$data_user[user_id]' $filter ORDER BY izin_id DESC LIMIT 10";
$result_izin = $connection->query($query_izin);
if($result_izin->num_rows > 0){
  while ($data_izin= $result_izin->fetch_assoc()){
  $izin_id = anti_injection($data_izin['izin_id']);
  
  if($data_izin['status'] == '-' OR $data_izin['status'] == 'PENDING'){
    $status='<span class="badge badge-primary badge-pill">Panding</span>';
  }elseif($data_izin['status'] == 'Y'){
    $status='<span class="badge badge-warning badge-pill">Disetujui</span>';
  }elseif($data_izin['status'] == 'N'){
    $status='<span class="badge badge-danger badge-pill">Ditotak</span>';
  }else{
    $status='';
  }

  if(file_exists('../../../sw-content/izin/'.strip_tags($data_izin['files']??'-').'')){
    $photo ='<a href="../sw-content/izin/'.strip_tags($data_izin['files']??'').'" class="popup-image">
    <img src="data:image/gif;base64,'.base64_encode(file_get_contents('../../../sw-content/izin/'.strip_tags($data_izin['files']??'-').'')).'" height="50"></a>';
  }else{
    $photo ='<img src="../../../sw-content/thumbnail.jpg" height="50">';
  }
    
echo'
<div class="card border-0 mb-2">
    <div class="card-body">
        <div class="row">
          <div class="col align-self-center">
              <p class="text-secondary"><span class="badge badge-warning badge-pill">'.$data_izin['alasan'].'</span> '.$status.'</p>
          </div> 
        </div>

        <div class="row align-items-center mt-1">
            <div class="col-auto align-self-center">
              <figure class="avatar avatar-50 rounded mb-0">
                '.$photo.'
              </figure>
            </div>

            <div class="col align-self-center">
              <span class="text-secondary">
               '.$data_user['nama_lengkap'].'
              </span> 
              <p class="text-secondary small">
                '.tanggal_ind($data_izin['tanggal']).' s.d '.tanggal_ind($data_izin['tanggal_selesai']).'
              </p>
            </div>
        
            <div class="col-auto align-self-center">
              <div class="dropdown dropleft">
                  <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                  </a>
                  <div class="dropdown-menu dropdown-width-50 ml-3">
                    <button class="dropdown-item small btn-update" data-id="'.convert("encrypt",$data_izin['izin_id']).'" type="button">Edit</button>
                    <button class="dropdown-item small btn-delete" data-id="'.convert("encrypt",$data_izin['izin_id']).'" type="button">Hapus</button>
                  </div>
              </div> 
            </div>

            <div class="col-12">
              <hr class="mt-2 mb-1">
              <p class="text-secondary small ">'.strip_tags($data_izin['keterangan']??'-').'</p>
            </div> 

        </div>
    </div>
</div>';
}
  echo'
  <div class="text-center show_more_main'.$izin_id.' mt-4">
      <button data-id="'.$izin_id.'" class="btn btn-light rounded load-more">Show more</button>
  </div>
<script type="text/javascript">
  $(".popup-image").magnificPopup({type:"image"});
</script>';
}else{
  echo'<div class="alert alert-secondary mt-3">Saat ini, data pengajuan izin belum tersedia atau masih kosong!</div>';
}

/** Moad More Izin */
break;
case 'data-izin-load':
if (empty($_POST['mulai']) OR empty($_POST['selesai'])){ 
  $filter = "AND MONTH(tanggal) ='$month' AND YEAR(tanggal) ='$year'";
} else { 
  $mulai = date('Y-m-d', strtotime($_POST['mulai']));
  $selesai = date('Y-m-d', strtotime($_POST['selesai']));
  $filter = "AND tanggal BETWEEN '$mulai' AND '$selesai'";
}

$id = anti_injection($_POST['id']);

$query_count    ="SELECT COUNT(izin_id) AS total FROM izin WHERE user_id='$data_user[user_id]' AND izin_id < $id $filter ORDER BY izin_id DESC";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 10;
$query_izin ="SELECT * FROM izin WHERE user_id='$data_user[user_id]' AND izin_id < $id $filter ORDER BY izin_id DESC LIMIT $showLimit";
$result_izin = $connection->query($query_izin);

if($result_izin->num_rows > 0){
  while ($data_izin= $result_izin->fetch_assoc()){
    $izin_id = anti_injection($data_izin['izin_id']);

    if($data_izin['status'] == '-' OR $data_izin['status'] == 'PENDING'){
      $status='<span class="badge badge-primary badge-pill">Panding</span>';
    }elseif($data_izin['status'] == 'Y'){
      $status='<span class="badge badge-warning badge-pill">Disetujui</span>';
    }elseif($data_izin['status'] == 'N'){
      $status='<span class="badge badge-danger badge-pill">Ditotak</span>';
    }else{
      $status='';
    }

  if(file_exists('../../../sw-content/izin/'.strip_tags($data_izin['files']??'-').'')){
    $photo ='<a href="../sw-content/izin/'.strip_tags($data_izin['files']??'').'" class="popup-image">
    <img src="data:image/gif;base64,'.base64_encode(file_get_contents('../../../sw-content/izin/'.strip_tags($data_izin['files']??'-').'')).'" height="50"></a>';
  }else{
    $photo ='<img src="../../../sw-content/thumbnail.jpg" height="50">';
  }
    
echo'
<div class="card border-0 mb-2">
    <div class="card-body">
        <div class="row">
          <div class="col align-self-center">
            <p class="text-secondary"><span class="badge badge-warning badge-pill">'.$data_izin['alasan'].'</span> '.$status.'</p>
          </div> 
        </div>

        <div class="row align-items-center mt-1">
            <div class="col-auto align-self-center">
              <figure class="avatar avatar-50 rounded mb-0">
                '.$photo.'
              </figure>
            </div>

            <div class="col align-self-center">
              <span class="text-secondary">
               '.$data_user['nama_lengkap'].'
              </span> 
              <p class="text-secondary small">
                '.tanggal_ind($data_izin['tanggal']).' s.d '.tanggal_ind($data_izin['tanggal_selesai']).'
              </p>
            </div>
        
            <div class="col-auto align-self-center">
              <div class="dropdown dropleft">
                  <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                  </a>
                  <div class="dropdown-menu dropdown-width-50 ml-3">
                    <button class="dropdown-item small btn-update" data-id="'.convert("encrypt",$data_izin['izin_id']).'" type="button">Edit</button>
                    <button class="dropdown-item small btn-delete" data-id="'.convert("encrypt",$data_izin['izin_id']).'" type="button">Hapus</button>
                  </div>
              </div> 
            </div>

            <div class="col-12">
              <hr class="mt-2 mb-1">
              <p class="text-secondary small ">'.strip_tags($data_izin['keterangan']??'-').'</p>
            </div> 

        </div>
    </div>
</div>';
}

if($totalRowCount > $showLimit){
  echo'
  <div class="text-center show_more_main'.$izin_id.' mt-4">
      <button data-id="'.$izin_id.'" class="btn btn-light rounded load-more">Show more</button>
  </div>';
}
echo'
<script type="text/javascript">
  $(".popup-image").magnificPopup({type:"image"});
</script>';

}else{
  echo'<div class="alert alert-secondary mt-3">Saat ini, data izin sudah tidak ada!</div>';
}


/** Tambah baru Izin*/
break;
case 'add':
$data_wali = getWaliKelas($data_user['kelas'], $connection);

$error = [];

$fields = [
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
      $newFileName = uniqid("izin_", true) . "." . $fileExt;
      $destPath = $uploadDir . $newFileName;
    }
  
    if (empty($error)){

      if (isHariLibur($connection, $tanggal, $tanggal_selesai)) {
          die("Tanggal yang Anda pilih libur. Tidak bisa menambah data izin.");
          exit;
      }

       if (!in_array($fileExt, $allowedTypes)) {
            die("Hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diperbolehkan!");
        }

        // Validasi ukuran file
        if ($fileSize > $maxFileSize) {
            die("Ukuran file terlalu besar, Maksimal Size 5MB!");
        }

        $notifikasi ="INSERT INTO notifikasi (user_id,
                pegawai_id,
                nama,
                keterangan,
                link,
                tanggal,
                datetime,
                tipe,
                tujuan,
                status) values('{$data_user['user_id']}',
                '{$data_wali['pegawai_id']}',
                '{$data_user['nama_lengkap']}',
                'Baru saja megajukan izin',
                'izin',
                '$tanggal',
                '$timeNow',
                'siswa',
                'pegawai',
                'N')";

        /** Cek Izin Hari ini berdasarkan tanggal sekarang */
          $cek_izin = "SELECT izin_id FROM izin WHERE user_id='$user_id' AND ('$tanggal' BETWEEN tanggal AND tanggal_selesai)";
            if ($connection->query($cek_izin)->num_rows > 0) {
              die("Izin sudah ada pada tanggal tersebut!");
            }
              
             $add ="INSERT INTO izin(user_id,
                  nama_lengkap,
                  tanggal,
                  tanggal_selesai,
                  files,
                  alasan,
                  keterangan,
                  time,
                  date) values('$user_id',
                  '$data_user[nama_lengkap]',
                  '$tanggal',
                  '$tanggal_selesai',
                  '$newFileName',
                  '$alasan',
                  '$keterangan',
                  '$time',
                  '$date')";

          if($connection->query($add) === false) { 
            echo'Sepertinya Sistem Kami sedang error!';
            die($connection->error.__LINE__); 
          } else{
            echo'success';
            $connection->query($notifikasi);
            if (!empty($_FILES['foto']['name'])) {
              $imageProcess = processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
            }
          }
    }else{       
      foreach ($error as $key => $values) {            
        echo"$values\n";
      }
    }



/** Get Update Data Izin */
break;
case 'get-data-update':
if(isset($_POST['id'])){
  $id       = anti_injection(convert("decrypt",$_POST['id']));
  $query_izin  = "SELECT * FROM izin WHERE user_id='$data_user[user_id]' AND izin_id='$id'";
  $result_izin = $connection->query($query_izin);
  if($result_izin->num_rows > 0){
    while ($data_izin = $result_izin->fetch_assoc()) {
      $data['izin_id']          = anti_injection(convert("encrypt",$data_izin["izin_id"]));
      $data['tanggal']          = tanggal_ind($data_izin["tanggal"]);
      $data['tanggal_selesai']  = tanggal_ind($data_izin["tanggal_selesai"]);
      $data['alasan']           = strip_tags($data_izin["alasan"]??'-');
      $data['keterangan']       = strip_tags($data_izin["keterangan"]??'-');
      $data['files']            = strip_tags($data_izin["files"]??'-');
    }
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan';
  }
}

/** Update */
break;
case 'update':
$error = array();

 $error = [];

$fields = [
  'id'               => 'ID',
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
            $$key =  htmlentities(convert("decrypt",$_POST[$key]));
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
    $newFileName = uniqid("izin_", true) . "." . $fileExt;
    $destPath = $uploadDir . $newFileName;
  }
  

if (empty($error)){

    if (!empty($_FILES['foto']['name'])) {
      if (!in_array($fileExt, $allowedTypes)) {
          die("Hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diperbolehkan!");
      }

      // Validasi ukuran file
      if ($fileSize > $maxFileSize) {
          die("Ukuran file terlalu besar, Maksimal Size 5MB!");
      }
    }

    /** Hapus file */
    if (!empty($_FILES['foto']['name'])) {
      $query_izin ="SELECT files FROM izin WHERE user_id='{$data_user['user_id']}' AND izin_id='$id'";
      $result_izin = $connection->query($query_izin);
      if($result_izin->num_rows > 0){
        $data_izin = $result_izin->fetch_assoc();
      if(!empty($data_izin['files']) && file_exists('../../../sw-content/izin/'.($data_izin['files']??'avatar.jpg').'')){
          unlink ('../../../sw-content/izin/'.$data_izin['files'].'');
        }
      }
    }
    
    $update="UPDATE izin SET tanggal='$tanggal',
        tanggal_selesai='$tanggal_selesai',
        alasan='$alasan',
        keterangan='$keterangan',
        date='$date',
        time='$time_sekarang'";
        // Tambahkan file jika ada
        if (!empty($_FILES['foto']['name'])) {
            $update .= ", files='$newFileName'";
        }
    $update .= "WHERE user_id='{$data_user['user_id']}' AND izin_id='$id'"; 

    if($connection->query($update) === false) { 
      echo'Sepertinya Sistem Kami sedang error!';
      die($connection->error.__LINE__); 
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


/** Delete Izin*/
break;
case 'delete':
  if(isset($_POST['id'])){
  $id       = anti_injection(convert("decrypt",$_POST['id']));
  $query_izin ="SELECT izin_id,files FROM izin WHERE user_id='{$data_user['user_id']}' AND izin_id='$id'";
  $result_izin = $connection->query($query_izin);
  if($result_izin->num_rows > 0){
      $data_izin = $result_izin->fetch_assoc();

      $deleted = "DELETE FROM izin WHERE user_id='{$data_user['user_id']}' AND izin_id='$id'";
      if($connection->query($deleted) === true) {
        echo'success';
        if(!empty($data_izin['files']) && file_exists('../../../sw-content/izin/'.($data_izin['files']??'avatar.jpg').'')){
          unlink ('../../../sw-content/izin/'.$data_izin['files'].'');
        }
      } else { 
        echo'Data tidak berhasil dihapus.!';
        die($connection->error.__LINE__);
      }
    }else{
      echo'Data tidak ditemukan, Silahkan hubungi Admin!';
    }
  }


  break;
  }
}