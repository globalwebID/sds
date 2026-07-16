<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
  echo'Not Found';
}else{
  require_once'../../../sw-library/sw-config.php';
  require_once'../../../sw-library/sw-function.php';
  require_once'../../oauth/user.php';

switch (@$_GET['action']){
case 'data-pelanggaran':
$filterParts = [];
$bulan      = isset($_GET['bulan']) ? strip_tags($_GET['bulan']) : $month;
$tahun      = isset($_GET['tahun']) ? strip_tags($_GET['tahun']) : $year;
$filterParts[] = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun'";

if (!empty($_GET['kelas'])) {
    $kelas = htmlentities($_GET['kelas']??'0');
    $filterParts[] = "pelanggaran.kelas='$kelas'";
}

if (!empty($_GET['siswa'])) {
    $siswa = htmlentities($_GET['siswa']??'0');
    $filterParts[] = "pelanggaran.user_id='$siswa'";
}

$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query_pelanggaran ="SELECT pelanggaran.*,user.nama_lengkap FROM pelanggaran
LEFT JOIN user ON user.user_id= pelanggaran.user_id $filter AND pelanggaran.pegawai_id='{$data_user['pegawai_id']}' ORDER BY pelanggaran.pelanggaran_id DESC LIMIT 10";
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
        
            <div class="col-auto align-self-center">
              <div class="dropdown dropleft">
                  <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                  </a>
                  <div class="dropdown-menu dropdown-width-50 ml-3">
                    <button class="dropdown-item small btn-update" data-id="'.convert("encrypt",$data_pelanggaran['pelanggaran_id']).'" type="button">Edit</button>
                    <button class="dropdown-item small btn-delete" data-id="'.convert("encrypt",$data_pelanggaran['pelanggaran_id']).'" type="button">Hapus</button>
                  </div>
              </div> 
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
  echo'<div class="alert alert-secondary mt-3">Saat ini, data pengajuan pelanggaran belum tersedia atau masih kosong!</div>';
}

/** Moad More*/
break;
case 'data-pelanggaran-load':

$filterParts = [];
$bulan      = isset($_POST['bulan']) ? strip_tags($_POST['bulan']) : $month;
$tahun      = isset($_POST['tahun']) ? strip_tags($_POST['tahun']) : $year;
$filterParts[] = "MONTH(tanggal)='$bulan' AND YEAR(tanggal)='$tahun'";

if (!empty($_POST['kelas'])) {
    $kelas = htmlentities($_POST['kelas']??'0');
    $filterParts[] = "pelanggaran.kelas='$kelas'";
}

if (!empty($_POST['siswa'])) {
    $siswa = htmlentities($_POST['siswa']??'0');
    $filterParts[] = "pelanggaran.user_id='$siswa'";
}

$filter = 'AND ' . implode(' AND ', $filterParts);

$id = anti_injection($_POST['id']);

$query_count    ="SELECT COUNT(pelanggaran_id) AS total FROM pelanggaran WHERE pelanggaran_id < $id $filter AND pegawai_id='{$data_user['pegawai_id']}' ORDER BY pelanggaran_id DESC";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 10;
$query_pelanggaran ="SELECT pelanggaran.*,user.nama_lengkap FROM pelanggaran
LEFT JOIN user ON user.user_id= pelanggaran.user_id WHERE pelanggaran.pelanggaran_id < $id $filter AND pelanggaran.pegawai_id='{$data_user['pegawai_id']}' 
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
        
            <div class="col-auto align-self-center">
              <div class="dropdown dropleft">
                  <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                  </a>
                  <div class="dropdown-menu dropdown-width-50 ml-3">
                    <button class="dropdown-item small btn-update" data-id="'.convert("encrypt",$data_pelanggaran['pelanggaran_id']).'" type="button">Edit</button>
                    <button class="dropdown-item small btn-delete" data-id="'.convert("encrypt",$data_pelanggaran['pelanggaran_id']).'" type="button">Hapus</button>
                  </div>
              </div> 
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
case 'dropdown-kelas':
if (!empty($_POST['kelas'])) {
  $kelas = anti_injection($_POST['kelas']);
  $query_siswa = "SELECT user_id,nama_lengkap FROM user WHERE kelas='$kelas'";
  $result_siswa = $connection->query($query_siswa);
  if($result_siswa->num_rows > 0) {
    while($data_siswa = $result_siswa->fetch_assoc()){
      if(isset($_POST['user'])){
        $selected = ($_POST['user'] == $data['user_id']) ? 'selected' : '';
      }else{
        $selected = '';
      }

      echo'<option value="'.$data_siswa['user_id'].'" '.$selected.'>'.strip_tags($data_siswa['nama_lengkap']).'</option>';
    }
  }else{
    echo'<option value="">Data tidak ditemukan</option>';
  }
}

break;
case'dropdown-kategori':
if(!empty($_POST['id'])){
  $id = htmlspecialchars($_POST['id']);
  $query ="SELECT * FROM bentuk_pelanggaran WHERE kategori_pelanggaran_id='$id' ORDER BY bobot ASC";
  $result = $connection->query($query);
  while($data = $result->fetch_assoc()){
    if(isset($_POST['bentuk_pelanggaran'])){
      $selected = ($_POST['bentuk_pelanggaran'] == $data['bentuk_pelanggaran']) ? 'selected' : '';
    }else{
      $selected = '';
    }
    echo'<option value="'.$data['bentuk_pelanggaran_id'].'" '.$selected.'>'.$data['bentuk_pelanggaran'].' : ['.$data['bobot'].']</option>';
  }
}else{
  echo'<option>Data tidak ditemukan</option>';
}


/** Tambah baru*/
break;
case 'add':
$error = [];

$fields = [
    'kelas'             => 'kelas',
    'siswa'             => 'Siswa',
    'kategori'          => 'Jenis Pelanggaran',
    'bentuk_pelanggaran'=> 'Bentuk Pelanggaran'
];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
      $$key = anti_injection($_POST[$key]); 
    }
  }

  if (empty($error)){
    
    $query ="SELECT * FROM bentuk_pelanggaran WHERE bentuk_pelanggaran_id='$bentuk_pelanggaran'";      
    $result = $connection->query($query);
    if($result->num_rows > 0){
      $data = $result->fetch_assoc();
    }else{
      die("Data pelanggaran tidak ditemukan!");
    }
      
      $notifikasi ="INSERT INTO notifikasi (pegawai_id,
        nama,
        keterangan,
        link,
        tanggal,
        datetime,
        tipe,
        tujuan,
        status) values('{$data_user['pegawai_id']}',
        '{$data_user['nama_lengkap']}',
        'Baru saja mengirimkan data pelanggaran siswa',
        'pelanggaran',
        '$tanggal',
        '$timeNow',
        'siswa',
        'pegawai',
        'N')";

       $add = "INSERT INTO pelanggaran(
            pegawai_id,
            kelas,
            user_id,
            jenis_pelanggaran,
            bentuk_pelanggaran,
            bobot,
            tanggal,
            time) VALUES(
            '{$data_user['pegawai_id']}',
            '$kelas',
            '$siswa',
            '$kategori',
            '".$data['bentuk_pelanggaran']."',
            '".$data['bobot']."',
            '$tanggal',
            '$time')";

        if($connection->query($add) === false) { 
          echo'Sepertinya Sistem Kami sedang error!';
          die($connection->error.__LINE__); 
        } else{
          echo'success';
          $connection->query($notifikasi);
        }
  }else{       
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }



/** Get Update Data */
break;
case 'get-data-update':
if (isset($_POST['id'])) {
    $id = anti_injection(convert("decrypt", $_POST['id']));
    $query_pelanggaran = "SELECT * FROM pelanggaran WHERE pelanggaran_id='$id'";
    $result_pelanggaran = $connection->query($query_pelanggaran);
    if ($result_pelanggaran->num_rows > 0) {
        $data_pelanggaran = $result_pelanggaran->fetch_assoc();

        $data['id']     = convert("encrypt", $data_pelanggaran["pelanggaran_id"]);
        $data['kelas']  = $data_pelanggaran['kelas'];
        $data['siswa']  = $data_pelanggaran['user_id'];
        $data['jenis_pelanggaran'] = $data_pelanggaran['jenis_pelanggaran'];
        $data['bentuk_pelanggaran'] = $data_pelanggaran['bentuk_pelanggaran'];

        echo json_encode($data);
    } else {
        echo 'Data tidak ditemukan';
    }
}

/** Update data */
break;
case 'update':
$error = [];
$fields = [
    'id'                => 'ID',
    'kelas'             => 'Kelas',
    'siswa'             => 'Siswa',
    'kategori'          => 'Jenis Pelanggaran',
    'bentuk_pelanggaran'=> 'Bentuk Pelanggaran'
];

foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        if($key == 'id'){
          $id = anti_injection(convert("decrypt", $_POST[$key]));
        }else{
          $$key = anti_injection($_POST[$key]); 
        }
    }
}

if (empty($error)) {
    $query ="SELECT * FROM bentuk_pelanggaran WHERE bentuk_pelanggaran_id='$bentuk_pelanggaran'";      
    $result = $connection->query($query);
    if($result->num_rows > 0){
      $data = $result->fetch_assoc();
    }else{
      die("Data pelanggaran tidak ditemukan!");
    }
      
      $update = "UPDATE pelanggaran SET kelas='$kelas',
            user_id='$siswa',
            jenis_pelanggaran='$kategori',
            bentuk_pelanggaran='".$data['bentuk_pelanggaran']."',
            bobot='".$data['bobot']."',
            tanggal='$tanggal',
            time='$time' WHERE pelanggaran_id='$id'";
      if ($connection->query($update) === false) { 
          echo 'Sepertinya Sistem Kami sedang error!';
          die($connection->error.__LINE__); 
      } else {
          echo 'success';
      }
} else {
  foreach ($error as $key => $values) {
    echo "$values\n";
  }
}

/** Delete */
break;
case 'delete':
if(isset($_POST['id'])){
  $id       = anti_injection(convert("decrypt",$_POST['id']));
  $deleted = "DELETE FROM pelanggaran WHERE pegawai_id='{$data_user['pegawai_id']}' AND pelanggaran_id='$id'";
  if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }
}

break;
  }
}