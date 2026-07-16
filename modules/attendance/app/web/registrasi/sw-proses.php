<?php
require_once'../../sw-library/sw-config.php';
require_once'../../sw-library/sw-function.php';
$iB 	= getBrowser();
$browser = $iB['name'].' '.$iB['version'];
$ip 	= $_SERVER['REMOTE_ADDR'];
switch (@$_GET['action']){
case 'form':

  if (isset($_GET['id']) && $_GET['id'] == '1') {
    // Siswa
    echo'
    <form class="form-siswa" action="javascript:void(0);" autocomplete="off">
        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">NISN</label>
                <input type="number" min="0" class="form-control" name="nisn" required>
            </div>
        </div>

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" required>
            </div>
        </div>

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">Kelas</label>
                <select class="form-control" name="kelas" required>';
                    $query_kelas = "SELECT * FROM absensi_kelas WHERE parent_id != 0 ORDER BY nama_kelas ASC";
                    $result_kelas = $connection->query($query_kelas);
                    while ($data_kelas = $result_kelas->fetch_assoc()) {
                        echo'<option value="'.$data_kelas['nama_kelas'].'">'.$data_kelas['nama_kelas'].'</option>';
                    }
                    echo'
                </select>
            </div>
        </div>


        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">Lokasi/Tempat Sekolah</label>
                <select class="form-control" name="lokasi" required>';
                    $query_lokasi ="SELECT lokasi_id,lokasi_nama FROM lokasi ORDER BY lokasi_nama ASC";
                    $result_lokasi = $connection->query($query_lokasi);
                    while ($data_lokasi = $result_lokasi->fetch_assoc()) {
                        echo'<option value="'.$data_lokasi['lokasi_id'].'">'.$data_lokasi['lokasi_nama'].'</option>';
                    }
                    echo'
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">E-mail</label>
                <input type="email" class="form-control"  name="email" required>
            </div>
        </div>

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label" for="password1">Password</label>
                <input type="password" class="form-control password" id="password" name="password" placeholder="Passworb baru" required>
            </div>
        </div>
        <div class="form-group">
            <input type="checkbox" id="showPassword">
            <label for="showPassword" class="checkbox-label">Tampilkan Password</label>
        </div>

      <hr>
      <div class="form-group mt-3">
          <button type="submit" class="btn btn-default rounded btn-block btn-login">Mendaftar</button>
      </div>
  </form>';

}elseif (isset($_GET['id']) && $_GET['id'] == '2') {
    // Pegawai
    echo'
    <form class="form-pegawai" action="javascript:void(0);" autocomplete="off">
        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">NIP</label>
                <input type="number" min="0" class="form-control" name="nip" required>
            </div>
        </div>

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" required>
            </div>
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
            <label class="form-control-label">Walli Kelas</label>
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
            <div class="input-wrapper">
                <label class="label">Lokasi/Tempat Sekolah</label>
                <select class="form-control" name="lokasi" required>';
                    $query_lokasi ="SELECT lokasi_id,lokasi_nama FROM lokasi ORDER BY lokasi_nama ASC";
                    $result_lokasi = $connection->query($query_lokasi);
                    while ($data_lokasi = $result_lokasi->fetch_assoc()) {
                        echo'<option value="'.$data_lokasi['lokasi_id'].'">'.$data_lokasi['lokasi_nama'].'</option>';
                    }
                    echo'
                </select>
            </div>
        </div>

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">E-mail</label>
                <input type="email" class="form-control"  name="email" required>
            </div>
        </div>

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label" for="password1">Password</label>
                <input type="password" class="form-control password" id="password" name="password" placeholder="Passworb baru" required>
            </div>
        </div>
        <div class="form-group">
            <input type="checkbox" id="showPassword">
            <label for="showPassword" class="checkbox-label">Tampilkan Password</label>
        </div>

      <hr>
      <div class="form-group mt-3">
          <button type="submit" class="btn btn-default rounded btn-block btn-login">Mendaftar</button>
      </div>
  </form>';

  }else{
    //Wali Murid
    echo'
    <form class="form-walimurid" action="javascript:void(0);" autocomplete="off">

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">Nama Lengkap</label>
                <input type="text" class="form-control" name="nama_lengkap" required>
            </div>
        </div>

    
        <h6>Biodata Siswa</h6>
        <div class="form-group">
            <label class="label" for="text4">NISN</label>
            <input type="number" class="form-control nisn" min="" name="nisn" required>
            <small class="result-output"></small>
        </div>

        <div class="form-group">
            <label class="form-control-label">Nama Siswa</label>
            <input type="text" class="form-control nama_siswa" name="nama_siswa" readonly required>
        </div>

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label">E-mail</label>
                <input type="email" class="form-control"  name="email" required>
            </div>
        </div>

        <div class="form-group">
            <div class="input-wrapper">
                <label class="label" for="password1">Password</label>
                <input type="password" class="form-control password" id="password" name="password" placeholder="Passworb baru" required>
            </div>
        </div>
        <div class="form-group">
            <input type="checkbox" id="showPassword">
            <label for="showPassword" class="checkbox-label">Tampilkan Password</label>
        </div>

      <hr>
      <div class="form-group mt-3">
          <button type="submit" class="btn btn-default rounded btn-block btn-login">Mendaftar</button>
      </div>
  </form>';?>
  <script>
    
$(document).ready(function(){
    var debounceTimeout;
    var app = {
        check: function(){
            clearTimeout(debounceTimeout);  // reset timer setiap keyup
            debounceTimeout = setTimeout(function(){
                var nisnVal = $(".nisn").val();
                $.ajax({
                    url: "./web/registrasi/sw-proses.php?action=cek-nisn",
                    method: "POST",
                    data: {nisnVal: nisnVal},
                    dataType: "json",
                    success: function(response){
                        if(response.status === "success"){
                            $(".nama_siswa").val(response.data.nama_lengkap);
                            $(".result-output").html('Siswa: <span class="text-primary">'+response.data.nama_lengkap+'</span>').fadeIn("slow");
                        } else {
                            $(".nama_siswa").val('');
                             $(".result-output").html('<span class="text-danger">'+response.message+'</span>').fadeIn("slow");
                        }
                    },
                    error: function(){
                        $(".nama_siswa").val('');
                        $(".result-output").html('<span class="text-danger">Terjadi kesalahan server</span>').fadeIn("slow");
                    }
                });
            }, 500);
        }
    }

    $(".nisn").keyup(app.check);
});
  </script>
  

<?php }



break;
case'add-siswa':
$error = array();

 $fields = [
    'nisn' => 'NISN tidak boleh kosong',
    'nama_lengkap' => 'Nama Lengkap tidak boleh kosong',
    'email' => 'Email tidak boleh kosong',
    'password' => 'Password tidak boleh kosong',
    'lokasi' => 'Lokasi Kerja tidak boleh kosong',
    'kelas' => 'Kelas tidak boleh kosong',
];

  // Validasi kosong
  foreach ($fields as $key => $errorMessage) {
      if (empty($_POST[$key])) {
          die($errorMessage);
      }
  }

  foreach ($fields as $key => $msg) {
    if (empty($_POST[$key])) {
      $error[] = $msg;
    } else {
      $$key = $key === 'nama_lengkap'
        ? mysqli_real_escape_string($connection, $_POST[$key])
        : ($key === 'tanggal_lahir'
          ? date('Y-m-d', strtotime($_POST[$key]))
          : anti_injection($_POST[$key]));
    }
  }


  if (!validasiNama($_POST['nama_lengkap'])) {
    $error[] = 'Nama tidak valid! Hanya huruf dan angka yang diperbolehkan';
  }

  if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error[] = "Email yang Anda masukan tidak valid";
  }

  if (!empty($password)) {
    $password = password_hash($password, PASSWORD_DEFAULT);
  }
  
  if (empty($error)) {
        $cek = $connection->query("SELECT nisn, email FROM user WHERE nisn='$nisn' OR email='$email' LIMIT 1");
        if ($cek->num_rows > 0) {
            $row = $cek->fetch_assoc();
            if ($row['nisn'] == $nisn) {
                die('NISN sudah terdaftar!');
            }
            if ($row['email'] == $email) {
                die('Email sudah terdaftar!');
            }
        }
      $add ="INSERT INTO user(
            nisn,
            email,
            password,
            nama_lengkap,
            kelas,
            lokasi,
            tanggal_registrasi,
            tanggal_login,
            ip,
            browser,
            status) values(
            '$nisn',
            '$email',
            '$password',
            '$nama_lengkap',
            '$kelas',
            '$lokasi',
            '$date $time',
            '$date $time',
            '$ip',
            '$browser',
            'Offline')";
    if($connection->query($add) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        echo'success';
    }
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}



break;
case'add-pegawai':
$error = [];
$data = [];

$fields = [
    'nip' => 'NIP tidak boleh kosong',
    'nama_lengkap' => 'Nama Lengkap tidak boleh kosong',
    'email' => 'Email tidak boleh kosong',
    'password' => 'Password tidak boleh kosong',
    'lokasi' => 'Lokasi Kerja tidak boleh kosong',
    'jabatan' => 'Jabatan tidak boleh kosong',
    'wali_kelas' => 'Wali Kelas tidak boleh kosong',
];


// Validasi kosong & sanitasi input
foreach ($fields as $key => $msg) {
    if (empty($_POST[$key])) {
        $error[] = $msg;
    } else {
        // Simpan data yang telah disanitasi
        $data[$key] = mysqli_real_escape_string($connection, trim($_POST[$key]));
    }
}

  
    // Validasi email
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $error[] = "Email yang Anda masukkan tidak valid";
    }


  if (!empty($data['password'])) {
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
  }

  $qrcode   = strtoupper(substr(hash('sha1', $data['nama_lengkap']), 0, 10)); 
  
  if (empty($error)) {
    $cek = $connection->query("SELECT nip, email FROM pegawai WHERE nip='{$data['nip']}' OR email='{$data['email']}' LIMIT 1");
    if ($cek->num_rows > 0) {
        $row = $cek->fetch_assoc();
        if ($row['nip'] == $data['nip']) {
            die('NIP sudah terdaftar!');
        }
        if ($row['email'] == $data['email']) {
            die('Email sudah terdaftar!');
        }
    }

        $add = "INSERT INTO pegawai (nip, 
            qrcode,
            nama_lengkap, 
            email, 
            password,
            jabatan,
            wali_kelas,
            lokasi,
            tanggal_registrasi,
            tanggal_login,
            ip,
            browser,
            status) VALUES (
            '{$data['nip']}',
            '$qrcode',
            '{$data['nama_lengkap']}',
            '{$data['email']}',
            '$password',
            '{$data['jabatan']}',
            '{$data['wali_kelas']}',
            '{$data['lokasi']}',
            '$date $time',
            '$date $time',
            '$ip',
            '$browser',
            'Offline')";
    if($connection->query($add) === false) { 
        die($connection->error.__LINE__); 
        echo'Data tidak berhasil disimpan!';
    } else{
        echo'success';
    }
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}


break;

case 'cek-nisn':
header('Content-Type: application/json');
if (isset($_POST['nisnVal'])) {
    $nisnVal = anti_injection($_POST['nisnVal']);
    $query_siswa = "SELECT nisn, nama_lengkap FROM user WHERE nisn='$nisnVal' LIMIT 1";
    $result_siswa = $connection->query($query_siswa);
    if ($result_siswa && $result_siswa->num_rows > 0) {
        $data_siswa = $result_siswa->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'data' => $data_siswa
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Data tidak ditemukan'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Parameter tidak ditemukan'
    ]);
}


break;
case'add-walimurid':

  $fields = [
    'nama_lengkap'   => 'Nama Lengkap',
    'email'          => 'Email',
    'password'       => 'Password',
    'nisn'           => 'NISN',
    'nama_siswa'     => 'Nama Siswa',
  ];

  foreach ($fields as $key => $label) {
    if (empty($_POST[$key])) {
        $error[] = "$label tidak boleh kosong";
    } else {
        // khusus nama_lengkap -> real_escape_string
        if ($key === 'nama_lengkap') {
            $$key = mysqli_real_escape_string($connection, $_POST[$key]);
        }
        // khusus email
        elseif ($key === 'email') {
            $email = anti_injection($_POST[$key]);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error[] = "Format email tidak valid";
            }
            $$key = $email;
        }
        // default untuk field lainnya
        else {
            $$key = anti_injection($_POST[$key]);
        }
    }
  }

  if (empty($error)){
      $query_siswa = "SELECT nisn, nama_lengkap FROM user WHERE nisn='$nisn' LIMIT 1";
      $result_siswa = $connection->query($query_siswa);
      if ($result_siswa && $result_siswa->num_rows > 0) {
          $data_siswa = $result_siswa->fetch_assoc();
          $nama_siswa =  anti_injection($data_siswa['nama_lengkap']);
      }else{
        die("Data siswa tidak ditemukan, Cek kembali NISN siswa!");
      }

    // Cek apakah email sudah terdaftar di pegawai
    $query_email = "SELECT email FROM wali_murid WHERE email = '$email' LIMIT 1";
    $result_email = $connection->query($query_email);
    if ($result_email && $result_email->num_rows > 0) {
        die("Email sudah terdaftar!");
    }

    $add ="INSERT INTO wali_murid(nama_lengkap,
            email,
            password,
            nisn,
            nama_siswa,
            tanggal_registrasi,
            tanggal_login,
            ip,
            browser,
            status) values('$nama_lengkap',
            '$email',
            '$password',
            '$nisn',
            '$nama_siswa',
            '$date $time',
            '$date $time',
            '$ip',
            '$browser',
            'Offline')";
      if($connection->query($add) === false) { 
          die($connection->error.__LINE__); 
          echo'Data tidak berhasil disimpan!';
      } else{
          echo'success';
      }
}else{           
  foreach ($error as $key => $values) {            
    echo"$values\n";
  }
}

break;
}