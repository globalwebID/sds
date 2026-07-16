<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

$query_whatsapp_pesan = "SELECT * FROM whatsapp_pesan WHERE whatsapp_pesan_id='1' LIMIT 1";
$result_whatsapp_pesan = $connection->query($query_whatsapp_pesan);
$data_whatsapp_pesan = $result_whatsapp_pesan->fetch_assoc();


switch (@$_GET['action']){
case 'absen-in':
    $error = array();

    if (empty($_POST['qrcode'])) {
      $error[] = 'Qrcode tidak boleh kosong';
    } else {
      $qrcode = anti_injection($_POST['qrcode']);
    }

    if (empty($_POST['latitude'])) {
      $error[] = 'Lokasi tidak ditemukan, silahkan izinkan lokasi Anda';
    } else {
      $latitude = anti_injection($_POST['latitude']);
    }

  if (empty($error)){

    if($row_site['tipe_absen'] =='qrcode'){
      $filter = "user.nisn='$qrcode'";
    }else{
      /** RFID */
      $filter = "user.rfid='$qrcode'";
    }

    $query_siswa ="SELECT user.user_id,user.nama_lengkap,user.nisn,user.telp,user.avatar,kelas.kelas_id,kelas.nama_kelas,user.avatar FROM user INNER JOIN absensi_kelas ON user.kelas = kelas.kelas_id WHERE $filter";
    $result_siswa = $connection->query($query_siswa);
    if ($result_siswa->num_rows > 0){
        $data_siswa     = $result_siswa->fetch_assoc();
        
        $query_wali  ="SELECT telp FROM orangtua WHERE user_id='$data_siswa[user_id]' LIMIT 1";
        $result_wali = $connection->query($query_wali);
        if ($result_wali->num_rows > 0){
          $data_wali = $result_wali->fetch_assoc();
          if($data_wali['telp']==''){
            $nomorwa        = htmlentities($data_siswa['telp']);
          }else{
            $nomorwa        = htmlentities($data_wali['telp']);
          }
        }else{
          $nomorwa        = htmlentities($data_siswa['telp']);
        }

      $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
      $result_waktu = $connection->query($query_waktu);
      if($result_waktu->num_rows > 0){
        $data_waktu = $result_waktu->fetch_assoc();

         if ($time_absen <= $data_waktu['jam_telat'] ) {
            $status_masuk ='Tepat Waktu';
          } else {
            $status_masuk ='Telat';
          }

        /** Cek Absensi Hari ini berdasarkan tanggal sekarang */
        $query_absen ="SELECT absen_id FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
        $result_absen = $connection->query($query_absen);
        if(!$result_absen->num_rows > 0) {


            /** Pesan Ke WhatsApp */
            if($whatsapp_tipe =='wablas'){
              $isipesan  = ''.strip_tags(strtolower($row_site['site_name'])).'<br>'.format_hari_tanggal($date).'<br><br>'.strtolower($data_siswa['nama_lengkap']).'<br>>'.strip_tags($data_siswa['nisn']).'<br>>'.strip_tags($data_siswa['nama_kelas']).'<br><br>Presensi : Masuk<br>Jam Absen : '.$time_absen.'<br>Keterangan : '.$status_masuk.'<br><br><br>Notification sent by the system<br>E-Absensi Digital Application';
            }
            
            if($whatsapp_tipe =='universal'){
              $isipesan  = ''.strip_tags(strtolower($row_site['site_name'])).'%0A"'.format_hari_tanggal($date).'%0A%0A'.strtolower($data_siswa['nama_lengkap']).'%0A>'.strip_tags($data_siswa['nisn']).'%0A>'.strip_tags($data_siswa['nama_kelas']).'%0A%0APresensi : Masuk%0AJam Absen : '.$time_absen.'%0AKeterangan :  '.$status_masuk.'%0A%0A%0ANotification sent by the system%0AE-Absensi Digital Application';
              $isipesan = str_replace(' ', '%20', $isipesan);
            }
            
            /** Pesan Ke WhatsApp */

            /** Jika belum ada makan tambah absen baru */
            $add ="INSERT INTO absen (user_id,
                    kelas_id,
                    tanggal,
                    jam_masuk,
                    jam_toleransi,
                    jam_pulang,
                    absen_in,
                    absen_out,
                    status_masuk,
                    status_pulang,
                    map_in,
                    map_out,
                    kehadiran,
                    keterangan) values('$data_siswa[user_id]',
                    '$data_siswa[kelas_id]',
                    '$date',
                    '$data_waktu[jam_masuk]',
                    '$data_waktu[jam_telat]',
                    '$data_waktu[jam_pulang]',
                    '$time_absen',
                    '00:00:00', /** Jam Pulang kosong */
                    '$status_masuk',
                    '', /** Status Pulang kosong */
                    '$latitude',
                    '-', /** Latitude out */
                    'Hadir', /** 1. Hadir */
                    '-')"; /** Keterangan Kosong */
            if($connection->query($add) === false) { 
              echo'Sepertinya Sistem Kami sedang error!';
              die($connection->error.__LINE__); 
          } else{
              echo'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';
              if($whatsapp_tipe =='wablas'){
                KirimWa($nomorwa,$isipesan,$link,$token,$secret_key);
              }
              if($whatsapp_tipe =='universal'){
                  KirimWa($sender,$nomorwa,$isipesan,$link,$token);
              }
          }
        
        }else{
          /** Jika sudah absen masuk */
          echo'success/Terimakasih "'.$data_siswa['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Belajar!';
        }
        
      }else{
        /** Jika jadwaltidak ditemukan */
        echo'Hari ini tidak Ada jadwal/jam sekolah!';
      }

    }else{
      /** Jika siswa tidak ditemukan */
      echo'Qr Code/User tidak ditemukan, silahkan hubungi Admin!';
    }
        
  }else{           
      foreach ($error as $key => $values) {            
        echo"$values\n";
      }
  }



/** Absen Out */
break;
case 'absen-out':
$error = array();

    if (empty($_POST['qrcode'])) {
      $error[] = 'Qrcode tidak boleh kosong';
    } else {
      $qrcode = anti_injection($_POST['qrcode']);
    }

    if (empty($_POST['latitude'])) {
      $error[] = 'Lokasi tidak ditemukan, silahkan izinkan lokasi Anda';
    } else {
      $latitude = anti_injection($_POST['latitude']);
    }

  if (empty($error)) {

    if($row_site['tipe_absen'] =='qrcode'){
      $filter = "user.nisn='$qrcode'";
    }else{
      /** RFID */
      $filter = "user.rfid='$qrcode'";
    }

    $query_siswa ="SELECT user.user_id,user.nama_lengkap,user.nisn,user.telp,user.avatar,kelas.kelas_id,kelas.nama_kelas,user.avatar FROM user INNER JOIN absensi_kelas ON user.kelas = kelas.kelas_id WHERE $filter";
    $result_siswa = $connection->query($query_siswa);
    if ($result_siswa->num_rows > 0){
        $data_siswa     = $result_siswa->fetch_assoc();
        
      $query_wali  ="SELECT telp FROM orangtua WHERE user_id='$data_siswa[user_id]' LIMIT 1";
      $result_wali = $connection->query($query_wali);
      if ($result_wali->num_rows > 0){
        $data_wali = $result_wali->fetch_assoc();
        if($data_wali['telp']==''){
          $nomorwa        = htmlentities($data_siswa['telp']);
        }else{
          $nomorwa        = htmlentities($data_wali['telp']);
        }
      }else{
        $nomorwa        = htmlentities($data_siswa['telp']);
      }

      $query_waktu = "SELECT jam_masuk,jam_telat,jam_pulang FROM waktu WHERE hari='$hari_ini' AND active='Y'";
      $result_waktu = $connection->query($query_waktu);
      if($result_waktu->num_rows > 0){
        $data_waktu = $result_waktu->fetch_assoc();

        if($data_waktu['jam_pulang'] > $time_absen){
          $status_pulang='Pulang Cepat';
        }else{
          $status_pulang ='';
        }

         /** Pesan Ke WhatsApp */
         if($whatsapp_tipe =='wablas'){
          $isipesan  = ''.strip_tags(strtolower($row_site['site_name'])).'<br>'.format_hari_tanggal($date).'<br><br>'.strtolower($data_siswa['nama_lengkap']).'<br>>'.strip_tags($data_siswa['nisn']).'<br>>'.strip_tags($data_siswa['nama_kelas']).'<br><br>Presensi : Pulang<br>Jam Absen : '.$time_absen.'<br>Keterangan : '.$status_masuk.'<br><br><br>Notification sent by the system<br>E-Absensi Digital Application';
        }
        
        if($whatsapp_tipe =='universal'){
          $isipesan  = ''.strip_tags(strtolower($row_site['site_name'])).'%0A"'.format_hari_tanggal($date).'%0A%0A'.strtolower($data_siswa['nama_lengkap']).'%0A>'.strip_tags($data_siswa['nisn']).'%0A>'.strip_tags($data_siswa['nama_kelas']).'%0A%0APresensi : Pulang%0AJam Absen : '.$time_absen.'%0AKeterangan :  '.$status_masuk.'%0A%0A%0ANotification sent by the system%0AE-Absensi Digital Application';
          $isipesan = str_replace(' ', '%20', $isipesan);
        }
        
        /** Pesan Ke WhatsApp */


        /** Cek Absensi Hari ini berdasarkan tanggal sekarang */
        $query_absen ="SELECT absen_id,absen_out FROM absen WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
        $result_absen = $connection->query($query_absen);
        if($result_absen->num_rows > 0) {
          $data_absensi = $result_absen->fetch_assoc();

          if($data_absensi['absen_out']=='00:00:00'){
            /*Update Data Absensi */
            $update ="UPDATE absen SET absen_out='$time_absen',
                      status_pulang='$status_pulang',
                      map_out='$latitude' WHERE tanggal='$date' AND user_id='$data_siswa[user_id]'";
            if($connection->query($update) === false) { 
              echo'Sepertinya Sistem Kami sedang error!';
              die($connection->error.__LINE__); 
            } else{
                echo'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';
                
                if($whatsapp_tipe =='wablas'){
                  KirimWa($nomorwa,$isipesan,$link,$token,$secret_key);
                }
                if($whatsapp_tipe =='universal'){
                    KirimWa($sender,$nomorwa,$isipesan,$link,$token);
                }
            }

          }else{
            /** Jika Data Absensi sudah ada makan kasih notif kembali */
            echo'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time.', Sampai ketemu besok "'.$data_siswa['nama_lengkap'].'"!';
          }
        
        }else{
          /** Jika Data absensi masuk tidak ditemukan!*/
          echo'Sebelumnya Siswa '.$data_siswa['nama_lengkap'].' belum pernah Absen masuk!';
        }
        
      }else{
        /** Jika jadwaltidak ditemukan */
        echo'Hari ini tidak Ada jadwal/jam sekolah!';
      }

    }else{
      /** Jika siswa tidak ditemukan */
      echo'Qr Code/User tidak ditemukan, silahkan hubungi Admin!';
    }
        
  }else{           
      foreach ($error as $key => $values) {            
        echo"$values\n";
      }
  }


break;
}}