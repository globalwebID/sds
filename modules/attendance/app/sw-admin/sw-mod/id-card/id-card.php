<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='16' AND level_id='$current_user[level]'";
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
              <li class="breadcrumb-item active" aria-current="page">ID Card</li>
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
              <h3 class="mt-2 mb-0 text-left float-left">Tema ID Card</h3>
              <div class="float-right">';
              if($data_role['modifikasi']=='Y'){
                echo'
                <button class="btn btn-primary btn-add"><i class="fas fa-plus"></i> Tambah</button>';
              }else{
                echo'
                <button class="btn btn-primary btn-error"><i class="fas fa-plus"></i> Tambah</button>';
              }
              echo'
              </div>
            </div>';
        if($data_role['lihat']=='Y'){
        echo'
            <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th width="5">No</th>
                    <th width="10" class="text-center"><i class="far fa-images"></i></th>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th class="text-center">Aktif</th>
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

      if($data_role['modifikasi']=='Y'){
      echo'
      <!-- Modal ADD -->
      <div  class="modal fade modal-add" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <form class="form-add" role="form" action="#">
                    <input type="hidden" class="form-control id d-none" name="id" readonly>
                    <div class="modal-header">
                        <h5 class="modal-title">Modal Title</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>

                    <div class="modal-body">

                        <div class="form-group">
                            <label>Nama</label>
                            <input type="text" class="form-control nama" name="nama" required>
                        </div>

                        <div class="form-group">
                            <label>Upload Foto</label>
                            <div class="file-upload">
                              <div class="image-upload-wrap">
                                <input class="file-upload-input fileInput" type="file" name="foto" onchange="readURL(this);" accept="image/*">
                                  <div class="drag-text">
                                    <i class="lni lni-cloud-upload"></i>
                                    <h3>Drag and drop files here</h3>
                                  </div>
                              </div>
                                <div class="file-upload-content">
                                  <img class="file-upload-image" src="sw-assets/img/media.png" alt="Upload" height="150">
                                    <div class="image-title-wrap">
                                      <button type="button" onclick="removeUpload()" class="btn btn-danger btn-sm"><i class="fas fa-undo"></i> Ubah<span class="image-title"></span></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                          <label>Tipe</label>
                          <select class="form-control" name="tipe" required>
                            <option value="P">Portrait</option>
                            <option value="L">Landscape</option>
                          </select>
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

  