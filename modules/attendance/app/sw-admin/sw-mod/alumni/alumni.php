<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}
else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='1' AND level_id='$current_user[level]'";
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
              <li class="breadcrumb-item"><a href="./user">Siswa</a></li>
              <li class="breadcrumb-item active" aria-current="page">Alumni</li>
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
          <h3 class="mt-2 mb-0 text-left float-left">Alumni</h3>
        </div>';
        if($data_role['lihat']=='Y'){
        echo'
        <form class="form-table" action="javascript:void(0);" autocomplete="of">
        <div class="table-responsive">
          <table class="table align-items-center table-flush table-striped datatable-user">
            <thead class="bg-primary text-white">
              <tr>
                <th width="2" class="text-center">
                  #
                </th>
                <th width="4">No</th>
                <th>Nama</th>
                <th>NISN</th>
                <th>Jenis Kelamin</th>
                <th>Tgl Lahir</th>
                <th>Kelas</th>
                <th>Tanggal Login</th>
                <th>Status</th> 
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot class="bg-primary text-white">
                <tr>
                    <th colspan="10">
                      <div class="row">
                        <div class="col-md-1">
                          <label class="mt-3">Status</label>
                        </div>

                        <div class="col-md-2">
                          <select class="form-control status" name="status" required>
                            <option value="Y">Aktif</option>
                          </select>
                        </div>

                        <div class="col-md-2">
                          <button class="btn btn-warning btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
                        </div>
                    </th>
                </tr>
            </tfoot>

          </table>
        </div>
        </form>';
      }else{
        hak_akses();
      }
      echo'
      </div>
    </div>
  </div>

<div class="modal fade modal-import data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
<div class="modal-dialog modal-md">
<div class="modal-content">
  <div class="modal-header">
    <h5 class="modal-title">Impor Data Alumni</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
  <form class="form-import" action="javascript:void(0);" autocomplete="of">
    <div class="modal-body">
      <div class="form-group">
        <label>Upload file</label>
        <a href="../sw-content/tema/Format-siswa.xlsx" class="float-right" target="_blank"><i class="fas fa-download"></i> Format</a>
        <input type="file" class="form-control" name="file_excel" accept=".xls,.xlsx" placeholder="Impor data" required>
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary btn-save"><i class="fas fa-save"></i> Simpan</button>
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    </div>
  </form>
  </div>
</div>
</div>';
  break;
  }

  }else{
    /** Modul tidak ditemukan */
  }

}?>

  