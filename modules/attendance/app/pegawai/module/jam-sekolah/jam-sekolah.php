<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{
$query_jam_sekolah = mysqli_query($connection, "SELECT * FROM jam_sekolah WHERE tipe='guru' and active='Y' ORDER BY jam_sekolah_id ASC");

echo'
<main class="flex-shrink-0 main has-footer">  
    <div class="main-container">
    <div class="container mb-4">
        <div class="row mb-3">
            <div class="col">
                <h6 class="subtitle mb-0 mt-2">Jam Bekerja</h6>
            </div>
        </div>
        <hr>';
        while($data_jam_sekolah = mysqli_fetch_array($query_jam_sekolah)){
           echo'
           <div class="card border-0 mb-1">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-12 align-self-center">
                            <p class="mb-0">'.$data_jam_sekolah['hari'].'</p>
                            <hr class="mt-1 mb-1">
                        </div>

                        <div class="col-4 align-self-center">
                            <p class="mb-0">Masuk</p>
                            <p class="small text-secondary">'.$data_jam_sekolah['jam_masuk'].'</p>
                        </div>
                        <div class="col-4 align-self-center border-left">
                            <p class="mb-0">Toleransi</p>
                            <p class="small text-secondary">'.$data_jam_sekolah['jam_telat'].'</p>
                        </div>

                        <div class="col-auto align-self-center border-left">
                            <p class="mb-0">Pulang</p>
                            <p class="small text-secondary">'.$data_jam_sekolah['jam_pulang'].'</p>
                        </div>
                    </div>
                </div>
            </div>';
        }
        echo'
       
        </div>
    </div>
</main>';


}?>