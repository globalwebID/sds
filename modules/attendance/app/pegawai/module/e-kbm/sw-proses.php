<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
  echo'Not Found';
}else{
  require_once'../../../sw-library/sw-config.php';
  require_once'../../../sw-library/sw-function.php';
  require_once'../../oauth/user.php';

switch (@$_GET['action']){
case 'add-absen':
$error = [];
$fields = [
    'jadwal_id'       => 'Jadwal',
    'siswa'           => 'Siswa',
    'keterangan'      => 'Keterangan'
];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        if ($key === 'jadwal_id') {
            $$key =  convert("decrypt",$_POST[$key]);
        }
        // default untuk field lainnya
        else {
            $$key = anti_injection($_POST[$key]);
        }
    }
  }

  if (empty($error)){
    $query_jadwal = mysqli_query($connection, "SELECT jadwal_mengajar.*,mata_pelajaran.nama_mapel FROM jadwal_mengajar 
    LEFT JOIN mata_pelajaran ON jadwal_mengajar.mata_pelajaran = mata_pelajaran.id
    WHERE pegawai='$data_user[pegawai_id]' AND jadwal_id='$jadwal_id'");
    if(mysqli_num_rows($query_jadwal) > 0){
      $data_jadwal = mysqli_fetch_array($query_jadwal);

        $cek_kehadiran = "SELECT absen_id, keterangan FROM absen_ekbm 
        WHERE user_id='$siswa' AND pegawai='$data_user[pegawai_id]' AND jadwal_id='$data_jadwal[jadwal_id]' AND tanggal='$date'";
        $result_kehadiran = $connection->query($cek_kehadiran);
        if ($result_kehadiran->num_rows > 0) {
          /** Jika Ada maka update absensi */
          $update = "UPDATE absen_ekbm SET time = '$waktu_sekarang', keterangan = '$keterangan'
          WHERE jadwal_id = '$jadwal_id' AND user_id = '$siswa' AND tanggal = '$date'";
          if ($connection->query($update) === false) { 
              die($connection->error . __LINE__); 
              echo 'Data tidak berhasil diperbarui!';
          } else {
              echo 'success';
          }

        }else{
          // Jika belum ada maka tambah baru
          $add = "INSERT INTO absen_ekbm (
                jadwal_id,
                pegawai,
                user_id,
                kelas,
                pelajaran,
                tanggal,
                time,
                keterangan) VALUES (
                '$jadwal_id',
                '{$data_user['pegawai_id']}',
                '$siswa',
                '{$data_jadwal['kelas']}',
                '{$data_jadwal['mata_pelajaran']}',
                '$date',
                '$waktu_sekarang',
                '$keterangan')";
          if ($connection->query($add) === false) { 
              die($connection->error . __LINE__); 
              echo 'Data tidak berhasil disimpan!';
          } else {
              echo 'success';
          }
        }   

    }else{
      echo'Jadwal tidak ditemukan!';
    }
  }else{
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }
break;
  }
}