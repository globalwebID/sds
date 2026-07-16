<?php session_start();
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
require_once '../../../sw-library/sw-config.php';
require_once '../../../sw-library/sw-function.php';
require_once '../../login/user.php';

switch (@$_GET['action']){
case 'filtering':

if(!$_POST['lokasi'] =='' AND !$_POST['posisi'] =='' AND !$_POST['tanggal'] =='') {
  $tanggal = date('Y-m-d', strtotime($_POST['tanggal']));
  $lokasi = anti_injection($_POST['lokasi']);
  $posisi = anti_injection($_POST['posisi']);
  $filter ="user.lokasi_id='$lokasi' AND user.posisi_id='$posisi' AND absen.tanggal='$tanggal'";

}elseif(!$_POST['lokasi'] =='' AND !$_POST['tanggal'] ==''){
  $tanggal = date('Y-m-d', strtotime($_POST['tanggal']));
  $lokasi = anti_injection($_POST['lokasi']);
  $filter ="user.lokasi_id='$lokasi' AND absen.tanggal='$tanggal'";

}elseif(!$_POST['posisi'] =='' AND !$_POST['tanggal'] ==''){
  $tanggal = date('Y-m-d', strtotime($_POST['tanggal']));
  $posisi = anti_injection($_POST['posisi']);
  $filter ="user.posisi_id='$posisi' AND absen.tanggal='$tanggal'";

}elseif(!$_POST['tanggal'] ==''){
  $tanggal = date('Y-m-d', strtotime($_POST['tanggal']));
  $filter ="absen.tanggal='$tanggal'";

}else{
  $tanggal = $date;
  $filter = "absen.tanggal='$date'";
}


$limit=30; 
$no = 0;
if(isset($_GET['halaman'])){
$halaman = mysqli_real_escape_string($connection,$_GET['halaman']);}
else{$halaman = 1;} $offset = ($halaman - 1) * $limit;

$query_absen ="SELECT absen.user_id,absen.tanggal,absen.absen_in,absen.absen_out,absen.foto_in,absen.foto_out,absen.status_masuk,absen.status_pulang,absen.latitude_longtitude_in,absen.latitude_longtitude_out,absen.kehadiran,absen.tipe,user.nip,user.nama_lengkap,posisi.posisi_nama FROM absen
  INNER JOIN user ON absen.user_id = user.user_id
  INNER JOIN posisi ON user.posisi_id = posisi.posisi_id WHERE $filter ORDER BY absen.absen_in ASC LIMIT $offset, $limit";
  $result_absen = $connection->query($query_absen);
  
  echo'
    <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
      <thead class="thead-light">
        <tr>
          <th class="text-center" width="5">No</th>
          <th>NIP</th>
          <th>Nama</th>
          <th>Posisi</th>
          <th class="text-center">Foto Masuk</th>
          <th>Asben Masuk</th>
          <th class="text-center">Foto Pulang</th>
          <th>Absen Pulang</th>
          <th>Lokasi</th>
        </tr>
      </thead>
      <tbody>';
      if($result_absen->num_rows > 0){
        while($data_absen = $result_absen->fetch_assoc()){$no++;

          if($data_absen['status_masuk']=='Tepat Waktu'){
            $status_masuk ='<span class="badge badge-success">'.$data_absen['status_masuk'].'</span>';
          }else{
            $status_masuk ='<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>';
          }

          if($data_absen['status_pulang']=='Tepat Waktu'){
              $status_pulang ='<span class="badge badge-success">'.$data_absen['status_pulang'].'</span>';
          }else{
              $status_pulang ='<span class="badge badge-danger">'.$data_absen['status_pulang'].'</span>';
          }

          if($data_absen['latitude_longtitude_in'] =='-' OR $data_absen['latitude_longtitude_in'] ==''){
            $map_in ='';
          }else{
            $map_in = '<a href="https://www.google.com/maps/place/'.$data_absen['latitude_longtitude_in'].'" class="btn btn-success btn-sm" target="_blank"><i class="fas fa-map-marker-alt"></i> IN</a>';
          }
      
          if($data_absen['latitude_longtitude_out'] =='-' OR $data_absen['latitude_longtitude_out'] ==''){
            $map_out ='';
          }else{
            $map_out = '<a href="https://www.google.com/maps/place/'.$data_absen['latitude_longtitude_out'].'" class="btn btn-info btn-sm" target="_blank"><i class="fas fa-map-marker-alt"></i> OUT</a>';
          }
          if($data_absen['absen_out'] =='00:00:00'){
            $absen_out = '<span class="text-danger">Belum absen</span>';
          }else{
            $absen_out = $data_absen['absen_out'];
          }

          if($data_absen['tipe']=='Foto'){
            if(file_exists('../../../sw-content/absen/'.strip_tags($data_absen['foto_in']).'')){
              $foto_masuk = '<a href="../sw-content/absen/'.strip_tags($data_absen['foto_in']).'" class="open-popup-link"><img src="../sw-content/absen/'.strip_tags($data_absen['foto_in']).'" class="imaged w100 rounded" height="50"></a>';
            }else{
              $foto_masuk = '<img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">';
            }
      
            if(file_exists('../../../sw-content/absen/'.strip_tags($data_absen['foto_out']).'')){
              if(!$data_absen['foto_out'] ==''){
                $foto_pulang = '<a href="../sw-content/absen/'.strip_tags($data_absen['foto_out']).'" class="open-popup-link">
                  <img src="../sw-content/absen/'.strip_tags($data_absen['foto_out']).'" class="imaged w100 rounded" height="50"></a>';
              }else{
                $foto_pulang = '<img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">';
              }
            }else{
              $foto_pulang = '<img src="../sw-content/avatar/avatar.jpg" class="imaged w100 rounded" height="50">';
            }
      
          }else{
            $foto_masuk ='-';
            $foto_pulang ='-';
          }

          echo'
          <tr>
            <td class="text-center">'.$no.'</td>
            <td>'.strip_tags($data_absen['nip']).'</td>
            <td>'.strip_tags($data_absen['nama_lengkap']).'</td>
            <td>'.strip_tags($data_absen['posisi_nama']).'</td>
            <td class="text-center">'.$foto_masuk.'</td>
            <td>'.$data_absen['absen_in'].' '.$status_masuk.'</td>
            <td class="text-center">'.$foto_pulang.'</td>
            <td>'.$absen_out.' '.$status_pulang.'</td>
            <td>'.$map_in.''.$map_out.'</td>
          </tr>';
      }}
      echo'
      </tbody>
    </table>

    <nav>
    <ul class="pagination justify-content-center">';
    $query_pagination = "SELECT COUNT(absen.absen_id) AS jumData FROM absen
    INNER JOIN user ON absen.user_id = user.user_id
    INNER JOIN posisi ON user.posisi_id = posisi.posisi_id WHERE $filter";
    $result_pagination = $connection->query($query_pagination);
      $data  = $result_pagination->fetch_assoc();
      $jumData = $data['jumData'];
      $jumPage = ceil($jumData/$limit);
          //menampilkan link << Previou
          if ($halaman > 1){echo '<li class="page-item"><a class="page-link btn-pagination" href="javascript:void(0);" data-id="'.($halaman-1).'">«</a></li>';}
          //menampilkan urutan paging
              for($i = 1; $i <= $jumPage; $i++){
          //mengurutkan agar yang tampil i+3 dan i-3
              if ((($i >= $halaman - 1) && ($i <= $halaman + 4)) || ($i == 1) || ($i == $jumPage)){
                  if($i==$jumPage && $halaman <= $jumPage-4)
                      echo'<li class="disabled"><a href="#">..</a></li>';
                      if ($i == $halaman) echo '<li class="page-item active"><a class="page-link btn-pagination"href="javascript:void(0);" data-id="'.$i.'">'.$i.'</a></li>';
                      else echo '<li class="page-item"><a class="page-link btn-pagination"  href="javascript:void(0)" data-id="'.$i.'">'.$i.'</a></li>';

              if($i==1 && $halaman >= 4) echo '<li class="disabled"><a href="#">..</a></li>';

          }}

          //menampilkan link Next >>
          if ($halaman < $jumPage){echo'<li class="page-item"><a class="page-link btn-pagination" href="javascript:void(0);" data-id="'.($halaman+1).'">»</a></li>';
          }

    echo'
    </ul>
  </nav>';?>
  <script type="text/javascript">
    $(".load-data .datatable").dataTable({
      "iDisplayLength":35,
      "aLengthMenu": [[35, 40, 50, -1], [35, 40, 50, "All"]],
      paginate :false,
      ordering: true,
      language: {
          paginate: {
            previous: "<i class='fas fa-angle-left'>",
            next: "<i class='fas fa-angle-right'>"
          }
        },
    });
    $(".open-popup-link").magnificPopup({type:"image"});
  </script>
<?php
break;
}}