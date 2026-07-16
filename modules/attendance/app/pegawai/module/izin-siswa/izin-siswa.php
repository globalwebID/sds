<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{
    
$notifikasi = "UPDATE notifikasi SET status='Y' 
                WHERE link='izin' 
                AND tipe='siswa'
                AND tujuan='pegawai' 
                AND pegawai_id='".mysqli_real_escape_string($connection, $data_user['pegawai_id']) . "'";
$connection->query($notifikasi);

$tanggal_awal = date('d-m-Y');
$tanggal = DateTime::createFromFormat('d-m-Y', $tanggal_awal);
$tanggal->modify('-6 days');

echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">
            <div class="card shadow-default s-widodo.com">
                <div class="card-body s-widodo.com">
                    <div class="row input-daterange datepicker-filter align-items-center s-widodo.com">
                        <div class="col-md-6 s-widodo.com">
                            <select class="form-control siswa mb-1 mt-1" required>
                                <option value="">Semua Siswa</option>';
                                $query_siswa = "SELECT user_id,nama_lengkap FROM user WHERE kelas='$data_user[wali_kelas]' ORDER BY nama_lengkap ASC";
                                $result_siswa = $connection->query($query_siswa);
                                if($result_siswa->num_rows > 0) {
                                    while($data_siswa = $result_siswa->fetch_assoc()){
                                        echo'<option value="'.convert("encrypt",$data_siswa['user_id']).'">'.strip_tags($data_siswa['nama_lengkap']??'-').'</option>';
                                    }
                                }else{
                                    echo'<option value="">Data tidak ditemukan</option>';
                                }
                                echo'
                            </select>
                        </div>

                        <div class="col-md-6 s-widodo.com">
                                <div class="form-group position-relative mb-1 mt-1 s-widodo.com">
                                <div class="bottom-left s-widodo.com">
                                    <a href="#" class="btn btn-sm btn-link text-secondary btn-40 rounded text-mute s-widodo.com"><i class="material-icons">calendar_month</i></a>
                                </div>

                                <input type="text" class="form-control tanggal s-widodo.com search" placeholder="To" value="'.tanggal_ind($date).'">
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-4 s-widodo.com">
            <div class="load-izin postList s-widodo.com"></div>
        </div>
    </div>

    <div class="btn-floating s-widodo.com">
        <button type="submit" class="btn btn-warning btn-print s-widodo.com text-white"><span class="material-icons s-widodo.com">print</span></button>
    </div>

</main>';
}?>