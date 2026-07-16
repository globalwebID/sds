<?PHP
if(!isset($_COOKIE['ADMIN_KEY'])){
  header('location:./login/');
  exit;
}
else{
  $query_user ="SELECT * FROM admin WHERE admin.admin_id='$current_user[admin_id]'";
  $result_user = $connection->query($query_user);
echo'
<div class="header bg-primary pb-6">
    <div class="container-fluid">
      <div class="header-body">
        <div class="row align-items-center py-4">
          <div class="col-lg-6 col-7">
            <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
              <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Profil</li>
              </ol>
            </nav>
          </div>
          
        </div>
      </div>
    </div>
  </div>';

  if($result_user->num_rows > 0){
    $data_user  = $result_user->fetch_assoc();
    echo'
  <!-- Page content -->
  <div class="container-fluid mt--6">
    <div class="row">
      <div class="col-xl-4 order-xl-2">
        <div class="card card-profile">
          <img src="./sw-assets/img/theme/img-1-1000x600.jpg" alt="Image placeholder" class="card-img-top">
          <div class="row justify-content-center">
            <div class="col-lg-3 order-lg-2">
              <div class="card-profile-image">
              <input type="file" class="upload foto" name="foto" accept=".jpg, .jpeg, ,gif, .png" capture="camera">';
              if($current_user['avatar']==''){
                echo'
                    <img class="rounded-circle img-140 view-image" src="./sw-assets/avatar/avatar.jpg">';
                }else{
                echo'
                    <img class="rounded-circle img-140 view-image" src="./sw-assets/avatar/'.$current_user['avatar'].'">';
                }
                echo'
                <span class="button">
                  <i class="fas fa-camera"></i>
                </span>
              </div>
            </div>
          </div>

          <div class="card-body pt-0 mt-6">
            
            <div class="text-center">
              <h5 class="h3">
              '.strip_tags($data_user['fullname']).'
              </h5>
    
              <div class="h5 font-weight-300">';
                if($data_user['status']=='Offline'){
                  echo'<span class="badge badge-danger">Offline</span>';
                }else{
                  echo'<span class="badge badge-info">Online</span>';
                }
              echo'
              </div>
            </div>
              
              <div class="mt-3">
                <ul class="list-group list-group-flush">
                  <li class="list-group-item">User Sejak: '.tgl_indo($data_user['registrasi_date']).'</li>
                  <li class="list-group-item">Terakhir Login: '.tgl_ind($data_user['tanggal_login']).'</li>
                  <li class="list-group-item">Ip: '.strip_tags($data_user['ip']).'</li>
                  <li class="list-group-item">Browser: '.strip_tags($data_user['browser']).'</li>
                </ul>
              </div>

          </div>
        </div>
       
      </div>
      <div class="col-xl-8 order-xl-1">
        
        <div class="card">
         
            <div class="pt-2 pl-2 mb-2">
            <ul class="nav nav-tabs custom-nav-tabs">
                <li class="nav-item">
                  <a class="nav-link" href="#1" onclick="loadUser(1);"><i class="fas fa-user-tie"></i> Profile</a>
                </li>

                <li class="nav-item">
                  <a class="nav-link" href="#2" onclick="loadUser(2);"><i class="fas fa-key"></i> Ubah Password</a>
                </li>

              </ul>
            </div>

          <div class="card-body load-data">
          </div>
    
    </div>
  </div>
</div>';}else{
  echo' <div class="container-fluid mt--6">
  <!-- Table -->
  <div class="row">
    <div class="col">
      <div class="card pb-6 pt-6">';
        theme_404();
      echo'</div>
        </div>
  </div>';
}

}?>

  