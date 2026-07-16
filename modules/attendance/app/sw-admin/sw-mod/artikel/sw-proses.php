<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:../login/');
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

$uploadDir = '../../../sw-content/artikel/';
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
  if (empty($_POST['judul'])) {
    $error[] = 'Judul tidak boleh kosong';
  } else {
    $judul = anti_injection($_POST['judul']);
    $domain = seo_title($judul);
  }

  if (empty($_POST['deskripsi'])) {
      $error[] = 'Deskripsi tidak boleh kosong';
    } else {
      $deskripsi = mysqli_real_escape_string($connection,$_POST['deskripsi']);
  }

  if (empty($_POST['kategori'])) {
    $error[] = 'Kategori tidak boleh kosong';
  } else {
    $kategori = anti_injection($_POST['kategori']);
  }

  if (empty($_POST['date'])) {
      $error[] = 'Tanggal tidak boleh kosong';
    } else {
      $date = date('Y-m-d', strtotime($_POST['date']));
  }

  if (empty($_POST['time'])) {
    $error[] = 'Waktu tidak boleh kosong';
  } else {
    $time = strip_tags($_POST['time']);
  }

  if (empty($_FILES['foto']['name'])) {
    $error[] = 'Thumbnail belum diunggah';
  } else {
    $fileTmpPath = $_FILES['foto']['tmp_name'];
    $fileSize = $_FILES['foto']['size'];
    $fileName = basename($_FILES['foto']['name']);
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $sourceProperties = getimagesize($fileTmpPath);
    $uploadImageType  = $sourceProperties[2];
    $sourceImageWidth = $sourceProperties[0];
    $sourceImageHeight = $sourceProperties[1];

    // Beri nama unik dan pindahkan file
    $newFileName = uniqid("file_", true) . "." . $fileExt;
    $destPath = $uploadDir . $newFileName;
  }

  if (empty($_POST['active'])) {
    $active ='N';
  } else {
    $active ='Y';
  }

  if (empty($error)){
    // Validasi ekstensi file
    if (!in_array($fileExt, $allowedTypes)) {
        die("Hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diperbolehkan!");
    }

    // Validasi ukuran file
    if ($fileSize > $maxFileSize) {
        die("Ukuran file terlalu besar, Maksimal Size 5MB!");
    }

    $add ="INSERT INTO artikel(penerbit,
                judul,
                domain,
                deskripsi,
                foto,
                kategori,
                time,
                date,
                statistik,
                active) values('$current_user[fullname]',
                '$judul',
                '$domain',
                '$deskripsi',
                '$newFileName',
                '$kategori',
                '$time',
                '$date',
                '0',
                '$active')";
    if($connection->query($add) === false) { 
          die($connection->error.__LINE__); 
          echo'Data tidak berhasil disimpan!';
      } else{
          echo'success';
          $imageProcess = processImage($uploadImageType, $fileTmpPath, $sourceImageWidth, $sourceImageHeight, $destPath);
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
      $id = anti_injection(epm_decode($_POST['id']));
    }

    if (empty($_POST['judul'])) {
      $error[] = 'Judul tidak boleh kosong';
    } else {
      $judul = anti_injection($_POST['judul']);
      $domain = seo_title($judul);
    }

    if (empty($_POST['deskripsi'])) {
        $error[] = 'Deskripsi tidak boleh kosong';
      } else {
        $deskripsi = mysqli_real_escape_string($connection,$_POST['deskripsi']);
    }

    if (empty($_POST['kategori'])) {
      $error[] = 'Kategori tidak boleh kosong';
    } else {
      $kategori = anti_injection($_POST['kategori']);
    }

    if (empty($_POST['date'])) {
        $error[] = 'Tanggal tidak boleh kosong';
      } else {
        $date = date('Y-m-d', strtotime($_POST['date']));
    }

    if (empty($_POST['time'])) {
      $error[] = 'Waktu tidak boleh kosong';
    } else {
      $time = strip_tags($_POST['time']);
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

      // Beri nama unik dan pindahkan file
      $newFileName = uniqid("file_", true) . "." . $fileExt;
      $destPath = $uploadDir . $newFileName;
    }

    if (empty($_POST['active'])) {
      $active ='N';
    } else {
      $active ='Y';
    }

  if (empty($error)) {
      if (!empty($_FILES['foto']['name'])) {
          if (!in_array($fileExt, $allowedTypes)) {
            die("Foto  hanya file JPG, JPEG, PNG, GIF, dan WEBP yang diperbolehkan!");
          }

          // Validasi ukuran file
          if ($fileSize > $maxFileSize) {
            die("Foto  ukuran file terlalu besar, maksimal ukuran 5MB!");
          }

      }

      $update="UPDATE artikel SET judul='$judul',
            domain='$domain',
            deskripsi='$deskripsi',
            kategori='$kategori',
            time='$time',
            date='$date',
            active='$active'";
      if (!empty($_FILES['foto']['name'])) {
        $update .= ", foto='$newFileName'";
      }
        
      $update .= "WHERE artikel_id='$id'"; 
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
  

/* --------------- Delete ------------*/
break;
case 'delete':
  $id       = anti_injection(epm_decode($_POST['id']));
  $query ="SELECT foto from artikel where artikel_id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $row = $result->fetch_assoc();
    if($row['foto'] == NULL && !file_exists('../../../sw-content/artikel/'.$row['foto'].'')){
      unlink ('../../../sw-content/artikel/'.$row['foto'].''); 
    }
  }

  $deleted  = "DELETE FROM artikel WHERE artikel_id='$id'";
  if($connection->query($deleted) === true) { 
    echo'success';
  } else { 
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }


/** Tambah Kategori */
break;
case 'add-kategori':
$error = array();

    if (empty($_POST['title'])) {
      $error[] = 'Judul tidak boleh kosong';
    } else {
      $title    = anti_injection($_POST['title']);
      $seotitle = seo_title($title);
    }

  if (empty($error)){
    $query_kategori="SELECT title from kategori WHERE title='$title'";
    $result_kategori = $connection->query($query_kategori);
    if(!$result_kategori->num_rows > 0){

        $add ="INSERT INTO kategori (title,seotitle) values('$title','$seotitle')";
        if($connection->query($add) === false) { 
              die($connection->error.__LINE__); 
              echo'error/Data tidak berhasil disimpan!';
          } else{
              $query="SELECT * from kategori order by title ASC";
              $result = $connection->query($query);
              while($row = $result->fetch_assoc()) { 
                  echo'<option value="'.$row['seotitle'].'">'.strip_tags($row['title']).'</option>';
              }
          }
        }else{
          echo'error/Kategori '.$title.' sudah ada';
        }
  }else{           
      foreach ($error as $key => $values) {   
        echo'error/';         
        echo"$values\n";
      }
  }

      

break;
}}