<?php if(empty($connection) AND !isset($_COOKIE['wali_murid'])){
  echo'Not Found';
}else{
  require_once'../../../sw-library/sw-config.php';
  require_once'../../../sw-library/sw-function.php';
  require_once'../../oauth/user.php';
  $data_siswa = NULL;
  $data_siswa = getSiswa($connection, $data_user['nisn']);

  if (!$data_siswa) {
    echo "<div class='alert alert-secondary mt-3'>Data siswa dengan NISN {$data_user['nisn']} tidak ditemukan!</div>";
    exit; // hentikan proses
  }

switch (@$_GET['action']){
case 'data-pelanggaran':
$filterParts = [];
$bulan      = isset($_GET['bulan']) ? strip_tags($_GET['bulan']) : $month;
$filterParts[] = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$year'";

$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query_pelanggaran ="SELECT pelanggaran.*,user.nama_lengkap FROM pelanggaran
LEFT JOIN user ON user.user_id= pelanggaran.user_id $filter AND pelanggaran.user_id='{$data_siswa['user_id']}' ORDER BY pelanggaran.pelanggaran_id DESC LIMIT 10";
$result_pelanggaran = $connection->query($query_pelanggaran);
if($result_pelanggaran->num_rows > 0){
  while ($data_pelanggaran= $result_pelanggaran->fetch_assoc()){
  $pelanggaran_id = anti_injection($data_pelanggaran['pelanggaran_id']);

  $uqery_pegawai ="SELECT nama_lengkap FROM pegawai WHERE pegawai_id='{$data_pelanggaran['pegawai_id']}'";
  $result_pegawai = $connection->query($uqery_pegawai); 
  $data_pegawai = $result_pegawai->fetch_assoc();

echo'
<div class="card border-0 mb-2">
    <div class="card-body">
        <div class="row">
          <div class="col align-self-center">
              <p class="text-secondary"><span class="badge badge-warning">'.$data_pegawai['nama_lengkap'].'</span> 
              <small>'.tanggal_ind($data_pelanggaran['tanggal']).'</small></p>
          </div> 
        </div>

        <div class="row align-items-center mt-1">
            <div class="col align-self-center">
              <span class="text-secondary">
               '.$data_pelanggaran['nama_lengkap'].'
              </span> 
              <p class="text-secondary">
                Kelas : <span class="badge badge-info">'.strip_tags($data_pelanggaran['kelas']??'-').'</span>
              </p>
            </div>
      
            <div class="col-12">
              <hr class="mt-2 mb-1">
              <p class="text-secondary">'.strip_tags($data_pelanggaran['bentuk_pelanggaran']??'-').' 
              <span class="badge badge-danger">'.($data_pelanggaran['bobot']).'</span></p>
            </div> 

        </div>
    </div>
</div>';
}
  echo'
  <div class="text-center show_more_main'.$pelanggaran_id.' mt-4">
      <button data-id="'.$pelanggaran_id.'" class="btn btn-light rounded load-more">Show more</button>
  </div>';

}else{
  echo'<div class="alert alert-secondary mt-3">Saat ini, data pelanggaran belum tersedia atau masih kosong!</div>';
}

/** Moad More*/
break;
case 'data-pelanggaran-load':

$filterParts = [];
$bulan      = isset($_POST['bulan']) ? strip_tags($_POST['bulan']) : $month;
$filterParts[] = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$year'";

$filter = 'AND ' . implode(' AND ', $filterParts);
$id = anti_injection($_POST['id']);

$query_count    ="SELECT COUNT(pelanggaran_id) AS total FROM pelanggaran WHERE pelanggaran_id < $id $filter AND user_id='{$data_siswa['user_id']}' ORDER BY pelanggaran_id DESC";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 10;
$query_pelanggaran ="SELECT pelanggaran.*,user.nama_lengkap FROM pelanggaran
LEFT JOIN user ON user.user_id= pelanggaran.user_id WHERE pelanggaran.pelanggaran_id < $id $filter AND pelanggaran.user_id='{$data_siswa['user_id']}' ORDER BY pelanggaran.pelanggaran_id DESC LIMIT $showLimit";
$result_pelanggaran = $connection->query($query_pelanggaran);
if($result_pelanggaran->num_rows > 0){
  while ($data_pelanggaran= $result_pelanggaran->fetch_assoc()){
  $pelanggaran_id = anti_injection($data_pelanggaran['pelanggaran_id']);

echo'
<div class="card border-0 mb-2">
    <div class="card-body">
        <div class="row">
          <div class="col align-self-center">
              <p class="text-secondary"><span class="badge badge-warning">'.$data_pegawai['nama_lengkap'].'</span> 
              <small>'.tanggal_ind($data_pelanggaran['tanggal']).'</small></p>
          </div> 
        </div>

        <div class="row align-items-center mt-1">
            <div class="col align-self-center">
              <span class="text-secondary">
               '.$data_pelanggaran['nama_lengkap'].'
              </span> 
              <p class="text-secondary">
                Kelas : <span class="badge badge-info">'.strip_tags($data_pelanggaran['kelas']??'-').'</span>
              </p>
            </div>
  

            <div class="col-12">
              <hr class="mt-2 mb-1">
              <p class="text-secondary">'.strip_tags($data_pelanggaran['bentuk_pelanggaran']??'-').' 
              <span class="badge badge-danger">'.($data_pelanggaran['bobot']).'</span></p>
            </div> 

        </div>
    </div>
</div>';
}

  if($totalRowCount > $showLimit){
    echo'
    <div class="text-center show_more_main'.$pelanggaran_id.' mt-4">
        <button data-id="'.$pelanggaran_id.'" class="btn btn-light rounded load-more">Show more</button>
    </div>';
  }

}else{
  echo'<div class="alert alert-secondary mt-3">Saat ini, data pelanggaran sudah tidak ada!</div>';
}


  break;
  }
}