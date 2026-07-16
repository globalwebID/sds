<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='11' AND level_id='$current_user[level]'";
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
                  <li class="breadcrumb-item active" aria-current="page">Perngaturan</li>
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
          <div class="card pb-3">';
          if($data_role['lihat']=='Y' OR $data_role['modifikasi']=='Y'){
          echo'
            <!-- Card header -->
            <div class="pt-2 pl-2 mb-2">
              <ul class="nav nav-tabs custom-nav-tabs">
                <li class="nav-item">
                  <a class="nav-link text-primary" href="javascript:;" onclick="loadSetting(1);"><i class="ni ni-settings-gear-65"></i> Pengaturan Web</a>
                </li>

                <li class="nav-item">
                  <a class="nav-link text-primary" href="javascript:;" onclick="loadSetting(2);"><i class="fas fa-user-clock"></i> Pengaturan Absen</a>
                </li>

                <li class="nav-item">
                  <a class="nav-link text-primary" href="javascript:;" onclick="loadSetting(3);"><i class="fab fa-google"></i> Google & Gmail</a>
                </li>
               
                <li class="nav-item">
                  <a class="nav-link text-primary" href="javascript:;" onclick="loadSetting(4);"><i class="fab fa-whatsapp"></i> Api WhatsApp</a>
                </li>

                
                <li class="nav-item">
                  <a class="nav-link text-primary" href="javascript:;" onclick="loadSetting(6);"><i class="fas fa-cloud-download-alt"></i> Backup</a>
                </li>

              </ul>
            </div>
            
            <div class="card-body load-form">
            
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
                    <div class="modal-header">
                        <h5 class="modal-title">Modal Title <span class="modal-title-name text-info"></span></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body">

                        <div class="form-group password-show">
                            <label>Masukkan Password</label>
                            <input type="password" class="form-control password" name="password" required>
                        </div>
                        <div class="status"></div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-backup-download">Kirim Persetujuan</button>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    </div>
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

  