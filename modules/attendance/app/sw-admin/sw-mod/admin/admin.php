<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{

$isCentralAbsensiManager = !empty($current_user['central_admin'])
  && in_array(($current_user['app_role'] ?? ''), ['superadmin', 'admin'], true);
if (!$isCentralAbsensiManager) {
  http_response_code(403);
  echo '<div class="container-fluid mt-4"><div class="alert alert-danger">Akun staf tidak memiliki akses untuk mengelola akun lain.</div></div>';
  return;
}

$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='13' AND level_id='$current_user[level]'";
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
                  <li class="breadcrumb-item active" aria-current="page">Admin</li>
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
              <h3 class="mt-2 mb-0 text-left float-left">Data Admin</h3>
              <div class="float-right">';
              if($data_role['modifikasi']=='Y'){
                echo'
                <a href="./hak-akses" class="btn btn-info"><i class="fas fa-user-lock"></i> Hak Akses</a>';
              }else{
                echo'<button class="btn btn-primary" disabled><i class="fas fa-plus"></i> Tambah</button>';
              }
              echo'
                <a href="'.$mod.'&op=add"  class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</a>
              </div>
            </div>';

            if($data_role['lihat']=='Y'){
              echo'
            <div class="table-responsive" style="overflow:auto">
              <table class="table align-items-center table-flush table-striped datatable-user">
                <thead class="thead-light">
                  <tr>
                      <th width="8">No</th>
                      <th class="text-center">Avatar</th>
                      <th>Nama</th>
                      <th>Email</th>
                      <th>Telp</th>
                      <th>Level</th>
                      <th>Tanggal Daftar</th>
                      <th>Tanggal Login</th>
                      <th>Aktif</th>
                      <th class="text-center">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>';
            }else{
              hak_akses();
            }
          echo'
          </div>
        </div>
      </div>';


/* -------------- Add -------------- */
break;
case 'add':
    echo'
    <!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item"><a href="./'.$mod.'">Admin</a></li>
              <li class="breadcrumb-item active" aria-current="page">Tambah</li>
            </ol>
          </nav>
        </div>
        
      </div>
    </div>
  </div>
</div>

    <!-- Page content -->
<div class="container-fluid mt--6 mb-5">
    <div class="card-wrapper">
    <!-- Form controls -->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h3 class="mb-0">Tambah Data Admin</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
      if($data_role['modifikasi']=='Y'){
        echo'
        <form class="form-add" role="form" method="post" action="#" autocomplete="off">
        <div class="form-group row">
            <label  class="col-sm-2 col-form-label">Nama lengkap</label>
            <div class="col-sm-6">
                <input type="text" class="form-control" name="fullname" required>
            </div>
        </div>

        <div class="form-group row">
            <label  class="col-sm-2 col-form-label">No. Telp</label>
            <div class="col-sm-6">
                <input type="number" class="form-control" name="phone" required>
            </div>
        </div>

        <div class="form-group row">
            <label  class="col-sm-2 col-form-label">Email</label>
            <div class="col-sm-6">
                <input type="email" class="form-control email" name="email" required>
                <div class="email-response"></div>
            </div>
        </div>


        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Username</label>
            <div class="col-sm-6">
                <input type="text" class="form-control password" name="username" required>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Password</label>
            <div class="col-sm-6">
              <div class="input-group input-group-merge">
                    <input type="password" class="form-control password" id="password-field"  name="password" required>
                <div class="input-group-append">
                  <span class="input-group-text"><span toggle="#password-field" class="fas fa-eye toggle-password"></span></span>
                </div>
              </div>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Level</label>
            <div class="col-sm-6">
              <select class="form-control level" name="level" required>
                  <option value="">Pilih level:</option>';
                  $query_level ="SELECT level_id, 'Staf Absensi' AS level_nama FROM level WHERE level_id=2";
                  $result_level = $connection->query($query_level);
                  while ($data_level = $result_level->fetch_assoc()){
                      echo'<option value="'.$data_level['level_id'].'">'.$data_level['level_nama'].'</option>';
                  }
              echo'
              </select>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-sm-2 col-form-label">Aktif</label>
            <div class="col-sm-6">
              <label class="custom-toggle mt-2">
                <input type="checkbox" name="active" value="Y" checked>
                 <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
              </label>
            </div>
        </div>
      <hr>

        <div class="form-group row mb-0">
            <label class="col-sm-2 col-form-label"></label>
            <div class="col-sm-6">
              <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
              <a href="./'.$mod.'" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
            </div>
        </div>
      </form>';
        }else{
          hak_akses();
        }
    echo'
    </div>
  </div>
</div>';


/** Update Admin/User*/
break;
case 'update':
if(!empty($_GET['id'])){
  
  $id     =  anti_injection(epm_decode($_GET['id']));  
  $query_user ="SELECT * FROM admin WHERE admin.admin_id='$id'";
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
              <li class="breadcrumb-item"><a href="./'.$mod.'">Admin</a></li>
              <li class="breadcrumb-item active" aria-current="page">Ubah</li>
            </ol>
          </nav>
        </div>
        
        </div>
      </div>
    </div>
  </div>

  <!-- Page content -->
  <div class="container-fluid mt--6 mb-5">
  <div class="card-wrapper">
    <!-- Form controls -->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h3 class="mb-0">Ubah Data Admin</h3>
      </div>
      
      <!-- Card body -->
      <div class="card-body">';
        if($result_user->num_rows > 0){
          $data_user  = $result_user->fetch_assoc();
          if($data_role['modifikasi']=='Y'){

            if($data_user['level'] == 2 || $data_user['level']== 3){
              $cabang_show = '';
            }else{
              $cabang_show = 'display:none';
            }

          echo'
          <form class="form-update" role="form" method="post" action="#" autocomplete="off">
          <input type="hidden" class="d-none" name="id" value="'.epm_encode($data_user['admin_id']).'" required readonly>

          <div class="form-group row">
          <label  class="col-sm-2 col-form-label">Nama lengkap</label>
              <div class="col-sm-6">
                  <input type="text" class="form-control" name="fullname" value="'.strip_tags($data_user['fullname']).'" required>
              </div>
          </div>

          <div class="form-group row">
              <label  class="col-sm-2 col-form-label">No. Telp</label>
              <div class="col-sm-6">
                  <input type="number" class="form-control" name="phone" value="'.strip_tags($data_user['phone']).'" required>
              </div>
          </div>

          <div class="form-group row">
              <label  class="col-sm-2 col-form-label">Email</label>
              <div class="col-sm-6">
                  <input type="email" class="form-control" name="email" value="'.strip_tags($data_user['email']).'" required>
              </div>
          </div>


          <div class="form-group row">
              <label class="col-sm-2 col-form-label">Username</label>
              <div class="col-sm-6">
                  <input type="text" class="form-control password" name="username" value="'.strip_tags($data_user['username']).'" required>
              </div>
          </div>

          <div class="form-group row">
            <label class="col-sm-2 col-form-label">Level</label>
            <div class="col-sm-6">
              <select class="form-control level" name="level" required>
                  <option value="">Pilih level:</option>';
                  $query_level ="SELECT level_id, 'Staf Absensi' AS level_nama FROM level WHERE level_id=2";
                  $result_level = $connection->query($query_level);
                  while ($data_level = $result_level->fetch_assoc()){
                      if($data_level['level_id']== $data_user['level']){
                        echo'<option value="'.$data_level['level_id'].'" selected>'.$data_level['level_nama'].'</option>';
                      }else{
                        echo'<option value="'.$data_level['level_id'].'">'.$data_level['level_nama'].'</option>';
                      }
                  }
              echo'
              </select>
            </div>
          </div>


          <div class="form-group row">
              <label class="col-sm-2 col-form-label">Aktif</label>
              <div class="col-sm-6">
                <label class="custom-toggle mt-2">';
                  if($data_user['active']=='Y'){
                    echo'<input type="checkbox" name="active" value="Y" checked>';
                  }else{
                    echo'<input type="checkbox" name="active" value="Y">';
                  }
                  echo'
                  <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                </label>
              </div>
          </div>
        <hr>

        <div class="form-group row mb-0">
            <label class="col-sm-2 col-form-label"></label>
            <div class="col-sm-6">
              <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
              <a href="./'.$mod.'" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
            </div>
        </div>
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

  /** Profile Admin/User*/
  break;
  case 'profile':
  if(!empty($_GET['id'])){
    $id     =  anti_injection(epm_decode($_GET['id'])); 
    $query_user ="SELECT * FROM admin WHERE admin.admin_id='$id'";
    $result_user = $connection->query($query_user);

  echo'
  <div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
            <h6 class="h2 text-white d-inline-block mb-0">Admin</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="./'.$mod.'">Admin</a></li>
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
                  <a href="#">
                    <img src="../sw-content/avatar/avatar.jpg" class="rounded-circle w-150">
                  </a>
                </div>
              </div>
            </div>
            <div class="card-header text-center border-0 pt-8 pt-md-4 pb-0 pb-md-4">
              <div class="d-flex justify-content-between">
                
              </div>
            </div>
            <div class="card-body pt-0">
              <div class="row">
                <div class="col">
                  <div class="card-profile-stats d-flex justify-content-center">
                    <div>
                      <span class="heading">22</span>
                      <span class="description">Izin</span>
                    </div>
                    <div>
                      <span class="heading">10</span>
                      <span class="description">Sakit</span>
                    </div>
                    <div>
                      <span class="heading">89</span>
                      <span class="description">Keluar Kota</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="text-center">
                <h5 class="h3">
                '.strip_tags($data_user['nama_lengkap']).'
                </h5>
                <div class="h5 font-weight-300">
                  <i class="ni location_pin mr-2"></i>'.strip_tags($data_user['nip']).'
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
                    <li class="list-group-item">User Sejak: '.tgl_indo($data_user['tanggal_registrasi']).'</li>
                    <li class="list-group-item">Terakhir Login: '.tgl_indo($data_user['tanggal_login']).'</li>
                    <li class="list-group-item">Ip: </li>
                    <li class="list-group-item">Browser: </li>
                  </ul>
                </div>

            </div>
          </div>
          <!-- Progress track -->
          <div class="card">
            <!-- Card header -->
            <div class="card-header">
              <!-- Title -->
              <h5 class="h3 mb-0">Dalam 1 minggu terakhir</h5>
            </div>
            <!-- Card body -->
            <div class="card-body">
              <!-- List group -->
              <ul class="list-group list-group-flush list my--3">
                <li class="list-group-item px-0">
                  <div class="row align-items-center">
                    <div class="col-auto">
                      <!-- Avatar -->
                      <a href="#" class="avatar rounded-circle">
                        <img alt="Image placeholder" src="../../assets/img/theme/bootstrap.jpg">
                      </a>
                    </div>
                    <div class="col">
                      <h5>Argon Design System</h5>
                      <div class="progress progress-xs mb-0">
                        <div class="progress-bar bg-orange" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: 60%;"></div>
                      </div>
                    </div>
                  </div>
                </li>
                <li class="list-group-item px-0">
                  <div class="row align-items-center">
                    <div class="col-auto">
                      <!-- Avatar -->
                      <a href="#" class="avatar rounded-circle">
                        <img alt="Image placeholder" src="../../assets/img/theme/angular.jpg">
                      </a>
                    </div>
                    <div class="col">
                      <h5>Angular Now UI Kit PRO</h5>
                      <div class="progress progress-xs mb-0">
                        <div class="progress-bar bg-green" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                      </div>
                    </div>
                  </div>
                </li>
                <li class="list-group-item px-0">
                  <div class="row align-items-center">
                    <div class="col-auto">
                      <!-- Avatar -->
                      <a href="#" class="avatar rounded-circle">
                        <img alt="Image placeholder" src="../../assets/img/theme/sketch.jpg">
                      </a>
                    </div>
                    <div class="col">
                      <h5>Black Dashboard</h5>
                      <div class="progress progress-xs mb-0">
                        <div class="progress-bar bg-red" role="progressbar" aria-valuenow="72" aria-valuemin="0" aria-valuemax="100" style="width: 72%;"></div>
                      </div>
                    </div>
                  </div>
                </li>
                <li class="list-group-item px-0">
                  <div class="row align-items-center">
                    <div class="col-auto">
                      <!-- Avatar -->
                      <a href="#" class="avatar rounded-circle">
                        <img alt="Image placeholder" src="../../assets/img/theme/react.jpg">
                      </a>
                    </div>
                    <div class="col">
                      <h5>React Material Dashboard</h5>
                      <div class="progress progress-xs mb-0">
                        <div class="progress-bar bg-teal" role="progressbar" aria-valuenow="90" aria-valuemin="0" aria-valuemax="100" style="width: 90%;"></div>
                      </div>
                    </div>
                  </div>
                </li>
                <li class="list-group-item px-0">
                  <div class="row align-items-center">
                    <div class="col-auto">
                      <!-- Avatar -->
                      <a href="#" class="avatar rounded-circle">
                        <img alt="Image placeholder" src="../../assets/img/theme/vue.jpg">
                      </a>
                    </div>
                    <div class="col">
                      <h5>Vue Paper UI Kit PRO</h5>
                      <div class="progress progress-xs mb-0">
                        <div class="progress-bar bg-green" role="progressbar" aria-valuenow="100" aria-valuemin="0" aria-valuemax="100" style="width: 100%;"></div>
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>
        <div class="col-xl-8 order-xl-1">
          <div class="row">
            <div class="col-lg-6">
              <div class="card bg-gradient-info border-0">
                <!-- Card body -->
                <div class="card-body">
                  <div class="row">
                    <div class="col">
                      <h5 class="card-title text-uppercase text-muted mb-0 text-white">Absen Masuk</h5>
                      <span class="h2 font-weight-bold mb-0 text-white">350,897</span>
                    </div>
                    <div class="col-auto">
                      <div class="icon icon-shape bg-white text-dark rounded-circle shadow">
                        <i class="ni ni-active-40"></i>
                      </div>
                    </div>
                  </div>
                  <p class="mt-3 mb-0 text-sm">
                    <span class="text-nowrap text-light">Selama 1 bulan</span>
                  </p>
                </div>
              </div>
            </div>
            <div class="col-lg-6">
              <div class="card bg-gradient-danger border-0">
                <!-- Card body -->
                <div class="card-body">
                  <div class="row">
                    <div class="col">
                      <h5 class="card-title text-uppercase text-muted mb-0 text-white">Absen Keluar</h5>
                      <span class="h2 font-weight-bold mb-0 text-white">49,65%</span>
                    </div>
                    <div class="col-auto">
                      <div class="icon icon-shape bg-white text-dark rounded-circle shadow">
                        <i class="ni ni-spaceship"></i>
                      </div>
                    </div>
                  </div>
                  <p class="mt-3 mb-0 text-sm">
                    <span class="text-nowrap text-light">Selama 1 bulan</span>
                  </p>
                </div>
              </div>
            </div>
          </div>
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
                        <p>'.strip_tags($data_user['nama_lengkap']).'</p>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Tempat Lahir</label>
                        <p>'.strip_tags($data_user['tempat_lahir']).'</p>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Tanggal  Lahir</label>
                        <p>'.tanggal_ind($data_user['tanggal_lahir']).'</p>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Jenis Kelamin</label>
                        <p>'.strip_tags($data_user['jenis_kelamin']).'</p>
                      </div>
                    </div>
                  </div>
                </div>
                <hr class="my-4" />
                <!-- Address -->
                <h6 class="heading-small text-muted mb-4">Kontak</h6>
                <div class="pl-lg-4">
                  <div class="row">
                    <div class="col-md-12">
                      <div class="form-group">
                        <label class="form-control-label">Alamat rumah</label>
                        <p>'.strip_tags($data_user['alamat']).'</p>
                      </div>
                    </div>
                  </div>
                  <div class="row">
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">Email</label>
                        <p>'.strip_tags($data_user['email']).'</p>
                      </div>
                    </div>
                    <div class="col-lg-6">
                      <div class="form-group">
                        <label class="form-control-label">No. Telp</label>
                        <p>'.strip_tags($data_user['telp']).'</p>
                      </div>
                    </div>

                  </div>
                </div>
                <hr class="my-4" />
                <!-- Description -->
                <h6 class="heading-small text-muted mb-4">Pekerjaan</h6>
                <div class="pl-lg-4">
                  <div class="row">
                      <div class="col-lg-6">
                        <div class="form-group">
                          <label class="form-control-label">Penempatan</label>
                          <p>'.strip_tags($data_user['lokasi_nama']).'</p>
                        </div>
                      </div>
                      <div class="col-lg-6">
                        <div class="form-group">
                          <label class="form-control-label">Posisi/Jabatan</label>
                          <p>'.strip_tags($data_user['posisi_nama']).'</p>
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
  theme_404();
}
}?>

  
