<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='24' AND level_id='$current_user[level]'";
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
                  <li class="breadcrumb-item active" aria-current="page">Absen Manual Siswa</li>
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
              <h3 class="mb-0 text-left float-left">Absen Manual Siswa</h3>
            </div>';
            if($data_role['lihat']=='Y'){
              echo'
              <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="bg-primary text-white">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>Nama Siswa</th>
                    <th>Absen Masuk</th>
                    <th>Absen Pulang</th>
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
            <div class="card">
              <div class="card-header mb-2">
                <h3 class="mb-0 text-left float-left">Input Absen Manual Siswa</h3>
              </div>

              <div class="card-body">
                <ul class="nav nav-tabs custom-nav-tabs" id="myTab" role="tablist">
                  <li class="nav-item" role="presentation">
                    <button class="nav-link active btn-absen" data-tipe="manual" id="manual-tab" data-toggle="tab" data-target="#manual" type="button" role="tab" aria-controls="manual" aria-selected="true">Absen Manual</button>
                  </li>
    
                  <li class="nav-item" role="presentation">
                    <button class="nav-link btn-absen" data-tipe="webcame" id="kamera-tab" data-toggle="tab" data-target="#kamera" type="button" role="tab" aria-controls="kamera" aria-selected="false">Absen Kamera</button>
                  </li>
                </ul>
                <div class="tab-content" id="myTabContent">
                  <div class="tab-pane fade show active pt-3" id="manual" role="tabpanel" aria-labelledby="manual-tab">
                    <form class="form-add" role="form" action="javascript:;">
                      <input type="hidden" class="form-control id d-none" name="id" readonly>
                      <input type="hidden" class="form-control latitude d-none" name="latitude" readonly>

                      <div class="form-group">
                        <label>Cari NISN</label>
                        <input type="text" class="form-control nisn" name="nisn" required>
                        <small class="result-output"></small>
                      </div>

                      <div class="form-group">
                        <label>Siswa</label>
                        <input type="text" class="form-control siswa" name="siswa" readonly required>
                      </div>

                      <div class="form-group">
                        <label>Kelas</label>
                        <input type="text" class="form-control result-kelas" readonly required>
                      </div>

                      <div class="form-group">
                        <label>Kehadiran</label>
                        <select class="form-control kehadiran" name="kehadiran" required>
                          <option value="">Pilih Kehadiran</option>
                          <option value="masuk">Masuk</option>
                          <option value="pulang">Pulang</option>
                        </select>
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

                  <div class="tab-pane fade pt-3" id="kamera" role="tabpanel" aria-labelledby="kamera-tab">

                    <div class="form-group">
                      <label>Kehadiran</label>
                      <select class="form-control kehadiran-webcame" required>
                        <option value="">Pilih Kehadiran</option>
                        <option value="masuk">Masuk</option>
                        <option value="pulang">Pulang</option>
                      </select>
                    </div>

                    <div class="webcame text-center">
                      <div id="reader"></div>
                    </div>
                  </div>
         
                </div>
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

  