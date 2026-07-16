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
$filterParts[] = "absen.tanggal='$tanggal' AND user.kelas='$data_user[wali_kelas]'";

if (!empty($_GET['siswa'])) {
    $siswa = htmlentities(convert("decrypt",$_GET['siswa']));
    $filterParts[] = "absen.user_id='$siswa'";
}

$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query_histori = "SELECT absen.*,user.nama_lengkap,user.kelas FROM absen 
LEFT JOIN user ON absen.user_id=user.user_id $filter ORDER BY user.nama_lengkap ASC LIMIT 20";
$result_histori = $connection->query($query_histori);
if($result_histori->num_rows > 0){
    while ($data_histori= $result_histori->fetch_assoc()) {
      $absen_id =  htmlentities($data_histori['absen_id']??'0');
        
        if($data_histori['kehadiran'] == 'Hadir'){
            $kehadiran = '<span class="badge badge-primary">'.strip_tags($data_histori['kehadiran']??'-').'</span>';
          }else{
            $kehadiran = '<span class="badge badge-warning">'.strip_tags($data_histori['kehadiran']??'-').'</span>';
        }

      echo'
      <div class="card border-0 mb-2">
        <div class="card-body pt-2">
            <div class="row">
                <div class="col-10 align-self-center">
                    <p class="text-secondary">
                      <small class="text-secondary">'.tgl_indo($data_histori['tanggal']).'</small> 
                    </p>
                </div> 
                <div class="col-2 text-right">
                  '.$kehadiran.'
                </div>
            </div>
            <hr class="p-0 mt-1 mb-2">
            <div class="row">
              <div class="col-12 align-self-center">
                  <p class="text-secondary mb-0">'.($data_histori['nama_lengkap']??'-').' <span class="badge badge-primary">'.($data_histori['kelas']??'-').'</span></p>
              </div> 
            </div>

            <div class="row align-items-center">
                <div class="col-6 align-self-center">
                  <small class="text-info">CHECK IN</small>
                  <p class="text-secondary">'.($data_histori['absen_in']??'-').'</p>
                </div>

                <div class="col align-self-center">
                  <small class="text-danger">CHECK OUT</small>
                  <p class="text-secondary">' . ((empty($data_histori['absen_out']) || $data_histori['absen_out'] == '00:00:00') ? '-' : strip_tags($data_histori['absen_out']??'-')) . '</p>
                </div>

                <div class="col-auto">
                  <div class="dropdown dropleft">
                    <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                      <i class="fas fa-ellipsis-v"></i>
                    </a>
                    <div class="dropdown-menu dropdown-width-50 ml-3">
                      <a href="./rekap-absen-siswa&op=detail&id='.strip_tags(convert('encrypt',$data_histori['absen_id']??'0')).'" class="dropdown-item small">Detail</a>
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
      echo'<div class="alert alert-secondary mt-3">Saat ini, data kehadiran belum tersedia atau masih kosong!</div>';
  }


  /** Load More data absensi */
break;
case 'data-histori-load':
$id = anti_injection($_POST['id']??'-');

$filterParts = [];
$tanggal = isset($_POST['tanggal']) ? date('Y-m-d', strtotime($_POST['tanggal'])) : $date;
$filterParts[] = "absen.tanggal='$tanggal'";

if (!empty($_POST['siswa'])) {
    $siswa = htmlentities(convert("decrypt",$_POST['siswa']));
    $filterParts[] = "absen.user_id='$siswa'";
}

$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query_count    ="SELECT COUNT(absen.absen_id) AS total FROM absen $filter AND absen.absen_id < $id";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 20;
$query_histori = "SELECT absen.*,user.nama_lengkap,user.kelas FROM absen 
LEFT JOIN user ON absen.user_id=user.user_id $filter AND absen.absen_id < $id ORDER BY user.nama_lengkap ASC LIMIT $showLimit";
$result_histori = $connection->query($query_histori);
if($result_histori->num_rows > 0){
    while ($data_histori= $result_histori->fetch_assoc()) {
      $absen_id =  htmlentities($data_histori['absen_id']);
        
        if($data_histori['kehadiran'] == 'Hadir'){
            $kehadiran = '<span class="badge badge-primary">'.strip_tags($data_histori['kehadiran']??'-').'</span>';
          }else{
            $kehadiran = '<span class="badge badge-warning">'.strip_tags($data_histori['kehadiran']??'-').'</span>';
        }
        
      echo'
      <div class="card border-0 mb-2">
        <div class="card-body pt-2">
            <div class="row">
                <div class="col-10 align-self-center">
                    <p class="text-secondary">
                      <small class="text-secondary">'.tgl_indo($data_histori['tanggal']).'</small> 
                    </p>
                </div> 
                <div class="col-2 text-right">
                  '.$kehadiran.'
                </div>
            </div>
            <hr class="p-0 mt-1 mb-2">
            <div class="row">
              <div class="col-12 align-self-center">
                  <p class="text-secondary mb-0">'.($data_histori['nama_lengkap']??'-').' <span class="badge badge-primary">'.($data_histori['kelas']??'-').'</span></p>
              </div> 
            </div>

            <div class="row align-items-center">
                <div class="col-6 align-self-center">
                  <small class="text-info">CHECK IN</small>
                  <p class="text-secondary">'.($data_histori['absen_in']??'-').'</p>
                </div>

                <div class="col align-self-center">
                  <small class="text-danger">CHECK OUT</small>
                  <p class="text-secondary">' . ((empty($data_histori['absen_out']) || $data_histori['absen_out'] == '00:00:00') ? '-' : strip_tags($data_histori['absen_out']??'-')) . '</p>
                </div>

                <div class="col-auto">
                  <div class="dropdown dropleft">
                    <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                      <i class="fas fa-ellipsis-v"></i>
                    </a>
                    <div class="dropdown-menu dropdown-width-50 ml-3">
                      <a href="./rekap-absen-siswa&op=detail&id='.strip_tags(convert('encrypt',$data_histori['absen_id']??'0')).'" class="dropdown-item small">Detail</a>
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
  }
}