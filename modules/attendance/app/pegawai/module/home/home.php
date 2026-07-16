<?php if(empty($connection)){
    header('location:../404');
}else{
if(isset($_COOKIE['pegawai'])){
$data_jam = getJam($connection, $hari_ini, $data_user['jabatan']);

$query = "SELECT COUNT(*) AS jumlah_pelanggaran FROM pelanggaran 
WHERE MONTH(tanggal) = '$month' AND YEAR(tanggal) = '$year' AND kelas= '{$data_user['wali_kelas']}'";
$result = $connection->query($query);
$data_pelanggaran = $result->fetch_assoc();

$data = [
    'labels' => [],
    'total_absen' => [],
    'total_izin' => [],
    'total_pelanggaran' => []
];

$sql = "SELECT 
            COUNT(DISTINCT absen.absen_id) AS total_absen,
            COUNT(DISTINCT izin.izin_id) AS total_izin,
            COUNT(DISTINCT pelanggaran.pelanggaran_id) AS total_pelanggaran
        FROM user
        LEFT JOIN absen 
            ON absen.user_id = user.user_id 
            AND absen.tanggal = '$tanggal'
        LEFT JOIN izin 
            ON izin.user_id = user.user_id 
            AND MONTH(izin.tanggal)='$month'
        LEFT JOIN pelanggaran 
            ON pelanggaran.user_id = user.user_id 
            AND MONTH(pelanggaran.tanggal)='$month'
        WHERE user.kelas = '{$data_user['wali_kelas']}'";
$result = $connection->query($sql);
if ($result) {
    $row = $result->fetch_assoc();
    if ($row) {
        $data['labels'][] = tanggal_ind($tanggal);
        $data['total_absen'][] = (int)$row['total_absen'];
        $data['total_izin'][] = (int)$row['total_izin'];
        $data['total_pelanggaran'][] = (int)$row['total_pelanggaran'];
    }
} 

echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="container mt-2 mb-4 text-center">
        <h4 class="text-white">'.strip_tags($data_user['nama_lengkap']).'</h4>
        <div class="text-white">
         NIP. '.$data_user['nip'].'
        </div>
    </div>

    <div class="card bg-default-secondary shadow-default mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col text-left">
                    '.tgl_indo($date).'<br>
                    <span class="clock"></span>
                </div>
                <div class="col pl-0 text-right">';
                if ($data_jam) {
                    if($data_jam['active']=='Y'){
                        echo'IN:'.$data_jam['jam_masuk'].'<br>OUT:'.$data_jam['jam_pulang'].'';
                    }else{
                        echo'Libur';
                    }
                }else{
                    echo'Tidak ada!';
                }
                echo'
                </div>
            </div>
        <hr>
        <!-- Menu -->

        <div class="row">
           <div class="col-6 col-md-6">';
            if (!$data_absen) {
                echo'<a href="absen-in">';
            }else{
                echo'<a href="javascript:void(0);">';
            }
            echo'
                <div class="card border-0">
                    <div class="card-body button-absent" style="padding:15px 5px">
                            <div class="col align-self-center">
                                <span class="material-icons text-primary">login</span>
                                <p class="mb-0">IN</p>';
                                    if (!$data_absen) {
                                        echo'
                                        <p class="small text-secondary">Belum absen</p>';
                                    }else{
                                        if ($data_absen['absen_in'] === null) {
                                            $absen_in = $data_absen['kehadiran'];
                                        } else {
                                            $absen_in = $data_absen['absen_in'];
                                        }

                                        echo'
                                        <p class="small text-secondary">'.$absen_in.'</p>';
                                    }
                            echo'
                            </div>
                    </div>
                </div>
                </a>
            </div>
            
            <div class="col-6 col-md-6">';
            if (!$data_absen) {
                echo'<a href="javascript:void(0);">';
                }else{
                    if($data_absen['kehadiran'] == 'Hadir'){
                        echo'<a href="absen-out">';
                    }else{
                        echo'<a href="javascript:void(0);">';
                    }
                }
                echo'
                <div class="card border-0">
                    <div class="card-body button-absent" style="padding:15px 5px">
                            <div class="col align-self-center">
                                <span class="material-icons text-warning">logout</span>
                                <p class="mb-0">OUT</p>';
                                if (!$data_absen) {
                                    echo'
                                    <p class="small text-secondary">Belum absen</p>';
                                }else{
                                    if ($data_absen['absen_out'] === null) {
                                        $absen_out = $data_absen['kehadiran'];
                                    } else {
                                        $absen_out = $data_absen['absen_out'];
                                    }
                                    if(!$data_absen['kehadiran'] == 'Hadir'){
                                        echo'<p class="small text-secondary">Belum absen</p>';
                                    }else{
                                        echo'<p class="small text-secondary">'.$absen_out.'</p>';
                                    }
                                }
                            echo'
                            </div>
                        </div>
                    </div>
                    </a>
                </div>
            </div>

        </div>
    </div>

          
    <div class="main-container">
        <div class="container mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="row justify-content-equal no-gutters">

                        <div class="col-4 col-md-2 mb-3">
                            <a href="./e-kbm">
                                <div class="icon icon-60 rounded mb-1 bg-light text-default">
                                <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/004-registered.png')).'" alt="s-widodo.com" class="image-block imaged w36">
                                </div>
                            <p class="text-secondary"><small>E-KBM</small></p>
                            </a>
                        </div>
                        

                        <div class="col-4 col-md-2 mb-3">
                            <a href="javascript:;" dropdown-toggle" role="button" data-toggle="dropdown" aria-expanded="false">
                                <div class="icon icon-60 rounded mb-1 bg-light text-default">
                                <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/009-contract.png')).'" alt="s-widodo.com" class="image-block imaged w36">
                                </div>
                            <p class="text-secondary"><small>Izin</small></p>
                            </a>
                             <div class="dropdown-menu">
                                <a class="dropdown-item" href="izin">Izin Saya</a>
                                <a class="dropdown-item" href="izin-siswa">Izin Siswa</a>
                            </div>
                        </div>

                        <div class="col-4 col-md-2 mb-3">
                            <a href="./pelanggaran">
                                <div class="icon icon-60 rounded mb-1 bg-light text-default">
                                    <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/001-football-card.png')).'" alt="s-widodo.com" class="image-block imaged w36">
                                    <span class="counter text-white">'.json_encode($data['total_pelanggaran'][0] ?? 0).'</span>
                                </div>
                            <p class="text-secondary"><small>Pelanggaran</small></p>
                            </a>
                        </div>

                        <div class="col-4 col-md-2 mb-3">
                            <a href="./sanksi-pelanggaran">
                                <div class="icon icon-60 rounded mb-1 bg-light text-default">
                                <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/002-data-breach.png')).'" alt="s-widodo.com" class="image-block imaged w36">
                                </div>
                            <p class="text-secondary"><small>Sanksi Pelanggaran</small></p>
                            </a>
                        </div>

                        <div class="col-4 col-md-2 mb-3">
                            <a href="./kartu-nama">
                            <div class="icon icon-60 rounded mb-1 bg-light text-default">
                            <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/010-id-card.png')).'" alt="s-widodo.com" class="image-block imaged w36">
                            </div>
                            <p class="text-secondary"><small>Id Card</small></p>
                            </a>
                        </div>
        
                        <div class="col-4 col-md-2 mb-3">
                            <a href="javascript:;" dropdown-toggle" role="button" data-toggle="dropdown" aria-expanded="false">
                                <div class="icon icon-60 rounded mb-1 bg-light text-default">
                                <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/008-documentation.png')).'" alt="s-widodo.com" class="image-block imaged w36">
                                </div>
                            <p class="text-secondary"><small>Rekap Absensi</small></p>
                            </a>
                             <div class="dropdown-menu">
                                <a class="dropdown-item" href="histori-absen">Absensi Saya</a>';
                                if ($data_user['jabatan'] == 'guru' && $data_user['wali_kelas'] !== '-') {
                                    echo '<a class="dropdown-item" href="rekap-absen-siswa">Absensi Siswa</a>';
                                }
                                echo'
                                <a class="dropdown-item" href="rekap-absen-ekbm">Absensi E-KBM</a>
                            </div>
                        </div>

                    </div>

                    <button class="btn btn-sm btn-outline-secondary rounded" id="more-expand-btn">Show more <span class="ml-2 small material-icons">expand_more</span></button>

                    <div class="row justify-content-equal no-gutters" id="more-expand">
                       <div class="col-4 col-md-2 mb-3">
                            <a href="./blog">
                            <div class="icon icon-60 rounded mb-1 bg-light text-default">
                                <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/001-informative.png')).'" alt="s-widodo.com" class="image-block imaged w36">
                            </div>
                            <p class="text-secondary"><small>Informasi</small></p>
                            </a>
                        </div>

                        <div class="col-4 col-md-2 mb-3">
                            <a href="./profile">
                            <div class="icon icon-60 rounded mb-1 bg-light text-default">
                            <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/002-user.png')).'" alt="s-widodo.com" class="image-block imaged w36">
                            </div>
                            <p class="text-secondary"><small>Profile</small></p>
                            </a>
                        </div>

                        <div class="col-4 col-md-2 mb-3">
                            <a href="./setting">
                                <div class="icon icon-60 rounded mb-1 bg-light text-default">
                                <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/006-skills.png')).'" alt="s-widodo.com" class="image-block imaged w36">
                                </div>
                                <p class="text-secondary"><small>Setting</small></p>
                            </a>
                        </div>
    
                    </div>
                </div>
        </div> 
    </div>
            
        <div class="container">';
            $query_slide = "SELECT * FROM slider WHERE active ='Y'";
            $result_slide = $connection->query($query_slide);
            if($result_slide->num_rows > 0){
                echo'
                <!-- Swiper intro -->
                <div class="swiper-container introduction text-white">
                    <div class="swiper-wrapper">';
                    while ($data_slide = $result_slide->fetch_assoc()){
                        echo'
                        <div class="swiper-slide slider overflow-hidden text-center">
                            <a href="'.strip_tags($data_slide['slider_url']).'">
                                <div class="align-self-center">';
                                if($data_slide['foto']== NULL && file_exists('../sw-content/slider/'.($data_slide['foto']??"-.jpg").'')){
                                    echo'
                                    <img src="../template/img/sw-big.jpg" alt="'.strip_tags($data_slide['slider_nama']).'" class="mw-100">';
                                }else{
                                    echo'
                                    <img src="data:image/png;base64,'.base64_encode(file_get_contents('../sw-content/slider/'.$data_slide['foto'].'')).'" alt="'.strip_tags($data_slide['slider_nama']).'" class="mw-100">';
                                }
                                echo'
                                </div>
                            </a>
                        </div>';
                    }
                echo'   
                    </div>
                    <!-- Add Pagination -->
                    <div class="swiper-pagination"></div>
                </div>';
            }
        
         /** Grafik */
         echo'
            <h6 class="subtitle mb-0">Statistik Siswa</h6>
            <div class="card mt-2 mb-4 text-center">
                <div class="card-body text-center">
                    <canvas id="myPieChart" width="280" height="280" style="display:inline-block"></canvas>
                </div>
            </div>';

        /** Artikel Terbaru */
        echo'
        <div class="row">
            <div class="col">
                <h6 class="subtitle mb-0">Informasi</h6>
            </div>
            <div class="col-auto"><a href="blog" class="float-right small">View All</a></div>
        </div>';

        $query_artikel="SELECT artikel_id,judul,domain,foto,deskripsi,kategori,date FROM artikel WHERE active='Y' ORDER BY artikel_id DESC LIMIT 5";
        $result_artikel = $connection->query($query_artikel);
        if($result_artikel->num_rows > 0){
        echo'
        <div class="swiper-container swiper-home-article text-center mb-2 mt-2">
            <div class="swiper-wrapper mb-2">';
                while ($data_artikel = $result_artikel->fetch_assoc()){
                    $judul = strip_tags($data_artikel['judul']);
                    if(strlen($judul ) >30)$judul= substr($judul,0,30).'..';
                    echo'
                    <div class="card border-0 mb-4 overflow-hidden swiper-slide">
                        <div class="card-body h-150 position-relative">
                            <a href="./blog-'.strip_tags($data_artikel['artikel_id']).'-'.strip_tags($data_artikel['domain']).'" class="background" data-toggle="tooltip" data-placement="top" title="'.strip_tags($data_artikel['judul']).'">';
                                if(file_exists('../sw-content/artikel/'.($data_artikel['foto']??'-.jpg').'')){
                                    echo'<img src="../sw-content/artikel/'.$data_artikel['foto'].'" height="150">';
                                }else{
                                    echo'<img src="../sw-content/thumbnail.jpg" height="150">';
                                }
                        echo' 
                            </a>
                        </div>
                        <div class="card-body">
                            <p class="mb-0"><small class="text-secondary">'.$data_artikel['date'].'</small></p>
                            <a href="./blog-'.strip_tags($data_artikel['artikel_id']).'-'.strip_tags($data_artikel['domain']).'" title="'.strip_tags($data_artikel['judul']).'">
                                <p class="mb-0">'.$judul.'</p>
                            </a>
                        </div>
                    </div>';
                  }     
                echo'
                </div>
                <div class="swiper-pagination"></div>
            </div>';
            }else{
                echo'<div class="alert alert-info mt-2">Saat ini belum ada artikel</div>';
            }

        echo'
        </div>';
?>
<script>
var labels = <?php echo json_encode($data['labels']); ?>;
var totalAbsen = <?php echo json_encode($data['total_absen']); ?>;
var totalIzin = <?php echo json_encode($data['total_izin']); ?>;
var totalPelanggaran = <?php echo json_encode($data['total_pelanggaran']); ?>;
var ctx = document.getElementById('myPieChart').getContext('2d');
var myPieChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Absen', 'Izin', 'Pelanggaran'],
        datasets: [{
            data: [
                totalAbsen,
                totalIzin,
                totalPelanggaran
            ],
            backgroundColor: ['#6385ff', '#eb9736', '#f13131']
        }]
    },
    options: {
        responsive: false,
        plugins: {
            legend: {
                display: true
            }
        }
    }
});
</script>
</div>
</main>
<?php }}?>