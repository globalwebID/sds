<?php if(empty($connection) AND !isset($_COOKIE['wali_murid'])){
    header('location:../404');
}else{

    $query_tema ="SELECT foto FROM kartu_nama WHERE active='Y' LIMIT 1";
    $result_tema = $connection->query($query_tema);
    $data_tema = $result_tema->fetch_assoc();
    $data_siswa = getSiswa($connection, $data_user['nisn']);
echo'
<main class="flex-shrink-0 main has-footer s-widodo.com">
    <div class="main-container s-widodo.com">
        <div class="container s-widodo.com mt-3">
            <h6 class="subtitle text-center mb-4">Kartu nama</h6>
            <div class="row justify-content-md-center s-widodo.com">
                <div class="col-md-4 text-center s-widodo.com mb-1">
                    <div class="thema-id-card s-widodo.com" style="background: url(../sw-content/tema/'.$data_tema['foto'].')">
            
                        <div class="avatar-idcard s-widodo.com">';
                            if(file_exists('../sw-content/avatar/'.$data_siswa['avatar'].'')){
                                echo'<img src="data:image/gif;base64,'.base64_encode(file_get_contents('../sw-content/avatar/'.$data_siswa['avatar'].'')).'" class="s-widodo.com">';
                            }else{
                                echo'<img src="../sw-content/avatar/avatar.jpg" class="s-widodo.com">';
                            }
                        echo'
                        </div>

                        <div class="description s-widodo.com">
                            <div class="label">
                                <p class="s-widodo.com">'.strip_tags($data_siswa['nama_lengkap']??'-').'</p>
                                <p class="s-widodo.com">Kelas '.strip_tags($data_siswa['kelas']??'-').'</p>
                            </div>
                        </div>
                        <div class="description s-widodo.com">
                            <ul>
                                <li><span>NISN</span>: '.$data_siswa['nisn'].'</li>
                                <li><span>Email</span>: '.($data_siswa['email']??'-').'</li>
                            </ul>
                         </div>
                    </div>
                </div>

                <div class="col-md-4 text-center s-widodo.com">
                    <div class="thema-id-card s-widodo.com" style="background: url(../sw-content/tema/'.$data_tema['foto'].')">
    
                        <div class="qrcode s-widodo.com">';
                         if(file_exists('../sw-content/qrcode/'.($data_siswa['nisn']??'avatar').'.png')){
                                echo'<img src="data:image/png;base64,'.base64_encode(file_get_contents('../sw-content/qrcode/'.($data_siswa['nisn']??'avatar.jpg').'.png')).'"  class="s-widodo.com">';
                            }else{
                                echo'<img src="./sw-content/avatar/avatar.jpg" class="s-widodo.com">';
                            }
                        echo'
                        </div>

                        <div class="description s-widodo.com">
                            <div class="label">
                                <p class="s-widodo.com">'.strip_tags($data_siswa['nama_lengkap']??'-').'</p>
                                <p class="s-widodo.com">Kelas '.strip_tags($data_siswa['kelas']??'-').'</p>
                            </div>
                        </div>
                        <div class="description s-widodo.com">
                            <ul>
                                <li><span>NISN</span>: '.$data_siswa['nisn'].'</li>
                                <li><span>Email</span>: '.($data_siswa['email']??'-').'</li>
                            </ul>
                         </div>

                         <div class="description s-widodo.com">
                            <p>'.$row_site['nama_sekolah'].'</p>
                            <p>Telp. '.$row_site['site_phone'].'</p>
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