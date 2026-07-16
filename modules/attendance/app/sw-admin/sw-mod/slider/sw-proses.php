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

$uploadDir = '../../../sw-content/slider/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

function resizeImage($resourceType, int $image_width, int $image_height): GdImage {
  $resizeWidth = 1000;
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
  
  if (empty($_POST['slider_nama'])){
    $error[]    = 'Judul tidak boleh kosong';
    } else {
      $slider_nama = anti_injection($_POST['slider_nama']);
  }

  if (empty($_POST['slider_url'])){
      $error[]    = 'Url/Domain tidak boleh kosong';
    } else {
      $slider_url =htmlentities($_POST['slider_url']);
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
      $newFileName = uniqid("slider_", true) . "." . $fileExt;
      $destPath = $uploadDir . $newFileName;
    }

  if (empty($error)) {
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

    $add ="INSERT INTO slider (slider_nama,
        slider_url,
        foto,
        active) values('$slider_nama',
        '$slider_url',
        '$newFileName',
        'Y')";
    if($connection->query($add) === false) { 
      die($connection->error.__LINE__); 
      echo'Sepertinya Sistem Kami sedang error!';
    } else{
      echo'success';
      $imageProcess = processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
    }
}else{
  foreach ($error as $key => $values) {            
    echo "$values\n";
  }
}

/** Update */
break;
case'update':

  if (empty($_POST['id'])){
    $error[]    = 'ID tidak boleh kosong';
    } else {
      $id = anti_injection(epm_decode($_POST['id']));
  }

  if (empty($_POST['slider_nama'])){
    $error[]    = 'Judul tidak boleh kosong';
    } else {
      $slider_nama = anti_injection($_POST['slider_nama']);
  }

  if (empty($_POST['slider_url'])){
      $error[]    = 'Url/Domain tidak boleh kosong';
    } else {
      $slider_url =htmlentities($_POST['slider_url']);
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
      $newFileName = uniqid("slider_", true) . "." . $fileExt;
      $destPath = $uploadDir . $newFileName;
    }

  if (empty($error)) {

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

    $update = "UPDATE slider SET slider_nama='$slider_nama', slider_url='$slider_url'";
      // Tambahkan file jika ada
      if (!empty($_FILES['foto']['name'])) {
          $update .= ", foto='$newFileName'";
      }
    $update .= " WHERE slider_id='$id'";

    if($connection->query($update) === false) { 
      die($connection->error.__LINE__); 
      echo'Sepertinya Sistem Kami sedang error!';
    } else{
      echo'success';

      if (!empty($_FILES['foto']['name'])) {
        $imageProcess = processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
      }

    }
}else{
  foreach ($error as $key => $values) {            
    echo "$values\n";
  }
}


/** Set Active Slider */
break;
case 'active':
  $id = htmlentities($_POST['id']);
  $active = htmlentities($_POST['active']);
  $update="UPDATE slider SET active='$active' WHERE slider_id='$id'";
  if($connection->query($update) === false) { 
    echo 'error';
    die($connection->error.__LINE__); 
  }
  else{
    echo'success';
  }


/* --------------- Delete ------------*/
break;
case 'delete':
if(isset($_POST['id'])){
  $id       = anti_injection(epm_decode($_POST['id']));
  $query ="SELECT foto from slider where slider_id='$id'";
  $result = $connection->query($query);
    if($result->num_rows > 0){
      $row = $result->fetch_assoc();
      $foto_delete = strip_tags($row['foto']);
      if(file_exists("../../../sw-content/slider/$foto_delete")){
          unlink ("../../../sw-content/slider/".$foto_delete."");
      }
    }

  /* Script Delete Data ------------*/
  $deleted = "DELETE FROM slider WHERE slider_id='$id'";
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