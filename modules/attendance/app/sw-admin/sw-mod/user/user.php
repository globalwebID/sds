<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}
else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='1' AND level_id='$current_user[level]'";
$result_role = $connection->query($query_role);
if($result_role->num_rows > 0){
$data_role = $result_role->fetch_assoc();

switch(@$_GET['op']){ 
default:
echo'
<span class="modifikasi d-none">'.convert('encrypt', $data_role['modifikasi']).'</span>
<span class="hapus d-none">'.convert('encrypt',$data_role['hapus']).'</span>
<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Siswa</li>
            </ol>
          </nav>
        </div>
        
      </div>
    </div>
  </div>
</div>
<!-- Page content -->
<div class="container-fluid mt--6">
  <!-- Table -->
  <div class="row">
    <div class="col">
      <div class="card pb-3">
        <!-- Card header -->
        <div class="card-header mb-2">
          <h3 class="mt-2 mb-0 text-left float-left">Siswa</h3>
          <div class="float-right">';
            if($data_role['modifikasi']=='Y'){
            echo'
            <a href="./alumni" class="btn btn-secondary btn-qrcode">Alumni</a>
            <button class="btn btn-secondary btn-qrcode">Cetak Id Card</button>
            <button class="btn btn-secondary btn-import"><i class="fas fa-file-upload"></i> Impor</button>
            <a href="'.$mod.'&op=add"  class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</a>';
            }else{
            echo'
            <button class="btn btn-primary" disabled><i class="fas fa-plus"></i> Tambah</button>';
            }
          echo'
          </div>
        </div>';
        if($data_role['lihat']=='Y'){
        echo'
        <form class="form-table" action="javascript:void(0);" autocomplete="of">
        <div class="table-responsive">
          <table class="table align-items-center table-flush table-striped datatable-user">
            <thead class="bg-primary text-white">
              <tr>
                <th width="2" class="text-center">
                  #
                </th>
                <th width="4">No</th>
                <th class="text-center" width="5">QRCODE</th>
                <th>Nama</th>
                <th>NISN</th>
                <th>Jenis Kelamin</th>
                <th>Tgl Lahir</th>
                <th>Kelas</th>
                <th>Tanggal Login</th>
                <th>Status</th> 
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot class="bg-primary text-white">
                <tr>
                    <th colspan="11">
                      <div class="row">
                        <div class="col-md-1">
                          <label class="mt-3">Naik Kelas</label>
                        </div>
                        <div class="col-md-2">
                          <select class="form-control tahun_ajaran" name="tahun_ajaran" required>';
                            $query_tahun ="SELECT * FROM tahun_ajaran WHERE is_active=1 AND status='active' ORDER BY tahun_ajaran_id DESC";
                            $result_tahun = $connection->query($query_tahun);
                            while ($data_tahun = $result_tahun->fetch_assoc()) {
                              echo'<option value="'.$data_tahun['tahun_ajaran'].'">'.$data_tahun['tahun_ajaran'].'</option>';
                            }
                            echo'
                          </select>
                        </div>
                        
                        <div class="col-md-2">
                          <select class="form-control kelas" name="kelas" required>';
                            $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                            $result_kelas = $connection->query($query_kelas);
                            while ($data_kelas = $result_kelas->fetch_assoc()) {
                              echo'<option value="'.$data_kelas['nama_kelas'].'">'.$data_kelas['nama_kelas'].'</option>';
                            }
                            echo'
                          </select>
                        </div>

                        <div class="col-md-1">
                          <select class="form-control status" name="status" required>
                            <option value="Y">Aktif</option>
                            <option value="N">Alumni</option>
                          </select>
                        </div>

                        <div class="col-md-2">
                          <button class="btn btn-warning btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
                        </div>
                    </th>
                </tr>
            </tfoot>

          </table>
        </div>
        </form>';
      }else{
        hak_akses();
      }
      echo'
      </div>
    </div>
  </div>

<div class="modal fade modal-import data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
<div class="modal-dialog modal-md">
<div class="modal-content">
  <div class="modal-header">
    <h5 class="modal-title">Impor Data Siswa</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
  <form class="form-import" action="javascript:void(0);" autocomplete="of">
    <div class="modal-body">
      <div class="form-group">
        <label>Upload file</label>
        <a href="../sw-content/tema/Format-siswa.xlsx" class="float-right" target="_blank"><i class="fas fa-download"></i> Format</a>
        <input type="file" class="form-control" name="file_excel" accept=".xls,.xlsx" placeholder="Impor data" required>
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary btn-save"><i class="fas fa-save"></i> Simpan</button>
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    </div>
  </form>
  </div>
</div>
</div>
  
  <!-- Modal Cetak Qr Code -->
  <div class="modal fade modal-qrcode data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
  <div class="modal-dialog modal-md">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Cetak ID Card</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      
        <div class="modal-body">
        

          <div class="form-group">
            <label class="form-control-label">Kelas</label>
              <select class="form-control result-kelas" required>';
              $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
              $result_kelas = $connection->query($query_kelas);
              while ($data_kelas = $result_kelas->fetch_assoc()) {
                echo'<option value="'.$data_kelas['nama_kelas'].'">'.$data_kelas['nama_kelas'].'</option>';
              }
              echo'
              </select>
          </div>
          
          
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary btn-export"><i class="fas fa-print"></i> Print</span></button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
        </div>

      </div>
    </div>
  </div>';


/* -------------- Add -------------- */
break;
case 'add':
echo'
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item"><a href="./'.$mod.'">Siswa</a></li>
              <li class="breadcrumb-item active" aria-current="page">Tambah</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--6">
<div class="card-wrapper">
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h3 class="mb-0">Tambah Data Siswa</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
      if($data_role['modifikasi']=='Y'){
        echo'
        <form class="form-add" role="form" method="post" action="#" autocomplete="off">
          <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                  <label class="form-control-label">NISN</label>
                  <input type="number" class="form-control" min="0" name="nisn" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">ID RFID</label>
                  <input type="text" class="form-control" name="rfid">
                  <small>Biarkan kosong jika belum memiliki ID RFID</small>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Nama Lengkap</label>
                  <input type="text" class="form-control" name="nama_lengkap" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Email</label>
                  <input type="email" class="form-control" name="email" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Password</label>
                    <input type="text" class="form-control password" name="password" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Tempat Lahir</label>
                  <input type="text" class="form-control" name="tempat_lahir" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Tanggal Lahir</label>
                  <input type="text" class="form-control datepicker" name="tanggal_lahir" placeholder="'.tanggal_ind($date).'" required>
                </div>

            </div>

            <!-- Right -->
            <div class="col-md-6">

                <div class="form-group">
                  <label class="form-control-label">Jenis Kelamin</label>
                  <select class="form-control" name="jenis_kelamin" required>
                      <option value="Laki-laki">Laki-laki</option>
                      <option value="Perempuan">Perempuan</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label class="form-control-label">Kelas</label>
                  <select class="form-control" name="kelas" required>';
                  $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                  $result_kelas = $connection->query($query_kelas);
                  while ($data_kelas = $result_kelas->fetch_assoc()) {
                    echo'<option value="'.$data_kelas['nama_kelas'].'">'.$data_kelas['nama_kelas'].'</option>';
                  }
                  echo'
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Tahun Ajaran</label>
                  <select class="form-control" name="tahun_ajaran" required>';
                  $query_tahun ="SELECT * FROM tahun_ajaran WHERE is_active=1 AND status='active' ORDER BY tahun_ajaran_id DESC";
                  $result_tahun = $connection->query($query_tahun);
                  while ($data_tahun = $result_tahun->fetch_assoc()) {
                    echo'<option value="'.$data_tahun['tahun_ajaran'].'">'.$data_tahun['tahun_ajaran'].'</option>';
                  }
                  echo'
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Lokasi/Tempat Sekolah</label>
                  <select class="form-control" name="lokasi" required>';
                  $query_lokasi ="SELECT lokasi_id,lokasi_nama FROM lokasi ORDER BY lokasi_nama ASC";
                  $result_lokasi = $connection->query($query_lokasi);
                  while ($data_lokasi = $result_lokasi->fetch_assoc()) {
                    echo'<option value="'.$data_lokasi['lokasi_id'].'">'.$data_lokasi['lokasi_nama'].'</option>';
                  }
                  echo'
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-control-label">No. WhatsApp</label>
                  <input type="number" class="form-control" min="0" name="telp" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Alamat Rumah</label>
                  <textarea class="form-control" name="alamat" rows="3" required></textarea>
                </div>
            </div>
          </div>

      <hr>
      <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
      <a href="./'.$mod.'" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
          
    </form>';
    }else{
      hak_akses();
    }
    echo'
    </div>
  </div>
</div>';


/** Update Siswa */
break;
case 'update':
if(!empty($_GET['id'])){
$id     =  anti_injection(convert('decrypt',$_GET['id'])); 
$query_user  ="SELECT * from user WHERE user_id='$id' LIMIT 1";
$result_user = $connection->query($query_user);
echo'
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item"><a href="./'.$mod.'">Siswa</a></li>
              <li class="breadcrumb-item active" aria-current="page">Ubah</li>
            </ol>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--6">
  <div class="card-wrapper">
    <div class="card">
      <div class="card-header">
        <h3 class="mb-0">Ubah Data Siswa</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
      if($result_user->num_rows > 0){
        $data_user  = $result_user->fetch_assoc();
      if($data_role['modifikasi']=='Y'){
      echo'
      <form class="form-update" role="form" method="post" action="#" autocomplete="off" enctype="multipart/form-data">
        <input type="hidden" class="d-none" name="id" value="'.epm_encode($data_user['user_id']).'" required readonly>
          <div class="row">
            <div class="col-md-6">

              <div class="form-group">
                <label class="form-control-label">NISN</label>
                <input type="number" class="form-control" name="nisn" value="'.strip_tags($data_user['nisn']??'-').'" required>
              </div>

              <div class="form-group">
                <label class="form-control-label">RFID</label>
                <input type="text" class="form-control" name="rfid" value="'.strip_tags($data_user['rfid']??'').'">
                <small>Kosongankan saja jika belum memiliki kode RFID</small>
              </div>

              <div class="form-group">
                <label class="form-control-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" value="'.strip_tags($data_user['nama_lengkap']??'-').'" required>
              </div>

              <div class="form-group">
                  <label class="form-control-label">Email</label>
                  <input type="email" class="form-control" name="email" value="'.strip_tags($data_user['email']??'-').'" required>
              </div>

              <div class="form-group">
                <label class="form-control-label">Tempat Lahir</label>
                <input type="text" class="form-control" name="tempat_lahir" value="'.strip_tags($data_user['tempat_lahir']??'-').'" required>
              </div>

              <div class="form-group">
                <label class="form-control-label">Tanggal Lahir</label>
                <input type="text" class="form-control datepicker" name="tanggal_lahir" value="' . (!empty($data_user['tanggal_lahir']) ? tanggal_ind($data_user['tanggal_lahir']) : '-') . '" required>
              </div>

          </div>

          <!-- Right -->
          <div class="col-md-6">

            <div class="form-group">
              <label class="form-control-label">Jenis Kelamin</label>
                <select class="form-control" name="jenis_kelamin" required>';
                  if($data_user['jenis_kelamin'] =='Laki-laki'){
                    echo'<option value="Laki-laki" selected>Laki-laki</option>';
                  }else{
                      echo'<option value="Laki-laki">Laki-laki</option>';
                  }
                  if($data_user['jenis_kelamin'] =='Perempuan'){
                      echo'<option value="Perempuan" selected>Perempuan</option>';
                  }else{
                      echo'<option value="Perempuan">Perempuan</option>';
                  }
                  echo'
                </select>
            </div>
            
            <div class="form-group">
              <label class="form-control-label">Kelas</label>
              <select class="form-control" name="kelas" required>';
              $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
              $result_kelas = $connection->query($query_kelas);
              while ($data_kelas = $result_kelas->fetch_assoc()) {
                $selected = ($data_kelas['nama_kelas'] == $data_user['nama_kelas']) ? 'selected' : '';
                echo'<option value="'.$data_kelas['nama_kelas'].'" '.$selected.'>'.$data_kelas['nama_kelas'].'</option>';
              }
              echo'
              </select>
            </div>

              <div class="form-group">
                <label class="form-control-label">Tahun Ajaran</label>
                <select class="form-control" name="tahun_ajaran" required>';
                $tahun_user_lama = $connection->real_escape_string((string)($data_user['tahun_ajaran'] ?? ''));
                $query_tahun ="SELECT * FROM tahun_ajaran WHERE (is_active=1 AND status='active') OR tahun_ajaran='$tahun_user_lama' ORDER BY is_active DESC, tahun_ajaran DESC";
                $result_tahun = $connection->query($query_tahun);
                while ($data_tahun = $result_tahun->fetch_assoc()) {
                  $selected = ($data_tahun['tahun_ajaran'] == $data_user['tahun_ajaran']) ? 'selected' : '';
                  echo'<option value="'.$data_tahun['tahun_ajaran'].'" '.$selected.'>'.$data_tahun['tahun_ajaran'].'</option>';
                }
                echo'
                </select>
              </div>

              <div class="form-group">
                <label class="form-control-label">Lokasi/Tempat Sekolah</label>
                <select class="form-control" name="lokasi" required>';
                $query_lokasi ="SELECT lokasi_id,lokasi_nama FROM lokasi ORDER BY lokasi_nama ASC";
                $result_lokasi = $connection->query($query_lokasi);
                while ($data_lokasi = $result_lokasi->fetch_assoc()) {
                  if($data_lokasi['lokasi_id'] == $data_user['lokasi']) {
                    echo'<option value="'.$data_lokasi['lokasi_id'].'" selected>'.$data_lokasi['lokasi_nama'].'</option>';
                  }else{
                    echo'<option value="'.$data_lokasi['lokasi_id'].'">'.$data_lokasi['lokasi_nama'].'</option>';
                  }
                }
                echo'
                </select>
              </div>

              <div class="form-group">
                <label class="form-control-label">No. Telp/WhatsApp</label>
                <input type="number" class="form-control" name="telp" value="'.strip_tags($data_user['telp']??'0').'" required>
              </div>

              <div class="form-group">
                <label class="form-control-label">Alamat Lengkap</label>
                <textarea class="form-control" name="alamat" rows="3" required>'.strip_tags($data_user['alamat']??'-').'</textarea>
              </div>

              

            </div>
          </div>
            <hr>
            <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
            <a href="./'.$mod.'" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
      </form>';
        }else{
          hak_akses();
        }

        }else{
          theme_404();
        }
    echo'
    </div>
  </div>
</div>';
}

/** Siswa/User*/
break;
case 'profile':
if(!empty($_GET['id'])){
    $id     =  anti_injection(epm_decode($_GET['id'])); 
    $query_user ="SELECT user.*, kelas.nama_kelas,lokasi.lokasi_nama FROM user 
    LEFT JOIN absensi_kelas
    ON user.kelas = kelas.kelas_id
    LEFT JOIN lokasi ON user.lokasi=lokasi.lokasi_id WHERE user.user_id='$id'";
    $result_user = $connection->query($query_user);

  echo'
  <div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
            <h6 class="h2 text-white d-inline-block mb-0">Siswa</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="./'.$mod.'">Siswa</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Profil</li>
                </ol>
              </nav>
            </div>
            
          </div>
        </div>
      </div>
    </div>';

    if($result_user->num_rows > 0){
      $data_user  = $result_user->fetch_assoc();
      
      if($data_user['avatar'] == NULL OR $data_user['avatar']=='avatar.jpg'){
        $avatar ='<img src="../sw-content/avatar/avatar.jpg" class="rounded-circle w-150" height="140">';
      }else{
        $avatar ='
        <a class="open-popup-link" href="../sw-content/avatar/'.strip_tags($data_user['avatar']).'">
            <img src="../sw-content/avatar/'.strip_tags($data_user['avatar']).'" class="rounded-circle w-150" height="140">
        </a>';
      }

      echo'
    <!-- Page content -->
    <div class="container-fluid mt--6">
      <div class="row">
        <div class="col-xl-4 order-xl-2">
          <div class="card card-profile">
            <img src="./sw-assets/img/theme/img-1-1000x600.jpg" alt="Image placeholder" class="card-img-top">
            <div class="row justify-content-center">
              <div class="col-lg-3 order-lg-2">
                <div class="card-profile-image">
                  '.$avatar.'
                </div>
              </div>
            </div>
            <div class="card-header text-center border-0 pt-8 pt-md-4 pb-0 pb-md-4">
              <div class="d-flex justify-content-between">
                
              </div>
            </div>
            <div class="card-body pt-0">
              <div class="text-center">
                <h5 class="h3 mt-5">
                '.strip_tags($data_user['nama_lengkap']??'-').'
                </h5>
                <div class="h5 font-weight-300">
                  <i class="ni location_pin mr-2"></i>'.strip_tags($data_user['nisn']).'
                </div>

                <div class="h5 font-weight-300">';
                  if($data_user['status']=='Offline'){
                    echo'<span class="badge badge-danger">Offline</span>';
                  }else{
                    echo'<span class="badge badge-info">Online</span>';
                  }
                echo'
                </div>
              </div>
                
              <div class="mt-3">
              <ul class="list-group list-group-flush">
                <li class="list-group-item">User Sejak: '.tgl_indo($data_user['tanggal_registrasi']??'-').'</li>
                <li class="list-group-item">Terakhir Login: '.tgl_indo($data_user['tanggal_login']??'-').'</li>
                <li class="list-group-item">Ip: '.($data_user['ip']??'-').'</li>
                <li class="list-group-item">Browser: '.($data_user['browser']??'-').'</li>
              </ul>
            </div>

            </div>
          </div>
    
        </div>

        <div class="col-xl-8 order-xl-1">
          
          <div class="card">
            <div class="card-header">
              <div class="row align-items-center">
                <div class="col-8">
                  <h3 class="mb-0">Profil</h3>
                </div>
                <div class="col-4 text-right">
                  <a href="user&op=update&id='.epm_encode($data_user['user_id']).'" class="btn btn-sm btn-primary">Settings</a>
                </div>
              </div>
            </div>
            <div class="card-body">
              <form>
                <h6 class="heading-small text-muted mb-4">Informasi Profil</h6>
                <div class="pl-lg-4">
                  <div class="row">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Nama Lengkap</label>
                        <p>'.strip_tags($data_user['nama_lengkap']??'-').'</p>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Tempat Lahir</label>
                        <p>'.strip_tags($data_user['tempat_lahir']??'-').'</p>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Tanggal  Lahir</label>
                        <p>'.tanggal_ind($data_user['tanggal_lahir']??'-').'</p>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Jenis Kelamin</label>
                        <p>'.strip_tags($data_user['jenis_kelamin']??'-').'</p>
                      </div>
                    </div>

                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Kelas</label>
                        <p>'.strip_tags($data_user['nama_kelas']??'-').'</p>
                      </div>
                    </div>

                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Lokasi Sekolah</label>
                        <p>'.strip_tags($data_user['lokasi_nama']??'-').'</p>
                      </div>
                    </div>

                  </div>
                </div>
                <hr class="my-4">
                <!-- Address -->
                <!-- Description -->
                <h6 class="heading-small text-muted mb-4">KONTAK</h6>
                <div class="pl-lg-4">
                  <div class="row">
                      <div class="col-lg-6">
                        <div class="form-group">
                          <label class="form-control-label">Telp</label>
                          <p>'.strip_tags($data_user['telp']??'-').'</p>
                        </div>
                      </div>

                      <div class="col-lg-6">
                        <div class="form-group">
                          <label class="form-control-label">Email</label>
                          <p>'.strip_tags($data_user['email']??'-').'</p>
                        </div>
                      </div>


                      <div class="col-lg-12">
                        <div class="form-group">
                          <label class="form-control-label">Alamat</label>
                          <p>'.strip_tags($data_user['alamat']??'-').'</p>
                        </div>
                      </div>
                  </div>
                </div>
              </form>
            </div>
      
      </div>
    </div>
  </div>';}else{
    echo' <div class="container-fluid mt--6">
    <!-- Table -->
    <div class="row">
      <div class="col">
        <div class="card pb-6 pt-6">';
          theme_404();
        echo'</div>
          </div>
    </div>';
  }
  }
  break;
  }

  }else{
    /** Modul tidak ditemukan */
  }

}?>

  