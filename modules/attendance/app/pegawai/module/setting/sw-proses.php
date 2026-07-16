<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:../404');
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

$uploadDir = '../../../sw-content/avatar/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

function resizeImage($resourceType, int $image_width, int $image_height): GdImage {
  $resizeWidth = 400;
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
case 'avatar':
$error = array();

  if (empty($_FILES['file']['name'])) {
      $error[] = 'Unggah Foto avatar';
    } else {
      $fileTmpPath = $_FILES['file']['tmp_name'];
      $fileSize = $_FILES['file']['size'];
      $fileName = basename($_FILES['file']['name']);
      $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

      $sourceProperties = getimagesize($fileTmpPath);
      $uploadImageType  = $sourceProperties[2];
      $sourceImageWidth = $sourceProperties[0];
      $sourceImageHeight = $sourceProperties[1];

      $finfo = finfo_open(FILEINFO_MIME_TYPE);
      $mimeType = finfo_file($finfo, $fileTmpPath);
      finfo_close($finfo);

      // Beri nama unik dan pindahkan file
      $newFileName = 'avatar_'.uniqid('', true).'.jpg';
      $destPath = $uploadDir . $newFileName;
    }

if (empty($error)){
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

      $query_avatar ="SELECT avatar FROM pegawai WHERE pegawai_id='$data_user[pegawai_id]'";
      $result_avatar = $connection->query($query_avatar);
      if($result_avatar->num_rows > 0){
        $data_avatar = $result_avatar->fetch_assoc();
        if(file_exists('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').'') && $row['avatar'] !== 'avatar.jpg'){
          unlink ('../../../sw-content/avatar/'.$data_avatar['avatar'].'');
        }
      }

      $update="UPDATE pegawai SET avatar='$newFileName' WHERE pegawai_id='".htmlspecialchars($data_user['pegawai_id'], ENT_QUOTES, 'UTF-8')."'"; 
      if($connection->query($update) === false) { 
        echo'Sepertinya Sistem Kami sedang error!';
        die($connection->error.__LINE__); 
      } else{
        echo'success';
        if (!empty($_FILES['file']['name'])) {
          $imageProcess = processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
        }
      }
  }else{
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }

break;
}

}