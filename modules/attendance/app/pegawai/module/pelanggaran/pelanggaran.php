<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{ 
$notifikasi = "UPDATE notifikasi SET status='Y' WHERE tipe='admin' AND tujuan='pegawai' AND pegawai_id='" . mysqli_real_escape_string($connection, $data_user['pegawai_id']) . "'";
$connection->query($notifikasi);



echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">
            <div class="card shadow-default s-widodo.com">
                <div class="card-body s-widodo.com">

                    <div class="row input-daterange datepicker-filter align-items-center s-widodo.com">
                        <div class="col-md-6 s-widodo.com">
                            <select class="form-control siswa search mb-1 mt-1" required>
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
                            <select class="form-control bulan search mb-1 mt-1" required>';
                                $bulan_nama =array(1=>"Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember");
                                for($bulan=1; $bulan<=12; $bulan++){
                                    if($bulan<=$month ) {
                                    echo'<option value="'.$bulan.'" selected>'.$bulan_nama[$bulan].'</option>';
                                    }else { 
                                    echo'<option value="'.$bulan.'">'.$bulan_nama[$bulan].'</option>'; 
                                    }
                                }
                            echo'
                            </select>
                        </div>
                    
                    </div>
                </div>
            </div>
        </div>

        <div class="container mb-4 s-widodo.com">
            <div class="s-widodo.com">
            <div class="row mb-2">
                <div class="col">
                    <h7 class="subtitle s-widodo.com">Top 4 Pelanggaran Siswa</h7>
                </div>
                
                <div class="col-auto">
                    <a href="javascript:;" class="float-right small more-expand-btn">View All</a>
                </div>
            </div>
            
            <div class="load-pelanggaran postList s-widodo.com"></div>
        </div>
    </div>


    <div class="btn-floating s-widodo.com">
        <a href="./input-pelanggaran" class="btn btn-primary s-widodo.com" style="line-height:50px"><span class="material-icons s-widodo.com">add_circle</span></a>
        <button type="submit" class="btn btn-warning btn-print s-widodo.com text-white"><span class="material-icons s-widodo.com">print</span></button>
    </div>

</main>';
}?>