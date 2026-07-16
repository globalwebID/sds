<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
  $query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='4' AND level_id='$current_user[level]'";
  $result_role = $connection->query($query_role);
  if($result_role->num_rows > 0){
    $data_role = $result_role->fetch_assoc();

switch(@$_GET['op']){ 
  default:
echo'
<!-- Header -->
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
                  <li class="breadcrumb-item active" aria-current="page">Artikel</li>
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
              <h3 class="mt-2 mb-0 text-left float-left">Artikel</h3>
              <div class="float-right">
                <a href="'.$mod.'&op=add"  class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</a>
              </div>
            </div>
            <div class="table-responsive">';
            if($data_role['lihat']=='Y'){
              echo'
              <table class="table align-items-center table-flush table-striped datatable">
                <thead class="thead-light">
                  <tr>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Tanggal</th>
                    <th class="text-center">Aksi</th>
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


/* -------------- Add -------------- */
break;
case 'add':
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
                  <li class="breadcrumb-item"><a href="./'.$mod.'">Artikel</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Tambah</li>
                </ol>
              </nav>
            </div>
            
          </div>
        </div>
      </div>
    </div>

    <div class="modal fade" id="fileManagerModal" tabindex="-1" aria-labelledby="fileManagerLabel" aria-hidden="true" style="z-index:999999">
      <div class="modal-dialog modal-xl">
        <div class="modal-content">
          <div class="modal-header pt-3 pb-2">
            <h5 class="modal-title" id="fileManagerLabel">File Manager</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body p-0">
            <iframe id="filemanager_iframe" src="" style="width:100%;height:600px;border:none;"></iframe>
          </div>
        </div>
      </div>
    </div>

    <div class="container-fluid mt--6 mb-5">
      <div class="card-wrapper">
          <div class="card">
            <div class="card-body">';
            if($data_role['modifikasi']=='Y'){
              echo'
              <form class="form-add" role="form" method="post" action="#" autocomplete="off">
                <div class="row">
                  <div class="col-md-8">
                    
                        <div class="form-group">
                          <label class="form-control-label">Judul</label>
                          <input type="text" class="form-control" name="judul" required>
                        </div>

                        <div class="form-group">
                          <label class="form-control-label">Deskripsi</label>
                          <textarea class="form-control swEditorText"  name="deskripsi" style="height:350px;"></textarea>
                        </div>

                  </div>

                    <div class="col-md-4">
                      
                        <div class="form-group">
                          <label>Thumbnail</label>
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
                        <label class="form-control-label">Kategori</label>
                          <div class="input-group">
                              <select class="form-control kategori" name="kategori" required>
                                  <option value="">Pilih Kategori:</option>';
                                  $query="SELECT * from kategori order by title ASC";
                                  $result = $connection->query($query);
                                  while($row = $result->fetch_assoc()) { 
                                    echo'<option value="'.$row['seotitle'].'">'.strip_tags($row['title']).'</option>';
                                  }echo'
                              </select>
                              <div class="input-group-prepend">
                                  <button class="btn btn-outline-secondary btn-kategori" type="button"><i class="fas fa-plus"></i></button>
                              </div>
                          </div>
                        </div>

                        <hr>
                        
                          <div class="form-group">
                            <label>Waktu</label>
                              <div class="input-group input-group-merge">
                              <input class="form-control" type="time" name="time" value="'.$time.'" required> 
                              </div>
                          </div>

                          <div class="form-group">
                            <label>Tanggal</label>
                            <div class="input-group input-group-merge">
                              <input type="text" class="form-control datepicker" name="date" value="'.tanggal_ind($date).'" placeholder="" required>
                                <div class="input-group-append">
                                  <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                </div>
                            </div>
                          </div>

                          <div class="form-group">
                            <label>Terbitkan</label><br>
                            <label class="custom-toggle">
                            <input type="checkbox" class="btn-active" name="active" value="Y" data-active="Y" checked>
                                <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                            </label>
                          </div>
    
                  </div>
                  <hr>
                  <div class="col-md-12">
                  <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
                  <a href="./'.$mod.'" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
                  </div>
          </form>';
        }else{
          hak_akses();
        }
        echo'
        </div>
    </div>
  </div>';


/** Update Artikel/User*/
break;
case 'update':
if(!empty($_GET['id'])){
  $id     =  anti_injection(epm_decode($_GET['id'])); 
  $query_artikel  ="SELECT * from artikel WHERE artikel_id='$id'";
  $result_artikel = $connection->query($query_artikel);

  echo'
  <div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item"><a href="./'.$mod.'">Artikel</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Ubah</li>
                </ol>
              </nav>
            </div>
            
          </div>
        </div>
      </div>
    </div>

<!-- Page content -->
<div class="container-fluid mt--6 mb-5">
  <div class="card-wrapper">
    <!-- Form controls -->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h3 class="mb-0">Ubah Artikel</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
      if($result_artikel->num_rows > 0){
        $data_artikel  = $result_artikel->fetch_assoc();
        if(strip_tags($data_artikel['foto']) ==NULL){
          $imageuploadwrap = 'display:block';
          $display_none ='display:none';
        }else{
            $imageuploadwrap = 'display:none';
            $display_none = 'display:block';    
        }
        
        if($data_artikel['active'] =='Y'){
          $active = '<label class="custom-toggle">
          <input type="checkbox" class="btn-active" name="active" data-active="Y" value="Y" checked>
              <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
        </label>';
        }else{
            $active = '<label class="custom-toggle">
            <input type="checkbox" class="btn-active" name="active" value="Y">
            <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
          </label>';
        }

        if($data_role['modifikasi']=='Y'){
        echo'
        <form class="form-update" role="form" method="post" action="#" autocomplete="off">
          <input type="hidden" class="d-none" name="id" value="'.epm_encode($data_artikel['artikel_id']).'" required>
                <div class="row">
                  <div class="col-md-8">
                    
                        <div class="form-group">
                          <label class="form-control-label">Judul</label>
                          <input type="text" class="form-control" name="judul" value="'.strip_tags($data_artikel['judul']).'" required>
                        </div>

                        <div class="form-group">
                          <label class="form-control-label">Deskripsi</label>
                          <textarea class="form-control swEditorText"  name="deskripsi" style="height:350px;">'.$data_artikel['deskripsi'].'</textarea>
                        </div>

                  </div>

                    <div class="col-md-4">
                      
                        <div class="form-group">
                          <label>Thumbnail</label>
                          <div class="file-upload">
                              <div class="image-upload-wrap"  style="'.$imageuploadwrap.'">
                                <input class="file-upload-input fileInput" type="file" name="foto" onchange="readURL(this);" accept="image/*">
                                  <div class="drag-text">
                                    <i class="lni lni-cloud-upload"></i>
                                    <h3>Drag and drop files here</h3>
                                  </div>
                              </div>
                                <div class="file-upload-content" style="'.$display_none.'">';
                                if(!$data_artikel['foto']== NULL && !file_exists('../../../sw-content/artikel/'.$data_artikel['foto'].'')){
                                  echo'<img src="../sw-content/artikel/'.strip_tags($data_artikel['foto']).'" class="file-upload-image" height="150">';
                                  }else{
                                    echo'<img class="file-upload-image" src="sw-assets/img/media.png" alt="Upload" height="70">';
                                  }
                                  echo'
                                    <div class="image-title-wrap">
                                      <button type="button" onclick="removeUpload()" class="btn btn-danger btn-sm"><i class="fas fa-undo"></i> Ubah<span class="image-title"></span></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                        <label class="form-control-label">Kategori</label>
                          <div class="input-group">
                              <select class="form-control kategori" name="kategori" required>
                                  <option value="">- Pilih -</option>';
                                  $query="SELECT * from kategori order by title ASC";
                                  $result = $connection->query($query);
                                  while($row = $result->fetch_assoc()) {
                                    if($data_artikel['kategori'] == $row['seotitle']) {
                                    echo'<option value="'.$row['seotitle'].'" selected>'.strip_tags($row['title']).'</option>';
                                    }else{
                                      echo'<option value="'.$row['seotitle'].'">'.strip_tags($row['title']).'</option>';
                                    }
                                  }echo'
                              </select>
                              <div class="input-group-prepend">
                                  <button class="btn btn-outline-secondary btn-kategori" type="button"><i class="fas fa-plus"></i></button>
                              </div>
                          </div>
                        </div>

                        <hr>
                        
                          <div class="form-group">
                            <label>Waktu</label>
                              <div class="input-group input-group-merge">
                              <input class="form-control" type="time" name="time" value="'.$data_artikel['time'].'" required> 
                              </div>
                          </div>

                          <div class="form-group">
                            <label>Tanggal</label>
                            <div class="input-group input-group-merge">
                              <input type="text" class="form-control datepicker" name="date" value="'.tanggal_ind($data_artikel['date']).'" placeholder="" required>
                                <div class="input-group-append">
                                  <span class="input-group-text"><i class="far fa-calendar-alt"></i></span>
                                </div>
                            </div>
                          </div>

                          <div class="form-group">
                            <label>Publish</label><br>
                                '.$active.'
                          </div>
    
                  </div>
                  <hr>
                  <div class="col-md-12">
                  <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
                  <a href="./'.$mod.'" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
                  </div>

          </form>';
        }else{
          hak_akses();
        }
          }else{
            theme_404();
          }
    }
      echo'
      </div>
    </div>
  </div>';
break;
}
}else{
  theme_404();
}
}?>

  