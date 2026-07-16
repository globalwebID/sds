<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}else{
  $query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='10' AND level_id='$current_user[level]'";
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

if(!empty($_GET['from']) && !empty($_GET['to'])){
  $from       = !empty($_GET['from']) ? date('d-m-Y', strtotime($_GET['from'])) : $date;
  $to         = !empty($_GET['to']) ? date('Y-m-d', strtotime($_GET['to'])) : $date;
  $startDate  = new DateTime($from);
  $endDate    = new DateTime($to);
}else{
  $from       = $tanggal_awal;
  $to         = date('Y-m-d');
  $startDate = new DateTime(date('Y-m-d', strtotime($tanggal_awal)));
  $endDate = new DateTime($date);
}


 function cekLibur($connection, $tanggal) {
  $query = "SELECT keterangan FROM libur_nasional WHERE libur_tanggal ='$tanggal'";
  $result = $connection->query($query);
  if ($result->num_rows > 0) {
      $data = $result->fetch_assoc();
      return [
          'is_libur' => true,
          'keterangan' => strip_tags($data['keterangan'] ?? ''),
          'status_libur' => 'danger'
      ];
  }

  return [
      'is_libur' => false,
      'keterangan' => '',
      'status_libur' => ''
  ];
}

// Memanggil fungsi

$englishToIndonesianDays = [
  'monday'    => 'senin',
  'tuesday'   => 'selasa',
  'wednesday' => 'rabu',
  'thursday'  => 'kamis',
  'friday'    => 'jumat',
  'saturday'  => 'sabtu',
  'sunday'    => 'minggu'
];
$libur_hari = [];

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
              <li class="breadcrumb-item active" aria-current="page">Laporan /Hari</li>
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
          <h3 class="mt-2 mb-0 text-left float-left">Laporan /Hari</h3>
          <div class="float-right">
            <div class="dropdown">
                  <button class="btn btn-outline-primary dropdown-toggle" type="button" id="download" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Download
                  </button>
                  <div class="dropdown-menu" aria-labelledby="download">
                    <button class="dropdown-item btn-download" type="button" data="print"><i class="fas fa-print"></i> PRINT</button>
                    
                    <button class="dropdown-item btn-download" type="button" data="excel"><i class="far fa-file-excel"></i> EXCEL</button>
                  </div>
                </div>
            </div>
        </div>

      <div class="card-body pb-3">
        <div class="row">

        <div class="col-md-2">
            <div class="form-group">
              <select class="form-control kelas kelas-dropdown" name="kelas" required>
                <option value="">Semua Kelas</option>';
                  $selectedKelas = isset($_GET['kelas']) ? $_GET['kelas'] : null;
                  $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                  $result_kelas = $connection->query($query_kelas);
                  while ($data_kelas = $result_kelas->fetch_assoc()) {
                    $selected = ($data_kelas['nama_kelas'] == $selectedKelas) ? 'selected="selected"' : '';
                    echo'<option value="'.$data_kelas['nama_kelas'].'" '.$selected.'>'.$data_kelas['nama_kelas'].'</option>';
                  }
                echo'
              </select>
            </div>
          </div>

          <div class="col-md-2">
            <div class="form-group">';
              $selectedSiswa = isset($_GET['siswa']) ? $_GET['siswa'] : null;
              echo'<span class="text-muted result-siswa d-none">'.$selectedSiswa.'</span>
              <select class="form-control siswa" required>
                <option value="">Pilih Siswa</option>
              </select>
            </div>
          </div>

          <div class="col-md-5">
              <div class="row input-daterange datepicker align-items-center">
                <div class="col">
                  <div class="form-group">
                    <div class="input-group input-group-merge">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                      </div>
                      <input class="form-control datepicker from" placeholder="Start date" type="text" value="'.$from.'">
                    </div>
                  </div>
                </div>

                <div class="col">
                  <div class="form-group">
                    <div class="input-group input-group-merge">
                      <div class="input-group-prepend">
                        <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                      </div>
                      <input class="form-control datepicker to" placeholder="End date" type="text" value="'.tanggal_ind($to).'">
                    </div>
                  </div>
                </div>
              </div>
          </div>

          <div class="col-md-2">
            <div class="form-group">
              <button type="button" class="btn btn-primary btn-block btn-cari"><i class="fas fa-search"></i> Cari</button>
            </div>
          </div>
    
        </div>
        <hr class="mt-0 mb-2">
        </div>
        <div class="table-responsive">';
        if($data_role['lihat']=='Y'){
          echo'
          <table class="table align-items-center table-bordered table-striped datatable" style="width:100%">
            <thead class="bg-primary text-white">
                <tr>
                <th rowspan="2" class="text-center">No</th>
                <th rowspan="2">Nama Siswa</th>
                <th rowspan="2">Kelas</th>';
                while ($startDate <= $endDate) {
                  $dateStr =$startDate ->format('Y-m-d');
                  $dayNameEnglish = strtolower($startDate->format('l'));
                  $dayName = $englishToIndonesianDays[$dayNameEnglish];
                  $hari_libur = date('D',strtotime($dateStr));

                  $query_libur = "SELECT hari FROM jam_sekolah WHERE active='N' AND tipe='Siswa'";
                  $result_libur = $connection->query($query_libur);
                  if ($result_libur && $result_libur->num_rows > 0) {
                      while ($row_libur = $result_libur->fetch_assoc()) {
                          // Simpan dalam format lowercase untuk memudahkan perbandingan
                          $libur_hari[] = strtolower($row_libur['hari']);
                      }
                  }
                
                  $holidayName = in_array($dayName, $libur_hari) ? 'Libur' : '';
                  $liburInfo = cekLibur($connection, $dateStr);
                  /** End Cek Libur */
                  $backgroundColor = '';
                  if ($holidayName=='Libur') {
                      $backgroundColor = 'bg-danger text-white';
                  } else {
                      if ($liburInfo['is_libur']) {
                        $backgroundColor = 'bg-danger text-white';
                      }
                  }
                          
                    echo'<th colspan="2" class="text-center '.$backgroundColor.'">'.format_hari_tanggal($dateStr).'</th>';
                    $startDate->modify('+1 day');
                }
                echo'
                <th rowspan="2" class="text-center">Hadir</th>
                <th rowspan="2" class="text-center">Telat</th>
                <th rowspan="2" class="text-center">Izin</th>
                <th rowspan="2" class="text-center">Sakit</th>
                <th rowspan="2" class="text-center">Alpha</th>
              </tr>

              <tr class="bg-primary text-white">';
              $startDate  = new DateTime($from);
                while ($startDate <= $endDate) {
                echo'
                <th class="text-center">IN</th>
                <th class="text-center">OUT</th>';
                  $startDate->modify('+1 day');
                }
                echo'
                
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