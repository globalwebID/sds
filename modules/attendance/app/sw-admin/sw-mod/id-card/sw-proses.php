<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
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

  $uploadDir = '../../../sw-content/tema/';
  if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
  }

  function resizeImage($resourceType, int $image_width, int $image_height): GdImage {
    $resizeWidth = 600;
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


switch (@$_GET['action']){
/* ---------- ADD  ---------- */
case 'add':
$error = array();

  $id = anti_injection(epm_decode($_POST['id']));

    $fields = [
    'nama'   => 'Nama',
    'tipe'   => 'Tipe',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        $$key = anti_injection($_POST[$key]);
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
      $newFileName = uniqid("file_", true) . "." . $fileExt;
      $destPath = $uploadDir . $newFileName;
    }
  
  if (empty($error)){
    if(empty($id)){
        if (!empty($_FILES['foto']['name'])) {
            if (!in_array($fileExt, $allowedTypes)) {
              die("Foto  hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diperbolehkan!");
            }

            // Validasi ukuran file
            if ($fileSize > $maxFileSize) {
              die("Foto  ukuran file terlalu besar, maksimal ukuran 5MB!");
            }

            if (!in_array(mime_content_type($fileTmpPath), $validMimeTypes)) {
              die("Foto  tipe MIME file tidak valid.");
            }
        }

        $add ="INSERT INTO kartu_nama (nama, foto, tipe, active) values('$nama', '$newFileName', '$tipe', 'Y')";
        if($connection->query($add) === false) { 
            die($connection->error.__LINE__); 
            echo'Sepertinya Sistem Kami sedang error!';
        } else{
            echo'success';    
             $imageProcess = processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
        }

    }else{
      /** Update */
        if (!empty($_FILES['foto']['name'])) {
            if (!in_array($fileExt, $allowedTypes)) {
              die("Foto  hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diperbolehkan!");
            }

            // Validasi ukuran file
            if ($fileSize > $maxFileSize) {
              die("Foto  ukuran file terlalu besar, maksimal ukuran 5MB!");
            }

            if (!in_array(mime_content_type($fileTmpPath), $validMimeTypes)) {
              die("Foto  tipe MIME file tidak valid.");
            }
        }

      if (empty($_FILES['foto']['name'])) {
        $update ="UPDATE kartu_nama SET nama='$nama', tipe='$tipe' WHERE kartu_id='$id'";
      }else{
        $update ="UPDATE kartu_nama SET nama='$nama', tipe='$tipe', foto='$newFileName' WHERE kartu_id='$id'";
      }

      if($connection->query($update) === false) { 
        die($connection->error.__LINE__); 
        echo'Sepertinya Sistem Kami sedang error!';
      } else{
        echo'success';
        if (!empty($_FILES['foto']['name'])) {
          $imageProcess = processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
        }
      }
    }
  }else{           
    foreach ($error as $key => $values) {            
      echo "$values\n";
    }
  }


break;
case 'get-data-update':
if(empty($_GET['id'])){
$id             = anti_injection(epm_decode($_POST['id']));
  $query_tema   = "SELECT * FROM kartu_nama WHERE kartu_id='$id'";
  $result_tema  = $connection->query($query_tema);
  if($result_tema->num_rows > 0){
    $data_tema = $result_tema->fetch_assoc();
      $data['id']    = epm_encode($data_tema['kartu_id']);
      $data['nama']  = strip_tags($data_tema['nama']??'');
      $data['tipe']  = strip_tags($data_tema['tipe']??'');
      $data['foto']  = strip_tags($data_tema['foto']??'');
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan';
  }
}

/** Set Active */
break;
case 'active':
if(empty($_GET['id'])){
  $id = htmlentities($_POST['id']);
  $active = htmlentities($_POST['active']);
  $update="UPDATE kartu_nama SET active='$active' WHERE kartu_id='$id'";
  if($connection->query($update) === false) { 
    echo 'error';
    die($connection->error.__LINE__); 
  }else{
    echo'success';
  }
}

/* --------------- Delete ------------*/
break;
case 'delete':
if(empty($_GET['id'])){
$id       = anti_injection(epm_decode($_POST['id']));
$query ="SELECT foto FROM kartu_nama where kartu_id='$id'";
$result = $connection->query($query);
    if($result->num_rows > 0){
      $row = $result->fetch_assoc();
      if(file_exists("../../../sw-content/tema/".$row['foto']."")){
          unlink ("../../../sw-content/tema/".$row['foto']."");
      }
    }

/* Script Delete Data ------------*/
  $deleted = "DELETE FROM kartu_nama WHERE kartu_id='$id'";
  if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    //tidak berhasil
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }
}

break;
}}