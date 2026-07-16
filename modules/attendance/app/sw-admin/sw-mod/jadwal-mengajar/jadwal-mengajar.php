<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='22' AND level_id='$current_user[level]'";
$result_role = $connection->query($query_role);
if($result_role->num_rows > 0){
  $data_role = $result_role->fetch_assoc();

switch(@$_GET['op']){
default:
echo'
<span class="modifikasi d-none">'.convert('encrypt', $data_role['modifikasi']).'</span>
<span class="hapus d-none">'.convert('encrypt',$data_role['hapus']).'</span>
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Jadwal Mengajar</li>
            </ol>
          </nav>
        </div>
        
      </div>
    </div>
  </div>
</div>

  <!-- Page content -->
  <div class="container-fluid mt--6">
    <div class="row">
        <div class="col-md-8">
          <div class="card pb-3">
            <!-- Card header -->
            <div class="card-header mb-2">
              <h3 class="mb-0 text-left float-left">Jadwal Mengajar</h3>
            </div>';
            if($data_role['lihat']=='Y'){
              echo'
              <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>Hari</th>
                    <th>Kelas</th>
                    <th>Mata Pelajaran</th>
                    <th class="text-center" width="10">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
              </div>';
            }else{
              hak_akses();
            }
            echo'
          </div>
          </div>

          <div class="col-md-4">
            <div class="card ">
              <div class="card-header mb-2">
                <h3 class="mb-0 text-left float-left">Input Jadwal Mengajar</h3>
              </div>

              <div class="card-body">
                <form class="form-add" role="form" action="javascript:;">
                  <input type="hidden" class="form-control id d-none" name="id" readonly>
                  <div class="form-group">
                    <label>Hari</label>
                    <select class="form-control hari" name="hari" required>';
                    $nama_hari = ["Senin","Selasa","Rabu","Kamis","Jumat","Sabtu","Minggu"];
                     foreach($nama_hari as $values){
                      echo'<option value="'.anti_injection($values).'">'.anti_injection($values).'</option>';
                     }
                     echo'
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Mata Pelajaran</label>
                    <select class="form-control mata_pelajaran" name="mata_pelajaran" required>
                      <option value="">Pilih Mata Pelajaran</option>';
                      $query_matkul = "SELECT id,nama_mapel FROM mata_pelajaran ORDER BY nama_mapel ASC";
                      $result_matkul = $connection->query($query_matkul);
                      while($data_matkul = $result_matkul->fetch_assoc()){
                        echo'<option value="'.$data_matkul['id'].'">'.strip_tags($data_matkul['nama_mapel']??'-').'</option>';
                      }
                    echo'
                    </select>
                  </div>
                  
                  <div class="form-group">
                    <label>Guru Pengampu</label>
                    <select class="form-control pegawai" name="pegawai" required>
                      <option value="">Pilih Guru</option>';
                      $query_guru = "SELECT pegawai_id,nama_lengkap FROM pegawai WHERE jabatan='guru' ORDER BY nama_lengkap ASC";
                      $result_guru = $connection->query($query_guru);
                      while($data_guru = $result_guru->fetch_assoc()){
                        echo'<option value="'.$data_guru['pegawai_id'].'">'.strip_tags($data_guru['nama_lengkap']??'-').'</option>';
                      }
                    echo'
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Kelas</label>
                    <select class="form-control kelas" name="kelas" required>
                    <option value="">Pilih Kelas</option>';
                    $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                    $result_kelas = $connection->query($query_kelas);
                      while ($data_kelas = $result_kelas->fetch_assoc()) {
                        echo'<option value="'.$data_kelas['nama_kelas'].'">'.$data_kelas['nama_kelas'].'</option>';
                      }
                    echo'
                    </select>
                  </div>

                  <div class="form-group">
                    <label>Dari Jam</label>
                    <input type="time" class="form-control dari_jam" name="dari_jam" required>
                  </div>

                  <div class="form-group">
                    <label>Sampai Jam</label>
                    <input type="time" class="form-control sampai_jam" name="sampai_jam" required>
                  </div>

                  <hr>';
                  if($data_role['modifikasi']=='Y'){
                      echo'
                      <button type="submit" class="btn  btn-primary btn-save"><i class="far fa-save"></i> Simpan</button>
                      <button class="btn btn-secondary btn-reset" type="reset"><i class="fas fa-undo"></i> Reset</button>';
                    }else{
                      echo'
                      <button class="btn btn-primary" disabled><i class="far fa-save"></i> Simpan</button>';
                    }
                  echo'
                </form>
              </div>
             </div>

          </div>
      </div>';
  break;
  }
  }else{
    theme_404();
  }
}?>

  