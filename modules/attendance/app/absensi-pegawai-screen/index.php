<?php
include_once '../sw-library/sw-config.php';
include_once '../sw-library/sw-function.php';
ob_start("minify_html");

echo '
 <!DOCTYPE html>
    <html>
    <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>' . strip_tags($site_name) . '</title>
    <meta name="description" content="' . $site_name . '">
    <meta name="author" content="s-widodo.com">
    <meta name="robots" content="noindex">
    <meta name="robots" content="nofollow">
    <!-- Favicon -->
    <link rel="icon" href="../sw-content/' . $site_favicon . '" type="image/png">

    <link rel="stylesheet" href="../template/css/style.css">
    <link rel="stylesheet" href="../template/css/sw-custom.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../template/vendor/fontawesome/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="./main.css">
    <link rel="stylesheet" href="../template/vendor/webcame/webcam.css">

    <style>
    .main {
        height: 100vh;
    }
    .main.has-footer {
        padding-bottom: 0;
    }
      .camera-preview-wrap{
        border-radius:14px;
        overflow:hidden;
        border:1px solid rgba(0,0,0,.08);
        background:#000;
      }
      .camera-preview-head{
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:10px;
        margin-bottom:10px;
      }
      .camera-preview-badge{
        font-size:12px;
        padding:6px 10px;
        border-radius:999px;
        background:rgba(0,0,0,.06);
      }
      #webcam_preview{
        width:100%;
        height:240px;
        object-fit:cover;
        background:#000;
        display:block;
      }
      .camera-preview-note{
        font-size:12px;
        color:#6b7280;
        margin-top:8px;
      }
      
      .absen-list{
          overflow:hidden;      /* tidak jalan + tidak scroll */
        }
        .data-absensi .card{
          margin-bottom:10px;
        }
      
        .header{ padding:10px 0; }
          .header-meta{ line-height:1.1; }
          .header-label{ font-size:12px; opacity:.7; }
          .header-value{ font-size:30px; }
          
          @media (display-mode: fullscreen) and (min-width: 1260px) {
            .card-body-absensi {
                min-height: 645px;
            }
            .imaged-scanner {
                height: 200px;
            }
            .aniamed-scanner {
            height: 187px;
            }
        
             }
    </style>

</head>';

if (($row_site['tipe_absen_layar_pegawai'] ?? '') == 'qrcode-webcame') {
    echo '<body onload="qrcode_webcame()">';
} else {
    echo '<body onload="webcame_selfie(); startPreviewFromMainWebcam();">';
}

echo '
<span class="latitude d-none"></span>
<header class="header">
  <div class="container-fluid">
    <div class="row align-items-center">

      <!-- TANGGAL (KIRI) -->
      <div class="col-4 text-left">
        <div class="header-meta">
          <strong class="header-value">' . format_hari_tanggal($date) . '</strong>
        </div>
      </div>

      <!-- LOGO (TENGAH) -->
      <div class="col-4 text-center">
        <div class="logo-header">
          <img src="../sw-content/' . $site_logo . '" style="height:46px; width:auto;">
        </div>
      </div>

      <!-- WAKTU (KANAN) -->
      <div class="col-4 text-right">
        <div class="header-meta">
          <strong class="header-value clock">--:--:--</strong>
        </div>
      </div>

    </div>
  </div>
</header>



<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="section mt-2">
        <div class="container-fluid mb-2">
            <div class="row">

                <div class="col-md-3">

                    <!-- PREVIEW KAMERA (GANTI CAROUSEL) -->
                    <div class="card">
                        <div class="card-body">

                          <div class="camera-preview-head">
                            <!-- <h5 class="m-0">Preview Kamera</h5>  -->
                            <!-- <span class="camera-preview-badge">  
                              Mode: ' . strip_tags($row_site['tipe_absen_layar_pegawai'] ?? 'qrcode') . '
                            </span> -->
                          </div>

                          <div class="camera-preview-wrap">';

// Jika mode qrcode-webcame, tampilkan reader (QR camera)
if (($row_site['tipe_absen_layar_pegawai'] ?? '') == 'qrcode-webcame') {
    echo '
                              <div class="p-2" style="background:#111;">
                                <div id="reader"></div>
                              </div>';
} else {
    // Mode qrcode/rfid: tampilkan video preview (ambil stream dari webcam utama)
    echo '
                              <video id="webcam_preview" autoplay playsinline muted></video>';
}

echo '
                          </div>
                        </div>
                    </div>


                    <div class="card mt-2">
                        <div class="card-body pt-4 pl-4 pr-4 text-center">';

if (($row_site['tipe_absen_layar_pegawai'] ?? '') == 'qrcode') {
    echo '
                                <h3>Cukup scan QR Code dengan mesin scanner dan biarkan wajah Anda tertangkap secara otomatis</h3>
                                <div class="aniamed-scanner">
                                    <img src="data:image/png;base64,' . base64_encode(file_get_contents('../template/img/qr-code.gif')) . '" class="imaged-scanner mt-2 bm-2">
                                    <div class="webcam-screen">
                                        <video id="webcam" autoplay playsinline width="640" height="480" class="s-widodo.com"></video>
                                        <canvas id="canvas" class="s-widodo.com"></canvas>
                                    </div>
                                </div>
                                 <input type="text" name="qrcode" class="form-control qrcode bg-white" required>';
} elseif (($row_site['tipe_absen_layar_pegawai'] ?? '') == 'rfid') {
    echo '<h3>Dekatkan kartu RFID Anda, dan proses verifikasi akan berjalan otomatis</h3>
                                 <div class="aniamed-scanner">
                                    <img src="data:image/png;base64,' . base64_encode(file_get_contents('../template/img/rfid.gif')) . '" class="imaged-scanner mt-2 bm-2">
                                    <div class="webcam-screen">
                                        <video id="webcam" autoplay playsinline width="640" height="480" class="s-widodo.com"></video>
                                        <canvas id="canvas" class="s-widodo.com"></canvas>
                                    </div>
                                </div>
                                 <input type="text" name="qrcode" class="form-control qrcode bg-white" required>';
} else {
    echo '<h3>Arahkan kamera Anda ke QR Code untuk memindai</h3>
                                <div class="webcame text-center">
                                    <div id="reader"></div>
                                </div>';
}

echo '
                        </div>
                    </div>

                </div>



                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body card-body-absensi">
                        <h3>Absensi terbaru</h3>
                        <hr>
                            <!-- <div class="marquee-container">
                                <div class="data-absensi marquee"></div>
                            </div> -->
                            <div class="absen-list" id="absenList">
                              <div class="data-absensi"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="transactions">
                        <div class="row data-counter-left">

                            <div class="col-md-12">
                                <div class="card border-0 mb-2 bg-warning">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,' . base64_encode(file_get_contents('../template/img/icons/003-profile.png')) . '" alt="img" class="image-block imaged w36">
                                                </div>
                                            </div>
                                            <div class="col align-self-center">
                                                <strong class="text-white">Total Pegawai</strong>
                                                <p class="text-white total-pegawai">0</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="card border-0 mb-2 bg-danger">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,' . base64_encode(file_get_contents('../template/img/icons/002-sand-clock.png')) . '" alt="img" class="image-block imaged w36">
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

                            <div class="col-md-12">
                                <div class="card border-0 mb-2 bg-primary">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,' . base64_encode(file_get_contents('../template/img/icons/007-insight.png')) . '" alt="img" class="image-block imaged w36">
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

                            <div class="col-md-12">
                                <div class="card border-0 mb-2 bg-secondary">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,' . base64_encode(file_get_contents('../template/img/icons/005-clipboard.png')) . '" alt="img" class="image-block imaged w36">
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

                            <div class="col-md-12">
                                <div class="card border-0 mb-2 bg-danger">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,' . base64_encode(file_get_contents('../template/img/icons/004-time.png')) . '" alt="img" class="image-block imaged w36">
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

                            <div class="col-md-12">
                                <div class="card border-0 mb-1 bg-info">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-auto pr-0">
                                                <div class="avatar avatar-50 border-0 text-default">
                                                   <img src="data:image/png;base64,' . base64_encode(file_get_contents('../template/img/icons/002-verified.png')) . '" alt="img" class="image-block imaged w36">
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

<footer class="footer" style="display: none;">
    <div class="marquee-left">
        <p>Selamat Datang di ' . $row_site['nama_sekolah'] . ' | Tanggal: ' . format_hari_tanggal($date) . ' | Waktu: <span class="clock"></span></p>
    </div>
</footer>

<div class="appBottomMenu  d-none bg-primary">
    <span class="credits">
        <a class="credits_a" id="mycredit" href="https://s-widodo.com"  target="_blank">S-widodo.com</a>
    </span>
</div>

<script src="../sw-library/bundle.min.php?get=s-widodo.com"></script>
<script src="./sw-script.js"></script>

<script>
// Preview mengambil stream dari video utama (#webcam) jika sudah hidup.
// Ini aman karena kita tidak start kamera dua kali.
function startPreviewFromMainWebcam(){
  try{
    const main = document.getElementById("webcam");
    const prev = document.getElementById("webcam_preview");
    if(!main || !prev) return;

    const attach = () => {
      if (main.srcObject) {
        prev.srcObject = main.srcObject;
      } else {
        // coba ulang sampai main webcam aktif
        setTimeout(attach, 400);
      }
    };
    attach();
  }catch(e){
    console.error("Preview camera error:", e);
  }
}
</script>

</body>
</html>';
?>