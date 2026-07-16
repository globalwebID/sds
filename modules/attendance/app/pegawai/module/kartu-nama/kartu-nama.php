<?php if(empty($connection) AND !isset($_COOKIE['pegawai'])){
    header('location:../404');
}else{
$qrcode = strip_tags($data_user['qrcode']??'');
$filepath = "../sw-content/qrcode/pegawai_$qrcode.png";

if ($qrcode && !file_exists($filepath)) {
    require_once'../sw-library/phpqrcode/qrlib.php'; 
    QRCode::png($qrcode, $filepath, 'QR_ECLEVEL_Q', 10, 1);
}

$query_tema ="SELECT foto FROM kartu_nama WHERE active='Y' LIMIT 1";
$result_tema = $connection->query($query_tema);
$data_tema = $result_tema->fetch_assoc();

echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container s-widodo.com mt-3">
            <h6 class="subtitle text-center mb-4">Kartu Absen Pegawai</h6>
            <div class="row justify-content-md-center s-widodo.com">
                <div class="col-sm-12 col-md-4 text-center s-widodo.com mb-1">
                    
                </div>

                <div class="col-md-4 text-center s-widodo.com">
                    <div class="thema-id-card s-widodo.com" style="background: url(../sw-content/tema/'.$data_tema['foto'].')">
    
                        <div class="qrcode s-widodo.com">';
                         if(file_exists('../sw-content/qrcode/pegawai_'.($data_user['qrcode']??'-').'.png')){
                                echo'<img src="data:image/png;base64,'.base64_encode(file_get_contents('../sw-content/qrcode/pegawai_'.($data_user['qrcode']??'-').'.png')).'"  class="s-widodo.com">';
                            }else{
                                echo'<img src="../sw-content/avatar/avatar.jpg" class="s-widodo.com">';
                            }
                        echo'
                        </div>

                        <div class="description s-widodo.com">
                            <div class="label">
                                <p class="s-widodo.com">'.strip_tags($data_user['nama_lengkap']??'-').'</p>
                                <p class="s-widodo.com">'.strip_tags($data_user['jabatan']??'-').'</p>
                            </div>
                        </div>
                        <div class="description s-widodo.com">
                            <ul>
                                <li style="color:white"><span>NIP</span>: '.$data_user['nip'].'</li>
                                <li style="color:white"><span>Email</span>: '.$data_user['email'].'</li>
                            </ul>
                         </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<div class="btn-floating s-widodo.com">
    <button type="submit" class="btn btn-warning text-white btn-print s-widodo.com"><span class="material-icons s-widodo.com">print</span></button>
</div>';
}?>