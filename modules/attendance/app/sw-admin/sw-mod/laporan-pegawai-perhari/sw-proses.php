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
case 'dropdown':
  if (empty($_POST['lokasi'])) {
    $filter = '';
  } else {
    $lokasi = anti_injection($_POST['lokasi']);
    $filter = "WHERE lokasi='$lokasi'";
  }

$selectedPegawai = isset($_POST['pegawai']) ? $_POST['pegawai'] : null;

echo'<option value="">Semua Pegawai</option>';
$query_pegawai = "SELECT pegawai_id,nama_lengkap FROM pegawai $filter ORDER BY nama_lengkap ASC";
$result_pegawai = $connection->query($query_pegawai);
if($result_pegawai->num_rows > 0) {
  while($data_pegawai = $result_pegawai->fetch_assoc()){
    $selected = ($data_pegawai['pegawai_id'] == epm_decode($selectedPegawai)) ? 'selected="selected"' : '';
    echo'<option value="'.epm_encode($data_pegawai['pegawai_id']).'" '.$selected.'>'.strip_tags($data_pegawai['nama_lengkap']).'</option>';
  }
}else{
  echo'<option value="">Data tidak ditemukan</option>';
}


break;
case 'filtering':
$warna      = '';
$background = '';
$jumlah_libur = 0;
$jumlah_libur_nasional = 0;
$jumlah_izin = 0;

if(isset($_POST['bulan']) OR isset($_POST['tahun'])){
  $bulan    = anti_injection($_POST['bulan']);
  $tahun    = anti_injection($_POST['tahun']);
} else{
  $bulan    = date ("m");
  $tahun    = date("Y");
}

/** Filter  */
if(!empty($_POST['lokasi']) AND !empty($_POST['posisi']) AND !empty($_POST['pegawai'])){
  $pegawai    = anti_injection($_POST['pegawai']);
  $lokasi     = anti_injection($_POST['lokasi']);
  $posisi     = anti_injection($_POST['posisi']);
  $filter     = "WHERE user.lokasi_id='$lokasi' AND user.posisi_id='$posisi' AND user.pegawai_id='$pegawai'";
  $pagination = "WHERE lokasi_id='$lokasi' AND posisi_id='$posisi' AND pegawai_id='$pegawai'";

}elseif(!empty($_POST['lokasi']) AND !empty($_POST['posisi'])){
  $lokasi        = anti_injection($_POST['lokasi']);
  $posisi     = anti_injection($_POST['posisi']);
  $filter       = "WHERE user.lokasi_id='$lokasi' AND user.posisi_id='$posisi'";
  $pagination   = "WHERE lokasi_id='$lokasi' AND posisi_id='$posisi'";

}elseif(!empty($_POST['lokasi']) AND !empty($_POST['pegawai'])){
  $pegawai    = anti_injection($_POST['pegawai']);
  $lokasi     = anti_injection($_POST['lokasi']);
  $filter     = "WHERE user.lokasi_id='$lokasi' AND user.pegawai_id='$pegawai'";
  $pagination = "WHERE lokasi_id='$lokasi' AND pegawai_id='$pegawai'";

}elseif(!empty($_POST['lokasi'])){
  $lokasi        = anti_injection($_POST['lokasi']);
  $filter       = "WHERE user.lokasi_id='$lokasi'";
  $pagination   = "WHERE lokasi_id='$lokasi'";
}else{
  $filter     = "";
  $pagination   = "";
}

$hari       = date("d");
$jumlahhari = date("t",mktime(0,0,0,$bulan,$hari,$tahun));
$kolom = $jumlahhari * 2;
echo'
<div class="table-responsive" style="overflow-x: auto!important;">
<table class="table align-items-center table-bordered datatable" style="width:100%">
  <thead class="thead-light">
    <tr>
      <th rowspan="3" width="40" class="text-center" style="vertical-align: middle;">No</th>
      <th rowspan="3" style="vertical-align: middle;">Pegawai</th>
      <th rowspan="3" style="vertical-align: middle;">Posisi</th>
      <th class="text-center" colspan="'.$kolom.'">'.ambilbulan($month).'</th>
      <th class="text-center" colspan="6">Keterangan</th>
    </tr>
    <tr>';
    for ($d=1;$d<=$jumlahhari;$d++) {
        $tanggal  = ''.$tahun.'-'.$bulan.'-'.$d.'';
        $hari_libur     = date('D',strtotime($tanggal));
    
      /** Menentukan Hari Libur Umum */

        $query_jumat ="SELECT libur_hari FROM libur WHERE libur_hari='Jumat' AND active='Y'";
        $result_jumat = $connection->query($query_jumat);
        if($result_jumat->num_rows >0 ){
          $jumat = 'Fri';
        }else{
          $jumat ='';
        }

        $query_sabtu ="SELECT libur_hari FROM libur WHERE libur_hari='Sabtu' AND active='Y'";
        $result_sabtu= $connection->query($query_sabtu);
        if($result_sabtu->num_rows >0 ){
          $sabtu = 'Sat';
        }else{
          $sabtu ='';
        }
    
        $query_minggu ="SELECT libur_hari FROM libur WHERE libur_hari='Minggu' AND active='Y'";
        $result_minggu = $connection->query($query_minggu);
        if($result_minggu->num_rows >0 ){
          $minggu = 'Sun';
        }else{
          $minggu ='';
        }
        
    /** End Menentukan Hari Libur Umum */
    
    
        if($hari_libur == $jumat OR $hari_libur == $sabtu OR $hari_libur == $minggu){
          $warna      ='#ffffff';
          $background ='#FF0000';
          $status     = 'Libur';
          $jumlah_libur++;
        }else{
          $query_libur  = "SELECT libur_tanggal,keterangan FROM libur_nasional WHERE libur_tanggal='$tanggal'";
          $result_libur = $connection->query($query_libur);
          if($result_libur->num_rows > 0){
            $data_libur = $result_libur->fetch_assoc();
            $warna='#ffffff';
            $background ='#FF0000';
            $jumlah_libur_nasional++;
            $status     = strip_tags($data_libur['keterangan']);
          }else{
            $warna      = '#111111';
            $background = '#f6f9fc';
            $status     = '-';
          }
        }
      echo'
        <th width="50" colspan="2" class="text-center" style="background:'.$background.';color:'.$warna.'">'.date('D', strtotime($tanggal)).'<br>'.$d.'</th>';
    }
      echo'
        <th width="50" rowspan="2" class="text-center">H</th>
        <th width="50" rowspan="2" class="text-center">T</th>
        <th width="50" rowspan="2" class="text-center">A</th>
        <th width="50" rowspan="2" class="text-center">I</th>
        <th width="50" rowspan="2" class="text-center">C</th>
        <th width="50" rowspan="2" class="text-center">PC</th>
      </tr>

      <tr>';
      for ($d=1;$d<=$jumlahhari;$d++) {
        echo'
        <th class="text-center">Masuk</th>
        <th class="text-center">Pulang</th>';
      }
      echo'
      </tr>
  </thead>
<tbody>';
    $limit=30; 
    $no =0;
    if(isset($_GET['halaman'])){
    $halaman = mysqli_real_escape_string($connection,$_GET['halaman']);}
    else{$halaman = 1;} $offset = ($halaman - 1) * $limit;

    $query_pegawai ="SELECT user.pegawai_id,user.nama_lengkap,posisi.posisi_nama FROM user
    INNER JOIN posisi  ON user.posisi_id = posisi.posisi_id $filter ORDER BY user.pegawai_id ASC LIMIT $offset, $limit";
    $result_pegawai = $connection->query($query_pegawai);
    if($result_pegawai->num_rows > 0){
      while ($data_pegawai = $result_pegawai->fetch_assoc()){$no++;
       echo'
      <tr>
        <td class="text-center">'.$no.'</td>
        <td width="150">'.strip_tags($data_pegawai['nama_lengkap']).'</td>
        <td width="150">'.strip_tags($data_pegawai['posisi_nama']).'</td>';
        for ($d=1;$d<=$jumlahhari;$d++){
          $tanggal  = ''.$tahun.'-'.$bulan.'-'.$d.'';
          $filter = "WHERE tanggal='$tanggal' AND MONTH(tanggal)='$bulan' AND year(tanggal)='$tahun' AND pegawai_id='$data_pegawai[pegawai_id]'";

          $query_absen ="SELECT tanggal,absen_in,absen_out,status_masuk,status_pulang,kehadiran FROM absen $filter";
          $result_absen = $connection->query($query_absen);
            if($result_absen->num_rows > 0){
              $data_absen = $result_absen->fetch_assoc();

              if($data_absen['status_masuk']=='Tepat Waktu'){
                $status_masuk ='<span class="badge badge-success">'.$data_absen['status_masuk'].'</span>';
              }elseif($data_absen['status_masuk']=='Telat'){
                  $status_masuk ='<span class="badge badge-danger">'.$data_absen['status_masuk'].'</span>';
              }else{
                if($data_absen['kehadiran'] =='Izin' OR $data_absen['kehadiran'] =='Cuti'){
                  $status_masuk ='<span class="badge badge-warning">'.$data_absen['kehadiran'].'</span>';
                } else{
                  $status_masuk ='';
                }
              }
        
              if($data_absen['absen_out']=='00:00:00'){
                if($data_absen['kehadiran'] =='Izin' OR $data_absen['kehadiran'] =='Cuti'){
                  $status_pulang ='<span class="badge badge-warning">'.$data_absen['kehadiran'].'</span>';
                } else{
                  $status_pulang ='';
                }
                $absen_out = '<span class="text-danger">Belum absen</span>';
              }else{
                if($data_absen['status_pulang']=='Tepat Waktu'){
                    $status_pulang ='<span class="badge badge-success">'.$data_absen['status_pulang'].'</span>';
                }else{
                    $status_pulang ='<span class="badge badge-danger">'.$data_absen['status_pulang'].'</span>';
                }
                $absen_out = $data_absen['absen_out'];
              }
            
            echo'
              <td class="text-center">'.$data_absen['absen_in'].'<br>'.$status_masuk.'</td>
              <td class="text-center">'.$absen_out.'<br>'.$status_pulang.'</td>';
            }else{
              echo'
              <td class="text-center"><i class="fas fa-times"></i></td>
              <td class="text-center"><i class="fas fa-times"></i></td>';
            }
          }

            $filter_jumlah = "MONTH(tanggal)='$bulan' AND year(tanggal)='$tahun' AND pegawai_id='$data_pegawai[pegawai_id]'";

            $query_hadir  = "SELECT absen_id FROM absen WHERE $filter_jumlah AND kehadiran='Hadir'";
            $hadir        = $connection->query($query_hadir);

            $query_telat  = "SELECT absen_id FROM absen WHERE $filter_jumlah AND status_masuk='Telat'";
            $terlambat   = $connection->query($query_telat);

            $query_pulang_cepat  = "SELECT absen_id FROM absen WHERE $filter_jumlah AND status_pulang='Pulang Cepat'";
            $pulang_cepat        = $connection->query($query_pulang_cepat);

            $query_izin  = "SELECT absen_id FROM absen WHERE $filter_jumlah AND kehadiran='Izin'";
            $izin   = $connection->query($query_izin);

            $query_cuti = "SELECT absen_id FROM absen WHERE $filter_jumlah AND kehadiran='Cuti'";
            $cuti  = $connection->query($query_cuti);

            $alpha = $jumlahhari  - $hadir->num_rows - $izin->num_rows - $cuti->num_rows - $jumlah_libur - $jumlah_libur_nasional;

          echo'
          <td width="50" class="text-center"><span class="badge badge-success">'.$hadir->num_rows.'</span></td>
          <th width="50" class="text-center"><span class="badge badge-warning">'.$terlambat->num_rows.'</span></td>
          <th width="50" class="text-center"><span class="badge badge-danger">'.$alpha.'</span></td>
          <th width="50" class="text-center"><span class="badge badge-primary">'.$izin->num_rows.'</span></td>
          <th width="50" class="text-center"><span class="badge badge-primary">'.$cuti->num_rows.'</span></td>
          <td width="50" class="text-center"><span class="badge badge-success">'.$pulang_cepat->num_rows.'</span></td>
      </tr>';

      }

    echo'
      </tbody>
    </table>
  </div>
        <nav>
          <ul class="pagination justify-content-center mt-3">';
          $query_pagination = "SELECT COUNT(pegawai_id) AS jumData FROM user $pagination";
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
                            echo'<li class="disabled"><a href="javascript:void(0)">..</a></li>';
                            if ($i == $halaman) echo '<li class="page-item active"><a class="page-link btn-pagination" data-id="'.$i.'" href="javascript:void(0)">'.$i.'</a></li>';
                            else echo '<li class="page-item"><a class="page-link btn-pagination"  href="javascript:void(0)" data-id="'.$i.'">'.$i.'</a></li>';

                    if($i==1 && $halaman >= 4) echo '<li class="disabled"><a href="javascript:void(0)">..</a></li>';

                }}

                //menampilkan link Next >>
                if ($halaman < $jumPage){echo'<li class="page-item"><a class="page-link btn-pagination" href="javascript:void(0);" data-id="'.($halaman+1).'">»</a></li>';
                }

          echo'
          </ul>
        </nav>';
  
    }else{
      //echo'Tidak Ada data pegawai';
    }
echo'
<script type="text/javascript">
    $(".load-data .datatable").dataTable({
      "iDisplayLength":35,
      "aLengthMenu": [[35, 40, 50, -1], [35, 40, 50, "All"]],
      paginate: false,
      language: {
          paginate: {
            previous: "<",
            next: ">"
          }
        },
  });
  </script>';
break;
}}?>