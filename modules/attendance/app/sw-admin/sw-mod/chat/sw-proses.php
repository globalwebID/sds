<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';


switch (@$_GET['action']){
case 'frendlist':

if(htmlentities($_GET['id'] =='1')){
  $query_frendlist = "SELECT chat_list.orangtua_id,orangtua.nama_lengkap,orangtua.avatar,orangtua.time,orangtua.status FROM chat_list
  INNER JOIN orangtua ON chat_list.orangtua_id = orangtua.orangtua_id WHERE chat_list.admin_id='$current_user[admin_id]' ORDER BY chat_list.datetime DESC";
  $result_frendlist = $connection->query($query_frendlist);
  if($result_frendlist->num_rows > 0){
    while($data_frendlist = $result_frendlist->fetch_assoc()){
      
      $query_pesan ="SELECT pesan,datetime FROM chat WHERE status_user='N' AND orangtua_id='$data_frendlist[orangtua_id]' AND admin_id='$current_user[admin_id]' ORDER BY chat_id DESC LIMIT 1";
      $result_pesan = $connection->query($query_pesan);
      if($result_pesan->num_rows > 0) {
        $data_pesan = $result_pesan->fetch_assoc();

        $status_pesan =''.substr($data_pesan['pesan'], 0,20).' '.facebook_time_ago($data_pesan['datetime']).'';
        $status = '<div class="status available"></div>';
      }else{
        $status_pesan =''.facebook_time_ago($data_frendlist['time']).'';
        $status = '<div class="status inactive"></div>';
      }

      echo'
      <li class="get-open-cat" data-id="'.epm_encode($data_frendlist['orangtua_id']).'">';
          if(file_exists('../../../sw-content/avatar/'.$data_frendlist['avatar'].'')){
              echo'<img src="../sw-content/avatar/'.$data_frendlist['avatar'].'" height="40">';
          }else{
              echo'<img src="../sw-content/avatar/avatar.jpg" height="40">';
          }     
        echo'
          <p>
            <strong>'.strip_tags($data_frendlist['nama_lengkap']).'</strong>
            <span>'.$status_pesan.'</span>
          </p>
          '.$status.'
      </li>';
    }
  }else{
    echo'<div class="text-center mt-4">Belum ada chat sama sekali</div>';
  }

}

/** Pencarian kontak Pertemanan*/
break;
case 'search-frendlist':
  if (isset($_POST['search'])) {
    $search = anti_injection($_POST['search']);
  }

  $query_frendlist = "SELECT orangtua_id,nama_lengkap,avatar,time,status FROM orangtua WHERE nama_lengkap like '%".$search."%'";
  $result_frendlist = $connection->query($query_frendlist);
  if($result_frendlist->num_rows > 0){
    while($data_frendlist = $result_frendlist->fetch_assoc()) {
      
      if($data_frendlist['status'] =='Online'){
        $status = '<div class="status available"></div>';
      }else{
        $status = '<div class="status inactive"></div>';
      }
    echo'
    <li class="add-chat" data-id="'.epm_encode($data_frendlist['orangtua_id']).'">';
        if(file_exists('../../../sw-content/avatar/'.$data_frendlist['avatar'].'')){
            echo'<img src="../sw-content/avatar/'.$data_frendlist['avatar'].'" height="40">';
        }else{
            echo'<img src="../sw-content/avatar/avatar.jpg" height="40">';
        }     
      echo'
        <p>
          <strong>'.strip_tags($data_frendlist['nama_lengkap']).'</strong>
          <span>'.facebook_time_ago($data_frendlist['time']).'</span>
        </p>
        '.$status.'
    </li>';
    }
  }else{
    echo'<div class="text-center mt-4">Data tidak ditemukan</div>';
  }
  

break;
case 'add-frendlist':
if (isset($_POST['id'])) {
  $id = epm_decode($_POST['id']);
}

$query_frendlist = "SELECT admin_id FROM chat_list WHERE orangtua_id='$id'";
$result_frendlist = $connection->query($query_frendlist);

if($result_frendlist->num_rows > 0){
  /** Update jika sudah ada di daftar */
  $update="UPDATE chat_list SET datetime='$date $time_sekarang' WHERE orangtua_id='$id' AND admin_id='$current_user[admin_id]'"; 
  if($connection->query($update) === false) { 
    echo'Sepertinya Sistem Kami sedang error!';
    die($connection->error.__LINE__); 
  } else{
    echo'success';
  }
}else{
  /** Tambah jika belum ada di daftaar */
  $add ="INSERT INTO chat_list(orangtua_id,
        admin_id,
        datetime) values('$id',
        '$current_user[admin_id]',
        '$date $time_sekarang')";
  if($connection->query($add) === false) { 
    echo'Sepertinya Sistem Kami sedang error!';
    die($connection->error.__LINE__); 
  } else{
    echo'success';
  }
}


/** Buka Live Chat  */
break;
case 'get-open-cat':
if (isset($_POST['id'])) {
  $id = epm_decode($_POST['id']);
}

$query_frendlist = "SELECT chat_list.orangtua_id,chat_list.admin_id,orangtua.nama_lengkap,orangtua.avatar,orangtua.time FROM chat_list
INNER JOIN orangtua ON chat_list.orangtua_id = orangtua.orangtua_id WHERE chat_list.orangtua_id='$id' AND chat_list.admin_id='$current_user[admin_id]'";
$result_frendlist = $connection->query($query_frendlist);
  if($result_frendlist->num_rows > 0){
    $data_frendlist = $result_frendlist->fetch_assoc();
    
    $data['orangtua_id']        = anti_injection($data_frendlist["orangtua_id"]);
    $data['nama_lengkap']   = htmlentities($data_frendlist["nama_lengkap"]);
    $data['time']           = facebook_time_ago($data_frendlist["time"]);
    
    if(file_exists('../../../sw-content/avatar/'.strip_tags($data_frendlist["avatar"]).'')){
      $data['avatar']       = '../sw-content/avatar/'.strip_tags($data_frendlist["avatar"]).'';
    }else{
      $data['avatar']       = '../sw-content/avatar/avatar.jpg';
    } 
  echo json_encode($data);
  }else{
    echo'Data tidak ditemukan';
  }


/** Load data message live chate */
break;
case 'message';
if (isset($_POST['id'])) {
  $id = anti_injection($_POST['id']);
}

$query_pesan ="SELECT chat.*,orangtua.nama_lengkap,orangtua.avatar FROM chat 
INNER JOIN orangtua ON orangtua.orangtua_id = chat.orangtua_id WHERE chat.orangtua_id='$id' AND chat.admin_id='$current_user[admin_id]' ORDER BY chat.chat_id ASC";
$result_pesan = $connection->query($query_pesan);
if($result_pesan->num_rows > 0) {
  while($data_pesan = $result_pesan->fetch_assoc()){

    $query_frendlist = "SELECT fullname,avatar FROM admin WHERE admin_id='$current_user[admin_id]'";
    $result_frendlist = $connection->query($query_frendlist);
    $data_frendlist = $result_frendlist->fetch_assoc();

      if($data_pesan['status_admin'] =='Y'){
        $status ='<span class="fas fa-check-double corner active"></span>';
      }else{
        $status ='<span class="fas fa-check-double corner"></span>';
      }

    if($data_pesan['status'] =='user'){
      $update="UPDATE chat SET status_user='Y' WHERE chat_id='$data_pesan[chat_id]' AND admin_id='$current_user[admin_id]'"; 
      $connection->query($update);
      echo'
        <div class="message left">';
          if(file_exists('../../sw-content/avatar/'.$data_pesan['avatar'].'')){
            echo'<img src="../sw-content/avatar/'.$data_pesam['avatar'].'" height="30">';
          }else{
              echo'<img src="../sw-content/avatar/avatar.jpg" height="30">';
          } 
          echo' 
          <div class="bubble">
              '.strip_tags($data_pesan['pesan']).'
              <span>'.facebook_time_ago($data_pesan['datetime']).'</span>
          </div>
        </div>';
      }
      else{
      echo'
      <div class="message right">';
        if(file_exists('../../sw-content/avatar/'.$data_frendlist['avatar'].'')){
          echo'<img src="../sw-content/avatar/'.$data_frendlist['avatar'].'" height="30">';
        }else{
            echo'<img src="../sw-content/avatar/avatar.jpg" height="30">';
        } 
        echo' 
        <div class="bubble">
            '.strip_tags($data_pesan['pesan']).'
            <span>'.facebook_time_ago($data_pesan['datetime']).'</span>
            '.$status.'
        </div>
      </div>';
      }
  }
}else{
  echo'stoploop';
}



/** Add */
break;
case 'add':
if (empty($_POST['parent_id'])) {
  $error[] = 'Tidak ada User yg dipilih';
} else {
  $parent_id = anti_injection($_POST['parent_id']);
}

if (empty($_POST['pesan'])) {
  $error[] = 'Pesan tidak boleh kosong';
} else {
  $pesan = anti_injection($_POST['pesan']);
}

if (empty($error)) {
  $add ="INSERT INTO chat(orangtua_id,
              admin_id,
              pesan,
              datetime,
              status,
              status_user,
              status_parent,
              status_admin) values('$parent_id',
              '$current_user[admin_id]',
              '$pesan',
              '$date $time_sekarang',
              'admin',
              '-',
              '-',
              'N')";
  if($connection->query($add) === false) { 
      die($connection->error.__LINE__); 
      echo'Data tidak berhasil disimpan!';
  } else{
      echo'success';
  }
}else{           
    foreach ($error as $key => $values) {            
      echo"$values\n";
    }
}


  break;
  }
}