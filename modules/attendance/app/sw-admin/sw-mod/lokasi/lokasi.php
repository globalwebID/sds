<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='5' AND level_id='$current_user[level]'";
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
              <li class="breadcrumb-item active" aria-current="page">Lokasi</li>
            </ol>
          </nav>
        </div>
        
      </div>
    </div>
  </div>
</div>

  <!-- Page content -->
  <div class="container-fluid mt--6 mb-5">
    <!-- Table -->
    <div class="row">
        <div class="col">
          <div class="card pb-3">
            <!-- Card header -->
            <div class="card-header mb-2">
              <h3 class="mt-2 mb-0 text-left float-left">Lokasi</h3>
              <div class="float-right">';
              if($data_role['modifikasi']=='Y'){
                echo'
                <a href="'.$mod.'&op=add"  class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</a>';
              }else{
                echo'<button class="btn btn-primary" disabled><i class="fas fa-plus"></i> Tambah</button>';
              }
              echo'
              </div>
            </div>';
            if($data_role['lihat']=='Y'){
            echo'
            <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped dataTable datatable-lokasi">
                <thead class="thead-light">
                  <tr>
                    <th width="5">No</th>
                    <th class="text-center" width="5">ID Lokasi</th>
                    <th class="text-center" width="5">Qr Code</th>
                    <th>Lokasi</th>
                    <th>Radius</th>
                    <th class="text-center">Radius Aktif</th> 
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
              <li class="breadcrumb-item"><a href="./'.$mod.'">Lokasi</a></li>
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
        <h3 class="mb-0">Tambah Data Lokasi</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
      if($data_role['modifikasi']=='Y'){
        echo'
        <form class="form-add" role="form" method="post" action="#" autocomplete="off">
          <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                  <label class="form-control-label">Nama Lokasi</label>
                  <input type="text" class="form-control" name="lokasi_nama" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Alamat Lengkap</label>
                  <textarea class="form-control" name="lokasi_alamat" id="alamat" rows="2" required></textarea>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Latitude</label>
                  <input type="text" class="form-control latitude" id="lat" name="lokasi_latitude" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Longitude</label>
                  <input type="text" class="form-control longitude" id="lng" name="lokasi_longitude" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Radius</label>
                  <input type="number" class="form-control" name="lokasi_radius" value="900" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Radius Aktif</label><br>
                  <label class="custom-toggle custom-toggle-info">
                    <input type="checkbox" name="lokasi_status" value="Y" checked>
                    <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                  </label>
                </div>
            </div>

            <div class="col-md-6">
            <div class="form-group">
              <label class="form-control-label pb-2">Google Map</label>
              <div class="card">
                  <input type="text" class="form-control mb-2" id="search" placeholder="Cari alamat...">
                   <ul id="suggestions"></ul>
                  <div id="map" style="height:460px"></div>
              </div>
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


/** Update Lokasi*/
break;
case 'update':
if(!empty($_GET['id'])){
  $id     =  anti_injection(convert('decrypt',$_GET['id'])); 
  $query_lokasi  ="SELECT * from lokasi WHERE lokasi_id='$id'";
  $result_lokasi = $connection->query($query_lokasi);

  echo'
  <div class="header bg-primary pb-6">
    <div class="container-fluid">
      <div class="header-body">
        <div class="row align-items-center py-4">
          <div class="col-lg-6 col-7">
            <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
              <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="./'.$mod.'">Lokasi</a></li>
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
        <h3 class="mb-0">Ubah Data Lokasi</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
      if($result_lokasi->num_rows > 0){
        $data_lokasi  = $result_lokasi->fetch_assoc();
        if($data_role['modifikasi']=='Y'){
        echo'
        <form class="form-update" role="form" method="post" action="#" autocomplete="off">
        <input type="hidden" class="d-none" name="id" value="'.epm_encode($data_lokasi['lokasi_id']).'" required readonly>
        <div class="row">
        <div class="col-md-6">
            <div class="form-group">
              <label class="form-control-label">Nama Lokasi</label>
              <input type="text" class="form-control" name="lokasi_nama" value="'.strip_tags($data_lokasi['lokasi_nama']).'" required>
            </div>

            <div class="form-group">
              <label class="form-control-label">Alamat Lengkap</label>
              <textarea class="form-control alamat" name="lokasi_alamat" id="alamat" rows="2" required>'.strip_tags($data_lokasi['lokasi_alamat']).'</textarea>
            </div>

            <div class="form-group">
              <label class="form-control-label">Latitude</label>
              <input type="text" class="form-control latitude" id="lat" name="lokasi_latitude" value="'.strip_tags($data_lokasi['lokasi_latitude']).'" required>
            </div>

            <div class="form-group">
              <label class="form-control-label">Longitude</label>
              <input type="text" class="form-control longitude" id="lng" name="lokasi_longitude"  value="'.strip_tags($data_lokasi['lokasi_longitude']).'" required>
            </div>

            <div class="form-group">
              <label class="form-control-label">Radius</label>
              <input type="number" class="form-control" name="lokasi_radius" value="'.strip_tags($data_lokasi['lokasi_radius']).'" required>
            </div>

            <div class="form-group">
              <label class="form-control-label">Radius Aktif</label><br>
                <label class="custom-toggle custom-toggle-info">';
                  if($data_lokasi['lokasi_status'] == 'Y'){
                  echo'
                  <input type="checkbox" name="lokasi_status" value="Y" checked>';
                  }else{
                  echo'
                  <input type="checkbox" name="lokasi_status" value="N">';
                  }
                  echo'
                  <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
              </label>
            </div>
        </div>

        <div class="col-md-6">
        <div class="form-group">
          <label class="form-control-label pb-2">Google Map</label>
          <div class="card">
            <input type="text" class="form-control mb-2" id="search" placeholder="Cari alamat...">
            <ul id="suggestions"></ul>
            <div id="map" style="height:460px"></div>
          </div>
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
    hak_akses();
  }
}?>