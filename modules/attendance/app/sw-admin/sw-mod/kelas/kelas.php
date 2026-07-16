<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
  $query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='5' AND level_id='$current_user[level]'";
  $result_role = $connection->query($query_role);
  if($result_role->num_rows > 0){
    $data_role = $result_role->fetch_assoc();

switch(@$_GET['op']){
default:
echo'
<span class="modifikasi d-none">'.convert('encrypt', $data_role['modifikasi']).'</span>
<span class="hapus d-none">'.convert('encrypt',$data_role['hapus']).'</span>
<div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Tingkat</li>
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
        <div class="col-md-8">
          <div class="card pb-3">
            <!-- Card header -->
            <div class="card-header mb-2">
              <h3 class="mb-0 text-left float-left">Tingkatan</h3>
            </div>';
            if($data_role['lihat']=='Y'){
              echo'
              <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th class="text-center" width="6">ID</th>
                    <th>Tingkat</th>
                    <th>Kelas</th>
                    <th class="text-center" width="10">Aksi</th>
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

          <div class="col-md-4">
            <div class="card ">
              <div class="card-header mb-2">
                <h3 class="mb-0 text-left float-left">Input Tingkat</h3>
              </div>

              <div class="card-body">
                <form class="form-add" role="form" action="javascript:;">
                <input type="hidden" class="form-control id d-none" name="id" readonly>

                <div class="form-group">
                    <label>Tingkat</label>
                    <input type="text" class="form-control nama_kelas" name="nama_kelas" required>
                </div>

                <hr>';
                if($data_role['modifikasi']=='Y'){
                    echo'
                    <button type="submit" class="btn  btn-primary btn-save"><i class="far fa-save"></i> Simpan</button>
                    <button class="btn btn-secondary btn-reset" type="reset"><i class="fas fa-undo"></i> Reset</button>';
                  }else{
                    echo'
                    <button class="btn btn-primary" disabled><i class="far fa-save"></i> Simpan</button>';
                  }
                echo'
                </form>
              </div>
             </div>

          </div>
      </div>';

break;
case'tingkat':
if(!empty($_GET['id'])){
$kelas_id = anti_injection(convert('decrypt',$_GET['id']??'-'));
$query_kelas="SELECT kelas_id,nama_kelas FROM absensi_kelas WHERE kelas_id='$kelas_id'";
$result_kelas = $connection->query($query_kelas);
if(!empty($result_kelas && $result_kelas->num_rows > 0)){
    $data = $result_kelas->fetch_assoc();
echo'
<span class="modifikasi d-none">'.convert('encrypt', $data_role['modifikasi']).'</span>
<span class="hapus d-none">'.convert('encrypt',$data_role['hapus']).'</span>
<span class="kelas d-none">'.convert('encrypt', $data['kelas_id']).'</span>
<div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-10 col-10">
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="./'.$mod.'">Tingkat</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Kelas</li>
                </ol>
              </nav>
            </div>

            <div class="col-lg-2 col-2">
              <a href="'.$mod.'" class="btn btn-sm btn-secondary float-right">Kembali</a>
            </div>
            
          </div>
        </div>
      </div>
    </div>
    <!-- Page content -->
    <div class="container-fluid mt--6">
      <!-- Table -->
      <div class="row">
        <div class="col-md-8">
          <div class="card pb-3">
            <!-- Card header -->
            <div class="card-header mb-2">
              <h3 class="mb-0 text-left float-left">Kelas</h3>
            </div>';
            if($data_role['lihat']=='Y'){
              echo'
              <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>Tingkat</th>
                    <th>Kelas</th>
                    <th class="text-center" width="10">Aksi</th>
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

          <div class="col-md-4">
            <div class="card ">
              <div class="card-header mb-2">
                <h3 class="mb-0 text-left float-left">Input Kelas</h3>
              </div>

              <div class="card-body">
                <form class="form-add-kelas" role="form" action="javascript:;">
                <input type="hidden" class="form-control id d-none" name="id" readonly>

                <div class="form-group">
                    <label>Taingkat</label>
                    <input type="hidden" class="form-control" name="kelas_id" value="'.convert('encrypt', $data['kelas_id']).'" readonly>
                    <input type="text" class="form-control" value="'.$data['nama_kelas'].'" readonly>
                </div>

                <div class="form-group">
                    <label>Kelas</label>
                    <input type="text" class="form-control nama_kelas" name="nama_kelas" required>
                </div>

                <hr>';
                if($data_role['modifikasi']=='Y'){
                    echo'
                    <button type="submit" class="btn  btn-primary btn-save"><i class="far fa-save"></i> Simpan</button>
                    <button class="btn btn-secondary btn-reset" type="reset"><i class="fas fa-undo"></i> Reset</button>';
                  }else{
                    echo'
                    <button class="btn btn-primary" disabled><i class="far fa-save"></i> Simpan</button>';
                  }
                echo'
                </form>
              </div>
             </div>

          </div>
      </div>';
    }else{
      theme_404();
    }
  }
  break;
  }
  }else{
    theme_404();
  }
}?>

  