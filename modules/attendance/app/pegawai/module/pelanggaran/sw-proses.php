<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
  echo'Not Found';
}else{
  require_once'../../../sw-library/sw-config.php';
  require_once'../../../sw-library/sw-function.php';
  require_once'../../oauth/user.php';
  $counter = 0;
  $more_cards = '';
  
switch (@$_GET['action']){
case 'data-pelanggaran':
$filterParts = [];
$bulan      = isset($_GET['bulan']) ? strip_tags($_GET['bulan']) : $month;
$filterParts[] = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$year'";

if (!empty($_GET['siswa'])) {
    $siswa = convert("decrypt", $_GET['siswa']??'0');
    $filterParts[] = "pelanggaran.user_id='$siswa'";
}

$filter = 'WHERE ' . implode(' AND ', $filterParts);

echo'
<div class="row justify-content-equal s-widodo.com">';
$query_pelanggaran = "SELECT user.nama_lengkap, SUM(pelanggaran.bobot) AS total_bobot 
                      FROM pelanggaran 
                      LEFT JOIN user ON user.user_id = pelanggaran.user_id 
                      WHERE MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$year'
                      GROUP BY user.user_id 
                      ORDER BY total_bobot DESC LIMIT 4";
$result_pelanggaran = $connection->query($query_pelanggaran);
while($data = $result_pelanggaran->fetch_assoc()) {
  $total_bobot = $data['total_bobot'] ?? 0;
  $counter++;
  if ($total_bobot > 50) {
      $card_class = 'bg-danger text-white'; // Kelas untuk total_bobot > 50
  } elseif ($total_bobot > 30) {
      $card_class = 'bg-warning'; // Kelas untuk total_bobot > 30
  } elseif ($total_bobot > 10) {
      $card_class = 'bg-info text-white'; // Kelas untuk total_bobot > 10
  } else {
      $card_class = 'bg-normal'; // Kelas untuk total_bobot <= 10
  }

  if ($counter <= 2) {
    echo'
    <div class="col-6 col-md-6 s-widodo.com">
        <div class="card mb-2 s-widodo.com '.$card_class.'">
            <div class="card-body">
              <h7 class="mb-1">'.strip_tags($data['nama_lengkap']??'-').'</h7>
              <p class="small">Bobot: 
                <span class="badge badge-danger"> '.strip_tags($data['total_bobot']??'0').'</span>
              </p> 
            </div>
        </div>
    </div>';
  }else{
     $more_cards .='
    <div class="col-6 col-md-6 s-widodo.com">
        <div class="card mb-2 s-widodo.com '.$card_class.'">
            <div class="card-body">
              <h7 class="mb-1">'.strip_tags($data['nama_lengkap']??'-').'</h7>
              <p class="small">Bobot: 
                <span class="badge badge-danger"> '.strip_tags($data['total_bobot']??'0').'</span>
              </p> 
            </div>
        </div>
    </div>';
  }
}
echo'
</div>';
if (!empty($more_cards)) {
    echo '
    <div class="row justify-content-equal more-expand" style="display:none;">
        '.$more_cards.'
    </div>';
}
echo'
<hr class="mt-2 mb-2">';
$query_pelanggaran ="SELECT pelanggaran.*,user.nama_lengkap FROM pelanggaran
LEFT JOIN user ON user.user_id= pelanggaran.user_id $filter AND pelanggaran.kelas='{$data_user['wali_kelas']}' ORDER BY pelanggaran.pelanggaran_id DESC LIMIT 10";
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

if (!empty($_POST['siswa'])) {
    $siswa = convert("decrypt", $_POST['siswa']??'0');
    $filterParts[] = "pelanggaran.user_id='$siswa'";
}

$filter = 'AND ' . implode(' AND ', $filterParts);
$id = anti_injection($_POST['id']);

$query_count    ="SELECT COUNT(pelanggaran_id) AS total FROM pelanggaran WHERE pelanggaran_id < $id $filter AND kelas='{$data_user['wali_kelas']}' ORDER BY pelanggaran_id DESC";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 10;
$query_pelanggaran ="SELECT pelanggaran.*,user.nama_lengkap FROM pelanggaran
LEFT JOIN user ON user.user_id= pelanggaran.user_id WHERE pelanggaran.pelanggaran_id < $id $filter AND pelanggaran.kelas='{$data_user['wali_kelas']}' 
ORDER BY pelanggaran.pelanggaran_id DESC LIMIT $showLimit";
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