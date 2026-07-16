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
                  <li class="breadcrumb-item active" aria-current="page">Laporan Absensi /Bulan</li>
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
              <h3 class="mt-2 mb-0 text-left float-left">Laporan Absensi /Bulan</h3>
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

          <div class="card-body pb-3">
            <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                  <select class="form-control lokasi lokasi-dropdown" name="lokasi" required>';
                      $query_lokasi ="SELECT lokasi_id,lokasi_nama FROM lokasi ORDER BY lokasi_nama ASC";
                      $result_lokasi = $connection->query($query_lokasi);
                      while ($data_lokasi = $result_lokasi->fetch_assoc()) {
                        echo'<option value="'.$data_lokasi['lokasi_id'].'">'.$data_lokasi['lokasi_nama'].'</option>';
                      }
                    echo'
                  </select>
                </div>
              </div>


              <div class="col-md-3">
                <div class="form-group">
                  <select class="form-control pegawai" required>
                    <option value="">Pilih Pegawai</option>
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
            <hr class="mt-0 mb-2">
            </div>

            <div class="table-responsive" style="overflow:auto">';
            if($data_role['lihat']=='Y'){
              echo'
              <table class="table align-items-center table-flush table-striped datatable">
                <thead class="bg-primary text-white">
                   <tr>
                    <th rowspan="2" class="text-center">No</th>
                    <th rowspan="2">Tanggal</th>
                    <th rowspan="2">Pegawai</th>
                    <th rowspan="2">Jam Kerja</th>
                    <th rowspan="2">Tolerasi</th>
                    <td colspan="2" class="text-center">Absen Masuk</td>
                    <td colspan="2" class="text-center">Absen Pulang</td>
                    <th rowspan="2">Durasi</th>
                    <th rowspan="2">Radius</th>
                    <th rowspan="2">Kehadrian</th>
                    <th rowspan="2">Map</th>
                    <th rowspan="2">Keterangan</th>
                    <th rowspan="2">Aksi</th>
                  </tr>
                  <tr class="bg-primary text-white">
                    <th class="text-center">Foto</th>
                    <th class="text-center">Jam</th>
                    <th class="text-center">Foto</th>
                    <th class="text-center">Jam</th>
                  </tr>
                </thead>
                <tbody>

                </tbody>
                <tfoot class="bg-secondary">
                    <tr>

                        <td class="text-right"></td>
                        <td class="text-right"></td>
                        <td class="text-right text-white">TOTAL</td>
               
                        <td class="text-right">Hadir : </td>
                        <td class="font-weight-bold"><span class="badge badge-success hadir-cell"></span></td>

                        <td class="text-right">Telat : </td>
                        <td><span class="badge badge-danger telat-cell"></span></td>

                        <td class="text-right">Izin : </td>
                        <td><span class="badge badge-warning izin-cell"></span></td>

                        <td class="text-right">Sakit : </td>
                        <td><span class="badge badge-info sakit-cell"></span></td>

                        <td class="text-right">Total Jam Bekerja:</td>
                        <td><span class="badge badge-info total-jam-cell"></span></td>

                        <td class="text-right">Total Belum Absen:</td>
                        <td><span class="badge badge-danger belum-absen-cell"></span></td>

                        
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

      if($data_role['modifikasi']=='Y'){
      echo'
      <div  class="modal fade modal-add" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <form class="form-add" role="form" action="#" autocomplete="off">
                    <input type="hidden" class="form-control id d-none" name="id" readonly>
                    <div class="modal-header">
                        <h5 class="modal-title">Modal Title</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>

                    <div class="modal-body">

                      <div class="row">
                        <div class="col">
                          <div class="form-group">
                            <label>Absen Masuk</label>
                            <input type="text" class="form-control absen-in" name="absen_in" required>
                          </div>
                        </div>

                        <div class="col">
                          <div class="form-group">
                            <label>Status</label>
                            <select class="form-control status-masuk" name="status_masuk">
                              <option value="">Pilih Status</option>
                              <option value="Tepat Waktu">Tepat Waktu</option>
                              <option value="Telat">Telat</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <div class="row">
                        <div class="col">
                          <div class="form-group">
                            <label>Absen Pulang</label>
                            <input type="text" class="form-control absen-out" name="absen_out" required>
                          </div>
                        </div>

                        <div class="col">
                          <div class="form-group">
                            <label>Status</label>
                            <select class="form-control status-pulang" name="status_pulang">
                              <option value="">Pilih Status</option>
                              <option value="Pulang Cepat">Pulang Cepat</option>
                              <option value="Tepat Waktu">Tepat Waktu</option>
                            </select>
                          </div>
                        </div>
                      </div>

                      <div class="form-group">
                        <label>Keterangan</label>
                        <textarea class="form-control keterangan" name="keterangan" rows="2"></textarea>
                      </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn  btn-primary btn-save">Simpan</button>
                        <button type="button" class="btn  btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
                </div>
            </div>
        </div>';
      }
  break;
  }
  }else{
    theme_404();
  }
}?>