<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='6' AND level_id='$current_user[level]'";
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
              <li class="breadcrumb-item active" aria-current="page">Jam Sekolah</li>
            </ol>
          </nav>
        </div>
        
      </div>
    </div>
  </div>
</div>
 
  <div class="container-fluid mt--6">
    <div class="row">
        <div class="col-md-12">
          <div class="card pb-3">
            <!-- Card header -->
            <div class="pt-2 pl-2 mb-2">
              <ul class="nav nav-tabs custom-nav-tabs">

                <li class="nav-item">
                  <a class="nav-link" href="'.$mod.'&op=siswa">Siswa</a>
                </li>

                <li class="nav-item">
                  <a class="nav-link" href="'.$mod.'&op=guru">Guru</a>
                </li>

                <li class="nav-item">
                  <a class="nav-link" href="'.$mod.'&op=staff">Staff</a>
                </li>

              </ul>
            </div>';
           if($data_role['lihat']=='Y' && $data_role['modifikasi']=='Y'){

              $tipe = htmlentities($_GET['op']??'siswa');
              echo'
              <div class="card-body">
                <h3 class="mb-4">Pengaturan Jam sekolah '.(ucfirst($_GET['op']??'Siswa')).'</h3>
                  <form class="form-add" role="form" method="post" action="javascript:;" autocomplete="off">
                    <input ttipe="hidden" class="d-none" name="tipe" value="'.$tipe.'" readonly required>

                    <div class="table-responsive">
                    <table class="table align-items-center table-flush table-bordered">
                      <thead class="thead-light">
                        <tr>
                          <th scope="col">Hari</th>
                          <th scope="col">Masuk</th>
                          <th scope="col">Toleransi</th>
                          <th scope="col">Pulang</th>
                          <th scope="col">Status</th>
                        </tr>
                      </thead>
                      <tbody class="list">';
                      
                      $nama_hari = ["Senin","Selasa","Rabu","Kamis","Jumat","Sabtu","Minggu"];
                      $i = 0;
                      foreach($nama_hari as $values){$i++;
                        $query_waktu  ="SELECT * FROM jam_sekolah WHERE hari='$values' AND tipe='$tipe'";
                        $result_waktu = $connection->query($query_waktu);
                        $data_waktu  = $result_waktu->fetch_assoc();
                        echo'
                        <tr>
                          <td scope="row">
                            <input class="d-none" type="hidden" style="display:none" name="item[]" value="'.anti_injection($values).'" required readonly>
                            '.$values.'
                          </td>
                          <td class="budget">
                            <input class="form-control" type="time" name="jam_masuk[]" value="'.($data_waktu['jam_masuk']??'-').'" placeholder="Masuk">
                          </td>
                          <td>
                            <input class="form-control" type="time" name="jam_telat[]" value="'.($data_waktu['jam_telat']??'-').'" placeholder="Batas Telat">
                          </td>
                          <td>
                            <input class="form-control" type="time" name="jam_pulang[]"  value="'.($data_waktu['jam_pulang']??'-').'"  placeholder="Pulang">
                          </td>

                          <td>
                              <div class="selectdiv">
                                <label>
                                    <select class="form-control" name="active[]">';
                                    if($data_waktu['active'] == 'Y'){
                                      echo'<option value="Y" selected>Masuk</option>';
                                    }else{
                                      echo'<option value="Y">Masuk</option>';
                                    }
                                    if($data_waktu['active'] == 'N'){
                                      echo'<option value="N" selected>Libur</option>';
                                    }else{
                                      echo'<option value="N">Libur</option>';
                                    }
                                    echo'
                                    </select>
                                </label>
                              </div>
                          </td>
                        </tr>';
                      }
                      echo' 
                      </tbody>
                    </table>
                  </div>
                  <hr class="m-0">
                  <button class="btn btn-primary btn-save mt-3" type="submit"><i class="far fa-save"></i> Simpan</button>
                  </form>
        
              </div>';
            }else{
              hak_akses();
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