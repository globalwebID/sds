<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:./404');
}else{
  require_once'../../../sw-library/sw-config.php';
  require_once'../../../sw-library/sw-function.php';
  require_once'../../oauth/user.php';


switch (@$_GET['action']){
case 'data-blog':

if (empty($_GET['search']) AND empty($_GET['kategori'])){ 
  $filter ='';
} else { 
  if(empty($_GET['kategori'])){
    $search = htmlentities($_GET['search']);
    $filter = "AND judul like '%$search%'";
  }else{
    $kategori = htmlentities($_GET['kategori']);
    $search = htmlentities($_GET['search']);
    $filter = "AND kategori='$kategori' AND judul like '%$search%'";
  }
}

$query_artikel="SELECT * FROM artikel WHERE active='Y' $filter ORDER BY artikel_id DESC LIMIT 8";
$result_artikel = $connection->query($query_artikel);
if($result_artikel->num_rows > 0){
    while ($data_artikel = $result_artikel->fetch_assoc()){
      $artikel_id = anti_injection($data_artikel['artikel_id']);
      $judul = strip_tags($data_artikel['judul']);
      if(strlen($judul ) >30)$judul= substr($judul,0,30).'..';
        echo'
          <div class="col-6 col-md-6 col-lg-3">
            <div class="card border-0 mb-4 overflow-hidden">
              <div class="card-body h-150 position-relative">
                <a href="./blog-'.strip_tags($data_artikel['artikel_id']).'-'.strip_tags($data_artikel['domain']).'" class="background" data-toggle="tooltip" data-placement="top" title="'.strip_tags(ucfirst($data_artikel['judul'])).'">';
                    if(file_exists('../../../sw-content/artikel/'.($data_artikel['foto']??'image.jpg').'')){
                        echo'<img src="../sw-content/artikel/'.$data_artikel['foto'].'" height="150">';
                    }else{
                        echo'<img src="../sw-content/thumbnail.jpg" height="150">';
                    }
              echo' 
                  </a>
              </div>
              <div class="card-body blog-title">
                  <p class="mb-0"><small class="text-secondary">'.$data_artikel['date'].'</small></p>
                  <a href="./blog-'.strip_tags($data_artikel['artikel_id']).'-'.strip_tags($data_artikel['domain']).'" title="'.strip_tags(ucfirst($data_artikel['judul'])).'">
                      <p class="mb-0">'.$judul.'</p>
                  </a>
              </div>
          </div>
    </div>';
  }     
echo'<div class="show-blog" style="position-relative">
      <div class="text-center show_more_main'.$artikel_id.' mt-4">
          <button data-id="'.$artikel_id.'" class="btn btn-light rounded load-more">Show more</button>
      </div>
    </div>';

}else{
  echo'
  <div class="col-12">
    <div class="alert alert-secondary mt-3">Saat ini, data informasi belum tersedia atau masih kosong!!</div>
  </div>';
}

/** Moad More Blog */
break;
case 'data-blog-load':

if (empty($_GET['search']) AND empty($_GET['kategori'])){ 
  $filter ='';
} else { 
  if(empty($_GET['kategori'])){
    $search = htmlentities($_GET['search']);
    $filter = "AND judul like '%$search%'";
  }else{
    $kategori = htmlentities($_GET['kategori']);
    $search = htmlentities($_GET['search']);
    $filter = "AND kategori='$kategori' AND judul like '%$search%'";
  }
}

$id = anti_injection($_POST['id']);

$query_count    ="SELECT COUNT(artikel_id) AS total FROM artikel WHERE artikel_id < $id $filter ORDER BY artikel_id DESC";
$result_count   = $connection->query($query_count);
$data_count     = $result_count->fetch_assoc();
$totalRowCount  = $data_count['total'];

$showLimit = 8;
$query_artikel="SELECT * FROM artikel WHERE active='Y' AND artikel_id < $id $filter ORDER BY artikel_id DESC LIMIT $showLimit";
$result_artikel = $connection->query($query_artikel);
if($result_artikel->num_rows > 0){
      while ($data_artikel = $result_artikel->fetch_assoc()){
        $artikel_id = anti_injection($data_artikel['artikel_id']);
        $judul = strip_tags($data_artikel['judul']);
        if(strlen($judul ) >30)$judul= substr($judul,0,30).'..';
       echo'
          <div class="col-6 col-md-6 col-lg-3">
            <div class="card border-0 mb-4 overflow-hidden">
              <div class="card-body h-150 position-relative">
                <a href="./blog-'.strip_tags($data_artikel['artikel_id']).'-'.strip_tags($data_artikel['domain']).'" class="background" data-toggle="tooltip" data-placement="top" title="'.strip_tags(ucfirst($data_artikel['judul'])).'">';
                    if(file_exists('../../../sw-content/artikel/'.($data_artikel['foto']??'image.jpg').'')){
                        echo'<img src="../sw-content/artikel/'.$data_artikel['foto'].'" height="150">';
                    }else{
                        echo'<img src="../sw-content/thumbnail.jpg" height="150">';
                    }
              echo' 
                  </a>
              </div>
              <div class="card-body blog-title">
                  <p class="mb-0"><small class="text-secondary">'.$data_artikel['date'].'</small></p>
                  <a href="./blog-'.strip_tags($data_artikel['artikel_id']).'-'.strip_tags($data_artikel['domain']).'" title="'.strip_tags(ucfirst($data_artikel['judul'])).'">
                      <p class="mb-0">'.$judul.'</p>
                  </a>
              </div>
          </div>
    </div>';
  } 

if($totalRowCount > $showLimit){
  echo'
  <div class="show-blog" style="position-relative">
    <div class="text-center show_more_main'.$izin_id.' mt-4">
        <button data-id="'.$izin_id.'" class="btn btn-light rounded load-more">Show more</button>
    </div>
  </div>';
  }

}else{
  echo'
  <div class="col-12">
    <div class="alert alert-secondary mt-3">Saat ini, data informasi sudah tidak ada!</div>
  </div>';
}

break;
  }
}