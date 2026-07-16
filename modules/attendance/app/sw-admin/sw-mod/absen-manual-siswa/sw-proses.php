<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:../login/');
  exit;
}else{

require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

switch (@$_GET['action']){
case 'cek-nisn':
header('Content-Type: application/json');
if (isset($_POST['nisnVal'])) {
    $nisnVal = anti_injection($_POST['nisnVal']);
    $query_siswa = "SELECT nisn, nama_lengkap,kelas FROM user WHERE nisn='$nisnVal' LIMIT 1";
    $result_siswa = $connection->query($query_siswa);
    if ($result_siswa && $result_siswa->num_rows > 0) {
        $data_siswa = $result_siswa->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'data' => $data_siswa
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Data tidak ditemukan'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter tidak ditemukan'
    ]);
}

break;
case 'add':
$error = array();

$data_post = [
    'nisn'     => 'nisn',
    'latitude' => 'Latitude',
    'kehadiran'=> 'Tipe Absen',
];

$data_jam = getJam($connection, $hari_ini, 'Siswa');

// Validasi input
foreach ($data_post as $key => $value) {
    if (!isset($_POST[$key]) || ($_POST[$key] === '' && $_POST[$key] !== '0')) {
        $error[] = ucfirst($key) . ' tidak boleh kosong';
    } else {
        $$key = htmlentities(strip_tags($_POST[$key]));
    }
}

if (empty($error)) {

  $query_siswa = "SELECT user.user_id,user.nama_lengkap,user.telp,lokasi.*
  FROM user LEFT JOIN lokasi ON user.lokasi = lokasi.lokasi_id 
  WHERE user.nisn='".htmlspecialchars($nisn, ENT_QUOTES, 'UTF-8')."'";
  $result_siswa = $connection->query($query_siswa);
  if ($result_siswa->num_rows > 0) {
    $data_siswa = $result_siswa->fetch_assoc();
    $penerima   = $data_siswa['telp'];

    if($kehadiran=='masuk'){
      // menentukan status telat s-widodo.com
      if (!$data_jam) {
        die('Jam Abensi tidak ditemukan!');
        break;
      }else{
        $status_in = ($time_sekarang <= $data_jam['jam_telat']) ? 'Tepat Waktu' : 'Telat';
      }

      if ($row_site['whatsapp_active'] == 'Y') {
        $pesan = str_replace(
            ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
            [
                $data_siswa['nama_lengkap'],
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

      $query_absensi = "SELECT absen_id,absen_in FROM absen WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_siswa['user_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
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
                  '{$data_siswa['user_id']}',
                  '$date',
                  '{$data_siswa['lokasi_id']}',
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
                echo "success/Terima kasih, {$data_siswa['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . "!";

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
            echo "success/Terima kasih, {$data_siswa['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul {$data_absen['absen_in']}!";
        }


    }else if($kehadiran=='pulang'){
        // Absen pulang
        if (!$data_jam) {
          die('Jam Abensi tidak ditemukan!');
          break;
        }else{
          $status_out = ($time_sekarang < $data_jam['jam_pulang']) ? 'Pulang Cepat' : 'Tepat Waktu';
        }

        if ($row_site['whatsapp_active'] == 'Y') {
          $pesan = str_replace(
              ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
              [
                  $data_siswa['nama_lengkap'],
                  tanggal_ind($date),
                  'PULANG',
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

        // Cek absensi hari ini
        $query_absensi = "SELECT absen_id,absen_out FROM absen WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_siswa['user_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
          $result_absensi = $connection->query($query_absensi);
          if ($result_absensi->num_rows > 0){
            $data_absen = $result_absensi->fetch_assoc();

            if (is_null($data_absen['absen_out'])){
              
                $update ="UPDATE absen SET absen_out='$time_absen',
                    status_pulang='$status_out',
                    map_out='$latitude',
                    radius_out='0'
                    WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_siswa['user_id'], ENT_QUOTES, 'UTF-8')."' AND absen_id='$data_absen[absen_id]'";
                if ($connection->query($update) === false) {
                    echo 'Sepertinya Sistem Kami sedang error!';
                    die($connection->error . __LINE__);
                } else {
                    echo "success/Terima kasih, {$data_siswa['nama_lengkap']},\nAbsensi Pulang telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . "!";

                    if($row_site['whatsapp_active'] =='Y'){
                      if($whatsapp_tipe =='POST'){
                        KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                      }else{
                        KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                      }
                    }
                }
              }else{
                /** Berikan notifikasi kebali jika doble absen */
                echo "success/Terima kasih, {$data_siswa['nama_lengkap']},\nAbsensi Pulang telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul {$data_absen['absen_out']}!";
              }

          }else{
            echo'Data Absensi Anda tidak ditemukan, Silahkan absen masuk terlebih dahulu!';
          }
      }
      
  }else{
    echo'NISN tidak ditemukan di data siswa.';
  }
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}


// Absen dengan webcame qrode

break;
case 'absen-webcame':
$error = array();

$data_post = [
    'qrcode'     => 'qrcode',
    'latitude' => 'Latitude',
    'kehadiran'=> 'Tipe Absen',
];

$data_jam = getJam($connection, $hari_ini, 'Siswa');

// Validasi input
foreach ($data_post as $key => $value) {
    if (!isset($_POST[$key]) || ($_POST[$key] === '' && $_POST[$key] !== '0')) {
        $error[] = ucfirst($key) . ' tidak boleh kosong';
    } else {
        $$key = htmlentities(strip_tags($_POST[$key]));
    }
}

if (empty($error)) {

  $query_siswa = "SELECT user.user_id,user.nama_lengkap,user.telp,lokasi.*
  FROM user LEFT JOIN lokasi ON user.lokasi = lokasi.lokasi_id 
  WHERE user.nisn='".htmlspecialchars($qrcode, ENT_QUOTES, 'UTF-8')."'";
  $result_siswa = $connection->query($query_siswa);
  if ($result_siswa->num_rows > 0) {
    $data_siswa = $result_siswa->fetch_assoc();
    $penerima   = $data_siswa['telp'];

    if($kehadiran=='masuk'){
      // menentukan status telat s-widodo.com
      if (!$data_jam) {
        die('Jam Abensi tidak ditemukan!');
        break;
      }else{
        $status_in = ($time_sekarang <= $data_jam['jam_telat']) ? 'Tepat Waktu' : 'Telat';
      }

      if ($row_site['whatsapp_active'] == 'Y') {
        $pesan = str_replace(
            ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
            [
                $data_siswa['nama_lengkap'],
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

      $query_absensi = "SELECT absen_id,absen_in FROM absen WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_siswa['user_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
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
                  '{$data_siswa['user_id']}',
                  '$date',
                  '{$data_siswa['lokasi_id']}',
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
                echo "success/Terima kasih, {$data_siswa['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . "!";

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
            echo "success/Terima kasih, {$data_siswa['nama_lengkap']},\nAbsensi Masuk telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul {$data_absen['absen_in']}!";
        }


    }else if($kehadiran=='pulang'){
        // Absen pulang
        if (!$data_jam) {
          die('Jam Abensi tidak ditemukan!');
          break;
        }else{
          $status_out = ($time_sekarang < $data_jam['jam_pulang']) ? 'Pulang Cepat' : 'Tepat Waktu';
        }

        if ($row_site['whatsapp_active'] == 'Y') {
          $pesan = str_replace(
              ['{{nama}}', '{{tanggal}}', '{{tipe}}', '{{jam_sekolah}}', '{{jam_absen}}', '{{status}}', '{{lokasi}}'],
              [
                  $data_siswa['nama_lengkap'],
                  tanggal_ind($date),
                  'PULANG',
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

        // Cek absensi hari ini
        $query_absensi = "SELECT absen_id,absen_out FROM absen WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_siswa['user_id'], ENT_QUOTES, 'UTF-8')."' LIMIT 1";
          $result_absensi = $connection->query($query_absensi);
          if ($result_absensi->num_rows > 0){
            $data_absen = $result_absensi->fetch_assoc();

            if (is_null($data_absen['absen_out'])){
              
                $update ="UPDATE absen SET absen_out='$time_absen',
                    status_pulang='$status_out',
                    map_out='$latitude',
                    radius_out='0'
                    WHERE tanggal='$date' AND user_id='".htmlspecialchars($data_siswa['user_id'], ENT_QUOTES, 'UTF-8')."' AND absen_id='$data_absen[absen_id]'";
                if ($connection->query($update) === false) {
                    echo 'Sepertinya Sistem Kami sedang error!';
                    die($connection->error . __LINE__);
                } else {
                    echo "success/Terima kasih, {$data_siswa['nama_lengkap']},\nAbsensi Pulang telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul " . $time_sekarang . "!";

                    if($row_site['whatsapp_active'] =='Y'){
                      if($whatsapp_tipe =='POST'){
                        KirimWa($penerima,$isipesan,$whatsapp_domain,$whatsapp_token,$secret_key);
                      }else{
                        KirimWa($whatsapp_sender,$penerima,$isipesan,$whatsapp_domain,$whatsapp_token);
                      }
                    }
                }
              }else{
                /** Berikan notifikasi kebali jika doble absen */
                echo "success/Terima kasih, {$data_siswa['nama_lengkap']},\nAbsensi Pulang telah berhasil tercatat pada tanggal " . tanggal_ind($date) . " pukul {$data_absen['absen_out']}!";
              }

          }else{
            echo'Data Absensi Anda tidak ditemukan, Silahkan absen masuk terlebih dahulu!';
          }
      }
      
  }else{
    echo'QRCODE tidak ditemukan di data siswa.';
  }
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}



break;
}
}?>