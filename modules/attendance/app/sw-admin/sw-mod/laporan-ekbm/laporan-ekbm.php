<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
  $query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='10' AND level_id='$current_user[level]'";
  $result_role = $connection->query($query_role);
  if($result_role->num_rows > 0){
    $data_role = $result_role->fetch_assoc();

  switch(@$_GET['op']){ 
    default:
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
                  <li class="breadcrumb-item active" aria-current="page">Laporan E-KBM</li>
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
              <h3 class="mt-2 mb-0 text-left float-left">Laporan E-KBM</h3>
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
                    <select class="form-control kelas" name="kelas" required>';
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
                    <select class="form-control pelajaran" name="pelajaran" required>
                      <option value="">Pilih Mata Pelajaran</option>';
                      $query_matkul = "SELECT id, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel ASC";
                      $result_matkul = $connection->query($query_matkul);

                      $query_jadwal = "SELECT DISTINCT mata_pelajaran FROM jadwal_mengajar WHERE hari = '$hari_ini'";
                      $result_jadwal = $connection->query($query_jadwal);
                      $mapel_hari_ini = [];
                      while ($row = $result_jadwal->fetch_assoc()) {
                          $mapel_hari_ini[] = $row['mata_pelajaran'];
                      }

                      // Tampilkan dropdown
                      while($data_matkul = $result_matkul->fetch_assoc()){
                          $id = $data_matkul['id'];
                          $nama = strip_tags($data_matkul['nama_mapel'] ?? '-');
                          $is_today = in_array($id, $mapel_hari_ini);

                          $selected = $is_today ? 'selected' : '';
                          $style = in_array($id, $mapel_hari_ini) ? 'style="background:green;color:white;"' : '';
                          echo "<option value='$id' $style  $selected>$nama</option>";
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
              <hr class="mt-0 mb-1">
            </div>

            <div class="table-responsive">';
            if($data_role['lihat']=='Y'){
              echo'
              <table class="table align-items-center table-flush table-striped datatable">
                <thead class="bg-primary text-white">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>NISN</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Mata Pelajaran</th>
                    <th>Kehadrian</th>
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

  