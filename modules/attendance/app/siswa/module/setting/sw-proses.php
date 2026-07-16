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
    $error[] = 'Unggah foto avatar terlebih dahulu.';
  } else {
      $fileTmpPath = $_FILES['file']['tmp_name'];
      $fileSize = $_FILES['file']['size'];
      $fileName = basename($_FILES['file']['name']);
      $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

      // Validasi ekstensi file
      if (!in_array($fileExt, $allowedTypes)) {
          $error[] = "Hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diperbolehkan!";
      }

      // Validasi ukuran file
      if ($fileSize > $maxFileSize) {
          $error[] = "Ukuran file terlalu besar, maksimal 5MB!";
      }

      // Cek apakah file benar-benar gambar
      $sourceProperties = @getimagesize($fileTmpPath);
      if ($sourceProperties === false) {
          $error[] = "File yang diunggah bukan gambar yang valid.";
      } else {
          $uploadImageType   = $sourceProperties[2];
          $sourceImageWidth  = $sourceProperties[0];
          $sourceImageHeight = $sourceProperties[1];

          // Cek apakah tipe gambar didukung oleh GD
          $supportedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP];
          if (!in_array($uploadImageType, $supportedTypes)) {
              $error[] = "Format gambar tidak didukung oleh server ini.";
          }
      }

      // Siapkan nama file tujuan jika semua valid
      $newFileName = 'avatar_' . uniqid('', true) . '.jpg';
      $destPath = $uploadDir . $newFileName;
  }


if (empty($error)){

      $query_avatar ="SELECT avatar FROM user WHERE user_id='".htmlspecialchars($data_user['user_id'], ENT_QUOTES, 'UTF-8')."'";
      $result_avatar = $connection->query($query_avatar);
      if($result_avatar->num_rows > 0){
        $data_avatar = $result_avatar->fetch_assoc();
        if(file_exists('../../../sw-content/avatar/'.strip_tags($row['avatar']??'-').'') && $row['avatar'] !== 'avatar.jpg'){
          unlink ('../../../sw-content/avatar/'.$data_avatar['avatar'].'');
        }
      }

      $update="UPDATE user SET avatar='$newFileName' WHERE user_id='".htmlspecialchars($data_user['user_id'], ENT_QUOTES, 'UTF-8')."'"; 
      if($connection->query($update) === false) { 
        echo'Sepertinya Sistem Kami sedang error!';
        die($connection->error.__LINE__); 
      } else{
        echo'success';
        try {
            if (!empty($_FILES['file']['name'])) {
                processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
            }

        } catch (RuntimeException $e) {
            echo 'Error saat memproses gambar: ' . $e->getMessage();
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