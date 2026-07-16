<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='18' AND level_id='$current_user[level]'";
$result_role = $connection->query($query_role);
if($result_role->num_rows > 0){
  $data_role = $result_role->fetch_assoc();

switch(@$_GET['op']){ 
default:
echo'
<span class="modifikasi d-none">'.convert('encrypt', $data_role['modifikasi']).'</span>
<span class="hapus d-none">'.convert('encrypt',$data_role['hapus']).'</span>
<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item active" aria-current="page">Pegawai</li>
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
      <div class="card pb-3">
        <!-- Card header -->
        <div class="card-header mb-2">
          <h3 class="mt-2 mb-0 text-left float-left">Pegawai</h3>
          <div class="float-right">';
            if($data_role['modifikasi']=='Y'){
            echo'
            <button class="btn btn-secondary btn-qrcode">Cetak Id Card</button>
            <button class="btn btn-secondary btn-import"><i class="fas fa-file-upload"></i> Impor</button>
            <a href="'.$mod.'&op=add"  class="btn btn-primary"><i class="fas fa-plus"></i> Tambah</a>';
            }else{
            echo'
            <button class="btn btn-primary" disabled><i class="fas fa-plus"></i> Tambah</button>';
            }
          echo'
          </div>
        </div>';
        if($data_role['lihat']=='Y'){
          echo'
          <form class="form-table" action="javascript:void(0);" autocomplete="of">
        <div class="table-responsive">
          <table class="table align-items-center table-flush table-striped datatable-user">
            <thead class="bg-primary text-white">
              <tr>
                <th width="4">#</th>
                <th width="4">No</th>
                <th class="text-center" width="5">Avatar</th>
                <th class="text-center" width="5">Qrcode</th>
                <th>Nama</th>
                <th>RFID</th>
                <th>Jabatan</th>
                <th>Jenis Kelamin</th>
                <th>Terakhir Login</th>
                <th class="text-center">Status</th> 
                <th class="text-center">Aksi</th>
              </tr>
            </thead>
            <tbody>
            </tbody>
            <tfoot class="bg-primary text-white">
                <tr>
                    <th colspan="11">
                      <div class="row">
                        <div class="col-md-1">
                          <label class="mt-3">Status</label>
                        </div>

                        <div class="col-md-2">
                          <select class="form-control status" name="status" required>
                            <option value="N">Tadak Aktif</option>
                            <option value="Y">Aktif</option>
                          </select>
                        </div>

                        <div class="col-md-2">
                          <button class="btn btn-warning btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
                        </div>
                    </th>
                </tr>
            </tfoot>
          </table>
        </div>
        </form> ';
      }else{
        hak_akses();
      }
      echo'
      </div>
    </div>
  </div>
      
<div class="modal fade modal-import data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
<div class="modal-dialog modal-md">
<div class="modal-content">
  <div class="modal-header">
    <h5 class="modal-title">Impor Data Pegawai</h5>
    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
  <form class="form-import" action="javascript:void(0);" autocomplete="of">
    <div class="modal-body">
      <div class="form-group">
        <label>Upload file</label>
        <a href="../sw-content/tema/Format-pegawai.xlsx" class="float-right" target="_blank"><i class="fas fa-download"></i> Format</a>
        <input type="file" class="form-control" name="file_excel" accept=".xls,.xlsx" placeholder="Impor data" required>
      </div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary btn-save"><i class="fas fa-save"></i> Simpan</button>
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
    </div>
  </form>
  </div>
</div>
</div>

<!-- Modal Cetak Qr Code -->
<div class="modal fade modal-qrcode data-backdrop="static" data-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
<div class="modal-dialog modal-md">
  <div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Cetak ID Card</h5>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    
      <div class="modal-body">
        <div class="form-group">
          <label class="form-control-label">Jabatan</label>
            <select class="form-control result-jabatan" required>
              <option value="">Semua Pegawai</option>
              <option value="guru">Pengajar</option>
              <option value="staff">Staff</option>
            </select>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-primary btn-export"><i class="fas fa-print"></i> Print</span></button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>

    </div>
  </div>
</div>';

/* -------------- Add -------------- */
break;
case 'add':
echo'
<!-- Header -->
<div class="header bg-primary pb-6">
  <div class="container-fluid">
    <div class="header-body">
      <div class="row align-items-center py-4">
        <div class="col-lg-6 col-7">
          <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
            <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
              <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
              <li class="breadcrumb-item"><a href="./'.$mod.'">Pegawai</a></li>
              <li class="breadcrumb-item active" aria-current="page">Tambah</li>
            </ol>
          </nav>
        </div>
        
      </div>
    </div>
  </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--6">
<div class="card-wrapper">
<!-- Form controls -->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h3 class="mb-0">Tambah Data Pegawai</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">';
      if($data_role['modifikasi']=='Y'){
        echo'
        <form class="form-add" role="form" method="post" action="javascript:;" autocomplete="off">
          <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                  <label class="form-control-label">NIP</label>
                  <input type="number" class="form-control" name="nip">
                </div>

                <div class="form-group">
                  <label class="form-control-label">ID RFID</label>
                  <input type="text" class="form-control" name="rfid">
                </div>

                <div class="form-group">
                  <label class="form-control-label">Nama Lengkap</label>
                  <input type="text" class="form-control" name="nama_lengkap" required>
                </div>


                <div class="form-group">
                  <label class="form-control-label">Email</label>
                  <input type="email" class="form-control" name="email" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Password</label>
                  <input type="text" class="form-control password" name="password" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Tempat Lahir</label>
                  <input type="text" class="form-control" name="tempat_lahir" required>
                </div>



                <div class="form-group">
                  <label class="form-control-label">Tanggal Lahir</label>
                  <input type="text" class="form-control datepicker" name="tanggal_lahir" placeholder="'.tanggal_ind($date).'" required>
                </div>
            </div>

          
            <!-- Right -->
             <div class="col-md-6">

                <div class="form-group">
                  <label class="form-control-label">Jenis Kelamin</label>
                  <select class="form-control" name="jenis_kelamin" required>
                      <option value="Laki-laki">Laki-laki</option>
                      <option value="Perempuan">Perempuan</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Jabatan</label>
                  <select class="form-control" name="jabatan" required>
                    <option value="">Pilih:</option>
                    <option value="guru">Pengajar</option>
                    <option value="staff">Staff</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Wali Kelas</label>
                  <select class="form-control" name="wali_kelas" required>
                  <option value="-">Bukan Wali Kelas</option>';
                  $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                  $result_kelas = $connection->query($query_kelas);
                  while ($data_kelas = $result_kelas->fetch_assoc()) {
                    echo'<option value="'.$data_kelas['nama_kelas'].'">'.$data_kelas['nama_kelas'].'</option>';
                  }
                  echo'
                  </select>
                </div>
                
                <div class="form-group">
                  <label class="form-control-label">Lokasi/Tempat Sekolah</label>
                  <select class="form-control" name="lokasi" required>';
                    $query_lokasi ="SELECT lokasi_id,lokasi_nama FROM lokasi ORDER BY lokasi_nama ASC";
                    $result_lokasi = $connection->query($query_lokasi);
                    while ($data_lokasi = $result_lokasi->fetch_assoc()) {
                      echo'<option value="'.$data_lokasi['lokasi_id'].'">'.$data_lokasi['lokasi_nama'].'</option>';
                    }
                    echo'
                  </select>
                </div>


                <div class="form-group">
                  <label class="form-control-label">Alamat Rumah</label>
                  <textarea class="form-control" name="alamat" rows="3"></textarea>
                </div>

                <div class="form-group">
                  <label class="form-control-label">No. Telp/WhatsApp</label>
                  <input type="number" class="form-control" name="telp">
                </div>
          
            </div>
          </div>
            <hr>
            <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
            <a href="./'.$mod.'" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
            
        </form>';
        }else{
          hak_akses();
        }
      echo'
      </div>
    </div>
  </div>';


/** Update Guru/User*/
break;
case 'update':
if(!empty($_GET['id'])){
$id     =  anti_injection(convert('decrypt',$_GET['id'])); 
$query_user  ="SELECT * FROM pegawai WHERE pegawai_id='$id' LIMIT 1";
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
              <li class="breadcrumb-item"><a href="./'.$mod.'">Pegawai</a></li>
              <li class="breadcrumb-item active" aria-current="page">Ubah</li>
            </ol>
          </nav>
        </div>
        
      </div>
    </div>
  </div>
</div>

<!-- Page content -->
<div class="container-fluid mt--6">
<div class="card-wrapper">
<!-- Form controls -->
<div class="card">
  <!-- Card header -->
  <div class="card-header">
    <h3 class="mb-0">Ubah Data Pegawai</h3>
  </div>
    <!-- Card body -->
    <div class="card-body">';
    if($result_user->num_rows > 0){
    $data_user  = $result_user->fetch_assoc();
      if($data_role['modifikasi']=='Y'){
        echo'
        <form class="form-update" role="form" method="post" action="javascript;;" autocomplete="off">
        <input type="hidden" class="d-none" name="id" value="'.epm_encode($data_user['pegawai_id']).'" required readonly>
          <div class="row">
            <div class="col-md-6">

              <div class="form-group">
                <label class="form-control-label">NIP</label>
                <input type="text" class="form-control" name="nip" value="'.strip_tags($data_user['nip']??'-').'">
              </div>

              <div class="form-group">
                <label class="form-control-label">ID RFID</label>
                <input type="text" class="form-control" name="rfid" value="'.htmlentities($data_user['rfid'] ?? '', ENT_QUOTES, 'UTF-8').'">
              </div>

              <div class="form-group">
                <label class="form-control-label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" value="'.strip_tags($data_user['nama_lengkap']??'-').'" required>
              </div>

              <div class="form-group">
                  <label class="form-control-label">Email</label>
                  <input type="email" class="form-control" name="email" value="'.strip_tags($data_user['email']??'-').'" required>
              </div>

              <div class="form-group">
                <label class="form-control-label">Tempat Lahir</label>
                <input type="text" class="form-control" name="tempat_lahir" value="'.strip_tags($data_user['tempat_lahir']??'-').'" required>
              </div>


              <div class="form-group">
                <label class="form-control-label">Tanggal Lahir</label>
                <input type="text" class="form-control datepicker" name="tanggal_lahir" value="' . (!empty($data_user['tanggal_lahir']) ? tanggal_ind($data_user['tanggal_lahir']) : '-') . '" required>
              </div>

          </div>

          <!-- Right -->
          <div class="col-md-6">

              <div class="form-group">
                <label class="form-control-label">Jenis Kelamin</label>
                  <select class="form-control" name="jenis_kelamin" required>';
                    if($data_user['jenis_kelamin'] =='Laki-laki'){
                      echo'<option value="Laki-laki" selected>Laki-laki</option>';
                    }else{
                        echo'<option value="Laki-laki">Laki-laki</option>';
                    }
                    if($data_user['jenis_kelamin'] =='Perempuan'){
                        echo'<option value="Perempuan">Perempuan</option>';
                    }else{
                        echo'<option value="Perempuan">Perempuan</option>';
                    }
                    echo'
                  </select>
              </div>

                <div class="form-group">
                  <label class="form-control-label">Jabatan</label>
                  <select class="form-control" name="jabatan" required>
                  <option value="">Pilih:</option>';
                    if($data_user['jabatan'] =='guru'){
                      echo'<option value="guru" selected>Pengajar</option>';
                    }else{
                      echo'<option value="guru">Pengajar</option>';
                    }
                    if($data_user['jabatan'] =='staff'){
                    echo'<option value="staff" selected>Staff</option>';
                    }else{
                      echo'<option value="staff">Staff</option>';
                    }
                    echo'
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Wali Kelas</label>
                  <select class="form-control" name="wali_kelas" required>
                  <option value="-">Bukan Wali Kelas</option>';
                  $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                  $result_kelas = $connection->query($query_kelas);
                  while ($data_kelas = $result_kelas->fetch_assoc()) {
                    $selected = ($data_kelas['nama_kelas'] == $data_user['wali_kelas']) ? 'selected' : '';
                    echo'<option value="'.$data_kelas['nama_kelas'].'" '.$selected.'>'.$data_kelas['nama_kelas'].'</option>';
                  }
                  echo'
                  </select>
                </div>

  
                <div class="form-group">
                <label class="form-control-label">Lokasi/Tempat Sekolah</label>
                <select class="form-control" name="lokasi" required>';
                $query_lokasi ="SELECT lokasi_id,lokasi_nama FROM lokasi ORDER BY lokasi_nama ASC";
                $result_lokasi = $connection->query($query_lokasi);
                while ($data_lokasi = $result_lokasi->fetch_assoc()) {
                  if($data_lokasi['lokasi_id'] == $data_user['lokasi']) {
                    echo'<option value="'.$data_lokasi['lokasi_id'].'" selected>'.$data_lokasi['lokasi_nama'].'</option>';
                  }else{
                    echo'<option value="'.$data_lokasi['lokasi_id'].'">'.$data_lokasi['lokasi_nama'].'</option>';
                  }
                }
                echo'
                </select>
              </div>

              <div class="form-group">
                <label class="form-control-label">No. Telp/WhatsApp</label>
                <input type="number" class="form-control" name="telp" value="'.strip_tags($data_user['telp']??'').'">
              </div>
          
              <div class="form-group">
                <label class="form-control-label">Alamat Lengkap</label>
                <textarea class="form-control" name="alamat" rows="3" required>'.strip_tags($data_user['alamat']).'</textarea>
              </div>

          </div>
          </div>
            <hr>
            <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
            <a href="./'.$mod.'" class="btn btn-secondary" type="button"><i class="fas fa-undo"></i> Kembali</a>
        </form>';
        }else{
          hak_akses();
        }

        }else{
          theme_404();
        }
    echo'
    </div>
  </div>
</div>';
}
  break;
  }

  }else{
    /** Modul tidak ditemukan */
  }

}?>

  