<?php if(empty($connection)){
  header('location:./404');
} else {

$kembali = $base_url;
$mod_titles = [
    'absen-in' => 'Absen Masuk',
    'absen-out' => 'Absen Pulang',
    'histori-absen' => 'Daftar Kehadiran',
    'izin' => 'Izin',
    'izin-siswa' => 'Izin Siswa',
    'blog' => 'Informasi',
    'blog-details' => 'Informasi',
    'kartu-nama' => 'Kartu Nama',
    'setting' => 'Pengaturan',
    'profile' => 'Profile',
    'jam-sekolah' => 'Jam Bekerja',
    'keamanan' => 'Keamanan',
    'rekap-absen-siswa' => 'Rekap Absen Siswa',
    'e-kbm' => 'E-KBM',
    'rekap-absen-ekbm' => 'Rekap Absen E-KBM',
    'pelanggaran' => 'Pelanggaran',
    'input-pelanggaran' => 'Input Pelanggaran',
    'sanksi-pelanggaran' => 'Sanksi Pelanggaran',
];

if (isset($mod_titles[$mod])) {
    $title = $mod_titles[$mod];
    if ($mod == 'blog' || $mod == 'blog-details') {
        $kembali = $base_url . 'blog';
    }
} else {
    $title = 'Home';
    $kembali = $base_url;
}

$notifikasi_header = "SELECT * FROM notifikasi 
WHERE pegawai_id = '{$data_user['pegawai_id']}'
AND (tipe = 'siswa' OR tipe = 'admin')
AND tujuan = 'pegawai'
AND status = 'N'
ORDER BY notifikasi_id DESC 
LIMIT 5";
$result_header = $connection->query($notifikasi_header);

/** Notifikasi live chat */
$query_notifikasi_chat ="SELECT chat_id FROM chat WHERE status_user='N' AND pegawai_id='$data_user[pegawai_id]'";
$result_notifikasi_chat = $connection->query($query_notifikasi_chat);

echo'
<!doctype html>
<html lang="id" class="h-100">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>'.$website_name.'</title>
<meta name="robots" content="noindex">
<meta name="description" content="Aplikasi Absensi Kehadiran Sekolah, dibuat oleh s-widodo.com">
<meta name="author" content="s-widodo.com">
<meta http-equiv="Copyright" content="'.$website_name.'">
<meta name="copyright" content="s-widodo.com">
<meta name="apple-mobile-web-app-capable" content="yes">

<link rel="shortcut icon" href="../sw-content/'.$site_favicon.'">
<link rel="apple-touch-icon" href="../sw-content/'.$site_favicon.'">
<link rel="apple-touch-icon" sizes="72x72" href="../sw-content/'.$site_favicon.'">
<link rel="apple-touch-icon" sizes="114x114" href="../sw-content/'.$site_favicon.'">
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

<link href="../template/vendor/swiper/css/swiper.min.css" rel="stylesheet">
<link href="../template/vendor/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css" rel="stylesheet">
<link href="../template/vendor/bootstrap-datepicker/dist/css/bootstrap-datepicker.standalone.min.css" rel="stylesheet">
<link href="../template/css/chat.css" rel="stylesheet">
<link href="../template/vendor/emojionearea/emojionearea.css" rel="stylesheet">
<link href="../template/vendor/fontawesome/css/all.min.css" rel="stylesheet">
<link href="../template/vendor/webcame/webcam.css" rel="stylesheet">
<link href="../template/vendor/Magnific-Popup/magnific-popup.css" rel="stylesheet">
<link href="../template/css/style.css" rel="stylesheet" id="style">
<link href="../template/css/style-custom.css" rel="stylesheet">
<link rel="stylesheet" href="../template/vendor/leatfet/leaflet.css">
<script src="../template/vendor/chart.js/dist/Chart.min.js"></script>

</head>';
if($mod=='histori-absen' AND htmlspecialchars(@$_GET['op']??'')=='detail'){
echo'
<body class="body-scroll d-flex flex-column h-100 menu-overlay" data-page="homepage" onload="Loadmap();">';
}elseif($mod=='rekap-absen-siswa' AND htmlspecialchars(@$_GET['op']??'')=='detail'){
echo'
<body class="body-scroll d-flex flex-column h-100 menu-overlay" data-page="homepage" onload="Loadmap();">';
}else{
echo'
<body class="body-scroll d-flex flex-column h-100 menu-overlay" data-page="homepage">';
}
echo'
<span class="base-url d-none">'.$base_url.'</span>
<!-- screen loader -->
<div class="container-fluid h-100 loader-display">
    <div class="row h-100">
        <div class="align-self-center col">
            <div class="logo-loading">
                <div class="icon icon-100 rounded-circle">
                    <img src="../sw-content/'.strip_tags($site_favicon).'" class="w-50">
                </div>
                <h6 class="text-default">'.$website_name.'</h6>
                <div class="loader-ellipsis">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
            </div>
        </div>
    </div>
</div>';

if(!empty($_COOKIE['pegawai'])){
echo'
<!-- Fixed navbar -->
<header class="header">
    <div class="row">';
    if($mod =='home'){
        echo'
        <div class="col-auto px-0">
            <button class="menu-btn btn btn-40 btn-link" type="button">
                <span class="material-icons">menu</span>
            </button>
        </div>';
    }else{
        echo'
        <div class="col-auto px-0">
            <a href="'.$kembali.'" class="btn btn-40 btn-link back-btn">
                <span class="material-icons">keyboard_arrow_left</span>
            </a>
        </div>
        
        <div class="text-left col align-self-center">
            <h6 class="mb-0 text-white">'.$title.'</h6>
        </div>';
    }
        echo'
        <div class="ml-auto col-auto pl-0">
         
            <span class="dropdown">
                <a href="javascript:;" class="btn btn-40 btn-link" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <span class="material-icons">notifications_none</span>
                    <span class="counter">'.$result_header->num_rows.'</span>
                </a>

                <div class="dropdown-menu dropdown-menu-xl dropdown-menu-right py-0 aria-labelledby="dropdownMenuButton">
                    <!-- Dropdown header -->
                    <div class="px-3 py-3">
                        <p class="text-sm text-muted m-0">Ada <strong class="text-primary">'.$result_header->num_rows.'</strong> notifikasi.</p>
                    </div>
                    <div class="list-group list-group-flush">';
                    if($result_header->num_rows > 0) {
                    while($data_notifikasi = $result_header->fetch_assoc()) {
                    if($data_notifikasi['tipe']=='siswa'){
                        $link ='izin-siswa';
                    }else{
                        $link ='izin';
                    }
                    echo'
                    <a href="'.$link.'" class="list-group-item list-group-item-action btn-notifikasi" data-id="'.$data_notifikasi['notifikasi_id'].'">
                        <div class="row align-items-center">
                        <div class="col ml--2">
                            <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <smal class="mb-0">'.strip_tags($data_notifikasi['nama']??'-').' | '.tanggal_ind($data_notifikasi['tanggal']).'</smal>
                            </div>
                            <div class="text-right text-muted">
                                <small>'.facebook_time_ago($data_notifikasi['datetime']).'</small>
                            </div>
                            </div>
                            <p class="text-sm mb-0">'.strip_tags($data_notifikasi['keterangan']).'</p>
                        </div>
                        </div>
                    </a>';
                        }
                    }else{
                        echo'
                        <a href="javascript:;" class="list-group-item list-group-item-action">
                            Tidak ada notifikasi 
                        </a>';
                    }
                echo'
                    </div> 
                </div>
            </span>

            <button type="button" class="btn btn-link btn-40 btn-open-chat">
                <i class="fas fa-comment-dots"></i>
                <span class="counter">'.$result_notifikasi_chat->num_rows.'</span>
            </button>
            
            <a href="setting" class="avatar avatar-30 shadow-sm rounded-circle ml-2">
                <figure class="m-0 background">';
                    if(file_exists('../sw-content/avatar/'.$data_user['avatar']??'-.jpg'.'')){
                        echo'<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../sw-content/avatar/'.$data_user['avatar']??'-.jpg'.'')).'" height="40">';
                    }else{
                        echo'<img src="./sw-content/avatar/avatar.jpg" height="40">';
                    }
                echo'
                </figure>
            </a>
        </div>
    </div>
</header>

<!-- menu main -->
<div class="main-menu">
    <div class="row mb-4 no-gutters">
        <div class="col-auto"><button class="btn btn-link btn-40 btn-close text-white"><span class="material-icons">chevron_left</span></button></div>
    </div>

    <div class="menu-container">
        <p>'.strip_tags($row_site['nama_sekolah']??'-').'<br>
        NPSN : '.strip_tags($row_site['npsn']??'-').'</p>
        <hr>
        <h6>Alamat</h6>
        <p><span class="material-icons icon mr-1" style="font-size: 16px;">place</span>'.strip_tags($row_site['site_address']??'-').'</p>

        <h6>Kontak</h6>
        <p><span class="material-icons icon mr-1" style="font-size: 16px;">mail</span>'.strip_tags($row_site['site_email']??'-').'<br>
        <span class="material-icons icon mr-1" style="font-size: 16px;">call</span>'.strip_tags($row_site['site_phone']??'-').'</p>

        <h6>Kepala Sekolah</h6>
        <p><span class="material-icons icon mr-1" style="font-size: 16px;">school</span>'.strip_tags($row_site['kepala_sekolah']??'-').'<br>
        <span class="material-icons icon mr-1" style="font-size: 16px;">card_membership</span>'.strip_tags($row_site['nip_kepala_sekolah']??'-').'</p>
        
        <div class="text-center">
            <a href="../logout" class="btn btn-outline-default btn-sm text-white rounded my-3 mx-auto">Logout</a>
        </div>
    </div>
</div>
<div class="backdrop"></div>';
}
}?>