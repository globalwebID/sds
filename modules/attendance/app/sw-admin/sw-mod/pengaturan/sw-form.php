<?php
if(!isset($_COOKIE['ADMIN_KEY']) && !isset($_COOKIE['KEY'])){
  header('location:./login/');
  exit;
}
else{
require_once'../../../sw-library/sw-config.php';
require_once'../../../sw-library/sw-function.php';
require_once'../../login/user.php';

if(htmlspecialchars($_GET['id']) == 1){
echo'
<form class="form-setting" role="form" method="post" action="javascript:void(0)" autocomplete="off">
  <div class="form-group">
      <h4>PENGATURAN WEB</h4>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Nama Aplikasi</label>
        <input type="text" class="form-control" name="site_name"  value="'.strip_tags($site_name).'" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Pemilik</label>
        <input type="text" class="form-control" name="site_owner"  value="'.strip_tags($site_owner).'" required>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Nama Sekolah</label>
        <input type="text" class="form-control" name="nama_sekolah"  value="'.strip_tags($nama_sekolah??'-').'" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Kementrian</label>
        <input type="text" class="form-control" name="kementrian"  value="'.strip_tags($kementrian??'-').'" required>
      </div>
    </div>
    <div class="col-md-4">
      <div class="form-group">
        <label class="form-control-label">NPSN</label>
        <input type="number" class="form-control" name="npsn"  value="'.strip_tags($npsn??'-').'" required>
      </div>
    </div>

    <div class="col-md-4">
      <div class="form-group">
        <label class="form-control-label">Telp</label>
        <input type="number" class="form-control telp" name="site_phone"  value="'.strip_tags($site_phone).'" required>
      </div>
    </div>

    <div class="col-md-4">
      <div class="form-group">
        <label class="form-control-label">Email</label>
        <input type="email" class="form-control email" name="site_email"  value="'.strip_tags($site_email).'" required>
      </div>
    </div>
  </div>
  <hr class="mt-2 mb-3">
  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Desa</label>
        <input type="text" class="form-control" name="desa"  value="'.strip_tags($desa??'-').'" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Kecamatan</label>
        <input type="text" class="form-control" name="kecamatan"  value="'.strip_tags($kecamatan??'-').'" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Kabupaten/Kota</label>
        <input type="text" class="form-control" name="kabupaten"  value="'.strip_tags($kabupaten??'-').'" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Propinsi</label>
        <input type="text" class="form-control" name="propinsi"  value="'.strip_tags($propinsi??'-').'" required>
      </div>
    </div>
  </div>

  <hr class="mt-2 mb-3">
  <div class="row">
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Kepala Sekolah</label>
        <input type="text" class="form-control" name="kepala_sekolah"  value="'.strip_tags($kepala_sekolah??'-').'" required>
      </div>
    </div>
    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">NIP Kepala Sekolah</label>
        <input type="text" class="form-control" name="nip_kepala_sekolah"  value="'.strip_tags($nip_kepala_sekolah??'-').'" required>
      </div>
    </div>

    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Alamat Lengkap</label>
        <input class="form-control" rows="3" name="site_address" value="'.strip_tags($site_address??'-').'" required>
      </div>
    </div>

    <div class="col-md-6">
      <div class="form-group">
        <label class="form-control-label">Server/Url Web 
        <small class="text-warning">(Jangan gunakan tanda / dibelakang)</small></label>
        <input type="url" class="form-control" name="site_url" value="'.strip_tags($site_url).'" required>
      </div>
    </div>
  </div>

  <hr class="mt-2 mb-3">

  <div class="row">
    <div class="col-md-4">
      <div class="form-group">
        <label class="form-control-label">Logo Aplikasi</label>
        <div class="card m-0" style="border:solid 1px #eee;">
            <div class="card-body text-center">';
            if(file_exists('../../../sw-content/'.($row_site['site_logo']??'thumbnail.jpg').'')){
              echo'
              <img src="../sw-content/'.($row_site['site_logo']??'thumbnail.jpg').'" class="img-responsive img-logo" height="40">';
            }else{
              echo'<img src="./sw-assets/img/media.png" class="img-responsive img-logo" height="40">';
            }
            echo'
            </div>
            <div class="card-footer">
            <div class="input-group-prepend">
              <button class="btn btn-outline-primary btn-file btn-block"><i class="fa fa-refresh"></i> Ubah Logo App <input type="file" class="upload logo" name="logo" accept=".jpg, .jpeg, ,gif, .png">
              </button>
            </div>
            </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="form-group">
        <label class="form-control-label">Favicon</label>
        <div class="card m-0 border-1" style="border:solid 1px #eee;">
            <div class="card-body text-center">';
            if(file_exists("../../../sw-content/$site_favicon")){
              echo'
              <img src="../sw-content/'.$site_favicon.'" class="img-responsive img-favicon" height="40">';
            }else{
              echo'<img src="./sw-assets/img/media.png" class="img-responsive img-favicon" height="40">';
            }
            echo'
            </div>
            <div class="card-footer">
              <div class="input-group-prepend">
                <button class="btn btn-outline-primary btn-file btn-block"><i class="fa fa-refresh"></i> Ubah Favicon <input type="file" class="upload favicon" name="favicon" accept=".jpg, .jpeg, ,gif, .png">
              </button>
            </div>
            </div>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="form-group">
        <label class="form-control-label">KOP</label>
        <div class="card m-0" style="border:solid 1px #eee;">
            <div class="card-body text-center">';
            if(file_exists("../../../sw-content/$site_kop")){
              echo'
              <img src="../sw-content/'.$site_kop.'" class="img-responsive img-logo" height="40">';
            }else{
              echo'<img src="./sw-assets/img/media.png" class="img-responsive img-logo" height="40">';
            }
            echo'
            </div>
            <div class="card-footer">
            <div class="input-group-prepend">
              <button class="btn btn-outline-primary btn-file btn-block"><i class="fa fa-refresh"></i> Ubah Kop <input type="file" class="upload kop" name="kop" accept=".jpg, .jpeg, ,gif, .png">
              </button>
            </div>
            </div>
        </div>
      </div>
    </div>


    <div class="col-md-4">
      <div class="form-group">
        <label class="form-control-label">Stempel</label>
          <div class="card m-0" style="border:solid 1px #eee;">
            <div class="card-body text-center">';
            if(file_exists("../../../sw-content/".($stempel??'-.jpg')."")){
              echo'
              <img src="../sw-content/'.($stempel??'-.jpg').'" class="img-responsive img-logo" height="40">';
            }else{
              echo'<img src="./sw-assets/img/media.png" class="img-responsive img-logo" height="40">';
            }
            echo'
            </div>
            <div class="card-footer">
            <div class="input-group-prepend">
              <button class="btn btn-outline-primary btn-file btn-block"><i class="fa fa-refresh"></i> Ubah Stempel<input type="file" class="upload stempel" name="stempel" accept=".jpg, .jpeg, ,gif, .png">
              </button>
              <button class="btn btn-outline-danger btn-delete">Hapus</button>
            </div>
            </div>
        </div>
      </div>
    </div>

  </div>
  <hr>
  <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
  <button class="btn btn-secondary" type="reset"><i class="fas fa-undo"></i> Reset</button>
</form>';


/** Pengaturan Absen */
}elseif(htmlspecialchars($_GET['id']) == 2){
    echo'
    <form class="form-setting-absensi" role="form" method="post" action="javascript:void(0)" autocomplete="off">
          <div class="form-group">
              <h4>PENGATURAN ABSENSI</h4>
          </div>

        <div class="form-group row">
          <label class="col-md-2 col-form-label form-control-label">Timezone</label>
          <div class="col-md-6">
            <select class="form-control valid" name="timezone" required="">';
              $query_time = "SELECT * FROM lain_lain WHERE tipe='timezone' ORDER BY lain_lain_id ASC";
              $result_time = $connection->query($query_time);
              while ($data = $result_time->fetch_assoc()){
                $selected = ($data['nama']== $row_site['timezone']) ? 'selected' : '';
                echo'<option value="'.strip_tags($data['nama']??'').'" '.$selected.'>'.strip_tags($data['nama']??'').'</option>';
              }
              echo'
            </select>
          </div>
        </div>

        <hr>
        
        <div class="form-group row">
          <label class="col-md-2 col-form-label form-control-label">Absensi Siswa<br>
          <small class="text-muted">Pengaturan ini berlaku untuk dashboard Siswa</small>
          </label>
          <div class="col-md-6 p-1">
            <select class="form-control valid" name="tipe_absen_siswa" required="">
              <option value="selfie" ' . ($row_site['tipe_absen_siswa'] == 'selfie' ? 'selected' : '') . '>Selfie</option>
              <option value="qrcode" ' . ($row_site['tipe_absen_siswa'] == 'qrcode' ? 'selected' : '') . '>QRCODE ID Card</option>
              <option value="qrcode_lokasi" ' . ($row_site['tipe_absen_siswa'] == 'qrcode_lokasi' ? 'selected' : '') . '>QRCODE Lokasi</option>
            </select>
          </div>
        </div>


        <div class="form-group row">
          <label class="col-md-2 col-form-label form-control-label">Absensi Pegawai<br>
          <small class="text-muted">Pengaturan ini berlaku untuk dashboard Pegawai/Guru</small>
          </label>
          <div class="col-md-6 p-1">
            <select class="form-control valid" name="tipe_absen_pegawai" required="">
              <option value="selfie" ' . ($row_site['tipe_absen_pegawai'] == 'selfie' ? 'selected' : '') . '>Selfie</option>
              <option value="qrcode" ' . ($row_site['tipe_absen_pegawai'] == 'qrcode' ? 'selected' : '') . '>QRCODE ID Card</option>
              <option value="qrcode_lokasi" ' . ($row_site['tipe_absen_pegawai'] == 'qrcode_lokasi' ? 'selected' : '') . '>QRCODE Lokasi</option>
            </select>
          </div>
        </div>


      <hr>
<h3>PENGATURAN ABSENSI LAYAR/SCREEN</h3>
<p class="text-muted mb-3">
  Pengaturan ini berlaku untuk absensi menggunakan layar/screen (Laptop/PC).
  Anda bisa set berbeda antara <b>Siswa</b> dan <b>Pegawai/Guru</b>.
</p>

<!-- ===================== -->
<!-- SCREEN UNTUK SISWA    -->
<!-- ===================== -->
<div class="form-group row">
  <label class="col-md-2 col-form-label form-control-label">
    Layar Siswa<br>
    <small class="text-muted">Dashboard Screen Siswa</small>
  </label>
  <div class="col-md-6 p-1">
    <select class="form-control valid" name="tipe_absen_layar" required="">
      <option value="qrcode" '.(($row_site['tipe_absen_layar'] ?? 'qrcode')=='qrcode'?'selected':'').'>QRCODE SCANNER</option>
      <option value="rfid" '.(($row_site['tipe_absen_layar'] ?? '')=='rfid'?'selected':'').'>RFID READER</option>
      <option value="qrcode-webcame" '.(($row_site['tipe_absen_layar'] ?? '')=='qrcode-webcame'?'selected':'').'>QRCODE WEBCAM</option>
    </select>
  </div>
</div>

<!-- ===================== -->
<!-- SCREEN UNTUK PEGAWAI  -->
<!-- ===================== -->
<div class="form-group row">
  <label class="col-md-2 col-form-label form-control-label">
    Layar Pegawai<br>
    <small class="text-muted">Dashboard Screen Pegawai/Guru</small>
  </label>
  <div class="col-md-6 p-1">
    <select class="form-control valid" name="tipe_absen_layar_pegawai" required="">
      <option value="qrcode" '.(($row_site['tipe_absen_layar_pegawai'] ?? 'qrcode')=='qrcode'?'selected':'').'>QRCODE SCANNER</option>
      <option value="rfid" '.(($row_site['tipe_absen_layar_pegawai'] ?? '')=='rfid'?'selected':'').'>RFID READER</option>
      <option value="qrcode-webcame" '.(($row_site['tipe_absen_layar_pegawai'] ?? '')=='qrcode-webcame'?'selected':'').'>QRCODE WEBCAM</option>
    </select>
  </div>
</div>
      
      

    </div>
        
  <hr>
    <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
    <button class="btn btn-secondary" type="reset"><i class="fas fa-undo"></i> Reset</button>
</form>';


/** Pengaturan Server Gmail */
}elseif(htmlspecialchars($_GET['id']) == 3){
    echo'
    <form class="form-setting-server" role="form" method="post" action="javascript:void(0)" autocomplete="off">
	<fieldset class="row">
		<div class="col-lg-6">
			<div class="form-group">
				<h4>PENGATURAN SERVER EMAIL</h4>
        <p>Kunjungi Halaman <a href="https://myaccount.google.com/security" target="_blank">myaccount.google.com</a>
       untuk menaktifkan notifikasi Email</p>
			</div>

        <div class="form-group">
            <label>Email/Username</label>
            <input type="email" class="form-control" name="gmail_username" value="'.$gmail_username.'" required>
        </div>

		    <div class="form-group">
		        <label>Password SMTP</label>
		        <input type="text" class="form-control" name="gmail_password" placeholder="Password" value="'.$gmail_password.'" required>
		    </div>

		   	<div class="form-group">
		        <label>Host Mail Server</label>
		        <input type="text" class="form-control" name="gmail_host" value="'.$gmail_host.'" readonly required>
		    </div>

		   	<div class="form-group">
		        <label>Mail Server Port</label>
		        <input type="number" class="form-control col-md-4" name="gmail_port" value="'.$gmail_port.'" readonly required>
		    </div>

        <div class="form-group">
            <label class="custom-toggle custom-toggle-primary">';
              if($gmail_active =='Y'){
                echo'<input type="checkbox" name="gmail_active" value="Y" checked>';
              }else{
                echo'<input type="checkbox" name="gmail_active" value="Y">';
              }
              echo'
              <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
            </label>
            </div>


    	</div>
        <!-- End col-lg-6 -->

        <div class="col-lg-6">
            <div class="form-group">
                <h4>PENGATURAN API LOGIN GOOGLE</h4>
                <p>Kunjungi Halaman <a href="https://console.developers.google.com/" target="_blank">console.cloud.google.com</a>
                untuk menaktifkan login menggunakan Google</p>
            </div>

            <div class="form-group">
                <label>Client ID</label>
                <input type="text" class="form-control" name="google_client_id" value="'.$google_client_id.'">
            </div>

            <div class="form-group">
                <label>Google Secret</label>
                <input type="text" class="form-control" name="google_client_secret" value="'.$google_client_secret.'">
                <label class="custom-toggle custom-toggle-primary mt-3">';
                  if($google_client_active =='Y'){
                    echo'<input type="checkbox" name="google_client_active" value="Y" checked>';
                  }else{
                    echo'<input type="checkbox" name="google_client_active" value="Y">';
                  }
                  echo'
                  <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
                </label>
            </div>

        </div>
    </fieldset>

    <hr>
        <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
        <button class="btn btn-secondary" type="reset"><i class="fas fa-undo"></i> Reset</button>
  </form>';

}
 
/** Pengarutan Api Wa */
elseif(htmlspecialchars($_GET['id']) == 4){
  echo'
  <form class="form-setting-whatsapp" role="form" method="post" action="javascript:void(0)" autocomplete="off">
      <fieldset class="row">
        <div class="col-lg-8">
          <div class="form-group">
            <h4>PENGATURAN API WHATAPP</h4>
          </div>

          <div class="form-group">
              <label>No. WhatsApp</label>
              <input type="text" class="form-control" name="whatsapp_phone" value="'.strip_tags($row_site['whatsapp_phone']??'').'">
          </div>

          <div class="form-group">
              <label>Token/API key <b>Wablas</b> (<a href="https://wablas.com" target="_blank">Wablas</a>)</label>
              <input type="text" class="form-control" name="whatsapp_token" value="'.strip_tags($row_site['whatsapp_token']??'').'">
          </div>

          <div class="form-group">
              <label>Secret Key</label>
              <input type="text" class="form-control" name="secret_key" value="'.strip_tags($row_site['secret_key']??'').'">
              <small>Hanya untuk pengguna wablas.com</span></small>
          </div>

          <div class="form-group">
              <label>Domain Server <b>Wablas</b></label>
              <input type="text" class="form-control" name="whatsapp_domain" value="'.strip_tags($row_site['whatsapp_domain']??'').'">
              <small>Examle:https://kudus.wablas.com/<span class="text-danger">api/v2/send-message</span></small>
          </div>

          <div class="form-group">
            <label>Tipe</label>
            <select class="form-control" name="tipe" required>
              <option value="POST" ';if ($row_site['whatsapp_tipe'] == 'POST') echo 'selected'; echo'>POST</option>
              <option value="GET" ';if ($row_site['whatsapp_tipe'] == 'GET') echo 'selected'; echo'>GET</option>
            </select>
          </div>

          <div class="form-group">
              <label>Template</label>
              <textarea class="form-control template" name="whatsapp_template" rows="10" placeholder="Tulis template di sini...">'.strip_tags($row_site['whatsapp_template']).'</textarea>
          </div>

          <div class="form-group">
              <label>Aktif</label><br>
              <label class="custom-toggle custom-toggle-primary">';
              if($row_site['whatsapp_active'] =='Y'){
                echo'<input type="checkbox" name="whatsapp_active" value="Y" checked>';
              }else{
                echo'<input type="checkbox" name="whatsapp_active" value="Y">';
              }
              echo'
              <span class="custom-toggle-slider rounded-circle" data-label-off="No" data-label-on="Yes"></span>
              </label>
          </div>

          </div>

          <div class="col-lg-4">
            <div class="form-group">
                <h4>Preview</h4>
            </div>

            <div class="card-body bg-secondary">
              <div class="form-group">
                <div class="preview-template"></div>
              </div>
            </div>
          </div>
         
        </fieldset>

        <hr>
        <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
        <button class="btn btn-secondary" type="reset"><i class="fas fa-undo"></i> Reset</button>
    </form>';?>
<script>
$(document).on('change', '.template', function(e) {
    previewTemplate();
});

function previewTemplate() {
    var isi = $(".template").val();

    const dummyData = {
        '{{nama}}': 'Widodo',
        '{{tanggal}}': new Date().toLocaleDateString('id-ID'),
        '{{jam_sekolah}}': '7:00:00',
        '{{jam_absen}}': '7:00:00',
        '{{terlambat_pulang}}': '00:10:00',
        '{{status}}': 'TEPAT WAKTU/TERLAMBAT',
        '{{tipe}}': 'MASUK/PULANG',
        '{{lokasi}}': '-',
    };

    for (const key in dummyData) {
        const regex = new RegExp(key, 'g');
        isi = isi.replace(regex, dummyData[key]);
    }
    isi = isi.replace(/\n/g, '<br>');
    $(".preview-template").html(isi);
}
previewTemplate();
</script>
<?php


/** Profile */
}elseif(htmlspecialchars($_GET['id'])== 5){
echo'
<form class="form-profile" role="form" method="post" action="javascript:void(0)" autocomplete="off">

    <div class="form-group">
        <h4>PROFIL PERUSAHAAN</h4>
    </div>

    <div class="form-group row">
      <label class="col-md-2 col-form-label form-control-label">Nama Perusahaan</label>
      <div class="col-md-6">
        <input type="text" class="form-control" name="site_company"  value="'.strip_tags($row_site['site_company']??'').'" required>
      </div>
    </div>

    <div class="form-group row">
      <label class="col-md-2 col-form-label form-control-label">Nama Pimpinan</label>
      <div class="col-md-6">
        <input type="text" class="form-control" name="site_pimpinan"  value="'.strip_tags($row_site['site_pimpinan']??'').'" required>
      </div>
    </div>

    <div class="form-group row">
      <label class="col-md-2 col-form-label form-control-label">Kota</label>
      <div class="col-md-6">
        <input type="text" class="form-control" name="site_kota"  value="'.strip_tags($row_site['site_kota']??'').'" required>
      </div>
    </div>

    <div class="form-group row">
        <label class="col-md-2 col-form-label form-control-label">Kop</label>
      <div class="col-md-6">
          <div class="card m-0" style="border:solid 1px #eee;">
              <div class="card-body text-center">';
              if(file_exists("../../../sw-content/$site_kop")){
                echo'
                <img src="../sw-content/'.$site_kop.'" class="img-responsive img-logo" height="40">';
              }else{
                echo'<img src="./sw-assets/img/media.png" class="img-responsive img-logo" height="40">';
              }
              echo'
              </div>
              <div class="card-footer">
              <div class="input-group-prepend">
                <button class="btn btn-outline-primary btn-file btn-block"><i class="fa fa-refresh"></i> Ubah Kop <input type="file" class="upload kop" name="file" accept=".jpg, .jpeg, ,gif, .png">
                </button>
              </div>
              </div>
          </div>
      </div>
    </div>
      
    <hr>

    <button class="btn btn-primary btn-save" type="submit"><i class="far fa-save"></i> Simpan</button>
    <button class="btn btn-secondary" type="reset"><i class="fas fa-undo"></i> Reset</button>
  </form>';


/** Backup  */
}elseif(htmlspecialchars($_GET['id']) == 6){
echo'
      <div class="form-group">
          <h4>BACKUP APLIKASI ABSENSI</h4>
      </div>

      <div class="form-group row mb-2">
        <label class="col-md-2 col-form-label form-control-label">Backup Database</label>
        <div class="col-md-6">
          <a href="javascript:;" class="btn btn-primary btn-backup" data-tipe="database"><i class="fas fa-database"></i> Backup Datatabse</a>
        </div>
      </div>
      
      <hr>
      <form method="post" class="backupForm" action="javascript:;">
        <div class="form-group">
          <label>Backup Folder<br>
            <small class="text-muted">Backup Beberapa Data Foto</small>
          </label>

          <div class="custom-control custom-checkbox mb-3">
            <input class="custom-control-input" id="customCheck1" type="checkbox" name="folders[]" value="absen" checked>
            <label class="custom-control-label" for="customCheck1">Data Absensi</label>
          </div>

          <div class="custom-control custom-checkbox mb-3">
            <input class="custom-control-input" id="customCheck2" type="checkbox" name="folders[]" value="qrcode" checked>
            <label class="custom-control-label" for="customCheck2">Data QRCode</label>
          </div>

        </div>

        <div class="form-group">
          <div class="status-backup"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-md">Backup Sekarang</button>
    </form>';

}

}