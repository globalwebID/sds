<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
  $query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='25' AND level_id='$current_user[level]'";
  $result_role = $connection->query($query_role);
  if($result_role->num_rows > 0){
    $data_role = $result_role->fetch_assoc();

switch(@$_GET['op']){ 
default:
$tanggal_awal = '1-'.date('m-Y');
$tanggal = DateTime::createFromFormat('d-m-Y', $tanggal_awal);
$tanggal_minimum = clone $tanggal;
$tanggal_minimum->modify('first day of this month');
$tanggal->modify('-6 days');
// Bandingkan dengan tanggal minimum
if ($tanggal < $tanggal_minimum) {
    $tanggal = $tanggal_minimum;
}
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
                  <li class="breadcrumb-item active" aria-current="page">Laporan Pelanggaran</li>
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
              <h3 class="mt-2 mb-0 text-left float-left">Laporan Pelanggaran</h3>
              <div class="float-right">
              <div class="dropdown">
                  <button class="btn btn-outline-primary dropdown-toggle" type="button" id="download" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Download
                  </button>
                  <div class="dropdown-menu" aria-labelledby="download">
                    <button class="dropdown-item btn-download" type="button" data="print"><i class="fas fa-print"></i>PRINT</button>
                    <button class="dropdown-item btn-download" type="button" data="pdf"><i class="fas fa-file-pdf"></i> PDF</button>
                    <button class="dropdown-item btn-download" type="button" data="excel"><i class="far fa-file-excel"></i> EXCEL</button>
                  </div>
                </div>
              </div>
            </div>

            <div class="card-body">
              <div class="row">
                <div class="col-md-1">
                  Filter :
                </div>
                
                <div class="col-md-3">
                  <div class="form-group">
                    <select class="form-control kelas kelas-dropdown" name="kelas" required>
                      <option value="">Semua Kelas</option>';
                        $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                        $result_kelas = $connection->query($query_kelas);
                        while ($data_kelas = $result_kelas->fetch_assoc()) {
                          echo'<option value="'.$data_kelas['nama_kelas'].'">'.$data_kelas['nama_kelas'].'</option>';
                        }
                      echo'
                    </select>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="form-group">
                    <select class="form-control siswa" required>
                      <option value="">Pilih Siswa</option>
                    </select>
                  </div>
                </div>

              <div class="col-md-4">
                  <div class="row input-daterange datepicker align-items-center">
                    <div class="col">
                      <div class="form-group">
                        <div class="input-group input-group-merge">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                          </div>
                          <input class="form-control datepicker from" placeholder="Start date" type="text" value="'. $tanggal->format('d-m-Y').'">
                        </div>
                      </div>
                    </div>

                    <div class="col">
                      <div class="form-group">
                        <div class="input-group input-group-merge">
                          <div class="input-group-prepend">
                            <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                          </div>
                          <input class="form-control datepicker to" placeholder="End date" type="text" value="'.tanggal_ind($date).'">
                        </div>
                      </div>
                    </div>
                  </div>
              </div>

              </div>
              <hr class="mt-0 mb-1">
            </div>

            <div class="table-responsive">';
            if($data_role['lihat']=='Y'){
              echo'
              <table class="table align-items-center table-flush table-striped datatable">
                <thead class="bg-primary text-white">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Bobot Pelanggaran</th>
                    <th>Jumlah Kehadiran</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
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