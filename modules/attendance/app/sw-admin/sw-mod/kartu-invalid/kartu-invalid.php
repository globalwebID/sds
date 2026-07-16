<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{

switch(@$_GET['op']){ 
default:
echo'
<!-- Header -->
<div class="header bg-primary pb-6">
      <div class="container-fluid">
        <div class="header-body">
          <div class="row align-items-center py-4">
            <div class="col-lg-6 col-7">
            <h6 class="h2 text-white d-inline-block mb-0">Kartu Invalid</h6>
              <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
                <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                  <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                  <li class="breadcrumb-item active" aria-current="page">Kartu Invalid</li>
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
              <h3 class="mt-2 mb-0 text-left float-left">Kartu Invalid</h3>
            </div>

            <div class="table-responsive">
              <table class="table align-items-center table-flush table-striped datatable" style="width:100%">
                <thead class="thead-light">
                  <tr>
                    <th class="text-center" width="2">No</th>
                    <th>ID KARTU</th>
                    <th>Status</th>
                    <th width="6">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>

            </div>
          </div>
        </div>
      </div>';

break;
case'add-siswa':
if(!empty($_GET['uid'])){
  $uid = anti_injection($_GET['uid']);

echo'
<!-- Header -->
  <div class="header bg-primary pb-6">
    <div class="container-fluid">
      <div class="header-body">
        <div class="row align-items-center py-4">
          <div class="col-lg-6 col-7">
          <h6 class="h2 text-white d-inline-block mb-0">Siswa</h6>
            <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
              <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="./'.$mod.'">Kartu Invalid</a></li>
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
        <h3 class="mb-0">Tambah Data Siswa/User</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">
        <form class="form-add" role="form" method="post" action="#" autocomplete="off">
          <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                  <label class="form-control-label">NISN</label>
                  <input type="number" class="form-control" name="nisn" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">RFID</label>
                  <input type="text" class="form-control" name="rfid" value="'.htmlspecialchars($uid).'" readonly required>
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
                  <input type="password" class="form-control" name="password" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Tempat Lahir</label>
                  <input type="text" class="form-control" name="tempat_lahir">
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
                  <label class="form-control-label">Kelas</label>
                  <select class="form-control" name="kelas" required>';
                  $query_kelas ="SELECT * FROM absensi_kelas ORDER BY nama_kelas ASC";
                  $result_kelas = $connection->query($query_kelas);
                  while ($data_kelas = $result_kelas->fetch_assoc()) {
                    echo'<option value="'.$data_kelas['kelas_id'].'">'.$data_kelas['nama_kelas'].'</option>';
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

        </form>

      </div>
    </div>
  </div>';
}


break;
case'add-guru':
if(!empty($_GET['uid'])){
  $uid = anti_injection($_GET['uid']);
echo'
  <div class="header bg-primary pb-6">
    <div class="container-fluid">
      <div class="header-body">
        <div class="row align-items-center py-4">
          <div class="col-lg-6 col-7">
          <h6 class="h2 text-white d-inline-block mb-0">Guru/Tu</h6>
            <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
              <ol class="breadcrumb breadcrumb-links breadcrumb-dark">
                <li class="breadcrumb-item"><a href="./"><i class="fas fa-home"></i> Dashboard</a></li>
                <li class="breadcrumb-item"><a href="./'.$mod.'">Kartu Invalid</a></li>
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
        <h3 class="mb-0">Tambah Data Guru</h3>
      </div>
      <!-- Card body -->
      <div class="card-body">
        <form class="form-add-guru" role="form" method="post" action="javascript:void(0);" autocomplete="off">
          <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                  <label class="form-control-label">NIP</label>
                  <input type="number" class="form-control" name="nip">
                </div>

                <div class="form-group">
                  <label class="form-control-label">ID RFID</label>
                  <input type="text" class="form-control" name="rfid" readonly value="'.htmlspecialchars($uid).'" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Nama Lengkap</label>
                  <input type="text" class="form-control" name="nama_lengkap" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Jabatan</label>
                  <input type="text" class="form-control" name="jabatan">
                </div>

                <div class="form-group">
                  <label class="form-control-label">Email</label>
                  <input type="email" class="form-control" name="email" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Password</label>
                  <input type="password" class="form-control password" name="password" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Tempat Lahir</label>
                  <input type="text" class="form-control" name="tempat_lahir">
                </div>

            </div>

          
            <!-- Right -->
             <div class="col-md-6">

                <div class="form-group">
                  <label class="form-control-label">Tanggal Lahir</label>
                  <input type="text" class="form-control datepicker" name="tanggal_lahir" placeholder="'.tanggal_ind($date).'" required>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Jenis Kelamin</label>
                  <select class="form-control" name="jenis_kelamin" required>
                      <option value="Laki-laki">Laki-laki</option>
                      <option value="Perempuan">Perempuan</option>
                  </select>
                </div>

                <div class="form-group">
                  <label class="form-control-label">Tipe</label>
                  <select class="form-control" name="tipe" required>
                    <option value="">Pilih:</option>
                    <option value="Guru">Guru</option>
                    <option value="Tu">TU</option>
                  </select>
                </div>
                
                <div class="form-group">
                  <label class="form-control-label">Lokasi</label>
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
            
        </form>
      </div>
    </div>
  </div>';
}

break;
  }
}?>

  