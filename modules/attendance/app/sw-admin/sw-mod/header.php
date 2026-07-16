<?php if(empty($connection)){
	header('location:./');
    exit();
} else {

$query_notifikasi  ="SELECT * FROM notifikasi WHERE status='N' AND (tipe='siswa' OR tipe='pegawai') AND (tujuan='admin' OR tujuan='siswa' OR tujuan='pegawai') ORDER BY notifikasi_id DESC LIMIT 5";
$result_notifikasi = $connection->query($query_notifikasi);
$brandFaviconUrl = !empty($sds_site_favicon_url) ? $sds_site_favicon_url : '../sw-content/'.$site_favicon;

echo'
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name="description" content="s-widodo.com">
<meta name="author" content="s-widodo.com">
<title>Dashboard</title>
<!-- Favicon -->
<link rel="icon" href="'.htmlspecialchars($brandFaviconUrl, ENT_QUOTES, 'UTF-8').'" type="image/png">
<!-- Fonts -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700">
<!-- Icons -->
<link rel="stylesheet" href="sw-assets/vendor/nucleo/css/nucleo.css" type="text/css">
<link rel="stylesheet" href="sw-assets/vendor/@fortawesome/fontawesome-free/css/all.min.css" type="text/css">
<!-- Page plugins -->
<link rel="stylesheet" href="sw-assets/vendor/select2/dist/css/select2.min.css">
<link rel="stylesheet" href="sw-assets/vendor/timepicker/bootstrap-timepicker.min.css">
<link rel="stylesheet" href="sw-assets/vendor/datatables.net-bs4/css/dataTables.bootstrap4.min.css">
<link rel="stylesheet" href="sw-assets/vendor/datatables.net-select-bs4/css/select.bootstrap4.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/fixedcolumns/4.3.0/css/fixedColumns.dataTables.min.css">
<link rel="stylesheet" href="sw-assets/vendor/Magnific-Popup/magnific-popup.css">
<link rel="stylesheet" href="sw-assets/css/app.css" type="text/css">
<link rel="stylesheet" href="sw-assets/css/argon.css" type="text/css">';
if($mod=='lokasi'){
    echo'
    <link rel="stylesheet" href="./sw-assets/vendor/leatfet/leaflet.css">
    <link rel="stylesheet" href="./sw-assets/vendor/leatfet/L.Control.Locate.min.css">';
}
 echo'
 </head>';
if($mod=='lokasi'){
    if(@$_GET['op'] == 'add' OR @$_GET['op'] == 'update'){
    echo'<body onload="lokasi();">';
    }else{
    echo'<body>';
    }
}elseif($mod=='kelas' && !@$_GET['op'] =='tingkat'){
    echo'<body onload="loadData();">';
}elseif($mod=='kelas' && @$_GET['op'] =='tingkat'){
    echo'<body onload="loadDataKelas();">';
}else{
    echo'<body>';
}
echo'
<!-- Sidenav -->';
/** Sidebar */
include_once'sidebar.php';
/** End Sidebar */
echo'
<div class="main-content" id="panel">
<!-- Topnav -->
<nav class="navbar navbar-top navbar-expand navbar-dark bg-primary border-bottom">
<div class="container-fluid">
    <div class="collapse navbar-collapse d-flex justify-content-between" id="navbarSupportedContent">
    <ul class="navbar-nav align-items-center">
        <li class="nav-item d-xl-none">
        <!-- Sidenav toggler -->
            <div class="pr-3 sidenav-toggler sidenav-toggler-dark" data-action="sidenav-pin" data-target="#sidenav-main">
                <div class="sidenav-toggler-inner">
                <i class="sidenav-toggler-line"></i>
                <i class="sidenav-toggler-line"></i>
                <i class="sidenav-toggler-line"></i>
                </div>
            </div>
        </li>

        <li class="nav-item">
            <a class="nav-link" href="../" target="_blank" role="button">
                <i class="ni ni-laptop"></i>
            </a> 
        </li>

    </ul>

    <!-- Navbar links -->
    <ul class="navbar-nav align-items-center ml-md-auto">
        
    <ul class="navbar-nav align-items-center ml-auto ml-md-0">

        <li class="nav-item dropdown">
            <a class="nav-link" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="ni ni-bell-55"></i>
                <span class="badge rounded-pill badge-notification bg-danger notifikasi">'.$result_notifikasi->num_rows.'</span>
            </a>
            <div class="dropdown-menu dropdown-menu-xl dropdown-menu-right py-0 overflow-hidden">
                <!-- Dropdown header -->
                <div class="px-3 py-3">
                <h6 class="text-sm text-muted m-0">Ada <strong class="text-primary">'.$result_notifikasi->num_rows.'</strong> notifikasi</h6>
                </div>
                <!-- List group -->
                <div class="list-group list-group-flush">';
                if($result_notifikasi->num_rows > 0) {
                    while($data_notifikasi = $result_notifikasi->fetch_assoc()) {
                        if($data_notifikasi['tujuan']=='siswa') {
                            $link_notif = './izin';  
                        }elseif($data_notifikasi['tujuan']=='pegawai'){
                            $link_notif = './izin-pegawai'; 
                        }
                        echo'
                        <a href="'.$link_notif.'" class="list-group-item list-group-item-action btn-notifikasi" data-id="'.$data_notifikasi['notifikasi_id'].'">
                            <div class="row align-items-center">
                            <div class="col ml--2">
                                <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-0 text-sm">'.strip_tags($data_notifikasi['nama']).' | '.tanggal_ind($data_notifikasi['tanggal']).'</h4>
                                </div>
                                <div class="text-right text-muted">
                                    <small>'.time_since(strtotime($data_notifikasi['datetime'])).'</small>
                                </div>
                                </div>
                                <p class="text-sm mb-0">'.strip_tags($data_notifikasi['keterangan']).'</p>
                            </div>
                            </div>
                        </a>';
                    }
                }else{
                    echo'
                    <a href="#" class="list-group-item list-group-item-action">
                        Tidak ada notifikasi 
                    </a>';
                }
                echo'
                </div>
                
            </div>
        </li>

        <li class="nav-item dropdown">
        <a class="nav-link pr-0" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <div class="media align-items-center">
            <span class="avatar avatar-sm rounded-circle">
                <img src="sw-assets/avatar/'.$current_user['avatar'].'">
            </span>
            <div class="media-body ml-2 d-none d-lg-block">
                <span class="mb-0 text-sm  font-weight-bold">'.strip_tags($current_user['fullname']).'</span>
            </div>
            </div>
        </a>
        <div class="dropdown-menu dropdown-menu-right">
            <div class="dropdown-header noti-title">
                <h6 class="text-overflow m-0">Welcome!</h6>
            </div>
            <a href="./profile" class="dropdown-item">
                <i class="ni ni-single-02"></i>
                <span>My profile</span>
            </a>

            <a href="./pengaturan" class="dropdown-item">
                <i class="ni ni-settings-gear-65"></i>
                <span>Settings</span>
            </a>
    
            <div class="dropdown-divider"></div>
                <a href="./logout" class="dropdown-item">
                    <i class="ni ni-user-run"></i>
                    <span>Logout</span>
                </a>
            </div>
        </li>
    </ul>
    </div>
</div>
</nav>
<!-- Header -->';
            
}?>
