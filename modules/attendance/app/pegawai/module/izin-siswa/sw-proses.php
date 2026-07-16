<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
  echo'Not Found';
}else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../oauth/user.php';

function tambahAbsen($connection, $user_id, $tanggal, $lokasi_id, $alasan, $keterangan) {
  $cek_absen = "SELECT absen_id FROM absen WHERE user_id='$user_id' AND tanggal='$tanggal'";
  $result = $connection->query($cek_absen);
  if ($result && $result->num_rows > 0) {
      // Jika sudah absen, hapus data izin (jika ada)
      $deleted = "DELETE FROM izin WHERE user_id='$user_id' AND tanggal='$tanggal'";
      $connection->query($deleted);
      echo "Anda sudah melakukan absensi pada tanggal " . tanggal_ind($tanggal) . "!";
      return false;
  } else {
      // Tambahkan data absen dengan status Izin
      $add_absen = "INSERT INTO absen (user_id, tanggal, lokasi_id, status_masuk, status_pulang, kehadiran, keterangan)
      VALUES ('$user_id', '$tanggal', '$lokasi_id', '$alasan', '$alasan', '$alasan', '$keterangan')";
      return $connection->query($add_absen);
  }
}

switch (@$_GET['action']){
case 'data-izin':
$filterParts = [];
$tanggal = isset($_GET['tanggal']) ? date('Y-m-d', strtotime($_GET['tanggal'])) : $date;
$filterParts[] = "user.kelas='{$data_user['wali_kelas']}' AND izin.tanggal='$tanggal'";

if (!empty($_GET['siswa'])) {
    $siswa = htmlentities(convert("decrypt",$_GET['siswa']));
    $filterParts[] = "izin.user_id='$siswa'";
}

$filter = 'WHERE ' . implode(' AND ', $filterParts);

$query_izin ="SELECT izin.*,user.nama_lengkap,user.kelas FROM izin 
INNER JOIN user ON user.user_id=izin.user_id $filter ORDER BY izin.izin_id DESC LIMIT 10";
$result_izin = $connection->query($query_izin);
if($result_izin->num_rows > 0){
  while ($data_izin= $result_izin->fetch_assoc()){
  $izin_id = anti_injection($data_izin['izin_id']);
  
  if($data_izin['status'] == '-' OR $data_izin['status'] == 'PENDING'){
    $status='<span class="badge badge-primary badge-pill">Panding</span>';
  }elseif($data_izin['status'] == 'Y'){
    $status='<span class="badge badge-warning badge-pill">Disetujui</span>';
  }elseif($data_izin['status'] == 'N'){
    $status='<span class="badge badge-danger badge-pill">Ditotak</span>';
  }else{
    $status='';
  }

  if(file_exists('../../../sw-content/izin/'.strip_tags($data_izin['files']??'-').'')){
    $photo ='<a href="../sw-content/izin/'.strip_tags($data_izin['files']??'').'" class="popup-image">
    <img src="data:image/gif;base64,'.base64_encode(file_get_contents('../../../sw-content/izin/'.strip_tags($data_izin['files']??'-').'')).'" height="50"></a>';
  }else{
    $photo ='<img src="../../../sw-content/thumbnail.jpg" height="50">';
  }
    
echo'
<div class="card border-0 mb-2">
    <div class="card-body">
        <div class="row">
          <div class="col align-self-center">
              <p class="text-secondary"><span class="badge badge-warning badge-pill">'.$data_izin['alasan'].'</span>
              '.$status.'</p>
          </div> 
        </div>

        <div class="row align-items-center mt-1">
            <div class="col-auto align-self-center">
              <figure class="avatar avatar-50 rounded mb-0">
                '.$photo.'
              </figure>
            </div>

            <div class="col align-self-center">
              <span class="text-secondary">
               '.$data_izin['nama_lengkap'].' <span class="badge badge-primary">'.($data_izin['kelas']??'-').'</span>
              </span> 
              <p class="text-secondary small">
                '.tanggal_ind($data_izin['tanggal']).' s.d '.tanggal_ind($data_izin['tanggal_selesai']).'
              </p>
            </div>
        
            <div class="col-auto align-self-center">
              <div class="dropdown dropleft">
                  <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                  </a>
                  <div class="dropdown-menu dropdown-width-50 ml-3">
                    <button class="dropdown-item small btn-status" data-id="'.convert("encrypt",$data_izin['izin_id']).'" data-status="Y" type="button">Diterima</button>
                    <button class="dropdown-item small btn-status" data-id="'.convert("encrypt",$data_izin['izin_id']).'" data-status="N" type="button">Ditolak</button>
                  </div>
              </div> 
            </div>

            <div class="col-12">
              <hr class="mt-2 mb-1">
              <p class="text-secondary small ">'.strip_tags($data_izin['keterangan']??'-').'</p>
            </div> 
        </div>
    </div>
</div>';
}
  echo'
  <div class="text-center show_more_main'.$izin_id.' mt-4">
      <button data-id="'.$izin_id.'" class="btn btn-light rounded load-more">Show more</button>
  </div>
<script type="text/javascript">
  $(".popup-image").magnificPopup({type:"image"});
</script>';
}else{
  echo'<div class="alert alert-secondary mt-3">Saat ini, data pengajuan izin belum tersedia atau masih kosong!</div>';
}

/** Moad More Izin */
break;
case 'data-izin-load':
$filterParts = [];
$tanggal = isset($_POST['tanggal']) ? date('Y-m-d', strtotime($_POST['tanggal'])) : $date;
$filterParts[] = "user.kelas='{$data_user['wali_kelas']}' AND izin.tanggal='$tanggal'";

if (!empty($_POST['siswa'])) {
    $siswa = htmlentities(convert("decrypt",$_POST['siswa']));
    $filterParts[] = "izin.user_id='$siswa'";
}

$filter = 'WHERE ' . implode(' AND ', $filterParts);

$id = anti_injection($_POST['id']??'0');

$query_count    ="SELECT COUNT(izin.izin_id) AS total FROM izin 
INNER JOIN user ON user.user_id=izin.user_id $filter AND izin_id < $id  ORDER BY izin.izin_id DESC";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 10;
$query_izin =$query_izin ="SELECT izin.*,user.nama_lengkap,user.kelas FROM izin 
INNER JOIN user ON user.user_id=izin.user_id $filter 
AND izin.izin_id < $id  ORDER BY izin.izin_id DESC LIMIT $showLimit";
$result_izin = $connection->query($query_izin);
if($result_izin->num_rows > 0){
  while ($data_izin= $result_izin->fetch_assoc()){
    $izin_id = anti_injection($data_izin['izin_id']);

    if($data_izin['status'] == '-' OR $data_izin['status'] == 'PENDING'){
      $status='<span class="badge badge-primary badge-pill">Panding</span>';
    }elseif($data_izin['status'] == 'Y'){
      $status='<span class="badge badge-warning badge-pill">Disetujui</span>';
    }elseif($data_izin['status'] == 'N'){
      $status='<span class="badge badge-danger badge-pill">Ditotak</span>';
    }else{
      $status='';
    }

  if(file_exists('../../../sw-content/izin/'.strip_tags($data_izin['files']??'-').'')){
    $photo ='<a href="../sw-content/izin/'.strip_tags($data_izin['files']??'').'" class="popup-image">
    <img src="data:image/gif;base64,'.base64_encode(file_get_contents('../../../sw-content/izin/'.strip_tags($data_izin['files']??'-').'')).'" height="50"></a>';
  }else{
    $photo ='<img src="../../../sw-content/thumbnail.jpg" height="50">';
  }
    
echo'
<div class="card border-0 mb-2">
    <div class="card-body">
        <div class="row">
          <div class="col align-self-center">
              <p class="text-secondary"><span class="badge badge-warning badge-pill">'.$data_izin['alasan'].'</span>
              '.$status.'</p>
          </div> 
        </div>

        <div class="row align-items-center mt-1">
            <div class="col-auto align-self-center">
              <figure class="avatar avatar-50 rounded mb-0">
                '.$photo.'
              </figure>
            </div>

            <div class="col align-self-center">
              <span class="text-secondary">
               '.$data_izin['nama_lengkap'].' <span class="badge badge-primary">'.($data_izin['kelas']??'-').'</span>
              </span> 
              <p class="text-secondary small">
                '.tanggal_ind($data_izin['tanggal']).' s.d '.tanggal_ind($data_izin['tanggal_selesai']).'
              </p>
            </div>
        
            <div class="col-auto align-self-center">
              <div class="dropdown dropleft">
                  <a href="javascript:;" class="btn btn-sm btn-link text-dark" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-ellipsis-v"></i>
                  </a>
                  <div class="dropdown-menu dropdown-width-50 ml-3">
                    <button class="dropdown-item small btn-status" data-id="'.convert("encrypt",$data_izin['izin_id']).'" data-status="Y" type="button">Diterima</button>
                    <button class="dropdown-item small btn-status" data-id="'.convert("encrypt",$data_izin['izin_id']).'" data-status="N" type="button">Ditolak</button>
                  </div>
              </div> 
            </div>

            <div class="col-12">
              <hr class="mt-2 mb-1">
              <p class="text-secondary small ">'.strip_tags($data_izin['keterangan']??'-').'</p>
            </div> 
        </div>
    </div>
</div>';
}

if($totalRowCount > $showLimit){
  echo'
  <div class="text-center show_more_main'.$izin_id.' mt-4">
      <button data-id="'.$izin_id.'" class="btn btn-light rounded load-more">Show more</button>
  </div>';
}
echo'
<script type="text/javascript">
  $(".popup-image").magnificPopup({type:"image"});
</script>';

}else{
  echo'<div class="alert alert-secondary mt-3">Saat ini, data izin sudah tidak ada!</div>';
}


/** --------- Set Status ------ */
break;
case 'setujui':
if (isset($_POST['id'])) {
  $id     = anti_injection(convert("decrypt",$_POST['id']));
  $status = htmlentities($_POST['status']??'-');

  if (empty($id) || empty($status)) {
      exit('ID atau status tidak boleh kosong.');
  }

  // Ambil berdasarkan izin_id
  $qIzin = $connection->query("SELECT * FROM izin WHERE izin_id='$id'");
  if ($qIzin->num_rows === 0) {
      exit('Data tidak ditemukan!');
  }
  $dataIzin   = $qIzin->fetch_assoc();
  $user_id    = $dataIzin['user_id'];
  $tanggal    = $dataIzin['tanggal'];
  $tglSelesai = $dataIzin['tanggal_selesai'];
  $alasan     = $dataIzin['alasan'];
  $keterangan = $dataIzin['keterangan'];
  
  // Cek status sebelumnya
  if (!in_array($dataIzin['status'], ['N', 'PENDING'])) {
      exit('Sebelumnya data ini sudah disetujui!');
  }

  // Ambil data user
  $qUser = $connection->query("SELECT nama_lengkap, lokasi FROM user WHERE user_id='$user_id'");
  if ($qUser->num_rows === 0) {
      exit('Data pengguna tidak ditemukan!');
  }
  $dataUser = $qUser->fetch_assoc();

  // Update status 
  if (!$connection->query("UPDATE izin SET status='$status' WHERE izin_id='$id'")) {
      exit('Data tidak dapat disimpan! ' . $connection->error);
  }

  // Tambah notifikasi
    $notifSql = "INSERT INTO notifikasi (user_id, nama, keterangan, link, tanggal, datetime, tipe, tujuan,status)
    VALUES ('{$dataIzin['user_id']}','{$dataUser['nama_lengkap']}',
    'Permohonan Izin Anda disetujui oleh {$data_user['nama_lengkap']}', 'izin', '$date', '$timeNow', 'pegawai', 'siswa', 'N')";
    $connection->query($notifSql);

  // Tambah absen per hari
  while (strtotime($tanggal) <= strtotime($tglSelesai)) {
      if (!tambahAbsen($connection, $user_id, $tanggal, $dataUser['lokasi'], $alasan, $keterangan)) break;
      $tanggal = date('Y-m-d', strtotime($tanggal . ' +1 day'));
  }
  echo 'success';
}



/** Tolak */ 
break;
case 'tolak':
if (isset($_POST['id'])) {
  $id     = anti_injection(convert("decrypt",$_POST['id']));
  $status = htmlentities($_POST['status']??'-');

  if (empty($id) || empty($status)) {
      exit('ID atau status tidak boleh kosong.');
  }

  $qIzin = $connection->query("SELECT user_id, tanggal, tanggal_selesai, status FROM izin WHERE izin_id='$id'");
  if ($qIzin->num_rows === 0) {
      exit('Data tidak ditemukan!');
  }

  $dataIzin = $qIzin->fetch_assoc();
  // Cegah update jika status sudah final
  if ($dataIzin['status'] === 'N') {
      exit('Permohonan ini sudah ditolak sebelumnya.');
  }

  // Ambil data user
  $qUser = $connection->query("SELECT nama_lengkap,lokasi FROM user WHERE user_id='{$dataIzin['user_id']}'");
  if ($qUser->num_rows === 0) {
      exit('Data pengguna tidak ditemukan!');
  }
  $dataUser = $qUser->fetch_assoc();

  // Update status cuti
  $update = $connection->query("UPDATE izin SET status='$status' WHERE izin_id='$id'");
  if (!$update) {
      exit('Gagal mengupdate status  izin: ' . $connection->error);
  }

  $notifSql = "INSERT INTO notifikasi (user_id, nama, keterangan, link, tanggal, datetime, tipe, tujuan,status)
  VALUES ('{$dataIzin['user_id']}','{$dataUser['nama_lengkap']}',
  'Permohonan Izin Anda ditolak oleh {$data_user['nama_lengkap']}', 'izin', '$date', '$timeNow', 'pegawai', 'siswa', 'N')";
  $connection->query($notifSql);

  $start  = new DateTime($dataIzin['tanggal']);
  $finish = new DateTime($dataIzin['tanggal_selesai']);
  $interval = new DateInterval('P1D');
  $daterange = new DatePeriod($start, $interval, $finish->modify('+1 day')); // Termasuk tanggal selesai

  foreach ($daterange as $dateObj) {
      $tanggal = $dateObj->format('Y-m-d');
      $connection->query("DELETE FROM absen WHERE tanggal='$tanggal' AND user_id='{$dataIzin['user_id']}'");
  }
  echo 'success';
}



break;
  }
}