<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:../login/');
  exit;
}
else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='2' AND level_id='$current_user[level]'";
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
                  <li class="breadcrumb-item active" aria-current="page">Wali Murid</li>
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
              <h3 class="mt-2 mb-0 text-left float-left">Wali Murid</h3>
              <div class="float-right">';
                if($data_role['modifikasi']=='Y'){
                echo'
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
            <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped datatable-user">
                <thead class="bg-primary text-white">
                  <tr>
                    <th width="4">No</th>
                    <th class="text-center">Avatar</th>
                    <th>Nama</th>
                    <th>Siswa</th>
                    <th>Jenis Kelamin</th>
                    <th>Tanggal Daftar</th>
                    <th>Tanggal Login</th>
                    <th class="text-center">Status</th> 
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
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item"><a href="./'.$mod.'">Wali Murid</a></li>
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
    <!-- Form controls -->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h3 class="mb-0">Tambah Data Wali Murid</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
      if($data_role['modifikasi']=='Y'){
        echo'
        <form class="form-add" role="form" method="post" action="#" autocomplete="off">
          <div class="row">
            <div class="col-md-6">

                <div class="form-group">
                  <label class="form-control-label">Nama Lengkap</label>
                  <input type="text" class="form-control" name="nama_lengkap" required>
                </div>

                <div class="bg-secondary pl-1 pr-1 pt-1 card mb-2">
                  <h3>Biodata Siswa</h3>
                    <div class="form-group">
                      <label class="label" for="text4">NISN</label>
                      <input type="number" class="form-control nisn" min="" name="nisn" required>
                      <small class="result-output"></small>
                  </div>

                  <div class="form-group">
                    <label class="form-control-label">Nama Siswa</label>
                    <input type="text" class="form-control nama_siswa" name="nama_siswa" readonly required>
                  </div>

                </div>

                <div class="form-group">
                  <label class="form-control-label">Tempat Lahir</label>
                  <input type="text" class="form-control" name="tempat_lahir" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Jenis Kelamin</label>
                  <select class="form-control" name="jenis_kelamin" required>
                      <option value="Laki-laki">Laki-laki</option>
                      <option value="Perempuan">Perempuan</option>
                  </select>
                </div>

            </div>

            <!-- Right -->
             <div class="col-md-6">
              
                <div class="form-group">
                  <label class="form-control-label">Telp</label>
                  <input type="number" class="form-control" name="telp" required>
                  <small class="text-danger">Ex : 6283160901108</small>
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
                  <label class="form-control-label">Alamat</label>
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


  /** Update Wali Murid/User*/
break;
case 'update':
if(!empty($_GET['id'])){
  $id     =  htmlentities(convert('decrypt',$_GET['id'])); 
  $query_user  ="SELECT * FROM wali_murid WHERE wali_murid_id='$id'";
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
                  <li class="breadcrumb-item"><a href="./'.$mod.'">Wali Murid</a></li>
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
    <!-- Form controls -->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h3 class="mb-0">Ubah Data Wali Murid</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
      if($result_user->num_rows > 0){
        $data_user  = $result_user->fetch_assoc();
        if($data_role['modifikasi']=='Y'){
        echo'
        <form class="form-update" role="form" method="post" action="javascript:;" autocomplete="off">
        <input type="hidden" class="d-none" name="id" value="'.epm_encode($data_user['wali_murid_id']).'" required readonly>
          <div class="row">
            <div class="col-md-6">

              <div class="form-group">
                <label class="form-control-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" value="'.strip_tags($data_user['nama_lengkap']??'-').'" required>
              </div>

              <div class="bg-secondary pl-1 pr-1 pt-1 card mb-2">
                  <h3>Biodata Siswa</h3>
                    <div class="form-group">
                      <label class="label" for="text4">NISN</label>
                      <input type="number" class="form-control nisn" min="" name="nisn" value="'.strip_tags($data_user['nisn']??'-').'" required>
                      <small class="result-output"></small>
                  </div>

                  <div class="form-group">
                    <label class="form-control-label">Nama Siswa</label>
                    <input type="text" class="form-control nama_siswa" name="nama_siswa" value="'.strip_tags($data_user['nama_siswa']??'-').'" readonly required>
                  </div>
              </div>


              <div class="form-group">
                <label class="form-control-label">Tempat Lahir</label>
                <input type="text" class="form-control" name="tempat_lahir" value="'.strip_tags($data_user['tempat_lahir']).'" required>
              </div>


              <div class="form-group">
                <label class="form-control-label">Jenis Kelamin</label>
                  <select class="form-control" name="jenis_kelamin" required>';
                    if($data_user['jenis_kelamin'] =='Laki-laki'){
                      echo'<option value="Laki-laki" selected>Laki-laki</option>';
                    }else{
                        echo'<option value="Laki-laki">Laki-laki</option>';
                    }
                    if($data_user['jenis_kelamin'] =='Perempuan'){
                        echo'<option value="Perempuan">Perempuan</option>';
                    }else{
                        echo'<option value="Perempuan">Perempuan</option>';
                    }
                    echo'
                  </select>
              </div>
          </div>

          <!-- Right -->
          <div class="col-md-6">

              <div class="form-group">
                  <label class="form-control-label">Email</label>
                  <input type="email" class="form-control" name="email" value="'.strip_tags($data_user['email']).'" required>
              </div>

              <div class="form-group">
                <label class="form-control-label">No. WhatsApp</label>
                <input type="number" class="form-control" name="telp" value="'.strip_tags($data_user['telp']??'0').'" required>
                <small class="text-danger">Ex : 6283160901108</small>
              </div>

              <div class="form-group boxed">
                <label class="form-control-label">Alamat Rumah</label>
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


  break;
}

}else{
  /** Modul tidak ditemukan */
  theme_404();
}

}?>

  