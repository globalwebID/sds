<?php 
include_once '../sw-library/sw-config.php';
include_once '../sw-library/sw-function.php';
ob_start("minify_html");
echo'
 <!DOCTYPE html>
    <html>
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>'.strip_tags($site_name).'</title>
    <meta name="description" content="'.$site_name.'">
    <meta name="author" content="s-widodo.com">
    <meta name="robots" content="noindex">
    <meta name="robots" content="nofollow">
    <!-- Favicon -->
    <link rel="icon" href="../sw-content/'.$site_favicon.'" type="image/png">
    
    <link rel="stylesheet" href="../template/css/style.css">
    <link rel="stylesheet" href="../template/css/sw-custom.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../template/vendor/fontawesome/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="./main.css">
    <link rel="stylesheet" href="../template/vendor/webcame/webcam.css">

</head>';
if($row_site['tipe_absen_layar'] =='qrcode-webcame'){
    echo'<body onload="qrcode_webcame()">';
}else{
    echo'<body onload="webcame_selfie()">';
}

echo'
<span class="latitude d-none"></span>
<header class="header">
    <div class="conatiner-fluid">
    <div class="row">
        <div class="col align-self-left logo-header">
            <img src="../sw-content/'.$site_logo.'">
        </div>                        
    </div>
    </div>
</header>


<main class="flex-shrink-0 main has-footer s-widodo.com mt-2">
    <div class="section mt-2">
        <div class="container-fluid mb-2">
            <div class="row">
            
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body text-center ">
                            <div class="screen-slider">
                                <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel" data-interval="5000">
                                    <div class="carousel-inner">';
                                    $query_slide = "SELECT * FROM slider WHERE active ='Y' ORDER BY slider_id desc";
                                    $result_slide = $connection->query($query_slide);
                                    if($result_slide->num_rows > 0){$active= 0;
                                        while ($data_slide = $result_slide->fetch_assoc()){$active++;
                                        if($active==1){
                                            echo'
                                            <div class="carousel-item active">';
                                        }else{
                                            echo'
                                            <div class="carousel-item">';
                                        }
                                        
                                        if(file_exists('../../sw-content/slider/'.$data_slide['foto'].'')){
                                            echo'
                                            <img src="../template/img/sw-big.jpg" alt="'.strip_tags($data_slide['slider_nama']).'" class="d-block w-100">';
                                        }else{
                                            if($data_slide['foto']==''){
                                            echo'
                                            <img src="../template/img/sw-big.jpg" alt="'.strip_tags($data_slide['slider_nama']).'" class="d-block w-100">';
                                            }else{
                                            echo'
                                            <img src="data:image/png;base64,'.base64_encode(file_get_contents('../sw-content/slider/'.$data_slide['foto'].'')).'" alt="'.strip_tags($data_slide['slider_nama']).'" class="d-block w-100">';
                                            }
                                        }
                                        echo'
                                        </div>';
                                        }
                                    }
                                    echo'
                                    </div>
                                    <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
                                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                    <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
                                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                        <span class="sr-only">Next</span>
                                    </a>
                                </div>
                            </div>
              
                            
                        </div>
                    </div>

                    <div class="card mt-2">
                        <div class="card-body pt-4 pl-4 pr-4 text-center">';
                            if($row_site['tipe_absen_layar'] =='qrcode'){
                                echo'
                                <h3>Cukup scan QR Code dengan mesin scanner dan biarkan wajah Anda tertangkap secara otomatis</h3>
                                <div class="aniamed-scanner">
                                    <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/qr-code.gif')).'" class="imaged-scanner mt-2 bm-2">
                                    <div class="webcam-screen">
                                        <video id="webcam" autoplay playsinline width="640" height="480" class="s-widodo.com"></video>
                                        <canvas id="canvas" class="s-widodo.com"></canvas>
                                    </div>
                                </div>
                                 <input type="text" name="qrcode" class="form-control qrcode bg-white" required>';
                             }elseif($row_site['tipe_absen_layar'] =='rfid'){
                                echo'<h3>Dekatkan kartu RFID Anda, dan proses verifikasi akan berjalan otomatis</h3>
                                 <div class="aniamed-scanner">
                                    <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/rfid.gif')).'" class="imaged-scanner mt-2 bm-2">
                                    <div class="webcam-screen">
                                        <video id="webcam" autoplay playsinline width="640" height="480" class="s-widodo.com"></video>
                                        <canvas id="canvas" class="s-widodo.com"></canvas>
                                    </div>
                                </div>
                                 <input type="text" name="qrcode" class="form-control qrcode bg-white" required>';
                            }else{
                                echo'<h3>Arahkan kamera Anda ke QR Code untuk memindai</h3>
                                <div class="webcame text-center">
                                    <div id="reader"></div>
                                </div>';
                            }
                            echo'
                        </div>
                    </div>

                </div>

                

                <div class="col-md-8">
                    <div class="card">
                        <div class="card-body card-body-absensi">
                        <h3>Absensi terbaru</h3>
                        <hr>
                            <div class="marquee-container">
                                <div class="data-absensi marquee"></div>
                            </div>
                        </div>
                    </div>

                    <div class="transactions mt-3">
                        <div class="row data-counter-left">

                            <div class="col-md-4">
                                <div class="card border-0 mb-2 bg-warning">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/003-profile.png')).'" alt="img" class="image-block imaged w36">
                                                </div>
                                            </div>
                                            <div class="col align-self-center">
                                                <strong class="text-white">Total Siswa</strong>
                                                <p class="text-white total-siswa">0</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card border-0 mb-2 bg-danger">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/002-sand-clock.png')).'" alt="img" class="image-block imaged w36">
                                                </div>
                                            </div>
                                            <div class="col align-self-center">
                                                <strong class="text-white">Belum Absen</strong>
                                                <p class="text-white belum-absen">0</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card border-0 mb-2 bg-primary">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/007-insight.png')).'" alt="img" class="image-block imaged w36">
                                                </div>
                                            </div>
                                            <div class="col align-self-center">
                                                <strong class="text-white">Total Absen</strong>
                                                <p class="text-white"><span class="total-absen">0</span>
                                                    <small class="text-white"><span class="material-icons ml-3" style="font-size:15px">show_chart</span> <span class="persentase ml-1">0</span>%</small>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card border-0 mb-2 bg-secondary">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/005-clipboard.png')).'" alt="img" class="image-block imaged w36">
                                                </div>
                                            </div>
                                            <div class="col align-self-center">
                                                <strong class="text-white">On Time</strong>
                                                <p class="text-white ontime">0</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card border-0 mb-2 bg-danger">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/004-time.png')).'" alt="img" class="image-block imaged w36">
                                                </div>
                                            </div>
                                            <div class="col align-self-center">
                                                <strong class="text-white">Terlambat</strong>
                                                <p class="text-white terlambat">0</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card border-0 mb-1 bg-info">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,'.base64_encode(file_get_contents('../template/img/icons/002-verified.png')).'" alt="img" class="image-block imaged w36">
                                                </div>
                                            </div>
                                            <div class="col align-self-center">
                                                <strong class="text-white">Izin</strong>
                                                <p class="text-white izin">0</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        


                        </div>
                    </div>

                 </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="marquee-left">
        <p>Selamat Datang di '.$row_site['nama_sekolah'].' | Tanggal: '.format_hari_tanggal($date).' | Waktu: <span class="clock"></span></p>
    </div>
</footer>

<div class="appBottomMenu  d-none bg-primary">
    <span class="credits">
        <a class="credits_a" id="mycredit" href="https://s-widodo.com"  target="_blank">S-widodo.com</a>
    </span>
</div>

<script src="../sw-library/bundle.min.php?get=s-widodo.com"></script>
<script src="./sw-script.js"></script>
</body>
</html>';
?>
