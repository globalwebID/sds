<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='10' AND level_id='$current_user[level]'";
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
              <li class="breadcrumb-item active" aria-current="page">Laporan hari ini</li>
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
            <h3 class="mt-2 mb-0 text-left float-left">Laporan hari ini</h3>
            <div class="float-right">
              <div class="dropdown">
                  <button class="btn btn-outline-primary dropdown-toggle" type="button" id="download" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Download
                  </button>
                  <div class="dropdown-menu" aria-labelledby="download">
                    <button class="dropdown-item btn-download" type="button" data="print"><i class="fas fa-print"></i> PRINT</button>
                    <button class="dropdown-item btn-download" type="button" data="pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                    <button class="dropdown-item btn-download" type="button" data="excel"><i class="far fa-file-excel"></i> EXCEL</button>
                  </div>
                </div>
              </div>
          </div>

          <div class="card-body pb-1">
            <div class="row">
              <div class="col-md-3">
                <div class="form-group">
                  <select class="form-control lokasi" name="lokasi" required>
                    <option value="">Semua Lokasi</option>';
                      $query_lokasi ="SELECT lokasi_id,lokasi_nama FROM lokasi ORDER BY lokasi_nama ASC";
                      $result_lokasi = $connection->query($query_lokasi);
                      while ($data_lokasi = $result_lokasi->fetch_assoc()) {
                        echo'<option value="'.convert('encrypt',$data_lokasi['lokasi_id']).'">'.$data_lokasi['lokasi_nama'].'</option>';
                      }
                    echo'
                  </select>
                </div>
              </div>


              <div class="col-md-3">
                  <div class="form-group">
                    <div class="input-group input-group-merge">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="ni ni-calendar-grid-58"></i></span>
                      </div>
                      <input class="form-control datepicker tanggal" value="'.tanggal_ind($date).'" placeholder="Tanggal" type="text">
                    </div>
                  </div>
              </div>

            </div>
              <hr class="mt-0 mb-2">
            </div>

            <div class="table-responsive">';
            if($data_role['lihat']=='Y'){
              echo'
              <table class="table align-items-center table-flush table-striped datatable">
                <thead class="bg-primary text-white">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>Nama</th>
                    <th>Jabatan</th>
                    <th>Absen Masuk</th>
                    <th>Absen Pulang</th>
                    <th>Radius</th>
                    <th>Lokasi</th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="3" class="text-right">Total Belum Absen:</th>
                        <th colspan="4" id="belum-absen-cell" class="text-danger font-weight-bold"></th>
                    </tr>
                </tfoot>
              </table>';
            }else{
              hak_akses();
            }
            echo'
            </div>
          </div>
        </div>
      </div>';

  break;
  }
  }else{
    theme_404();
  }
}?>

  