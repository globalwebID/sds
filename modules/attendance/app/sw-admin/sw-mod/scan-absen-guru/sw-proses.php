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

    $query_guru ="SELECT guru_id,nip,nama_lengkap,jabatan,telp,lokasi FROM guru WHERE nip='$qrcode'";
    $result_guru = $connection->query($query_guru);
    if ($result_guru->num_rows > 0){
        $data_guru     = $result_guru->fetch_assoc();
        $nomorwa        = htmlentities($data_guru['telp']);
        
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
        $query_absen ="SELECT absen_guru_id FROM absen_guru WHERE tanggal='$date' AND guru_id='$data_guru[guru_id]'";
        $result_absen = $connection->query($query_absen);
        if(!$result_absen->num_rows > 0) {


            /** Pesan Ke WhatsApp */
            if($whatsapp_tipe =='wablas'){
              $isipesan  = ''.strip_tags(strtolower($row_site['site_name'])).'<br><br>'.format_hari_tanggal($date).'<br><br>'.strtolower($data_guru['nama_lengkap']).'<br>>'.strip_tags($data_guru['nip']).'<br>Presensi : Masuk<br>Jam Absen : '.$time_absen.'<br>Keterangan : '.$status_masuk.'<br>Lokasi : https://www.google.com/maps/place/'.$latitude.'<br><br>Notification sent by the system<br>E-Absensi Digital Application';
            }
    
            if($whatsapp_tipe =='universal'){
              $isipesan  = ''.strip_tags(strtolower($row_site['site_name'])).'%0A"'.format_hari_tanggal($date).'%0A%0A'.strtolower($data_guru['nama_lengkap']).'%0A>'.strip_tags($data_guru['nip']).'%0APresensi : Masuk%0AJam Absen : '.$time_absen.'%0AKeterangan :  '.$status_masuk.'%0ALokasi : https://www.google.com/maps/place/'.$latitude.'%0A%0ANotification sent by the system%0AE-Absensi Digital Application';
              $isipesan = str_replace(' ', '%20', $isipesan);
            }

            /** Pesan Ke WhatsApp */

            /** Jika belum ada makan tambah absen baru */
            $add ="INSERT INTO absen_guru (guru_id,
                  tanggal,
                  lokasi_id,
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
                  keterangan) values('$data_guru[guru_id]',
                  '$date',
                  '$data_guru[lokasi]',
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
              echo'success/Terimakasih "'.$data_guru['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Bekerja!';
              if($whatsapp_tipe =='wablas'){
                KirimWa($nomorwa,$isipesan,$link,$token,$secret_key);
              }
              if($whatsapp_tipe =='universal'){
                  KirimWa($sender,$nomorwa,$isipesan,$link,$token);
              }
          }
        
        }else{
          /** Jika sudah absen masuk */
          echo'success/Terimakasih "'.$data_guru['nama_lengkap'].'", Absensi Anda berhasil pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.' Selamat Bekerja!';
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
    $query_guru ="SELECT guru_id,nip,nama_lengkap,jabatan,telp FROM guru WHERE nip='$qrcode'";
    $result_guru = $connection->query($query_guru);
    if ($result_guru->num_rows > 0){
        $data_guru     = $result_guru->fetch_assoc();
        $nomorwa        = htmlentities($data_guru['telp']);

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
          $isipesan  = ''.strip_tags(strtolower($row_site['site_name'])).'<br><br>'.format_hari_tanggal($date).'<br><br>'.strtolower($data_guru['nama_lengkap']).'<br>>'.strip_tags($data_guru['nip']).'<br>Presensi : Pulang<br>Jam Absen : '.$time_absen.'<br>Keterangan : '.$status_masuk.'<br>Lokasi : https://www.google.com/maps/place/'.$latitude.'<br><br>Notification sent by the system<br>E-Absensi Digital Application';
        }

        if($whatsapp_tipe =='universal'){
          $isipesan  = ''.strip_tags(strtolower($row_site['site_name'])).'%0A"'.format_hari_tanggal($date).'%0A%0A'.strtolower($data_guru['nama_lengkap']).'%0A>'.strip_tags($data_guru['nip']).'%0APresensi : Pulang%0AJam Absen : '.$time_absen.'%0AKeterangan :  '.$status_masuk.'%0ALokasi : https://www.google.com/maps/place/'.$latitude.'%0A%0ANotification sent by the system%0AE-Absensi Digital Application';
          $isipesan = str_replace(' ', '%20', $isipesan);
        }
        /** Pesan Ke WhatsApp */


        /** Cek Absensi Hari ini berdasarkan tanggal sekarang */
        $query_absen ="SELECT absen_guru_id,absen_out FROM absen_guru WHERE tanggal='$date' AND guru_id='$data_guru[guru_id]'";
        $result_absen = $connection->query($query_absen);
        if($result_absen->num_rows > 0) {
          $data_absensi = $result_absen->fetch_assoc();

          if($data_absensi['absen_out']=='00:00:00'){
            /*Update Data Absensi */
            $update ="UPDATE absen_guru SET absen_out='$time_absen',
                      status_pulang='$status_pulang', map_out='$latitude' WHERE tanggal='$date' AND guru_id='$data_guru[guru_id]'";
            if($connection->query($update) === false) { 
              echo'Sepertinya Sistem Kami sedang error!';
              die($connection->error.__LINE__); 
            } else{
              echo'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.', Sampai ketemu besok "'.strip_tags($data_guru['nama_lengkap']).'"!';
                
                if($whatsapp_tipe =='wablas'){
                  KirimWa($nomorwa,$isipesan,$link,$token,$secret_key);
                }
                if($whatsapp_tipe =='universal'){
                    KirimWa($sender,$nomorwa,$isipesan,$link,$token);
                }
            }

          }else{
            /** Jika Data Absensi sudah ada makan kasih notif kembali */
            echo'success/Selamat Anda berhasil Absen Pulang pada Tanggal '.tanggal_ind($date).' dan Jam : '.$time_absen.', Sampai ketemu besok "'.strip_tags($data_guru['nama_lengkap']).'"!';
          }
        
        }else{
          /** Jika Data absensi masuk tidak ditemukan!*/
          echo'Sebelumnya Anda belum pernah Absen masuk!';
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