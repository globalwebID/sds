<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
   echo'404';
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';

$penerima   = $data_user['telp'];
$data_jam = getJam($connection, $hari_ini, $data_user['jabatan']);

switch (@$_GET['action']){
case 'absen-qrcode':
$error = [];

$data_post = [
    'qrcode' => 'Qrcode',
    'latitude' => 'Latitude',
    'radius' => 'Radius',
    'radius_aktif' => 'Radius Aktif',
];

// Validasi input
foreach ($data_post as $key => $value) {
    if (!isset($_POST[$key]) || ($_POST[$key] === '' && $_POST[$key] !== '0')) {
        $error[] = ucfirst($key) . ' tidak boleh kosong';
    } else {
        $$key = htmlentities(strip_tags($_POST[$key]));
    }
}

if($row_site['tipe_absen_pegawai']=='qrcode'){
  $filter ="AND pegawai.qrcode = '$qrcode'";
}else{
  $filter ="AND lokasi.lokasi_qrcode = '$qrcode'";
}

if (empty($error)) {
    $query_pegawai = "SELECT pegawai.pegawai_id,lokasi.*
    FROM pegawai LEFT JOIN lokasi ON pegawai.lokasi = lokasi.lokasi_id 
    WHERE pegawai.pegawai_id='".htmlspecialchars($data_user['pegawai_id'], ENT_QUOTES, 'UTF-8')."' $filter";
    $result_pegawai = $connection->query($query_pegawai);
    if ($result_pegawai->num_rows > 0) {
      $data_pegawai = $result_pegawai->fetch_assoc();

      if (!$data_jam) {
        die('Jam Abensi tidak ditemukan!');
        break;
      }else{
        $waktu_awal = (new DateTime($data_jam['jam_masuk']))->modify('-60 minutes')->format('H:i:s');
        $status = cekMulaiAbsen($data_jam['jam_masuk'], $data_jam['jam_pulang'], $waktu_awal);
        if ($status !== 'Y') {
          die('Absensi belum aktif. Mulai absen jam masuk: ' . $status['masuk'] . ', jam pulang: ' . $status['pulang']);
        }
      }
      
      // menentukan status telat s-widodo.com
      $status_in = ($time_sekarang <= $data_jam['jam_telat']) ? 'Tepat Waktu' : 'Telat';

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
            
        // *** Absen dengan Radius ***
        if ($_POST['radius_aktif']=='Y') {

          if ($data_pegawai['lokasi_radius'] > $radius) {
              // Cek absensi hari ini
            $query_absensi = "SELECT absen_id,absen_in FROM absen_pegawai WHERE tanggal='$date' AND pegawai_id='".htmlspecialchars($data_user['pegawai_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
              $result_absensi = $connection->query($query_absensi);
              if ($result_absensi->num_rows == 0) {

                // Insert data absen dengan radius
                $add_absen = "INSERT INTO absen_pegawai (
                      pegawai_id,
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
                      '{$data_user['pegawai_id']}',
                      '$date',
                      '{$data_pegawai['lokasi_id']}',
                      '{$data_jam['jam_masuk']}',
                      '{$data_jam['jam_telat']}',
                      '{$data_jam['jam_pulang']}',
                      '$time_absen',
                      '$status_in',
                      '$latitude',
                      'Hadir',
                      '$radius')";
                if ($connection->query($add_absen) === false) {
                    echo 'Sepertinya Sistem Kami sedang error!';
                    die($connection->error . __LINE__);
                } else {
                    echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . ".";
                    
                    if($row_site['whatsapp_active'] =='Y'){
                      if($whatsapp_tipe =='POST'){
                        KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                      }else{
                        KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                      }
                    }
                }
            } else {
              // Absen sudah ada
              $data_absen = $result_absensi->fetch_assoc();
              echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul {$data_absen['absen_in']}.";
            }
        } else {
          echo 'Lokasi Anda saat ini jauh dari radius!';
        }
    }
        // *** Absen Tanpa Radius ***
      else {
            // Proses absen tanpa radius
            $query_absensi = "SELECT absen_id, absen_in FROM absen_pegawai WHERE tanggal='$date' AND pegawai_id='".htmlspecialchars($data_user['pegawai_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
            $result_absensi = $connection->query($query_absensi);
            if ($result_absensi->num_rows == 0) {

                $add_absen = "INSERT INTO absen_pegawai (
                      pegawai_id,
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
                      '{$data_user['pegawai_id']}',
                      '$date',
                      '{$data_pegawai['lokasi_id']}',
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
                    echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . ".";

                    if($row_site['whatsapp_active'] =='Y'){
                      if($whatsapp_tipe =='POST'){
                        KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                      }else{
                        KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                      }
                    }
                }
            } else {
                // Absen sudah ada
                $data_absen = $result_absensi->fetch_assoc();
                echo "success/Terima kasih, {$data_user['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul {$data_absen['absen_in']}.";
            }
        }
    } else {
      echo 'Qr code tidak ditemukan, silahkan hubungi Admin!';
    }
} else {
   echo implode("\n", $error);
}


/* -------------------------------- ABSEN MENGGUNAKAN FOTO -------------------------------- */
break;
case 'absen-selfie':
$error = [];

$data_post = [
  'latitude' => '',
  'radius' => '',
];

  // Validasi input
  foreach ($data_post as $key => $value) {
      if (!isset($_POST[$key]) || ($_POST[$key] === '' && $_POST[$key] !== '0')) {
          $error[] = ucfirst($key) . ' tidak boleh kosong';
      } else {
          $$key = htmlentities(strip_tags($_POST[$key]));
      }
  }

 if (empty($_POST['road'])) { 
    $road = '-';
  } else {
    $road = htmlentities(strip_tags($_POST['road']));
  }

  if (empty($_POST['city'])) { 
    $city = '-';
  } else {
    $city = htmlentities(strip_tags($_POST['city']));
  }


if (empty($_POST['img'])){
    $error[]    = 'Foto tidak dapat di unggah!';
  } else {
    $img = $_POST['img'];
    $img = str_replace('data:image/jpeg;base64,', '', $img);
    $img = str_replace(' ', '+', $img);
    $fetch_imgParts = explode(";base64,", $img);
    $image_type_aux = explode("image/", $fetch_imgParts[0]);
    $image_type = $image_type_aux[1];
    $image_base64 = base64_decode($fetch_imgParts[1]);

    $im = imagecreatefromstring($image_base64);
    $source_width = imagesx($im);
    $source_height = imagesy($im);
    $ratio =  $source_height / $source_width;

    $new_width = 350;
    $new_height = $ratio * 390;
    $watermark = "".strip_tags($data_user['nama_lengkap'])."\n".$road."\n".$city."\n".$time_sekarang." - ".tanggal_ind($date)."";
    /* -------- Upload Foto  -------*/
    $foto = 'masuk_' .$data_user['pegawai_id']. '_' . date('Y-m-d') . '_' . uniqid() . '.png';
    $filename = '../../../sw-content/absen/'.$foto.'';
  }


if (empty($error)) {

  $query_pegawai = "SELECT pegawai.pegawai_id,lokasi.*
    FROM pegawai LEFT JOIN lokasi ON pegawai.lokasi = lokasi.lokasi_id 
    WHERE pegawai.pegawai_id='".htmlspecialchars($data_user['pegawai_id'], ENT_QUOTES, 'UTF-8')."'";
    $result_pegawai = $connection->query($query_pegawai);
    if ($result_pegawai->num_rows > 0) {
        $data_pegawai = $result_pegawai->fetch_assoc();

        if (!$data_jam) {
          die('Jam Abensi tidak ditemukan!');
          break;
        }else{
          $waktu_awal = (new DateTime($data_jam['jam_masuk']))->modify('-60 minutes')->format('H:i:s');
          $status = cekMulaiAbsen($data_jam['jam_masuk'], $data_jam['jam_pulang'], $waktu_awal);
          if ($status !== 'Y') {
            die('Absensi belum aktif. Mulai absen jam masuk: ' . $status['masuk'] . ', jam pulang: ' . $status['pulang']);
          }
        }

        // menentukan status telat s-widodo.com
        $status_in = ($time_sekarang <= $data_jam['jam_telat']) ? 'Tepat Waktu' : 'Telat';

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

        // *** Absen dengan Radius ***
        if ($_POST['radius_aktif']=='Y') {

          if ($data_pegawai['lokasi_radius'] > $radius){

            $query_absensi = "SELECT absen_id,absen_in FROM absen_pegawai WHERE tanggal='$date' AND  pegawai_id='".htmlspecialchars($data_user['pegawai_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
            $result_absensi = $connection->query($query_absensi);
            if ($result_absensi->num_rows == 0) {
              // Insert data absen dengan radius
                $add_absen = "INSERT INTO absen_pegawai (
                      pegawai_id,
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
                      '{$data_user['pegawai_id']}',
                      '$date',
                      '{$data_pegawai['lokasi_id']}',
                      '{$data_jam['jam_masuk']}',
                      '{$data_jam['jam_telat']}',
                      '{$data_jam['jam_pulang']}',
                      '$time_absen',
                      '$foto',
                      '$status_in',
                      '$latitude',
                      'Hadir',
                      '$radius')";
              if ($connection->query($add_absen) === false) {
                  echo 'Sepertinya Sistem Kami sedang error!';
                  die($connection->error . __LINE__);
              } else {
                echo "success/Terimakasih {$data_user['nama_lengkap']}, \nAbsensi Anda berhasil pada Tanggal " . tanggal_ind($date) . " dan Jam : $time_sekarang!";
                addTextWatermark($im, $watermark, $filename);	
                
                if($row_site['whatsapp_active'] =='Y'){
                  if($whatsapp_tipe =='POST'){
                    KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                  }else{
                    KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                  }
                }
              }
          } else {
            // Absen sudah ada
            $data_absen = $result_absensi->fetch_assoc();
            echo "success/Terimakasih {$data_user['nama_lengkap']}, \nAbsensi Anda berhasil pada Tanggal " . tanggal_ind($date) . " dan Jam : {$data_absen['absen_in']}!";
          }
        } else {
          echo 'Lokasi Anda saat ini jauh dari radius!';
        }

      }else {
        // *** Absen Tanpa Radius ***
          $query_absensi = "SELECT absen_id, absen_in FROM absen_pegawai WHERE tanggal='$date' AND pegawai_id='".htmlspecialchars($data_user['pegawai_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
          $result_absensi = $connection->query($query_absensi);
          if ($result_absensi->num_rows == 0) {

            // Insert data absen tanpa radius
            $add_absen = "INSERT INTO absen_pegawai (
                pegawai_id,
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
                '{$data_user['pegawai_id']}',
                '$date',
                '{$data_pegawai['lokasi_id']}',
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
                echo "success/Terimakasih {$data_user['nama_lengkap']}, \nAbsensi Anda berhasil pada Tanggal " . tanggal_ind($date) . " dan Jam : $time_sekarang!";
                addTextWatermark($im, $watermark, $filename);	

                if($row_site['whatsapp_active'] =='Y'){
                  if($whatsapp_tipe =='POST'){
                    KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                  }else{
                    KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                  }
                }
            }
          }else{
            // Absen sudah ada
            $data_absen = $result_absensi->fetch_assoc();
            echo "success/Terimakasih {$data_user['nama_lengkap']}, \nAbsensi Anda berhasil pada Tanggal " . tanggal_ind($date) . " dan Jam : {$data_absen['absen_in']}!";
          }

      }
  } else {
      echo 'Data Siswa tidak ditemukan, silahkan hubungi Admin!';
  }
    
} else {
  echo implode("\n", $error);
}

  break;
  }
}