<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{

switch(@$_GET['op']){ 
default:
function getAbsensiSiswa($date, $guru_id, $kelas, $jadwal_id, $connection) {
    $query_siswa = "SELECT COUNT(*) AS total_siswa 
                    FROM user
                    WHERE kelas= '$kelas'";
    $result_siswa = $connection->query($query_siswa);
    $row_siswa = $result_siswa->fetch_assoc();
    $total_siswa = $row_siswa['total_siswa'];

    $query_absen = "SELECT 
            SUM(CASE WHEN keterangan = 'H' THEN 1 ELSE 0 END) AS total_hadir,
            SUM(CASE WHEN (keterangan = '' OR keterangan IS NULL) THEN 1 ELSE 0 END) AS total_belum
        FROM absen_ekbm
        WHERE tanggal = '$date' 
          AND pegawai = '$guru_id' 
          AND jadwal_id = '$jadwal_id'";
    $result_absen = $connection->query($query_absen);
    $row_absen = $result_absen->fetch_assoc();

    $total_hadir = $row_absen['total_hadir'] ?? 0;
    $total_belum = $row_absen['total_belum'] ?? 0;

    return [
        'total_siswa' => (int)$total_siswa,
        'total_hadir' => (int)$total_hadir,
        'total_belum' => (int)$total_belum
    ];
}

echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="container mt-2 mb-4 text-center">
        <h4 class="text-white">'.strip_tags($data_user['nama_lengkap']).'</h4>
        <div class="text-white">
            NIP. '.$data_user['nip'].'
        </div>
    </div>

    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">
            <h6 class="subtitle mb-0 mt-2">Jadwal<br><small>'.format_hari_tanggal($date).'</small></h6>
            <div class="row text-center mt-3">';
                $query_jadwal=mysqli_query($connection, "SELECT jadwal_mengajar.*,mata_pelajaran.nama_mapel FROM jadwal_mengajar 
                LEFT JOIN mata_pelajaran ON jadwal_mengajar.mata_pelajaran = mata_pelajaran.id
                WHERE pegawai='$data_user[pegawai_id]' AND hari='".$hari_ini."' ORDER BY dari_jam ASC");
                if(mysqli_num_rows($query_jadwal) > 0){
                    while($data_jadwal=mysqli_fetch_array($query_jadwal)){

                        $data_absensi = getAbsensiSiswa($date, $data_user['pegawai_id'], $data_jadwal['kelas'], $data_jadwal['jadwal_id'], $connection);

                        $active = ($data_absensi && $data_absensi['total_hadir'] > 0) ? 'bg-default-light' : '';
                        echo'
                        <div class="col-6 col-md-3">
                            <div class="card border-0 mb-4 '.$active.'">
                                <div class="card-body">
                                    
                                    <p class="mt-3 mb-0 font-weight-normal">'.$data_jadwal['nama_mapel'].'</p>
                                    <p class="mb-0"><span class="badge badge-primary text-white">'.strip_tags($data_jadwal['kelas']).'</span></p>
                                    <p class="small mb-0">'.substr($data_jadwal['dari_jam'],0,5).' - '.substr($data_jadwal['sampai_jam'],0,5).'</p>
                                    
                                    <p class="mb-0 pb-0 mt-0 small">Siswa: '.($data_absensi['total_siswa']??'0').' | Hadir:'.($data_absensi['total_hadir']??'0').'</p>
                                    <a href="'.$mod.'&op=absen&id='.convert('encrypt',$data_jadwal['jadwal_id']).'" class="mt-2 btn btn-outline-primary btn-sm">Absen</a>
                                </div>
                            </div>
                        </div>';
                    }

                }else{
                    echo'   
                    <div class="col-12">
                        <div class="alert alert-secondary mt-3">Tidak ada jadwal mengajar hari ini!</div>
                    </div>';
                }
            echo'
            </div>
        </div>
    </div>
</main>';


break;
case 'absen':
if(!empty($_GET['id'])){
$id       = anti_injection(convert("decrypt",$_GET['id']));
$query_jadwal = mysqli_query($connection, "SELECT jadwal_mengajar.*,mata_pelajaran.nama_mapel FROM jadwal_mengajar 
LEFT JOIN mata_pelajaran ON jadwal_mengajar.mata_pelajaran = mata_pelajaran.id
WHERE pegawai='$data_user[pegawai_id]' AND hari='".$hari_ini."' ORDER BY dari_jam ASC");
    if(mysqli_num_rows($query_jadwal) > 0){
    $data_jadwal = mysqli_fetch_array($query_jadwal);

    $query_siswa ="SELECT user_id,nama_lengkap,nisn FROM user WHERE kelas='$data_jadwal[kelas]' ORDER BY nama_lengkap ASC";
    $result_siswa = $connection->query($query_siswa);
    

    echo'
    <main class="flex-shrink-0 main has-footer s-widodo.com">
        <div class="main-container s-widodo.com">
            <div class="container mb-4 s-widodo.com">
                <div class="row">
                    <div class="col">
                        <h6 class="subtitle mb-0 mt-2">Absensi E-KBM<br><small>'.format_hari_tanggal($date).'</small></h6>
                    </div>
                    <div class="col-auto">
                        <a href="'.$mod.'" class="btn btn-light btn-sm rounded"><i class="fas fa-undo"></i> Kembli</a>
                    </div>
                </div>

                <div class="card border-0 mt-4 mb-3 text-center">
                    <div class="card-body">
                        <h6 class="mb-0 font-weight-normal">'.$data_jadwal['nama_mapel'].'</h6>
                        <p class="text-secondary mb-1"><span class="badge badge-primary text-white">'.strip_tags($data_jadwal['kelas']).'</span></p>
                        <p class="text-secondary small mb-0">'.substr($data_jadwal['dari_jam'],0,5).' - '.substr($data_jadwal['sampai_jam'],0,5).'</p>
                    </div>
                </div>';
                if($result_siswa->num_rows > 0){
                while($data_siswa = $result_siswa->fetch_assoc()){
                     $active = NULL;
                    $cek_kehadiran = "SELECT absen_id, keterangan FROM absen_ekbm 
                    WHERE user_id='$data_siswa[user_id]' AND pegawai='$data_user[pegawai_id]' AND jadwal_id='$data_jadwal[jadwal_id]' AND tanggal='$date'";
                    $result_kehadiran = $connection->query($cek_kehadiran);
                    $checked_h = $checked_a = $checked_i = $checked_s = $checked_n = '';
                    if ($result_kehadiran->num_rows > 0) {
                        $data_kehadiran = $result_kehadiran->fetch_assoc();
                        $keterangan = $data_kehadiran['keterangan'];
                        switch ($keterangan) {
                            case 'H':
                                $checked_h = 'checked';
                                $active = 'alert-info';
                                break;
                            case 'A':
                                $checked_a = 'checked';
                                $active = 'alert-danger';
                                break;
                            case 'I':
                                $checked_i = 'checked';
                                $active = 'alert-warning';
                                break;
                            case 'S':
                                $checked_s = 'checked';
                                $active = 'alert-warning';
                                break;
                            case NULL:
                                $checked_n = 'checked';
                                break;
                        }
                    }
                    
                    echo'
                    <div class="card border-0 mb-2 card-active'.$data_siswa['user_id'].' '.$active.'">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col align-self-center">
                                    <h6 class="mb-1">'.strip_tags($data_siswa['nama_lengkap']??'-').'</h6>
                                    <p class="small text-secondary">NISN. '.strip_tags($data_siswa['nisn']??'-').'</p>
                                </div>
                                <div class="col-auto pl-0">
                                
                                    <div class="radio-button d-inline">
                                        <label for="rdo-1'.$data_siswa['user_id'].'" class="btn-radio">
                                            <input type="radio" id="rdo-1'.$data_siswa['user_id'].'" data-keterangan="H" name="keterangan'.$data_siswa['user_id'].'" data-id="'.$data_siswa['user_id'].'" data-jadwal="'.convert("encrypt",$data_jadwal['jadwal_id']).'" class="btn-absen" '.$checked_h.'>
                                            <svg width="20px" height="20px" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="9"></circle>
                                                <path d="M10,7 C8.34314575,7 7,8.34314575 7,10 C7,11.6568542 8.34314575,13 10,13 C11.6568542,13 13,11.6568542 13,10 C13,8.34314575 11.6568542,7 10,7 Z" class="inner"></path>
                                                <path d="M10,1 L10,1 L10,1 C14.9705627,1 19,5.02943725 19,10 L19,10 L19,10 C19,14.9705627 14.9705627,19 10,19 L10,19 L10,19 C5.02943725,19 1,14.9705627 1,10 L1,10 L1,10 C1,5.02943725 5.02943725,1 10,1 L10,1 Z" class="outer"></path>
                                            </svg>
                                            <span>H</span>
                                        </label>

                                        <label for="rdo-2'.$data_siswa['user_id'].'" class="btn-radio">
                                            <input type="radio" id="rdo-2'.$data_siswa['user_id'].'" data-keterangan="A" name="keterangan'.$data_siswa['user_id'].'" data-id="'.$data_siswa['user_id'].'" data-jadwal="'.convert("encrypt",$data_jadwal['jadwal_id']).'" class="btn-absen" '.$checked_a.'>
                                            <svg width="20px" height="20px" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="9"></circle>
                                                <path d="M10,7 C8.34314575,7 7,8.34314575 7,10 C7,11.6568542 8.34314575,13 10,13 C11.6568542,13 13,11.6568542 13,10 C13,8.34314575 11.6568542,7 10,7 Z" class="inner"></path>
                                                <path d="M10,1 L10,1 L10,1 C14.9705627,1 19,5.02943725 19,10 L19,10 L19,10 C19,14.9705627 14.9705627,19 10,19 L10,19 L10,19 C5.02943725,19 1,14.9705627 1,10 L1,10 L1,10 C1,5.02943725 5.02943725,1 10,1 L10,1 Z" class="outer"></path>
                                            </svg>
                                            <span>A</span>
                                        </label>

                                        <label for="rdo-3'.$data_siswa['user_id'].'" class="btn-radio">
                                            <input type="radio" id="rdo-3'.$data_siswa['user_id'].'" data-keterangan="I" name="keterangan'.$data_siswa['user_id'].'" data-id="'.$data_siswa['user_id'].'" data-jadwal="'.convert("encrypt",$data_jadwal['jadwal_id']).'" class="btn-absen" '.$checked_i.'>
                                            <svg width="20px" height="20px" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="9"></circle>
                                                <path d="M10,7 C8.34314575,7 7,8.34314575 7,10 C7,11.6568542 8.34314575,13 10,13 C11.6568542,13 13,11.6568542 13,10 C13,8.34314575 11.6568542,7 10,7 Z" class="inner"></path>
                                                <path d="M10,1 L10,1 L10,1 C14.9705627,1 19,5.02943725 19,10 L19,10 L19,10 C19,14.9705627 14.9705627,19 10,19 L10,19 L10,19 C5.02943725,19 1,14.9705627 1,10 L1,10 L1,10 C1,5.02943725 5.02943725,1 10,1 L10,1 Z" class="outer"></path>
                                            </svg>
                                            <span>I</span>
                                        </label>

                                        <label for="rdo-5'.$data_siswa['user_id'].'" class="btn-radio">
                                            <input type="radio" id="rdo-5'.$data_siswa['user_id'].'" data-keterangan="S" name="keterangan'.$data_siswa['user_id'].'" data-id="'.$data_siswa['user_id'].'" data-jadwal="'.convert("encrypt",$data_jadwal['jadwal_id']).'" class="btn-absen" '.$checked_s.'>
                                            <svg width="20px" height="20px" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="9"></circle>
                                                <path d="M10,7 C8.34314575,7 7,8.34314575 7,10 C7,11.6568542 8.34314575,13 10,13 C11.6568542,13 13,11.6568542 13,10 C13,8.34314575 11.6568542,7 10,7 Z" class="inner"></path>
                                                <path d="M10,1 L10,1 L10,1 C14.9705627,1 19,5.02943725 19,10 L19,10 L19,10 C19,14.9705627 14.9705627,19 10,19 L10,19 L10,19 C5.02943725,19 1,14.9705627 1,10 L1,10 L1,10 C1,5.02943725 5.02943725,1 10,1 L10,1 Z" class="outer"></path>
                                            </svg>
                                            <span>S</span>
                                        </label>

                                        <label for="rdo-4'.$data_siswa['user_id'].'" class="btn-radio">
                                            <input type="radio" id="rdo-4'.$data_siswa['user_id'].'" data-keterangan="N" name="keterangan'.$data_siswa['user_id'].'" data-id="'.$data_siswa['user_id'].'" data-jadwal="'.convert("encrypt",$data_jadwal['jadwal_id']).'" class="btn-absen" '.$checked_n.'>
                                            <svg width="20px" height="20px" viewBox="0 0 20 20">
                                                <circle cx="10" cy="10" r="9"></circle>
                                                <path d="M10,7 C8.34314575,7 7,8.34314575 7,10 C7,11.6568542 8.34314575,13 10,13 C11.6568542,13 13,11.6568542 13,10 C13,8.34314575 11.6568542,7 10,7 Z" class="inner"></path>
                                                <path d="M10,1 L10,1 L10,1 C14.9705627,1 19,5.02943725 19,10 L19,10 L19,10 C19,14.9705627 14.9705627,19 10,19 L10,19 L10,19 C5.02943725,19 1,14.9705627 1,10 L1,10 L1,10 C1,5.02943725 5.02943725,1 10,1 L10,1 Z" class="outer"></path>
                                            </svg>
                                            <span>N</span>
                                        </label>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>';
                }}else{
                    echo'<div class="alert alert-secondary mt-3">Tidak ada data Siswa!</div>';
                }
                echo'
                <div class="card border-0 mt-3 mb-4">
                    <div class="card-body">
                        <h6>Keterangan</h6>
                        <p>H : Hadir dalam kelas<br>
                            A : Tidak masuk tanpa keterangan<br>
                            I : Tidak masuk ada surat ijin atau pemberitahuan<br>
                            S : Tidak masuk ada surat sakit atau pemberitahuan<br>
                            N : Belum ada keterangan absensi
                        </p>
                    </div>
                </div>
                    
                <div class="text-center mt-3">
                    <a href="'.$mod.'" class="btn btn-light rounded"><i class="fas fa-undo"></i> Kembli</a>
                </div>
            </div>  
        </div>
    </main>';
    }else{
        echo'   
        <div class="col-12">
            <div class="alert alert-secondary mt-3">Tidak ada jadwal mengajar hari ini!</div>
        </div>';
    }
}
}

}?>