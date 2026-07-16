<?php if(empty($connection) AND !isset($_COOKIE['siswa'])){
    header('location:../404');
}else{
$qrcode = ($data_user['nisn']??'');
$filepath = "../sw-content/qrcode/$qrcode.png";
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
            <h6 class="subtitle text-center mb-4">Kartu nama</h6>
            <div class="row justify-content-md-center s-widodo.com">
                <div class="col-sm-12 col-md-4 text-center s-widodo.com mb-1">
                    <div class="thema-id-card s-widodo.com" style="background: url(../sw-content/tema/'.$data_tema['foto'].')">
            
                        <div class="avatar-idcard s-widodo.com">';
                            if(file_exists('../sw-content/avatar/'.$avatarFile)){
                                echo'<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../sw-content/avatar/'.$data_user['avatar'].'')).'" class="s-widodo.com">';
                            }else{
                                echo'<img src="../sw-content/avatar/avatar.jpg" class="s-widodo.com">';
                            }
                        echo'
                        </div>

                        <div class="description s-widodo.com">
                            <div class="label">
                                <p class="s-widodo.com">'.strip_tags($data_user['nama_lengkap']??'-').'</p>
                                <p class="s-widodo.com">Kelas '.strip_tags($data_user['kelas']??'-').'</p>
                            </div>
                        </div>
                        <div class="description s-widodo.com">
                            <ul class="label">
                                <p><span>NISN</span>: '.$data_user['nisn'].'</p>
                                <p><span>Email</span>: '.$data_user['email'].'</p>
                            </ul>
                         </div>
                    </div>
                </div>

                <div class="col-md-4 text-center s-widodo.com">
                    <div class="thema-id-card s-widodo.com" style="background: url(../sw-content/tema/'.$data_tema['foto'].')">
    
                        <div class="qrcode s-widodo.com">';
                         if(file_exists('../sw-content/qrcode/'.($data_user['nisn']??'avatar').'.png')){
                                echo'<img src="data:image/png;base64,'.base64_encode(file_get_contents('../sw-content/qrcode/'.($data_user['nisn']??'avatar.jpg').'.png')).'"  class="s-widodo.com">';
                            }else{
                                echo'<img src="./sw-content/avatar/avatar.jpg" class="s-widodo.com">';
                            }
                        echo'
                        </div>

                        <div class="description s-widodo.com">
                            <div class="label">
                                <p class="s-widodo.com">'.strip_tags($data_user['nama_lengkap']??'-').'</p>
                                <p class="s-widodo.com">Kelas '.strip_tags($data_user['kelas']??'-').'</p>
                            </div>
                        </div>
                        <div class="description s-widodo.com">
                            <ul class="label">
                                <p><span>NISN</span>: '.$data_user['nisn'].'</p>
                                <p><span>Email</span>: '.$data_user['email'].'</p>
                            </ul>
                         </div>

                         <div class="description s-widodo.com">
                         <div class="label">
                            <p>'.$row_site['nama_sekolah'].'</p>
                            <p>Telp. '.$row_site['site_phone'].'</p>
                            </div>
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