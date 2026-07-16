<?PHP
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login');
  exit;
}
else{
$query_role ="SELECT lihat,modifikasi,hapus FROM role WHERE modul_id='9' AND level_id='$current_user[level]' LIMIT 1";
$result_role = $connection->query($query_role);
if($result_role->num_rows > 0){
$data_role = $result_role->fetch_assoc();

switch(@$_GET['op']){ 
default:

$notifikasi = "UPDATE notifikasi SET status='Y' WHERE tipe='pegawai' AND tujuan='siswa' AND status='N'";
$connection->query($notifikasi);

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
              <li class="breadcrumb-item active" aria-current="page">Izin Siswa</li>
            </ol>
          </nav>
        </div>
        
      </div>
    </div>
  </div>
</div>


<!-- Page content -->
<div class="container-fluid mt--6 mb-5">
  <!-- Table -->
  <div class="row">
        <div class="col">
          <div class="card pb-3">
            <!-- Card header -->
            <div class="card-header mb-2">
              <h3 class="mt-2 mb-0 text-left float-left">Izin Siswa</h3>
              <div class="float-right">';
              if($data_role['modifikasi']=='Y'){
                echo'
                <button class="btn btn-primary btn-add"><i class="fas fa-plus"></i> Tambah</button>';
              }else{
                echo'
                <button class="btn btn-primary" disabled><i class="fas fa-plus"></i> Tambah</button>';
              }
              echo'
              </div>
            </div>

            <div class="table-responsive">';
            if($data_role['lihat']=='Y'){
              echo'
              <table class="table align-items-center table-striped datatable">
                <thead class="bg-primary text-white">
                  <tr>
                    <th class="text-center" width="5">No</th>
                    <th>Nama</th>
                    <th>Tanggal Izin</th>
                    <th>Alasan</th>
                    <th>Keterangan</th>
                    <th class="text-center">Foto</th>
                    <th>Tanggal Terbit</th>
                    <th class="text-center">Status</th>
                    <th class="text-left"  width="6">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>';
            }else{
              hak_akses();
            }
            echo'
            </div>
          </div>
        </div>
      </div>';


      if($data_role['modifikasi']=='Y'){
      echo'

      <!-- Modal Add -->
      <div  class="modal fade modal-add" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
            <div class="modal-dialog modal-md" role="document">
                <div class="modal-content">
                <form class="form-add" role="form" action="#" autocomplete="off">
                    <input type="hidden" class="form-control id d-none" name="id" readonly>
                    <div class="modal-header">
                        <h5 class="modal-title">Modal Title</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <div class="modal-body" style="overflow-y: scroll; max-height:450px;">
                    
                    <div class="form-group">
                        <label>Siswa</label>
                        <select name="user_id" class="form-control user"  required>';
                          $query_siswa = "SELECT user_id,nama_lengkap,nisn FROM user ORDER BY nama_lengkap ASC";
                          $result_siswa = $connection->query($query_siswa);
                          while ($data_siswa = $result_siswa->fetch_assoc()) {
                            echo'<option value="'.htmlentities($data_siswa['user_id']).'">'.strip_tags($data_siswa['nama_lengkap']??'-').' ['.($data_siswa['nisn']??'-').']</option>';
                          }
                        echo'
                        </select>
                    </div>

                    <div class="row input-daterange datepicker align-items-center">
                      <div class="col">
                        <div class="form-group">
                          <label class="form-control-label">Mulai</label>
                          <input type="text"  class="form-control tanggal" name="tanggal" placeholder="Start date" value="'.tanggal_ind($date).'">
                        </div>
                      </div>
                      <div class="col">
                        <div class="form-group">
                          <label class="form-control-label">Sampai</label>
                          <input type="text" class="form-control tanggal-selesai" name="tanggal_selesai" placeholder="End date"  value="'.tanggal_ind($date).'">
                        </div>
                      </div>
                    </div>

                    <div class="form-group s-widodo.com">
                        <label class="form-control-label s-widodo.com">Alasan</label>
                        <select name="alasan" class="form-control alasan" required>
                            <option value="">Pilih:</option>';
                            $query = "SELECT nama FROM lain_lain WHERE tipe='izin' ORDER BY nama ASC";
                            $result = mysqli_query($connection, $query);
                            while ($row = mysqli_fetch_assoc($result)){
                                echo'
                                <option value="'.$row['nama'].'">'.$row['nama'].'</option>';
                            }
                        echo'
                        </select>                        
                    </div>

                    <div class="form-group">
                        <label>Keterangan</label>
                        <textarea class="form-control keterangan" name="keterangan" rows="2" required></textarea>
                    </div>

                    <div class="form-group">
                        <label>Unggah Foto</label>
                        <div class="file-upload">
                            <div class="image-upload-wrap">
                            <input class="file-upload-input fileInput" type="file" name="foto" onchange="readURL(this);" accept="image/*">
                                <div class="drag-text">
                                <i class="lni lni-cloud-upload"></i>
                                <h3>Drag and drop files here</h3>
                                </div>
                            </div>
                            <div class="file-upload-content">
                                <img class="file-upload-image" src="sw-assets/img/media.png" alt="Upload" height="150">
                                <div class="image-title-wrap">
                                    <button type="button" onclick="removeUpload()" class="btn btn-danger btn-sm">Ubah<span class="image-title"></span></button>
                                </div>
                            </div>
                        </div>
                        <div class="alert fade_info">Hanya format JPG atau JPEG yang diperbolehkan. Boleh dikosongkan jika tidak ingin mengunggah foto.</div>
                    </div>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn  btn-primary btn-save">Simpan</button>
                        <button type="button" class="btn  btn-secondary" data-dismiss="modal">Close</button>
                    </div>
                </form>
                </div>
            </div>
        </div>';
      }
  break;
  }
  }else{
    theme_404();
  }
}?>