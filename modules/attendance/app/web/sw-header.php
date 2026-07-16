<?php if(empty($connection)){
  header('location:./404');
} else {
echo'
<!doctype html>
<html lang="en" class="h-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>'.$website_name.'</title>
    <meta name="robots" content="noindex">
    <meta name="description" content="Aplikasi Absensi Kehadiran Karyawan Online dibuat oleh s-widodo.com">
    <meta name="author" content="s-widodo.com">
    <meta http-equiv="Copyright" content="'.$website_name.'">
    <meta name="copyright" content="s-widodo.com">
    <meta name="apple-mobile-web-app-capable" content="yes">

    <link rel="shortcut icon" href="'.$base_url.'sw-content/'.$site_favicon.'">
    <link rel="apple-touch-icon" href="'.$base_url.'sw-content/'.$site_favicon.'">
    <link rel="apple-touch-icon" sizes="72x72" href="'.$base_url.'sw-content/'.$site_favicon.'">
    <link rel="apple-touch-icon" sizes="114x114" href="'.$base_url.'sw-content/'.$site_favicon.'">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="'.$base_url.'template/vendor/swiper/css/swiper.min.css" rel="stylesheet">
    <link href="'.$base_url.'template/vendor/fontawesome/css/all.min.css" rel="stylesheet">
    <link href="'.$base_url.'template/css/style.css" rel="stylesheet" id="style">
    <link href="'.$base_url.'template/css/style-custom.css" rel="stylesheet">
</head>
<body class="body-scroll d-flex flex-column h-100 menu-overlay" data-page="homepage">
<span class="base-url d-none">'.$base_url.'</span>

    <!-- screen loader -->
    <div class="container-fluid h-100 loader-display">
    <div class="row h-100">
        <div class="align-self-center col">
            <div class="logo-loading">
                <div class="icon icon-100 rounded-circle">
                    <img src="'.$base_url.'sw-content/'.strip_tags($site_favicon).'" class="w-50">
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
}?>