<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:./404');
}else{
  require_once'../../../sw-library/sw-config.php';
  require_once'../../../sw-library/sw-function.php';
  require_once'../../oauth/user.php';

switch (@$_GET['action']){
case 'data-histori':
if (empty($_GET['mulai']) OR empty($_GET['selesai'])){ 
  $filter = "AND MONTH(tanggal) ='$month' AND YEAR(tanggal) ='$year'";
} else { 
  $mulai = date('Y-m-d', strtotime($_GET['mulai']));
  $selesai = date('Y-m-d', strtotime($_GET['selesai']));
  $filter = "AND tanggal BETWEEN  '$mulai' AND '$selesai'";
}

$query_histori ="SELECT * FROM absen WHERE user_id='$data_user[user_id]' $filter ORDER BY absen_id DESC LIMIT 15";
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
                <div class="col-12 align-self-center">
                    <p class="text-secondary"><small class="text-secondary">'.tanggal_ind($data_histori['tanggal']).'</small> '.$kehadiran.'</p>
                </div> 
            </div>
            <hr class="p-0 mt-1 mb-2">

            <div class="row align-items-center">
                <div class="col-6 align-self-center">
                  <small class="text-info">CHECK IN</small>
                  <p class="text-secondary">'.strip_tags($data_histori['absen_in']??'-').'</p>
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
                      <a href="./histori-absen&op=detail&id='.strip_tags(convert('encrypt',$data_histori['absen_id']??'0')).'" class="dropdown-item small">Detail</a>
                      <button class="dropdown-item small btn-update" data-id="'.strip_tags(convert('encrypt',$data_histori['absen_id']??'0')).'" type="button">Edit</button>
                      
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
$id = anti_injection($_POST['id']);

if (empty($_POST['mulai']) OR empty($_POST['selesai'])){ 
  $filter = "AND MONTH(tanggal) ='$month' AND YEAR(tanggal) ='$year'";
} else { 
  $mulai = date('Y-m-d', strtotime($_POST['mulai']));
  $selesai = date('Y-m-d', strtotime($_POST['selesai']));
  $filter = "AND tanggal BETWEEN '$mulai' AND '$selesai'";
}


$query_count    ="SELECT COUNT(absen_id) AS total FROM absen WHERE user_id='$data_user[user_id]' $filter AND absen_id < $id ORDER BY absen_id DESC";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 15;
$query_histori ="SELECT * FROM absen WHERE user_id='$data_user[user_id]' $filter AND absen_id < $id ORDER BY absen_id DESC LIMIT $showLimit";
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
                <div class="col-12 align-self-center">
                    <p class="text-secondary"><small class="text-secondary">'.tanggal_ind($data_histori['tanggal']).'</small> '.$kehadiran.'</p>
                </div> 
            </div>
            <hr class="p-0 mt-1 mb-2">

            <div class="row align-items-center">
                <div class="col-6 align-self-center">
                  <small class="text-info">CHECK IN</small>
                  <p class="text-secondary">'.strip_tags($data_histori['absen_in']??'-').'</p>
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
                      <a href="./histori-absen&op=detail&id='.strip_tags(convert('encrypt',$data_histori['absen_id']??'0')).'" class="dropdown-item small">Detail</a>
                      <button class="dropdown-item small btn-update" data-id="'.strip_tags(convert('encrypt',$data_histori['absen_id']??'0')).'" type="button">Edit</button>
                      
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
  $query  = "SELECT * FROM absen WHERE user_id='".htmlentities($data_user['user_id'], ENT_QUOTES, 'UTF-8')."' AND absen_id='$id'";
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
      $update="UPDATE absen SET keterangan='$keterangan' WHERE absen_id='$id' AND user_id='".htmlentities($data_user['user_id'], ENT_QUOTES, 'UTF-8')."'"; 
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