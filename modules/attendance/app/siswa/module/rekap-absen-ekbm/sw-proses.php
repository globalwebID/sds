<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:./404');
}else{
  require_once'../../../sw-library/sw-config.php';
  require_once'../../../sw-library/sw-function.php';
  require_once'../../oauth/user.php';


switch (@$_GET['action']){
case 'data-histori':

if (empty($_GET['mulai']) OR empty($_GET['selesai'])){ 
  $filter = "AND MONTH(absen_ekbm.tanggal) ='$month' AND YEAR(absen_ekbm.tanggal) ='$year'";
} else { 
  $mulai = date('Y-m-d', strtotime($_GET['mulai']));
  $selesai = date('Y-m-d', strtotime($_GET['selesai']));
  $filter = "AND absen_ekbm.tanggal BETWEEN  '$mulai' AND '$selesai'";
}

$data_pelajaran = NULL;
$query_histori = "SELECT absen_ekbm.*,user.nama_lengkap,user.kelas FROM absen_ekbm 
INNER JOIN user ON absen_ekbm.user_id=user.user_id AND user.user_id='$data_user[user_id]' $filter ORDER BY absen_ekbm.absen_id DESC LIMIT 10";
$result_histori = $connection->query($query_histori);
if($result_histori->num_rows > 0){
    while ($data_histori= $result_histori->fetch_assoc()) {
      $absen_id =  htmlentities($data_histori['absen_id']??'0');
      
      $query_pelajaran="SELECT nama_mapel FROM mata_pelajaran WHERE id='$data_histori[pelajaran]'";
      $result_pelajaran = $connection->query($query_pelajaran);
      $data_pelajaran = $result_pelajaran->fetch_assoc();

      $query_pegawai ="SELECT nama_lengkap FROM pegawai WHERE pegawai_id='$data_histori[pegawai]' AND jabatan='guru' LIMIT 1";
      $result_pegawai = $connection->query($query_pegawai);
      $data_pegawai  = $result_pegawai->fetch_assoc();

      if($data_histori['keterangan'] == 'H'){
        $kehadiran = '<span class="badge badge-primary">Hadir</span>';
      }elseif($data_histori['keterangan'] == 'A'){
        $kehadiran = '<span class="badge badge-danger">Alpha</span>';
      }elseif($data_histori['keterangan'] == 'I'){
        $kehadiran = '<span class="badge badge-warning">Ijin</span>';
      }elseif($data_histori['keterangan'] == 'S'){
        $kehadiran = '<span class="badge badge-warning">Sakit</span>';
      }else{
         $kehadiran = '<span class="badge badge-secondary">Belum Absen</span>';
      }

      echo'
      <div class="card border-0 mb-2">
        <div class="card-body pt-2">
            <div class="row">
              <div class="col align-self-center">
                  <small class="text-secondary">'.tgl_indo($data_histori['tanggal']).'</small>
              </div> 
              <div class="col-auto">
                  ' .($kehadiran). '
                </div>
            </div>
            <hr class="p-0 mt-1 mb-2">

            <div class="row align-items-center">
                <div class="col align-self-center">
                  <p class="text-secondary mb-0">'.($data_histori['nama_lengkap']??'-').' <span class="badge badge-primary">'.($data_histori['kelas']??'-').'</span></p>
                  <small class="text-secondary">'.($data_pelajaran['nama_mapel']??'-').' <b>- '.($data_pegawai['nama_lengkap']).'</b></small>
                </div>

            
            </div>
            
          </div>
      </div>';
    }
      echo'
      <div class="text-center show_more_main'.$absen_id.' mt-4">
          <button data-id="'.$absen_id.'" class="btn btn-light rounded load-more">Show more</button>
      </div>';
  }else{
      echo'<div class="alert alert-secondary mt-3">Saat ini, data kehadiran E-KBM belum tersedia atau masih kosong!</div>';
  }

  /** Load More data absen_ekbm */
break;
case 'data-histori-load':
$id = anti_injection($_POST['id']??'-');

if (empty($_GET['mulai']) OR empty($_GET['selesai'])){ 
  $filter = "AND MONTH(absen_ekbm.tanggal) ='$month' AND YEAR(absen_ekbm.tanggal) ='$year'";
} else { 
  $mulai = date('Y-m-d', strtotime($_GET['mulai']));
  $selesai = date('Y-m-d', strtotime($_GET['selesai']));
  $filter = "AND absen_ekbm.tanggal BETWEEN  '$mulai' AND '$selesai'";
}

$query_count    ="SELECT COUNT(absen_ekbm.absen_id) AS total FROM absen_ekbm 
INNER JOIN user ON absen_ekbm.user_id=user.user_id AND user.user_id='$data_user[user_id]' $filter AND absen_ekbm.absen_id < $id";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 10;
$data_pelajaran = NULL;
$query_histori = "SELECT absen_ekbm.*,user.nama_lengkap,user.kelas FROM absen_ekbm 
INNER JOIN user ON absen_ekbm.user_id=user.user_id AND user.user_id='$data_user[user_id]' $filter AND absen_ekbm.absen_id < $id ORDER BY user.nama_lengkap ASC LIMIT $showLimit";
$result_histori = $connection->query($query_histori);
if($result_histori->num_rows > 0){
    while ($data_histori= $result_histori->fetch_assoc()) {
      $absen_id =  htmlentities($data_histori['absen_id']);
        
      $query_pelajaran="SELECT nama_mapel FROM mata_pelajaran WHERE id='$data_histori[pelajaran]'";
      $result_pelajaran = $connection->query($query_pelajaran);
      $data_pelajaran = $result_pelajaran->fetch_assoc();

      $query_pegawai ="SELECT nama_lengkap FROM pegawai WHERE pegawai_id='$data_histori[pegawai]' AND jabatan='guru' LIMIT 1";
      $result_pegawai = $connection->query($query_pegawai);
      $data_pegawai  = $result_pegawai->fetch_assoc();

      if($data_histori['keterangan'] == 'H'){
        $kehadiran = '<span class="badge badge-primary">Hadir</span>';
      }elseif($data_histori['keterangan'] == 'A'){
        $kehadiran = '<span class="badge badge-danger">Alpha</span>';
      }elseif($data_histori['keterangan'] == 'I'){
        $kehadiran = '<span class="badge badge-warning">Ijin</span>';
      }elseif($data_histori['keterangan'] == 'S'){
        $kehadiran = '<span class="badge badge-warning">Sakit</span>';
      }else{
         $kehadiran = '<span class="badge badge-secondary">Belum Absen</span>';
      }

      echo'
      <div class="card border-0 mb-2">
        <div class="card-body pt-2">
            <div class="row">
              <div class="col align-self-center">
                  <small class="text-secondary">'.tgl_indo($data_histori['tanggal']).'</small>
              </div> 
              <div class="col-auto">
                  ' .($kehadiran). '
                </div>
            </div>
            <hr class="p-0 mt-1 mb-2">

            <div class="row align-items-center">
                <div class="col align-self-center">
                  <p class="text-secondary mb-0">'.($data_histori['nama_lengkap']??'-').' <span class="badge badge-primary">'.($data_histori['kelas']??'-').'</span></p>
                  <small class="text-secondary">'.($data_pelajaran['nama_mapel']??'-').' <b>- '.($data_pegawai['nama_lengkap']).'</b></small>
                </div>

            
            </div>
            
          </div>
      </div>';
    }
    
    if($totalRowCount > $showLimit){
      echo'
      <div class="text-center show_more_main'.$absen_id.' mt-4">
          <button data-id="'.$absen_id.'" class="btn btn-light rounded load-more">Show more</button>
      </div>';
    }
  }else{
     echo'<div class="alert alert-secondary mt-3">Saat ini, data kehadiran E-KBM sudah tidak ada!</div>';
  }

break;
  }
}