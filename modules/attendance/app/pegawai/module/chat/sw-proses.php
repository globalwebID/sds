<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
  echo'Not Found';
}else{
require_once'../../../sw-library/sw-config.php';
  require_once'../../../sw-library/sw-function.php';
  require_once'../../oauth/user.php';

switch (@$_GET['action']){
case 'frendlist':

if(htmlentities($_GET['id'] =='1')){
  $query_frendlist = "SELECT chat_list.*, user.nama_lengkap, user.avatar,user.status FROM chat_list
  INNER JOIN user ON chat_list.user_id = user.user_id
  WHERE chat_list.pegawai_id= '{$data_user['pegawai_id']}'
  ORDER BY chat_list.datetime DESC";
  $result_frendlist = $connection->query($query_frendlist);
  if($result_frendlist->num_rows > 0){
    while($data_frendlist = $result_frendlist->fetch_assoc()){

      $query_pesan ="SELECT pesan,datetime FROM chat WHERE status_user='N' AND user_id='$data_frendlist[user_id]' AND pegawai_id='$data_user[pegawai_id]' ORDER BY chat_id DESC LIMIT 1";
      $result_pesan = $connection->query($query_pesan);
      if($result_pesan->num_rows > 0) {
        $data_pesan = $result_pesan->fetch_assoc();

        $status_pesan =''.substr($data_pesan['pesan'], 0,20).' '.time_since(strtotime($data_pesan['datetime'])).'';
        $status = '<div class="status available"></div>';
      }else{
        $status_pesan =''.time_since(strtotime($data_frendlist['datetime'])).'';
        $status = '<div class="status inactive"></div>';
      }

      echo'
      <li class="get-open-cat" data-id="'.epm_encode($data_frendlist['user_id']).'">';
          if(file_exists('../../sw-content/avatar/'.($data_frendlist['avatar']??'-.png').'')){
              echo'<img src="../sw-content/avatar/'.$data_frendlist['avatar'].'" height="40">';
          }else{
              echo'<img src="../sw-content/avatar/avatar.jpg" height="40">';
          }     
        echo'
          <p>
            <strong>'.strip_tags($data_frendlist['nama_lengkap']??'-').'</strong>
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

  $query_frendlist = "SELECT user_id, nama_lengkap, avatar, tanggal_login, status 
                    FROM user 
                    WHERE nama_lengkap LIKE '%$search%'";
  $result_frendlist = $connection->query($query_frendlist);
  if($result_frendlist->num_rows > 0){
    while($data_frendlist = $result_frendlist->fetch_assoc()) {
      
      if($data_frendlist['status'] =='Online'){
        $status = '<div class="status available"></div>';
      }else{
        $status = '<div class="status inactive"></div>';
      }
    echo'
    <li class="add-chat" data-id="'.epm_encode($data_frendlist['user_id']).'">';
        if(file_exists('../../sw-content/avatar/'.($data_frendlist['avatar']??'-.png').'')){
            echo'<img src="../sw-content/avatar/'.$data_frendlist['avatar'].'" height="40">';
        }else{
            echo'<img src="../sw-content/avatar/avatar.jpg" height="40">';
        }     
      echo'
        <p>
          <strong>'.strip_tags($data_frendlist['nama_lengkap']??'-').'</strong>
          <span>'.time_since(strtotime($data_frendlist['tanggal_login'])).'</span>
        </p>
        '.$status.'
    </li>';
    }
  }else{
    echo'<div class="text-center mt-4">Data Pengajar tidak ditemukan</div>';
  }
  

break;
case 'add-frendlist':
if (isset($_POST['id'])) {
    $id = anti_injection(epm_decode($_POST['id']));

    $query_frendlist = "SELECT user_id FROM chat_list WHERE user_id='$id'";
    $result_frendlist = $connection->query($query_frendlist);
    if($result_frendlist->num_rows > 0){
      /** Update jika sudah ada di daftar */
      $update="UPDATE chat_list SET datetime='$date $time_sekarang' WHERE pegawai_id='$data_user[pegawai_id]' AND user_id='$id'"; 
      if($connection->query($update) === false) { 
        echo'Sepertinya Sistem Kami sedang error!';
        die($connection->error.__LINE__); 
      } else{
        echo'success';
      }
    }else{
      /** Tambah jika belum ada di daftar */
      $add ="INSERT INTO chat_list(user_id,
            pegawai_id,
            datetime)
            values('$id',
            '$data_user[pegawai_id]',
            '$date $time_sekarang')";
      if($connection->query($add) === false) { 
        echo'Sepertinya Sistem Kami sedang error!';
        die($connection->error.__LINE__); 
      } else{
        echo'success';
      }
    }
}


/** Buka Live Chat  */
break;
case 'get-open-cat':
if (isset($_POST['id'])) {
$id = anti_injection(epm_decode($_POST['id']));
$query_frendlist = "SELECT chat_list.*,user.nama_lengkap,user.avatar,user.tanggal_login FROM chat_list
INNER JOIN user ON chat_list.user_id = user.user_id WHERE chat_list.user_id='$id' AND chat_list.pegawai_id='$data_user[pegawai_id]'";
$result_frendlist = $connection->query($query_frendlist);
  if($result_frendlist->num_rows > 0){
    $data_frendlist = $result_frendlist->fetch_assoc();
    
    $data['pegawai_id']     = epm_encode($data_frendlist["user_id"]??'-');
    $data['nama_lengkap']   = anti_injection($data_frendlist["nama_lengkap"]??'-');
    $data['time']           = time_since(strtotime($data_frendlist["tanggal_login"]??'-'));
    
    if(file_exists('../../sw-content/avatar/'.($data_frendlist["avatar"]??'-.jpg').'')){
      $data['avatar']       = '../sw-content/avatar/'.($data_frendlist["avatar"]??'-.jpg').'';
    }else{
      $data['avatar']       = '../sw-content/avatar/avatar.jpg';
    } 
    echo json_encode($data);
  }else{
    echo'Data tidak ditemukan';
  }

}

/** Load data message live chate */
break;
case 'message';

if (isset($_POST['id'])) {
  $user_id = anti_injection(epm_decode($_POST['id']));
  $query_pesan ="SELECT * FROM chat WHERE pegawai_id='$data_user[pegawai_id]' AND user_id='$user_id' ORDER BY chat_id ASC";
  $result_pesan = $connection->query($query_pesan);
  if($result_pesan->num_rows > 0) {
    while($data_pesan = $result_pesan->fetch_assoc()){

      $query_frendlist = "SELECT nama_lengkap,avatar FROM user WHERE user_id='$data_pesan[user_id]'";
      $result_frendlist = $connection->query($query_frendlist);
      $data_frendlist = $result_frendlist->fetch_assoc();

        if($data_pesan['tujuan'] =='user'){

          if($data_pesan['status_pegawai'] =='Y'){
            $status ='<span class="material-icons corner active">done_all</span>';
          }else{
            $status ='<span class="material-icons corner">done_all</span>';
          }

        echo'
          <div class="message right">';
            if(file_exists('../../sw-content/avatar/'.($data_user['avatar']??'-.png').'')){
              echo'<img src="../sw-content/avatar/'.$data_user['avatar'].'" height="30">';
            }else{
                echo'<img src="../sw-content/avatar/avatar.jpg" height="30">';
            } 
            echo' 
            <div class="bubble">
                '.strip_tags($data_pesan['pesan']).'
                <div class="corner"></div>
                <span>'.facebook_time_ago($data_pesan['datetime']).'</span>
                '.$status.'
            </div>
          </div>';
      }else{
          $update="UPDATE chat SET status_user='Y' WHERE user_id='$data_pesan[user_id]' AND chat_id='$data_pesan[chat_id]'"; 
          $connection->query($update);
        echo'
        <div class="message left">';
          if(file_exists('../../sw-content/avatar/'.($data_frendlist['avatar']??'-.png').'')){
            echo'<img src="../sw-content/avatar/'.$data_frendlist['avatar'].'" height="30">';
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
    }
  }else{
    echo'stoploop';
  }
}



/** Add */
break;
case 'add':
if (empty($_POST['pegawai'])) {
    $error[] = 'Tidak ada User yang dipilih';
} else {
    $user_id = anti_injection(epm_decode($_POST['pegawai']));
}

if (empty($_POST['pesan'])) {
    $error[] = 'Pesan tidak boleh kosong';
} else {
    $pesan = anti_injection($_POST['pesan']);
}

if (empty($error)) {
    $add = "INSERT INTO chat (user_id, 
            pegawai_id, 
            pesan, 
            datetime, 
            tujuan, 
            status_user, 
            status_pegawai) 
          VALUES ('$user_id', 
          '$data_user[pegawai_id]', 
          '$pesan', 
          '$date $time_sekarang', 
          'user', 
          '-', 
          'N')";
    if ($connection->query($add) === false) {
        die($connection->error.__LINE__); 
        echo 'Data tidak berhasil disimpan!';
    } else {
        echo 'success';
    }
} else {           
    foreach ($error as $key => $values) {            
        echo "$values\n";
    }
}

break;
  }
}