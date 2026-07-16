<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:./404');
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';

switch (@$_GET['action']){
case 'data-histori':
$filterParts = [];
$tanggal = isset($_GET['tanggal']) ? date('Y-m-d', strtotime($_GET['tanggal'])) : $date;
$filterParts[] = "absen_ekbm.tanggal='$tanggal'";

if (!empty($_GET['kelas'])) {
  $kelas = htmlentities($_GET['kelas']);
  $filterParts[] = "absen_ekbm.kelas='$kelas'";
}

$filter = 'WHERE ' . implode(' AND ', $filterParts);
$data_pelajaran = NULL;
$query_histori = "SELECT absen_ekbm.*,user.nama_lengkap,user.kelas FROM absen_ekbm 
LEFT JOIN user ON absen_ekbm.user_id=user.user_id $filter ORDER BY user.nama_lengkap ASC LIMIT 25";
$result_histori = $connection->query($query_histori);
if($result_histori->num_rows > 0){
    while ($data_histori= $result_histori->fetch_assoc()) {
      $absen_id =  htmlentities($data_histori['absen_id']??'0');
      
      $query_pelajaran="SELECT nama_mapel FROM mata_pelajaran WHERE id='$data_histori[pelajaran]'";
      $result_pelajaran = $connection->query($query_pelajaran);
      $data_pelajaran = $result_pelajaran->fetch_assoc();

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
                  <small class="text-secondary">'.($data_pelajaran['nama_mapel']??'-').'</small>
                </div>

                <div class="col-auto">
                  <div class="dropdown dropleft">
                    <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                      <i class="fas fa-ellipsis-v"></i>
                    </a>
                    <div class="dropdown-menu dropdown-width-50 ml-3">
                      <a href="javascript:void(0);" class="dropdown-item small btn-update" data-id="'.strip_tags(convert('encrypt',$data_histori['absen_id']??'0')).'">Edit</a>
                    </div>
                  </div> 
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
      echo'<div class="alert alert-secondary mt-3">Saat ini, data kehadiran E_KBM belum tersedia atau masih kosong!</div>';
  }

  /** Load More data absen_ekbmsi */
break;
case 'data-histori-load':
$id = anti_injection($_POST['id']??'-');

$filterParts = [];
$tanggal = isset($_GET['tanggal']) ? date('Y-m-d', strtotime($_GET['tanggal'])) : $date;
$filterParts[] = "absen_ekbm.tanggal='$tanggal'";

if (!empty($_GET['kelas'])) {
    $kelas = htmlentities($_GET['kelas']);
    $filterParts[] = "absen_ekbm.kelas='$kelas'";
}

$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query_count    ="SELECT COUNT(absen_ekbm.absen_id) AS total FROM absen_ekbm $filter AND absen_ekbm.absen_id < $id";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 25;
$data_pelajaran = NULL;
$query_histori = "SELECT absen_ekbm.*,user.nama_lengkap,user.kelas FROM absen_ekbm 
LEFT JOIN user ON absen_ekbm.user_id=user.user_id $filter AND absen_ekbm.absen_id < $id ORDER BY user.nama_lengkap ASC LIMIT $showLimit";
$result_histori = $connection->query($query_histori);
if($result_histori->num_rows > 0){
    while ($data_histori= $result_histori->fetch_assoc()) {
      $absen_id =  htmlentities($data_histori['absen_id']);
        
      $query_pelajaran="SELECT nama_mapel FROM mata_pelajaran WHERE id='$data_histori[pelajaran]'";
      $result_pelajaran = $connection->query($query_pelajaran);
      $data_pelajaran = $result_pelajaran->fetch_assoc();

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
                  <small class="text-secondary">'.($data_pelajaran['nama_mapel']??'-').'</small>
                </div>

                <div class="col-auto">
                  <div class="dropdown dropleft">
                    <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                      <i class="fas fa-ellipsis-v"></i>
                    </a>
                    <div class="dropdown-menu dropdown-width-50 ml-3">
                      <a href="./rekap-absen_ekbm-siswa&op=detail&id='.strip_tags(convert('encrypt',$data_histori['absen_id']??'0')).'" class="dropdown-item small">Detail</a>
                    </div>
                  </div> 
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
     echo'<div class="alert alert-secondary mt-3">Saat ini, data kehadiran sudah tidak ada!</div>';
  }

  
break;
case 'get-data-update':
if(isset($_POST['id']) && !empty($_POST['id'])){  
  $id       = anti_injection(convert("decrypt",$_POST['id']));
  $query  = "SELECT * FROM absen_ekbm WHERE  absen_id='$id'";
  $result = $connection->query($query);
  if($result->num_rows > 0){
    $data_histori = $result->fetch_assoc();
    $data['id']         = convert("encrypt",$data_histori['absen_id']);
    $data['keterangan']= strip_tags($data_histori["keterangan"]??'');
    echo json_encode($data);
  }else{
    echo'Dataa tidak ditemukan!';
  }
}

/** Update Keterangan */
break;
case 'update':
  $error = array();
      if (empty($_POST['id'])) {
        $error[] = 'ID tidak boleh kosong';
      } else {
        $id = htmlentities(convert("decrypt",$_POST['id']));
      }
  
      if (empty($_POST['keterangan'])) {
          $error[] = 'Keterangan tidak boleh kosong';
        } else {
          $keterangan = anti_injection($_POST['keterangan']);
      }

    if (empty($error)) {
      $update="UPDATE absen_ekbm SET keterangan='$keterangan' WHERE absen_id='$id'"; 
      if($connection->query($update) === false) { 
          die($connection->error.__LINE__); 
          echo'Data tidak berhasil disimpan!';
      } else{
          echo'success';
      }

    }else{
      foreach ($error as $key => $values) {            
        echo"$values\n";
      }
    }


break;
  }
}