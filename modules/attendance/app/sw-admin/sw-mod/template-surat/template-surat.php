<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='26' AND level_id='$current_user[level]'";
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
              <li class="breadcrumb-item active" aria-current="page">Template Surat</li>
            </ol>
          </nav>
        </div>
        
      </div>
    </div>
  </div>
</div>

  <!-- Page content -->
  <div class="container-fluid mt--6">
    <div class="row">
        <div class="col-md-7">
          <div class="card pb-3">
            <!-- Card header -->
            <div class="card-header mb-2">
              <h3 class="mb-0 text-left float-left">Template Surat</h3>
            </div>';
            if($data_role['lihat']=='Y'){
              echo'
              <div class="table-responsive">
                <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                  <thead class="thead-light">
                    <tr>
                      <th class="text-center" width="5">No</th>
                      <th>Kode</th>
                      <th>Jenis Surat</th>
                      <th>Tanggal Terbit</th>
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

          <div class="col-md-5">
            <div class="card ">
              <div class="card-header mb-2">
                <h3 class="mb-0 text-left float-left">Input Template Surat</h3>
              </div>

              <div class="card-body">
                <form class="form-add" role="form" action="javascript:;">
                  <input type="hidden" class="form-control id d-none" name="id" readonly>
                  
                  <div class="form-group">
                      <label>Kode</label>
                      <input type="text" class="form-control kode" name="kode" value="" required>
                  </div>

                  <div class="form-group">
                      <label>Template</label>
                      <textarea name="template" class="form-control template input-text" rows="10" required></textarea>
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
  }
  }else{
    theme_404();
  }
}?>