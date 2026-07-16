<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:./404');
}else{
$data_jam = getJam($connection, $hari_ini, 'Siswa');
function getLokasiData($connection, $lokasi) {
    $lokasi_id_safe = $connection->real_escape_string($lokasi);
    $query = "SELECT * FROM lokasi WHERE lokasi_id='$lokasi_id_safe'";
    $result = $connection->query($query);
    if ($result && $result->num_rows > 0) {
        $data_lokasi = $result->fetch_assoc();
        $lokasi = strip_tags(epm_encode($data_lokasi['lokasi_id']));
        $latitude = strip_tags($data_lokasi['lokasi_latitude']??'-');
        $longitude = strip_tags($data_lokasi['lokasi_longitude']??'-');
        $radius = strip_tags($data_lokasi['lokasi_radius']??'-');
        $status = strip_tags($data_lokasi['lokasi_status']??'-');
        return [
            'lokasi' => $lokasi,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $radius,
            'latlong' => $latitude . ', ' . $longitude,
            'status' => $status
        ];
    } else {
        return null;
    }
}
echo'
<main class="flex-shrink-0 main">
    <div class="container mb-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="header-webcame mb-2">
                        <span class="title "> Selamat '.$salam.'</span>
                        <p class="subtitle text-nowrap text-success">'.ucfirst($data_user['nama_lengkap']).'</p>
                    </div>
                    <hr>';
                    if($row_site['tipe_absen_pegawai'] =='qrcode'){
                    echo'
                    <div class="webcame text-center">
                        <div id="reader"></div>
                    </div>';

                    }else{
                    echo'
                    <div class="webcam-app">
                         <div class="result-user d-none"></div>
                        <div class="body-webcame">
                            <div class="loading-webcame d-none"></div>
                            <div id="map-overlay"></div>
                            <div class="shift-overlay">
                                <ul>
                                    <li>'.tgl_ind($date).'</li>
                                    <li><span class="clock"></span></li>';
                                    if ($data_jam) {
                                        echo'
                                        <li>Masuk<span class="float-right">'.($data_jam['jam_masuk']??'-').'</span></li>
                                        <li>Pulang<span class="float-right">'.($data_jam['jam_pulang']??'-').'</span></li>';
                                    }else{
                                        echo'<li>Tidak ada jadwal</li>';
                                    }
                                echo'
                                </ul>
                            </div>
                            <video id="webcam" autoplay playsinline width="640" height="480" class="s-widodo.com"></video>
                            <canvas id="canvas" class="s-widodo.com"></canvas>
                        </div>
                        <div class="Controls-camera s-widodo.com">
                            <button class="btn btn-primary btn-md btn-take-photo"><span class="material-icons s-widodo.com">camera</span> Pulang</button>
                            <button class="btn btn-primary btn-md btn-resume-camera d-none"><span class="material-icons s-widodo.com">close</span> Ulangi</button>
                            <button class="btn btn-info btn-md btn-cameraFlip"><span class="material-icons s-widodo.com">cameraswitch</span> Flip</button>
                        </div>

                    </div>';
                    }
                echo'
                </div>
            </div>
        </div>
    
</main>';
}?>