<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='14' AND level_id='$current_user[level]'";
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
              <li class="breadcrumb-item"><a href="./admin">Admin</a></li>
              <li class="breadcrumb-item active" aria-current="page">Hak Akses</li>
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
            <div class="pt-2 pl-2 mb-2">
                <ul class="nav nav-tabs custom-nav-tabs">';
                  $query_level ="SELECT * FROM level ORDER BY level_id ASC";
                    $result_level = $connection->query($query_level);
                    while ($data_level = $result_level->fetch_assoc()){
                    echo'
                        <li class="nav-item">
                            <a href="javascript:void(0);" onclick="loadData('.$data_level['level_id'].');" data-name="'.strip_tags($data_level['level_nama']).'" class="nav-link text-uppercase btn-tab"  data-toggle="tab" aria-controls="home" aria-selected="true">'.strip_tags($data_level['level_nama']).'</a>
                        </li>';
                    }
                    echo'
              </ul>
            </div>
              
            <div class="card-header">
              <h3 class="mt-2 mb-0 text-left float-left title-header">Admin</h3>
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
            </div>
            
                <div class="card-body load-data">
                
                </div>';
                if($data_role['modifikasi']=='Y'){
                echo'
                <div  class="modal fade modal-add" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                <form class="form-add" role="form" action="#">
                                    <input type="hidden" class="form-control level d-none" name="level" readonly>
                                    <div class="modal-header">
                                        <h5 class="modal-title">Modal Title</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>Pilih Modul/Menu</label>
                                            <select class="form-control" name="modul_id" required>
                                                <option value="">-- Pilih modul --</option>';
                                                $query_module ="SELECT * FROM modul ORDER BY modul_id ASC";
                                                $result_module = $connection->query($query_module);
                                                while ($data_module = $result_module->fetch_assoc()){
                                                    echo'<option value="'.$data_module['modul_id'].'">'.$data_module['modul_nama'].'</option>';
                                                }
                                            echo'
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

  