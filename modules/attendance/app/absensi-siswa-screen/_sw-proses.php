<?PHP require_once'../sw-library/sw-config.php';
require_once'../sw-library/sw-function.php';

$uploadDir = '../sw-content/absen/';
if (!is_dir($uploadDir)) {
  mkdir($uploadDir, 0755, true);
}

// ===== Realtime (SSE) helper: sentuh flag agar layar update =====
function triggerRealtimeUpdate(): void {
  $flag = __DIR__ . '/realtime.flag';
  @file_put_contents($flag, (string)time());
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

$data_jam = getJam($connection, $hari_ini, 'Siswa');


switch (@$_GET['action']){
case 'absen':
 $error = [];
  $data_post = [
      'qrcode' => 'Qrcode',
      'latitude' => 'Latitude',
  ];

  // Validasi input
  foreach ($data_post as $key => $value) {
      if (!isset($_POST[$key]) || ($_POST[$key] === '' && $_POST[$key] !== '0')) {
          $error[] = ucfirst($key) . ' tidak boleh kosong';
      } else {
          $$key = htmlentities(strip_tags($_POST[$key]));
      }
  }

  
  if (empty($_FILES['img']['tmp_name'])) {
    $error[] = 'Foto tidak dapat diunggah!';
  } else {
    $file_tmp  = $_FILES['img']['tmp_name'];
    $source = imagecreatefromjpeg($file_tmp);
    if (!$source) {
        $error[] = 'Gagal membuat resource gambar!';
    } else {
        $source_width  = imagesx($source);
        $source_height = imagesy($source);
        $ratio         = $source_height / $source_width;
        $new_width     = 350;
        $new_height    = $ratio * $new_width;
        $thumb = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $new_width, $new_height, $source_width, $source_height);
    }
  }

  if($row_site['tipe_absen_layar'] =='qrcode'){
    $filter ="WHERE user.nisn='".htmlspecialchars($qrcode, ENT_QUOTES, 'UTF-8')."' LIMIT 1";

  }elseif($row_site['tipe_absen_layar'] =='rfid'){
    $filter ="WHERE user.rfid='".htmlspecialchars($qrcode, ENT_QUOTES, 'UTF-8')."' LIMIT 1";
  }

if (empty($error)){
  $query_user ="SELECT user.user_id,user.telp,user.nama_lengkap,lokasi.*
    FROM user LEFT JOIN lokasi ON user.lokasi = lokasi.lokasi_id $filter";
  $result_user = $connection->query($query_user);
  if ($result_user->num_rows > 0){
      $data_user = $result_user->fetch_assoc();
      $penerima   = $data_user['telp'];
      $user_id    = $data_user['user_id'];

      if (!$data_jam) {
          die('Jam Abensi tidak ditemukan!');
          break;
      }else{
          $status_in = ($time_sekarang <= $data_jam['jam_telat']) ? 'Tepat Waktu' : 'Terlambat';
          $status_out = ($time_sekarang < $data_jam['jam_pulang']) ? 'Pulang Cepat' : 'Tepat Waktu';

          // Hitung waktu absen masuk (60 menit sebelum jam mulai)
          $absen_masuk     = strtotime(''.$data_jam['jam_masuk'].' - 60 minute');
          $absen_masuk     = date('H:i:s', $absen_masuk);

          // Hitung waktu absen pulang (60 menit setelah jam selesai)
          $absen_pulang = strtotime(''.$data_jam['jam_pulang'].' -60 minute');
          $absen_pulang = date('H:i:s', $absen_pulang);
            
          if ($time_sekarang >= $absen_masuk && $time_sekarang <= $absen_pulang) {
  
              // Tambah Absen masuk
              $watermark = "".strip_tags($data_user['nama_lengkap'])."\n".$time_sekarang." - ".tanggal_ind($date)."";
              $foto = 'in_' .$data_user['user_id']. '_' . date('Y-m-d') . '_' . uniqid() . '.png';
              $filename = '../sw-content/absen/'.$foto.'';

              // Tambahkan Notifikasi dan Proses Absen Masuk
              if ($row_site['whatsapp_active'] == 'Y') {
                $pesan = str_replace(
                    ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
                    [
                        $data_user['nama_lengkap'],
                        tanggal_ind($date),
                        'MASUK',
                        ''.$data_jam['jam_masuk'].' - '.$data_jam['jam_pulang'],
                        $time_absen,
                        $status_in,
                        'https://www.google.com/maps/place/' . $latitude
                    ],
                    $row_site['whatsapp_template']
                );

                if($whatsapp_tipe =='POST'){
                  $isipesan = $pesan;
                }else{
                  $pesan = str_replace(["\r\n", "\n"], "%0A", $pesan);
                  $isipesan = str_replace(" ", "%20", $pesan);
                }
              }

              // Proses Absen Masuk
              $query_absensi = "SELECT absen_id,absen_in FROM absen WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_user['user_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
              $result_absensi = $connection->query($query_absensi);
              if ($result_absensi->num_rows == 0) {
                  $add_absen = "INSERT INTO absen (
                        user_id,
                        tanggal, 
                        lokasi_id,
                        jam_masuk, 
                        jam_toleransi, 
                        jam_pulang, 
                        absen_in, 
                        foto_in,
                        status_masuk,
                        map_in,
                        kehadiran,
                        radius) VALUES (
                        '{$data_user['user_id']}',
                        '$date',
                        '{$data_user['lokasi_id']}',
                        '{$data_jam['jam_masuk']}',
                        '{$data_jam['jam_telat']}',
                        '{$data_jam['jam_pulang']}',
                        '$time_absen',
                        '$foto',
                        '$status_in',
                        '$latitude',
                        'Hadir',
                        '0')";
                  if ($connection->query($add_absen) === false) {
                      echo 'Sepertinya Sistem Kami sedang error!';
                      die($connection->error . __LINE__);
                  } else {
                      triggerRealtimeUpdate();
                      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . "!";
                      // Simpan Foto Absen Masuk
                       addTextWatermark($thumb, $watermark, $filename);	

                      if($row_site['whatsapp_active'] =='Y'){
                        if($whatsapp_tipe =='POST'){
                          KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                        }else{
                          KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                        }
                      }
                  }
              }else{
                  echo 'Anda sudah absen masuk hari ini!';
              }


          }    // Tambah Absen pulang
          elseif ($time_sekarang >= $absen_pulang) {  

              $watermark = "".strip_tags($data_user['nama_lengkap'])."\n".$time_sekarang." - ".tanggal_ind($date)."";
              $foto = 'out_' .$data_user['user_id']. '_' . date('Y-m-d') . '_' . uniqid() . '.png';
              $filename = '../sw-content/absen/'.$foto.'';

              // Tambahkan Notifikasi dan Proses Absen Masuk
              if ($row_site['whatsapp_active'] == 'Y') {
                $pesan = str_replace(
                    ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
                    [
                        $data_user['nama_lengkap'],
                        tanggal_ind($date),
                        'PULANG',
                        ''.$data_jam['jam_masuk'].' - '.$data_jam['jam_pulang'],
                        $time_absen,
                        $status_out,
                        'https://www.google.com/maps/place/' . $latitude
                    ],
                    $row_site['whatsapp_template']
                );

                if($whatsapp_tipe =='POST'){
                  $isipesan = $pesan;
                }else{
                  $pesan = str_replace(["\r\n", "\n"], "%0A", $pesan);
                  $isipesan = str_replace(" ", "%20", $pesan);
                }
              }

              $query_absensi = "SELECT absen_id,absen_in,absen_out FROM absen WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_user['user_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
              $result_absensi = $connection->query($query_absensi);
              if ($result_absensi->num_rows > 0) {
              $data_absen = $result_absensi->fetch_assoc();

                  $update ="UPDATE absen SET absen_out='$time_absen',
                        foto_out='$foto',
                        status_pulang='$status_out',
                        map_out='$latitude',
                        radius_out='0'
                        WHERE tanggal='$date' AND 
                        user_id='".htmlspecialchars($data_user['user_id'], ENT_QUOTES, 'UTF-8')."' 
                        AND absen_id='$data_absen[absen_id]'";
                  
                  if ($connection->query($update) === false) {
                      echo 'Sepertinya Sistem Kami sedang error!';
                      die($connection->error . __LINE__);
                  } else {
                      triggerRealtimeUpdate();
                      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Pulang telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . ".";
                     addTextWatermark($thumb, $watermark, $filename);	

                      if($row_site['whatsapp_active'] =='Y'){
                        if($whatsapp_tipe =='POST'){
                          KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                        }else{
                          KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                        }
                      }
                  }
            
            }else{
                /** Notifikasi belum ada data absen masuk */
                echo'Data Absensi Anda tidak ditemukan, Silahkan absen masuk terlebih dahulu!';
            }

          } else {
              echo'Waktu absen tidak valid!';
          }

      }

  } else {
      echo'Data pengguna tidak ditemukan!';
  }
}else{           
  echo implode("\n", $error);
}


break;
case 'absen-webcame':
 $error = [];
  $data_post = [
      'qrcode' => 'Qrcode',
      'latitude' => 'Latitude',
  ];

  // Validasi input
  foreach ($data_post as $key => $value) {
      if (!isset($_POST[$key]) || ($_POST[$key] === '' && $_POST[$key] !== '0')) {
          $error[] = ucfirst($key) . ' tidak boleh kosong';
      } else {
          $$key = htmlentities(strip_tags($_POST[$key]));
      }
  }

  

if (empty($error)){
  $query_user ="SELECT user.user_id,user.telp,user.nama_lengkap,lokasi.*
    FROM user LEFT JOIN lokasi ON user.lokasi = lokasi.lokasi_id WHERE user.nisn='".htmlspecialchars($qrcode, ENT_QUOTES, 'UTF-8')."' LIMIT 1";
  $result_user = $connection->query($query_user);
  if ($result_user->num_rows > 0){
      $data_user = $result_user->fetch_assoc();
      $penerima   = $data_user['telp'];
      $user_id    = $data_user['user_id'];

      if (!$data_jam) {
          die('Jam Abensi tidak ditemukan!');
          break;
      }else{
          $status_in = ($time_sekarang <= $data_jam['jam_telat']) ? 'Tepat Waktu' : 'Telat';
          $status_out = ($time_sekarang < $data_jam['jam_pulang']) ? 'Pulang Cepat' : 'Tepat Waktu';

          // Hitung waktu absen masuk (60 menit sebelum jam mulai)
          $absen_masuk     = strtotime(''.$data_jam['jam_masuk'].' - 60 minute');
          $absen_masuk     = date('H:i:s', $absen_masuk);

          // Hitung waktu absen pulang (60 menit setelah jam selesai)
          $absen_pulang = strtotime(''.$data_jam['jam_pulang'].' -60 minute');
          $absen_pulang = date('H:i:s', $absen_pulang);
            
          if ($time_sekarang >= $absen_masuk && $time_sekarang <= $absen_pulang) {
  

              // Tambahkan Notifikasi dan Proses Absen Masuk
              if ($row_site['whatsapp_active'] == 'Y') {
                $pesan = str_replace(
                    ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
                    [
                        $data_user['nama_lengkap'],
                        tanggal_ind($date),
                        'MASUK',
                        ''.$data_jam['jam_masuk'].' - '.$data_jam['jam_pulang'],
                        $time_absen,
                        $status_in,
                        'https://www.google.com/maps/place/' . $latitude
                    ],
                    $row_site['whatsapp_template']
                );

                if($whatsapp_tipe =='POST'){
                  $isipesan = $pesan;
                }else{
                  $pesan = str_replace(["\r\n", "\n"], "%0A", $pesan);
                  $isipesan = str_replace(" ", "%20", $pesan);
                }
              }

              // Proses Absen Masuk
              $query_absensi = "SELECT absen_id,absen_in FROM absen WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_user['user_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
              $result_absensi = $connection->query($query_absensi);
              if ($result_absensi->num_rows == 0) {
                  $add_absen = "INSERT INTO absen (
                        user_id,
                        tanggal, 
                        lokasi_id,
                        jam_masuk, 
                        jam_toleransi, 
                        jam_pulang, 
                        absen_in, 
                        status_masuk,
                        map_in,
                        kehadiran,
                        radius) VALUES (
                        '{$data_user['user_id']}',
                        '$date',
                        '{$data_user['lokasi_id']}',
                        '{$data_jam['jam_masuk']}',
                        '{$data_jam['jam_telat']}',
                        '{$data_jam['jam_pulang']}',
                        '$time_absen',
                        '$status_in',
                        '$latitude',
                        'Hadir',
                        '0')";
                  if ($connection->query($add_absen) === false) {
                      echo 'Sepertinya Sistem Kami sedang error!';
                      die($connection->error . __LINE__);
                  } else {
                      triggerRealtimeUpdate();
                      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . "!";
   
                      if($row_site['whatsapp_active'] =='Y'){
                        if($whatsapp_tipe =='POST'){
                          KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                        }else{
                          KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                        }
                      }
                  }
              }else{
                  echo 'Anda sudah absen masuk hari ini!';
              }


          }    // Tambah Absen pulang
          elseif ($time_sekarang >= $absen_pulang) {  

              // Tambahkan Notifikasi dan Proses Absen Masuk
              if ($row_site['whatsapp_active'] == 'Y') {
                $pesan = str_replace(
                    ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
                    [
                        $data_user['nama_lengkap'],
                        tanggal_ind($date),
                        'PULANG',
                        ''.$data_jam['jam_masuk'].' - '.$data_jam['jam_pulang'],
                        $time_absen,
                        $status_out,
                        'https://www.google.com/maps/place/' . $latitude
                    ],
                    $row_site['whatsapp_template']
                );

                if($whatsapp_tipe =='POST'){
                  $isipesan = $pesan;
                }else{
                  $pesan = str_replace(["\r\n", "\n"], "%0A", $pesan);
                  $isipesan = str_replace(" ", "%20", $pesan);
                }
              }

              $query_absensi = "SELECT absen_id,absen_in,absen_out FROM absen WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_user['user_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
              $result_absensi = $connection->query($query_absensi);
              if ($result_absensi->num_rows > 0) {
              $data_absen = $result_absensi->fetch_assoc();
      
                  $update ="UPDATE absen SET absen_out='$time_absen',
                        status_pulang='$status_out',
                        map_out='$latitude',
                        radius_out='0'
                        WHERE tanggal='$date' AND 
                        user_id='".htmlspecialchars($data_user['user_id'], ENT_QUOTES, 'UTF-8')."' 
                        AND absen_id='$data_absen[absen_id]'";
                  
                  if ($connection->query($update) === false) {
                      echo 'Sepertinya Sistem Kami sedang error!';
                      die($connection->error . __LINE__);
                  } else {
                      triggerRealtimeUpdate();
                      echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Pulang telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . ".";

                      if($row_site['whatsapp_active'] =='Y'){
                        if($whatsapp_tipe =='POST'){
                          KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                        }else{
                          KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                        }
                      }
                  }
            
            }else{
                /** Notifikasi belum ada data absen masuk */
                echo'Data Absensi Anda tidak ditemukan, Silahkan absen masuk terlebih dahulu!';
            }

          } else {
              echo'Waktu absen tidak valid!';
          }
      }
  } else {
      echo'Data pengguna tidak ditemukan!';
  }
}else{           
  echo implode("\n", $error);
}



/** Data Absensi */
break;
case 'data-absensi':

  $query_absen ="SELECT absen.*,user.nama_lengkap,user.kelas FROM absen 
  LEFT JOIN user ON absen.user_id=user.user_id WHERE absen.tanggal='$date' ORDER BY absen_in DESC, absen_out DESC LIMIT 25";
  $result_absen = $connection->query($query_absen);
  if($result_absen->num_rows > 0){
        while($data_absen = $result_absen->fetch_assoc()){
          if(file_exists('../sw-content/absen/'.strip_tags($data_absen['foto_in']??'-.jpg').'')){
              $foto_in = '<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../sw-content/absen/'.$data_absen['foto_in'].'')).'" height="40">';
          }else{
              $foto_in = '<img src="../sw-content/avatar/avatar.jpg" height="40">';
          }

          if(file_exists('../sw-content/absen/'.strip_tags($data_absen['foto_out']??'-.jpg').'')){
              $foto_out = '<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../sw-content/absen/'.$data_absen['foto_out'].'')).'" height="40">';
          }else{
              $foto_out = '<img src="../sw-content/avatar/avatar.jpg" height="40">';
          }

        echo'
        <div class="card border-1 mb-2" style="border:solid 1px #e3e3e3;">
          <div class="card-body pt-2">
              <div class="row align-items-center">

                  <div class="col align-self-center">
                    <p class="text-secondary p-0 m-0">'.$data_absen['nama_lengkap'].'</p>
                    <small class="badge badge-primary">'.strip_tags($data_absen['kelas']??'-').'</small>
                  </div>

                <div class="col-auto align-self-center">
                  <figure class="avatar avatar-40 rounded mb-0">
                    '.$foto_in.'
                  </figure>
                </div>

                  <div class="col-4 align-self-center">
                    <small class="text-info">MASUK</small>
                    <p class="text-secondary">'.strip_tags($data_absen['absen_in']??'-').'</p>
                  </div>

                  <div class="col-auto align-self-center">
                  <figure class="avatar avatar-40 rounded mb-0">
                    '.$foto_out.'
                  </figure>
                </div>

                  <div class="col align-self-center">
                    <small class="text-danger">PULANG</small>
                    <p class="text-secondary">' . ((empty($data_absen['absen_out']) || $data_absen['absen_out'] == '00:00:00') ? '-' : strip_tags($data_absen['absen_out']??'-')) . '</p>
                  </div>

              </div>
            </div>
        </div>';
      }
    }else{
      echo'<div class="alert alert-info text-center">
            Data absensi masih kosong!
          </div>';
    }



/* Data Counter */
break;
case 'data-counter':

  // Total siswa aktif
  $qTotal = $connection->query("SELECT COUNT(*) AS total FROM user WHERE active='Y'");
  $rowTotal = $qTotal ? $qTotal->fetch_assoc() : ['total' => 0];
  $total_siswa = (int)($rowTotal['total'] ?? 0);

  // Rekap absen hari ini (hanya siswa aktif)
  $sqlRekap = "
    SELECT
      SUM(CASE WHEN a.kehadiran = 'Hadir' THEN 1 ELSE 0 END) AS hadir_count,
      SUM(CASE WHEN a.kehadiran = 'Izin'  THEN 1 ELSE 0 END) AS izin_count,
      SUM(CASE WHEN a.kehadiran = 'Hadir' AND a.status_masuk = 'Tepat Waktu' THEN 1 ELSE 0 END) AS ontime_count,
      SUM(CASE WHEN a.kehadiran = 'Hadir' AND a.status_masuk = 'Telat' THEN 1 ELSE 0 END) AS telat_count
    FROM user u
    LEFT JOIN absen a
      ON u.user_id = a.user_id
     AND a.tanggal = '$date'
    WHERE u.active = 'Y'
  ";

  $resRekap = $connection->query($sqlRekap);
  $row = $resRekap ? $resRekap->fetch_assoc() : [];

  $hadir_count  = (int)($row['hadir_count']  ?? 0);
  $izin_count   = (int)($row['izin_count']   ?? 0);
  $ontime_count = (int)($row['ontime_count'] ?? 0);
  $telat_count  = (int)($row['telat_count']  ?? 0);

  $belum_absen = max(0, $total_siswa - $hadir_count - $izin_count);
  $persentase  = ($total_siswa > 0) ? (int)round(($hadir_count / $total_siswa) * 100, 0) : 0;

  $data_counter = [
    'total_siswa' => $total_siswa,
    'on_time'     => $ontime_count,
    'terlambat'   => $telat_count,
    'izin'        => $izin_count,
    'belum_absen' => $belum_absen,
    'total_absen' => $hadir_count,
    'persentase'  => $persentase
  ];

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data_counter);

break;
}