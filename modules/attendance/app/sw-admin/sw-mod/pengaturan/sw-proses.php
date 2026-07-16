<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';



switch (@$_GET['action']){
case 'setting-web':
$error = array();
$fields = [
    'site_name'        => 'Nama Situs',
    'nama_sekolah'     => 'Nama Sekolah',
    'kementrian'       => 'Kementrian',
    'npsn'             => 'NPSN',
    'desa'             => 'Desa',
    'kecamatan'        => 'Kecamatan',
    'kabupaten'        => 'Kabupaten',
    'propinsi'         => 'Propinsi',
    'kepala_sekolah'   => 'Kepala Sekolah',
    'nip_kepala_sekolah'=> 'NIP Kepala Sekolah',
    'site_phone'       => 'Nomor Telepon',
    'site_email'       => 'Email',
    'site_address'     => 'Alamat Situs',
    'site_owner'       => 'Pemilik Situs',
    'site_url'         => 'URL Situs',
];

foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        // khusus site_name -> real_escape_string
        if ($key === 'site_name') {
            $$key = mysqli_real_escape_string($connection, $_POST[$key]);
        }
        // khusus email
        elseif ($key === 'email') {
            $email = anti_injection($_POST[$key]);
            // Validasi format email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error[] = "Format email tidak valid";
            } else {
                if (preg_match('/[\s!#$%^&*()+=]/', $email)) {
                  $error[] = "Email tidak boleh mengandung spasi atau karakter khusus yang tidak valid";
                }
            }
            $$key = $email;
        }
        // default untuk field lainnya
        else {
          $$key = mysqli_real_escape_string($connection, $_POST[$key]);
        }
    }
}

if (empty($error)) { 
      $update = "UPDATE setting 
          SET site_name='$site_name',
              nama_sekolah='$nama_sekolah',
              kementrian='$kementrian',
              npsn='$npsn',
              desa='$desa',
              kecamatan='$kecamatan',
              kabupaten='$kabupaten',
              propinsi='$propinsi',
              kepala_sekolah='$kepala_sekolah',
              nip_kepala_sekolah='$nip_kepala_sekolah',
              site_phone='$site_phone',
              site_email='$site_email',
              site_address='$site_address',
              site_owner='$site_owner',
              site_url='$site_url'
          WHERE site_id=1";
      if($connection->query($update) === false) { 
          echo'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi.!';
          die($connection->error.__LINE__); 
      } else   {
        echo'success';
      }
  }else{        
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }

break;
case 'upload-files':
function uploadFile($file, $destination_folder) {
  $allowed_extensions = ['gif', 'jpg', 'jpeg', 'png'];
  $upload_dir      = '../../../sw-content/';
  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
  }

  $file_name = $file['name'];
  $file_tmp = $file['tmp_name'];
  $file_size = $file['size'];
  $file_error = $file['error'];
  $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if ($file_error !== UPLOAD_ERR_OK) {
        return 'Error uploading file';
    }

    if (!in_array($file_ext, $allowed_extensions)) {
        return 'File extension not allowed. Only gif, jpg, jpeg, png are allowed.';
    }

    if ($file_size > 5000000) { // Limit file size to 5MB
        return 'File size too large. Max 5MB';
    }

    $new_file_name = uniqid('', true) . '.' . $file_ext;
    $destination = $upload_dir.$new_file_name;

    if (move_uploaded_file($file_tmp, $destination)) {
        return $new_file_name;
    } else {
        return 'Failed to move uploaded file.';
    }
}

$query_setting = "SELECT site_logo, site_favicon, site_kop, ttd_kepsek,stempel FROM setting WHERE site_id=1";
$result_setting = $connection->query($query_setting);
if ($result_setting->num_rows > 0) {
    $row = $result_setting->fetch_assoc();
    $upload_dir      = '../../../sw-content/';
    // Hapus s-widodo.com
    if (isset($_FILES['logo'])) {
      if (file_exists($upload_dir .($row['site_logo']??'-.jpg'))) {
        unlink($upload_dir . $row['site_logo']);
      }
    }

    if (isset($_FILES['favicon'])) {
      if (file_exists($upload_dir .($row['site_kop']??'-jpg'))) {
        unlink($upload_dir . $row['site_kop']);
      }
    }

    if (isset($_FILES['kop'])) {
        if (file_exists($upload_dir .($row['site_kop']??'-jpg'))) {
          unlink($upload_dir . $row['site_kop']);
        }
    }

    if (isset($_FILES['ttd'])) {
        if (file_exists($upload_dir .($row['ttd_kepsek']??'-.jpg'))) {
            unlink($upload_dir . $row['ttd_kepsek']);
        }
    }

    if (isset($_FILES['stempel'])) {
        if (file_exists($upload_dir .($row['stempel']??'-.jpg'))) {
            unlink($upload_dir . $row['stempel']);
        }
    }
}

    $uploaded_files = [];
    if (isset($_FILES['logo'])) {
        $logo_file = uploadFile($_FILES['logo'], 'logo');
        if (strpos($logo_file, 'Error') === false) {
            $uploaded_files['logo'] = $logo_file;
        } else {
            echo $logo_file;
            exit;
        }
    }

    if (isset($_FILES['favicon'])) {
        $favicon_file = uploadFile($_FILES['favicon'], 'favicon');
        if (strpos($favicon_file, 'Error') === false) {
            $uploaded_files['favicon'] = $favicon_file;
        } else {
            echo $favicon_file;
            exit;
        }
    }

    if (isset($_FILES['kop'])) {
        $kop_file = uploadFile($_FILES['kop'], 'kop');
        if (strpos($kop_file, 'Error') === false) {
            $uploaded_files['kop'] = $kop_file;
        } else {
            echo $kop_file;
            exit;
        }
    }

    if (isset($_FILES['ttd'])) {
        $ttd_file = uploadFile($_FILES['ttd'], 'ttd');
        if (strpos($ttd_file, 'Error') === false) {
            $uploaded_files['ttd_kepsek'] = $ttd_file;
        } else {
            echo $ttd_file;
            exit;
        }
    }

    if (isset($_FILES['stempel'])) {
        $stempel_file = uploadFile($_FILES['stempel'], 'stempel');
        if (strpos($stempel_file, 'Error') === false) {
            $uploaded_files['stempel'] = $stempel_file;
        } else {
            echo $stempel_file;
            exit;
        }
    }

    // If all files uploaded successfully, update the database
    if (empty($uploaded_files)) {
        echo 'No files uploaded.';
    } else {
      // Build the UPDATE query
      $update_fields = [];
      
      if (isset($uploaded_files['logo'])) {
          $logo = $uploaded_files['logo'];
          $update_fields[] = "site_logo='$logo'";
      }

      if (isset($uploaded_files['favicon'])) {
          $favicon = $uploaded_files['favicon'];
          $update_fields[] = "site_favicon='$favicon'";
      }

      if (isset($uploaded_files['kop'])) {
          $kop = $uploaded_files['kop'];
          $update_fields[] = "site_kop='$kop'";
      }

      if (isset($uploaded_files['ttd'])) {
          $ttd = $uploaded_files['ttd'];
          $update_fields[] = "ttg_kepsek='$ttd'";
      }

      if (isset($uploaded_files['stempel'])) {
          $stempel = $uploaded_files['stempel'];
          $update_fields[] = "stempel='$stempel'";
      }

      $update_query = "UPDATE setting SET " . implode(", ", $update_fields) . " WHERE site_id=1";
      if (mysqli_query($connection, $update_query)) {
          echo 'success';
      } else {
          echo 'Error updating database: ' . mysqli_error($connection);
      }
  }

/** Setting Absensi */
break;
case 'setting-absensi':
$error = array();

$fields = [
    'timezone'            => 'Timezon',
    'tipe_absen_siswa'    => 'Jenis Absen siswa',
    'tipe_absen_pegawai'  => 'Jenis Absen Pegawai',
    'tipe_absen_layar'    => 'Jenis Absen dilayar',
    'tipe_absen_layar_pegawai' => 'Jenis Absen layar pegawai',
];

foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
      $$key = mysqli_real_escape_string($connection, $_POST[$key]);
        
    }
}

if (empty($error)) { 

    $update="UPDATE setting SET
      tipe_absen_siswa='$tipe_absen_siswa',
      tipe_absen_pegawai='$tipe_absen_pegawai',
      tipe_absen_layar='$tipe_absen_layar',
      tipe_absen_layar_pegawai='$tipe_absen_layar_pegawai',
      timezone='$timezone'
    WHERE site_id=1";
    if($connection->query($update) === false) { 
        echo'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi!';
        die($connection->error.__LINE__); 
    } else   {
      echo'success';
    }
}else{        
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}


/** Setting Server */
break;
case 'setting-server':
  $error = array();

  if (empty($_POST['gmail_host'])) {
    $error[] = 'Host Email tidak boleh kosong';
  } else {
    $gmail_host = htmlentities(strip_tags($_POST['gmail_host']));
  }

  if (empty($_POST['gmail_username'])) { 
        $error[] = 'Username/Email tidak boleh kosong';
    } else { 
      if (!filter_var($_POST['gmail_username'], FILTER_VALIDATE_EMAIL)) {
        $error[] = "Email yang Anda masukan tidak valid"; 
      }else{
        $gmail_username = htmlentities(strip_tags($_POST['gmail_username']));
      }
  }

  if (empty($_POST['gmail_password'])) { 
    $error[] = 'No. Telp tidak boleh kosong';
  } else {
    $gmail_password = anti_injection($_POST['gmail_password']);
  }

  if (empty($_POST['gmail_port'])) { 
    $error[] = 'Alamat tidak boleh kosong';
  } else {
    $gmail_port= htmlentities(htmlspecialchars($_POST['gmail_port']));
  }

  if (empty($_POST['google_client_id'])) { 
        $error[] = 'Client ID tidak boleh kosong';
    } else {
      $google_client_id = htmlentities(strip_tags($_POST['google_client_id']));
  }

  if (empty($_POST['google_client_secret'])) { 
      $error[] = 'Secret tidak boleh kosong';
  } else {
    $google_client_secret = htmlentities(strip_tags($_POST['google_client_secret']));
  }

  if (empty($_POST['gmail_active'])) { 
    $gmail_active = 'N';
  } else {
    $gmail_active = 'Y';
  }

  if (empty($_POST['google_client_active'])) { 
    $google_client_active = 'N';
  } else {
    $google_client_active = 'Y';
  }
if (empty($error)) { 
    $update="UPDATE setting SET gmail_host='$gmail_host',
                gmail_username='$gmail_username',
                gmail_password='$gmail_password',
                gmail_port='$gmail_port',
                gmail_active='$gmail_active',
                google_client_id='$google_client_id',
                google_client_secret='$google_client_secret',
                google_client_active='$google_client_active' WHERE site_id=1";
          if($connection->query($update) === false) { 
             echo'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi.!';
             die($connection->error.__LINE__); 
          } else   {
            echo'success';
          }
  }else{        
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
}


/** Setting Api WhatsApp */
break;
case 'setting-whatsapp':
   $error = array();

  if (empty($_POST['whatsapp_phone'])) {
    $error[] = 'No. WhatsApp tidak boleh kosong';
  } else {
    $whatsapp_phone = htmlentities(strip_tags($_POST['whatsapp_phone']));
  }

  if (empty($_POST['whatsapp_token'])) { 
    $error[] = 'Api key/Token tidak boleh kosong';
  } else {
    $whatsapp_token = anti_injection($_POST['whatsapp_token']);
  }

  if (empty($_POST['secret_key'])) { 
    $secret_key = '';
  } else {
    $secret_key = anti_injection($_POST['secret_key']);
  }

  if (empty($_POST['whatsapp_domain'])) { 
    $error[] = 'Domain Server tidak boleh kosong';
  } else {
    $whatsapp_domain = htmlentities(htmlspecialchars($_POST['whatsapp_domain']));
  }

  if (empty($_POST['whatsapp_template'])) { 
    $error[] = 'Template tidak boleh kosong';
  } else {
    $whatsapp_template = trim($_POST['whatsapp_template']);
  }

  if (empty($_POST['tipe'])) { 
    $error[] = 'Tipe tidak boleh kosong';
  } else {
    $whatsapp_tipe = trim($_POST['tipe']);
  }

  if (empty($_POST['whatsapp_active'])) { 
    $active = 'N';
  } else {
    $active = 'Y';
  }
  if (empty($error)) { 
    $update = "UPDATE setting SET 
      whatsapp_phone='$whatsapp_phone',
      whatsapp_token='$whatsapp_token',
      secret_key='$secret_key',
      whatsapp_domain='$whatsapp_domain',
      whatsapp_tipe='$whatsapp_tipe',
      whatsapp_template='$whatsapp_template',
      whatsapp_active='$active' WHERE site_id=1";
    if($connection->query($update) === false) { 
      echo'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi!';
      die($connection->error.__LINE__); 
    } else   {
      echo'success';
    }

  }else{        
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }



/** Setting Absensi */
break;
case 'setting-absensi':
  $error = array();

  if (empty($_POST['site_company'])) { 
        $error[] = 'Nama Perusahaan tidak boleh kosong';
    } else {
      $site_company = htmlspecialchars(ucfirst($_POST['site_company']));
    }

  if (empty($_POST['site_pimpinan'])) { 
        $error[] = 'Pimpinan tidak boleh kosong';
    } else { 
      $site_pimpinan = anti_injection($_POST['site_pimpinan']); 
  }

  if (empty($_POST['site_kota'])) { 
    $error[] = 'Kota tidak boleh kosong';
  } else {
    $site_kota  = htmlspecialchars($_POST['site_kota']);
  }

if (empty($error)) { 
  
    $update="UPDATE setting SET site_company='$site_company',
          site_pimpinan ='$site_pimpinan',
          site_kota='$site_kota' WHERE site_id=1";
    if($connection->query($update) === false) { 
        echo'Pengaturan tidak dapat disimpan, coba ulangi beberapa saat lagi!';
        die($connection->error.__LINE__); 
    } else   {
      echo'success';
    }

}else{        
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}

/** Backup Database */
break;
case 'backup-database':
  $error = array();

  if (empty($_POST['password'])) { 
      $error[] = 'Password tidak boleh kosong';
    } else {
      $password_hash = mysqli_real_escape_string($connection,$_POST['password']);
  }

  if (empty($error)){

    if(password_verify($password_hash,$current_user['password'])) {

        /** Jika berhasil maka backup database */
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWD, DB_NAME);
        if ($mysqli->connect_error) {
            echo json_encode(['status' => 'error', 'message' => 'Koneksi gagal: ' . $mysqli->connect_error]);
            exit;
        }
        $mysqli->set_charset("utf8");

        // Ambil semua tabel
        $content = '';
        $tables = [];
        $queryTables = $mysqli->query("SHOW TABLES");
        while ($row = $queryTables->fetch_row()) {
            $tables[] = $row[0];
        }

        // Generate SQL
        foreach ($tables as $table) {
            $result = $mysqli->query("SELECT * FROM `$table`");
            $fields_amount = $result->field_count;
            $rows_num = $result->num_rows;

            $res = $mysqli->query("SHOW CREATE TABLE `$table`");
            $TableMLine = $res->fetch_row();
            $content .= "\n\n" . $TableMLine[1] . ";\n";

            $st_counter = 0;
            while ($row = $result->fetch_row()) {
                if ($st_counter % 100 == 0) {
                    $content .= "\nINSERT INTO `$table` VALUES";
                }

                $content .= "\n(";
                for ($j = 0; $j < $fields_amount; $j++) {
                    $row[$j] = addslashes($row[$j] ?? '');
                    $row[$j] = str_replace(["\r", "\n"], ["\\r", "\\n"], $row[$j]);
                    $content .= isset($row[$j]) ? '"' . $row[$j] . '"' : '""';
                    if ($j < ($fields_amount - 1)) {
                        $content .= ',';
                    }
                }
                $content .= ")";

                $st_counter++;
                if ($st_counter % 100 == 0 || $st_counter == $rows_num) {
                    $content .= ";";
                } else {
                    $content .= ",";
                }
            }
        }

        // Simpan file
        $backup_folder ='../../backup';
        if (!is_dir($backup_folder)) {
            mkdir($backup_folder, 0755, true);
        }

        $filename = DB_NAME . '_' . date('Y-m-d_H-i-s') . '.sql';
        $filepath = $backup_folder . '/' . $filename;

        $header = "-- Backup Database\n-- Host: " . DB_HOST . "\n-- Tanggal: " . date("Y-m-d H:i:s") . "\n\n";

        if (file_put_contents($filepath, $header . $content)) {
            echo json_encode(['status' => 'success', 'filename' => $filename]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Gagal membuat file backup.']);
        }
    }else{
      echo json_encode(['status' => 'error', 'message' => 'Pasword yang tidak sesuai.']);
    }
}else{
    foreach ($error as $key => $values) {   
      echo json_encode(['status' => 'error', 'message' => $values]);         
    }
}


/** Download dtabase */
break;
case'download':
$filename = $_GET['file'] ?? '';
$filepath = '../../backup/' . basename($filename);

if (!file_exists($filepath)) {
    http_response_code(404);
    exit('File tidak ditemukan.');
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
header('Content-Length: ' . filesize($filepath));
header('Pragma: public');
header('Cache-Control: must-revalidate');
flush();
readfile($filepath);

// Hapus setelah berhasil dikirim
unlink($filepath);
exit;

/** Backup Homepage */
break;
case 'backup-folder':
  
// Cek input
if (empty($_POST['folders']) || !is_array($_POST['folders'])) {
    die("Tidak ada folder yang dipilih.");
}

// Mapping label → path sebenarnya
$basePath = '../../../sw-content';
//$basePath = realpath(__DIR__ . '../../../sw-content'); // Perbaiki path
$allowedFolders = [
    'absen' => $basePath . '/absen',
    'qrcode' => $basePath . '/qrcode',
];

// Filter input
$selected = array_intersect(array_keys($allowedFolders), $_POST['folders']);
if (count($selected) === 0) {
    die("Folder yang dipilih tidak valid.");
}

// Buat folder backup jika belum ada
$backupFolder = '../../backup';
if (!is_dir($backupFolder)) {
    mkdir($backupFolder, 0755, true);
}

// Nama file ZIP
$zipFileName = 'backup_' . date('Y-m-d_H-i-s') . '.zip';
$zipFilePath = $backupFolder . '/' . $zipFileName;

// Buat file ZIP
$zip = new ZipArchive();
if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    die("Gagal membuat file zip.");
}

// Tambahkan semua folder terpilih ke dalam ZIP
foreach ($selected as $label) {
    $folderPath = $allowedFolders[$label];
    $realBase = realpath($folderPath);
    if (!$realBase || !is_dir($realBase)) continue;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($realBase, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $filePath = realpath($file);
        $relativePath = $label . '/' . substr($filePath, strlen($realBase) + 1);
        if (is_dir($file)) {
            $zip->addEmptyDir($relativePath);
        } else {
            $zip->addFile($filePath, $relativePath);
        }
    }
}

$zip->close();
// Kirim file ZIP ke browser
if (file_exists($zipFilePath)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . basename($zipFilePath) . '"');
    header('Content-Length: ' . filesize($zipFilePath));
    flush();
    readfile($zipFilePath);
    unlink($zipFilePath); // hapus setelah diunduh
    exit;
} else {
    die("File zip tidak ditemukan.");
}


break;
case'hapus-foto-absen':
$folder = 'absen';
$target_bulan = '08'; // Bulan yang ingin dihapus (format dua digit)
$target_tahun = '2025'; // Tahun target

if (is_dir($folder)) {
    if ($handle = opendir($folder)) {
        while (false !== ($file = readdir($handle))) {
            $filePath = $folder . DIRECTORY_SEPARATOR . $file;

            // Abaikan . dan ..
            if ($file == "." || $file == "..") continue;

            // Cocokkan dengan pola nama file
            // Contoh nama file: in_1234_2025-08-10.png
            if (preg_match('/in_\d+_(\d{4})-(\d{2})-\d{2}\.png$/', $file, $matches)) {
                $file_tahun = $matches[1]; // 2025
                $file_bulan = $matches[2]; // 08

                if ($file_tahun === $target_tahun && $file_bulan === $target_bulan) {
                    if (unlink($filePath)) {
                        echo "Dihapus: $file\n";
                    } else {
                        echo "Gagal hapus: $file\n";
                    }
                }
            }
        }
        closedir($handle);
    } else {
        echo "Tidak bisa membuka folder.";
    }
} else {
    echo "Folder tidak ditemukan.";
}

break;
}}