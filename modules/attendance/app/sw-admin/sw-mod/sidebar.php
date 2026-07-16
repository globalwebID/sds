<?php
if (!isset($connection)) {
    die("Koneksi database tidak ditemukan.");
}else{
$query = "SELECT modul_id FROM role WHERE lihat = 'Y' AND level_id = $current_user[level]";
$result_modul = $connection->query($query);
$izin_modul = array();
if ($result_modul->num_rows > 0) {
    while ($data_modul = $result_modul->fetch_assoc()) {
        $izin_modul[$data_modul['modul_id']] = true;
    }
}

// Superadmin SDS harus memperoleh seluruh menu yang diizinkan level Superadmin
// Absensi. Admin dan operator tetap dibatasi ke fitur operasional agar master
// data yang dipusatkan di SDS tidak diubah oleh staf modul.
$isCentralSuperadmin = !empty($current_user['central_admin'])
    && ($current_user['app_role'] ?? '') === 'superadmin';
if (!$isCentralSuperadmin) {
    $izin_modul = array_intersect_key($izin_modul, array_flip([3, 5, 8, 9, 10, 11, 13, 15, 22, 24, 25]));
}


$raw = basename($_SERVER['REQUEST_URI']);
$parts = explode('&', $raw);
$current_page = $parts[0];

$show_scanabsen = in_array($current_page, ['absen-manual-siswa', 'absen-manual-pegawai']) ? "show" : "";
$active_scanabsen = in_array($current_page, ['absen-manual-siswa', 'absen-manual-pegawai']) ? "active" : "";

$show_artikel = in_array($current_page, ['artikel', 'kategori']) ? "show" : "";
$active_artikel = in_array($current_page, ['artikel', 'kategori']) ? "active" : "";

$show_master = in_array($current_page, ['tahun-ajaran', 'kelas', 'jam-sekolah', 'lokasi', 'libur', 'id-card','template-surat']) ? "show" : "";
$active_master = in_array($current_page, ['tahun-ajaran', 'kelas', 'jam-sekolah', 'lokasi', 'libur', 'id-card', 'template-surat']) ? "active" : "";

$show_kbm = in_array($current_page, ['jadwal-notifikasi', 'mata-pelajaran', 'jadwal-mengajar']) ? "show" : "";
$active_kbm = in_array($current_page, ['jadwal-notifikasi', 'mata-pelajaran', 'jadwal-mengajar']) ? "active" : "";

$show_users = in_array($current_page, ['user', 'pegawai', 'wali-murid', 'alumni']) ? "show" : "";
$active_users = in_array($current_page, ['user', 'pegawai', 'wali-murid', 'alumni']) ? "active" : "";


$show_pelanggaran = in_array($current_page, ['kategori-pelanggaran', 'bentuk-pelanggaran', 'pelanggaran-siswa', 'sanksi-pelanggaran','laporan-pelanggaran']) ? "show" : "";
$active_pelanggaran = in_array($current_page, ['kategori-pelanggaran', 'bentuk-pelanggaran', 'pelanggaran-siswa', 'sanksi-pelanggaran', 'laporan-pelanggaran']) ? "active" : "";

$show_izin = in_array($current_page, ['izin', 'izin-pegawai']) ? "show" : "";
$active_izin = in_array($current_page, ['izin', 'izin-pegawai']) ? "active" : "";

$show_laporan = in_array($current_page, ['laporan-hari-ini', 'laporan-siswa', 'laporan-perhari']) ? "show" : "";
$active_laporan = in_array($current_page, ['laporan-hari-ini', 'laporan-siswa', 'laporan-perhari']) ? "active" : "";

$show_laporan_pegawai = in_array($current_page, ['laporan-pegawai-hari-ini', 'laporan-pegawai', 'laporan-pegawai-perhari']) ? "show" : "";
$active_laporan_pegawai = in_array($current_page, ['laporan-pegawai-hari-ini', 'laporan-pegawai', 'laporan-pegawai-perhari']) ? "active" : "";

$show_setting = in_array($current_page, ['pengaturan', 'slider']) ? "show" : "";
$active_setting = in_array($current_page, ['pengaturan', 'slider']) ? "active" : "";


$brandLogoUrl = !empty($sds_site_logo_url) ? $sds_site_logo_url : '../sw-content/'.$site_logo;
echo'
<nav class="sidenav navbar navbar-vertical fixed-left navbar-expand-xs navbar-light bg-white" id="sidenav-main">
<div class="scrollbar-inner">
<!-- Brand -->
<div class="sidenav-header d-flex align-items-center">
    <a class="navbar-brand" href="./">
    <img src="'.htmlspecialchars($brandLogoUrl, ENT_QUOTES, 'UTF-8').'" class="navbar-brand-img" alt="Logo Sekolah">
    </a>
    <div class="ml-auto">
    <!-- Sidenav toggler -->
    <div class="sidenav-toggler d-none d-xl-block" data-action="sidenav-unpin" data-target="#sidenav-main">
        <div class="sidenav-toggler-inner">
        <i class="sidenav-toggler-line"></i>
        <i class="sidenav-toggler-line"></i>
        <i class="sidenav-toggler-line"></i>
        </div>
    </div>
    </div>
</div>
<div class="navbar-inner">
    <!-- Collapse -->
    <div class="collapse navbar-collapse" id="sidenav-collapse-main">
    <!-- Nav items -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" href="./home">
            <i class="ni ni-shop text-primary"></i>
                <span class="nav-link-text">Dashboard</span>
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link '.$active_scanabsen.'" href="#absen" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="aben">
                <i class="fas fa-camera"></i>
                <span class="nav-link-text">Scan Absensi</span>
            </a>

            <div class="collapse '.$show_scanabsen.'" id="absen">
                <ul class="nav nav-sm flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="../absensi-siswa-screen" target="_blank">Absen Layar Siswa</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="../absensi-pegawai-screen" target="_blank">Absen Layar Pegawai</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="./absen-manual-siswa">Absen Manual Siswa</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="./absen-manual-pegawai">Absen Manual Pegawai</a>
                    </li>

                </ul>
            </div>
        </li>';

        if (!empty($izin_modul[4])){
            echo'
            <li class="nav-item">
                <a class="nav-link '.$active_artikel.'" href="#navbar-artikel" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-artikel">
                    <i class="fas fa-file-alt text-primary"></i>
                    <span class="nav-link-text">Artikel</span>
                </a>
                <div class="collapse '.$show_artikel.'" id="navbar-artikel">
                    <ul class="nav nav-sm flex-column sub-menu">
                    <li class="nav-item">
                        <a href="./artikel" class="nav-link">Artikel</a>
                    </li>
                    <li class="nav-item">
                        <a href="./kategori" class="nav-link">Kategori</a>
                    </li>
                    </ul>
                </div>
            </li>';
        }

        
        if (!empty($izin_modul[15])){
        echo'
        <li class="nav-item">
            <a class="nav-link '.$active_master.'" href="#navbar-master" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-master">
                <i class="ni ni-money-coins text-orange"></i>
                <span class="nav-link-text">Master Data</span>
            </a>

            <div class="collapse '.$show_master.'" id="navbar-master">
                <ul class="nav nav-sm flex-column sub-menu">';
                    
                    if (!empty($izin_modul[3])){
                        echo'
                        <li class="nav-item">
                            <a href="./jam-sekolah" class="nav-link">Jam Sekolah</a>
                        </li>';
                    }

                    if (!empty($izin_modul[8])){
                        echo'
                        <li class="nav-item">
                            <a href="./libur" class="nav-link">Libur</a>
                        </li>';
                    }

                    if (!empty($izin_modul[5])){
                        echo'
                        <li class="nav-item">
                            <a href="./lokasi" class="nav-link">Titik Lokasi</a>
                        </li>';
                    }

                    if (!empty($izin_modul[26])){
                        echo'
                        <li class="nav-item">
                            <a href="./template-surat" class="nav-link">Template Surat</a>
                        </li>';
                    }

                    if (!empty($izin_modul[16])){
                        echo'
                        <li class="nav-item">
                            <a href="./id-card" class="nav-link">Template ID CARD</a>
                        </li>';
                    }
                    
                echo'
                </ul>
            </div>
        </li>';
        }

        if (!empty($izin_modul[21])){
            echo'
            <li class="nav-item">
                <a class="nav-link '.$active_kbm.'" href="#navbar-kmb" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-kmb">
                    <i class="ni ni-books text-primary"></i>
                    <span class="nav-link-text">E-KBM</span>
                </a>

                <div class="collapse '.$show_kbm.'" id="navbar-kmb">
                    <ul class="nav nav-sm flex-column sub-menu">
                        <li class="nav-item">
                            <a href="./jadwal-notifikasi" class="nav-link">Notifikasi</a>
                        </li>';
                        

                        if (!empty($izin_modul[1])){
                            echo'
                            <li class="nav-item">
                                <a href="./mata-pelajaran" class="nav-link">Mata Pelajaran</a>
                            </li>';
                        }

                        if (!empty($izin_modul[22])){
                            echo'
                            <li class="nav-item">
                                <a href="./jadwal-mengajar" class="nav-link">Jadwal Mengajar</a>
                            </li>';
                        }
                    echo'
                    </ul>
                </div>
            </li>';
        }

        if (!empty($izin_modul[19])){
        echo'
        <li class="nav-item">
            <a class="nav-link '.$active_users.'" href="#navbar-users" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-users">
                <i class="ni ni-circle-08 text-info"></i>
                <span class="nav-link-text">Users</span>
            </a>

            <div class="collapse '.$show_users.'" id="navbar-users">
                <ul class="nav nav-sm flex-column sub-menu">';
                    if (!empty($izin_modul[1])){
                        echo'
                        <li class="nav-item">
                            <a href="./user" class="nav-link">Siswa</a>
                        </li>';
                    }

                    if (!empty($izin_modul[18])){
                        echo'
                        <li class="nav-item">
                            <a href="./pegawai" class="nav-link">Pengajar/Staff</a>
                        </li>';
                    }

                    if (!empty($izin_modul[2])){
                        echo'
                        <li class="nav-item">
                            <a href="./wali-murid" class="nav-link">Wali Murid</a>
                        </li>';
                    }
                    
                echo'
                </ul>
            </div>
        </li>';
        }

        if (!empty($izin_modul[25])){
            echo'
            <li class="nav-item">
                <a class="nav-link '.$active_pelanggaran.'" href="#tata-tertib" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="tata-tertib">
                    <i class="ni ni-collection text-danger"></i>
                    <span class="nav-link-text">Pelanggaran</span>
                </a>

                <div class="collapse '.$show_pelanggaran.'" id="tata-tertib">
                    <ul class="nav nav-sm flex-column sub-menu">
                        <li class="nav-item">
                            <a href="./kategori-pelanggaran" class="nav-link">Kategori Pelanggaran</a>
                        </li>
                        <li class="nav-item">
                            <a href="./bentuk-pelanggaran" class="nav-link">Bentuk Pelanggaran</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="./pelanggaran-siswa">Palanggaran Siswa</a>
                        </li>

                        <li class="nav-item">
                            <a href="./laporan-pelanggaran" class="nav-link">Laporan Pelaggaran</a>
                        </li>

                        <li class="nav-item">
                            <a href="./sanksi-pelanggaran" class="nav-link">Sanksi Pelanggaran</a>
                        </li>

                    </ul>
                </div>
            </li>';
        }

        if (!empty($izin_modul[9])){
            echo'
            <li class="nav-item">
            <a class="nav-link '.$active_izin.'" href="#izin" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="izin">
                <i class="ni ni ni-single-copy-04 text-success"></i>
                <span class="nav-link-text">Izin</span>
            </a>

                <div class="collapse '.$show_izin.'" id="izin">
                    <ul class="nav nav-sm flex-column sub-menu">
                        <li class="nav-item">
                            <a href="./izin" class="nav-link">Siswa</a>
                        </li>
                        <li class="nav-item">
                            <a href="./izin-pegawai" class="nav-link">Pegawai</a>
                        </li>
                    </ul>
                </div>
            </li>';
        }

    
        if (!empty($izin_modul[10])){
            echo'
            <li class="nav-item">
            <a class="nav-link '.$active_laporan.'" href="#laporan" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="laporan">
                <i class="fas fa-print text-orange"></i>
                <span class="nav-link-text">Lap. Absensi Siswa</span>
            </a>

                <div class="collapse '.$show_laporan.'" id="laporan">
                    <ul class="nav nav-sm flex-column sub-menu">
                            <li class="nav-item">
                                <a href="./laporan-hari-ini" class="nav-link">Laporan Absen Hari ini</a>
                            </li>
                            <li class="nav-item">
                                <a href="./laporan-siswa" class="nav-link">Laporan Per Siswa</a>
                            </li>

                            <li class="nav-item">
                                <a href="./laporan-perhari" class="nav-link">Laporan Per Hari</a>
                            </li>
                    </ul>
                </div>
            </li>


            <li class="nav-item">
            <a class="nav-link '.$active_laporan_pegawai.'" href="#laporanpegawai" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="laporan">
                <i class="fas fa-print text-orange"></i>
                <span class="nav-link-text">Lap. Absensi Pegawai</span>
            </a>

                <div class="collapse '.$show_laporan_pegawai.'" id="laporanpegawai">
                    <ul class="nav nav-sm flex-column sub-menu">
                            <li class="nav-item">
                                <a href="./laporan-pegawai-hari-ini" class="nav-link">Laporan Absen Hari ini</a>
                            </li>
                            <li class="nav-item">
                                <a href="./laporan-pegawai" class="nav-link">Laporan Per Pegawai</a>
                            </li>

                            <li class="nav-item">
                                <a href="./laporan-pegawai-perhari" class="nav-link">Laporan Per Hari</a>
                            </li>
                    </ul>
                </div>
            </li>

            ';
        }

        echo'
        <li class="nav-item">
        <a class="nav-link '.$active_setting.'" href="#navbar-app" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="navbar-app">
            <i class="fas fa-tools text-danger"></i>
            <span class="nav-link-text">Pengaturan App</span>
        </a>
        <div class="collapse '.$show_setting.'" id="navbar-app">
            <ul class="nav nav-sm flex-column sub-menu">';
            if (!empty($izin_modul[11])){
                echo'
                <li class="nav-item">
                    <a href="./pengaturan" class="nav-link">Pengaturan</a>
                </li>';
            }

            if (!empty($izin_modul[22])){
                echo'
                <li class="nav-item">
                    <a href="./slider" class="nav-link">Slider</a>
                </li>';
            }

            echo'
            </ul>
        </div>
        </li>';
    
        if (!empty($izin_modul[13])){
            echo'
            <li class="nav-item">
                <a class="nav-link" href="./admin">
                    <i class="fas fa-user text-green"></i>
                    <span class="nav-link-text">Admin</span>
                </a>
            </li>';
        }  
            
        echo'
        </ul>
        
        <hr class="my-3">
        <!-- Navigation -->
        <ul class="navbar-nav mb-md-3">
            <li class="nav-item">
                <a class="nav-link" href="./logout">
                    <i class="fas fa-sign-out-alt text-danger"></i>
                    <span class="nav-link-text">Logout</span>
                </a>
            </li>  
        </ul>
        </div>
    </div>
    </div>
</nav>';
}?>
