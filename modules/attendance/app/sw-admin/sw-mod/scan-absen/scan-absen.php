<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
switch(@$_GET['op']){ 
  default:
echo'

<!-- Header -->
<div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
            <h6 class="h2 text-white d-inline-block mb-0">Absen</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Absen</li>
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
              <h3 class="mt-2 mb-0 text-left float-left">Data Absensi '.tanggal_ind($date).'</h3>
              <div class="float-right">
                <button class="btn btn-primary btn-add-in"><i class="fas fa-search"></i> Scan Masuk</button>
                <button class="btn btn-danger btn-add-out"><i class="fas fa-search"></i> Scan Keluar</button>
              </div>
            </div>
          
            <div class="table-responsive">
              
              <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th class="text-center">Jam Masuk</th>
                    <th class="text-center">Jam Pulang</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
      
            </div>
          </div>
        </div>
      </div>


      <!-- Modal ADD -->
      <div  class="modal fade modal-add" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true" aria-hidden="true" data-backdrop="static" data-keyboard="false">
            <div class="modal-dialog" role="document">
                <div class="modal-content bg-primary">
                    <div class="modal-header">
                        <h5 class="modal-title text-white">Modal Title <span class="modal-title-name text-info"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form class="form-absen" role="form" action="javascript:void(0);">
                    <div class="modal-body pt-1 pb-1">
                        <p class="text-white">Gunakan Mesin Scanner yang sudah support qrcode untuk absensi ini</p>
                        
                          <input type="hidden" class="form-control latitude" name="latitude" readonly>
                          <input type="hidden" class="form-control d-none tipe" name="tipe" readonly>
                          <div class="form-group mb-1">
                              <input type="text" class="form-control form-control-lg qrcode" name="qrcode" placeholder="Scan Qrcode disini" required>
                          </div>
                          <spa class="text-white">'.format_hari_tanggal($date).'</span>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success btn-submit">Simpan</button>
                        <button type="button" class="btn  btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                     </form>
                </div>
            </div>
        </div>';
    
  break;
  }

}?>

  