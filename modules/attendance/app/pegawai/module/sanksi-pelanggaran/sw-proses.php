<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
  echo'Not Found';
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

if (!empty($_GET['siswa'])) {
    $siswa = convert("decrypt", $_GET['siswa']??'0');
    $filterParts[] = "user.user_id='$siswa'";
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

$query_sanksi = "SELECT sanksi_pelanggaran.*, user.nama_lengkap,user.kelas 
                 FROM sanksi_pelanggaran 
                 LEFT JOIN user ON user.user_id = sanksi_pelanggaran.user_id
                 $filter AND pegawai_id='{$data_user['pegawai_id']}'
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
              <div class="dropdown dropleft">
                  <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                  </a>
                  <div class="dropdown-menu dropdown-width-50 ml-3">
                    <a href="../print-sanksi?id='.convert("encrypt",$data_sanksi['id']).'" class="dropdown-item small btn-print" target="_blank">Print</a>
                    <a href="./sanksi-pelanggaran&op='.convert("encrypt", "update").'&id='.convert("encrypt",$data_sanksi['id']).'" class="dropdown-item small">Edit</a>
                    <button class="dropdown-item small btn-delete" data-id="'.convert("encrypt",$data_sanksi['id']).'" type="button">Hapus</button>
                  </div>
              </div> 
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


if (!empty($_POST['siswa'])) {
    $siswa = htmlentities($_POST['siswa']??'0');
    $filterParts[] = "pelanggaran.user_id='$siswa'";
}

$filter = 'AND ' . implode(' AND ', $filterParts);

$id = anti_injection($_POST['id']);

$query_count    ="SELECT COUNT(id) AS total FROM sanksi_pelanggaran 
WHERE id < $id $filter AND pegawai_id='{$data_user['pegawai_id']}' ORDER BY id DESC";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 10;
$query_sanksi = "SELECT sanksi_pelanggaran.*, user.nama_lengkap,user.kelas 
                 FROM sanksi_pelanggaran 
                 LEFT JOIN user ON user.user_id = sanksi_pelanggaran.user_id
                 WHERE id < $id $filter AND pegawai_id='{$data_user['pegawai_id']}'
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
              <div class="dropdown dropleft">
                  <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                  </a>
                  <div class="dropdown-menu dropdown-width-50 ml-3">
                    <a href="../print-sanksi?id='.convert("encrypt",$data_sanksi['id']).'" class="dropdown-item small btn-print" target="_blank">Print</a>
                    <a href="./sanksi-pelanggaran&op='.convert("encrypt", "update").'&id='.convert("encrypt",$data_sanksi['id']).'" class="dropdown-item small">Edit</a>
                    <button class="dropdown-item small btn-delete" data-id="'.convert("encrypt",$data_sanksi['id']).'" type="button">Hapus</button>
                  </div>
              </div> 
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

/** Tambah baru*/
break;
case 'add':
$error = [];

$fields = [
    'siswa'             => 'Siswa',
    'wali_murid'        => 'Wali Murid',
    'ditujukan'         => 'Nama Wali',
    'perihal'           => 'Perihal',
    'keterangan'        => 'Keterangan'
];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        $value = mysqli_real_escape_string($connection, $_POST[$key]);
        $$key = $value;
    }
  }

  $siswa = convert("decrypt",$siswa);

 

  if (empty($error)){
    
    $sql = "SELECT kode,template FROM template_surat WHERE tipe='pelanggaran' LIMIT 1";
    $result = mysqli_query($connection, $sql);
    if ($result && mysqli_num_rows($result) > 0) {
      $row = mysqli_fetch_assoc($result);
      $jenis_surat = ''.$row['kode'].''; 
    }else{
      die('Template surat tidak ditemukan, Silahkan Hub Admin..');
    }
    
    // Ambil nomor surat terakhir tahun ini untuk jenis surat yang sama
    $sql = "SELECT id FROM sanksi_pelanggaran WHERE YEAR(tanggal) = '$year' ORDER BY id DESC LIMIT 1";
    $q = mysqli_query($connection, $sql);
    $d = mysqli_fetch_assoc($q);
    if ($d) {
        $last_number = (int) substr($d['nomor_surat'], 0, 2);
        $nomor_berikut = $last_number + 1;
    } else {
        $nomor_berikut = 1;
    }

      $nomor_urut = str_pad($nomor_berikut, 2, "0", STR_PAD_LEFT);
      $kode_surat = "$nomor_urut/$jenis_surat/KS/" . $bulan_romawi[$month] . "/$year";

        $notifikasi ="INSERT INTO notifikasi (user_id,
            pegawai_id,
            nama,
            keterangan,
            link,
            tanggal,
            datetime,
            tipe,
            tujuan,
            status) values('{$siswa}',
            '{$data_user['pegawai_id']}',
            '{$data_user['nama_lengkap']}',
            'Baru saja mengirimkan surat peringatan',
            'sanksi-pelanggaran',
            '$tanggal',
            '$timeNow',
            'pegawai',
            'siswa',
            'N')";

        $add = "INSERT INTO sanksi_pelanggaran (
            user_id,
            wali_murid,
            ditujukan,
            kode_surat,
            perihal,
            keterangan,
            template,
            tanggal,
            time
        ) VALUES (
            '{$siswa}',                
            '{$wali_murid}',          
            '{$ditujukan}',           
            '{$kode_surat}',          
            '{$perihal}',           
            '{$keterangan}',  
            '{$row['template']}',        
            '{$tanggal}',             
            '{$time}'                 
        )";

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

break;
case 'update':
$error = [];

$fields = [
    'id'                => 'ID',
    'siswa'             => 'Siswa',
    'wali_murid'        => 'Wali Murid',
    'ditujukan'         => 'Nama Wali',
    'perihal'           => 'Perihal',
    'keterangan'        => 'Keterangan'
];

// Validasi input
foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        $value = mysqli_real_escape_string($connection, $_POST[$key]);
        $$key = $value;
    }
}


$id_sanksi = anti_injection(convert("decrypt", $_POST['id']??'0'));
$siswa = convert("decrypt", $siswa);

if (empty($error)) {
    // Update data sanksi
    $update = "UPDATE sanksi_pelanggaran SET
                user_id      = '{$siswa}',
                wali_murid   = '{$wali_murid}',
                ditujukan    = '{$ditujukan}',
                perihal      = '{$perihal}',
                keterangan   = '{$keterangan}',
                tanggal      = '{$tanggal}',
                time         = '{$time}'
               WHERE id = '{$id_sanksi}'";
    if($connection->query($update) === false) { 
        echo 'Sepertinya Sistem Kami sedang error!';
        die($connection->error.__LINE__); 
    } else {
        echo 'success';
    }

} else {       
    foreach ($error as $values) {            
        echo "$values\n";
    }
}

/** Get Update Data */
break;
case 'get-data-update':
if (isset($_POST['id'])) {
    $id = anti_injection(convert("decrypt", $_POST['id']));
    $query_pelanggaran = "SELECT * FROM pelanggaran WHERE s$sanksi_id='$id'";
    $result_pelanggaran = $connection->query($query_pelanggaran);
    if ($result_pelanggaran->num_rows > 0) {
        $data_sanksi = $result_pelanggaran->fetch_assoc();

        $data['id']     = convert("encrypt", $data_sanksi["s$sanksi_id"]);
        $data['kelas']  = $data_sanksi['kelas'];
        $data['siswa']  = $data_sanksi['user_id'];
        $data['jenis_pelanggaran'] = $data_sanksi['jenis_pelanggaran'];
        $data['bentuk_pelanggaran'] = $data_sanksi['bentuk_pelanggaran'];

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
    $query ="SELECT * FROM bentuk_pelanggaran WHERE bentuk_s$sanksi_id='$bentuk_pelanggaran'";      
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
            time='$time' WHERE s$sanksi_id='$id'";
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
  $deleted = "DELETE FROM sanksi_pelanggaran WHERE pegawai_id='{$data_user['pegawai_id']}' AND id='$id'";
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