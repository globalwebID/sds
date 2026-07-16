<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:../404');
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';

$counter = 0;
$more_cards = '';

$bulan_romawi = [
    1 => "I", 2 => "II", 3 => "III", 4 => "IV", 5 => "V", 6 => "VI",
    7 => "VII", 8 => "VIII", 9 => "IX", 10 => "X", 11 => "XI", 12 => "XII"
];
  
switch (@$_GET['action']){
case 'data-sanksi':
$filterParts = [];
$bulan      = isset($_GET['bulan']) ? strip_tags($_GET['bulan']) : $month;
$filterParts[] = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$year'";

$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query_sanksi = "SELECT sanksi_pelanggaran.*, user.nama_lengkap,user.kelas 
                 FROM sanksi_pelanggaran 
                 LEFT JOIN user ON user.user_id = sanksi_pelanggaran.user_id
                 $filter AND user.user_id='{$data_user['user_id']}'
                 ORDER BY sanksi_pelanggaran.id DESC LIMIT 10";
$result_sanksi = $connection->query($query_sanksi);
if ($result_sanksi->num_rows > 0) {
  while ($data_sanksi = $result_sanksi->fetch_assoc()) {
  $sanksi_id = anti_injection($data_sanksi['id']);

echo'
<div class="card border-0 mb-2">
    <div class="card-body">
        <div class="row">
          <div class="col align-self-center">
              <small><a href="../print-sanksi?id='.convert("encrypt",$data_sanksi['id']).'" class="text-info" target="_blank">'.$data_sanksi['kode_surat'].'</a> | '.tanggal_ind($data_sanksi['tanggal']).'</small>
          </div> 
        </div>

        <div class="row align-items-center mt-1">
            <div class="col align-self-center">
              <span class="text-secondary">
               '.$data_sanksi['nama_lengkap'].'
              </span> 

              <p class="text-secondary">
                Kelas : <span class="badge badge-info">'.strip_tags($data_sanksi['kelas']??'-').'</span>
              </p>
            </div>
        
            <div class="col-auto align-self-center">
                  <a href="../print-sanksi?id='.convert("encrypt",$data_sanksi['id']).'" class="btn btn-sm btn-link text-dark" target="_blank">
                    <i class="fas fa-print"></i></i>
                  </a>
            </div>

            <div class="col-12">
              <hr class="mt-2 mb-1">
              <p class="text-secondary">'.strip_tags($data_sanksi['perihal']??'-').'</p>
            </div> 

        </div>
    </div>
</div>';
}
  echo'
  <div class="text-center show_more_main'.$sanksi_id.' mt-4">
      <button data-id="'.$sanksi_id.'" class="btn btn-light rounded load-more">Show more</button>
  </div>';

}else{
  echo'<div class="alert alert-secondary mt-3">Saat ini, data surat sanksi belum tersedia atau masih kosong!</div>';
}

/** Moad More*/
break;
case 'data-pelanggaran-load':

$filterParts = [];
$bulan      = isset($_POST['bulan']) ? strip_tags($_POST['bulan']) : $month;
$tahun      = isset($_POST['tahun']) ? strip_tags($_POST['tahun']) : $year;
$filterParts[] = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun'";

$filter = 'AND ' . implode(' AND ', $filterParts);

$id = anti_injection($_POST['id']);

$query_count    ="SELECT COUNT(id) AS total FROM sanksi_pelanggaran 
WHERE id < $id $filter AND user_id='{$data_user['user_id']}' ORDER BY id DESC";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 10;
$query_sanksi = "SELECT sanksi_pelanggaran.*, user.nama_lengkap,user.kelas 
                 FROM sanksi_pelanggaran 
                 LEFT JOIN user ON user.user_id = sanksi_pelanggaran.user_id
                 WHERE id < $id $filter AND user.user_id='{$data_user['user_id']}'
                 ORDER BY sanksi_pelanggaran.id DESC LIMIT $showLimit";
$result_sanksi = $connection->query($query_sanksi);
if ($result_sanksi->num_rows > 0) {
  while ($data_sanksi = $result_sanksi->fetch_assoc()) {
  $sanksi_id = anti_injection($data_sanksi['id']);
echo'
<div class="card border-0 mb-2">
    <div class="card-body">
        <div class="row">
          <div class="col align-self-center">
              <small><a href="../print-sanksi?id='.convert("encrypt",$data_sanksi['id']).'" class="text-info" target="_blank">'.$data_sanksi['kode_surat'].'</a> | '.tanggal_ind($data_sanksi['tanggal']).'</small>
          </div> 
        </div>

        <div class="row align-items-center mt-1">
            <div class="col align-self-center">
              <span class="text-secondary">
               '.$data_sanksi['nama_lengkap'].'
              </span> 

              <p class="text-secondary">
                Kelas : <span class="badge badge-info">'.strip_tags($data_sanksi['kelas']??'-').'</span>
              </p>
            </div>
        
            <div class="col-auto align-self-center">
                  <a href="../print-sanksi?id='.convert("encrypt",$data_sanksi['id']).'" class="btn btn-sm btn-link text-dark" target="_blank">
                    <i class="fas fa-print"></i></i>
                  </a>
            </div>

            <div class="col-12">
              <hr class="mt-2 mb-1">
              <p class="text-secondary">'.strip_tags($data_sanksi['perihal']??'-').'</p>
            </div> 

        </div>
    </div>
</div>';
}

  if($totalRowCount > $showLimit){
    echo'
    <div class="text-center show_more_main'.$sanksi_id.' mt-4">
        <button data-id="'.$sanksi_id.'" class="btn btn-light rounded load-more">Show more</button>
    </div>';
  }

}else{
  echo'<div class="alert alert-secondary mt-3">Saat ini, data surat sanksi sudah tidak ada!</div>';
}

break;
case 'dropdown-siswa':
if (!empty($_POST['siswa'])) {

    $siswa = convert("decrypt", $_POST['siswa']);
    $query ="SELECT * FROM user WHERE user_id='$siswa'";
    $result = $connection->query($query);

    if($result->num_rows > 0){

        $data_siswa = $result->fetch_assoc();

        // AMBIL DATA WALI
        $q_wali = "SELECT wali_murid_id, nama_lengkap FROM wali_murid WHERE nisn='{$data_siswa['nisn']}'";
        $r_wali = $connection->query($q_wali);

        if($r_wali->num_rows > 0){
            $data_wali = $r_wali->fetch_assoc();
            $response['wali_murid'] = ($data_wali['wali_murid_id']??'0');
            $response['nama_lengkap'] = $data_wali['nama_lengkap'];
        }else{
            $response['nama_lengkap'] = 'Tidak ditemukan';
        }

        // AMBIL DATA PELANGGARAN
        $q_pelanggaran = "SELECT * FROM pelanggaran WHERE user_id='{$data_siswa['user_id']}'";
        $r_pelanggaran = $connection->query($q_pelanggaran);

        $pelanggaran_data = [];

        while ($row = $r_pelanggaran->fetch_assoc()) {
            $pelanggaran_data[] = $row;
        }

        // Masukkan pelanggaran ke dalam response
        $response['status'] = 'success';
        $response['pelanggaran'] = $pelanggaran_data;

    }else{
        $response = [
            'status' => 'error',
            'message' => 'Data siswa tidak ditemukan'
        ];
    }

    echo json_encode($response);
}

break;
  }
}