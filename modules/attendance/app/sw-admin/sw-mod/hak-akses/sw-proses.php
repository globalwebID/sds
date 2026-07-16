<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

switch (@$_GET['action']){
  case 'data':
  $id = htmlentities($_GET['id']);
  echo'
  <div class="table-responsive">
      <table class="table datatable table-inverse table-hover" style="vertical-align:middle">
          <thead>
              <tr>
                  <th>Modul</th>
                  <th class="text-center">Lihat</th>
                  <th class="text-center">Tambah/Edit</th>
                  <th class="text-center">Hapus</th>
                  <th class="text-center">Aksi</th>
              </tr>
          </thead>
          <tbody>';
          $query_role = "SELECT role.*, modul.modul_nama FROM role
          LEFT JOIN modul
          ON role.modul_id = modul.modul_id WHERE role.level_id='$id' order by role.role_id ASC";
          $result_role  =  $connection->query($query_role);
          if($result_role->num_rows > 0){
            while ($data_role = $result_role->fetch_assoc()){
              /** Lihat */
                if($data_role['lihat']=='Y'){
                  if($data_role['level_id'] == $current_user['level']){
                    $lihat = '<label class="custom-toggle" style="display:inline-block">
                    <input type="checkbox" class="btn-error" disabled checked>
                        <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                    </label>';
                  }else{
                    $lihat = '<label class="custom-toggle" style="display:inline-block">
                    <input type="checkbox" class="btn-active active'.$data_role['role_id'].' lihat" data-id="'.$data_role['role_id'].'" data-active="N" checked>
                        <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                    </label>';
                  }
                 
                }else{
                  $lihat = '<label class="custom-toggle" style="display:inline-block">
                  <input type="checkbox" class="btn-active active'.$data_role['role_id'].' lihat" data-id="'.$data_role['role_id'].'" data-active="Y">
                      <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                </label>';
                }
  
                /** Modifikasi */
                if($data_role['modifikasi']=='Y'){
                  $modifikasi = '<label class="custom-toggle" style="display:inline-block">
                  <input type="checkbox" class="btn-active active'.$data_role['role_id'].' modifikasi" data-id="'.$data_role['role_id'].'" data-active="N" checked>
                      <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                </label>';
                }else{
                  $modifikasi = '<label class="custom-toggle" style="display:inline-block">
                  <input type="checkbox" class="btn-active active'.$data_role['role_id'].' modifikasi" data-id="'.$data_role['role_id'].'" data-active="Y">
                      <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                </label>';
                }
  
                /** Hapus */
                if($data_role['hapus']=='Y'){
                  $hapus = '<label class="custom-toggle" style="display:inline-block">
                  <input type="checkbox" class="btn-active active'.$data_role['role_id'].' hapus" data-id="'.$data_role['role_id'].'" data-active="N" checked>
                      <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                </label>';
                }else{
                  $hapus = '<label class="custom-toggle" style="display:inline-block">
                  <input type="checkbox" class="btn-active active'.$data_role['role_id'].' hapus" data-id="'.$data_role['role_id'].'" data-active="Y">
                      <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                </label>';
                }
                
                
              echo'
              <tr>
                  <td class="text-info">'.strip_tags($data_role['modul_nama']).'</td>
                  <td class="text-center">'.$lihat.'</td>
                  <td class="text-center">'.$modifikasi.'</td>
                  <td class="text-center">'.$hapus.'</td>
                  <td class="text-center"><a href="javascript:void(0)" class="table-action table-action-delete btn-tooltip btn-delete" data-toggle="tooltip" data-placement="right" title="Hapus" data-id="'.convert('encrypt', $data_role['role_id']).'">
            <i class="fas fa-trash"></i>
            </a></td>
              </tr>';
            }}
            echo'
          </tbody>
      </table>
  </div>
  
  <script>
  $(".datatable").dataTable({
    "iDisplayLength":35,
    "aLengthMenu": [[35, 40, 50, -1], [35, 40, 50, "All"]],
    language: {
      paginate: {
        previous: "<",
        next: ">"
      }
    },
  });
  </script>';


/** Tambah Baru */
  break;
  case 'add':
  $error = array();
      if (empty($_POST['level'])) {
          $error[] = 'Level tidak boleh kosong';
        } else {
          $level = anti_injection($_POST['level']);
      }
  
      if (empty($_POST['modul_id'])) {
        $error[] = 'Modul/Menu tidak boleh kosong';
      } else {
        $modul_id = anti_injection($_POST['modul_id']);
      }
  
    if (empty($error)) {
      $query="SELECT role_id from role where level_id='$level' AND modul_id='$modul_id'";
      $result= $connection->query($query);
      if(!$result ->num_rows >0){
          $add ="INSERT INTO role(level_id,
                      modul_id,
                      lihat,
                      modifikasi,
                      hapus) values('$level',
                      '$modul_id',
                      'N',
                      'N',
                      'N')";
          if($connection->query($add) === false) { 
                die($connection->error.__LINE__); 
                echo'Data tidak berhasil disimpan!';
            } else{
                echo'success';
            }
     }else{
      echo'Sepertinya Modul/menu ini sudah ada!';
    }
  }else{           
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
  }
  
  
/** Set Active Hak Akses */
break;
case 'lihat':
if(isset($_POST['id'])){
  $id = htmlentities($_POST['id']);
  $active = htmlentities($_POST['active']);
  $update="UPDATE role SET lihat='$active' WHERE role_id='$id'";
  if($connection->query($update) === false) { 
    echo 'error';
    die($connection->error.__LINE__); 
  }else{
    echo'success';
  }
}
  
break;
case 'modifikasi':
if(isset($_POST['id'])){
  $id = htmlentities($_POST['id']);
  $active = htmlentities($_POST['active']);
  $update="UPDATE role SET modifikasi='$active' WHERE role_id='$id'";
  if($connection->query($update) === false) { 
    echo 'error';
    die($connection->error.__LINE__); 
  }else{
    echo'success';
  }
}


break;
case 'hapus':
if(isset($_POST['id'])){
  $id = htmlentities($_POST['id']);
  $active = htmlentities($_POST['active']);
  $update="UPDATE role SET hapus='$active' WHERE role_id='$id'";
  if($connection->query($update) === false) { 
    echo 'error';
    die($connection->error.__LINE__); 
  }else{
    echo'success';
  }
}


break;
case'delete':
if(isset($_POST['id'])){
  $id       = htmlentities(convert('decrypt', $_POST['id']??'-'));
  $deleted = "DELETE FROM role WHERE role_id='$id'";
  if($connection->query($deleted) === true) {
    echo'success';
  } else { 
    echo'Data tidak berhasil dihapus.!';
    die($connection->error.__LINE__);
  }
}


break;
}}