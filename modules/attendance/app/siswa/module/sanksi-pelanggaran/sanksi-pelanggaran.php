<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:../404');
}else{ 
$notifikasi = "UPDATE notifikasi SET status='Y' WHERE tipe='pegawai' AND tujuan='siswa' AND user_id='" . mysqli_real_escape_string($connection, $data_user['user_id']) . "'";
$connection->query($notifikasi);
echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container mb-4 s-widodo.com">
            <div class="card shadow-default s-widodo.com">
                <div class="card-body s-widodo.com">
        
                    <div class="row input-daterange datepicker-filter align-items-center s-widodo.com">
                        <div class="col-md-12 s-widodo.com">
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
            <div class="load-sanksi postList s-widodo.com"></div>
        </div>
    </div>

</main>';
}?>